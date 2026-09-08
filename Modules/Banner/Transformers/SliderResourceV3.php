<?php

namespace Modules\Banner\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Entertainment\Models\Entertainment;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\Entertainment\Transformers\MoviesResourceBannerV3;
use Modules\Entertainment\Transformers\TvshowResourceBannerV3;
use Modules\LiveTV\Transformers\LiveTvChannelResourceBannerV3;
use Modules\Entertainment\Models\Watchlist;
use Illuminate\Support\Facades\Crypt;
use Modules\Video\Models\Video;
use Modules\Video\Transformers\VideoResourceBannerV3;
use Modules\Subscriptions\Models\Subscription;
use Modules\Entertainment\Models\EntertainmentGenerMapping;


class SliderResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function __construct($resource, $userId = null)
    {
        parent::__construct($resource);
        $this->userId = $userId;
    }

    public function toArray($request): array
    {
        $entertainment = null;
        $data = null;
        
        // STEP 1: Use pre-loaded data (NO DB queries in Resource)
        $deviceTypeResponse = $this->preloaded_device_support ?? ['isDeviceSupported' => true];
        \Log::info("deviceTypeResponse: ".json_encode($deviceTypeResponse));
        $purchasedIds = $this->preloaded_purchased_ids ?? [];
        $userPlanId = $this->userPlanId ?? null;
        
        if($this->banner_for == 'home'){
            $this->banner_for = $this->type;
        }
        
        switch ($this->banner_for) {
                case 'movie':
                case 'tv_show':
                case 'tvshow':
                // STEP 1: Use pre-loaded entertainment (NO DB query)
                $entertainment = $this->preloaded_entertainment ?? null;

                if ($entertainment) {
                    // STEP 3: Use pre-loaded watchlist status (NO DB query)
                    $entertainment['is_watch_list'] = $this->preloaded_is_watchlist ?? 0;

                    $entertainment->user_id = $this->user_id ?? null;
                    $entertainment->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $entertainment->required_plan_level = $userPlanId >= $entertainment->plan_id ? 1 : 0;
                    $posterUrl = $this->device_type == 'tv' ? $entertainment->poster_tv_url : $entertainment->poster_url;
                    // Extract filename from full URL if it's a URL, otherwise use as is
                    $entertainment->poster_image = $posterUrl ? (filter_var($posterUrl, FILTER_VALIDATE_URL) ? basename($posterUrl) : $posterUrl) : null;
                    $entertainment->user_id = $this->user_id ?? null;
                    $entertainment->type = $this->type === 'movie'? 'movie':'tvshow';
                    $entertainment->imdb_rating = $this->imdb_rating ?? $this->IMDb_rating;
                    $entertainment->trailer_url =  $entertainment->trailer_url_type == 'Local' ? setBaseUrlWithFileName($entertainment->trailer_url, 'video', $entertainment->type) : $entertainment->trailer_url;
                    $entertainment->access = $entertainment->movie_access;
                    // STEP 3: Optimized setContentAccess with purchasedIds
                    $entertainment = setContentAccess($entertainment, $this->user_id, $userPlanId, $purchasedIds);
                    $data = $this->type === 'movie' ? new MoviesResourceBannerV3($entertainment) : new TvshowResourceBannerV3($entertainment);
                }
                break;

            case 'livetv':
                // STEP 1: Use pre-loaded livetv (NO DB query)
                $livetv = $this->preloaded_livetv ?? null;
                
                if ($livetv) {
                    $livetv->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $livetv->required_plan_level = $userPlanId >= $livetv->plan_id ? 1 : 0;
                    $posterUrl = $this->device_type == 'tv' ? $livetv->poster_tv_url : $livetv->poster_url;
                    $livetv->poster_image = $posterUrl ? (filter_var($posterUrl, FILTER_VALIDATE_URL) ? basename($posterUrl) : $posterUrl) : null;
                    $livetv->user_id = $this->user_id ?? null;
                    $livetv->type = 'livetv';
                    $livetv->imdb_rating = $this->imdb_rating ?? $this->IMDb_rating;
                    $livetv = setContentAccess($livetv, $this->user_id, $userPlanId, $purchasedIds);
                    $data = new LiveTvChannelResourceBannerV3($livetv);
                }
                break;
            case 'video':
                // STEP 1: Use pre-loaded video (NO DB query)
                $video = $this->preloaded_video ?? null;
                
                if ($video) {
                    // STEP 3: Use pre-loaded watchlist status (NO DB query)
                    $video['is_watch_list'] = $this->preloaded_is_watchlist ?? 0;
                    
                    $video->user_id = $this->user_id ?? null;
                    $video->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $video->required_plan_level = $userPlanId >= $video->plan_id ? 1 : 0;
                    $posterUrl = $this->device_type == 'tv' ? $video->poster_tv_url : $video->poster_url;
                    $video->poster_image = $posterUrl ? (filter_var($posterUrl, FILTER_VALIDATE_URL) ? basename($posterUrl) : $posterUrl) : null;
                    $video->user_id = $this->user_id ?? null;
                    $video->type = 'video';
                    $video->imdb_rating = $this->imdb_rating ?? $this->IMDb_rating;
                    // STEP 3: Optimized setContentAccess with purchasedIds
                    $video = setContentAccess($video, $this->user_id, $userPlanId, $purchasedIds);
                    $data = new VideoResourceBannerV3($video);
                }
                break;
            case 'promotional':
                $data = [
                    "title" => $this->title,
                    "description" => $this->description,
                ];
                break;

        }
        if ($data instanceof JsonResource) {
            $dataArray = $data->toArray($request);
        } elseif (is_array($data)) {
            $dataArray = $data;
        } elseif (is_object($data)) {
            $dataArray = (array)$data;
        } else {
            $dataArray = [];
        }

        // Use banner poster_url if available, otherwise fall back to entertainment poster_url
        $posterUrl = $this->poster_url;
        if (empty($posterUrl) && isset($dataArray['poster_image'])) {
            $posterUrl = $dataArray['poster_image'];
        }

        return [
            'id' => $this->id,
            'poster_image' => setBaseUrlWithFileName($posterUrl,'image','banner'),
            'poster_tv_image' => $dataArray['poster_tv_image'] ?? null,
            'details' =>  $dataArray['details'] ?? $dataArray ?? null,
            'trailer_data' => $dataArray['trailer_data'] ?? $dataArray['trailer_data'] ?? null,
        ];
    }
}
