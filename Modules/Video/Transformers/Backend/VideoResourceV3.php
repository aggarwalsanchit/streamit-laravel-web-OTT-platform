<?php

namespace Modules\Video\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Entertainment\Models\Watchlist;
use Modules\Subscriptions\Models\Plan;
use Modules\Entertainment\Models\Entertainment;

class VideoResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        // OPTIMIZATION: Skip plans query if not needed (it's not used in the response)
        // $plans = [];
        // $plan = $this->plan;
        // if($plan){
        //     $plans = Plan::where('level', '<=', $plan->level)->get();
        // }
        
        $userId = $request->input('user_id') ?? auth()->id();
        
        if (isset($this->is_watch_list)) {
            $isInWatchList = (bool) $this->is_watch_list;
        } elseif ($userId) {
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $isInWatchList = WatchList::where('entertainment_id', $this->id)
                ->where('user_id', $userId)
                ->where('type', 'video')
                ->where('profile_id', $profile_id)
                ->exists();
        } else {
            $isInWatchList = false;
        }

        // OPTIMIZATION: Use preloaded user if available, otherwise fallback to auth()->user()
        $currentUser = $this->preloaded_user ?? auth()->user();
        // OPTIMIZATION: Use preloaded plan level if available
        $videoPlanLevel = $this->preloaded_plan_level ?? optional($this->plan)->level ?? 0;
        $currentPlanLevel = $this->preloaded_user_plan_level ?? optional(optional($currentUser)->subscriptionPackage)->level ?? 0;
        
        $isPremium = ($this->access === 'paid') && (($currentUser === null) || ($videoPlanLevel > $currentPlanLevel));
        $showPremiumBadge = ($this->access === 'paid') && (($currentUser === null) || ($videoPlanLevel > $currentPlanLevel));

        $isPurchased = false;
        if ($this->access === 'pay-per-view') {
            $purchasedIds = $this->preloaded_purchased_ids ?? [];
            if (!empty($purchasedIds) && isset($purchasedIds['video'])) {
                $isPurchased = in_array($this->id, $purchasedIds['video']);
            } else {
                $isPurchased = Entertainment::isPurchased($this->id, 'video', $userId);
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'trailer_url_type' => $this->trailer_url_type,
            'short_desc'=>$this->short_desc,
            'trailer_url' => $this->trailer_url_type=='Local' ? setBaseUrlWithFileName($this->trailer_url,'video','video') : $this->trailer_url,
            'access' => $this->access,
            'plan_id' => $this->plan_id,
            'description' => $this->description,
            'type'=>'video',
            'is_watch_list' => $isInWatchList,
            'plan_level' => $videoPlanLevel,
            'is_premium' => $isPremium,
            'show_premium_badge' => $showPremiumBadge,
            'is_purchased' => $isPurchased,
            'is_pay_per_view' => $this->access === 'pay-per-view',
            'imdb_rating' => $this->IMDb_rating,
            'duration' => $this->duration,
            'poster_image' => setBaseUrlWithFileName($this->poster_url,'image','video'),
        ];
    }
}
