<?php

namespace Modules\Episode\Repositories;

use Modules\Episode\Models\Episode;
use Modules\Episode\Models\EpisodeStreamContentMapping;
use Modules\Episode\Models\EpisodeDownloadMapping;
use Auth;

class EpisodeRepository implements EpisodeRepositoryInterface
{
    public function all()
    {
        $query = Episode::query();

        $query->where('status', 1)
              ->orderBy('updated_at', 'desc')->get();

        return $query;
    }

    public function find(int $id)   
    {
        $episode = Episode::query();

        if (Auth::user()->hasRole('user')) {
            $episode->whereNull('deleted_at'); // Only show non-trashed genres
        }

        $episode = $episode->withTrashed()->findOrFail($id);

        return $episode;
    }

    public function create(array $data)
    {
        return Episode::create($data);
    }

    public function update(int $id, array $data)
    {
        $episode = Episode::findOrFail($id);

        $episode->update($data);

        if (isset($data['enable_quality']) && $data['enable_quality'] == 1) {
            $this->updateQualityMappings($episode->id, $data);
        }
        return $episode;
    }

    public function delete(int $id)
    {
        $episode = Episode::findOrFail($id);
        $episode->delete();
        return $episode;
    }

    public function restore($id)
    {
        $episode = Episode::withTrashed()->findOrFail($id);
        $episode->restore();
        return $episode;
    }

    public function forceDelete(int $id)    
    {
        $episode = Episode::withTrashed()->findOrFail($id);
        $episode->forceDelete();
        return $episode;
    }

    public function query()
    {

        $episode=Episode::query()->withTrashed();

        if(Auth::user()->hasRole('user') ) {
            $episode->whereNull('deleted_at');
        }

        return $episode;

    }

    public function list(array $perPage, string $searchTerm = null)
    {
        $query = Episode::query();

        if ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        $query->where('status', 1)
              ->orderBy('updated_at', 'desc');

        return $query->paginate($perPage);
    }

    public function saveQualityMappings(int $episodeId, array $videoQuality, array $qualityVideoUrl, array $videoQualityType, array $qualityVideoFile)
    {
        foreach ($videoQuality as $index => $quality) {
            if ($quality != '' && ($qualityVideoUrl[$index] != '' || $qualityVideoFile[$index] != '') && $videoQualityType[$index] != '') {
                EpisodeStreamContentMapping::create([
                    'episode_id' => $episodeId,
                    'url' => $qualityVideoUrl[$index] ?? extractFileNameFromUrl($qualityVideoFile[$index],'episode'),
                    'type' => $videoQualityType[$index],
                    'quality' => $quality,
                ]);
            }
        }
    }



    protected function updateQualityMappings(int $episodeId, array $requestData)  
    {
        $qualityVideoUrlInput = $requestData['quality_video_url_input'] ?? [];
        $qualityVideo = $requestData['quality_video'] ?? [];

    $Quality_video_url = array_map(function($urlInput, $index) use ($qualityVideo) {
        return $urlInput !== null ? $urlInput : ($qualityVideo[$index] ?? null);
    }, $qualityVideoUrlInput, array_keys($qualityVideoUrlInput));
        $videoQuality = $requestData['video_quality'];
        $videoQualityType = $requestData['video_quality_type'];

        if (!empty($videoQuality) && !empty($Quality_video_url) && !empty($videoQualityType)) {
            EpisodeStreamContentMapping::where('episode_id', $episodeId)->forceDelete();
            foreach ($videoQuality as $index => $videoquality) {
                if ($videoquality != '' && $Quality_video_url[$index] != '' && $videoQualityType[$index]) {
                    $url = isset($Quality_video_url[$index])
                    ? ($videoQualityType[$index] == 'Local'
                        ? extractFileNameFromUrl($Quality_video_url[$index],'episode')
                        : $Quality_video_url[$index])
                    : null;
                    $type = $videoQualityType[$index] ?? null;
                    $quality = $videoquality;

                    EpisodeStreamContentMapping::create([
                        'episode_id' => $episodeId,
                        'url' => $url,
                        'type' => $type,
                        'quality' => $quality
                    ]);
                }
            }
        }
    }

    public function storeDownloads(array $data, int $id)
    {
        $episode = Episode::findOrFail($id);

        $downloadType = $data['video_upload_type_download'] ?? null;
        $downloadUrl = null;
        if (!empty($downloadType)) {
            if ($downloadType === 'Local') {
                $fileVal = $data['video_file_input_download'] ?? null;
                $downloadUrl = $fileVal ? (function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($fileVal,'episode') : $fileVal) : null;
            } else {
                $downloadUrl = $data['video_url_input_download'] ?? null;
            }
            $data['download_type'] = $downloadType;
            $data['download_url'] = $downloadUrl;
        }

        $episode->update([
            'enable_download_quality' => $data['enable_download_quality'] ?? 0,
            'download_type'           => $downloadType,
            'download_url'            => $downloadUrl,
        ]);

        EpisodeDownloadMapping::where('episode_id', $id)->forceDelete();

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
                            $rowUrl = function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($val,'episode') : $val;
                        }
                    } else {
                        $rowUrl = $urlInputs[$index] ?? null;
                    }

                    if (!empty($rowUrl)) {
                        EpisodeDownloadMapping::create([
                            'episode_id' => $episode->id,
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
