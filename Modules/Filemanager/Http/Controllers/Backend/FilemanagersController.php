<?php
namespace Modules\Filemanager\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use Modules\Filemanager\Models\Filemanager;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Filemanager\Http\Requests\FilemanagerRequest;
use App\Traits\ModuleTrait;
use App\Models\Setting;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessFileUpload;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\DB;
use Modules\Entertainment\Models\Entertainment;
use Illuminate\Support\Facades\Log;

class FilemanagersController extends Controller
{
    protected string $exportClass = '\App\Exports\FilemanagerExport';

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'filemanager.title', // module title
            'media', // module name
            'fa-solid fa-clipboard-list' // module icon
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $module_action = 'List';
        $searchQuery = $request->get('query');
        $perPage = 31;
        $page = $request->get('page', 1);

        $result = getMediaUrls($searchQuery, $perPage, $page);
        $mediaUrls = $result['mediaUrls'];
        $hasMore = $result['hasMore'];

        if ($request->ajax()) {
            return response()->json([
                'html' => view('filemanager::backend.filemanager.partial', compact('mediaUrls'))->render(),
                'hasMore' => $hasMore,
            ]);
        }

        return view('filemanager::backend.filemanager.index', compact('module_action', 'mediaUrls', 'hasMore'));
    }



    public function getMediaStore(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = 31; // Number of items per page

        $searchQuery = $request->get('query');
        $result = getMediaUrls($searchQuery, $perPage, $page);


        $mediaUrls = $result['mediaUrls'];
        $hasMore = $result['hasMore'];

        $html = view('filemanager::backend.filemanager.partial', compact('mediaUrls'))->render();

            return response()->json([
                'html' => $html,
                'hasMore' => $hasMore,
            ]);
    }


    public function store(FilemanagerRequest $request)
  {

    $page_type = $request->input('page_type');

    // Mode A: direct file post (fallback)
    if ($request->hasFile('file_url')) {
        foreach ($request->file('file_url') as $file) {
            $file = validateUploadedFile($file, 'media_library');
            $extension = $file->getClientOriginalExtension();
            $fileType = $this->getFileType($extension);
            $uniqueFileName = sanitizeUploadedFilename($file, 'media_library');
            $originalName = $file->getClientOriginalName();
            $temporaryPath = $file->storeAs('temp/uploads', $uniqueFileName);
            // If chunk-assembled temp (original name) exists, remove to avoid duplicate
            $assembledTempPath = storage_path('app/temp/uploads/' . $originalName);
            if (file_exists($assembledTempPath)) {
                @unlink($assembledTempPath);
            }
            $filemanager = Filemanager::create([
                'file_url' => $temporaryPath,
                'file_name' => $uniqueFileName,
            ]);
            $diskType = config('filesystems.active');

            // Dispatch immediately
            ProcessFileUpload::dispatchSync($filemanager, $temporaryPath, $diskType, $originalName, $page_type, $fileType);
        }
    }
    // Mode B: chunk upload already assembled and validated; receive unique temp filenames
    elseif ($request->filled('uploaded_filenames')) {
        foreach ((array) $request->input('uploaded_filenames', []) as $uniqueFileName) {
            $uniqueFileName = basename($uniqueFileName);
            $assembledPath = storage_path('app/temp/uploads/' . $uniqueFileName);
            if (! file_exists($assembledPath)) {
                continue;
            }
            $assembledFile = new \Illuminate\Http\UploadedFile(
                $assembledPath,
                $uniqueFileName,
                mime_content_type($assembledPath) ?: null,
                null,
                true
            );
            $assembledFile = validateUploadedFile($assembledFile, 'media_library');
            $extension = $assembledFile->getClientOriginalExtension();
            $fileType = $this->getFileType($extension);
            $temporaryPath = 'temp/uploads/' . $uniqueFileName;
            $filemanager = Filemanager::create([
                'file_url' => $temporaryPath,
                'file_name' => $uniqueFileName,
            ]);
            $diskType = config('filesystems.active');

            ProcessFileUpload::dispatchSync($filemanager, $temporaryPath, $diskType, null, $page_type, $fileType);
        }
    }
    // Mode C: legacy chunk upload using original file names
    elseif ($request->filled('file_names')) {
        foreach ((array) $request->input('file_names', []) as $originalName) {
            $originalName = basename($originalName);
            $assembledPath = storage_path('app/temp/uploads/' . $originalName);
            if (! file_exists($assembledPath)) {
                continue;
            }
            $assembledFile = new \Illuminate\Http\UploadedFile(
                $assembledPath,
                $originalName,
                mime_content_type($assembledPath) ?: null,
                null,
                true
            );
            $assembledFile = validateUploadedFile($assembledFile, 'media_library');
            $extension = $assembledFile->getClientOriginalExtension();
            $fileType = $this->getFileType($extension);
            $uniqueFileName = sanitizeUploadedFilename($assembledFile, 'media_library');
            $temporaryPath = 'temp/uploads/' . $originalName;
            $filemanager = Filemanager::create([
                'file_url' => $temporaryPath,
                'file_name' => $uniqueFileName,
            ]);
            $diskType = config('filesystems.active');

            ProcessFileUpload::dispatchSync($filemanager, $temporaryPath, $diskType, $originalName, $page_type, $fileType);
        }
    }

    $message = trans('filemanager.file_added');

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json(['success' => true, 'message' => $message]);
    }

    return redirect()->route('backend.media-library.index')->with('success', $message);
}


