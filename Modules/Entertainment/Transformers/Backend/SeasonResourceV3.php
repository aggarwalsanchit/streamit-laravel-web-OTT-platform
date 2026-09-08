<?php

namespace Modules\Entertainment\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Subscriptions\Transformers\PlanResource;

class SeasonResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $currentUser = auth()->user();
        $currentPlanLevel = optional(optional($currentUser)->subscriptionPackage)->level ?? 0;
        $seasonPlanLevel = optional($this->plan)->level ?? 0;
        $showPremiumBadge = ($this->access === 'paid') && (($currentUser === null) || ($seasonPlanLevel > $currentPlanLevel));
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'entertainment_id' => $this->entertainment_id,
            'trailer_url_type' => $this->trailer_url_type,
            'type'=>'season',
            'trailer_url' => $this->trailer_url_type=='Local' ? setBaseUrlWithFileName($this->trailer_url,'video','season') : $this->trailer_url,
            'access' => $this->access,
            'plan_id' => $this->plan_id,
            'plan_level' => $seasonPlanLevel,
            'short_desc' => $this->short_desc,
            'description' => strip_tags($this->description),
            'poster_image' =>setBaseUrlWithFileName($this->poster_url,'image','season'),
            'episode_slug' => $this->episodes->first() ? $this->episodes->first()->slug : null,
            'show_premium_badge' => $showPremiumBadge,
            'tv_show_data' => $this->entertainmentdata->name ?? null,
        ];
    }
}
