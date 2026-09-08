<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Genres\Transformers\GenresResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Subscriptions\Models\Plan;
use Modules\Entertainment\Models\Entertainment;

class TvshowResourceBannerV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        // OPTIMIZATION: Use collection filter and pluck for genres (faster than foreach loop)
        // Double-check status to ensure inactive genres are excluded
        $genre_data = $this->entertainmentGenerMappings
            ->filter(function($mapping) {
                return $mapping->genre && 
                       isset($mapping->genre->status) && 
                       (int)$mapping->genre->status === 1 &&
                       empty($mapping->genre->deleted_at);
            })
            ->pluck('genre.name')
            ->filter()
            ->values()
            ->toArray();

        // OPTIMIZATION: Cache property access to avoid repeated lookups
        $posterImage = $this->poster_image;
        $posterTvUrl = $this->poster_tv_url;
        $type = $this->type;
        $releaseDate = $this->release_date;
        $isRestricted = $this->is_restricted ?? 0;
        $imdbRating = $this->IMDb_rating ?? $this->imdb_rating ?? null;

        return [
            'poster_image' => setBaseUrlWithFileName($posterImage, 'image', $type),
            'poster_tv_image' => setBaseUrlWithFileName($posterTvUrl, 'image', $type),
            'details'=>[
                'id' => $this->id,
                'name' => $this->name,
                'type' => $type,
                'release_date' => $releaseDate ? formatDate($releaseDate) : null,
                'access' => $this->movie_access,
                'is_restricted' => $isRestricted,
                'is_device_supported'=> $this->isDeviceSupported ?? 0,
                "has_content_access"=> $this->has_content_access ?? 0,
                "required_plan_level"=> $this->required_plan_level ?? 0,
                'imdb_rating' => $imdbRating,
                'language' => $this->language ?? 'english',
                'duration' => $this->duration ?? null,
                'is_in_watchlist' => $this->is_watch_list ?? 0,
                'genres' => $genre_data,
            ],
            'trailer_data'=>[
                'trailer_url_type' => $this->trailer_url_type ?? null,
                'trailer_url' => $this->trailer_url ?? null,
            ],
        ];
    }
}
