<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Genres\Transformers\GenresResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Entertainment\Transformers\ReviewResource;
use Modules\CastCrew\Transformers\CastCrewListResource;
use Modules\Entertainment\Transformers\EpisodeResource;
use Modules\Entertainment\Transformers\TvshowResource;
use Modules\Entertainment\Models\EntertainmentGenerMapping;
use Modules\Entertainment\Models\Entertainment;
use Modules\Subscriptions\Models\Plan;
use Carbon\Carbon;
use Modules\Episode\Models\Episode;
use Modules\Subscriptions\Models\Subscription;

class TvshowDetailResourceV2 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $genre_data = [];

        // Get genre IDs from the relationship
        $genre_ids = !empty($this->entertainmentGenerMappings) 
            ? $this->entertainmentGenerMappings->pluck('genre_id')->toArray() 
            : [];
        
        // Only query if we have genre IDs, otherwise use empty array to return no results
        $entertaintment_ids = [];
        if (!empty($genre_ids)) {
            $entertaintment_ids = EntertainmentGenerMapping::whereIn('genre_id', $genre_ids)
                ->pluck('entertainment_id')
                ->toArray();
        }

        $more_items = Entertainment::whereIn('id', $entertaintment_ids)->where('type','tvshow');

            isset(request()->is_restricted) && $more_items = $more_items->where('is_restricted', request()->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $more_items = $more_items->where('is_restricted',0);

         $more_items = $more_items->where('status',1)
            ->limit(7)->get()
            ->except($this->id);

        $plans = [];


        $casts = [];
        $directors = [];
        foreach ($this->entertainmentTalentMappings as $mapping) {

            if($mapping->talentprofile){

            if ($mapping->talentprofile->type === 'actor') {
                $casts[] = $mapping->talentprofile;
            } elseif ($mapping->talentprofile->type === 'director') {
                $directors[] = $mapping->talentprofile;
            }
           }
        }

        $tvShowLinks = [];
        foreach($this->season as $season)
        {

            $episodes = Episode::selectRaw('episodes.*,plan.level as plan_level, (CASE WHEN (select exists(select id from `entertainment_downloads` where `entertainment_id` = ? AND `user_id` = ? AND `entertainment_type` = "episode" AND is_download = 1 LIMIT 1)) THEN 1 ELSE 0 END) AS is_download', [$this->id, $this->userId])
            ->leftJoin('plan','plan.id','=','episodes.plan_id');

            isset(request()->is_restricted) && $episodes = $episodes->where('is_restricted', request()->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $episodes = $episodes->where('is_restricted',0);

            $episodes = $episodes->where('season_id', $season->id)
            ->get();

            $allHaveEpisodeNumber = $episodes->every(function ($episode) {
                return !is_null($episode->episode_number);
            });

            if ($allHaveEpisodeNumber && $episodes->count() > 0) {
                $episodes = $episodes->sortBy('episode_number')->values();
            }

            $totalEpisodes = $episodes->count();
            $tvShowLinks[] = [
                'season_id' => $season->id,
                'name' => $season->name,
                'short_desc' => $season->short_desc,
                'description' => $season->description,
                'poster_image' => setBaseUrlWithFileName($season->poster_url,'image','season'),
                'trailer_url_type' => $season->trailer_url_type,
                'trailer_url ' => $season->trailer_url_type=='Local' ? setBaseUrlWithFileName($season->trailer_url,'video','season') : $season->trailer_url,
                'total_episodes' => $totalEpisodes,
                'episodes' => self::getEpisodes($episodes->take(5))
            ];

        }
        $header = request()->headers->all();
        $device_type = !empty($header['device-type'])? $header['device-type'][0] : []; //for tv
        $getDeviceTypeData = Subscription::checkPlanSupportDevice($request->user_id,$device_type);
        $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true); // Decode to associative array

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => strip_tags($this->description),
            'trailer_url_type' => $this->trailer_url_type,
            'type' => $this->type,
            'trailer_url' => $this->trailer_url_type=='Local' ? setBaseUrlWithFileName($this->trailer_url,'video',$this->type) : $this->trailer_url,
            'movie_access' => $this->movie_access,
            'plan_id' => $this->plan_id,
            'plan_level' => $this->plan_level,
            'language' => $this->language,
                'imdb_rating' => $this->IMDb_rating ?? $this->imdb_rating,
            'content_rating' => $this->content_rating,
            'duration' => $this->duration,
            'release_date' => $this->release_date,
            'release_year' => $this->release_year,
            'is_restricted' => $this->is_restricted,
            'video_upload_type' => $this->video_upload_type,
            'video_url_input' => $this->video_upload_type=='Local' ? setBaseUrlWithFileName($this->video_url_input,'video',$this->type) : $this->video_url_input,
            'enable_quality' => $this->enable_quality,
            'download_url' => $this->download_url,
            'poster_image' =>setBaseUrlWithFileName($this->poster_url,'image',$this->type),
            'thumbnail_image' =>setBaseUrlWithFileName($this->thumbnail_url,'image',$this->type),
            'is_watch_list' => $this->is_watch_list,
            'is_likes' => $this->is_likes ,
            'your_review' => $this->your_review ?? null,
            'genres' =>  self::getGenes($this->genresdata ?? []),
            'three_reviews' => ReviewResource::collection($this->reviews->take(3)),
            'reviews' => ReviewResource::collection($this->reviews),
            'casts' => CastCrewListResource::collection($casts),
            'directors' => CastCrewListResource::collection($directors),
            'tvShowLinks' => $tvShowLinks,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
           'is_device_supported' => $deviceTypeResponse['isDeviceSupported'],
           'price' => (float)$this->price,
           'discounted_price' => round((float)$this->price - ($this->price * ($this->discount / 100)), 2),
            'purchase_type' => $this->purchase_type,
            'access_duration' => $this->access_duration,
            'discount'=> (float)$this->discount,
            'available_for' => $this->available_for,
            'is_purchased' => $this->isPurchased($this->id,$this->type,$this->user_id),
        ];
    }

    public static function getGenes($genres)
    {
        $result = [];
        if (!empty($genres) && (is_array($genres) || $genres instanceof \Countable)) {
            if(count($genres) > 0)
            {
                foreach($genres as $key=>$val)
                {
                    // Handle both array and object access
                    $id = is_array($val) ? ($val['id'] ?? null) : ($val->id ?? null);
                    $name = is_array($val) ? ($val['name'] ?? null) : ($val->name ?? null);
                    $fileUrl = is_array($val) ? ($val['file_url'] ?? null) : ($val->file_url ?? null);
                    $status = is_array($val) ? ($val['status'] ?? null) : ($val->status ?? null);
                    
                    $result[] = [
                        'id' => $id,
                        'name' => $name,
                        'genre_image' => !empty($fileUrl) ? setBaseUrlWithFileName($fileUrl,'image','genre') : null,
                        'status' => $status,
                    ];
                }
            }
        }
        return $result;
    }

    public static function getEpisodes($resultData)
    {
        $result = [];
        if(count($resultData) > 0)
        {
            foreach($resultData as $key=>$val)
            {
                $result[] = [
                    'id' => $val['id'],
                    'name' => $val['name'],
                    'entertainment_id' => $val['entertainment_id'],
                    'season_id' => $val['season_id'],
                    'episode_number' => $val['episode_number'] ?? null,
                    'trailer_url_type' => $val['trailer_url_type'],
                    'type'=>'episode',
                    'trailer_url' => $val['trailer_url_type']=='Local' ? setBaseUrlWithFileName($val['trailer_url'],'video','episode') : $val['trailer_url'],
                    'access' => $val['access'],
                    'plan_id' => $val['plan_id'],
                    'plan_level' => $val['plan_level'],
                    'imdb_rating' => $val['IMDb_rating'],
                    'content_rating' => $val['content_rating'],
                    'duration' => $val['duration'],
                    'release_date' => $val['release_date'],
                    'is_restricted' => $val['is_restricted'],
                    'short_desc' => $val['short_desc'],
                    'description' => strip_tags($val['description']),
                    'video_upload_type' => $val['video_upload_type'],
                    'video_url_input' => $val['video_upload_type']=='Local' ? setBaseUrlWithFileName($val['video_url_input'],'video','episode') : $val['video_url_input'],
                    'download_status' => $val['is_download'] ?? false,
                    'enable_quality' => $val['enable_quality'],
                    'download_url' => $val['download_url'],
                    'poster_image' =>  setBaseUrlWithFileName($val['poster_url'],'image','episode'),
                    'video_links' => $val->EpisodeStreamContentMapping ?? 'null',
                ];
            }
        }
        return $result;
    }
}
