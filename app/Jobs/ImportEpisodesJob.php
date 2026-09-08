<?php

namespace App\Jobs;

use Modules\Episode\Models\Episode;
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
use Modules\Entertainment\Models\Entertainment;
use Modules\Season\Models\Season;
use Modules\Subscriptions\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportEpisodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CsvImportHelper;

    protected $filePath;
    protected $userId;
    protected $batchSize = 100; // Smaller batch size for episodes due to complexity

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
        if (preg_match('/^\d{1,2}:\d{2}$/', $duration)) {
            return $duration;
        }
        
        // If it's numeric (minutes), convert to HH:MM
        if (is_numeric($duration)) {
            $hours = floor($duration / 60);
            $minutes = $duration % 60;
            return sprintf('%02d:%02d', $hours, $minutes);
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
            return [
                'valid' => false,
                'message' => __('messages.file_not_found'),
                'errors' => []
            ];
        }
        
        try {
            $rows = collect();
            if (in_array($extension, ['csv'])) {
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
            $requiredHeaders = ['Name', 'TV Show', 'Season', 'Episode Number'];
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
                    'valid' => false,
                    'message' => __('messages.validation_errors_found'),
                    'errors' => [__('messages.validation_error', ['error' => 'Missing episode headers: ' . implode(', ', $missingHeaders)])]
                ];
            }
            
            // Validate first 10 rows
            $rowsToValidate = $rows->take(10);
            $errorCount = 0;
            $displayRow = 2; // header is row 1
            foreach ($rowsToValidate as $rowIndex => $row) {
                $rowErrors = [];
                
                
                // Check required fields
                if (!isset($row['Name']) || trim((string)$row['Name']) === '') {
                    $rowErrors[] = __('messages.name_required');
                }
                
                if (empty($row['TV Show'])) {
                    $rowErrors[] = __('messages.tv_show_required');
                }
                
                if (empty($row['Season'])) {
                    $rowErrors[] = __('messages.season_required');
                }
                
                if (empty($row['Episode Number'])) {
                    $rowErrors[] = __('messages.episode_number_required');
                }
                if (!isset($row['Status']) || trim((string)$row['Status']) === '') {
                    $rowErrors[] = __('messages.status_required');
                }
                
                // Check for duplicate episode names
                if (!empty($row['Name']) && Episode::where('name', $row['Name'])->exists()) {
                    $rowErrors[] = __('messages.episode_name_already_exists', ['name' => $row['Name']]);
                }
                
              
                
                // Validate Episode Number
                if (!empty($row['Episode Number']) && !is_numeric($row['Episode Number'])) {
                    $rowErrors[] = __('messages.episode_number_must_be_numeric');
                }

                if (!empty($row['Plan ID']) && !is_numeric($row['Plan ID'])) {
                    $rowErrors[] = __('messages.plan_id_must_be_numeric');
                }

                if(empty($row['Content Rating'])) {
                    $rowErrors[] = __('messages.content_rating_required');
                }
                if(empty($row['Trailer URL Type'])) {
                    $rowErrors[] = __('messages.trailer_url_type_required');
                }
                if (!empty($row['Trailer URL Type']) && !in_array(strtolower($row['Trailer URL Type']), ['url', 'local', 'embedded', 'youtube', 'hls', 'vimeo', 'x265'])) {
                    $rowErrors[] = __('messages.trailer_url_type_must_be_valid');
                }
                if(empty($row['Trailer URL'])) {
                    $rowErrors[] = __('messages.trailer_url_required');
                }
                if (!empty($row['Trailer URL']) && !filter_var($row['Trailer URL'], FILTER_VALIDATE_URL)) {
                    $rowErrors[] = __('messages.trailer_url_must_be_valid');
                }
                if(empty($row['Video Upload Type'])) {
                    $rowErrors[] = __('messages.video_upload_type_required');
                }
                if(empty($row['Video URL Input'])) {
                    $rowErrors[] = __('messages.video_url_input_required');
                }
                if (!empty($row['Video Upload Type']) && !in_array(strtolower($row['Video Upload Type']), ['url', 'local', 'embedded', 'youtube', 'hls', 'vimeo', 'x265'])) {
                    $rowErrors[] = __('messages.video_upload_type_must_be_valid');
                }

                if (!empty($row['Video URL Input']) && !filter_var($row['Video URL Input'], FILTER_VALIDATE_URL)) {
                    $rowErrors[] = __('messages.video_url_input_must_be_valid');
                }
                if(empty($row['Release Date'])) {
                    $rowErrors[] = __('messages.release_date_required');
                }

                if (empty($row['Description']) || trim((string)$row['Description']) === '') {
                    $rowErrors[] = __('messages.description_required');
                }
                
                if (empty($row['Access'])) {
                    $rowErrors[] = __('messages.access_required');
                } elseif (!in_array(strtolower($row['Access']), ['free', 'paid', 'pay-per-view'])) {
                    $rowErrors[] = __('messages.access_must_be_valid');
                }
                
                if (!empty($row['Plan ID']) && !is_numeric($row['Plan ID'])) {
                    $rowErrors[] = __('messages.plan_id_must_be_numeric');
                }
                
                if (!empty($row['Price']) && (!is_numeric($row['Price']) || $row['Price'] < 0)) {
                    $rowErrors[] = __('messages.price_must_be_positive_number');
                }
                
                if (empty($row['IMDb Rating']) || !is_numeric($row['IMDb Rating']) || $row['IMDb Rating'] < 1 || $row['IMDb Rating'] > 10) {
                    $rowErrors[] = __('messages.imdb_rating_must_be_between_0_and_10');
                }
                
                if (!empty($row['Duration'])) {
                    $duration = $row['Duration'];
                    // Check if it's in HH:MM format or numeric minutes
                    if (!preg_match('/^\d{1,2}:\d{2}$/', $duration) && !is_numeric($duration)) {
                        $rowErrors[] = __('messages.duration_must_be_in_hh_mm_format_or_numeric_minutes');
                    }
                } else {
                    $rowErrors[] = __('messages.duration_required');
                }
                
                if (!empty($row['Access Duration']) && (!is_numeric($row['Access Duration']) || $row['Access Duration'] < 0)) {
                    $rowErrors[] = __('messages.access_duration_must_be_positive_number');
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
                    'valid' => false,
                    'message' => __('messages.validation_errors_found'),
                    'errors' => $errors
                ];
            }
            
            return ['valid' => true];
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => __('messages.validation_error', ['error' => $e->getMessage()]),
                'errors' => []
            ];
        }
    }

    public function handle()
    {
        \Log::info("=== ImportEpisodesJob STARTED ===");
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
                    'Name', 'Description', 'TV Show', 'Season', 'Episode Number', 'Access', 'Status', 
                    'Trailer URL Type', 'Trailer URL', 'Poster URL', 'Poster TV URL', 'Video Upload Type', 'Video URL Input', 
                    'Plan ID', 'IMDb Rating', 'Content Rating', 'Duration', 'Release Date', 'Is Restricted', 
                    'Price', 'Purchase Type', 'Access Duration', 'Discount', 'Available For'
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
            'Name', 'Description', 'TV Show', 'Season', 'Episode Number', 'Access', 'Status', 
            'Trailer URL Type', 'Trailer URL', 'Poster URL', 'Poster TV URL', 'Video Upload Type', 'Video URL Input', 
            'Plan ID', 'IMDb Rating', 'Content Rating', 'Duration', 'Release Date', 'Is Restricted', 
            'Price', 'Purchase Type', 'Access Duration', 'Discount', 'Available For'
        ];
        
        $header = $rows->first();
        \Log::info("CSV Header: " . json_encode($header));
        \Log::info("Required Header: " . json_encode($requiredHeader));
        \Log::info("Header count: " . count($header) . ", Required count: " . count($requiredHeader));
        
        // Check if required headers exist
        $requiredHeaders = ['Name', 'TV Show', 'Season', 'Episode Number'];
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
                $data = array_combine($requiredHeader, $row);
                
                // Debug: Log the data array to see what we're getting
                \Log::info("Processing row data: " . json_encode($data));
                \Log::info("Poster URL from CSV: " . ($data['Poster URL'] ?? 'NOT_FOUND'));
                
                // Check if the data array has the correct keys
                if (!isset($data['Poster URL'])) {
                    \Log::error("Missing poster URL key in data array. Available keys: " . implode(', ', array_keys($data)));
                }
                
                $rowErrors = [];
                
                if (empty($data['Name'])) $rowErrors[] = __('messages.name_required');
                if (empty($data['TV Show'])) $rowErrors[] = __('messages.tv_show_required');
                if (empty($data['Season'])) $rowErrors[] = __('messages.season_required');
                if (empty($data['Episode Number'])) $rowErrors[] = __('messages.episode_number_required');
                if (!is_numeric($data['Episode Number'])) $rowErrors[] = __('messages.episode_number_must_be_numeric');
                if (Episode::where('name', $data['Name'])->where('episode_number', $data['Episode Number'])->exists()) {
                    $rowErrors[] = __('messages.episode_already_exists');
                }
                if (empty($data['Access'])) {
                    $rowErrors[] = __('messages.access_required');
                } elseif (!in_array(strtolower($data['Access']), ['free', 'premium', 'pay-per-view'])) {
                    $rowErrors[] = __('messages.access_must_be_valid');
                }
                if (empty($data['Description'])) $rowErrors[] = __('messages.description_required');
                if (empty($data['Content Rating'])) $rowErrors[] = __('messages.content_rating_required');
                if (empty($data['IMDb Rating']) || !is_numeric($data['IMDb Rating']) || $data['IMDb Rating'] < 1 || $data['IMDb Rating'] > 10) {
                    $rowErrors[] = __('messages.imdb_rating_must_be_between_0_and_10');
                }
                if (!empty($data['Duration'])) {
                    if (!preg_match('/^\d{1,2}:\d{2}$/', $data['Duration']) && !is_numeric($data['Duration'])) {
                        $rowErrors[] = __('messages.duration_must_be_in_hh_mm_format_or_numeric_minutes');
                    }
                } else {
                    $rowErrors[] = __('messages.duration_required');
                }
                if (empty($data['Trailer URL Type'])) {
                    $rowErrors[] = __('messages.trailer_url_type_required');
                } else {
                    $allowedTrailerTypes = ['url','local','embedded','youtube','hls','vimeo','x265'];
                    if (!in_array(strtolower($data['Trailer URL Type']), $allowedTrailerTypes)) {
                        $rowErrors[] = __('messages.trailer_url_type_must_be_valid');
                    }
                }
                if (empty($data['Video Upload Type'])) {
                    $rowErrors[] = __('messages.video_upload_type_required');
                }
                if (!empty($data['Status']) && !in_array(strtolower($data['Status']), ['active', 'inactive', '1', '0'])) {
                    $rowErrors[] = __('messages.status_must_be_active_or_inactive');
                }
                if (!empty($data['Release Date']) && !strtotime($data['Release Date'])) {
                    $rowErrors[] = __('messages.release_date_must_be_valid');
                }
                if (!empty($data['Price']) && (!is_numeric($data['Price']) || $data['Price'] < 0)) {
                    $rowErrors[] = __('messages.price_must_be_positive_number');
                }
                if (!empty($data['Access Duration']) && (!is_numeric($data['Access Duration']) || $data['Access Duration'] < 0)) {
                    $rowErrors[] = __('messages.access_duration_must_be_positive_number');
                }
                if (!empty($data['Discount']) && (!is_numeric($data['Discount']) || $data['Discount'] < 0 || $data['Discount'] > 100)) {
                    $rowErrors[] = __('messages.discount_must_be_between_0_and_100');
                }
                
                if (!empty($rowErrors)) {
                    $failed++;
                    \Log::error("Validation failed for '{$data['Name']}': " . implode('; ', $rowErrors));
                    $errorRows[] = array_merge($data, ['Errors' => implode('; ', $rowErrors)]);
                    continue;
                }
                
                try {
                    DB::beginTransaction();
                    
                    // Find or create the TV show by name
                    $tvShow = Entertainment::where('name', $data['TV Show'])->where('type', 'tvshow')->first();
                    if (!$tvShow) {
                        // Create TV Show if it doesn't exist
                        $tvShow = Entertainment::create([
                            'name' => $data['TV Show'],
                            'type' => 'tvshow',
                            'status' => 1, // Active
                            'access' => 'free', // Default access
                            'created_by' => $this->userId,
                            'updated_by' => $this->userId,
                        ]);
                        \Log::info("Created TV Show: {$data['TV Show']} (ID: {$tvShow->id})");
                    }
                    
                    // Find or create the season by name and TV show
                    $season = Season::where('name', $data['Season'])->where('entertainment_id', $tvShow->id)->first();
                    if (!$season) {
                        // Create Season if it doesn't exist
                        $season = Season::create([
                            'name' => $data['Season'],
                            'entertainment_id' => $tvShow->id,
                            'season_index' => 1, // Default season index
                            'status' => 1, // Active
                            'access' => 'free', // Default access
                            'created_by' => $this->userId,
                            'updated_by' => $this->userId,
                        ]);
                        \Log::info("Created Season: {$data['Season']} (ID: {$season->id})");
                    }
                    
                    // Process poster URL
                    \Log::info("About to call handleImageUrl for poster: " . ($data['Poster URL'] ?? 'NULL'));
                    $posterUrl = $this->handleImageUrl($data['Poster URL'] ?? null, 'tvshow/episode', $data['Name'] ?? null);
                    $posterTvUrl = $this->handleImageUrl($data['Poster TV URL'] ?? null, 'tvshow/episode', $data['Name'] ?? null);
                    
                    \Log::info("Poster URL processing result: " . ($posterUrl ?: 'NULL'));
                    
                    // Validate that we have local image paths, not external URLs
                    if ($posterUrl && filter_var($posterUrl, FILTER_VALIDATE_URL)) {
                        \Log::warning("Poster URL is still external, setting to null: " . $posterUrl);
                        $posterUrl = null;
                    }
                    if ($posterTvUrl && filter_var($posterTvUrl, FILTER_VALIDATE_URL)) {
                        \Log::warning("Poster TV URL is still external, setting to null: " . $posterTvUrl);
                        $posterTvUrl = null;
                    }
                    \Log::info("Poster TV URL processing result: " . ($posterTvUrl ?: 'NULL'));
                    $episodeData = [
                        'name' => $data['Name'],
                        'description' => $data['Description'] ?? null,
                        'entertainment_id' => $tvShow->id,
                        'season_id' => $season->id,
                        'episode_number' => (int)$data['Episode Number'],
                        'access' => strtolower($data['Access'] ?? 'free'),
                        'status' => $this->convertStatusToBoolean($data['Status'] ?? 'active'),
                        'trailer_url_type' => $data['Trailer URL Type'] ?? 'YouTube',
                        'trailer_url' => !empty($data['Trailer URL']) ? $data['Trailer URL'] : null,
                        'poster_url' => $posterUrl,
                        'poster_tv_url' => $posterTvUrl, // Use same poster for TV
                        'video_upload_type' => $data['Video Upload Type'] ?? 'YouTube',
                        'video_url_input' => !empty($data['Video URL Input']) ? $data['Video URL Input'] : null,
                        'plan_id' => !empty($data['Plan ID']) ? (int)$data['Plan ID'] : null,
                        'IMDb_rating' => !empty($data['IMDb Rating']) ? round((float)$data['IMDb Rating'], 1) : null,
                        'content_rating' => $data['Content Rating'] ?? null,
                        'duration' => $this->processDuration($data['Duration'] ?? null),
                        'release_date' => !empty($data['Release Date']) ? date('Y-m-d', strtotime($data['Release Date'])) : null,
                        'is_restricted' => $this->convertBoolean($data['Is Restricted'] ?? '0'),
                        'price' => !empty($data['Price']) ? (float)$data['Price'] : null,
                        'purchase_type' => $data['Purchase Type'] ?? null,
                        'access_duration' => !empty($data['Access Duration']) ? (int)$data['Access Duration'] : null,
                        'discount' => !empty($data['Discount']) ? (float)$data['Discount'] : null,
                        'available_for' => $data['Available For'] ?? null,
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                    ];
                    
                    $newEpisode = Episode::create($episodeData);
                    
                    DB::commit();
                    $success++;
                    \Log::info("Successfully created episode: {$data['Name']} (ID: {$newEpisode->id})");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    \Log::error("Failed to create episode '{$data['Name']}': " . $e->getMessage());
                    $errorRows[] = array_merge($data, ['Errors' => __('messages.episode_creation_failed') . ': ' . $e->getMessage()]);
                }
            }
        }

        $errorFile = null;
        if ($failed > 0) {
            $errorFileName = 'import_errors_episodes_' . time() . '.csv';
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

     
        \Log::info("=== ImportEpisodesJob COMPLETED ===");
        \Log::info("Success: {$success}, Failed: {$failed}, Total: {$totalRecords}");
        
        // 🟢 Final cleanup of any remaining temp files
        $this->cleanupTempDirectory();
        
        try {
           
        } catch (\Exception $e) {
            \Log::error('Failed to send episodes import completion email: ' . $e->getMessage());
        }
    }

    private function convertStatusToBoolean($status)
    {
        if (is_numeric($status)) {
            return (bool) $status;
        }
        
        return strtolower($status) === 'active' || strtolower($status) === '1';
    }

    private function convertBoolean($value)
    {
        if (is_numeric($value)) {
            return (bool) $value;
        }
        
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on']);
    }

    /**
     * Handle image URL by downloading and saving the image
     */
    private function handleImageUrl($imageUrl, $folder = 'tvshow/episode', $episodeName = null)
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
            
          
            if ($episodeName) {
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $episodeName));
                $cleanName = preg_replace('/\s+/', '_', trim($cleanName));
                $filename = $cleanName . '_' . $extension;
            } 
            
            $filePath = $filename;

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
                \Log::info("🟢 Temp URL created: {$tempUrl}");
                
                // 🟢 Process via extractFileNameFromUrl (handles all storage types)
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
