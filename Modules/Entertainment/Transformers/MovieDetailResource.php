<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Genres\Transformers\GenresResource;

use Modules\Entertainment\Transformers\ReviewResource;
use Modules\CastCrew\Transformers\CastCrewListResource;
use Modules\Entertainment\Transformers\ClipResource;
use Modules\Entertainment\Transformers\SubtitleResource;
use Modules\Subscriptions\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

class MovieDetailResource extends JsonResource
{
    public function toArray($request)
    {
        // --- Genres ---
        $genres = $this->entertainmentGenerMappings->pluck('genre')->where('status', 1)->where('deleted_at', null);
        // --- Casts & Directors (limit 11 each) ---
        $casts = $this->entertainmentTalentMappings
            ->filter(fn($mapping) => $mapping->talentprofile && $mapping->talentprofile->type === 'actor')
            ->take(11)
            ->pluck('talentprofile');

        $directors = $this->entertainmentTalentMappings
            ->filter(fn($mapping) => $mapping->talentprofile && $mapping->talentprofile->type === 'director')
            ->take(11)
            ->pluck('talentprofile');

        // --- Download mappings ---
        $downloadMappings = $this->entertainmentDownloadMappings ? $this->entertainmentDownloadMappings->toArray() : [];

        if ($this->download_status && $this->download_type && $this->download_url) {
            $downloadMappings[] = [
                'type' => $this->download_type,
                'url' => $this->download_url,
                'quality' => 'default',
            ];
        }

        // --- User download info (move this out of Resource ideally) ---
        $download = $this->whenLoaded('userDownload', function() use ($request) {
            return $this->userDownload;
        });

        // --- Device type support ---
        $deviceTypeResponse = json_decode(
            Subscription::checkPlanSupportDevice($request->user_id)->getContent(),
            true
        );

        if ($this->trailer_url_type == 'Local' && !empty($this->bunny_trailer_url && config('filesystems.active') == 'bunny')) {
            $this->trailer_url_type = 'HLS';
            $this->trailer_url = Crypt::encryptString($this->bunny_trailer_url);
        } else {
            $this->trailer_url = $this->trailer_url_type == 'Local'
                ? setBaseUrlWithFileName($this->trailer_url,'video',$this->type)
                : $this->trailer_url;
        }

        $reviews = collect($this->reviews ?? []);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => strip_tags($this->description),
            'trailer_url_type' => $this->trailer_url_type,
            'type' => $this->type,
            'trailer_url' => $this->trailer_url,
            'movie_access' => $this->movie_access,
            'plan_id' => $this->plan_id,
            'plan_level' => $this->plan->level ?? 0,
            'release_date' => $this->release_date,
            'language' => $this->language,
            'imdb_rating' => $this->IMDb_rating,
            'content_rating' => $this->content_rating,
            'watched_time' => optional($this->continue_watch)->watched_time,
            'duration' => $this->duration,
            'release_year' => Carbon::parse($this->release_date)->year,
            'is_restricted' => $this->is_restricted,
            'video_upload_type' => $this->video_upload_type,
            'video_url_input' => $this->video_upload_type === 'Local'
                ? setBaseUrlWithFileName($this->video_url_input, 'video', $this->type)
                : $this->video_url_input,
            'enable_quality' => $this->enable_quality,
            'is_download' => $this->is_download ?? false,
            'download_status' => $this->download_status,
            'download_type' => $this->download_type,
            'download_url' => $this->download_url,
            'enable_download_quality' => $this->enable_download_quality,
            'download_quality' => $downloadMappings,
            'poster_image' => setBaseUrlWithFileName($this->poster_url, 'image', $this->type),
            'thumbnail_image' => setBaseUrlWithFileName($this->thumbnail_url, 'image', $this->type),
            'is_watch_list' => $this->is_watch_list ?? false,
            'subtitle_info' => $this->enable_subtitle ? SubtitleResource::collection($this->subtitles) : null,
            'is_likes' => $this->is_likes ?? false,
            'your_review' => $this->your_review ?? null,
            'total_review' => $this->total_review ?? 0,
            'genres' => GenresResource::collection($genres),
            'reviews' => ReviewResource::collection($reviews),
            'three_reviews' => ReviewResource::collection($reviews->take(3)),
            'video_links' => $this->entertainmentStreamContentMappings,
            'casts' => CastCrewListResource::collection($casts),
            'directors' => CastCrewListResource::collection($directors),
            'status' => $this->status,
            'download_id' => $download->id ?? null,
            'is_device_supported' => $deviceTypeResponse['isDeviceSupported'] ?? false,
            'poster_tv_image' => setBaseUrlWithFileName($this->poster_tv_url, 'image', $this->type),
            'price' => (float)$this->price,
            'discounted_price' => round($this->price - ($this->price * ($this->discount / 100)), 2),
            'purchase_type' => $this->purchase_type,
            'access_duration' => $this->access_duration,
            'discount' => (float)$this->discount,
            'available_for' => $this->available_for,
            'intro_starts_at' => $this->start_time ?? null,
            'intro_ends_at' => $this->end_time ?? null,
            'is_clips_enabled' => $this->enable_clips,
            'clips' => ClipResource::collection(($this->clips ?? collect())->where('content_type', 'movie')->values()),
            'bunny_video_url' => Crypt::encryptString($this->bunny_video_url),
            'bunny_trailer_url' => Crypt::encryptString($this->bunny_trailer_url),
        ];
    }
}
