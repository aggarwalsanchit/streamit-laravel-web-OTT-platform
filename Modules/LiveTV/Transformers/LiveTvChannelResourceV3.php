<?php

namespace Modules\LiveTV\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class LiveTvChannelResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'poster_image' => $this->poster_image,
            'poster_tv_image' => setBaseUrlWithFileName($this->poster_tv_url,'image','livetv'),
            'details'=>[
                'name' => $this->name,
                'type' => 'livetv',
                'access' => $this->access,
                'is_device_supported'=> $this->isDeviceSupported ?? null,
                "has_content_access"=> $this->has_content_access, //->access == 'free'|| $this->access == 'pay-per-view' ? 1 : ($this->plan_id ?? 0),
                "required_plan_level"=> $this->required_plan_level ?? null,
                'is_restricted' => $this->is_restricted ?? 0,
                "category"=> $this->TvCategory->name ?? null,

            ]
        ];
    }
}
