<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Genres\Transformers\GenresResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Subscriptions\Models\Plan;

class ContinueWatchResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $entertainment = null;
        $plans = [];
        $thumbnail_url = null;
        $content_type = $this->entertainment_type;

        // Use thumbnail_url set in controller if available
        if (isset($this->thumbnail_url) && $this->thumbnail_url !== null) {
            $thumbnail_url = $this->thumbnail_url;

            // TV show with specific episode → use episode data
            if ($this->entertainment_type === 'tvshow' && $this->episode_id && isset($this->episode)) {
                $content_type = 'episode';
                $entertainment = $this->episode;
            }
            // Standalone video → always use Video model + its own poster
            elseif ($this->entertainment_type === 'video') {
                $content_type = 'video';
                $entertainment = $this->videoNew ?? $this->video;
                $thumbnail_url = $entertainment->poster_url ?? $thumbnail_url;
            }
            // Movie / tvshow without episode context
            else {
                $entertainment = $this->entertainmentNew ?? $this->entertainment;
            }
        }
        // If it's a TV show with an episode, use episode's poster image
        elseif ($this->entertainment_type === 'tvshow' && $this->episode_id && isset($this->episode)) {
            $entertainment = $this->episode;
            $thumbnail_url = $entertainment->poster_url ?? null;
            $content_type = 'episode'; // Use episode type for image path
        }
        elseif ($this->entertainment_type === 'movie' || $this->entertainment_type === 'tvshow') {
            $entertainment = $this->entertainmentNew;
            $thumbnail_url = $entertainment->poster_url ?? null;
        }
        elseif ($this->entertainment_type === 'episode') {
            $entertainment = $this->episodeNew;
            $thumbnail_url = $entertainment->poster_url ?? null;
        }
        elseif ($this->entertainment_type === 'video') {
            $entertainment = $this->videoNew ?? $this->video;
            $thumbnail_url = $entertainment->poster_url ?? null;
        }
        return [
            'id' => $this->id,
            'thumbnail_image' => setBaseUrlWithFileName( $thumbnail_url,'image', $content_type),
            'details'=>[
                'id' => $this->entertainment_id,
                'episode_id' => $this->episode_id ?? null,
                'name' => $entertainment->name ?? null,
                'type'=> $this->entertainment_type ,
                'watched_duration' => $this->watched_time ?? '00:00:01',
                'total_duration' => $this->total_watched_time ?? '00:00:01',
                'tv_show_data' => $this->tv_show_data ?? null
            ],
            'trailer_data'=>[
                'trailer_url_type' => $this->trailer_url_type,
                'trailer_url' => $this->trailer_url,
            ],
        ];
    }
}
