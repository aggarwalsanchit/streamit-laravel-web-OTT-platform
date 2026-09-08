<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Entertainment\Models\Entertainment;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Entertainment\Models\EntertainmentDownload;

class EpisodeResource extends JsonResource
{
    protected $userId;
    public function __construct($resource, $userId = null)
    {
        parent::__construct($resource);
        $this->userId = $userId;
    }

    public function toArray($request)
    {
        if($this->userId){
            $is_download = EntertainmentDownload::where('entertainment_id', $this->id)->where('user_id', $this->userId)->where('entertainment_type', 'episode')->where('is_download', 1)->exists();
        }
        
        // Premium badge logic
        $currentUser = auth()->user();
        $currentPlanLevel = optional(optional($currentUser)->subscriptionPackage)->level ?? 0;
        $PlanLevel = optional($this->plan)->level ?? 0;
        $isPayPerView = $this->access === 'pay-per-view';
        $isPaid = $this->access === 'paid';
        $showPremiumBadge = !$isPayPerView && $isPaid && $PlanLevel > $currentPlanLevel;
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'entertainment_id' => $this->entertainment_id,
            'season_id' => $this->season_id,
            'episode_number' => $this->episode_number,
            'trailer_url_type' => $this->trailer_url_type,
            'type'=>'episode',
            'trailer_url' => $this->trailer_url_type=='Local' ? setBaseUrlWithFileName($this->trailer_url,'video','episode') : $this->trailer_url,
            'access' => $this->access,
            'plan_id' => $this->plan_id,
            'plan_level' => $this->plan ? $this->plan->level : null,
            'imdb_rating' => $this->IMDb_rating,
            'content_rating' => $this->content_rating,
            'duration' => $this->duration,
            'release_date' => $this->release_date,
            'is_restricted' => $this->is_restricted,
            'short_desc' => $this->short_desc,
            'description' => strip_tags($this->description),
            'video_upload_type' => $this->video_upload_type,
            'video_url_input' => $this->video_upload_type=='Local' ? setBaseUrlWithFileName($this->video_url_input,'video','episode') : $this->video_url_input,
            'download_status' => $is_download ?? false,
            'enable_quality' => $this->enable_quality,
            'download_url' => $this->download_url,
            'poster_image' =>  setBaseUrlWithFileName($this->poster_url,'image','episode'),
            'video_links' => $this->EpisodeStreamContentMapping ?? null,
            'plan' => new PlanResource($this->plan),
            'thumbnail_image' => setBaseUrlWithFileName($this->thumbnail_url,'image','episode'),
            'poster_tv_image' =>  setBaseUrlWithFileName($this->poster_tv_url,'image','episode'),
            'price' => (float)$this->price,
            'discounted_price' => round((float)$this->price - ($this->price * ($this->discount / 100)), 2),
            'purchase_type' => $this->purchase_type,
            'access_duration' => $this->access_duration,
            'discount'=> (float)$this->discount,
            'available_for' => $this->available_for,
            'is_purchased' => Entertainment::isPurchased($this->id,'episode', $this->user_id ?? $this->userId ),
            'subtitle_info' => $this->enable_subtitle == 1 ? SubtitleResource::collection($this->subtitles) : null,
            'intro_starts_at' => $this->start_time ?? null,
            'intro_ends_at' => $this->end_time ?? null,
            'bunny_video_url' => $this->bunny_video_url ?? null,
            'bunny_trailer_url' => $this->bunny_trailer_url ?? null,
            'show_premium_badge' => $showPremiumBadge,

        ];
    }
}
