<?php

namespace Modules\LiveTV\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Subscriptions\Models\Plan;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\LiveTV\Transformers\Backend\LiveTvChannelResourceV3;

class LiveTvChannelDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $plans = [];
        $plan = $this->plan;
        if($plan){
            $plans = Plan::where('level', '<=', $plan->level)->get();
        }
        $moreItems = LiveTvChannel::where('category_id', $this->category_id)->get()->except($this->id);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'access' => $this->access,
            'plan_id' => $this->plan_id,
            'plan_level' => $this->plan->level ?? 0,
            'description' => strip_tags($this->description),
            'poster_image' => setBaseUrlWithFileName($this->poster_url, 'image', 'livetv'),
            'category' => optional($this->TvCategory)->name ?? null,
            'stream_type' => optional($this->TvChannelStreamContentMappings)->stream_type ?? null,
            'embedded' => optional($this->TvChannelStreamContentMappings)->embedded ?? null,
            'server_url' => optional($this->TvChannelStreamContentMappings)->server_url ?? null,
            'server_url1' => optional($this->TvChannelStreamContentMappings)->server_url1 ?? null,
            'plans' => PlanResource::collection($plans),
            'more_items' => LiveTvChannelResourceV3::collection($moreItems),
            'status' => $this->status,
            'poster_tv_image' => setBaseUrlWithFileName($this->poster_tv_url, 'image', 'livetv'),
            'thumbnail_image' => $this->thumb_url != null ? setBaseUrlWithFileName($this->thumb_url, 'image', 'livetv') : setBaseUrlWithFileName($this->poster_url, 'image', 'livetv'),
        ];
    }
}
