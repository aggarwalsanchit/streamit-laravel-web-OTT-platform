<?php

namespace Modules\Entertainment\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Entertainment\Models\Entertainment;

class EpisodeResourceV3 extends JsonResource
{
    protected $userId;

    public function __construct($resource, $userId = null)
    {
        parent::__construct($resource);
        $this->userId = $userId;
    }

    public function toArray($request)
    {
        $currentUser = auth()->user();
        $currentPlanLevel = optional(optional($currentUser)->subscriptionPackage)->level ?? 0;
        $PlanLevel = optional($this->plan)->level ?? 0;
        $isPremium = ($this->access === 'paid') && ($currentUser === null || $PlanLevel > $currentPlanLevel);
        $showPremiumBadge = ($this->access === 'paid') && ($currentUser === null || $PlanLevel > $currentPlanLevel);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'entertainment_id' => $this->entertainment_id,
            'slug' => $this->slug,
            'season_id' => $this->season_id,
            'type' => 'episode',
            'trailer_url_type' => $this->trailer_url_type,
            'trailer_url' => $this->trailer_url_type === 'Local'
                ? setBaseUrlWithFileName($this->trailer_url, 'video', 'episode')
                : $this->trailer_url,
            'access' => $this->access,
            'plan_id' => $this->plan_id,
            'plan_level' => $PlanLevel,
            'current_user_plan_level' => $currentPlanLevel,
            'is_premium' => $isPremium,
            'show_premium_badge' => $showPremiumBadge,
            'is_purchased' => Entertainment::isPurchased($this->id, 'episode', $this->user_id ?? $currentUser?->id),
            'is_pay_per_view' => $this->access === 'pay-per-view',
            'imdb_rating' => $this->IMDb_rating,
            'duration' => $this->duration,
            'description' => strip_tags($this->description),
            'poster_image' => setBaseUrlWithFileName($this->poster_url, 'image', 'episode'),

        ];
    }

}
