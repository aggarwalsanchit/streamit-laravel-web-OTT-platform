<?php

namespace App\Jobs;

use Modules\CastCrew\Models\CastCrew;
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

class ImportCastCrewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CsvImportHelper;

    protected $filePath;
    protected $userId;
    protected $type;
    protected $batchSize = 100;

    public function __construct($filePath, $userId, $type)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->type = $type;
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
                // Raw CSV reader to preserve empty cells
                if (($handle = fopen($fullPath, 'r')) === false) {
                    throw new \RuntimeException('Unable to open CSV');
                }
                $headers = fgetcsv($handle);
                if ($headers === false) {
                    fclose($handle);
                    return ['success' => false, 'errors' => ['Error reading file: Empty CSV']];
                }
                $rowIndex = 2;
                while (($csvRow = fgetcsv($handle)) !== false) {
                    if (count($csvRow) < count($headers)) {
                        $csvRow = array_pad($csvRow, count($headers), '');
                    }
                    $row = array_combine($headers, array_slice($csvRow, 0, count($headers)));
                    $allBlank = true;
                    foreach ($row as $cell) { if (trim((string)$cell) !== '') { $allBlank = false; break; } }
                    if ($allBlank) { $rowIndex++; continue; }
                    $row['_csvRowNumber'] = $rowIndex;
                    $rows->push($row);
                    $rowIndex++;
                }
                fclose($handle);
            } else {
                $rows = collect(Excel::toArray(new \stdClass(), $fullPath)[0]);
                $headers = $rows->shift();
                $rows = $rows->map(function ($row) use ($headers) {
                    $minLength = min(count($headers), count($row));
                    $headersSlice = array_slice($headers, 0, $minLength);
                    $rowSlice = array_slice($row, 0, $minLength);
                    $assoc = array_combine($headersSlice, $rowSlice);
                    return $assoc;
                });
            }

            $requiredHeaders = ['Name', 'Type'];
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
                    'errors' => ['This file is missing required cast/crew headers: ' . implode(', ', $missingHeaders) . '. Please use a proper cast/crew import file.']
                ];
            }

            $rowsToValidate = $rows->take(10);
            $errorCount = 0;
            foreach ($rowsToValidate as $rowIndex => $row) {
                $rowErrors = [];

                if (!isset($row['Name']) || trim((string)$row['Name']) === '') {
                    $rowErrors[] = __('messages.name_required');
                }

                $typeKey = strtolower($row['Type']) === 'actor' ? 'actor_type' : 'director_type';
                if (!empty($row['Name']) && CastCrew::where('name', $row['Name'])->where('type', strtolower($row['Type']))->whereNull('deleted_at')->exists()) {
                    $rowErrors[] = __('messages.castcrew_already_exists', ['name' => $row['Name'], 'type' => __("messages.{$typeKey}")]);
                }

                if (!empty($row['Type']) && !in_array(strtolower($row['Type']), ['actor', 'director'])) {
                    $rowErrors[] = __('messages.type_must_be_actor_or_director');
                }
                if (!empty($row['Image URL']) && !filter_var($row['Image URL'], FILTER_VALIDATE_URL)) {
                    $rowErrors[] = __('messages.image_url_must_be_a_valid_url');
                }
                
                if (empty($row['Date of Birth'])) {
                    $rowErrors[] = __('messages.date_of_birth_required');
                }

                if (empty($row['Bio'])) {
                    $rowErrors[] = __('messages.bio_required_for_castcrew', ['type' => __("messages.{$typeKey}")]);
                }

                if (empty($row['Place of Birth'])) {
                    $rowErrors[] = __('messages.place_of_birth_required');
                }

                if (!empty($row['Date of Birth']) && !strtotime($row['Date of Birth'])) {
                    $rowErrors[] = __('messages.date_of_birth_must_be_valid');
                }

                if (!empty($rowErrors)) {
                    $displayRow = $row['_csvRowNumber'] ?? ($rowIndex + 2);
                    $errors[] = "Row " . $displayRow . ": " . implode('; ', $rowErrors);
                    $errorCount++;
                }
                
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
                'errors' => ['Validation error: ' . $e->getMessage()]
            ];
        }
    }

    public function handle()
    {
        \Log::info("=== ImportCastCrewJob STARTED ===");
        \Log::info("File path: {$this->filePath}");
        \Log::info("User ID: {$this->userId}");
        \Log::info("Type: {$this->type}");
        
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
                $rows = collect();
                if (($handle = fopen($fullPath, 'r')) === false) {
                    \Log::error('Unable to open CSV');
                    return;
                }
                $headers = fgetcsv($handle);
                if ($headers === false) {
                    fclose($handle);
                    \Log::error('Empty CSV');
                    return;
                }
                $rows->push($headers);
                while (($csvRow = fgetcsv($handle)) !== false) {
                    if (count($csvRow) < count($headers)) {
                        $csvRow = array_pad($csvRow, count($headers), '');
                    }
                    $rows->push(array_slice($csvRow, 0, count($headers)));
                }
                fclose($handle);
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
            'Name', 'Type', 'Bio', 'Place of Birth', 'Date of Birth', 'Designation', 'Image URL'
        ];
        
        $header = $rows->first();
        \Log::info("CSV Header: " . json_encode($header));
        \Log::info("Required Header: " . json_encode($requiredHeader));
        \Log::info("Header count: " . count($header) . ", Required count: " . count($requiredHeader));
        
        $dataRows = $rows->skip(1); // Skip header row
        $success = 0;
        $failed = 0;
        $errorRows = [];
        $totalRecords = $dataRows->count();
        
        \Log::info("Total records to process: {$totalRecords}");
        
        // Check if ALL records already exist - if so, show error
        $allRecordsExist = true;
        $existingRecords = [];
        foreach ($dataRows as $row) {
            $data = array_combine($requiredHeader, $row);
            if (!empty($data['Name']) && !empty($data['Type'])) {
                $exists = CastCrew::where('name', $data['Name'])->where('type', strtolower($data['Type']))->whereNull('deleted_at')->exists();
                if (!$exists) {
                    $allRecordsExist = false;
                    break;
                } else {
                    $existingRecords[] = $data['Name'];
                }
            }
        }
        
        if ($allRecordsExist && !empty($existingRecords)) {
            \Log::error("All records already exist in database");
            // Create error file with all existing records
            $errorFileName = 'import_errors_all_exist_' . time() . '.csv';
            $errorFilePath = 'public/import_errors/' . $errorFileName;
            $errorHeader = array_merge($requiredHeader, ['Errors']);
            $errorContent = [implode(',', $errorHeader)];
            foreach ($dataRows as $row) {
                $data = array_combine($requiredHeader, $row);
                $errorContent[] = implode(',', array_map(function($v) { return '"'.str_replace('"','""',$v).'"'; }, array_merge($data, ['Errors' => 'Record already exists in database'])));
            }
            Storage::put($errorFilePath, implode("\n", $errorContent));
            $errorFile = Storage::url($errorFilePath);
            
            try {
                \Log::info("Completion email sent to: {$user->email}");
            } catch (\Exception $e) {
                \Log::error('Failed to send cast & crew import completion email: ' . $e->getMessage());
            }
            return; // Exit without processing
        }

        foreach ($dataRows->chunk($this->batchSize) as $batchIndex => $batch) {
            \Log::info("Processing batch " . ($batchIndex + 1) . " with " . count($batch) . " records");
            foreach ($batch as $rowIndex => $row) {
                \Log::info("Processing row " . ($rowIndex + 1) . " in batch " . ($batchIndex + 1));
                $data = array_combine($requiredHeader, $row);
                
                // Debug: Log the data array to see what we're getting
                \Log::info("Processing row data: " . json_encode($data));
                
                $rowErrors = [];
                
                // Validation
                if (!isset($data['Name']) || trim((string)$data['Name']) === '') $rowErrors[] = __('messages.name_required');
                if (!empty($data['Type']) && !in_array(strtolower($data['Type']), ['actor', 'director'])) {
                    $rowErrors[] = __('messages.type_must_be_actor_or_director');
                }
                if (!isset($data['Date of Birth']) || trim((string)$data['Date of Birth']) === '') {
                    $rowErrors[] = __('messages.date_of_birth_required');
                } elseif (!strtotime($data['Date of Birth'])) {
                    $rowErrors[] = __('messages.date_of_birth_must_be_valid');
                }
                if (!isset($data['Bio']) || trim((string)$data['Bio']) === '') {
                    $rowErrors[] = __('messages.bio_required_for_castcrew');
                }
                if (!isset($data['Place of Birth']) || trim((string)$data['Place of Birth']) === '') {
                    $rowErrors[] = __('messages.place_of_birth_required');
                }
                if (!empty($data['Image URL']) && !filter_var($data['Image URL'], FILTER_VALIDATE_URL)) {
                    $rowErrors[] = __('messages.image_url_must_be_a_valid_url');
                }
                
                if (!empty($rowErrors)) {
                    $failed++;
                    \Log::error("Validation failed for '{$data['Name']}': " . implode('; ', $rowErrors));
                    $errorRows[] = array_merge($data, ['Errors' => implode('; ', $rowErrors)]);
                    continue;
                }
                
                if (CastCrew::where('name', $data['Name'])->where('type', strtolower($data['Type']))->whereNull('deleted_at')->exists()) {
                    \Log::info("Skipping existing record: {$data['Name']} ({$data['Type']}) - already exists in database");
                    continue; 
                }
                
                try {
                    DB::beginTransaction();
                    
                    $imageUrl = $this->handleImageUrl($data['Image URL'] ?? null, 'castcrew', $data['Name'] ?? null, $data['Type']);   
                    
                    if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        \Log::warning("Image URL is still external, setting to null: " . $imageUrl);
                        $imageUrl = null;
                    }
                    
                    $castCrewData = [
                        'name' => $data['Name'],
                        'type' => strtolower($data['Type']), // Use the type from CSV data (actor/director)
                        'bio' => $data['Bio'] ?? null,
                        'place_of_birth' => $data['Place of Birth'] ?? null,
                        'dob' => !empty($data['Date of Birth']) ? date('Y-m-d', strtotime($data['Date of Birth'])) : null,
                        'designation' => $data['Designation'] ?? null,
                        'file_url' => $imageUrl,
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                    ];
                    
                    $newCastCrew = CastCrew::create($castCrewData);
                    
                    DB::commit();
                    $success++;
                    \Log::info("Successfully created cast & crew: {$data['Name']} (ID: {$newCastCrew->id})");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    \Log::error("Failed to create cast & crew '{$data['Name']}': " . $e->getMessage());
                    $errorRows[] = array_merge($data, ['Errors' => __('messages.castcrew_creation_failed') . ': ' . $e->getMessage()]);
                }
            }
        }

        $errorFile = null;
        if ($failed > 0) {
            $errorFileName = 'import_errors_castcrew_' . time() . '.csv';
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

        \Log::info("=== ImportCastCrewJob COMPLETED ===");
        \Log::info("Success: {$success}, Failed: {$failed}, Total: {$totalRecords}");
        
        // 🟢 Final cleanup of any remaining temp files
        $this->cleanupTempDirectory();
        
        try {
            \Log::info("Completion email sent to: {$user->email}");
        } catch (\Exception $e) {
            \Log::error('Failed to send cast & crew import completion email: ' . $e->getMessage());
        }
    }

    /**
     * Handle image URL by downloading and saving the image
     */
    private function handleImageUrl($imageUrl, $folder = 'castcrew', $castCrewName = null, $type = null)
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
                
            if ($castCrewName) {
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $castCrewName));
                $cleanName = preg_replace('/\s+/', '_', trim($cleanName));
                $filename = $cleanName . '_' . $type . '.' . $extension;
            }else{
                $filename = 'castcrew'.time() . '_' . $type . '.' . $extension;
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
                            return null;
                        }
                    } else {
                        return null;
                    }
                } else {
                    return null;
                }
            }

            // Validate that it's actually an image
            $imageInfo = getimagesizefromstring($response->body());
            if ($imageInfo === false) {
                \Log::warning("Downloaded content is not a valid image: {$imageUrl}");
                return null;
            }

            \Log::info("Image validation successful: {$imageInfo[0]}x{$imageInfo[1]} pixels, size: " . strlen($response->body()) . " bytes");

            \Log::info("✅ Image downloaded successfully, creating optimized temp storage");
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
                Storage::disk('public')->delete($tempPath);
                \Log::info("✅ Processed image successfully: {$processedFileName}");
                return $processedFileName; // return final filename
            } else {
                \Log::error("❌ Failed to save temp image: {$tempPath}");
                return null;
            }

        } catch (\Exception $e) {
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
