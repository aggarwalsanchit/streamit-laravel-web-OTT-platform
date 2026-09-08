<?php

namespace App\Jobs;

use Modules\Genres\Models\Genres;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\CsvImportHelper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportGenresJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CsvImportHelper;

    protected $filePath;
    protected $userId;
    protected $batchSize = 100;

    public function __construct($filePath, $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    /**
     * Validate import data before processing
     */
    public static function validateImportData($filePath)
    {
        $errors = [];
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fullPath = storage_path('app/' . $filePath);
        
        if (!file_exists($fullPath)) {
            return ['success' => false, 'errors' => [__('messages.file_not_found')]];
        }
        
        try {
            $rows = collect();
            if (in_array($extension, ['csv'])) {
                 $headers = [];
                $rows = collect();
                if (($handle = fopen($fullPath, 'r')) !== false) {
                    if (($headers = fgetcsv($handle)) !== false) {
                        $rowLimit = 0;
                        while (($dataRow = fgetcsv($handle)) !== false && $rowLimit < 100) { // read up to 50 for safety
                            $minLength = min(count($headers), count($dataRow));
                            $headersSlice = array_slice($headers, 0, $minLength);
                            $rowSlice = array_slice($dataRow, 0, $minLength);
                            $rows->push(array_combine($headersSlice, $rowSlice));
                            $rowLimit++;
                        }
                    }
                    fclose($handle);
                }
            } else {
                $rows = collect(Excel::toArray(new \stdClass(), $fullPath)[0]);
                $headers = $rows->shift();
                $rows = $rows->map(function ($row) use ($headers) {
                    $minLength = min(count($headers), count($row));
                    $headersSlice = array_slice($headers, 0, $minLength);
                    $rowSlice = array_slice($row, 0, $minLength);
                    return array_combine($headersSlice, $rowSlice);
                });
            }
            
            // Check if required headers exist
            $requiredHeaders = ['Name'];
            $missingHeaders = [];
            $firstRow = $rows->first();
            
            if ($firstRow) {
                foreach ($requiredHeaders as $header) {
                    if (!isset($firstRow[$header])) {
                        $missingHeaders[] = $header;
                    }
                }
            }
            
            if (!empty($missingHeaders)) {
                return [
                    'success' => false,
                    'errors' => [__('messages.missing_required_genre_headers', ['headers' => implode(', ', $missingHeaders)])]
                ];
            }
            
            // Validate first 10 rows
            $rowsToValidate = $rows->take(10);
            $errorCount = 0;
            foreach ($rowsToValidate as $rowIndex => $row) {
                $rowErrors = [];
                $rowNumber = $rowIndex + 2; // Actual row number in file (header is row 1, first data row is row 2)
                
                // Check required fields
                if (empty($row['Name'])) {
                    $rowErrors[] = __('messages.name_required');
                }
                if (!isset($row['Description']) || trim((string)$row['Description']) === '') {
                    $rowErrors[] = __('messages.description_required');
                }
                if (!isset($row['Status']) || trim((string)$row['Status']) === '') {
                    $rowErrors[] = __('messages.status_required');
                }
                if (!isset($row['Image URL']) || trim((string)$row['Image URL']) === '') {
                    $rowErrors[] = __('messages.image_url_required');
                }
                
                // Check for duplicate genre names
                if (!empty($row['Name']) && Genres::where('name', $row['Name'])->exists()) {
                    $rowErrors[] = __('messages.genre_already_exists', ['name' => $row['Name']]);
                }
                
                // Validate Status field
                if (!empty($row['Status']) && !in_array(strtolower($row['Status']), ['active', 'inactive', '1', '0'])) {
                    $rowErrors[] = __('messages.status_must_be_active_or_inactive');
                }
                
                // Validate Image URL field (optional)
                if (!empty($row['Image URL']) && !filter_var($row['Image URL'], FILTER_VALIDATE_URL)) {
                    $rowErrors[] = __('messages.invalid_url_format');
                }
                
                if (!empty($rowErrors)) {
                    $errors[] = "Row {$rowNumber}: " . implode('; ', $rowErrors);
                    $errorCount++;
                }
                
                // Stop after 5 errors to avoid overwhelming the user
                if ($errorCount >= 5) {
                    $errors[] = __('messages.more_errors_showing_first');
                    break;
                }
            }
            
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'errors' => $errors
                ];
            }
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => [__('messages.validation_error', ['error' => $e->getMessage()])]
            ];
        }
    }

    public function handle()
    {
        \Log::info("=== ImportGenresJob STARTED ===");
        \Log::info("File path: {$this->filePath}");
        \Log::info("User ID: {$this->userId}");
        
        $user = User::find($this->userId);
        if (!$user) {
            \Log::error("User not found with ID: {$this->userId}");
            return;
        }
        
        \Log::info("User found: {$user->name} ({$user->email})");

        $extension = pathinfo($this->filePath, PATHINFO_EXTENSION);
        $fullPath = storage_path('app/' . $this->filePath);
        \Log::info("File extension: {$extension}");
        \Log::info("Full path: {$fullPath}");
        \Log::info("File exists: " . (file_exists($fullPath) ? 'YES' : 'NO'));
        
        $rows = [];
        
        if (in_array($extension, ['csv'])) {
            \Log::info("Processing CSV file...");
            try {
                \Log::info("Calling processCsvFile...");
                $rows = collect($this->processCsvFile($fullPath, [
                    'Name', 'Description', 'Status', 'Image URL'
                ]));
                \Log::info("CSV processing completed. Rows count: " . $rows->count());
            } catch (\Exception $e) {
                \Log::error("CSV processing error: " . $e->getMessage());
                return;
            }
        } else {
            \Log::info("Processing Excel file...");
            $excelData = Excel::toArray(new \stdClass(), $fullPath);
            $rows = collect($excelData[0] ?? []);
            \Log::info("Excel processing completed. Rows count: " . $rows->count());
        }

        if ($rows->isEmpty()) {
            \Log::error("No data found in file");
            return;
        }
        
        \Log::info("Data found, proceeding with processing...");

        $requiredHeader = [
            'Name', 'Description', 'Status', 'Image URL'
        ];
        
        $header = $rows->first();
        \Log::info("CSV Header: " . json_encode($header));
        \Log::info("Required Header: " . json_encode($requiredHeader));
        \Log::info("Header count: " . count($header) . ", Required count: " . count($requiredHeader));
        
        // Check if required headers exist
        $requiredHeaders = ['Name'];
        $missingHeaders = [];
        
        if ($header && is_array($header)) {
            $headerValues = array_values($header);
            foreach ($requiredHeaders as $reqHeader) {
                if (!in_array($reqHeader, $headerValues)) {
                    $missingHeaders[] = $reqHeader;
                }
            }
        }
        
        if (!empty($missingHeaders)) {
            \Log::error("Missing required headers: " . implode(', ', $missingHeaders));
            return;
        }
        
        $rows = $rows->skip(1);
        $success = 0;
        $failed = 0;
        $errorRows = [];
        $totalRecords = $rows->count();
        
        \Log::info("Total records to process: {$totalRecords}");

        foreach ($rows->chunk($this->batchSize) as $batchIndex => $batch) {
            \Log::info("Processing batch " . ($batchIndex + 1) . " with " . count($batch) . " records");
            foreach ($batch as $rowIndex => $row) {
                \Log::info("Processing row " . ($rowIndex + 1) . " in batch " . ($batchIndex + 1));
                
                // Ensure both arrays have the same length
                $minLength = min(count($requiredHeader), count($row));
                $headerSlice = array_slice($requiredHeader, 0, $minLength);
                $rowSlice = array_slice($row, 0, $minLength);
                
                \Log::info("Row data length: " . count($row) . ", Header length: " . count($requiredHeader) . ", Min length: " . $minLength);
                \Log::info("Row slice: " . json_encode($rowSlice));
                \Log::info("Header slice: " . json_encode($headerSlice));
                
                $data = array_combine($headerSlice, $rowSlice);
                
                \Log::info("Processing row data: " . json_encode($data));
                
                $rowErrors = [];
                
                // Validation
                if (empty($data['Name'])) $rowErrors[] = __('messages.name_required');
                if (Genres::where('name', $data['Name'])->exists()) {
                    $rowErrors[] = __('messages.genre_already_exists', ['name' => $data['Name']]);
                }
                if (!empty($data['Status']) && !in_array(strtolower($data['Status']), ['active', 'inactive', '1', '0'])) {
                    $rowErrors[] = __('messages.status_must_be_active_or_inactive');
                }
                if (!empty($data['Image URL']) && !filter_var($data['Image URL'], FILTER_VALIDATE_URL)) {
                    $rowErrors[] = __('messages.invalid_url_format');
                }
                
                if (!empty($rowErrors)) {
                    $failed++;
                    \Log::error("Validation failed for '{$data['Name']}': " . implode('; ', $rowErrors));
                    $errorRows[] = array_merge($data, ['Errors' => implode('; ', $rowErrors)]);
                    continue;
                }
                
                try {
                    DB::beginTransaction();
                    
                    // Process image URL
                    $imageUrl = $data['Image URL'] ?? null;
                    \Log::info("Processing image URL for genre '{$data['Name']}': " . ($imageUrl ?: 'NULL'));
                    
                    $fileUrl = $this->handleImageUrl($imageUrl, 'genres', $data['Name'] ?? null, 'image');
                    \Log::info("Image processing result for '{$data['Name']}': " . ($fileUrl ?: 'NULL'));
                    
                    $genreData = [
                        'name' => $data['Name'],
                        'description' => $data['Description'] ?? null,
                        'status' => $this->convertStatusToBoolean($data['Status'] ?? 'active'),
                        'file_url' => $fileUrl,
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                    ];
                    
                    $newGenre = Genres::create($genreData);
                    
                    DB::commit();
                    $success++;
                    \Log::info("Successfully created genre: {$data['Name']} (ID: {$newGenre->id})");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    \Log::error("Failed to create genre '{$data['Name']}': " . $e->getMessage());
                    $errorRows[] = array_merge($data, ['Errors' => __('messages.genre_creation_failed') . ': ' . $e->getMessage()]);
                }
            }
        }

        $errorFile = null;
        if ($failed > 0) {
            $errorFileName = 'import_errors_genres_' . time() . '.csv';
            $errorFilePath = 'public/import_errors/' . $errorFileName;
            $errorHeader = array_merge($requiredHeader, ['Errors']);
            $errorContent = [implode(',', $errorHeader)];
            foreach ($errorRows as $row) {
                $errorContent[] = implode(',', array_map(function($v) { return '"'.str_replace('"','""',$v).'"'; }, $row));
            }
            Storage::put($errorFilePath, implode("\n", $errorContent));
            $errorFile = Storage::url($errorFilePath);
        }

        // Determine status based on success/failure rates
        $status = 'success';
        if ($totalRecords > 0) {
            if ($success == 0 && $failed > 0) {
                $status = 'failed';
            } elseif ($success > 0 && $failed > 0) {
                $status = 'partial';
            } elseif ($success == 0 && $failed == 0) {
                $status = 'no_data';
            }
        } else {
            $status = 'no_data';
        }

        \Log::info("=== ImportGenresJob COMPLETED ===");
        \Log::info("Success: {$success}, Failed: {$failed}, Total: {$totalRecords}");
        
        // 🟢 Final cleanup of any remaining temp files
        $this->cleanupTempDirectory();
        
        try {
            \Log::info("Completion email sent to: {$user->email}");
        } catch (\Exception $e) {
            \Log::error('Failed to send genres import completion email: ' . $e->getMessage());
        }
    }

    private function handleImageUrl($imageUrl, $folder = 'genres', $genreName = null, $type = null)
    {
      
        if (empty($imageUrl)) {
            \Log::info("Empty image URL provided, skipping image download");
            return null;
        }

        \Log::info("Processing image URL: {$imageUrl}");
        
        // Skip if it's already a local path (starts with / or doesn't contain http)
        if (!str_starts_with($imageUrl, 'http')) {
            \Log::info("Image URL is already local, returning filename: {$imageUrl}");
            return basename($imageUrl); // Return just the filename
        }

        try {
            // Validate URL
            if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                \Log::warning("Invalid image URL provided: {$imageUrl}");
                return null;
            }

            // Convert Unsplash Plus URLs to regular Unsplash URLs
            $originalUrl = $imageUrl;
            \Log::info("Original URL: {$originalUrl}");
            $imageUrl = $this->convertUnsplashUrl($imageUrl);
            
            if ($originalUrl !== $imageUrl) {
                \Log::info("Converted Unsplash Plus URL: {$originalUrl} -> {$imageUrl}");
            }


            \Log::info("Attempting to download image", ['url' => $imageUrl]);

            // Set headers to mimic a browser request
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'DNT' => '1',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ])->timeout(30)->get($imageUrl);
            
            \Log::info("HTTP response status: " . $response->status());
            
            if (!$response->successful()) {
                \Log::warning("Failed to download image from URL: {$imageUrl}. Status: " . $response->status());
                
                // Try alternative URL formats for Unsplash
                if (strpos($imageUrl, 'images.unsplash.com') !== false) {
                    $alternativeUrl = $this->tryAlternativeUnsplashUrl($originalUrl);
                    if ($alternativeUrl && $alternativeUrl !== $imageUrl) {
                        \Log::info("Trying alternative Unsplash URL: {$alternativeUrl}");
                        $response = Http::withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                            'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
                        ])->timeout(30)->get($alternativeUrl);
                        
                        if ($response->successful()) {
                            \Log::info("Alternative URL successful, proceeding with download");
                            $imageUrl = $alternativeUrl;
                        } else {
                            \Log::warning("Alternative URL also failed with status: " . $response->status());
                            // Return null if image download fails
                            return null;
                        }
                    } else {
                        // Return null if image download fails
                        return null;
                    }
                } else {
                    // Return null if image download fails
                    return null;
                }
            }

            // Validate that it's actually an image
            $imageInfo = getimagesizefromstring($response->body());
            if ($imageInfo === false) {
                \Log::warning("Downloaded content is not a valid image: {$imageUrl}");
                // Return null if image validation fails
                return null;
            }

            \Log::info("Image validation successful: {$imageInfo[0]}x{$imageInfo[1]} pixels, size: " . strlen($response->body()) . " bytes");

            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg'; // Default extension
            }
            
         
            if ($genreName) {
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $genreName));
                $cleanName = preg_replace('/\s+/', '_', trim($cleanName));
                $filename = $cleanName . '_' . $type . '.' . $extension;
            }else{
                $filename = 'genre'.time() . '_' . $type . '.' . $extension;
            }
            
            $tempDirectory = "temp/image";
            if (!Storage::disk('public')->exists($tempDirectory)) {
                Storage::disk('public')->makeDirectory($tempDirectory);
            }
            
            // Save to temp location
            $tempPath = $tempDirectory . '/' . $filename;
            $saved = Storage::disk('public')->put($tempPath, $response->body());
            
            if ($saved) {
                $tempUrl = url("storage/temp/image/{$filename}");
                $processedFileName = extractFileNameFromUrl($tempUrl, $folder);
                \Log::info("🟢 extractFileNameFromUrl returned: {$processedFileName}");
                
                Storage::disk('public')->delete($tempPath);
                return $processedFileName; // return final filename
            } else {
                \Log::error("❌ Failed to save temp image: {$tempPath}");
                return null;
            }

        } catch (\Exception $e) {
            \Log::error("Error downloading image from URL {$imageUrl}: " . $e->getMessage());
            return null;
        }
    }
    private function convertStatusToBoolean($status)
    {
        if (is_numeric($status)) {
            return (bool) $status;
        }
        
        return strtolower($status) === 'active' || strtolower($status) === '1';
    }

    /**
     * Convert Unsplash Plus URLs to regular Unsplash URLs
     */
    private function convertUnsplashUrl($url)
    {
        // Convert Unsplash Plus URLs to regular Unsplash URLs
        if (strpos($url, 'plus.unsplash.com') !== false) {
            // Simply replace the domain to make it accessible
            return str_replace('plus.unsplash.com', 'images.unsplash.com', $url);
        }
        
        return $url;
    }

    /**
     * Try alternative Unsplash URL formats
     */
    private function tryAlternativeUnsplashUrl($originalUrl)
    {
        // Extract photo ID from the original URL
        if (preg_match('/premium_photo-([a-zA-Z0-9]+)/', $originalUrl, $matches)) {
            $photoId = $matches[1];
            
            // Try different Unsplash URL formats
            $alternatives = [
                "https://images.unsplash.com/photo-{$photoId}?ixlib=rb-4.1.0&auto=format&fit=crop&w=800&q=80",
                "https://images.unsplash.com/photo-{$photoId}?ixlib=rb-4.1.0&auto=format&fit=crop&w=1200&q=80",
                "https://images.unsplash.com/photo-{$photoId}?ixlib=rb-4.1.0&auto=format&fit=crop&w=1920&q=80",
                "https://images.unsplash.com/photo-{$photoId}?w=800&h=600&fit=crop&crop=center",
                "https://images.unsplash.com/photo-{$photoId}?w=1200&h=800&fit=crop&crop=center",
            ];
            
            return $alternatives[0]; // Return the first alternative to try
        }
        
        return null;
    }

    /**
     * Optimized cleanup of temporary directory
     */
    private function cleanupTempDirectory()
    {
        try {
            $tempDirectory = "temp/image";
            if (Storage::disk('public')->exists($tempDirectory)) {
                // Get all files in temp directory
                $files = Storage::disk('public')->allFiles($tempDirectory);
                
                if (!empty($files)) {
                    // Delete all files in temp directory
                    Storage::disk('public')->delete($files);
                    \Log::info("🟢 Final cleanup: " . count($files) . " temp files removed");
                }
                
                // Remove the temp directory itself
                Storage::disk('public')->deleteDirectory($tempDirectory);               
            } else {
                \Log::info("🟢 No temp directory to clean up");
            }
        } catch (\Exception $e) {
            \Log::error("❌ Error cleaning up temp directory: " . $e->getMessage());
        }
    }
}