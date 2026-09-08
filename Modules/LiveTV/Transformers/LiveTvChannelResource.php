<?php

namespace Modules\LiveTV\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Subscriptions\Models\Plan;

class LiveTvChannelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {

        return [
            'id' => $this->id,
            'show_premium_badge' => $this->show_premium_badge ?? false,
            'name' => $this->name,
            'plan_id' => $this->plan_id,
            'plan_level' => $this->plan->level ?? 0,
            'slug' => $this->slug,
            'description' => strip_tags($this->description),
            'poster_image' => setBaseUrlWithFileName($this->poster_url, 'image', 'livetv'),
            'thumbnail_image' => $this->thumb_url != null ? setBaseUrlWithFileName($this->thumb_url, 'image', 'livetv') : setBaseUrlWithFileName($this->poster_url, 'image', 'livetv'),
            'category' => optional($this->TvCategory)->name ?? null,
            'stream_type' => optional($this->TvChannelStreamContentMappings)->stream_type ?? null,
            'embedded' => optional($this->TvChannelStreamContentMappings)->embedded ?? null,
            'server_url' => optional($this->TvChannelStreamContentMappings)->server_url ?? null,
            'server_url1' => optional($this->TvChannelStreamContentMappings)->server_url1 ?? null,
            'status' => $this->status,
            'access'=>$this->access,
            'poster_tv_image' => setBaseUrlWithFileName($this->poster_tv_url, 'image', 'livetv'),
        ];
    }
}
