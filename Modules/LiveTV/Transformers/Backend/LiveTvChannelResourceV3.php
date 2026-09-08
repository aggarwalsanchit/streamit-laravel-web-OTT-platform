<?php

namespace Modules\LiveTV\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;

class LiveTvChannelResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $user        = auth()->user();
        $userPlanLevel = (int) ($user?->subscriptionPackage?->level ?? 0);

        $isPaid          = $this->access == 'paid';
        $channelPlanLevel = (int) ($this->plan?->level ?? 0);
        $showPremiumBadge = $isPaid && $channelPlanLevel > $userPlanLevel;
        
        return [
            'id' => $this->id,
            'poster_image' => setBaseUrlWithFileName($this->poster_url, 'image', 'livetv'),
            'slug' => $this->slug,
            'category' => optional($this->TvCategory)->name ?? null,
            'category_id' => optional($this->TvCategory)->id ?? null,
            'name' => $this->name,
            'access' => $this->access,
            'plan_id' => $this->plan_id,
            'plan_level' => $channelPlanLevel,
            'show_premium_badge' => $showPremiumBadge,
            'type' => 'livetv',
        ];
    }
}
