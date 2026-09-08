<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class EpisodeResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $genre_data = [];
        // Safely access entertainmentdata relationship with null check
        if ($this->entertainmentdata && $this->entertainmentdata->entertainmentGenerMappings) {
            $genres = $this->entertainmentdata->entertainmentGenerMappings;
            if (!empty($genres)) {
                foreach ($genres as $genre) {
                    if ($genre->genre && isset($genre->genre->status) && (int)$genre->genre->status == 1) {
                        $genre_data[] = $genre->genre->name;
                    }
                }
            }
        }

        return [
            'id' => $this->id,
            'poster_image' => setBaseUrlWithFileName($this->poster_image,'image','episode'),
            'poster_tv_image' => setBaseUrlWithFileName($this->poster_tv_url,'image','episode'),
            'details' => [
                'name' => $this->name,
                'type' => 'episode',
                'slug' => $this->slug,
                'genres' => $genre_data,
                'language' => $this->entertainmentdata->language ?? null,
                'release_date' => $this->release_date ? formatDate($this->release_date):null,
                'access' => $this->access,
                'is_restricted' => $this->is_restricted,
                'is_device_supported' => $this->isDeviceSupported,
                'has_content_access' => $this->has_content_access, // $this->access == 'free'|| $this->access == 'pay-per-view' ? 1 : $this->plan_id,
                'required_plan_level' => $this->required_plan_level,
                'imdb_rating' => $this->IMDb_rating ?? $this->imdb_rating ?? null,
                'tv_show_data' => $this->tv_show_data,
                'season_data' => $this->season_data,
                'duration'=>$this->duration,
                'short_description' => $this->short_desc,
                'watched_duration' => $this->watched_time ?? '00:00:01',
            ],
            'download_data' => $this->download_data,
            'rental_data' => $this->rental ?? []
        ];
    }
}
