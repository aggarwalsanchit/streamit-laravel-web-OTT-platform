<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class CommonContentList  extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'poster_image' => $this->posterImage,
            'poster_tv_image'=>$this->poster_tv_image,
            'details'=>[
                'name' => $this->name,
                'type' => $this->type,
                'is_device_supported' => $this->isDeviceSupported,
                'access'=> $this->access,
                'imdb_rating'=> $this->IMDb_rating ?? $this->imdb_rating ?? null,
                'has_content_access'=> $this->has_content_access, // $this->access == 'free'|| $this->access == 'pay-per-view' ? 1 : $this->plan_id,
                'required_plan_level'=> $this->required_plan_level, // $this->userPlanId  >= $this->plan_id ? 1 : 0  ,
                'season_data' => $this->season_data,
                'tv_show_data' => $this->tv_show_data,
                'is_restricted' => $this->is_restricted,
            ]
        ];

    }


}
