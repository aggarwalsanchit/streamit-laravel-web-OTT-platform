<?php

namespace Modules\LiveTV\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Subscriptions\Models\Plan;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\LiveTV\Transformers\LiveTvChannelResource;

class LiveTvChannelDetailsResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'details' => [
                'name' => $this->name,
                'type' => 'livetv',
                'server_url'=> optional($this->TvChannelStreamContentMappings)->server_url ?? null,
                'access' => $this->access,
                'is_device_supported'=> $this->isDeviceSupported ?? null,
                "has_content_access"=> $this->has_content_access, // $this->access == 'free' || $this->access == 'pay-per-view' ? 1 : ($this->plan_id ?? 0),
                "required_plan_level"=> $this->required_plan_level ?? 0,
                'description' => strip_tags($this->description),
                "thumbnail_image" => setBaseUrlWithFileName($this->poster_url,'image','livetv'),
                'category' => $this->TvCategory->name ?? null,
            ],
            'video_qualities'=> $this->video_qualities,
            'suggested_content' => LiveTvChannelResourceV3::collection($this->moreItems),
        ];
    }
}
