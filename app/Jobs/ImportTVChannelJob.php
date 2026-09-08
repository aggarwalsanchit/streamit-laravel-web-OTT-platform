<?php

namespace App\Jobs;

use Modules\LiveTV\Models\LiveTvChannel;
use Modules\LiveTV\Models\LiveTvCategory;
use Modules\LiveTV\Models\TvChannelStreamContentMapping;
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

class ImportTVChannelJob implements ShouldQueue
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
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fullPath = storage_path('app/' . $filePath);

        if (!file_exists($fullPath)) {
            return ['success' => false, 'errors' => [__('messages.file_not_found')]];
        }

        try {
            // Read CSV/Excel preserving empties
            if (in_array($extension, ['csv'])) {
                $headers = [];
                $rows = collect();
                if (($handle = fopen($fullPath, 'r')) !== false) {
                    if (($headers = fgetcsv($handle)) !== false) {
                        while (($dataRow = fgetcsv($handle)) !== false) {
                            $minLength = min(count($headers), count($dataRow));
                            $headersSlice = array_slice($headers, 0, $minLength);
                            $rowSlice = array_slice($dataRow, 0, $minLength);
                            $rows->push(array_combine($headersSlice, $rowSlice));
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

            // Header presence (basic)
            $requiredHeaders = ['Name', 'Category', 'Description', 'Access', 'Status'];
            $missing = [];
            if ($rows->isNotEmpty()) {
                $available = array_map(function($h){ return strtolower(trim($h)); }, array_keys($rows->first()));
                foreach ($requiredHeaders as $h) {
                    if (!in_array(strtolower(trim($h)), $available)) $missing[] = $h;
                }
            }
            if (!empty($missing)) {
                return [
                    'success' => false,
                    'errors' => ['This file is missing required TV channel headers: ' . implode(', ', $missing) . '. Please use a proper TV channel import file.']
                ];
            }

            // Row validations
            $errorsList = [];
            $errorCount = 0;
            $displayRow = 2; // header is row 1
            foreach ($rows as $row) {
                $rowErrors = [];
                if (!isset($row['Name']) || trim((string)$row['Name']) === '') $rowErrors[] = __('messages.name_required');
                if (!isset($row['Category']) || trim((string)$row['Category']) === '') {
                    $rowErrors[] = __('messages.category_required');
                } else if (!LiveTvCategory::where('name', $row['Category'])->exists()) {
                    $rowErrors[] = __('messages.category_not_found');
                }
                if (!isset($row['Description']) || trim((string)$row['Description']) === '') $rowErrors[] = __('messages.description_required');
                if (!isset($row['Access']) || trim((string)$row['Access']) === '') {
                    $rowErrors[] = __('messages.access_required');
                } else if (!in_array(strtolower(trim((string)$row['Access'])), ['free','paid','pay-per-view'])) {
                    $rowErrors[] = __('messages.access_must_be_valid');
                }
                if (!isset($row['Status']) || trim((string)$row['Status']) === '') {
                    $rowErrors[] = __('messages.status_required');
                } else if (!in_array(strtolower($row['Status']), ['active','inactive','1','0'])) {
                    $rowErrors[] = __('messages.status_must_be_active_or_inactive');
                }
                // Type-specific validations
                $typeValue = strtolower(trim((string)($row['Type'] ?? '')));
                $streamType = strtolower(trim((string)($row['Stream Type'] ?? '')));
                // Backward-compat: if Type column is missing, infer from Stream Type
                if ($typeValue === '') {
                    if (in_array($streamType, ['t_embedded','embedded'])) {
                        $typeValue = 't_embedded';
                    } elseif ($streamType !== '') {
                        $typeValue = 't_url';
                    }
                }
                $server = $row['Server URL'] ?? '';
                $embed = $row['Embedded'] ?? '';

                if ($typeValue === 't_embedded') {
                    if (trim((string)$embed) === '') {
                        $rowErrors[] = __('messages.embedded_required');
                    }
                } elseif ($typeValue === 't_url') {
                    // Require stream type and server URL; embedded must be empty
                    if ($streamType === '' || !in_array($streamType, ['hls','rtmp','dash','url'])) {
                        $rowErrors[] = __('messages.stream_type_must_be_valid');
                    }
                    if (trim((string)$server) === '') {
                        $rowErrors[] = __('messages.server_url_required');
                    }
                    if (trim((string)$embed) !== '') {
                        $rowErrors[] = __('messages.embedded_must_be_empty');
                    }
                } else {
                    // If type unknown, try best-effort: require either server or embed
                    if (trim((string)$server) === '' && trim((string)$embed) === '') {
                        $rowErrors[] = __('messages.server_url_required');
                    }
                }
                // Duplicate by name
                if (!empty($row['Name']) && LiveTvChannel::where('name', $row['Name'])->exists()) {
                    $rowErrors[] = __('messages.tv_channel_already_exists', ['name' => $row['Name']]);
                }

                if (!empty($rowErrors)) {
                    $errorsList[] = "Row {$displayRow}: " . implode('; ', $rowErrors);
                    $errorCount++;
                }
                if ($errorCount >= 5) { $errorsList[] = __('messages.more_errors_showing_first'); break; }
                $displayRow++;
            }

                if (!empty($errorsList)) {
                return ['success' => false, 'errors' => $errorsList];
            }

            return ['success' => true, 'errors' => []];
        } catch (\Exception $e) {
            return ['success' => false, 'errors' => [__('messages.validation_error', ['error' => $e->getMessage()])]];
        }
    }

    public function handle()
    {
        \Log::info("=== ImportTVChannelJob STARTED ===");
        \Log::info("File path: {$this->filePath}");
        \Log::info("User ID: {$this->userId}");
        
        $user = User::find($this->userId);
        if (!$user) {
            \Log::error("User not found with ID: {$this->userId}");
            return;
        }
        
        \Log::info("User found: {$user->name} ({$user->email})");

        // Create import log entry
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
                    'Name', 'Category', 'Description', 'Access', 'Plan ID', 'Poster URL', 'Poster TV URL', 'Type', 'Stream Type', 'Server URL', 'Server URL1', 'Embedded', 'Status'
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
            'Name', 'Category', 'Description', 'Access', 'Plan ID', 'Poster URL', 'Poster TV URL', 'Type', 'Stream Type', 'Server URL', 'Server URL1', 'Embedded', 'Status'
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
                if (LiveTvChannel::where('name', $data['Name'])->exists()) {
                    $rowErrors[] = __('messages.tv_channel_already_exists', ['name' => $data['Name']]);
                }
                if (empty($data['Category'])) $rowErrors[] = __('messages.category_required');
                if (!empty($data['Category'])) {
                    $category = LiveTvCategory::where('name', $data['Category'])->first();
                    if (!$category) {
                        $rowErrors[] = __('messages.category_not_found');
                    }
                }
                if (!empty($data['Access']) && !in_array(strtolower($data['Access']), ['free', 'premium', 'pay-per-view', 'paid'])) {
                    $rowErrors[] = __('messages.access_must_be_valid');
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
                    
                    // Get or create category
                    $category = LiveTvCategory::where('name', $data['Category'])->first();
                    $plan = null;
                    \Log::info("Plan: ============= " . $data['Plan ID']);
                    if (!empty($data['Plan ID'])) {
                        $plan = Plan::where('id', $data['Plan ID'])->first();
                    }
                    
                    // Process poster URLs
                    $posterUrl = $this->handleImageUrl($data['Poster URL'] ?? null, 'livetv', $data['Name'] ?? null, 'poster');
                    $posterTvUrl = $this->handleImageUrl($data['Poster TV URL'] ?? null, 'livetv', $data['Name'] ?? null, 'poster_tv');
                    
                    // Validate that we have local image paths, not external URLs
                    if ($posterUrl && filter_var($posterUrl, FILTER_VALIDATE_URL)) {
                        \Log::warning("Poster URL is still external, setting to null: " . $posterUrl);
                        $posterUrl = null;
                    }
                    if ($posterTvUrl && filter_var($posterTvUrl, FILTER_VALIDATE_URL)) {
                        \Log::warning("Poster TV URL is still external, setting to null: " . $posterTvUrl);
                        $posterTvUrl = null;
                    }
                    
                    $channelData = [
                        'name' => $data['Name'],
                        'category_id' => $category->id,
                        'description' => $data['Description'] ?? null,
                        'type' => $data['Type'] ?? 't_url',
                        'access' => $data['Access'] ?? 'free',
                        'plan_id' => $plan ? $plan->id : null,
                        'poster_url' => $posterUrl,
                        'poster_tv_url' => $posterTvUrl,
                        'status' => $this->convertStatusToBoolean($data['Status'] ?? 'active'),
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                    ];
                    
                    $newChannel = LiveTvChannel::create($channelData);
                    
                    // Create stream content mapping if stream data is provided
                    if (!empty($data['Stream Type']) || !empty($data['Server URL']) || !empty($data['Embedded'])) {
                        $streamData = [
                            'tv_channel_id' => $newChannel->id,
                            'type' => $data['Type'] ?? 't_url',
                            'stream_type' => $data['Stream Type'] ?? null,
                            'server_url' => $data['Server URL'] ?? null,
                            'server_url1' => $data['Server URL1'] ?? null,
                            'embedded' => $data['Embedded'] ?? null,
                            'created_by' => $this->userId,
                            'updated_by' => $this->userId,
                        ];
                        
                        TvChannelStreamContentMapping::create($streamData);
                    }
                    
                    DB::commit();
                    $success++;
                    \Log::info("Successfully created TV channel: {$data['Name']} (ID: {$newChannel->id})");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    \Log::error("Failed to create TV channel '{$data['Name']}': " . $e->getMessage());
                    $errorRows[] = array_merge($data, ['Errors' => __('messages.tv_channel_creation_failed') . ': ' . $e->getMessage()]);
                }
            }
        }

        $errorFile = null;
        if ($failed > 0) {
            $errorFileName = 'import_errors_tv_channels_' . time() . '.csv';
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

        
        \Log::info("=== ImportTVChannelJob COMPLETED ===");
        \Log::info("Success: {$success}, Failed: {$failed}, Total: {$totalRecords}");
        \Log::info("Final Status: {$status}");
        
        // 🟢 Final cleanup of any remaining temp files
        $this->cleanupTempDirectory();
        
        try {
            \Log::info("Completion email sent to: {$user->email}");
        } catch (\Exception $e) {
            \Log::error('Failed to send TV channel import completion email: ' . $e->getMessage());
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
    private function handleImageUrl($imageUrl, $folder = 'livetv', $channelName = null, $type = null)
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

            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg'; // Default extension
            }
            
            if ($channelName) {
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $channelName));
                $cleanName = preg_replace('/\s+/', '_', trim($cleanName));
                $filename = $cleanName . '_' . $type . '.' . $extension;
            }else{
                $filename = 'tvchannel' . '_' . $type . '.' . $extension;
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
               
            } else {
                \Log::info("🟢 No temp directory to clean up");
            }
        } catch (\Exception $e) {
            \Log::error("❌ Error cleaning up temp directory: " . $e->getMessage());
        }
    }
}
