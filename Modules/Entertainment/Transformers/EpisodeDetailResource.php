<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Genres\Transformers\GenresResource;
use Modules\Episode\Models\Episode;
use Modules\Entertainment\Transformers\EpisodeResource;
use Modules\Season\Models\Season;
use Modules\Entertainment\Transformers\Backend\CommonContentResourceV3;
use Illuminate\Support\Facades\Crypt;


class EpisodeDetailResource extends JsonResource
{
    protected $userId;
    public function __construct($resource, $userId = null)
    {
        parent::__construct($resource);
        $this->userId = $userId;
    }

    public function toArray($request): array
    {


        $seasons = Season::where('entertainment_id', $this->entertainment_id)->where('status', 1)->where('deleted_at', null)->orderBy('season_index', 'asc')->orderBy('id', 'asc')->get();
        $tvShowLinks = [];
        foreach($seasons as $season){
            $episodes = Episode::where('season_id', $season->id)
                    ->where('status', 1)
                    ->where('deleted_at', null)
                    ->when(request()->has('is_restricted'), function ($query) {
                        $query->where('is_restricted', request()->is_restricted);
                    })
                    ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                        $query->where('is_restricted', 0);
                    })
                    ->get();

                    $allHaveEpisodeNumber = $episodes->every(function ($episode) {
                        return !is_null($episode->episode_number);
                    });

                    if ($allHaveEpisodeNumber && $episodes->count() > 0) {
                        $episodes = $episodes
                            ->sortBy(fn ($episode) => (int) $episode->episode_number)
                            ->values();
                    }

            $totalEpisodes = $episodes->count();


            $tvShowLinks[] = [
                'season_id' => $season->id,
                'name' => $season->name,
                'short_desc' => $season->short_desc,
                'description' => strip_tags($season->description),
                'poster_image' => setBaseUrlWithFileName($season->poster_url,'image','season'),
                'poster_tv_image' =>  setBaseUrlWithFileName($season->poster_tv_url,'image','season'),
                'trailer_url_type' => $season->trailer_url_type,
                'trailer_url ' => $season->trailer_url_type=='Local' ? setBaseUrlWithFileName($season->trailer_url,'video','season') : $season->trailer_url,
                'total_episodes' => $totalEpisodes,
                'episodes' => EpisodeResource::collection(
                                    $episodes->take(8)->map(function ($episode) {
                                        return new EpisodeResource($episode, $this->user_id);
                                    })
                                ),
            ];

        }

        $downloadMappings = $this->episodeDownloadMappings ? $this->episodeDownloadMappings->toArray() : [];

        if ($this->download_status == 1) {

           if($this->download_type != null &&  $this->download_url !=null){

            $downloadData = [
                'type' => $this->download_type,
                'url' => $this->download_url,
                'quality' => 'default',
            ];
            $downloadMappings[] = $downloadData;

          }
        }
        $download = EntertainmentDownload::where('entertainment_id', $this->entertainment_id)->where('user_id',  $this->user_id)->where('entertainment_type', 'episode')->where('is_download', 1)->first();

         if ($this->trailer_url_type == 'Local' && !empty($this->bunny_trailer_url && config('filesystems.active') == 'bunny')) {
            $this->trailer_url_type = 'HLS';
            $this->trailer_url = Crypt::encryptString($this->bunny_trailer_url);
        } else {
            $this->trailer_url = $this->trailer_url_type == 'Local'
                ? setBaseUrlWithFileName($this->trailer_url,'video','episode')
                : $this->trailer_url;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'entertainment_id' => $this->entertainment_id,
            'season_id' => $this->season_id,
            'trailer_url_type' => $this->trailer_url_type,
            'trailer_url' => $this->trailer_url,
            'access' => $this->access,
            'plan_id' => $this->plan_id,
            'imdb_rating' => $this->IMDb_rating,
            'content_rating' => $this->content_rating,
            'watched_time' => optional($this->continue_watch)->watched_time ?? null,
            'duration' => $this->duration,
            'release_date' => $this->release_date,
            'is_restricted' => $this->is_restricted,
            'short_desc' => $this->short_desc,
            'description' => strip_tags($this->description),
            'video_upload_type' => $this->video_upload_type,
            'video_url_input' => $this->video_upload_type=='Local' ? setBaseUrlWithFileName($this->video_url_input,'video','episode') : $this->video_url_input,
            'enable_quality' => $this->enable_quality,
            'is_download' => $this->is_download ?? false,
            'download_status' => $this->download_status,
            'download_type' => $this->download_type,
            'download_url' => $this->download_url,
            'enable_download_quality' => $this->enable_download_quality,
            'download_quality' => $downloadMappings,
            'poster_image' =>setBaseUrlWithFileName($this->poster_url,'image','episode'),
            'language' => optional($this->entertainmentdata)->language,
            'video_links' => $this->EpisodeStreamContentMapping ?? null,
            'plan' => new PlanResource($this->plan),
            'subtitle_info' => $this->enable_subtitle == 1 ? SubtitleResource::collection($this->subtitles) : null,
            'genres' => GenresResource::collection($this->genre_data),
            'tvShowLinks' => $tvShowLinks,
            'more_items' => CommonContentResourceV3::collection($this->moreItems),
            'download_id' => !empty($download) ? $download->id: null,
            'poster_tv_image' =>  setBaseUrlWithFileName($this->poster_tv_url,'image','episode'),
            'price' => (float)$this->price,
            'discounted_price' => round((float)$this->price - ($this->price * ($this->discount / 100)), 2),
            'purchase_type' => $this->purchase_type,
            'access_duration' => $this->access_duration,
            'discount'=> (float)$this->discount,
            'available_for' => $this->available_for,
            'intro_starts_at' => $this->start_time ?? null,
            'intro_ends_at' => $this->end_time ?? null,
            'bunny_video_url' => $this->bunny_video_url,
            'bunny_trailer_url' => $this->bunny_trailer_url,
            'tvshow_name' => optional($this->entertainmentdata)->name ?? optional($this->entertainmentdata)->title ?? '',
        ];
    }
}