private function getFileType($extension)
{
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico', 'tiff', 'tif'];
    $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'm4v', 'mpg', 'mpeg'];

    $extension = strtolower($extension);

    if (in_array($extension, $imageExtensions)) {
        return 'image';
    } elseif (in_array($extension, $videoExtensions)) {
        return 'video';
    } else {
        return 'other';
    }
}


//old file upload code


// public function upload(Request $request)
// {
//     $fileChunk = $request->file('file_chunk');
//     $fileName = $request->input('file_name');           // original name or server-generated token
//     $index = (int) $request->input('index');            // 0-based index
//     $totalChunks = (int) $request->input('total_chunks');
//     $temporaryDirectory = storage_path('app/temp/uploads/');
//     if (! is_dir($temporaryDirectory)) {
//         mkdir($temporaryDirectory, 0775, true);
//     }
//     $partPath = $temporaryDirectory . $fileName . '.part' . $index;
//     $fileChunk->move($temporaryDirectory, $fileName . '.part' . $index);
//     // If last chunk, merge all parts
//     if ($index + 1 === $totalChunks) {
//         $outputFilePath = $temporaryDirectory . $fileName;  // or final destination path
//         $output = fopen($outputFilePath, 'wb');             // overwrite, not append
//         for ($i = 0; $i < $totalChunks; $i++) {
//             $chunkPath = $temporaryDirectory . $fileName . '.part' . $i;
//             $in = fopen($chunkPath, 'rb');
//             stream_copy_to_stream($in, $output);
//             fclose($in);
//             unlink($chunkPath);
//         }
//         fclose($output);
//     }
//     return response()->json(['success' => true]);
// }


    public function upload(Request $request)
    {
        try {
            $request->validate([
                'file_chunk' => 'required|file',
                'file_name' => 'required|string|max:255',
                'upload_session_id' => 'nullable|string|max:64',
                'file_size' => 'nullable|integer|min:1',
                'index' => 'required|integer|min:0',
                'total_chunks' => 'required|integer|min:1',
            ]);

            $uploadService = app(\App\Services\Upload\FileUploadValidationService::class);
            $fileName = basename($request->input('file_name'));
            $sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $request->input('upload_session_id', ''));
            $assemblyName = $sessionId !== '' ? $sessionId.'_'.$fileName : $fileName;

            if ($uploadService->hasDangerousExtension($fileName)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'file' => [__('messages.invalid_upload_file_type')],
                ]);
            }

            $fileChunk = $request->file('file_chunk');
            $index = (int) $request->input('index');
            $totalChunks = (int) $request->input('total_chunks');
            $isLastChunk = ($index + 1 === $totalChunks);

            $temporaryDirectory = storage_path('app/temp/uploads/');
            if (! is_dir($temporaryDirectory)) {
                mkdir($temporaryDirectory, 0775, true);
            }

            $outputFilePath = $temporaryDirectory . $assemblyName;

            // First chunk: start fresh (handles re-uploads of the same filename)
            if ($index === 0 && file_exists($outputFilePath)) {
                @unlink($outputFilePath);
            }

            // Append this chunk directly — avoids a slow full-file merge on the last request
            $in = fopen($fileChunk->getRealPath(), 'rb');
            $out = fopen($outputFilePath, $index === 0 ? 'wb' : 'ab');
            if ($out === false) {
                fclose($in);
                throw new \RuntimeException('Unable to open output file for writing.');
            }

            @flock($out, LOCK_EX);
            stream_copy_to_stream($in, $out);
            fflush($out);
            @flock($out, LOCK_UN);
            fclose($out);
            fclose($in);

            if (! $isLastChunk) {
                return response()->json([
                    'success' => true,
                    'completed' => false,
                ]);
            }

            // Last chunk: only validate + rename (file is already fully assembled)
            set_time_limit(300);

            if (! file_exists($outputFilePath)) {
                throw new \RuntimeException('Assembled file is missing.');
            }

            clearstatcache(true, $outputFilePath);

            if ($request->filled('file_size')) {
                $expectedSize = (int) $request->input('file_size');
                $actualSize = filesize($outputFilePath);

                if ($actualSize !== $expectedSize) {
                    @unlink($outputFilePath);

                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'file' => [__('messages.invalid_upload_file_type')],
                    ]);
                }
            }

            $assembledFile = new \Illuminate\Http\UploadedFile(
                $outputFilePath,
                $fileName,
                mime_content_type($outputFilePath) ?: null,
                null,
                true
            );
            $assembledFile = validateUploadedFile($assembledFile, 'media_library');
            $uniqueFileName = sanitizeUploadedFilename($assembledFile, 'media_library');
            $uniqueTempPath = $temporaryDirectory . $uniqueFileName;

            rename($outputFilePath, $uniqueTempPath);

            return response()->json([
                'success' => true,
                'completed' => true,
                'unique_filename' => $uniqueFileName,
                'original_filename' => $fileName,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (isset($outputFilePath) && is_string($outputFilePath) && file_exists($outputFilePath)) {
                @unlink($outputFilePath);
            }

            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first('file') ?? __('messages.invalid_upload_file_type'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Chunk upload failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => __('messages.invalid_upload_file_type'),
            ], 500);
        }
    }
    //delete function chnage while old is repating url with public/storage/
    public function destroy(Request $request)
    {


        $url = $request->input('url');

        $activeDisk = env('ACTIVE_STORAGE', 'local');

        $parsedUrl = parse_url($url);
        $urlPath = ltrim($parsedUrl['path'] ?? '', '/');



        $relativePath = null;

        if ($activeDisk === 'local') {

            $storagePos = strpos($urlPath, 'storage/');
            if ($storagePos !== false) {
                $afterStorage = substr($urlPath, $storagePos + strlen('storage/'));
                $relativePath = 'public/' . ltrim($afterStorage, '/');
            } else if (strpos($urlPath, 'public/') === 0) {
                $relativePath = $urlPath;
            } else {
                $relativePath = 'public/' . $urlPath;
            }
        } else {
            // For S3/Spaces, use the key without leading public/storage
            $relativePath = ltrim($urlPath, '/');
            if (strpos($relativePath, 'storage/') === 0) {
                $relativePath = substr($relativePath, strlen('storage/'));
            }
            if (strpos($relativePath, 'public/') === 0) {
                $relativePath = substr($relativePath, strlen('public/'));
            }
        }

        $fileName = basename($relativePath);

        deleteBunnyStreamVideoByFile($fileName);

        $deleted = Storage::disk($activeDisk)->delete($relativePath);

        if ($deleted) {
            $filemanager = Filemanager::where('file_name', $fileName)->first();
            if ($filemanager) {
                $filemanager->forceDelete();
            }
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 500);
    }

   public function SearchMedia(Request $request){
        $search = $request->input('search', '');
        $storagePath = storage_path('app/public');
        $results = [];

        if (empty($search)) {
            return response()->json([
                'success' => true,
                'results' => []
            ]);
        }

        // Search through all directories recursively
        $this->searchMediaRecursively($storagePath, $search, $results);

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
   }

   private function searchMediaRecursively($path, $search, &$results, $currentFolder = '') {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;
            $relativePath = $currentFolder ? $currentFolder . '/' . $item : $item;

            if (is_dir($itemPath)) {
                // Recursively search subdirectories
                $this->searchMediaRecursively($itemPath, $search, $results, $relativePath);
            } else {
                // Check if it's an image or video file
                $isVideo = preg_match('/\.(mp4|webm|avi|mov|mkv)$/i', $item);
                $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $item);

                if (($isVideo || $isImage) && stripos($item, $search) !== false) {
                    // Derive page_type from folder structure
                    $pageType = 'default';
                    $folderSegments = explode('/', $currentFolder);
                    $imageIndex = array_search('image', $folderSegments, true);
                    $videoIndex = array_search('video', $folderSegments, true);

                    if ($imageIndex !== false && $imageIndex > 0) {
                        $pageType = $folderSegments[$imageIndex - 1];
                    } elseif ($videoIndex !== false && $videoIndex > 0) {
                        $pageType = $folderSegments[$videoIndex - 1];
                    } elseif (!empty($folderSegments)) {
                        $pageType = end($folderSegments);
                    }

                    $mediaUrl = '';
                    if ($isVideo || $isImage) {
                        $type = $isVideo ? 'video' : 'image';
                        $mediaUrl = setBaseUrlWithFileName($item, $type, $pageType);
                    }

                    $results[] = [
                        'name' => $item,
                        'path' => $relativePath,
                        'is_dir' => false,
                        'size' => filesize($itemPath),
                        'modified' => filemtime($itemPath),
                        'media_url' => $mediaUrl,
                        'is_video' => $isVideo,
                        'is_image' => $isImage,
                        'folder' => $currentFolder
                    ];
                }
            }
        }
    }

    /**
     * Get folder contents via AJAX with pagination support
     */
    public function getFolderContents(Request $request)
    {
        $folder = $request->get('folder', '');
        $limit = (int) $request->get('limit', 60);
        $offset = (int) $request->get('offset', 0);
        $activeDisk = env('ACTIVE_STORAGE', 'local');
        $contents = [];
        $allItems = [];

        try {
            if ($activeDisk === 'local') {
                // Local storage
                $storagePath = storage_path('app/public');
                $fullPath = $folder ? $storagePath . '/' . $folder : $storagePath;

                if (is_dir($fullPath)) {
                    $items = array_diff(scandir($fullPath), ['.', '..']);
                    foreach ($items as $item) {
                        $itemAbsolutePath = $fullPath . '/' . $item;
                        $isDir = is_dir($itemAbsolutePath);
                        // Build the relative path that the frontend can feed back to us
                        $relativePath = ltrim(($folder ? $folder . '/' : '') . $item, '/');
                        $allItems[] = $this->formatItem($item, $itemAbsolutePath, $folder, 'local', $isDir, $relativePath);
                    }
                }
            } else {
                // Remote storage (Bunny, S3, etc.)
                $disk = Storage::disk($activeDisk);
                $files = $disk->files($folder);
                $directories = $disk->directories($folder);

                foreach ($directories as $dir) {
                    $allItems[] = $this->formatItem(basename($dir), $dir, $folder, $activeDisk, true, trim($dir, '/'));
                }

                foreach ($files as $file) {
                    $allItems[] = $this->formatItem(basename($file), $file, $folder, $activeDisk, false, trim($file, '/'));
                }
            }

            // Sort items by modification time (newest first) so newly uploaded files appear first
            usort($allItems, function($a, $b) {
                $timeA = $a['modified'] ?? 0;
                $timeB = $b['modified'] ?? 0;
                return $timeB <=> $timeA; // Descending order (newest first)
            });

            // Apply pagination
            $totalItems = count($allItems);
            $contents = array_slice($allItems, $offset, $limit);
            $nextOffset = ($offset + $limit) < $totalItems ? ($offset + $limit) : null;

        } catch (\Exception $e) {
            Log::error('Error getting folder contents: ' . $e->getMessage());
        }


        return response()->json([
            'success' => true,
            'contents' => $contents,
            'pagination' => [
                'next_offset' => $nextOffset,
                'total_items' => $totalItems ?? 0,
                'current_offset' => $offset,
                'limit' => $limit,
                'has_more' => $nextOffset !== null
            ]
        ]);
    }

    /**
     * Format a file or directory item for response.
     */
    private function formatItem($name, $absolutePath, $folder, $disk = 'local', $isDir = false, $relativePath = null)
    {
        $isVideo = preg_match('/\.(mp4|webm|avi|mov|mkv)$/i', $name);
        $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $name);

        // Derive page type
        $pageType = 'default';
        if (!empty($folder)) {
            $segments = explode('/', $folder);
            $imageIndex = array_search('image', $segments, true);
            $videoIndex = array_search('video', $segments, true);

            if ($imageIndex !== false && $imageIndex > 0) {
                $pageType = $segments[$imageIndex - 1];
            } elseif ($videoIndex !== false && $videoIndex > 0) {
                $pageType = $segments[$videoIndex - 1];
            } else {
                $pageType = end($segments) ?: 'default';
            }
        }

        $mediaUrl = '';
        if (!$isDir && ($isVideo || $isImage)) {
            $type = $isVideo ? 'video' : 'image';
            $mediaUrl = setBaseUrlWithFileName($name, $type, $pageType);
        }

        // When local, compute size/mtime using absolute path; expose relative path to the client
        $size = $disk === 'local' && !$isDir && is_file($absolutePath) ? filesize($absolutePath) : 0;
        $modified = $disk === 'local' && !$isDir && file_exists($absolutePath) ? filemtime($absolutePath) : time();

        return [
            'name' => $name,
            'path' => $relativePath !== null ? $relativePath : $absolutePath,
            'is_dir' => $isDir,
            'size' => $size,
            'modified' => $modified,
            'media_url' => $mediaUrl,
            'is_video' => $isVideo,
            'is_image' => $isImage,
        ];
    }

    /**
     * Get media URL using helper function
     */
    public function getMediaUrl(Request $request)
    {
        $fileName = $request->get('file');
        $type = $request->get('type', 'image');
        $pageType = $request->get('page_type', 'default');

        // Call the helper function
        $url = setBaseUrlWithFileName($fileName, $type, $pageType);

        return response()->json([
            'success' => true,
            'url' => $url
        ]);
    }

}