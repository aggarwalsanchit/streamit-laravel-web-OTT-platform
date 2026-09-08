<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Genres\Transformers\GenresResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Subscriptions\Models\Plan;
use Modules\Entertainment\Models\Entertainment;

class WatchlistResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        // Determine which relationship to use based on entertainment_type
        $isVideo = ($this->type ?? '') == 'video';
        $contentItem = $isVideo ? $this->video : $this->entertainment;

        $genre_data = [];
        $plans = [];

        if (!$isVideo && $contentItem) {
            // For entertainment items, get genres and plans
            $genres = optional($contentItem)->entertainmentGenerMappings;
            foreach($genres as $genre){
                $genre_data[] = $genre->genre;
            }

            $plan = optional($contentItem)->plan;
            if(!empty($plan)){
                $plans = Plan::where('level', '<=', $plan->level)->get();
            }
        }

        $access = $isVideo ? optional($contentItem)->access : optional($contentItem)->movie_access;
        return [
            'id' => $this->id,
            'poster_image' => $this->posterImage, // setBaseUrlWithFileName(optional($contentItem)->poster_url ?? null),
            'poster_tv_image' => $this->poster_tv_image, // setBaseUrlWithFileName(optional($contentItem)->poster_url ?? null),
            'details'=>[
                'id' => $this->entertainment_id,
                'type' => $this->type ?? '',
                'name' => optional($contentItem)->name,
                'description' => strip_tags(optional($contentItem)->description),
                'imdb_rating'=> $this->IMDb_rating ?? $this->imdb_rating ?? null,
                'access' => $access,
                'season_data' => $this->season_data,
                'required_plan_level'=>$this->required_plan_level,
                'is_device_supported'=> $this->isDeviceSupported,
                'has_content_access' => $this->has_content_access, // $access == 'free'|| $access == 'pay-per-view' ? 1 : optional($contentItem)->plan_id,
            ],
        ];
    }
}
