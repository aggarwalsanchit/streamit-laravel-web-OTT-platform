<?php

namespace Modules\Entertainment\Repositories;

use Modules\Entertainment\Models\Entertainment;
use Modules\Entertainment\Models\EntertainmentStreamContentMapping;
use Modules\Entertainment\Models\EntertainmentGenerMapping;
use Modules\Entertainment\Models\EntertainmentTalentMapping;
use Modules\Entertainment\Models\EntertainmnetDownloadMapping;

use Auth;
use Modules\Entertainment\Models\EntertainmentCountryMapping;

class EntertainmentRepository implements EntertainmentRepositoryInterface
{
    public function all()
    {
        return Entertainment::all();
    }
    public function movieGenres(int $id)
    {
        return EntertainmentGenerMapping::where('entertainment_id',$id)->with('genre')->get()->pluck('genre.name')->unique();
    }

    public function moviecountries(int $id)
    {
        return EntertainmentCountryMapping::where('entertainment_id',$id)->with('country')->get()->pluck('country.name')->unique();
    }


    public function find(int $id)
    {
        $entertainment = Entertainment::query();

        if (Auth::user()->hasRole('user')) {
            $entertainment->whereNull('deleted_at');
        }

        $genre = $entertainment->findOrFail($id);

        return $genre;
    }


    public function create(array $data)
    {
        return Entertainment::create($data);
    }

    public function update(int $id, array $data)
    {
        $entertainment = Entertainment::findOrFail($id);

        if ($data['movie_access'] == 'free') {
            $data['plan_id'] = null;
        }

        if (isset($data['name']) && !empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug(trim($data['name']));
        }

        $entertainment->update($data);

        if (isset($data['genres'])) {
            $this->updateGenreMappings($entertainment->id, $data['genres']);
        }
        if (isset($data['countries'])) {
            $this->updateCountryMappings($entertainment->id, $data['countries']);
        }

        if (isset($data['actors'])) {
            $this->updateTalentMappings($entertainment->id, $data['actors'], 'actor');
        }

        if (isset($data['directors'])) {
            $this->updateTalentMappings($entertainment->id, $data['directors'], 'director');
        }

        if (isset($data['enable_quality']) && $data['enable_quality'] == 1) {
            $this->updateQualityMappings($entertainment->id, $data);
        }

        return $data;
        return $entertainment;
    }

    public function delete(int $id)
    {
        $entertainment = Entertainment::findOrFail($id);
        $entertainment->delete();
        return $entertainment;
    }

    public function restore(int $id)
    {
        $entertainment = Entertainment::withTrashed()->findOrFail($id);
        $entertainment->restore();
        return $entertainment;
    }

    public function forceDelete(int $id)    
    {
        $entertainment = Entertainment::withTrashed()->findOrFail($id);
        $entertainment->forceDelete();
        return $entertainment;
    }

    public function query()
    {
        $entertainemnt = Entertainment::with('entertainmentGenerMappings');

        if (Auth::user()->hasRole('user')) {
            $entertainemnt->whereNull('deleted_at'); // Only include non-deleted
        }

        return $entertainemnt;
    }
    public function list(array $filters)
    {

        $query = Entertainment::with([
            'entertainmentGenerMappings',
            'plan',
            'entertainmentReviews',
            'entertainmentTalentMappings',
            'entertainmentStreamContentMappings',
            'entertainmentDownloadMappings'
        ])->where('status', 1);


        return $query;
    }


    public function saveGenreMappings(array $genres, int $entertainmentId)
    {
        foreach ($genres as $genre) {
            $genre_data = [
                'entertainment_id' => $entertainmentId,
                'genre_id' => $genre
            ];

            EntertainmentGenerMapping::create($genre_data);
        }

    }
    public function saveCountryMappings(array $countries, int $entertainmentId)
    {
        foreach ($countries as $country) {
            $country_data = [
                'entertainment_id' => $entertainmentId,
                'country_id' => $country
            ];

            EntertainmentCountryMapping::create($country_data);
        }

    }

    public function saveTalentMappings(array $talents, int $entertainmentId)
    {
        foreach ($talents as $talent) {
            $talent_data = [
                'entertainment_id' => $entertainmentId,
                'talent_id' => $talent
            ];

            EntertainmentTalentMapping::create($talent_data);
        }
    }

