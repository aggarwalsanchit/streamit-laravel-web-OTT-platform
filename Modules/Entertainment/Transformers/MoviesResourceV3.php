<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Genres\Transformers\GenresResource;
use Modules\Entertainment\Models\Entertainment;

class MoviesResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'poster_image' => setBaseUrlWithFileName($this->poster_image,'image',$this->type),
            'poster_tv_image' => setBaseUrlWithFileName($this->poster_tv_url,'image',$this->type),
            'details'=>[
                'name' => $this->name,
                'type' => $this->type,
                'release_date' => $this->release_date ? formatDate($this->release_date):null,
                'access' => $this->movie_access,
                'imdb_rating'=> $this->IMDb_rating ?? $this->imdb_rating ?? null,
                'is_restricted' => $this->is_restricted,
                'is_device_supported'=> $this->isDeviceSupported,
                "has_content_access"=> $this->has_content_access, // $this->movie_access == 'free'|| $this->movie_access == 'pay-per-view' ? 1 : $this->plan_id,
                "required_plan_level"=>$this->required_plan_level, // $this->userPlanId  >= $this->plan_id ? 1 : 0  ,
            ],
            'trailer_data'=>[
                'trailer_url_type' => $this->trailer_url_type,
                'trailer_url' => $this->trailer_url
            ],
        ];
    }
}
