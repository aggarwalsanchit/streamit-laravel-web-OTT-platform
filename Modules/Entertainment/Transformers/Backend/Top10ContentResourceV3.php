<?php

namespace Modules\Entertainment\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Entertainment\Models\Entertainment;

class Top10ContentResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $user = $request->user();

        $userPlanLevel = (int) ($user?->subscriptionPackage?->level ?? 0);
        $movieAccess   = (string) ($this->movie_access ?? '');
        $videoPlanLevel = (int) ($this->plan_level ?? $this->plan?->level ?? 0);

        $isPayPerView = $movieAccess === 'pay-per-view';
        $isPaid       = $movieAccess === 'paid';
        $showPremiumBadge = $isPaid && $videoPlanLevel > $userPlanLevel;

        $isPurchased = $isPayPerView
            ? Entertainment::isPurchased($this->id, $this->type)
            : false;

        return [
            'id'                => $this->id,
            'type'              => $this->type,
            'slug'              => $this->slug,
            'movie_access'      => $movieAccess,
            'plan_id'           => (int) ($this->plan_id ?? 0),
            'plan_level'        => $videoPlanLevel,
            'is_pay_per_view'   => $isPayPerView,
            'is_paid'           => $isPaid,
            'user_plan_level'   => $userPlanLevel,
            'show_premium_badge'=> $showPremiumBadge,
            'is_purchased'      => $isPurchased,
            'imdb_rating'       => $this->IMDb_rating ?? $this->imdb_rating ?? null,
            'poster_image'      => setBaseUrlWithFileName($this->poster_url ?? null, 'image', $this->type),
        ];
    }
}
