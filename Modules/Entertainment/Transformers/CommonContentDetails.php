<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class CommonContentDetails  extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'details'=>[
                'name' => $this->name,
                'type' => $this->type,
                'is_device_supported' => $this->isDeviceSupported,
                'genres' => $this->genre_data ?? null,
                'language' => $this?->language ?? null,
                'duration'=> $this->duration,
                'watched_duration'=>$this->watched_time ?? null,
                'intro_starts_at' => $this->start_time ?? null,
                'intro_ends_at' => $this->end_time ?? null,
                'content_rating'=>$this->content_rating,
                'is_restricted' => $this->is_restricted,
                'is_like' => $this->is_likes,
                'release_date'=> $this->release_date ? formatDate($this->release_date):null,
                'imdb_rating'=> $this->IMDb_rating ?? $this->imdb_rating,
                'access'=> $this->access,
                'description'=> strip_tags($this->description),
                'is_in_watchlist'=>$this->is_watch_list ,
                'thumbnail_image' => $this->thumbnail_url ? setBaseUrlWithFileName($this->thumbnail_url,'image',$this->type) : null ,
                'poster_image' => $this->poster_url ? setBaseUrlWithFileName($this->poster_url,'image',$this->type) : null ,
                'has_content_access'=> $this->has_content_access, // $this->access == 'free'|| $this->access == 'pay-per-view' ? 1 : $this->plan_id,
                'required_plan_level'=> $this->required_plan_level, // $this->userPlanId  >= $this->plan_id ? 1 : 0  ,
                'tv_show_data' => $this->tv_show_data,
                'season_data' => $this->season_data,
            ],
                'trailer_data'=> $this->trailer_data ?? [
                    'id'=> $this->id,
                    'url_type' => $this->trailer_url_type,
                    'url' => $this->trailer_url,
                    'poster_image' => $this->posterImage
                ],
                'video_qualities' => $this->video_qualities,
                'download_data' => $this->download_data,
                'rental_data'=> $this->rental,
                'subtitle_info' => collect($this->subtitles)->map(function ($subtitle) {
                    return [
                        'id' => $subtitle->id,
                        'language' => $subtitle->language,
                        'is_default' => $subtitle->is_default,
                        'subtitle_file' => setBaseUrlSubtitleFile($subtitle->subtitle_file,'subtitle', $this->type),
                    ];
                })->values(),

                'actors' => collect($this->actors)->take(5)->map(function ($cast) {
                    return [
                        'id' => $cast->id,
                        'type' => 'actor',
                        'name' => $cast->name,
                        'profile_image' => setBaseUrlWithFileName($cast->file_url,'image','castcrew'),
                    ];
                })->values(),

                'directors' => collect($this->directors)->take(5)->map(function ($director) {
                    return [
                        'id' => $director->id,
                        'type' => 'director',
                        'name' => $director->name,
                        'profile_image' => setBaseUrlWithFileName($director->file_url,'image','castcrew'),
                    ];
                })->values(),

                'ads_data' => [
                    'custom_ads' => $this->customAds,
                    'vast_ads'=> $this->vast_ads
                ],

                'suggested_content' => CommanResourceV3::collection($this->more_items ?? []),
                'review' => $this->review ?? null,
                'poster_image' => $this->poster_tv_url ? setBaseUrlWithFileName($this->poster_tv_url,'image',$this->type) : null ,
        ];

    }


}
