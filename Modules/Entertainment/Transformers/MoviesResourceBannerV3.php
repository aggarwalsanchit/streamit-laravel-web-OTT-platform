<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Genres\Transformers\GenresResource;
use Modules\Entertainment\Models\Entertainment;

class MoviesResourceBannerV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {

        $genre_data = [];
        $genres = $this->entertainmentGenerMappings;
        if (!empty($genres)) {
            foreach ($genres as $genre) {
                if ($genre->genre && 
                    isset($genre->genre->status) && 
                    (int)$genre->genre->status === 1 &&
                    empty($genre->genre->deleted_at)) {
                    $genre_data[] = $genre->genre->name;
                }
            }
        }
        return [
            'poster_image' => setBaseUrlWithFileName($this->poster_image,'image',$this->type),
            'poster_tv_image' => setBaseUrlWithFileName($this->poster_tv_url,'image',$this->type),
            'details'=>[
                'id' => $this->id,
                'name' => $this->name,
                'type' => $this->type,
                'release_date' => $this->release_date ? formatDate($this->release_date):null,
                'access' => $this->movie_access,
                'genres' => $genre_data,
                'is_restricted' => $this->is_restricted,
                'is_device_supported'=> $this->isDeviceSupported,
                "has_content_access"=> $this->has_content_access, // $this->movie_access == 'free'|| $this->movie_access == 'pay-per-view' ? 1 : $this->plan_id,
                "required_plan_level"=>$this->required_plan_level, // $this->userPlanId  >= $this->plan_id ? 1 : 0  ,
                'imdb_rating' => $this->IMDb_rating,
                'language' => $this->language ?? 'english',
                'duration' => $this->duration ?? null,
                'is_restricted' => $this->is_restricted ?? 0,
                'is_in_watchlist' => $this->is_watch_list ?? 0,
            ],
            'trailer_data'=>[
                'trailer_url_type' => $this->trailer_url_type,
                'trailer_url' => $this->trailer_url,
            ],
        ];
    }
}
