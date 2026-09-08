<?php

namespace Modules\Entertainment\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\Entertainment;
use Modules\Entertainment\Transformers\ClipResource;
use Carbon\Carbon;
use \Modules\Entertainment\Models\Like;
use Illuminate\Http\Request;

    class ComingSoonResourceV3 extends JsonResource
    {
    public function toArray($request)
    {
        $genre_data = [];
        $is_remind = 0;
        if(!empty($this->UserReminder)){
            $is_remind = optional($this->userReminder->first())->is_remind ?? 0;
        }
        if (!empty($this->entertainmentGenerMappings)) {
            foreach ($this->entertainmentGenerMappings as $genre) {
                $genre_data[] = [
                    'id' => $genre->id,
                    'name' => $genre->genre->name ?? null,
                ];
            }
        }

        $userId = $request->input('user_id') ?? auth()->id();
        $user = auth()->user();
        $isInWatchList = 0;

        $isVideo = ($this->type ?? null) === 'video' || get_class($this->resource) === 'Modules\Video\Models\Video';
        $contentType = $isVideo ? 'video' : ($this->type ?? 'movie');

        if ($userId) {
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $isInWatchList = Watchlist::where('entertainment_id', $this->id)
                ->where('user_id', $userId)
                ->where('type', $contentType)
                ->where('profile_id', $profile_id)
                ->exists() ? 1 : 0;
        } elseif (isset($this->is_watch_list)) {
            $isInWatchList = $this->is_watch_list ? 1 : 0;
        }

        $movieAccess = $isVideo ? ($this->access ?? '') : ($this->movie_access ?? '');

        // Premium badge logic
        $userPlanLevel = (int) ($user?->subscriptionPackage?->level ?? 0);
        $videoPlanLevel = (int) ($this->plan_level ?? $this->plan?->level ?? 0);

        $isPayPerView = $movieAccess === 'pay-per-view';
        $isPaid       = $movieAccess === 'paid';
        $showPremiumBadge = !$isPayPerView && $isPaid && $videoPlanLevel > $userPlanLevel;

        $isPurchased = $isPayPerView
            ? Entertainment::isPurchased($this->id, $this->type ?? 'video')
            : false;

        // Handle type - ensure it's set to 'video' for Video models
        $contentType = $isVideo ? 'video' : ($this->type ?? 'movie');

        // Handle thumbnail - prefer thumbnail, then poster, then TV poster
        $thumbnailUrl = $isVideo
            ? ($this->thumbnail_url ?? $this->poster_url ?? $this->poster_tv_url ?? null)
            : ($this->thumbnail_url ?? $this->poster_url ?? $this->poster_tv_url ?? null);

        $hasLiked = 0;
        if ($userId) {
            $hasLiked = Like::query()
                ->where('entertainment_id', $this->id)
                ->where('user_id', $userId)
                ->when($request->filled('profile_id'), function ($q) use ($request) {
                    $q->where('profile_id', $request->profile_id);
                })
                ->where('is_like', 1)
                ->exists() ? 1 : 0;
        } elseif (isset($this->is_likes)) {
            $hasLiked = $this->is_likes ? 1 : 0;
        }

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug ?? null,
            'description'     => $this->description ?? $this->short_desc ?? null,
            'type'            => $contentType,
            'trailer_url_type'=> $this->trailer_url_type ?? null,
            'trailer_url'     => ($this->trailer_url_type ?? null) == 'Local'
                                    ? setBaseUrlWithFileName($this->trailer_url ?? null,'video',$contentType)
                                    : ($this->trailer_url ?? null),
            'movie_access'    => $movieAccess,
            'imdb_rating'     => $this->IMDb_rating ?? $this->imdb_rating ?? null,
            'plan_id'         => $this->plan_id ?? null,
            'plan_level'      => $this->plan_level ?? optional($this->plan)->level ?? 0,
            'language'        => $this->language ?? null,
            'duration'        => $this->duration ?? null,
            'thumbnail_image'    => setBaseUrlWithFileName($thumbnailUrl, 'image', $contentType),
            'is_in_watchlist'   => $isInWatchList,
            'is_likes'        => $hasLiked,
            'genres'          => $genre_data,
            'show_premium_badge' => $showPremiumBadge,
            'release_date' => $this->release_date ? formatDateTimeWithTimezone($this->release_date,'date') : null,
            'is_restricted' => $this->is_restricted ?? 0,
            'is_remind'=> $is_remind,
            'is_clips_enabled' => $this->enable_clips ?? 0,
            'clips' => ClipResource::collection(
                ($this->clips ?? collect())
                    ->when($isVideo, function($q){
                        return $q->where('content_type', 'video');
                    })
                    ->when(!$isVideo && in_array($this->type ?? null, ['movie','tvshow']), function($q){
                        $mapType = ($this->type ?? 'movie') === 'movie' ? 'movie' : 'tv_show';
                        return $q->where('content_type', $mapType);
                    })
                    ->values()
            ),
            'remaining_release_days' => $this->release_date
                ? abs(Carbon::parse($this->release_date)->diffInDays(Carbon::today()))
                : null,
            'poster_image' => setBaseUrlWithFileName(
                $this->poster_url ?? $this->poster_tv_url ?? $this->posterImage ?? null,
                'image',
                $contentType
            ),
            'content_rating' => $this->content_rating ?? null,
            'poster_tv_url' => setBaseUrlWithFileName($this->poster_tv_url ?? null, 'image', $contentType),
        ];
    }
}
