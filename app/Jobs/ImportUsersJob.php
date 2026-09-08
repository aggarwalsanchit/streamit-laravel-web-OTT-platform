<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\CsvImportHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class ImportUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CsvImportHelper;

    protected $filePath;
    protected $userId;
    protected $batchSize = 500;

    public function __construct($filePath, $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    /**
     * Validate import data and return errors if any
     */
    public static function validateImportData($filePath)
    {
        $errors = [];
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fullPath = storage_path('app/' . $filePath);

        if (!file_exists($fullPath)) {
            return [
                'success' => false,
                'message' => __('messages.validation_errors_found'),
                'errors' => ['File not found']
            ];
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
                    return [
                        'success' => false,
                        'message' => __('messages.validation_errors_found'),
                        'errors' => ['Error reading file: Empty CSV']
                    ];
                }
                // Normalize headers exactly as-is to keep mapping
                $rowIndex = 2; // header is row 1
                while (($csvRow = fgetcsv($handle)) !== false) {
                    // Keep exact column count with array_pad
                    if (count($csvRow) < count($headers)) {
                        $csvRow = array_pad($csvRow, count($headers), '');
                    }
                    $row = array_combine($headers, array_slice($csvRow, 0, count($headers)));
                    // Detect fully blank rows
                    $allBlank = true;
                    foreach ($row as $cell) {
                        if (trim((string)$cell) !== '') { $allBlank = false; break; }
                    }
                    if ($allBlank) { $rowIndex++; continue; }
                    $row['_csvRowNumber'] = $rowIndex;
                    $rows->push($row);
                    $rowIndex++;
                }
                fclose($handle);
            } else {
                $excelData = Excel::toArray(new \stdClass(), $fullPath)[0];
                $headers = array_shift($excelData);
                $rows = collect();
                foreach ($excelData as $excelIndex => $excelRow) {
                    // Check if row is empty/blank
                    $isEmpty = true;
                    foreach ($excelRow as $cell) {
                        if (!empty(trim($cell ?? ''))) {
                            $isEmpty = false;
                            break;
                        }
                    }
                    
                    if ($isEmpty) continue; // Skip blank rows
                    
                    $minLength = min(count($headers), count($excelRow));
                    $headersSlice = array_slice($headers, 0, $minLength);
                    $rowSlice = array_slice($excelRow, 0, $minLength);
                    $row = array_combine($headersSlice, $rowSlice);
                    $row['_csvRowNumber'] = $excelIndex + 2; // +2 because index starts at 0 after removing header
                    $rows->push($row);
                }
            }

            $requiredHeaders = ['First Name', 'Last Name', 'Email', 'Mobile', 'Gender', 'Password', 'Date Of Birth', 'Address'];
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
                    'errors' => ['This file is missing required user headers: ' . implode(', ', $missingHeaders) . '. Please use a proper user import file.']
                ];
            }

            // Check for duplicate users and validate data
            $duplicateErrors = [];
            $validationErrors = [];
            $errorCount = 0;
            foreach ($rows as $index => $row) {
                // Use stored CSV row number if available, otherwise calculate from index
                $rowNumber = $row['_csvRowNumber'] ?? ($index + 2);
                $rowErrors = [];

                // Basic validation for users
                if (!isset($row['First Name']) || trim((string)$row['First Name']) === '') {
                    $rowErrors[] = __('messages.first_name_required');
                }
                
                if (!isset($row['Last Name']) || trim((string)$row['Last Name']) === '') {
                    $rowErrors[] = __('messages.last_name_required'); 
                }
                
                if (!isset($row['Email']) || trim((string)$row['Email']) === '') {
                    $rowErrors[] = __('messages.email_required');
                } elseif (!filter_var($row['Email'], FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = __('messages.email_format_invalid');
                } elseif (User::where('email', $row['Email'])->exists()) {
                    $rowErrors[] = __('messages.user_already_exists', ['name' => $row['First Name'] . ' ' . $row['Last Name']]);
                }
                
                if (!isset($row['Mobile']) || trim((string)$row['Mobile']) === '') {
                    $rowErrors[] = __('messages.mobile_required');
                } elseif (!is_numeric($row['Mobile'])) {
                    $rowErrors[] = __('messages.mobile_must_be_numeric');
                }
                
                if (!isset($row['Password']) || trim((string)$row['Password']) === '') {
                    $rowErrors[] = __('messages.password_required');
                } elseif (strlen($row['Password']) < 8) {
                    $rowErrors[] = __('messages.password_must_be_at_least_8_characters');
                }
                
                if (!isset($row['Date Of Birth']) || trim((string)$row['Date Of Birth']) === '') {
                    $rowErrors[] = __('messages.date_of_birth_required');
                } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $row['Date Of Birth'])) {
                    $rowErrors[] = __('messages.date_of_birth_must_be_in_yyyy_mm_dd_format');
                }
                
                if (!isset($row['Gender']) || trim((string)$row['Gender']) === '') {
                    $rowErrors[] = __('messages.gender_required');
                } elseif (!in_array(strtolower($row['Gender']), ['male', 'female', 'other'])) {
                    $rowErrors[] = __('messages.gender_must_be_male_female_or_other');
                }
                
                $fileUrlCell = isset($row['File URL']) ? trim((string)$row['File URL']) : '';
                if ($fileUrlCell === '') {
                    $rowErrors[] = __('messages.file_url_required');
                } elseif (!filter_var($fileUrlCell, FILTER_VALIDATE_URL)) {
                    $rowErrors[] = __('messages.file_url_must_be_a_valid_url');
                }

                if (!empty($rowErrors)) {
                    $validationErrors[] = "Row {$rowNumber}: " . implode(', ', $rowErrors);
                    $errorCount++;
                }
                
                // Stop after 5 errors to avoid overwhelming the user
                if ($errorCount >= 5) {
                    $validationErrors[] = __('messages.more_errors_showing_first');
                    break;
                }
            }

            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'message' => __('messages.validation_errors_found'),
                    'errors' => $validationErrors
                ];
            }

            return ['success' => true, 'errors' => []];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => __('messages.validation_errors_found'),
                'errors' => ['Error reading file: ' . $e->getMessage()]
            ];
        }
    }

    public function handle()
    {
        \Log::info('ImportUsersJob started', ['filePath' => $this->filePath, 'userId' => $this->userId]);
        
        $uploader = User::find($this->userId);
        if (!$uploader) {
            \Log::error('Uploader not found', ['userId' => $this->userId]);
            return;
        }

        

        $extension = pathinfo($this->filePath, PATHINFO_EXTENSION);
        $fullPath = storage_path('app/' . $this->filePath);
        \Log::info('Processing file', ['extension' => $extension, 'fullPath' => $fullPath]);
        
        $rows = [];
        if (in_array(strtolower($extension), ['csv'])) {
            try {
                // Use the helper trait to clean CSV data and remove blank columns
                $rows = collect($this->processCsvFile($fullPath, [
                    'First Name', 'Last Name', 'Email', 'Mobile', 'File URL', 'Gender', 'Password', 'Date Of Birth', 'Address' 
                ]));
                \Log::info('CSV processed', ['rowCount' => $rows->count()]);
            } catch (\Exception $e) {
                \Log::error('CSV processing failed', ['error' => $e->getMessage()]);
                return;
            }
        } else {
            $rows = collect(Excel::toArray([], $fullPath)[0]);
            \Log::info('Excel processed', ['rowCount' => $rows->count()]);
        }

        if ($rows->isEmpty()) {
            return;
        }

        $requiredHeader = ['First Name', 'Last Name', 'Email', 'Mobile', 'File URL', 'Gender', 'Password', 'Date Of Birth', 'Address'];
        $header = $rows->first();
        $rows = $rows->skip(1);
        $success = 0;
        $failed = 0;
        $errorRows = [];
        $totalRecords = $rows->count();

        foreach ($rows->chunk($this->batchSize) as $batch) {
            foreach ($batch as $row) {
                // Row is already cleaned by the helper trait, no need for additional processing
                $data = array_combine($requiredHeader, $row);
                $rowErrors = [];
                $fileUrl = $this->handleImageUrl($data['File URL'] ?? null, 'users', $data['First Name'] ?? null, 'image'); 
                
                // Basic validation
                if (empty($data['First Name'])) $rowErrors[] = __('messages.first_name_required');
                if (empty($data['Last Name'])) $rowErrors[] = __('messages.last_name_required');
                if (empty($data['Email']) || !filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) $rowErrors[] = __('messages.valid_email_required');
                if (User::where('email', $data['Email'])->exists()) $rowErrors[] = __('messages.user_already_exists', ['name' => $data['First Name'] . ' ' . $data['Last Name']]);
                if (empty($data['Mobile']) || !is_numeric($data['Mobile'])) $rowErrors[] = __('messages.contact_number_numeric');
                if (empty($data['Password']) || strlen($data['Password']) < 8) $rowErrors[] = __('messages.password_min_length');
                if (empty($data['Date Of Birth']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['Date Of Birth'])) $rowErrors[] = __('messages.date_format_invalid');
                if (empty($data['Gender']) || !in_array(strtolower($data['Gender']), ['male','female','other'])) $rowErrors[] = __('messages.gender_invalid');
                
                // File URL validation (optional)
                $fileUrlInput = isset($data['File URL']) ? trim((string)$data['File URL']) : '';
                if ($fileUrlInput === '') {
                    $rowErrors[] = __('messages.file_url_required');
                } elseif (!filter_var($fileUrlInput, FILTER_VALIDATE_URL)) {
                    $rowErrors[] = __('messages.invalid_url_format');
                }
                // If URL provided but image download/validation failed, surface error
                if ($fileUrlInput !== '' && $fileUrl === null) {
                    $rowErrors[] = __('messages.invalid_url_format');
                }

                if (!empty($rowErrors)) {
                    $failed++;
                    $errorRows[] = array_merge($data, ['Errors' => implode('; ', $rowErrors)]);
                    continue;
                }

                try {
                    $userData = [
                        'first_name' => $data['First Name'],
                        'last_name' => $data['Last Name'],
                        'email' => $data['Email'],
                        'mobile' => $data['Mobile'],
                        'password' => Hash::make($data['Password']),
                        'date_of_birth' => $data['Date Of Birth'],
                        'address' => $data['Address'],
                        'gender' => strtolower($data['Gender']),
                        'user_type' => 'user',
                        'status' => 1,
                    ];
                    
                    // Add file URL if provided
                    if (!empty($data['File URL'])) {
                        $userData['file_url'] = $fileUrl;
                    }
                    
                    $newUser = User::create($userData);
                    $newUser->assignRole('user');
                    $success++;
                } catch (\Exception $e) {
                    $failed++;
                    $errorRows[] = array_merge($data, ['Errors' => __('messages.user_creation_failed') . ': ' . $e->getMessage()]);
                }
            }
        }

        $errorFile = null;
        if ($failed > 0) {
            $errorFileName = 'import_errors_users_' . time() . '.csv';
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
                $status = 'no_data'; // No records processed
            }
        } else {
            $status = 'no_data'; // No records in file
        }
        
        \Log::info('ImportUsersJob completed', [
            'success' => $success,
            'failed' => $failed,
            'totalRecords' => $totalRecords,
            'status' => $status
        ]);
        
        // 🟢 Final cleanup of any remaining temp files
        $this->cleanupTempDirectory();
    }
     /**
     * Handle image URL by downloading and saving the image
     */
    private function handleImageUrl($imageUrl, $folder = 'image', $userFirstName = null, $type = null)    
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

            // Generate unique filename with imported_ prefix
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg'; // Default extension
            }
            
            if ($userFirstName) {
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $userFirstName));
                $cleanName = preg_replace('/\s+/', '_', trim($cleanName));
                $filename = $cleanName . '_' . $type . '.' . $extension;
            }else{
                $filename = 'user'.time() . '_' . $type . '.' . $extension;
            }
            
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
