<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Filemanager\Models\Filemanager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $filemanager;
    public $filePath;
    public $diskType;
    public $originalName;
    public $page_type;
    public $fileType;
    /**
     * Create a new job instance.
     */
    public function __construct(Filemanager $filemanager, $filePath, $diskType, $originalName, $page_type, $fileType)
    {
        $this->filemanager = $filemanager;
        $this->filePath = $filePath;
        $this->diskType = $diskType;
        $this->originalName = $originalName;
        $this->page_type = $page_type;
        $this->fileType = $fileType;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Large media processing can exceed the default 60s in some environments.
            set_time_limit(300);

            Log::info($this->filePath );

            Log::info($this->filePath );


            if (!Storage::disk('local')->exists($this->filePath)) {
                Log::info("File does not exist at path: {$this->filePath}");
                throw new \Exception("File does not exist at path: {$this->filePath}");
            }

            if($this->page_type == 'season' ) {
                $this->page_type = 'tvshow/season';
            }

            if($this->page_type == 'episode' ) {
                $this->page_type = 'tvshow/episode';
            }



            if ($this->diskType === 'local') {
                $folderPath = 'public/' . $this->page_type . '/'. $this->fileType . '/' . $this->filemanager->file_name;

                $directoryPath = 'public/' . $this->page_type . '/' . $this->fileType;

                if(!Storage::disk('local')->exists($directoryPath)) {
                    Log::info("Directory does not exist at path: {$directoryPath}");
                    $absoluteDirectoryPath = storage_path('app/' . $directoryPath);
                    File::makeDirectory($absoluteDirectoryPath, 0775, true, true);
                }

                // On local disk this is typically the same filesystem; move is far faster than copying GBs.
                $moved = Storage::disk('local')->move($this->filePath, $folderPath);

                if ($moved === false) {
                    // Fallback to stream copy when direct move is not possible.
                    $stream = Storage::disk('local')->readStream($this->filePath);
                    if ($stream === false) {
                        throw new \Exception("Unable to open read stream for path: {$this->filePath}");
                    }
                    $written = Storage::disk('local')->writeStream($folderPath, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    if ($written === false) {
                        throw new \Exception("Unable to write stream to local disk at path: {$folderPath}");
                    }

                    Storage::disk('local')->delete($this->filePath);
                }

                $fullPath = storage_path('app/' . $folderPath);
                if (file_exists($fullPath)) {
                    chmod($fullPath, 0664);

                    $dirPath = dirname($fullPath);
                    if (is_dir($dirPath)) {
                        chmod($dirPath, 0775);
                    }
                }
            } else {
                $folderPath =  $this->page_type . '/' . $this->fileType . '/' . $this->filemanager->file_name;
                // Stream file content to remote disk to avoid loading large files fully into memory.
                $stream = Storage::disk('local')->readStream($this->filePath);

                if ($stream === false) {
                    throw new \Exception("Unable to open read stream for path: {$this->filePath}");
                }

                $written = Storage::disk($this->diskType)->writeStream($folderPath, $stream);
                fclose($stream);

                if ($written === false) {
                    throw new \Exception("Unable to write stream to disk [{$this->diskType}] at path: {$folderPath}");
                }
            }

            $this->filemanager->save();

            // Delete the unique file (with ID)
            if (Storage::exists($this->filePath)){
                $deleted = Storage::disk('local')->delete($this->filePath);
            }

            // Also delete original filename if it exists in temp/uploads
            if($this->originalName) {
                $originalTempPath = 'temp/uploads/' . $this->originalName;
                if (Storage::disk('local')->exists($originalTempPath)) {
                    Storage::disk('local')->delete($originalTempPath);
                }
            }

            Artisan::call('config:clear');
            Artisan::call('cache:clear');
        } catch (\Exception $e) {

            Log::info("Error processing file upload: " . $e->getMessage());

            throw $e;
        }
    }


}
