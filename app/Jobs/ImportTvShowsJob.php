<?php

namespace App\Jobs;

use Modules\Entertainment\Models\Entertainment;
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
use App\Services\VideoEncryptor;
use Modules\Genres\Models\Genres;
use Modules\CastCrew\Models\CastCrew;
use Modules\World\Models\Country;
use Modules\Subscriptions\Models\Plan;
use Modules\Constant\Models\Constant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ImportTVshowsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CsvImportHelper;

    protected $filePath;
    protected $userId;
    protected $batchSize = 100; // Smaller batch size for TV shows due to complexity

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
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $fullPath = storage_path('app/' . $filePath);
        
        if (!file_exists($fullPath)) {
            return [
                'valid' => false,
                'message' => __('messages.file_not_found'),
                'errors' => []
            ];
        }
        
        $rows = [];
        
        if (in_array($extension, ['csv'])) {
            try {
                $handle = fopen($fullPath, 'r');
                if ($handle !== false) {
                    $header = fgetcsv($handle);
                    $rowCount = 0;
                    while (($row = fgetcsv($handle)) !== false && $rowCount < 10) { // Check first 10 rows
                        $rows[] = array_combine($header, $row);
                        $rowCount++;
                    }
                    fclose($handle);
                }
            } catch (\Exception $e) {
                return [
                    'valid' => false,
                    'message' => __('messages.error_reading_csv', ['error' => $e->getMessage()]),
                    'errors' => []
                ];
            }
        } else {
            try {
                $excelData = \Maatwebsite\Excel\Facades\Excel::toArray([], $fullPath);
                $header = $excelData[0][0] ?? [];
                $dataRows = array_slice($excelData[0], 1, 10); // First 10 data rows
                foreach ($dataRows as $row) {
                    $rows[] = array_combine($header, $row);
                }
            } catch (\Exception $e) {
                return [
                    'valid' => false,
                    'message' => __('messages.error_reading_excel', ['error' => $e->getMessage()]),
                    'errors' => []
                ];
            }
        }
        
        if (empty($rows)) {
            return [
                'valid' => false,
                'message' => __('messages.no_data_found_in_file'),
                'errors' => []
            ];
        }
        
        $errors = [];
        $errorCount = 0;
        
        foreach ($rows as $index => $row) {
            $rowErrors = [];
            $rowNumber = $index + 2; // Actual row number in file (header is row 1, first data row is row 2)
            
           
            // Basic validation for TV shows
            if (empty($row['Name'])) {
                $rowErrors[] = __('messages.name_required');
            } else {
                // Check for duplicate TV show names
                if (Entertainment::where('name', $row['Name'])->where('type', 'tvshow')->exists()) {
                    $rowErrors[] = __('messages.tvshow_name_already_exists', ['name' => $row['Name']]);
                }
            }
            
            // Required fields aligned with TV show create form
            if (!isset($row['Description']) || trim((string)$row['Description']) === '') {
                $rowErrors[] = __('messages.description_required');
            }
            if (!isset($row['Trailer URL Type']) || trim((string)$row['Trailer URL Type']) === '') {
                $rowErrors[] = __('messages.trailer_url_type_required');
            } else {
                $allowedTrailerTypes = ['url','local','embedded','youtube','hls','vimeo','x265'];
                if (!in_array(strtolower($row['Trailer URL Type']), $allowedTrailerTypes)) {
                    $rowErrors[] = __('messages.trailer_url_type_must_be_valid');
                }
            }
            if (!isset($row['Language']) || trim((string)$row['Language']) === '') {
                $rowErrors[] = __('messages.language_required');
            }
            if (!isset($row['Genres']) || trim((string)$row['Genres']) === '') {
                $rowErrors[] = __('messages.genres_required');
            }
            if (!isset($row['Actors']) || trim((string)$row['Actors']) === '') {
                $rowErrors[] = __('messages.actors_required');
            }
            if (!isset($row['Directors']) || trim((string)$row['Directors']) === '') {
                $rowErrors[] = __('messages.directors_required');
            }
            if (!isset($row['IMDb Rating']) || trim((string)$row['IMDb Rating']) === '') {
                $rowErrors[] = __('messages.imdb_rating_must_be_between_0_and_10');
            }

            if (!empty($row['Duration'])) {
                $duration = trim($row['Duration']);
                if (!preg_match('/^\d{2}:\d{2}$/', $duration) && !is_numeric($duration)) {
                    $rowErrors[] = __('messages.duration_must_be_in_hh_mm_format_or_numeric_minutes');
                }
            } else {
                $rowErrors[] = __('messages.duration_required');
            }
            
            if (!empty($row['Status']) && !in_array(strtolower($row['Status']), ['active', 'inactive', '1', '0'])) {
                $rowErrors[] = __('messages.status_must_be_active_or_inactive');
            }
            
            if (!isset($row['Tv Show Access']) || trim((string)$row['Tv Show Access']) === '') {
                $rowErrors[] = __('messages.tv_show_access_required');
            } elseif (!in_array(strtolower(trim((string)$row['Tv Show Access'])), ['free', 'paid', 'pay-per-view'])) {
                $rowErrors[] = __('messages.tv_show_access_must_be_valid');
            }
            
            if (!empty($row['IMDb Rating']) && (!is_numeric($row['IMDb Rating']) || $row['IMDb Rating'] < 0 || $row['IMDb Rating'] > 10)) {
                $rowErrors[] = __('messages.imdb_rating_must_be_between_0_and_10');
            }
            if (empty($row['Content Rating'])) {
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
            if(empty($row['Status'])) {
                $rowErrors[] = __('messages.status_required');
            }
            if (!empty($row['Status']) && !in_array(strtolower($row['Status']), ['active', 'inactive', '1', '0'])) {
                $rowErrors[] = __('messages.status_must_be_active_or_inactive');
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
            
            if (!empty($row['Plan ID']) && !is_numeric($row['Plan ID'])) {
                $rowErrors[] = __('messages.plan_id_must_be_numeric');
            }

            if (!empty($row['Price']) && (!is_numeric($row['Price']) || $row['Price'] < 0)) {
                $rowErrors[] = __('messages.price_must_be_positive_number');
            }

            if (!empty($row['Access Duration']) && (!is_numeric($row['Access Duration']) || $row['Access Duration'] < 0)) { 
                $rowErrors[] = __('messages.access_duration_must_be_positive_number');
            }
            
            if (!empty($rowErrors)) {
                $errors[] = "Row {$rowNumber}: " . implode(', ', $rowErrors);
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
                'valid' => false,
                'message' => __('messages.validation_errors_found'),
                'errors' => $errors
            ];
        }
        
        return ['valid' => true];
    }

    public function handle()
    {
        \Log::info("=== ImportTVshowsJob STARTED ===");
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
                    'Name', 'Description', 'Type', 'Tv Show Access', 'Status', 'Language', 'IMDb Rating', 
                    'Content Rating', 'Duration', 'Release Date', 'Is Restricted', 'Trailer URL Type', 
                    'Trailer URL', 'Thumbnail URL', 'Poster URL', 'Poster TV URL', 'Video Upload Type', 'Video URL Input',
                    'Genres', 'Actors', 'Directors', 'Countries', 'Plan ID', 'Price', 'Purchase Type',
                    'Access Duration', 'Discount', 'Available For', 'Enable Quality', 'Enable Subtitle'
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
            'Name', 'Description', 'Type', 'Tv Show Access', 'Status', 'Language', 'IMDb Rating', 
            'Content Rating', 'Duration', 'Release Date', 'Is Restricted', 'Trailer URL Type', 
            'Trailer URL', 'Thumbnail URL', 'Poster URL', 'Poster TV URL', 'Video Upload Type', 'Video URL Input',
            'Genres', 'Actors', 'Directors', 'Countries', 'Plan ID', 'Price', 'Purchase Type',
            'Access Duration', 'Discount', 'Available For', 'Enable Quality', 'Enable Subtitle'
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
                \Log::info("Thumbnail URL from CSV: " . ($data['Thumbnail URL'] ?? 'NOT_FOUND'));
                \Log::info("Poster URL from CSV: " . ($data['Poster URL'] ?? 'NOT_FOUND'));
                
                // Check if the data array has the correct keys
                if (!isset($data['Thumbnail URL']) || !isset($data['Poster URL'])) {
                    \Log::error("Missing image URL keys in data array. Available keys: " . implode(', ', array_keys($data)));
                }
                
                $rowErrors = [];
                
                // Validation
                if (empty($data['Name'])) $rowErrors[] = __('messages.name_required');
                if (Entertainment::where('name', $data['Name'])->where('type', 'tvshow')->exists()) $rowErrors[] = __('messages.tvshow_name_already_exists', ['name' => $data['Name']]);
                if (!empty($data['Status']) && !in_array(strtolower($data['Status']), ['active', 'inactive', '1', '0'])) {
                    $rowErrors[] = __('messages.status_must_be_active_or_inactive');
                }
                if (!empty($data['Type']) && !in_array(strtolower($data['Type']), ['tvshow', 'tv_show'])) {
                    $rowErrors[] = __('messages.type_must_be_tvshow');
                }
                if (!empty($data['Tv Show Access']) && !in_array(strtolower($data['Tv Show Access']), ['free', 'paid', 'pay-per-view'])) {
                    $rowErrors[] = __('messages.tv_show_access_must_be_valid');
                }
                if (!empty($data['Duration'])) {
                    // Accept both HH:MM format and numeric minutes
                    $duration = trim($data['Duration']);
                    if (!preg_match('/^\d{2}:\d{2}$/', $duration) && !is_numeric($duration)) {
                        $rowErrors[] = __('messages.duration_must_be_in_hh_mm_format_or_numeric_minutes');
                    }
                }
                if (!empty($data['Release Date']) && !strtotime($data['Release Date'])) {
                    $rowErrors[] = __('messages.release_date_must_be_valid');
                }
                if (!empty($data['IMDb Rating']) && (!is_numeric($data['IMDb Rating']) || $data['IMDb Rating'] < 0 || $data['IMDb Rating'] > 10)) {
                    $rowErrors[] = __('messages.imdb_rating_must_be_between_0_and_10');
                }

                if(empty($data['Content Rating'])) {
                    $rowErrors[] = __('messages.content_rating_required');
                }
                if(empty($data['Trailer URL Type'])) {
                    $rowErrors[] = __('messages.trailer_url_type_required');
                }
                if (!empty($data['Trailer URL Type']) && !in_array(strtolower($data['Trailer URL Type']), ['url', 'local', 'embedded', 'youtube', 'hls', 'vimeo', 'x265'])) {
                    $rowErrors[] = __('messages.trailer_url_type_must_be_valid');
                }
                
                if(empty($data['Trailer URL'])) {
                    $rowErrors[] = __('messages.trailer_url_required');
                }
                if (!empty($data['Trailer URL']) && !filter_var($data['Trailer URL'], FILTER_VALIDATE_URL)) {
                    $rowErrors[] = __('messages.trailer_url_must_be_valid');
                }
                
                
                if(empty($data['Video Upload Type'])) {
                    $rowErrors[] = __('messages.video_upload_type_required');
                }
                if(empty($data['Video URL Input'])) {
                    $rowErrors[] = __('messages.video_url_input_required');
                }
                if (!empty($data['Video Upload Type']) && !in_array(strtolower($data['Video Upload Type']), ['url', 'local', 'embedded', 'youtube', 'hls', 'vimeo', 'x265'])) {
                    $rowErrors[] = __('messages.video_upload_type_must_be_valid');
                }
                if (!empty($data['Video URL Input']) && !filter_var($data['Video URL Input'], FILTER_VALIDATE_URL)) {
                    $rowErrors[] = __('messages.video_url_input_must_be_valid');
                }
                if(empty($data['Release Date'])) {
                    $rowErrors[] = __('messages.release_date_required');
                }
                if (empty($data['Description']) || trim((string)$data['Description']) === '') {
                    $rowErrors[] = __('messages.description_required');
                }
                if (empty($data['Tv Show Access'])) {
                    $rowErrors[] = __('messages.tv_show_access_required');
                } elseif (!in_array(strtolower(trim((string)$data['Tv Show Access'])), ['free', 'paid', 'pay-per-view'])) {
                    $rowErrors[] = __('messages.tv_show_access_must_be_valid');
                }
                if (!empty($rowErrors)) {
                    $failed++;
                    \Log::error("Validation failed for '{$data['Name']}': " . implode('; ', $rowErrors));
                    $errorRows[] = array_merge($data, ['Errors' => implode('; ', $rowErrors)]);
                    continue;
                }
                
                try {
                    DB::beginTransaction();
                    
                    // Process image URLs
                    \Log::info("About to call handleImageUrl for thumbnail: " . ($data['Thumbnail URL'] ?? 'NULL'));    
                    $thumbnailUrl = $this->handleImageUrl($data['Thumbnail URL'] ?? null, 'tvshow', $data['Name'] ?? null, 'thumb');
                    \Log::info("About to call handleImageUrl for poster: " . ($data['Poster URL'] ?? 'NULL'));
                    $posterUrl = $this->handleImageUrl($data['Poster URL'] ?? null, 'tvshow', $data['Name'] ?? null, 'poster');
                    $posterTvUrl = $this->handleImageUrl($data['Poster TV URL'] ?? null, 'tvshow', $data['Name'] ?? null, 'poster_tv');
                    \Log::info("Thumbnail URL processing result: " . ($thumbnailUrl ?: 'NULL'));
                    \Log::info("Poster URL processing result: " . ($posterUrl ?: 'NULL'));
                    
                    // Validate that we have local image paths, not external URLs
                    if ($thumbnailUrl && filter_var($thumbnailUrl, FILTER_VALIDATE_URL)) {
                        \Log::warning("Thumbnail URL is still external, setting to null: " . $thumbnailUrl);
                        $thumbnailUrl = null;
                    }
                    if ($posterUrl && filter_var($posterUrl, FILTER_VALIDATE_URL)) {
                        \Log::warning("Poster URL is still external, setting to null: " . $posterUrl);
                        $posterUrl = null;
                    }
                    
                    $tvshowData = [
                        'name' => $data['Name'],
                        'description' => $data['Description'] ?? null,
                        'type' => 'tvshow', // Force type to tvshow
                        'movie_access' => strtolower($data['Tv Show Access'] ?? 'free'),
                        'status' => $this->convertStatusToBoolean($data['Status'] ?? 'active'),
                        'language' => $data['Language'] ?? 'english',
                        'IMDb_rating' => !empty($data['IMDb Rating']) ? (float)$data['IMDb Rating'] : null,
                        'content_rating' => $data['Content Rating'] ?? null,
                        'duration' => $this->processDuration($data['Duration'] ?? null),
                        'release_date' => !empty($data['Release Date']) ? date('Y-m-d', strtotime($data['Release Date'])) : null,
                        'is_restricted' => $this->convertToBoolean($data['Is Restricted'] ?? 'false'),
                        'trailer_url_type' => $data['Trailer URL Type'] ?? 'YouTube',
                        'trailer_url' => !empty($data['Trailer URL']) ? $data['Trailer URL'] : null,
                        'thumbnail_url' => $thumbnailUrl,
                        'poster_url' => $posterUrl,
                        'poster_tv_url' => $posterTvUrl, // Use same poster for TV
                        'video_upload_type' => $data['Video Upload Type'] ?? 'YouTube',
                        'video_url_input' => !empty($data['Video URL Input']) ?$data['Video URL Input'] : null,
                        'plan_id' => !empty($data['Plan ID']) ? (int)$data['Plan ID'] : null,
                        'price' => !empty($data['Price']) ? (float)$data['Price'] : null,
                        'purchase_type' => $data['Purchase Type'] ?? null,
                        'access_duration' => !empty($data['Access Duration']) ? (int)$data['Access Duration'] : null,
                        'discount' => !empty($data['Discount']) ? (float)$data['Discount'] : null,
                        'available_for' => $data['Available For'] ?? null,
                        'enable_quality' => $this->convertToBoolean($data['Enable Quality'] ?? 'true'),
                        'enable_subtitle' => $this->convertToBoolean($data['Enable Subtitle'] ?? 'false'),
                        'created_by' => $this->userId,
                        'updated_by' => $this->userId,
                    ];
                    
                    $newTVshow = Entertainment::create($tvshowData);
                    
                    // Handle genres
                    if (!empty($data['Genres'])) {
                        $this->handleGenres($newTVshow, $data['Genres']);
                    }
                    
                    // Handle actors
                    if (!empty($data['Actors'])) {
                        $this->handleActors($newTVshow, $data['Actors']);
                    }
                    
                    // Handle directors
                    if (!empty($data['Directors'])) {
                        $this->handleDirectors($newTVshow, $data['Directors']);
                    }
                    
                    // Handle countries
                    if (!empty($data['Countries'])) {
                        $this->handleCountries($newTVshow, $data['Countries']);
                    }
                    
                    DB::commit();
                    $success++;
                    \Log::info("Successfully created TV show: {$data['Name']} (ID: {$newTVshow->id})");
                } catch (\Exception $e) {
                    \Log::error("-------------------Failed to create TV show ");
                    DB::rollBack();
                    $failed++;
                    \Log::error("Failed to create TV show '{$data['Name']}': " . $e->getMessage());
                    $errorRows[] = array_merge($data, ['Errors' => __('messages.tvshow_creation_failed') . ': ' . $e->getMessage()]);
                }
            }
        }

        $errorFile = null;
        if ($failed > 0) {
            $errorFileName = 'import_errors_tvshows_' . time() . '.csv';
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

        \Log::info("=== ImportTVshowsJob COMPLETED ===");
        \Log::info("Success: {$success}, Failed: {$failed}, Total: {$totalRecords}");
        
        // 🟢 Final cleanup of any remaining temp files
        $this->cleanupTempDirectory();
        
        try {
            \Log::info("Completion email sent to: {$user->email}");
        } catch (\Exception $e) {
            \Log::error('Failed to send TV shows import completion email: ' . $e->getMessage());
        }
    }

    private function convertStatusToBoolean($status)
    {
        if (is_numeric($status)) {
            return (bool) $status;
        }
        
        return strtolower($status) === 'active' || strtolower($status) === '1';
    }

    private function convertToBoolean($value)
    {
        if (is_numeric($value)) {
            return (bool) $value;
        }
        
        return in_array(strtolower($value), ['true', '1', 'yes', 'active']);
    }

    private function processDuration($duration)
    {
        if (empty($duration)) {
            return null;
        }
        
        $duration = trim($duration);
        
        // If it's already in HH:MM format, return as is
        if (preg_match('/^\d{2}:\d{2}$/', $duration)) {
            return $duration;
        }
        
        // If it's numeric (minutes), convert to HH:MM format
        if (is_numeric($duration)) {
            $minutes = (int) $duration;
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return sprintf('%02d:%02d', $hours, $remainingMinutes);
        }
        
        return $duration; // Return as is if it doesn't match expected formats
    }

    private function handleGenres($tvshow, $genresString)
    {
        $genreNames = array_map('trim', explode(',', $genresString));
        
        foreach ($genreNames as $genreName) {
            if (!empty($genreName)) {
                $genre = Genres::firstOrCreate(
                    ['name' => $genreName],
                    ['name' => $genreName, 'slug' => \Str::slug($genreName), 'status' => 1]
                );
                
                DB::table('entertainment_gener_mapping')->insertOrIgnore([
                    'entertainment_id' => $tvshow->id,
                    'genre_id' => $genre->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function handleActors($tvshow, $actorsString)
    {
        $actorNames = array_map('trim', explode(',', $actorsString));
        
        foreach ($actorNames as $actorName) {
            if (!empty($actorName)) {
                $actor = CastCrew::firstOrCreate(
                    ['name' => $actorName, 'type' => 'actor'],
                    ['name' => $actorName, 'type' => 'actor', 'status' => 1]
                );
                
                DB::table('entertainment_talent_mapping')->insertOrIgnore([
                    'entertainment_id' => $tvshow->id,
                    'talent_id' => $actor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function handleDirectors($tvshow, $directorsString)
    {
        $directorNames = array_map('trim', explode(',', $directorsString));
        
        foreach ($directorNames as $directorName) {
            if (!empty($directorName)) {
                $director = CastCrew::firstOrCreate(
                    ['name' => $directorName, 'type' => 'director'],
                    ['name' => $directorName, 'type' => 'director', 'status' => 1]
                );
                
                DB::table('entertainment_talent_mapping')->insertOrIgnore([
                    'entertainment_id' => $tvshow->id,
                    'talent_id' => $director->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function handleCountries($tvshow, $countriesString)
    {
        $countryNames = array_map('trim', explode(',', $countriesString));
        
        foreach ($countryNames as $countryName) {
            if (!empty($countryName)) {
                $country = Country::firstOrCreate(
                    ['name' => $countryName],
                    ['name' => $countryName, 'status' => 1]
                );
                
                DB::table('entertainment_country_mapping')->insertOrIgnore([
                    'entertainment_id' => $tvshow->id,
                    'country_id' => $country->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Handle image URL by downloading and saving the image
     */
    private function handleImageUrl($imageUrl, $folder = 'images', $tvshowName = null, $type = null)
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
            
            if ($tvshowName) {
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $tvshowName));
                $cleanName = preg_replace('/\s+/', '_', trim($cleanName));
                $filename = $cleanName . '_' . $type . '.' . $extension;  
            }else{
                $filename = 'tvshow'.time() . '_' . $type . '.' . $extension;
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
            // Return null if image download fails
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
     * Send completion email with import results
     */

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
