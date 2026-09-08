<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class SeasonResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'poster_image' => setBaseUrlWithFileName($this->poster_image,'image','season'),
            'poster_tv_image' => setBaseUrlWithFileName($this->poster_tv_url,'image','season'),
            'details' => [
                'name' => $this->name,
                'type' => 'season',
                'release_date' => $this->release_date,
                'movie_access' => $this->access,
                'is_restricted' => $this->is_restricted,
                'is_device_supported' => $this->isDeviceSupported,
                'has_content_access' => $this->has_content_access,
                'required_plan_level' => $this->required_plan_level,
                'tv_show_data' => $this->tv_show_data,
            ]
        ];
    }
}
