<?php

namespace Modules\Video\Repositories;

use Modules\Video\Models\Video;
use Auth;
use Modules\Video\Models\VideoDownloadMapping;

class VideoRepository implements VideoRepositoryInterface
{
    public function all()
    {
        $query = Video::query();

        $query->where('status', 1)
            ->orderBy('updated_at', 'desc')->get();

        return $query;
    }

    public function find(int $id)
    {
        $videoQuery = Video::query();

        if (Auth::user()->hasRole('user')) {
            $videoQuery->whereNull('deleted_at'); // Only show non-trashed Video
        }

        $video = $videoQuery->withTrashed()->findOrFail($id);

        return $video;
    }

    public function create(array $data)
    {
        return Video::create($data);
    }

    public function update(int $id, array $data)    
    {
        $video = Video::findOrFail($id);
        $video->update($data);
        return $video;
    }

    public function delete(int $id)    
    {
        $video = Video::findOrFail($id);
        $video->delete();
        return $video;
    }

    public function restore(int $id)    
    {
        $video = Video::withTrashed()->findOrFail($id);
        $video->restore();
        return $video;
    }

    public function forceDelete(int $id)    
    {
        $video = Video::withTrashed()->findOrFail($id);
        $video->forceDelete();
        return $video;
    }

    public function query()
    {

        $videoQuery = Video::query()->withTrashed();

        if (Auth::user()->hasRole('user')) {
            $videoQuery->whereNull('deleted_at');
        }

        return $videoQuery;

    }

    public function list(array $perPage, string $searchTerm = null)    
    {
        $query = Video::query();

        if ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        $query->where('status', 1)
            ->orderBy('updated_at', 'desc');

        return $query->paginate($perPage);
    }
    public function storeDownloads(array $data, int $id)    
    {
        $video = Video::findOrFail($id);

        $downloadType = $data['video_upload_type_download'] ?? null;
        $downloadUrl = null;
        if (!empty($downloadType)) {
            if ($downloadType === 'Local') {
                $fileVal = $data['video_file_input_download'] ?? null;
                $downloadUrl = $fileVal ? (function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($fileVal,'video') : $fileVal) : null;
            } else {
                $downloadUrl = $data['video_url_input_download'] ?? null;
            }
            $data['download_type'] = $downloadType;
            $data['download_url'] = $downloadUrl;
        }

        $video->update([
            'enable_download_quality' => $data['enable_download_quality'] ?? 0,
            'download_type'           => $downloadType,
            'download_url'            => $downloadUrl,
        ]);

        VideoDownloadMapping::where('video_id', $id)->forceDelete();

        if (isset($data['enable_download_quality']) && $data['enable_download_quality'] == 1) {
            $types = $data['quality_video_download_type'] ?? [];
            $qualities = $data['video_download_quality'] ?? [];
            $urlInputs = $data['download_quality_video_url'] ?? [];
            $fileInputs = $data['download_quality_video'] ?? [];

            if (!empty($types) && !empty($qualities)) {
                $max = max(count($types), count($qualities), count($urlInputs), count($fileInputs));
                for ($index = 0; $index < $max; $index++) {
                    $type = $types[$index] ?? '';
                    $quality = $qualities[$index] ?? '';
                    if ($type === '' || $quality === '') { continue; }

                    $rowUrl = null;
                    if ($type === 'Local') {
                        $val = $fileInputs[$index] ?? null;
                        if ($val) {
                            $rowUrl = function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($val,'video') : $val;
                        }
                    } else {
                        $rowUrl = $urlInputs[$index] ?? null;
                    }

                    if (!empty($rowUrl)) {
                        VideoDownloadMapping::create([
                            'video_id' => $video->id,
                            'url' => $rowUrl,
                            'type' => $type,
                            'quality' => $quality,
                        ]);
                    }
                }
            }
        }
    }


}
