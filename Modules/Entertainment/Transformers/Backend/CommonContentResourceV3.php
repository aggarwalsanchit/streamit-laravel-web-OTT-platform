<?php

namespace Modules\Entertainment\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\Entertainment;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\Plan;

class CommonContentResourceV3 extends JsonResource
{
    public function toArray($request)
    {

        $genre_data = [];
        if (!empty($this->entertainmentGenerMappings)) {
            foreach ($this->entertainmentGenerMappings as $genre) {
                $genre_data[] = [
                    'id' => $genre->id,
                    'name' => $genre->genre->name ?? null,
                ];
            }
        }

        $userId = $request->input('user_id') ?? auth()->id();
        $user = $this->preloaded_user ?? auth()->user();
        $isInWatchList = false;

        // OPTIMIZATION: Use preloaded is_watch_list if available (prevents N+1 query)
        if (isset($this->is_watch_list)) {
            $isInWatchList = (bool) $this->is_watch_list;
        } elseif ($userId) {
            // Fallback: Only query if not preloaded (shouldn't happen with optimized query)
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $contentType = $this->type ?? 'movie';
            $isInWatchList = Watchlist::where('entertainment_id', $this->id)
                ->where('user_id', $userId)
                ->where('type', $contentType)
                ->where('profile_id', $profile_id)
                ->exists();
        }

        // Premium badge logic
        static $userMaxLevelCache = [];
        
        $userPlanLevel = 0;
        if ($userId) {
            if (isset($userMaxLevelCache[$userId])) {
                $userPlanLevel = $userMaxLevelCache[$userId];
            } else {
                $subscriptions = Subscription::where('user_id', $userId)
                    ->where(function ($query) {
                        $query->where('status', 'active')
                              ->orWhere('end_date', '>', now());
                    })
                    ->get();
                
                foreach ($subscriptions as $sub) {
                    $lvl = $sub->level;
                    if (is_null($lvl)) {
                        $p = Plan::find($sub->plan_id);
                        if ($p) $lvl = $p->level;
                    }
                    if ($lvl > $userPlanLevel) {
                        $userPlanLevel = $lvl;
                    }
                }
                $userMaxLevelCache[$userId] = $userPlanLevel;
            }
        }
        $movieAccess   = (string) ($this->movie_access ?? '');
        $videoPlanLevel = (int) ($this->plan_level ?? $this->plan?->level ?? 0);

        $isPayPerView = $movieAccess === 'pay-per-view';
        $isPaid       = $movieAccess === 'paid';
        $showPremiumBadge = !$isPayPerView && $isPaid && $videoPlanLevel > $userPlanLevel;

        $isPurchased = false;
        if ($isPayPerView) {
            $purchasedIds = $this->preloaded_purchased_ids ?? [];
            $contentType = $this->type ?? 'movie';
            if (!empty($purchasedIds) && isset($purchasedIds[$contentType])) {
                $isPurchased = in_array($this->id, $purchasedIds[$contentType]);
            } else {
                $isPurchased = Entertainment::isPurchased($this->id, $this->type);
            }
        }

        if ($this->trailer_url_type == 'Local' && !empty($this->bunny_video_url && config('filesystems.active') == 'bunny')) {
            $this->trailer_url_type = 'HLS';
            $this->trailer_url = $this->bunny_video_url;
        } else {
            $this->trailer_url = $this->trailer_url_type == 'Local'
                ? setBaseUrlWithFileName($this->trailer_url,'video',$this->type)
                : $this->trailer_url;
        }

        // Determine which poster to use based on device type or preloaded poster_image
        $deviceType = $request->input('device_type') ?? getDeviceType($request);
        $posterUrl = $this->poster_url;
        
        // Use poster_tv_url for TV devices if available
        if ($deviceType == 'tv' && !empty($this->poster_tv_url)) {
            $posterUrl = $this->poster_tv_url;
        }
        
        // If poster_image was already set by controller, use that URL directly
        if (isset($this->poster_image) && !empty($this->poster_image)) {
            $posterImageUrl = $this->poster_image;
        } else {
            $posterImageUrl = setBaseUrlWithFileName($posterUrl ?? null, 'image', $this->type);
        }

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'type'            => $this->type,
            'trailer_url_type'=> $this->trailer_url_type,
            'trailer_url'     => $this->trailer_url,
            'movie_access'    => $this->movie_access,
            'imdb_rating'     => $this->IMDb_rating ?? $this->imdb_rating,
            'plan_id'         => $this->plan_id,
            'plan_level'      => $this->plan_level ?? optional($this->plan)->level,
            'language'        => $this->language,
            'duration'        => $this->duration,
            'release_date'    => $this->release_date,
            'poster_image'    => $posterImageUrl,
            'is_watch_list'   => $isInWatchList,
            'genres'          => $genre_data,
            'show_premium_badge' => $showPremiumBadge,
            'is_purchased'    => $isPurchased,
            'is_pay_per_view' => $isPayPerView,
            'is_paid'         => $isPaid,
        ];
    }
}
