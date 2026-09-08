<?php

namespace App\Jobs;

use Modules\Video\Models\Video;
use Modules\Video\Models\VideoStreamContentMapping;
use Modules\Subscriptions\Models\Plan;
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

class ImportVideosJob implements ShouldQueue
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
     * Process duration - convert numeric minutes to HH:MM format
     */
    private function processDuration($duration)
    {
        if (empty($duration)) {
            return null;
        }
        
        // If it's already in HH:MM format, return as is
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $duration)) {
            return $duration;
        }
        
        // If it's numeric (minutes), convert to HH:MM:SS
        if (is_numeric($duration)) {
            $hours = floor($duration / 60);
            $minutes = $duration % 60;
            return sprintf('%02d:%02d:00', $hours, $minutes);
        }
        
        return $duration;
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
            return ['success' => false, 'errors' => ['File not found']];
        }
        
        try {
            $rows = collect();
            if (in_array($extension, ['csv'])) {
                // Read CSV raw to preserve empty values
                $headers = [];
                $rows = collect();
                if (($handle = fopen($fullPath, 'r')) !== false) {
                    if (($headers = fgetcsv($handle)) !== false) {
                        $rowLimit = 0;
                        while (($dataRow = fgetcsv($handle)) !== false && $rowLimit < 100) {
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
                    // Ensure both arrays have the same length
                    $minLength = min(count($headers), count($row));
                    $headersSlice = array_slice($headers, 0, $minLength);
                    $rowSlice = array_slice($row, 0, $minLength);
                    return array_combine($headersSlice, $rowSlice);
                });
            }
            
            // Check if required headers exist
            $requiredHeaders = ['Name', 'Access', 'Duration'];
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
                    'errors' => ['This file is missing required video headers: ' . implode(', ', $missingHeaders) . '. Please use a proper video import file.']
                ];
            }
            
            // Validate first 10 rows
            $rowsToValidate = $rows->take(10);
            $errorCount = 0;
            $displayRow = 2; // header row is 1
            foreach ($rowsToValidate as $rowIndex => $row) {
                $rowErrors = [];
                
                // Check required fields
                if (!isset($row['Name']) || trim((string)$row['Name']) === '') {
                    $rowErrors[] = __('messages.name_required');
                }
                
                // Check for duplicate video names
                if (!empty($row['Name']) && Video::where('name', $row['Name'])->exists()) {
                    $rowErrors[] = __('messages.video_name_already_exists', ['name' => $row['Name']]);
                }

                if(empty($row['Description'])) {
                    $rowErrors[] = __('messages.description_required');
                }
                if(empty($row['Duration'])) {
                    $rowErrors[] = __('messages.duration_required');
                }
                if(empty($row['Release Date'])) {
                    $rowErrors[] = __('messages.release_date_required');
                }
                if(empty($row['Access'])) {
                    $rowErrors[] = __('messages.access_required');
                }
                if(empty($row['Status'])) {
                    $rowErrors[] = __('messages.status_required');
                }
                if(empty($row['Video Upload Type'])) {
                    $rowErrors[] = __('messages.video_upload_type_required');
                }
                if(!empty($row['Video Upload Type']) && empty($row['Video URL'])) {
                    $rowErrors[] = __('messages.video_url_required');
                }

                // Validate Access field
                if (!empty($row['Access']) && !in_array(strtolower(trim((string)$row['Access'])), ['free','pay-per-view', 'paid'])) {
                    $rowErrors[] = __('messages.access_must_be_valid');
                }
                
                // Validate Status field
                if (!empty($row['Status']) && !in_array(strtolower($row['Status']), ['active', 'inactive', '1', '0'])) {
                    $rowErrors[] = __('messages.status_must_be_active_or_inactive');
                }
                
                // Validate Video Upload Type
                if (!empty($row['Video Upload Type']) && !in_array($row['Video Upload Type'], ['URL', 'Local', 'Embedded', 'YouTube', 'HLS', 'Vimeo', 'x265'])) {
                    $rowErrors[] = __('messages.video_upload_type_must_be_valid');
                }
                
                // Validate Duration format
                if (!empty($row['Duration'])) {
                    $duration = $row['Duration'];
                    // Check if it's in HH:MM:SS format or numeric minutes
                    if (!preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $duration) && !is_numeric($duration)) {
                        $rowErrors[] = __('messages.duration_must_be_in_hh_mm_ss_format_or_numeric_minutes');
                    }
                }
                
                // Validate Release Date
                if (!empty($row['Release Date']) && !strtotime($row['Release Date'])) {
                    $rowErrors[] = __('messages.release_date_must_be_valid');
                }
                
                // Validate Plan existence
                if (!empty($row['Plan ID'])) {
                    $plan = Plan::where('id', $row['Plan ID'])->first();
                    if (!$plan) {
                        $availablePlans = Plan::pluck('id')->implode(', ');
                        $rowErrors[] = "Plan '{$row['Plan ID']}' does not exist. Available plans: {$availablePlans}";
                    }
                }
                
                // Validate Quality Type if Quality is provided
                if (!empty($row['Quality']) && !empty($row['Quality Type'])) {
                    if (!in_array($row['Quality Type'], ['URL', 'Local', 'Embedded', 'YouTube', 'HLS', 'Vimeo', 'x265'])) {
                        $rowErrors[] = 'Quality Type must be URL, Local, Embedded, YouTube, HLS, Vimeo, or x265';
                    }
                }
                
                if (!empty($rowErrors)) {
                    $errors[] = "Row " . ($displayRow) . ": " . implode('; ', $rowErrors);
                    $errorCount++;
                }
                $displayRow++;
                
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
                'errors' => ['Validation error: ' . $e->getMessage()]
            ];
        }
    }

    public function handle()
    {
        \Log::info("=== ImportVideosJob STARTED ===");
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
                    'Name', 'Description', 'Access', 'Plan', 'Duration', 'Release Date', 'Poster URL', 'Poster TV URL', 'Video Upload Type', 'Video URL', 'Quality', 'Quality Type', 'Quality URL', 'Status'
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
            'Name', 'Description', 'Access', 'Plan ID', 'Duration', 'Release Date', 'Poster URL', 'Poster TV URL', 'Video Upload Type', 'Video URL', 'Quality', 'Quality Type', 'Quality URL', 'Status'
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
                
                $rowErrors = [];
                
                // Validation
                if (empty($data['Name'])) $rowErrors[] = __('messages.name_required');
                if (Video::where('name', $data['Name'])->exists()) {
                    $rowErrors[] = __('messages.video_already_exists');
                }
                if (!empty($data['Plan ID'])) {
                    $plan = Plan::where('id', $data['Plan ID'])->first();
                    if (!$plan) {
                        $rowErrors[] = __('messages.plan_not_found');
                    }
                }
                if (!empty($data['Access']) && !in_array(strtolower($data['Access']), ['free', 'premium', 'pay-per-view', 'paid'])) {
                    $rowErrors[] = __('messages.access_must_be_valid');
                }
                if (!empty($data['Status']) && !in_array(strtolower($data['Status']), ['active', 'inactive', '1', '0'])) {
                    $rowErrors[] = __('messages.status_must_be_active_or_inactive');
                }
                if (!empty($data['Video Upload Type']) && !in_array($data['Video Upload Type'], ['URL', 'Local', 'Embedded', 'YouTube', 'HLS', 'Vimeo', 'x265'])) {
                    $rowErrors[] = __('messages.video_upload_type_must_be_valid');
                }

                if(empty($data['Description'])) {
                    $rowErrors[] = __('messages.description_required');
                }
                if(empty($data['Duration'])) {
                    $rowErrors[] = __('messages.duration_required');
                }
                if(empty($data['Release Date'])) {
                    $rowErrors[] = __('messages.release_date_required');
                }
                if(empty($data['Access'])) {
                    $rowErrors[] = __('messages.access_required');
                }
                if(empty($data['Status'])) {
                    $rowErrors[] = __('messages.status_required');
                }
                if(empty($data['Video Upload Type'])) {
                    $rowErrors[] = __('messages.video_upload_type_required');
                }
                if(!empty($data['Video Upload Type']) && empty($data['Video URL'])) {
                    $rowErrors[] = __('messages.video_url_required');
                }

                // If there are any validation errors for this row, record and skip
                if (!empty($rowErrors)) {
                    \Log::error("Validation failed for '{$data['Name']}': " . implode('; ', $rowErrors));
                    $errorRows[] = array_merge($data, ['Errors' => implode('; ', $rowErrors)]);
                    continue;
                }
                
                try {
                    DB::beginTransaction();
                    
                    // Get plan ID
                    $plan = null;
                    if (!empty($data['Plan ID'])) {
                        $plan = Plan::where('id', $data['Plan ID'])->first();
                    }
                    
                    // Process poster URLs
                    $posterUrl = $this->handleImageUrl($data['Poster URL'] ?? null, 'video', $data['Name'] ?? null, 'poster');
                    $posterTvUrl = $this->handleImageUrl($data['Poster TV URL'] ?? null, 'video', $data['Name'] ?? null, 'poster_tv');
                    
                    // Validate that we have local image paths, not external URLs
                    if ($posterUrl && filter_var($posterUrl, FILTER_VALIDATE_URL)) {
                        \Log::warning("Poster URL is still external, setting to null: " . $posterUrl);
                        $posterUrl = null;
                    }
                    if ($posterTvUrl && filter_var($posterTvUrl, FILTER_VALIDATE_URL)) {
                        \Log::warning("Poster TV URL is still external, setting to null: " . $posterTvUrl);
                        $posterTvUrl = null;
                    }
                    
                    $videoData = [
                        'name' => $data['Name'],
                        'description' => $data['Description'] ?? null,
                        'access' => $data['Access'] ?? 'free',
                        'plan_id' => $plan ? $plan->id : null,
                        'duration' => $this->processDuration($data['Duration'] ?? null),
                        'release_date' => !empty($data['Release Date']) ? date('Y-m-d', strtotime($data['Release Date'])) : null,
                        'poster_url' => $posterUrl,
                        'poster_tv_url' => $posterTvUrl,
                        'video_upload_type' => $data['Video Upload Type'] ?? 'URL',
                        'video_url_input' => $data['Video URL'] ?? null,
                        'type' => 'video',
                        'status' => $this->convertStatusToBoolean($data['Status'] ?? 'active'),
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                    ];
                    
                    $newVideo = Video::create($videoData);
                    
                    // Create video stream content mapping if quality data is provided
                    if (!empty($data['Quality']) && !empty($data['Quality Type']) && !empty($data['Quality URL'])) {
                        $streamData = [
                            'video_id' => $newVideo->id,
                            'type' => $data['Quality Type'],
                            'quality' => $data['Quality'],
                            'url' => $data['Quality URL'],
                            'created_by' => $this->userId,
                            'updated_by' => $this->userId,
                        ];
                        
                        VideoStreamContentMapping::create($streamData);
                    }
                    
                    DB::commit();
                    $success++;
                    \Log::info("Successfully created video: {$data['Name']} (ID: {$newVideo->id})");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    \Log::error("Failed to create video '{$data['Name']}': " . $e->getMessage());
                    $errorRows[] = array_merge($data, ['Errors' => __('messages.video_creation_failed') . ': ' . $e->getMessage()]);
                }
            }
        }

        $errorFile = null;
        if ($failed > 0) {
            $errorFileName = 'import_errors_videos_' . time() . '.csv';
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

        \Log::info("Success: {$success}, Failed: {$failed}, Total: {$totalRecords}");
        
        // 🟢 Final cleanup of any remaining temp files
        $this->cleanupTempDirectory();
        
        try {
            \Log::info("Completion email sent to: {$user->email}");
        } catch (\Exception $e) {
            \Log::error('Failed to send videos import completion email: ' . $e->getMessage());
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
    private function handleImageUrl($imageUrl, $folder = 'video', $videoName = null, $type = null)
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
            
            if ($videoName) {
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $videoName));
                $cleanName = preg_replace('/\s+/', '_', trim($cleanName));
                $filename = $cleanName . '_' . $type . '.' . $extension;  
            }else{
                $filename = 'video' . '_' . $type . '.' . $extension;
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
                Storage::disk('public')->deleteDirectory($tempDirectory);
              
            } else {
                \Log::info("🟢 No temp directory to clean up");
            }
        } catch (\Exception $e) {
            \Log::error("❌ Error cleaning up temp directory: " . $e->getMessage());
        }
    }
}
