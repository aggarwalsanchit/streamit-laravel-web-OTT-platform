<?php

namespace Modules\Video\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Entertainment\Models\Watchlist;
use Modules\Subscriptions\Models\Plan;
use Modules\Entertainment\Models\Entertainment;

class VideoResourceBannerV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $isInWatchList = false;

        $userId = $request->input('user_id') ?? auth()->id();
        if (isset($this->is_watch_list)) {
            $isInWatchList = (bool) $this->is_watch_list;
        } elseif ($userId) {
            // Fallback: Only query if not preloaded (shouldn't happen with optimized query)
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $isInWatchList = WatchList::where('entertainment_id', $this->id)
                ->where('user_id', $userId)
                ->where('type', 'video')
                ->where('profile_id', $profile_id)
                ->exists();                
        }
         return [
             'poster_image' => setBaseUrlWithFileName($this->poster_image,'image','video'),
             'poster_tv_image' => setBaseUrlWithFileName($this->poster_tv_url,'image','video'),
             'details'=>[
                'id' => $this->id,
                'name' => $this->name,
                'slug' => $this->slug,
                'type' => $this->type ?? 'video',
                'release_date' => $this->release_date ? formatDate($this->release_date):null,
                'access' => $this->access,
                'is_restricted' => $this->is_restricted,
                'is_device_supported'=> $this->isDeviceSupported,
                "has_content_access"=> $this->has_content_access ?? 0,
                "required_plan_level"=>$this->required_plan_level,
                'is_in_watchlist' => $isInWatchList ?? 0,
                'language' => $this->language ?? 'english',
                'duration' => $this->duration ?? null,
                'is_restricted' => $this->is_restricted ?? 0,
                'imdb_rating' => $this->IMDb_rating ?? $this->imdb_rating,
                'description' => $this->description ?? '',
             ],
             'trailer_data'=>[
                'trailer_url_type' => $this->trailer_url_type,
                'trailer_url' => $this->trailer_url_type == 'Local' ? setBaseUrlWithFileName($this->trailer_url) : $this->trailer_url,
             ]
        ];
    }
}
