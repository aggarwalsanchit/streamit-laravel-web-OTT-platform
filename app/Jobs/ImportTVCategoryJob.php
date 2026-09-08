<?php

namespace App\Jobs;

use Modules\LiveTV\Models\LiveTvCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\CsvImportHelper;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportTVCategoryJob implements ShouldQueue
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

    public static function validateImportData($filePath)
    {
        $errors = [];
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fullPath = storage_path('app/' . $filePath);

        if (!file_exists($fullPath)) {
            return ['success' => false, 'errors' => ['File not found']];
        }

        try {
            $rows = collect();
            if (in_array($extension, ['csv'])) {
                // Read CSV file directly without using processCsvFile to avoid header validation
                $csvData = [];
                if (($handle = fopen($fullPath, 'r')) !== FALSE) {
                    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                        $csvData[] = $data;
                    }
                    fclose($handle);
                }

                $headers = $csvData[0] ?? [];
                $rows = collect($csvData)->slice(1)->map(function ($row) use ($headers) {
                    $minLength = min(count($headers), count($row));
                    $headersSlice = array_slice($headers, 0, $minLength);
                    $rowSlice = array_slice($row, 0, $minLength);
                    return array_combine($headersSlice, $rowSlice);
                });
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

            $requiredHeaders = ['Name', 'Status'];
            $missingHeaders = [];
            $firstRow = $rows->first();

            if ($firstRow) {
                // Get all headers in lowercase for case-insensitive comparison
                $availableHeaders = array_map('strtolower', array_keys($firstRow));
                \Log::info("Available headers in file: " . json_encode(array_keys($firstRow)));
                \Log::info("Required headers: " . json_encode($requiredHeaders));
                
                foreach ($requiredHeaders as $header) {
                    if (!in_array(strtolower($header), $availableHeaders)) {
                        $missingHeaders[] = $header;
                    }
                }
            }

            if (!empty($missingHeaders)) {
                return [
                    'success' => false,
                    'errors' => ['This file is missing required TV category headers: ' . implode(', ', $missingHeaders) . '. Please use a proper TV category import file.']
                ];
            }

            // Row validations (duplicates + required fields)
            $errorsList = [];
            $errorCount = 0;
            $displayRow = 2; // header row is 1
            foreach ($rows as $row) {
                $rowErrors = [];
                // Required fields
                if (!isset($row['Name']) || trim((string)$row['Name']) === '') {
                    $rowErrors[] = __('messages.name_required');
                }
                if(empty($row['Description'])) {
                    $rowErrors[] = __('messages.description_required');
                }
                if (!isset($row['Status']) || trim((string)$row['Status']) === '') {
                    $rowErrors[] = __('messages.status_required');
                } elseif (!in_array(strtolower($row['Status']), ['active','inactive','1','0'])) {
                    $rowErrors[] = __('messages.status_must_be_active_or_inactive');
                }
                // Duplicate by name
                if (!empty($row['Name']) && LiveTvCategory::where('name', $row['Name'])->exists()) {
                    $rowErrors[] = __('messages.tv_category_already_exists', ['name' => $row['Name']]);
                }
                if (!empty($rowErrors)) {
                    $errorsList[] = "Row {$displayRow}: " . implode('; ', $rowErrors);
                    $errorCount++;
                }
                if ($errorCount >= 5) {
                    $errorsList[] = __('messages.more_errors_showing_first');
                    break;
                }
                $displayRow++;
            }

            if (!empty($errorsList)) {
                return [
                    'success' => false,
                    'errors' => $errorsList
                ];
            }

            return ['success' => true, 'errors' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'errors' => ['Error reading file: ' . $e->getMessage()]];
        }
    }

    public function handle()
    {
        \Log::info("=== ImportTVCategoryJob STARTED ===");
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
                // Use the helper trait to clean CSV data and remove blank columns
                \Log::info("Calling processCsvFile...");
                $rows = collect($this->processCsvFile($fullPath, [
                    'Name', 'Description', 'File URL', 'Status'
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
            'Name', 'Description', 'File URL', 'Status'
        ];
        
        $header = $rows->first();
        \Log::info("CSV Header: " . json_encode($header));
        \Log::info("Required Header: " . json_encode($requiredHeader));
        \Log::info("Header count: " . count($header) . ", Required count: " . count($requiredHeader));
        
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
                $data = array_combine($requiredHeader, $row);
                
                // Debug: Log the data array to see what we're getting
                \Log::info("Processing row data: " . json_encode($data));
                \Log::info("File URL from CSV: " . ($data['File URL'] ?? 'NOT_FOUND'));
                
                // Check if the data array has the correct keys
                if (!isset($data['File URL'])) {
                    \Log::error("Missing file URL key in data array. Available keys: " . implode(', ', array_keys($data)));
                }
                
                $rowErrors = [];
                
                // Validation
                if (empty($data['Name'])) $rowErrors[] = __('messages.name_required');
                if (LiveTvCategory::where('name', $data['Name'])->exists()) {
                    $rowErrors[] = __('messages.tv_category_already_exists', ['name' => $data['Name']]);
                }
                if (!empty($data['Status']) && !in_array(strtolower($data['Status']), ['active', 'inactive', '1', '0'])) {
                    $rowErrors[] = __('messages.status_must_be_active_or_inactive');
                }
                
                if (!empty($rowErrors)) {
                    $failed++;
                    \Log::error("Validation failed for '{$data['Name']}': " . implode('; ', $rowErrors));
                    $errorRows[] = array_merge($data, ['Errors' => implode('; ', $rowErrors)]);
                    continue;
                }
                
                try {
                    DB::beginTransaction();
                    
                    // Process file URL
                    \Log::info("About to call handleImageUrl for file: " . ($data['File URL'] ?? 'NULL'));
                    $fileUrl = $this->handleImageUrl($data['File URL'] ?? null, 'livetv');
                    
                    \Log::info("File URL processing result: " . ($fileUrl ?: 'NULL'));
                    
                    // Validate that we have local image paths, not external URLs
                    if ($fileUrl && filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                        \Log::warning("File URL is still external, setting to null: " . $fileUrl);
                        $fileUrl = null;
                    }
                    
                    $categoryData = [
                        'name' => $data['Name'],
                        'description' => $data['Description'] ?? null,
                        'file_url' => $fileUrl,
                        'status' => $this->convertStatusToBoolean($data['Status'] ?? 'active'),
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                    ];
                    
                    $newCategory = LiveTvCategory::create($categoryData);
                    
                    DB::commit();
                    $success++;
                    \Log::info("Successfully created TV category: {$data['Name']} (ID: {$newCategory->id})");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    \Log::error("Failed to create TV category '{$data['Name']}': " . $e->getMessage());
                    $errorRows[] = array_merge($data, ['Errors' => __('messages.tv_category_creation_failed') . ': ' . $e->getMessage()]);
                }
            }
        }

        $errorFile = null;
        if ($failed > 0) {
            $errorFileName = 'import_errors_tv_categories_' . time() . '.csv';
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

        \Log::info("=== ImportTVCategoryJob COMPLETED ===");
        \Log::info("Success: {$success}, Failed: {$failed}, Total: {$totalRecords}");
        
       
        $this->cleanupTempDirectory();
        
        try {
            \Log::info("Completion email sent to: {$user->email}");
        } catch (\Exception $e) {
            \Log::error('Failed to send TV category import completion email: ' . $e->getMessage());
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
     * Handle image URL by downloading and saving the image
     */
    private function handleImageUrl($imageUrl, $folder = 'livetv', $categoryName = null, $type = null)
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
            $imageUrl = $this->convertUnsplashUrl($imageUrl);
            
            if ($originalUrl !== $imageUrl) {
                \Log::info("Converted Unsplash Plus URL: {$originalUrl} -> {$imageUrl}");
            }

            // Generate unique filename
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg'; // Default extension
            }
            
            if ($categoryName) {
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $categoryName));
                $cleanName = preg_replace('/\s+/', '_', trim($cleanName));
                $filename = $cleanName . '_' . $type . '.' . $extension;
            }else{
                $filename = 'tvcategory'.time() . '_' . $type . '.' . $extension;
            }
         

            \Log::info("Attempting to download image from: {$imageUrl}");

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
                return null;
            }

            // Validate that it's actually an image
            $imageInfo = getimagesizefromstring($response->body());
            if ($imageInfo === false) {
                \Log::warning("Downloaded content is not a valid image: {$imageUrl}");
                return null;
            }

            \Log::info("Image validation successful: {$imageInfo[0]}x{$imageInfo[1]} pixels, size: " . strlen($response->body()) . " bytes");

            // 🟢 Create optimized temporary storage for extractFileNameFromUrl
            \Log::info("✅ Image downloaded successfully, creating optimized temp storage");
            
            // Create temp directory (optimized structure)
            $tempDirectory = "temp/image";
            if (!Storage::disk('public')->exists($tempDirectory)) {
                Storage::disk('public')->makeDirectory($tempDirectory);
            }
            
            // Save to temp location
            $tempPath = $tempDirectory . '/' . $filename;
            $saved = Storage::disk('public')->put($tempPath, $response->body());
            
            if ($saved) {
                // Create temp URL that extractFileNameFromUrl can process
                $tempUrl = url("storage/temp/image/{$filename}");
                $processedFileName = extractFileNameFromUrl($tempUrl, $folder);
                \Log::info("🟢 extractFileNameFromUrl returned: {$processedFileName}");
                
                // Clean up temp file immediately after processing
                Storage::disk('public')->delete($tempPath);
                \Log::info("🟢 Temp file cleaned up immediately: {$tempPath}");
                
                \Log::info("✅ Processed image successfully: {$processedFileName}");
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
                \Log::info("🟢 Temp directory removed: {$tempDirectory}");
            } else {
                \Log::info("🟢 No temp directory to clean up");
            }
        } catch (\Exception $e) {
            \Log::error("❌ Error cleaning up temp directory: " . $e->getMessage());
        }
    }
}
