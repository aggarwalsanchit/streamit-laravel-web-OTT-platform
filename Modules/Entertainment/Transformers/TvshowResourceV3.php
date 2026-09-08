<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Genres\Transformers\GenresResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Subscriptions\Models\Plan;
use Modules\Entertainment\Models\Entertainment;

class TvshowResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        // Get genres directly from the relationship
        $genre_data = [];
        $genres = $this->entertainmentGenerMappings;
        if(!empty($genres)){
            foreach($genres as $genre) {
                $genre_data[] = $genre->genre->name ?? null;
            }
        }


        return [
            'id' => $this->id,
            'poster_image' => setBaseUrlWithFileName($this->poster_image,'image',$this->type),
            'poster_tv_image' => setBaseUrlWithFileName($this->poster_tv_url,'image',$this->type),
            'details'=>[
                'name' => $this->name,
                'type' => $this->type,
                'release_date' => $this->release_date ? formatDate($this->release_date) : null,
                'movie_access' => $this->movie_access,
                'imdb_rating'=> $this->IMDb_rating ?? $this->imdb_rating ?? null,
                'is_restricted' => $this->is_restricted,
                'is_device_supported'=> $this->isDeviceSupported,
                "has_content_access"=> $this->has_content_access,
                "required_plan_level"=>$this->required_plan_level,
            ],
            'trailer_data'=>[
                'trailer_url_type' => $this->trailer_url_type,
                'trailer_url' => $this->trailer_url_type == 'Local' ? setBaseUrlWithFileName($this->trailer_url, 'video', $this->type) : $this->trailer_url,
            ]
        ];
    }
}