    public function saveQualityMappings(int $entertainmentId, array $videoQuality, array $qualityVideoUrl, array $videoQualityType, array $qualityVideoFile)
    {

        foreach ($videoQuality as $index => $quality) {
            if ($quality != '' && ($qualityVideoUrl[$index] != '' || $qualityVideoFile[$index] != '') && $videoQualityType[$index] != '' ) {
                EntertainmentStreamContentMapping::create([
                    'entertainment_id' => $entertainmentId,
                    'url' => $qualityVideoUrl[$index] ?? extractFileNameFromUrl($qualityVideoFile[$index],'movie'),
                    'type' => $videoQualityType[$index],
                    'quality' => $quality,
                ]);
            }
        }
    }

    protected function updateGenreMappings(int $entertainmentId, array $genres)
    {
        EntertainmentGenerMapping::where('entertainment_id', $entertainmentId)->forceDelete();
        $this->saveGenreMappings($genres, $entertainmentId);
    }
    protected function updateCountryMappings(int $entertainmentId, array $countries)
    {
        EntertainmentCountryMapping::where('entertainment_id', $entertainmentId)->forceDelete();
        $this->saveCountryMappings($countries, $entertainmentId);
    }

    protected function updateTalentMappings(int $entertainmentId, array $talents, string $type)
    {
        EntertainmentTalentMapping::where('entertainment_id', $entertainmentId)
        ->whereHas('talentprofile', function ($query) use ($type) {
            $query->where('type', $type);
        })
        ->forceDelete();

        $this->saveTalentMappings($talents, $entertainmentId);
    }

    protected function updateQualityMappings(int $entertainmentId, array $requestData)
    {
        // dd($requestData);
        $qualityVideoUrlInput = $requestData['quality_video_url_input'] ?? [];
        $qualityVideo = $requestData['quality_video'] ?? [];

    $Quality_video_url = array_map(function($urlInput, $index) use ($qualityVideo) {
        return $urlInput !== null ? $urlInput : ($qualityVideo[$index] ?? null);
    }, $qualityVideoUrlInput, array_keys($qualityVideoUrlInput));
        $videoQuality = $requestData['video_quality'] ?? [];
        $videoQualityType = $requestData['video_quality_type'] ?? [];


        if (!empty($videoQuality) && !empty($Quality_video_url) && !empty($videoQualityType)) {
            EntertainmentStreamContentMapping::where('entertainment_id', $entertainmentId)->forceDelete();
            foreach ($videoQuality as $index => $videoquality) {

                if ($videoquality != '' && $Quality_video_url[$index] != '' && $videoQualityType[$index]) {
                    $url = isset($Quality_video_url[$index])
                    ? ($videoQualityType[$index] == 'Local'
                        ? extractFileNameFromUrl($Quality_video_url[$index],'movie')
                        : $Quality_video_url[$index])
                    : null;
                    $type = $videoQualityType[$index] ?? null;
                    $quality = $videoquality;

                    EntertainmentStreamContentMapping::create([
                        'entertainment_id' => $entertainmentId,
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

        $entertainment = Entertainment::findOrFail($id);

        // Persist main download_type and download_url on entertainment
        $downloadType = $data['video_upload_type_download'] ?? null;
        $downloadUrl = null;
        if (!empty($downloadType)) {
            if ($downloadType === 'Local') {
                $fileVal = $data['video_file_input_download'] ?? null;
                $downloadUrl = $fileVal ? (function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($fileVal,'movie') : $fileVal) : null;
            } else {
                $downloadUrl = $data['video_url_input_download'] ?? null;
            }
            $data['download_type'] = $downloadType;
            $data['download_url'] = $downloadUrl;
        }


        $entertainment->update([
            'enable_download_quality' => $data['enable_download_quality'] ?? 0,
            'download_type'           => $downloadType,
            'download_url'            => $downloadUrl,
        ]);

        EntertainmnetDownloadMapping::where('entertainment_id', $id)->forceDelete();

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
                            $rowUrl = function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($val,'movie') : $val;
                        }
                    } else {
                        $rowUrl = $urlInputs[$index] ?? null;
                    }

                    if (!empty($rowUrl)) {
                        EntertainmnetDownloadMapping::create([
                            'entertainment_id' => $entertainment->id,
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
