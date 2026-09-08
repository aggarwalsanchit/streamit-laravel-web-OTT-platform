<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\MobileSetting;
use Modules\Entertainment\Models\Entertainment;
use Modules\Banner\Models\Banner;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Banner\Transformers\SliderResource;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\LiveTV\Transformers\LiveTvChannelResource;
use Modules\LiveTV\Transformers\LiveTvChannelResourceV3;
use Modules\CastCrew\Models\CastCrew;
use Modules\CastCrew\Transformers\CastCrewListResource;
use Modules\Genres\Transformers\GenresResource;
use Modules\Genres\Models\Genres;
use Modules\Video\Models\Video;
use App\Services\RecommendationService;
use App\Services\RecommendationServiceV2;
use App\Services\RecommendationServiceV3;
use Modules\Entertainment\Transformers\MoviesResource;
use Modules\Entertainment\Transformers\MoviesResourceV3;
use Modules\Entertainment\Transformers\CommanResource;
use Modules\Entertainment\Transformers\CommanResourceV3;
use Modules\Entertainment\Transformers\TvshowResource;
use Modules\Entertainment\Transformers\TvshowResourceV3;
use Modules\Constant\Models\Constant;
use Modules\Video\Transformers\VideoResource;
use Modules\Video\Transformers\VideoResourceV3;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Modules\Banner\Transformers\SliderResourceV3;
use Modules\Entertainment\Transformers\ContinueWatchResourceV2;
use Modules\Entertainment\Transformers\ContinueWatchResourceV3;
use Modules\Entertainment\Transformers\SeasonResource;
use Modules\Entertainment\Transformers\EpisodeResource;
use Modules\Entertainment\Transformers\SeasonResourceV3;
use Modules\Entertainment\Transformers\EpisodeResourceV3;
use Modules\Episode\Models\Episode;
use Modules\Season\Models\Season;
use Modules\Ad\Models\CustomAdsSetting;
use Modules\Subscriptions\Models\Subscription;
use Modules\Entertainment\Transformers\Backend\CommonContentResourceV3;
use Modules\Frontend\Models\PayPerView;



class DashboardController extends Controller
{
    protected $recommendationService,$recommendationServiceV2,$recommendationServiceV3;
    public function __construct(RecommendationService $recommendationService, RecommendationServiceV2 $recommendationServiceV2, RecommendationServiceV3 $recommendationServiceV3)
    {
        $this->recommendationService = $recommendationService;
        $this->recommendationServiceV2 = $recommendationServiceV2;
        $this->recommendationServiceV3 = $recommendationServiceV3;

    }

      public function DashboardDetailDataV2(Request $request)
    {

        $user_id = !empty($request->user_id) ? $request->user_id : null;

        if (!Cache::has('genres')) {
            $genresData = Genres::get(['id','name'])->keyBy('id')->toArray();
            Cache::put('genres', $genresData);
        }


            if($request->has('user_id'))
            {
            //    $continueWatchList = ContinueWatch::where('user_id', $user_id)
            //    ->where('profile_id',$request->profile_id)->get();
            //    $continueWatch = ContinueWatchResource::collection($continueWatchList);

               $user = User::where('id',$request->user_id)->first();
               $profile_id=$request->profile_id;

               if( $user_id !=null)
               {
                   $user = User::where('id',$user_id)->first();

                    $likedMovies = $this->recommendationServiceV2->getLikedMovies($user, $profile_id);
                    $likedMovies->each(function ($movie) use ($user_id) {
                        $movie->user_id = $user_id; // Add the user_id to each movie
                    });
                    $likedMovies = CommanResource::collection($likedMovies);
                    $viewedMovies = $this->recommendationService->getEntertainmentViews($user, $profile_id);
                    $viewedMovies->each(function ($movie) use ($user_id) {
                        $movie->user_id = $user_id; // Add the user_id to each movie
                    });
                    $viewedMovies = CommanResource::collection($viewedMovies);

                    $FavoriteGener = $this->recommendationService->getFavoriteGener($user, $profile_id);
                    $FavoriteGener = GenresResource::collection($FavoriteGener);


                    $favorite_personality = $this->recommendationService->getFavoritePersonality($user, $profile_id);
                     $favorite_personality = CastCrewListResource::collection($favorite_personality);

                    $trendingMovies = $this->recommendationService->getTrendingMoviesByCountry($user);
                    $trendingMovies->each(function ($movie) use ($user_id) {
                        $movie->user_id = $user_id; // Add the user_id to each movie
                    });
                    $trendingMovies = CommanResource::collection($trendingMovies);
               }

            }

           $setting_latestMovieIds = MobileSetting::getNameAndValueBySlug('latest-movies');
            $latestMovieIds = ($setting_latestMovieIds && empty($setting_latestMovieIds['type'])) ? $setting_latestMovieIds['value'] : null;
           $latestMovieIdsArray = json_decode($latestMovieIds, true);


           $latest_movie = (!empty($latestMovieIdsArray)) ? Entertainment::get_latest_movie($latestMovieIdsArray) : collect();
           $latest_movie->each(function ($movie) use ($user_id) {
                $movie->user_id = $user_id; // Add the user_id to each movie
            });


           $latest_movie = MoviesResource::collection($latest_movie)->toArray(request());


           $setting_languageIds = MobileSetting::getNameAndValueBySlug('enjoy-in-your-native-tongue');
            $languageIds = ($setting_languageIds && empty($setting_languageIds['type'])) ? $setting_languageIds['value'] : null;
           $languageIdsArray = json_decode($languageIds, true);
           $popular_language = !empty($languageIdsArray) ? Constant::whereIn('id', $languageIdsArray)->where('status', 1)->where('deleted_at', null)->get() : collect();

           $setting_popularMovieIds = MobileSetting::getNameAndValueBySlug('popular-movies');
            $popularMovieIds = ($setting_popularMovieIds && empty($setting_popularMovieIds['type'])) ? $setting_popularMovieIds['value'] : null;

           $popularMovieIdsArray = json_decode($popularMovieIds, true);
           $popular_movie = (!empty($popularMovieIdsArray)) ? Entertainment::get_popular_movie($popularMovieIdsArray) : collect();
           $popular_movie->each(function ($movie) use ($user_id) {
                $movie->user_id = $user_id; // Add the user_id to each movie
           });
           $popular_movie = MoviesResource::collection($popular_movie)->toArray(request());


           $channelIds = MobileSetting::getValueBySlug('top-channels');
           $channelIdsArray = json_decode($channelIds, true);

           $top_channel = (!empty($channelIdsArray)) ? LiveTvChannel::get_top_channel($channelIdsArray) : collect();
           $top_channel = LiveTvChannelResource::collection($top_channel)->toArray(request());



           $castIds = MobileSetting::getValueBySlug('your-favorite-personality');
           $castIdsArray = json_decode($castIds, true);
           $personality = [];
            if (!empty($castIdsArray)) {
               $casts = CastCrew::whereIn('id', $castIdsArray)->where('status', 1)->where('deleted_at', null)->get();
               foreach ($casts as $value) {
                   $personality[] = [
                       'id' => $value->id,
                       'name' => $value->name,
                       'type' => $value->type,
                       'profile_image' => setBaseUrlWithFileName($value->file_url, 'image', 'castcrew'),
                   ];
               }
            }

           $movieIds = MobileSetting::getValueBySlug('500-free-movies');
           $movieIdsArray = json_decode($movieIds, true);


           $free_movie = !empty($movieIdsArray) ? Entertainment::get_free_movie($movieIdsArray) : collect();
           $free_movie = MoviesResource::collection($free_movie)->toArray(request());


           $popular_tvshowIds = MobileSetting::getValueBySlug('popular-tvshows');
           $popular_tvshowIdsArray = json_decode($popular_tvshowIds, true);

           $popular_tvshow = !empty($popular_tvshowIdsArray) ? Entertainment::get_popular_tvshow($popular_tvshowIdsArray) : collect();
           $popular_tvshow->each(function ($video) use ($user_id) {
                 $video->user_id = $user_id; // Add the user_id to each movie
            });
           $popular_tvshow = TvshowResource::collection($popular_tvshow)->toArray(request());


           $genreIds = MobileSetting::getValueBySlug('genre');
           $genreIdsArray = json_decode($genreIds, true);
           $genres = !empty($genreIdsArray) ? GenresResource::collection(
               Genres::whereIn('id', $genreIdsArray)
                   ->where('status', 1)
                   ->where('deleted_at', null)
                   ->get()
           ) : collect();

            $videoIds = MobileSetting::getValueBySlug('popular-videos');
            $videoIdsArray = json_decode($videoIds, true);

            $popular_videos = !empty($videoIdsArray) ? Video::get_popular_videos($videoIdsArray) : collect();
            $popular_videos->each(function ($video) use ($user_id) {
                $video->user_id = $user_id; // Add the user_id to each movie
            });
            $popular_videos = VideoResource::collection($popular_videos)->toArray(request());

            $tranding_movie = Entertainment::get_entertainment_list();
            $tranding_movie = MoviesResource::collection($tranding_movie)->toArray(request());
            $payPerViewRequest = new Request(['user_id' => $user_id]);

            $payPerViewContent = $this->getPayPerViewUnlockedContent( $payPerViewRequest);
            // Define slugs and their default names
            $slugsWithDefaults = [
                'latest-movies' => 'Latest Movies',
                'enjoy-in-your-native-tongue' => 'Popular Language',
                'popular-movies' => 'Popular Movies',
                'top-channels' => 'Top Channels',
                'your-favorite-personality' => 'Popular Personalities',
                '500-free-movies' => 'Free Movies',
                'popular-tvshows' => 'Popular TV Show',
                'genre' => 'Genres',
                'popular-videos' => 'Popular Videos',
            ];

            // Fetch all required settings in one query
            $settings = MobileSetting::whereIn('slug', array_keys($slugsWithDefaults))->pluck('name', 'slug');

            // Resolve names with fallback to default
            $sectionNames = [];
            foreach ($slugsWithDefaults as $slug => $default) {
                $sectionNames[$slug] = $settings[$slug] ?? $default;
            }
           $responseData = [
               'latest_movie' => [
                    'name' => $sectionNames['latest-movies'],
                    'data' => $latest_movie,
                    ],
                'popular_language' => [
                    'name' => $sectionNames['enjoy-in-your-native-tongue'],
                    'data' => $popular_language,
                ],
                'popular_movie' => [
                    'name' => $sectionNames['popular-movies'],
                    'data' => $popular_movie,
                ],
                'top_channel' => [
                    'name' => $sectionNames['top-channels'],
                    'data' => $top_channel,
                ],
                'popular_tvshow' => [
                    'name' => $sectionNames['popular-tvshows'],
                    'data' => $popular_tvshow,
                ],
                'personality' => [
                    'name' => $sectionNames['your-favorite-personality'],
                    'data' => $personality,
                ],
                'free_movie' => [
                    'name' => $sectionNames['500-free-movies'],
                    'data' => $free_movie,
                ],
                'genres' => [
                    'name' => $sectionNames['genre'],
                    'data' => $genres,
                ],
                'popular_videos' => [
                    'name' => $sectionNames['popular-videos'],
                    'data' => $popular_videos,
                ],
               'likedMovies' => $likedMovies ?? [],
               'viewedMovies' => $viewedMovies ?? [],
               'trendingMovies' => $trendingMovies ?? [],
               'favorite_gener' => $FavoriteGener ?? [],
               'favorite_personality' => $favorite_personality ?? [],
               'base_on_last_watch'=> $Lastwatchrecommendation ?? [],
               'tranding_movie'=>$tranding_movie,
               'pay_per_view' => $payPerViewContent,
           ];

        return ApiResponse::success($responseData, __('messages.dashboard_detail'), 200);
    }


public function getTrandingData(Request $request){


    if ($request->has('is_ajax') && $request->is_ajax == 1) {

        $popularMovieIds = MobileSetting::getValueBySlug(slug: 'popular-movies');
        $movieList = Entertainment::whereIn('id',json_decode($popularMovieIds));

        isset(request()->is_restricted) && $movieList = $movieList->where('is_restricted', request()->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $movieList = $movieList->where('is_restricted',0);

        $movieList = $movieList->where('status',1)
                        ->where(function($query) {
                            $query->whereNull('release_date')
                                ->orWhere('release_date', '<=', now());
                        })
                    ->get();

        $html = '';
        if($request->has('section')&& $request->section == 'tranding_movie'){
            $movieData = (isenablemodule('movie') == 1) ? CommonContentResourceV3::collection($movieList) : [];
            if(!empty( $movieData)){

                foreach( $movieData->toArray(request()) as $index => $movie){
                    $html .= view('frontend::components.card.card_entertainment',['value' => $movie])->render();
                }
            }
        }


    return ApiResponse::success(null, __('movie.tvshow_list'), 200, ['html' => $html]);
    }



}

       public function DashboardDetailV2(Request $request)
    {
        if (!Cache::has('genres')) {
            $genresData = Genres::get(['id','name'])->keyBy('id')->toArray();
            Cache::put('genres', $genresData);
        }

        $user_id = !empty($request->user_id) ? $request->user_id : null;
        $continueWatch = [];

        $isContinueWatchEnabled = MobileSetting::getCacheValueBySlug('continue-watching') == 1;

        if($request->has('user_id') && $isContinueWatchEnabled){
            $continueWatchList = ContinueWatch::where('user_id', $user_id)
            ->where('profile_id',$request->profile_id)->get();
            $continueWatch = ContinueWatchResourceV2::collection($continueWatchList);
        }

        $setting_isBanner = MobileSetting::getNameAndValueBySlug('banner');
            $isBanner = ($setting_isBanner && empty($setting_isBanner['type'])) ? $setting_isBanner['value'] : null;
        $sliderList = $isBanner
        ? Banner::where('banner_for','home')->where('status', 1)->get()
        : collect();

        // Filter home banners based on their actual content type (type field)
        $sliderList = $sliderList->filter(function($banner) {
            $bannerType = $banner->type;
            if ($bannerType == 'movie') {
                return isenablemodule('movie') == 1;
            } elseif ($bannerType == 'tvshow' || $bannerType == 'tv_show') {
                return isenablemodule('tvshow') == 1;
            } elseif ($bannerType == 'video') {
                return isenablemodule('video') == 1;
            } elseif ($bannerType == 'livetv') {
                return isenablemodule('livetv') == 1;
            }
            // For promotional or other types, include them
            return true;
        });

        $sliders = SliderResource::collection(
            $sliderList->map(fn($slider) => new SliderResource($slider, $user_id))
       );


        $setting_topMovieIds = MobileSetting::getNameAndValueBySlug('top-10');
            $topMovieIds = ($setting_topMovieIds && empty($setting_topMovieIds['type'])) ? $setting_topMovieIds['value'] : null;

        $top_10 = !empty($topMovieIds) ? Entertainment::get_top_movie(json_decode($topMovieIds, true)) : collect();



        $top_10 = MoviesResource::collection($top_10)->toArray(request());

        $responseData = [
           'slider' => $sliders,
           'continue_watch' => $continueWatch,
           'top_10' => [
              'name' => MobileSetting::where('slug', 'top-10')->value('name') ?? 'Top 10',
              'data' => $top_10,
          ],
        ];

       // Cache::put($cacheKey,$responseData);

       return ApiResponse::success($responseData, __('messages.dashboard_detail'), 200);
    }


    public function getEntertainmentDataV3(Request $request)
    {
        // $type = $request->query('type', 'movie'); // Default to 'movie'
        $type = $request->query('banner_for'); // Default to 'movie'

        $user_id = $request->user_id ?? null;
        $profile_id = $request->profile_id ?? null;
        $device_type =  getDeviceType($request)??null;
        $is_restricted = $request->is_restricted ?? null;
        // Create cache key based on request parameters
        $cacheKey = 'entertainment_data_v3_'. $type . '_' . $user_id . '_' . $profile_id . '_' . $device_type . '_' . $is_restricted;
        
        // Check cache version to invalidate cache when genres are updated
        // When version changes, old cache keys become invalid
        $cacheVersionKey = 'banner_data_cache_version';
        $cacheVersion = Cache::get($cacheVersionKey, 0);
        $versionedCacheKey = $cacheKey . '_v' . $cacheVersion;
        
        // Also clear the non-versioned key if it exists (for backward compatibility)
        Cache::forget($cacheKey);
        
        $ttl = 300; // 5 minutes cache

        $result = cacheApiResponse($versionedCacheKey, $ttl, function() use ($type, $user_id, $profile_id, $device_type, $is_restricted, $request) {
            // OPTIMIZATION 1: Use cached method for MobileSetting
            $setting_isBanner = MobileSetting::getNameAndValueBySlug('banner');
            $isBanner = ($setting_isBanner && empty($setting_isBanner['type'])) ? $setting_isBanner['value'] : null;
            if($type == 'tvshow'){
                $type = 'tv_show';
                $is_restricted = $is_restricted ?? null;
            }

            // OPTIMIZATION 2: Cache module enable checks (avoid repeated DB queries)
            $moduleCache = cache()->remember('module_enable_status', 600, function() {
                return [
                    'movie' => isenablemodule('movie'),
                    'tvshow' => isenablemodule('tvshow'),
                    'video' => isenablemodule('video'),
                    'livetv' => isenablemodule('livetv'),
                ];
            });

            if ($type == 'home') {
                $sliderList = $isBanner
                    ? Banner::select('id', 'banner_for', 'type', 'type_id', 'poster_url', 'poster_tv_url', 'title', 'description')
                        ->where('status',1)
                        ->where('deleted_at',null)
                        ->where('banner_for',$type)
                        ->get()
                    : collect();
                $sliderList = $sliderList->filter(function($banner) use ($moduleCache) {
                    $bannerType = $banner->type;
                    if ($bannerType == 'movie') {
                        return $moduleCache['movie'] == 1;
                    } elseif ($bannerType == 'tvshow' || $bannerType == 'tv_show') {
                        return $moduleCache['tvshow'] == 1;
                    } elseif ($bannerType == 'video') {
                        return $moduleCache['video'] == 1;
                    } elseif ($bannerType == 'livetv') {
                        return $moduleCache['livetv'] == 1;
                    }
                    return true;
                });
            } else {
                $isModuleEnabled = true;
                if ($type == 'movie') {
                    $isModuleEnabled = $moduleCache['movie'] == 1;
                } elseif ($type == 'tv_show') {
                    $isModuleEnabled = $moduleCache['tvshow'] == 1;
                } elseif ($type == 'video') {
                    $isModuleEnabled = $moduleCache['video'] == 1;
                } elseif ($type == 'livetv') {
                    $isModuleEnabled = $moduleCache['livetv'] == 1;
                }

                $sliderList = ($isBanner && $isModuleEnabled)
                    ? Banner::select('id', 'banner_for', 'type', 'type_id', 'poster_url', 'poster_tv_url', 'title', 'description')
                        ->where('status',1)
                        ->where('deleted_at',null)
                        ->where('banner_for',$type)
                        ->get()
                    : collect();
            }
            $userLevel = Subscription::select('plan_id')
            ->where(['user_id' => $user_id, 'status' => 'active'])
            ->latest()
            ->first();
            $userPlanId = $userLevel->plan_id ?? null;
            $profile_id = $request->profile_id ?? null;
            $finalProfileId = $profile_id ?? getCurrentProfile($user_id, $request);
            $isChildProfile = !empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0;
            
            // OPTIMIZATION 3: Cache device support result (expensive operation)
            $deviceSupport = Subscription::checkPlanSupportDevice($user_id, $device_type);
            $deviceTypeResponse = json_decode($deviceSupport->getContent(), true);
            
            // STEP 2: Bulk preload data - Get all type_ids
            $entertainmentTypeIds = [];
            $videoTypeIds = [];
            $livetvTypeIds = [];
            
            
            foreach ($sliderList as $banner) {
                $bannerType = $banner->banner_for == 'home' ? $banner->type : $banner->banner_for;
              
                if (in_array($bannerType, ['movie', 'tv_show', 'tvshow'])) {
                    $entertainmentTypeIds[] = $banner->type_id;
                } elseif ($bannerType == 'video') {
                    $videoTypeIds[] = $banner->type_id;
                } elseif ($bannerType == 'livetv') {
                    
                    $livetvTypeIds[] = $banner->type_id;
                }
            }
        
            
            // STEP 2: Bulk preload ALL entertainments in ONE query
            // OPTIMIZATION 7: Optimize genre loading - filter at relationship level
            $entertainments = [];
            if (!empty($entertainmentTypeIds)) {
              
                $uniqueIds = array_unique($entertainmentTypeIds);
                $entertainmentQuery = Entertainment::with([
                    'plan',
                    'entertainmentGenerMappings' => function($query) {
                        // Filter mappings where genre exists, is active, and not deleted
                        $query->whereHas('genre', function($q) {
                            $q->where('status', 1)
                              ->whereNull('deleted_at');
                        });
                    },
                    'entertainmentGenerMappings.genre' => function($query) {
                        // Ensure only active, non-deleted genres are loaded
                        $query->where('status', 1)
                              ->whereNull('deleted_at')
                              ->select('id', 'name', 'status', 'deleted_at');
                    }
                ])
                    ->select('id', 'name', 'type', 'release_date', 'plan_id', 'is_restricted', 'IMDb_rating', 'duration', 'poster_url', 'poster_tv_url', 'trailer_url', 'movie_access','trailer_url_type')
                    ->whereIn('id', $uniqueIds)
                    ->where('status', 1)
                    ->whereNull('deleted_at');
                
                if (isset($is_restricted)) {
                    $entertainmentQuery->where('is_restricted', $is_restricted);
                }
                if ($isChildProfile) {
                    $entertainmentQuery->where('is_restricted', 0);
                }
                
                $entertainments = $entertainmentQuery->get()->keyBy('id');
            }
            
            // Bulk preload videos
            $videos = [];
            if (!empty($videoTypeIds)) {
                $uniqueIds = array_unique($videoTypeIds);
                $videoQuery = Video::whereIn('id', $uniqueIds)
                    ->select('id', 'name', 'type', 'release_date', 'plan_id', 'is_restricted', 'IMDb_rating', 'duration', 'poster_url', 'poster_tv_url', 'trailer_url', 'access','trailer_url_type')
                    ->where('status', 1)
                    ->whereNull('deleted_at');
                
                if (isset($is_restricted)) {
                    $videoQuery->where('is_restricted', $is_restricted);
                }
                if ($isChildProfile) {
                    $videoQuery->where('is_restricted', 0);
                }
                
                $videos = $videoQuery->get()->keyBy('id');
            }
            
            // Bulk preload livetv
            $livetvChannels = [];
            if (!empty($livetvTypeIds)) {
                $uniqueIds = array_unique($livetvTypeIds);
                $livetvChannels = LiveTvChannel::whereIn('id', $uniqueIds)
                    ->select('id', 'name', 'plan_id', 'poster_url', 'poster_tv_url', 'access')
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->get()
                    ->keyBy('id');
            }
            
            // STEP 3: Fix Watchlist N+1 - Bulk fetch watchlist IDs with type filtering for accuracy
            $watchlistIds = [];
            if ($user_id && $finalProfileId) {
                $allTypeIds = array_unique(array_merge($entertainmentTypeIds, $videoTypeIds));
                if (!empty($allTypeIds)) {
                    $watchlistIds = \Modules\Entertainment\Models\Watchlist::where('user_id', $user_id)
                        ->where('profile_id', $finalProfileId)
                        ->whereIn('entertainment_id', $allTypeIds)
                        ->get(['entertainment_id', 'type'])
                        ->map(function($w) {
                            // Normalize type for matching
                            $type = ($w->type == 'tv_show' || $w->type == 'tvshow') ? 'tvshow' : $w->type;
                            return $type . '_' . $w->entertainment_id;
                        })
                        ->toArray();
                }
            }
            
            // OPTIMIZATION 4: Filter PayPerView by type_ids (only fetch what we need)
            $purchasedIds = [];
            if ($user_id) {
                $allTypeIds = array_merge($entertainmentTypeIds, $videoTypeIds);
                if (!empty($allTypeIds)) {
                    $purchasedIds = PayPerView::where('user_id', $user_id)
                        ->whereIn('movie_id', array_unique($allTypeIds))
                        ->where(function($q) {
                            $q->whereNull('view_expiry_date')
                              ->orWhere('view_expiry_date', '>', now());
                        })
                        ->get()
                        ->groupBy('type')
                        ->map(function($items) {
                            return $items->pluck('movie_id')->toArray();
                        })
                        ->toArray();
                }
            }
            
            // STEP 5: Pass prepared data into Resource
            $sliderList->each(function ($item) use ($user_id, $userPlanId, $is_restricted, $device_type, $profile_id, $entertainments, $videos, $livetvChannels, $watchlistIds, $deviceTypeResponse, $purchasedIds) {
                $item->user_id = $user_id;
                $item->device_type = $device_type;
                $item->userPlanId = $userPlanId;
                $item->is_restricted = $is_restricted;
                $item->profile_id = $profile_id;
                $item->poster_url = $device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
                
                // Attach pre-loaded data
                $typeId = $item->type_id;
                $bannerType = $item->banner_for == 'home' ? $item->type : $item->banner_for;
                
                if (in_array($bannerType, ['movie', 'tv_show', 'tvshow']) && isset($entertainments[$typeId])) {
                    $item->preloaded_entertainment = $entertainments[$typeId];
                    // Correct type-specific check
                    $normType = ($bannerType == 'movie') ? 'movie' : 'tvshow';
                    $item->preloaded_is_watchlist = in_array($normType . '_' . $typeId, $watchlistIds) ? 1 : 0;
                } elseif ($bannerType == 'video' && isset($videos[$typeId])) {
                    $item->preloaded_video = $videos[$typeId];
                    $item->preloaded_is_watchlist = in_array('video_' . $typeId, $watchlistIds) ? 1 : 0;
                } elseif ($bannerType == 'livetv' && isset($livetvChannels[$typeId])) {
                    $item->preloaded_livetv = $livetvChannels[$typeId];
                }
                
                $item->preloaded_device_support = $deviceTypeResponse;
                $item->preloaded_purchased_ids = $purchasedIds;
            });
            
            $sliders = SliderResourceV3::collection($sliderList)->toArray($request);
            $sliders = array_filter($sliders, function($item) {
                $details = $item['details'] ?? null;
                if ($details === null || (is_array($details) && empty($details))) {
                    return false;
                }
                return true;
            });
            $sliders = array_values($sliders);
            // OPTIMIZATION 5: Use withCount for notification count (single query)
            $all_unread_count = 0;
            if($user_id){
                $user = User::withCount('unreadNotifications')->where('id',$user_id)->first();
                $all_unread_count = $user->unread_notifications_count ?? 0;
            }

            return [
                'slider' => $sliders,
                'unread_notification_count'=> isset($all_unread_count) ? $all_unread_count : 0,
            ];
        });

        return ApiResponse::success($result['data'], __('messages.' . $type . '_detail'), 200);
    }

     public function DashboardDetailDataV3(Request $request)
    {
        $user_id = !empty($request->user_id) ? $request->user_id : null;
        $profile_id = $request->profile_id ?? null;

        $device_type = getDeviceType($request);

        // OPTIMIZATION: Simple cache key without expensive queries - move MobileSetting queries inside callback
        $baseCacheKey = 'dashboard_detail_data_v3_'.md5(json_encode([
            'user_id' => $user_id,
            'profile_id' => $profile_id,
            'device_type' => $device_type,
        ]));
        
        // Check cache version to invalidate cache when movies are updated/deleted
        $cacheVersionKey = 'dashboard_cache_version';
        $cacheVersion = Cache::get($cacheVersionKey, 0);
        $cacheKey = $baseCacheKey . '_v' . $cacheVersion;

        // Use cacheApiResponse helper for Redis caching
        $cachedResult = cacheApiResponse($cacheKey, 60, function () use ($request, $user_id, $profile_id, $device_type) {

            if (!Cache::has('genres')) {
                $genresData = Genres::where('status', 1)
                    ->whereNull('deleted_at')
                    ->get(['id','name'])
                    ->keyBy('id')
                    ->toArray();
                Cache::put('genres', $genresData);
            }
            
            // OPTIMIZATION: Batch fetch all MobileSetting values in one query
            $requiredSlugs = [
                'enjoy-in-your-native-tongue',
                'popular-movies',
                'top-channels',
                'your-favorite-personality',
                '500-free-movies',
                'popular-tvshows',
                'genre',
                'popular-videos'
            ];
            $mobileSettings = MobileSetting::whereIn('slug', $requiredSlugs)
                ->get(['value', 'slug', 'type'])
                ->keyBy('slug')
                ->toArray();
            
            $userPlanId = 0;
            $deviceTypeResponse = ['isDeviceSupported' => false];
            $purchasedIds = []; // Initialize for non-user case
            $user = null;

            if($user_id)
            {
                // OPTIMIZATION: Remove duplicate User query
                $user = User::where('id',$user_id)->first();
                $profile_id = $request->profile_id ?? $profile_id;

                // OPTIMIZATION: Cache device support check
                $deviceTypeResponse = cache()->remember(
                    "device_support_{$user_id}_{$device_type}",
                    600,
                    function() use ($user_id, $device_type) {
                        $getDeviceTypeData = Subscription::checkPlanSupportDevice($user_id, $device_type);
                        return json_decode($getDeviceTypeData->getContent(), true);
                    }
                );
                
                $userLevel = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();
                $userPlanId = $userLevel->plan_id ?? 0;
                
                // OPTIMIZATION: Bulk fetch PayPerView purchases for setContentAccess
                $purchasedIds = PayPerView::where('user_id', $user_id)
                    ->pluck('movie_id')
                    ->toArray();



                    // OPTIMIZATION: Wrap recommendation calls in try-catch and limit results
                    try {
                        $FavoriteGener = $this->recommendationService->getFavoriteGener($user, $profile_id);
                        $FavoriteGener = GenresResource::collection($FavoriteGener);
                    } catch (\Exception $e) {
                        $FavoriteGener = [];
                    }

                    try {
                        $favorite_personality = $this->recommendationService->getFavoritePersonality($user, $profile_id);
                        $favorite_personality = CastCrewListResource::collection($favorite_personality);
                    } catch (\Exception $e) {
                        $favorite_personality = [];
                    }

                    // OPTIMIZATION: getTrendingMoviesByCountry returns array of IDs, need to fetch Entertainment models (same as FrontendController)
                    try {
                        $trendingMovieIds = $this->recommendationServiceV3->getTrendingMoviesByCountry($user);
                        $trendingMovies = (!empty($trendingMovieIds)) ? Entertainment::get_recommended_movie($trendingMovieIds) : collect();
                        // OPTIMIZATION: Only process if collection is not empty
                        
                        if ($trendingMovies->isNotEmpty()) {
                            $trendingMovies->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type, $purchasedIds) {
                                $movie->user_id = $user_id;
                                $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                                $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                                $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                                $movie->access = $movie->movie_access;
                                $movie = setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);
                            });
                            $trendingMovies = CommanResourceV3::collection($trendingMovies)->toArray(request());
                        } else {
                            $trendingMovies = [];
                        }
                    } catch (\Exception $e) {
                        $trendingMovies = [];
                    }

            }


           // OPTIMIZATION: Use batched MobileSetting values
           $setting_languageIds = $mobileSettings['enjoy-in-your-native-tongue'] ?? null;
            $languageIds = ($setting_languageIds && empty($setting_languageIds['type'])) ? $setting_languageIds['value'] : null;
           $languageIdsArray = !empty($languageIds) ? json_decode($languageIds, true) : null;
           
           // OPTIMIZATION: Only process if IDs exist
           if (!empty($languageIdsArray) && is_array($languageIdsArray)) {
               $popular_language = Constant::whereIn('id', $languageIdsArray)
                   ->where('status', 1)
                   ->where('deleted_at', null)
                   ->select('id', 'name','language_image')
                   ->get()
                   ->makeHidden(['feature_image', 'media', 'status', 'created_at', 'updated_at', 'deleted_at']);
               
               $popular_language->each(function ($language) {
                   $language->language_image = setBaseUrlWithFileName($language->language_image,'image','constant');
               });
           } else {
               $popular_language = collect();
           }

           // OPTIMIZATION: Use batched MobileSetting values
           $setting_popularMovieIds = $mobileSettings['popular-movies'] ?? null;
            $popularMovieIds = ($setting_popularMovieIds && empty($setting_popularMovieIds['type'])) ? $setting_popularMovieIds['value'] : null;
           $popularMovieIdsArray = !empty($popularMovieIds) ? json_decode($popularMovieIds, true) : null;
           // OPTIMIZATION: Only process if IDs exist and limit array size to prevent large IN queries
           if (!empty($popularMovieIdsArray) && is_array($popularMovieIdsArray)) {
               // OPTIMIZATION: Limit to first 100 IDs to prevent huge IN() queries on 500k table
               $popularMovieIdsArray = array_slice($popularMovieIdsArray, 0, 100);
               $popular_movie = Entertainment::get_popular_movieV3($popularMovieIdsArray);
               $popular_movie->each(function ($movie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                   $movie->user_id = $user_id;
                   $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                   $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                   $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                   $movie->access = $movie->movie_access;
                   $movie = setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);
               });
               $popular_movie = MoviesResourceV3::collection($popular_movie)->toArray(request());
           } else {
               $popular_movie = [];
           }
            
           // OPTIMIZATION: Use batched MobileSetting values and limit IDs
           $setting_channelIds = $mobileSettings['top-channels'] ?? null;
            $channelIds = ($setting_channelIds && empty($setting_channelIds['type'])) ? $setting_channelIds['value'] : null;
           $channelIdsArray = !empty($channelIds) ? json_decode($channelIds, true) : null;
           
           // OPTIMIZATION: Limit to first 100 IDs to prevent huge IN() queries
           if (!empty($channelIdsArray) && is_array($channelIdsArray)) {
               $channelIdsArray = array_slice($channelIdsArray, 0, 100);
               $top_channel = LiveTvChannel::get_top_channel($channelIdsArray);
           } else {
               $top_channel = collect();
           }
            $top_channel->each(function ($channel) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                $channel->user_id = $user_id;
                $channel->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $channel->poster_image =  $device_type == 'tv' ? setBaseUrlWithFileName($channel->poster_tv_url,'image','livetv')  : setBaseUrlWithFileName($channel->poster_url,'image','livetv');
                $channel->access = $channel->access;
                $channel = setContentAccess($channel, $user_id, $userPlanId, $purchasedIds ?? []);
            });

           $top_channel = LiveTvChannelResourceV3::collection($top_channel)->toArray(request());
           

           // OPTIMIZATION: Use batched MobileSetting values
           $setting_castIds = $mobileSettings['your-favorite-personality'] ?? null;
            $castIds = ($setting_castIds && empty($setting_castIds['type'])) ? $setting_castIds['value'] : null;
           $castIdsArray = !empty($castIds) ? json_decode($castIds, true) : null;
           $personality = [];
            if (!empty($castIdsArray)) {
               $casts = CastCrew::whereIn('id', $castIdsArray)
                   ->where('deleted_at',null)
                   ->where('status',1)
                   ->select('id', 'name', 'type', 'file_url')
                   ->get();
               foreach ($casts as $value) {
                   $personality[] = [
                       'id' => $value->id,
                       'name' => $value->name,
                       'type' => $value->type,
                       'profile_image' => setBaseUrlWithFileName($value->file_url,'image','castcrew'),
                   ];
               }
            }

           // OPTIMIZATION: Use batched MobileSetting values
           $setting_movieIds = $mobileSettings['500-free-movies'] ?? null;
            $movieIds = ($setting_movieIds && empty($setting_movieIds['type'])) ? $setting_movieIds['value'] : null;
           $movieIdsArray = !empty($movieIds) ? json_decode($movieIds, true) : null;

           // OPTIMIZATION: Only process if IDs exist and limit array size
           if (!empty($movieIdsArray) && is_array($movieIdsArray)) {
               // OPTIMIZATION: Limit to first 100 IDs to prevent huge IN() queries
               $movieIdsArray = array_slice($movieIdsArray, 0, 100);
               $free_movie = Entertainment::get_free_movieV3($movieIdsArray);
               $free_movie->each(function ($freeMovie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                   $freeMovie->user_id = $user_id;
                   $freeMovie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                   $freeMovie->poster_image =  $device_type == 'tv' ? $freeMovie->poster_tv_url : $freeMovie->poster_url ?? null;
                   $freeMovie->trailer_url =  $freeMovie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($freeMovie->trailer_url, 'video', $freeMovie->type) : $freeMovie->trailer_url;
                   $freeMovie->access = $freeMovie->movie_access;
                   $freeMovie = setContentAccess($freeMovie, $user_id, $userPlanId, $purchasedIds ?? []);
               });
               $free_movie = MoviesResourceV3::collection($free_movie)->toArray(request());
           } else {
               $free_movie = [];
           }

           // OPTIMIZATION: Use batched MobileSetting values
           $setting_popular_tvshowIds = $mobileSettings['popular-tvshows'] ?? null;
            $popular_tvshowIds = ($setting_popular_tvshowIds && empty($setting_popular_tvshowIds['type'])) ? $setting_popular_tvshowIds['value'] : null;
           $popular_tvshowIdsArray = !empty($popular_tvshowIds) ? json_decode($popular_tvshowIds, true) : null;

           // OPTIMIZATION: Only process if IDs exist and limit array size
           if (!empty($popular_tvshowIdsArray) && is_array($popular_tvshowIdsArray)) {
               // OPTIMIZATION: Limit to first 100 IDs to prevent huge IN() queries
               $popular_tvshowIdsArray = array_slice($popular_tvshowIdsArray, 0, 100);
               $popular_tvshow = Entertainment::get_popular_tvshowV3($popular_tvshowIdsArray);
               if($user_id){
                       $popular_tvshow->each(function ($tvshow) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                           $tvshow->user_id = $user_id;
                           $tvshow->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                           $tvshow->poster_image =  $device_type == 'tv' ? $tvshow->poster_tv_url : $tvshow->poster_url ?? null;
                           $tvshow->trailer_url =  $tvshow->trailer_url_type == 'Local' ? setBaseUrlWithFileName($tvshow->trailer_url, 'video', $tvshow->type) : $tvshow->trailer_url;
                           $tvshow->access = $tvshow->movie_access;
                           $tvshow = setContentAccess($tvshow, $user_id, $userPlanId, $purchasedIds ?? []);
                       });
                }else{
                   $popular_tvshow->each(function ($tvshow) use ($device_type) {
                       $tvshow->isDeviceSupported = 0;
                       $tvshow->poster_image =  $device_type == 'tv' ? $tvshow->poster_tv_url : $tvshow->poster_url ?? null;
                       $tvshow->trailer_url =  $tvshow->trailer_url_type == 'Local' ? setBaseUrlWithFileName($tvshow->trailer_url, 'video', $tvshow->type) : $tvshow->trailer_url;
                       $tvshow->access = $tvshow->movie_access;
                       $tvshow = setContentAccess($tvshow, null, null, []);
                   });
               }
              $popular_tvshow = TvshowResourceV3::collection($popular_tvshow)->toArray(request());
           } else {
               $popular_tvshow = [];
           }

           // OPTIMIZATION: Use batched MobileSetting values
           $setting_genreIds = $mobileSettings['genre'] ?? null;
            $genreIds = ($setting_genreIds && empty($setting_genreIds['type'])) ? $setting_genreIds['value'] : null;
           $genreIdsArray = !empty($genreIds) ? json_decode($genreIds, true) : null;
           $genres = !empty($genreIdsArray) ? GenresResource::collection(
            Genres::whereIn('id', $genreIdsArray)
              ->where('status', 1)
              ->where('deleted_at',null)
              ->select('id', 'name', 'file_url')
              ->get()
                )->map(function ($genre) {
                    return [
                        'id'           => $genre['id'],
                        'name'         => $genre['name'],
                        'poster_image' =>!empty($genre['file_url']) ? setBaseUrlWithFileName($genre['file_url'],'image','genres') : null,
                    ];
                })
                : collect();

            // OPTIMIZATION: Use batched MobileSetting values
            $setting_videoIds = $mobileSettings['popular-videos'] ?? null;
            $videoIds = ($setting_videoIds && empty($setting_videoIds['type'])) ? $setting_videoIds['value'] : null;
            $videoIdsArray = !empty($videoIds) ? json_decode($videoIds, true) : null;
            // OPTIMIZATION: Only process if IDs exist and limit array size
            if (!empty($videoIdsArray) && is_array($videoIdsArray)) {
                // OPTIMIZATION: Limit to first 100 IDs to prevent huge IN() queries
                $videoIdsArray = array_slice($videoIdsArray, 0, 100);
                $popular_videos = Video::get_popular_videos($videoIdsArray);
                // OPTIMIZATION: Combine loops to avoid multiple iterations
                if($user_id){
                    $popular_videos->each(function ($video) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                        $video->user_id = $user_id;
                        $video->type = 'video';
                        $video->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                        $video = setContentAccess($video, $user_id, $userPlanId, $purchasedIds ?? []);
                        $video->poster_image =  $device_type == 'tv' ? $video->poster_tv_url : $video->poster_url ?? null;
                    });
                }else{
                    $popular_videos->each(function ($video) use ($device_type) {
                        $video->type = 'video';
                        $video->isDeviceSupported = 0;
                        $video->poster_image =  $device_type == 'tv' ? $video->poster_tv_url : $video->poster_url ?? null;
                        $video->access = $video->access;
                        $video = setContentAccess($video, null, null, []);
                    });
                }
                $popular_videos = VideoResourceV3::collection($popular_videos)->toArray(request());
            } else {
                $popular_videos = [];
            }

            // OPTIMIZATION: Simplified query without expensive subqueries for faster performance
            // Remove watched_time and watchlist subqueries which are slow on large tables
            $latestMoviesData = Entertainment::with([
                'genresdata:id,name',
                'plan:id,level'
            ])
            ->select([
                'id', 'name', 'slug', 'type', 'release_date', 'trailer_url_type',
                'is_restricted', 'imdb_rating', 'poster_url', 'thumbnail_url',
                'poster_tv_url', 'trailer_url', 'video_url_input', 'movie_access', 'plan_id'
            ])
            ->where('status', 1)
            ->where('deleted_at', null)
            ->where('type', 'movie')
            ->whereDate('release_date', '<=', now())
            ->when(request()->has('is_restricted'), function ($q) {
                $q->where('is_restricted', request()->is_restricted);
            })
            ->when(!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0, function ($q) {
                $q->where('is_restricted', 0);
            })
            ->latest('id');

            $latestMovies = $latestMoviesData->take(20) // OPTIMIZATION: Limit to 20 records for dashboard
            ->get();
            
            // OPTIMIZATION: Batch load watched_time and watchlist data separately if user_id exists (much faster)
            if ($user_id && $latestMovies->isNotEmpty()) {
                $entertainmentIds = $latestMovies->pluck('id')->toArray();
                
                // Batch fetch watched times
                $watchedTimes = \Modules\Entertainment\Models\ContinueWatch::whereIn('entertainment_id', $entertainmentIds)
                    ->where('user_id', $user_id)
                    ->where('profile_id', $profile_id)
                    ->pluck('watched_time', 'entertainment_id')
                    ->toArray();
                
                // Batch fetch watchlist with type
                $watchlistIds = \Modules\Entertainment\Models\Watchlist::whereIn('entertainment_id', $entertainmentIds)
                    ->where('user_id', $user_id)
                    ->where('profile_id', $profile_id)
                    ->get(['entertainment_id', 'type'])
                    ->map(function($w) {
                        $type = ($w->type == 'tv_show' || $w->type == 'tvshow') ? 'tvshow' : $w->type;
                        return $type . '_' . $w->entertainment_id;
                    })
                    ->toArray();
                
                // Attach data to models
                $latestMovies->each(function ($movie) use ($watchedTimes, $watchlistIds) {
                    $movie->watched_time = $watchedTimes[$movie->id] ?? null;
                    $movie->is_watch_list = in_array('movie_' . $movie->id, $watchlistIds) ? 1 : 0;
                });
            }
            
            if($user_id){
                $latestMovies->each(function ($latest) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                    $latest->user_id = $user_id;
                    $latest->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $latest->required_plan_level = $userPlanId >= ($latest->plan_id ?? 0) ? 1 : 0;
                    $latest->trailer_url =  $latest->trailer_url_type == 'Local' ? setBaseUrlWithFileName($latest->trailer_url, 'video', $latest->type) : $latest->trailer_url;
                    $latest->poster_image =  $device_type == 'tv' ? $latest->poster_tv_url : $latest->poster_url ?? null;
                    $latest->access = $latest->movie_access;
                    $latest = setContentAccess($latest, $user_id, $userPlanId, $purchasedIds ?? []);
                });
            }else{
                $latestMovies->each(function ($latest) use ($device_type) {
                    $latest->isDeviceSupported = 0;
                    $latest->trailer_url =  $latest->trailer_url_type == 'Local' ? setBaseUrlWithFileName($latest->trailer_url, 'video', $latest->type) : $latest->trailer_url;
                    $latest->poster_image =  $device_type == 'tv' ? $latest->poster_tv_url : $latest->poster_url ?? null;
                    $latest->access = $latest->movie_access;
                    $latest = setContentAccess($latest, null, null, []);
                });
            }
            $latestMovies = MoviesResourceV3::collection($latestMovies)->toArray(request());

           
            // OPTIMIZATION: Skip PayPerView if user_id is null (returns empty anyway) and limit results
            $payPerViewContent = [];
            if ($user_id) {
                $payPerViewRequest = new Request(['user_id' => $user_id, 'per_page' => 20]); // Limit to 20 items
                $payPerViewContent = $this->getPayPerViewUnlockedContentV3($payPerViewRequest);
            }
            // Define slugs and their default names
            $slugsWithDefaults = [
                'enjoy-in-your-native-tongue' => 'Popular Language',
                'popular-movies' => 'Popular Movies',
                'top-channels' => 'Top Channels',
                'your-favorite-personality' => 'Popular Personalities',
                '500-free-movies' => 'Free Movies',
                'popular-tvshows' => 'Popular TV Show',
                'genre' => 'Genres',
                'popular-videos' => 'Popular Videos',
            ];

            // OPTIMIZATION: Fetch section names in one query (already have mobileSettings, but need names)
            $settings = MobileSetting::whereIn('slug', array_keys($slugsWithDefaults))->pluck('name', 'slug');
             
            // Resolve names with fallback to default
            $sectionNames = [];
            foreach ($slugsWithDefaults as $slug => $default) {
                $sectionNames[$slug] = $settings[$slug] ?? $default;
            }
            // OPTIMIZATION: getOtherSectionData is already optimized with limits
            $otherSectionData = $this->getOtherSectionData($request);
           $likedMovies = collect();
           $viewedMovies = collect();
           $Lastwatchrecommendation = collect();
           // OPTIMIZATION: Skip expensive recommendation calls if user doesn't exist or limit results
           if ($user_id && isset($user)) {
               // OPTIMIZATION: Limit recommendation results to prevent slow queries
               try {
                   $likedMovies = $this->recommendationService->getLikedMoviesV3($user, $profile_id);
                   if ($likedMovies->isNotEmpty()) {
                       // OPTIMIZATION: Limit to first 20 items
                       $likedMovies = $likedMovies->take(20);
                       $likedMovies->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type, $purchasedIds) {
                           $movie->user_id = $user_id;
                           $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                           $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                           $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                           $movie->access = $movie->movie_access;
                           $movie = setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);
                       });
                       $likedMovies = CommanResourceV3::collection($likedMovies);
                   } else {
                       $likedMovies = [];
                   }
               } catch (\Exception $e) {
                   $likedMovies = [];
               }

               try {
                   $viewedMovies = $this->recommendationService->getEntertainmentViewsV3($user, $profile_id);
                   if ($viewedMovies->isNotEmpty()) {
                       // OPTIMIZATION: Limit to first 20 items
                       $viewedMovies = $viewedMovies->take(20);
                       $viewedMovies->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type, $purchasedIds) {
                           $movie->access = $movie->movie_access;
                           $movie->user_id = $user_id;
                           $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                           $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                           $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                           $movie = setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);
                       });
                       $viewedMovies = CommanResourceV3::collection($viewedMovies);
                   } else {
                       $viewedMovies = [];
                   }
               } catch (\Exception $e) {
                   $viewedMovies = [];
               }

               try {
                   $based_on_last_watch = collect($this->recommendationService->recommendByLastHistoryV3($user, $profile_id));
                   if ($based_on_last_watch->isNotEmpty()) {
                       // OPTIMIZATION: Limit to first 20 items
                       $based_on_last_watch = $based_on_last_watch->take(20);
                       $based_on_last_watch->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type, $purchasedIds) {
                           $movie->user_id = $user_id;
                           $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                           $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                           $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                           $movie->access = $movie->movie_access;
                           $movie = setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);
                       });
                       $Lastwatchrecommendation = MoviesResourceV3::collection($based_on_last_watch);
                   } else {
                       $Lastwatchrecommendation = [];
                   }
               } catch (\Exception $e) {
                   $Lastwatchrecommendation = [];
               }
           }

                       $responseData = [
                'based_on_last_watch' => $Lastwatchrecommendation ?? [],
                'based_on_likes' => $likedMovies ?? [],
                'based_on_views' => $viewedMovies ?? [],
                'trending_movies' => $trendingMovies ?? [],
                'favorite_genres' => $FavoriteGener ?? [],
                'favorite_personality' => $favorite_personality ?? [],

                'top_channel' => [
                    'name' => $sectionNames['top-channels'],
                    'data' => $top_channel,
                ],
                'popular_tvshow' => [
                    'name' => $sectionNames['popular-tvshows'],
                    'data' => $popular_tvshow,
                ],
                'personality' => [
                    'name' => $sectionNames['your-favorite-personality'],
                    'data' => $personality,
                ],
                'free_movie' => [
                    'name' => $sectionNames['500-free-movies'],
                    'data' => $free_movie,
                ],
                'genres' => [
                    'name' => $sectionNames['genre'],
                    'data' => $genres,
                ],
                'popular_videos' => [
                    'name' => $sectionNames['popular-videos'],
                    'data' => $popular_videos,
                ],

                'trending_in_country' => $trendingMovies ?? [],
                'other_section' => $otherSectionData,
            ];

            return $responseData;
        });

        return ApiResponse::success($cachedResult['data'], __('messages.dashboard_detail'), 200);
    }

    public function getOtherSectionData(Request $request)
    {
        $user_id = $request->user_id ?? null;
        $device_type = getDeviceType($request);

        $userPlanId = 0;
        $deviceTypeResponse = ['isDeviceSupported' => false];
        $purchasedIds = [];

        if ($user_id) {
            // OPTIMIZATION: Cache device support check
            $deviceTypeResponse = cache()->remember(
                "device_support_{$user_id}_{$device_type}",
                600,
                function() use ($user_id, $device_type) {
                    $getDeviceTypeData = Subscription::checkPlanSupportDevice($user_id, $device_type);
                    return json_decode($getDeviceTypeData->getContent(), true);
                }
            );
            $userLevel = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();
            $userPlanId = $userLevel->plan_id ?? 0;
            
            // OPTIMIZATION: Bulk fetch PayPerView purchases
            $purchasedIds = PayPerView::where('user_id', $user_id)
                ->pluck('movie_id')
                ->toArray();
        }

        $explicit_slugs = [
            'top-10',
            'latest-movies',
            'popular-movies',
            'popular-tvshows',
            'popular-videos',
            'top-channels',
            'genre',
            '500-free-movies',
            'your-favorite-personality',
            'enjoy-in-your-native-tongue',
            'banner',
            'continue-watching'
        ];

        // OPTIMIZATION: Select only needed columns and limit sections
        $sections = MobileSetting::select('id', 'slug', 'type', 'value', 'name')
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->take(20) // OPTIMIZATION: Limit to 20 sections max
            ->get();

        // OPTIMIZATION: Early return if no sections
        if ($sections->isEmpty()) {
            return [];
        }

        $response = [];

        foreach ($sections as $section) {
            $ids = json_decode($section->value, true);
            if (empty($ids) || !is_array($ids)) {
                continue;
            }
            
            // OPTIMIZATION: Limit IDs array to prevent huge IN() queries on 500k table
            $ids = array_slice($ids, 0, 50); // OPTIMIZATION: Reduced from 100 to 50 for other sections

            $data = collect();
            switch ($section->type) {
                case 'movie':
                    // OPTIMIZATION: Add eager loading to prevent N+1 queries
                    $data = Entertainment::with([
                        'plan:id,level',
                        'genresdata:id,name'
                    ])
                    ->whereIn('id', $ids)
                        ->select(['id', 'name', 'slug', 'type', 'video_upload_type', 'release_date', 'trailer_url', 'trailer_url_type', 'is_restricted', 'imdb_rating', 'poster_url', 'thumbnail_url', 'poster_tv_url', 'trailer_url as base_url', 'trailer_url', 'video_url_input', 'movie_access', 'plan_id'])
                        ->where('type', 'movie')
                        ->released()
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->get();

                    $data->each(function ($movie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                        $movie->user_id = $user_id;
                        $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                        $movie->poster_image = $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                        $movie->trailer_url = $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                        $movie->access = $movie->movie_access;
                        setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);
                    });

                    $data = MoviesResourceV3::collection($data)->toArray(request());
                    break;

                case 'tvshow':
                    // OPTIMIZATION: Add eager loading to prevent N+1 queries
                    $data = Entertainment::with([
                        'plan:id,level',
                        'genresdata:id,name'
                    ])
                    ->whereIn('id', $ids)
                    ->select(['id', 'name', 'slug', 'type', 'video_upload_type', 'release_date', 'trailer_url', 'trailer_url_type', 'is_restricted', 'imdb_rating', 'poster_url', 'thumbnail_url', 'poster_tv_url', 'trailer_url as base_url', 'trailer_url', 'video_url_input', 'movie_access', 'plan_id'])
                        ->where('type', 'tvshow')
                        ->released()
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->get();

                    if ($user_id) {
                        $data->each(function ($tvshow) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                            $tvshow->user_id = $user_id;
                            $tvshow->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                            $tvshow->poster_image = $device_type == 'tv' ? $tvshow->poster_tv_url : $tvshow->poster_url ?? null;
                            $tvshow->trailer_url = $tvshow->trailer_url_type == 'Local' ? setBaseUrlWithFileName($tvshow->trailer_url, 'video', $tvshow->type) : $tvshow->trailer_url;
                            $tvshow->access = $tvshow->movie_access;
                            setContentAccess($tvshow, $user_id, $userPlanId, $purchasedIds ?? []);
                        });
                    } else {
                        $data->each(function ($tvshow) use ($device_type) {
                            $tvshow->isDeviceSupported = 0;
                            $tvshow->poster_image = $device_type == 'tv' ? $tvshow->poster_tv_url : $tvshow->poster_url ?? null;
                            $tvshow->trailer_url = $tvshow->trailer_url_type == 'Local' ? setBaseUrlWithFileName($tvshow->trailer_url, 'video', $tvshow->type) : $tvshow->trailer_url;
                            $tvshow->access = $tvshow->movie_access;
                            setContentAccess($tvshow, null, null, []);
                        });
                    }

                    $data = TvshowResourceV3::collection($data)->toArray(request());
                    break;

                case 'video':
                    // OPTIMIZATION: Add eager loading to prevent N+1 queries
                    $data = Video::with([
                        'plan:id,level'
                    ])
                    ->whereIn('id', $ids)
                    ->select(['id', 'name', 'slug', 'type', 'video_upload_type', 'release_date', 'trailer_url', 'trailer_url_type', 'is_restricted', 'IMDb_rating', 'poster_url', 'thumbnail_url', 'poster_tv_url', 'trailer_url as base_url', 'trailer_url', 'video_url_input', 'access', 'plan_id'])
                        ->whereDate('release_date', '<=', now())
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->get();

                    if ($user_id) {
                        $data->each(function ($video) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                            $video->user_id = $user_id;
                            $video->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                            setContentAccess($video, $user_id, $userPlanId, $purchasedIds ?? []);
                            $video->poster_image = $device_type == 'tv' ? $video->poster_tv_url : $video->poster_url ?? null;
                        });
                    } else {
                        $data->each(function ($video) use ($device_type) {
                            $video->isDeviceSupported = 0;
                            $video->poster_image = $device_type == 'tv' ? $video->poster_tv_url : $video->poster_url ?? null;
                            $video->access = $video->access;
                            setContentAccess($video, null, null, []);
                        });
                    }

                    $data = VideoResourceV3::collection($data)->toArray(request());
                    break;

                case 'channel':
                    // OPTIMIZATION: Add eager loading to prevent N+1 queries
                    $data = LiveTvChannel::with([
                        'plan:id,level',
                        'TvCategory:id,name'
                    ])
                    ->whereIn('id', $ids)
                    ->select(['id', 'name', 'slug', 'plan_id', 'poster_url', 'thumb_url', 'poster_tv_url', 'trailer_url', 'access', 'category_id'])
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->get();

                    $data->each(function ($channel) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                        $channel->user_id = $user_id;
                        $channel->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                        $channel->poster_image = $device_type == 'tv'
                            ? setBaseUrlWithFileName($channel->poster_tv_url, 'image', 'livetv')
                            : setBaseUrlWithFileName($channel->poster_url, 'image', 'livetv');
                        $channel->access = $channel->access;
                        setContentAccess($channel, $user_id, $userPlanId, $purchasedIds ?? []);
                    });

                    $data = LiveTvChannelResourceV3::collection($data)->toArray(request());
                    break;

                default:
                    continue 2;
            }

            if (!empty($data)) {
                $response[] = [
                    'slug' => $section->slug,
                    'name' => $section->name,
                    'type' => $section->type,
                    'data' => $data,
                ];
            }
        }

        return $response;
    }


    public function DashboardDetailV3(Request $request)
    {
        $device_type = getDeviceType($request);
        if (!Cache::has('genres')) {
            $genresData = Genres::where('status', 1)
                ->whereNull('deleted_at')
                ->get(['id','name'])
                ->keyBy('id')
                ->toArray();
            Cache::put('genres', $genresData);
        }

        $user_id = !empty($request->user_id) ? $request->user_id : null;
        $profile_id = $request->profile_id ?? null;

        // OPTIMIZATION: Simple cache key without expensive queries - move MobileSetting queries inside callback
        $baseCacheKey = 'dashboard_detail_v3_'.md5(json_encode([
            'user_id' => $user_id,
            'profile_id' => $profile_id,
            'device_type' => $device_type
        ]));
        
        // Check cache version to invalidate cache when movies are updated/deleted
        $cacheVersionKey = 'dashboard_cache_version';
        $cacheVersion = Cache::get($cacheVersionKey, 0);
        $cacheKey = $baseCacheKey . '_v' . $cacheVersion;
        $cachedResult = cacheApiResponse($cacheKey, 300, function () use ($request, $user_id, $profile_id, $device_type) {
            $continueWatch = [];
            $deviceTypeResponse = ['isDeviceSupported' => false];
            $userPlanId = 0;
            $purchasedIds = []; // Initialize for non-user case

            if($request->has('user_id')){

                $user = User::where('id',$user_id)->first();
                
                // OPTIMIZATION: Cache device support check
                $deviceTypeResponse = cache()->remember(
                    "device_support_{$user_id}_{$device_type}",
                    600,
                    function() use ($user_id, $device_type) {
                        $getDeviceTypeData = Subscription::checkPlanSupportDevice($user_id, $device_type);
                        return json_decode($getDeviceTypeData->getContent(), true);
                    }
                );
                
                $userLevel = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();
                $userPlanId = $userLevel->plan_id ?? 0;
                
                // OPTIMIZATION: Bulk fetch PayPerView purchases for setContentAccess
                $purchasedIds = PayPerView::where('user_id', $user_id)
                    ->pluck('movie_id')
                    ->toArray();

                $isContinueWatchEnabled = MobileSetting::getCacheValueBySlug('continue-watching') == 1;

                if ($isContinueWatchEnabled) {
                    // OPTIMIZATION: Optimize ContinueWatch query - filter by user/profile first, then check relationships, limit results
                    $continueWatchList = ContinueWatch::where('user_id', $user_id)
                        ->select('id', 'user_id', 'profile_id', 'entertainment_id', 'entertainment_type', 'episode_id', 'watched_time', 'total_watched_time')
                        ->where('profile_id', $profile_id)
                        ->whereNotNull('watched_time')
                        ->whereNotNull('total_watched_time')
                        ->with(['entertainment', 'episode.seasondata', 'video'])
                        ->orderBy('updated_at', 'desc')
                        ->take(20) // OPTIMIZATION: Limit to 20 records for dashboard
                        ->get()
                        ->filter(function ($item) {
                            // Filter in memory after eager loading (faster than whereHas)
                            if ($item->entertainment_type == 'movie' || $item->entertainment_type == 'tvshow') {
                                return $item->entertainment && 
                                    $item->entertainment->status == 1 && 
                                    $item->entertainment->deleted_at === null;
                            }
                            if ($item->entertainment_type == 'tvshow' && $item->episode_id) {
                                return $item->episode && 
                                    $item->episode->status == 1 && 
                                    $item->episode->deleted_at === null;
                            }
                            if ($item->entertainment_type == 'video') {
                                return $item->video && 
                                    $item->video->status == 1 && 
                                    $item->video->deleted_at === null;
                            }
                            return false;
                        })
                        ->values();

                    $continueWatchList->each(function ($continueWatchItem){
                        // If it's a TV show with an episode, use episode's poster image instead of TV show's
                        if ($continueWatchItem->entertainment_type == 'tvshow' && $continueWatchItem->episode) {
                            // Use episode's poster image
                            $continueWatchItem->thumbnail_url = $continueWatchItem->episode->poster_url ?? null;
                            $continueWatchItem->trailer_url_type = $continueWatchItem->episode->trailer_url_type ?? $continueWatchItem->entertainment->trailer_url_type ?? null;
                            $continueWatchItem->trailer_url = $continueWatchItem->episode->trailer_url_type == 'Local' 
                                ? setBaseUrlWithFileName($continueWatchItem->episode->trailer_url, 'video', 'episode') 
                                : ($continueWatchItem->episode->trailer_url ?? ($continueWatchItem->entertainment->trailer_url_type == 'Local' 
                                    ? setBaseUrlWithFileName($continueWatchItem->entertainment->trailer_url, 'video', $continueWatchItem->entertainment->type) 
                                    : $continueWatchItem->entertainment->trailer_url));
                            
                            if ($continueWatchItem->episode->seasondata) {
                                $continueWatchItem->tv_show_data = [
                                    'id' => $continueWatchItem->episode->seasondata->entertainment_id ?? $continueWatchItem->episode->seasondata->id,
                                    'episode_name' => $continueWatchItem->episode->name,
                                    'season_name' => $continueWatchItem->episode->seasondata->name,
                                    'season_id' => $continueWatchItem->episode->seasondata->id,
                                ];
                            } else {
                                $continueWatchItem->tv_show_data = null;
                            }
                        } else {
                            // For movies, videos, or TV shows without episodes, use entertainment poster
                            $continueWatchItem->thumbnail_url = $continueWatchItem->entertainment->thumbnail_url ?? $continueWatchItem->entertainment->poster_url ?? null;
                            $continueWatchItem->trailer_url_type = $continueWatchItem->entertainment->trailer_url_type ?? null;
                            $continueWatchItem->trailer_url =  $continueWatchItem->entertainment->trailer_url_type == 'Local' ? setBaseUrlWithFileName($continueWatchItem->entertainment->trailer_url, 'video', $continueWatchItem->entertainment->type) : $continueWatchItem->entertainment->trailer_url;
                            $continueWatchItem->tv_show_data = null;
                        }
                    });
                    $continueWatch = ContinueWatchResourceV3::collection($continueWatchList);
                } else {
                    $continueWatch = [];
                }


                // $likedMovies = $this->recommendationService->getLikedMovies($user, $profile_id);

                // $likedMovies->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type, $purchasedIds) {
                //     $movie->user_id = $user_id;
                //     $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                //     $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                //     $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                //     $movie->access = $movie->movie_access;
                //     $movie = setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);
                // });
                // $likedMovies = CommanResourceV3::collection($likedMovies);
                // $viewedMovies = $this->recommendationService->getEntertainmentViews($user, $profile_id);
                // $viewedMovies->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type, $purchasedIds) {
                //     $movie->access = $movie->movie_access;
                //     $movie->user_id = $user_id;
                //     $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                //     $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                //     $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                //     $movie = setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);

                // });
                // $viewedMovies = CommanResourceV3::collection($viewedMovies);

                // $based_on_last_watch = collect($this->recommendationService->recommendByLastHistory($user,$profile_id));
                // $based_on_last_watch->each(function ($movie) use ($user_id, $deviceTypeResponse, $userPlanId, $device_type, $purchasedIds) {
                //     $movie->user_id = $user_id;
                //     $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                //     $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                //     $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                //     $movie->access = $movie->movie_access;
                //     $movie = setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);

                // });
                // $Lastwatchrecommendation = MoviesResourceV3::collection($based_on_last_watch );

            }

            $setting_latestMovieIds = MobileSetting::getNameAndValueBySlug('latest-movies');
            $latestMovieIds = ($setting_latestMovieIds && empty($setting_latestMovieIds['type'])) ? $setting_latestMovieIds['value'] : null;
            $latestMovieIdsArray = json_decode($latestMovieIds, true);

            // OPTIMIZATION: Only process if IDs exist and limit array size to prevent large IN queries
            if (!empty($latestMovieIdsArray) && is_array($latestMovieIdsArray)) {
                // OPTIMIZATION: Limit to first 100 IDs to prevent huge IN() queries on 500k table
                $latestMovieIdsArray = array_slice($latestMovieIdsArray, 0, 100);
                $latest_movie = Entertainment::get_latest_movieV3($latestMovieIdsArray);
            } else {
                $latest_movie = collect();
            }
            if ($latest_movie->isNotEmpty()) {
                if($request->has('user_id')){
                     $latest_movie->each(function ($movie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                     $movie->user_id = $user_id;
                     $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                     $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                     $movie->access = $movie->movie_access;
                     $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                     $movie = setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);
                 });
                }else{
                    $latest_movie->each(function ($movie) use ($device_type) {
                        $movie->isDeviceSupported = 0;
                        $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                        $movie->access = $movie->movie_access;
                        $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                        $movie = setContentAccess($movie, null, null, []);
                    });
                }
                $latest_movie = MoviesResourceV3::collection($latest_movie)->toArray(request());
            } else {
                $latest_movie = [];
            }

            $setting_isBanner = MobileSetting::getNameAndValueBySlug('banner');
            $isBanner = ($setting_isBanner && empty($setting_isBanner['type'])) ? $setting_isBanner['value'] : null;
            $sliderList = $isBanner
            ? Banner::select('id', 'banner_for', 'type', 'type_id', 'poster_url', 'poster_tv_url', 'title', 'description')
                ->where('banner_for','home')
                ->where('status', 1)
                ->where('deleted_at', null)
                ->get()
            : collect();

            // OPTIMIZATION: Cache module enable checks (avoid repeated DB queries)
            $moduleCache = cache()->remember('module_enable_status', 600, function() {
                return [
                    'movie' => isenablemodule('movie'),
                    'tvshow' => isenablemodule('tvshow'),
                    'video' => isenablemodule('video'),
                    'livetv' => isenablemodule('livetv'),
                ];
            });

            $sliderList = $sliderList->filter(function($banner) use ($moduleCache) {
                $bannerType = $banner->type;
                if ($bannerType == 'movie') {
                    return $moduleCache['movie'] == 1;
                } elseif ($bannerType == 'tvshow' || $bannerType == 'tv_show') {
                    return $moduleCache['tvshow'] == 1;
                } elseif ($bannerType == 'video') {
                    return $moduleCache['video'] == 1;
                } elseif ($bannerType == 'livetv') {
                    return $moduleCache['livetv'] == 1;
                }
                return true;
            });

            SliderResource::collection(
                $sliderList->map(fn($slider) => new SliderResource($slider, $user_id))
           );

            $setting_topMovieIds = MobileSetting::getNameAndValueBySlug('top-10');
            $topMovieIds = ($setting_topMovieIds && empty($setting_topMovieIds['type'])) ? $setting_topMovieIds['value'] : null;
            $topMovieIdsArray = json_decode($topMovieIds, true);
            
            // OPTIMIZATION: Only process if IDs exist and limit array size
            if (!empty($topMovieIdsArray) && is_array($topMovieIdsArray)) {
                // OPTIMIZATION: Limit to first 100 IDs to prevent huge IN() queries
                $topMovieIdsArray = array_slice($topMovieIdsArray, 0, 100);
                $top_10 = Entertainment::get_top_movie_v3($topMovieIdsArray);
             
                 if($request->has('user_id')){
                    $top_10->each(function ($top10) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {

                    $top10->user_id = $user_id;
                    $top10->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;

                    $top10->poster_image =  $device_type == 'tv' ? $top10->poster_tv_url : $top10->poster_url ?? null;
                    $top10->access = $top10->movie_access;
                    $top10->trailer_url =  $top10->trailer_url_type == 'Local' ? setBaseUrlWithFileName($top10->trailer_url, 'video', $top10->type) : $top10->trailer_url;
                    $top10 = setContentAccess($top10, $user_id, $userPlanId, $purchasedIds ?? []);
                });
            }else{
                    $top_10->each(function ($top10) use ($device_type) {
                    $top10->poster_image =  $device_type == 'tv' ? $top10->poster_tv_url : $top10->poster_url ?? null;
                    $top10->trailer_url =  $top10->trailer_url_type == 'Local' ? setBaseUrlWithFileName($top10->trailer_url, 'video', $top10->type) : $top10->trailer_url;
                    $top10->access = $top10->movie_access;
                    $top10 = setContentAccess($top10, null, null, []);
                });
            }
                $top_10 = MoviesResourceV3::collection($top_10)->toArray(request());
                // Ensure rating is available at root for app cards
                $top_10 = collect($top_10)->map(function ($item) {
                    $item['imdb_rating'] = $item['imdb_rating'] ?? data_get($item, 'details.imdb_rating');
                    return $item;
                })->values()->toArray();
            } else {
                $top_10 = [];
            }

            $setting_languageIds = MobileSetting::getNameAndValueBySlug('enjoy-in-your-native-tongue');
            $languageIds = ($setting_languageIds && empty($setting_languageIds['type'])) ? $setting_languageIds['value'] : null;
            $languageIdsArray = json_decode($languageIds, true);
            $popular_language = !empty($languageIdsArray) ? Constant::whereIn('id', $languageIdsArray)->where('status', 1)->where('deleted_at', null)->select('id', 'name','language_image')->get()->makeHidden(['feature_image', 'media', 'status', 'created_at', 'updated_at', 'deleted_at']) : collect();

            // OPTIMIZATION: Only process if collection is not empty
            if ($popular_language->isNotEmpty()) {
                $popular_language->each(function ($language) {
                   $language->language_image = setBaseUrlWithFileName($language->language_image,'image','constant');
                });
            }

            $setting_popularMovieIds = MobileSetting::getNameAndValueBySlug('popular-movies');
            $popularMovieIds = ($setting_popularMovieIds && empty($setting_popularMovieIds['type'])) ? $setting_popularMovieIds['value'] : null;
            $popularMovieIdsArray = json_decode($popularMovieIds, true);
            
            // OPTIMIZATION: Only process if IDs exist and limit array size
            if (!empty($popularMovieIdsArray) && is_array($popularMovieIdsArray)) {
                // OPTIMIZATION: Limit to first 100 IDs to prevent huge IN() queries on 500k table
                $popularMovieIdsArray = array_slice($popularMovieIdsArray, 0, 100);
                $popular_movie = Entertainment::get_popular_movieV3($popularMovieIdsArray);

                $popular_movie->each(function ($movie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                    $movie->user_id = $user_id;
                    $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $movie->poster_image =  $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
                    $movie->trailer_url =  $movie->trailer_url_type == 'Local' ? setBaseUrlWithFileName($movie->trailer_url, 'video', $movie->type) : $movie->trailer_url;
                    $movie->access = $movie->movie_access;
                    $movie = setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);
                });
                $popular_movie = MoviesResourceV3::collection($popular_movie)->toArray(request());
            } else {
                $popular_movie = [];
            }

            // Show PayPerView even when user_id is null
            $payPerViewRequest = new Request(['user_id' => $user_id, 'per_page' => 20]); // Limit to 20 items
            $payPerViewContent = $this->getPayPerViewUnlockedContentV3($payPerViewRequest);

            $today = Carbon::now()->toDateString();
            // $is_advertisement_enabled = MobileSetting::where('slug', 'advertisement')->first();
            $customAds = CustomAdsSetting::
                        where('status', 1)
                        ->where('placement', 'home_page')
                        ->whereDate('start_date', '<=', $today)
                        ->whereDate('end_date', '>=', $today)
                        ->get(['type','media','redirect_url'])->map(function($ad) {
                            return [
                                'type' => $ad->type,
                                'url' => $ad->media ? setBaseUrlWithFileName($ad->media,$ad->type,'ads') : null,
                                'redirect_url' => $ad->redirect_url,
                            ];
                        });

            $slugsWithDefaultsAdditional = [
                'enjoy-in-your-native-tongue' => 'Popular Language',
                'popular-movies' => 'Popular Movies',
            ];
            $settingsAdditional = MobileSetting::whereIn('slug', array_keys($slugsWithDefaultsAdditional))->pluck('name', 'slug');
            $sectionNamesAdditional = [];
            foreach ($slugsWithDefaultsAdditional as $slug => $default) {
                $sectionNamesAdditional[$slug] = $settingsAdditional[$slug] ?? $default;
            }

                        $slugsWithDefaults = [
                            'latest-movies' => 'Latest Movies',
                            'top-10' => 'Top 10',
                        ];

                        $settings = MobileSetting::whereIn('slug', array_keys($slugsWithDefaults))->pluck('name', 'slug');

                        $sectionNames = [];
                        foreach ($slugsWithDefaults as $slug => $default) {
                            $sectionNames[$slug] = $settings[$slug] ?? $default;
                        }

                    return [
                        'continue_watch' => $continueWatch,
                        'top_10' => [
                            'name' => $sectionNames['top-10'],
                            'data' => $top_10,
                        ],
                        'latest_movie' => [
                                'name' => $sectionNames['latest-movies'],
                                'data' => $latest_movie,
                            ],
                        'popular_language' => [
                            'name' => $sectionNamesAdditional['enjoy-in-your-native-tongue'] ?? 'Popular Language',
                            'data' => $popular_language,
                        ],
                        'custom_ads' => $customAds,
                        'popular_movie' => [
                            'name' => $sectionNamesAdditional['popular-movies'] ?? 'Popular Movies',
                            'data' => $popular_movie,
                        ],
                        'pay_per_view' => $payPerViewContent,
                    ];
        });

        $responseData = $cachedResult['data'];
       return ApiResponse::success($responseData, __('messages.dashboard_detail'), 200);
    }
    public function getPayPerViewUnlockedContent(Request $request)
    {
        $payPerViewContent = [];
        $user_id = $request->query('user_id');


        // Movies
       $movies = MoviesResource::collection(
          Entertainment::where('movie_access', 'pay-per-view')
              ->where('type', 'movie')
              ->where('status', 1)
              ->where('deleted_at', null)
              ->when(request()->has('is_restricted'), function ($query) {
                  $query->where('is_restricted', request()->is_restricted);
              })
              ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                  $query->where('is_restricted', 0);
              })
              ->get()
      )->map(function ($item) use ($user_id) {
          $item->user_id = $user_id;
          return $item;
      })->toArray(request());

      $payPerViewContent = array_merge($payPerViewContent, $movies);

        // TV Shows
        $tvshows = TvshowResource::collection(
            Entertainment::where('movie_access', 'pay-per-view')
                ->where('type', 'tvshow')
                ->where('status', 1)
                ->where('deleted_at', null)
                ->get()
        )->map(function ($item) use ($user_id) {
            $item->user_id = $user_id;
            return $item;
        })->toArray(request());
        $payPerViewContent = array_merge($payPerViewContent, $tvshows);

        // Videos
        $videos = VideoResource::collection(
            Video::where('access', 'pay-per-view')
                ->where('status', 1)
                ->where('deleted_at', null)
                  ->when(request()->has('is_restricted'), function ($query) {
                  $query->where('is_restricted', request()->is_restricted);
              })
              ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                  $query->where('is_restricted', 0);
              })
                ->get()
        )->map(function ($item) use ($user_id) {
            $item->user_id = $user_id;
            return $item;
        })->toArray(request());
        $payPerViewContent = array_merge($payPerViewContent, $videos);

        // Seasons
        $seasons = SeasonResource::collection(
            Season::where('access', 'pay-per-view')
                ->where('status', 1)
                ->where('deleted_at', null)
                ->get()
        )->map(function ($item) use ($user_id) {
            $item->user_id = $user_id;
            return $item;
        })->toArray(request());
        $payPerViewContent = array_merge($payPerViewContent, $seasons);

        // Episodes
        $episodes = EpisodeResource::collection(
            Episode::where('access', 'pay-per-view')
                ->where('status', 1)
                ->where('deleted_at', null)
                  ->when(request()->has('is_restricted'), function ($query) {
                  $query->where('is_restricted', request()->is_restricted);
              })
              ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                  $query->where('is_restricted', 0);
              })
                ->get()
        )->map(function ($item) use ($user_id) {
            $item->user_id = $user_id;
            return $item;
        })->toArray(request());
        $payPerViewContent = array_merge($payPerViewContent, $episodes);

        if ($request->is('api/*')) {
            return ApiResponse::success($payPerViewContent, null, 200);
        }

        return $payPerViewContent;
    }

    public function getPayPerViewUnlockedContentV3(Request $request)
    {
        $device_type = getDeviceType($request);
        $user_id = $request->query('user_id');
        $page = $request->query('page', 1);
        $per_page = $request->query('per_page', null);
        $is_restricted = $request->query('is_restricted');
        $type = $request->query('type', 'all'); // Get type filter, default to 'all'

        if($per_page == null){
            $per_page = 0;
        }
        // Create unique cache key based on request parameters
        $baseCacheKey = 'pay_per_view_content_v3_' .md5(json_encode([
            'user_id' => $user_id,
            'device_type' => $device_type,
            'page' => $page,
            'per_page' => $per_page,
            'is_restricted' => $is_restricted,
            'type' => $type,
            'is_child_profile' => getCurrentProfileSession('is_child_profile')
        ]));
        
        // Check cache version to invalidate cache when movies are updated/deleted
        $cacheVersionKey = 'pay_per_view_cache_version';
        $cacheVersion = Cache::get($cacheVersionKey, 0);
        $cacheKey = $baseCacheKey . '_v' . $cacheVersion;

        // Use cacheApiResponse helper for Redis caching
        $cachedResult = cacheApiResponse($cacheKey, 300, function () use ($request, $user_id, $device_type, $page, $per_page, $type) {
            $userPlanId = Subscription::select('plan_id')
            ->where(['user_id' => $user_id, 'status' => 'active'])
            ->latest()
            ->first();
            $userPlanId = optional($userPlanId)->plan_id ?? 0;
            
            // OPTIMIZATION: Cache device support check
            $deviceTypeResponse = cache()->remember(
                "device_support_{$user_id}_{$device_type}",
                600,
                function() use ($user_id, $device_type) {
                    $getDeviceTypeData = Subscription::checkPlanSupportDevice($user_id, $device_type);
                    return json_decode($getDeviceTypeData->getContent(), true);
                }
            );
            
            // OPTIMIZATION: Bulk fetch PayPerView purchases
            $purchasedIds = PayPerView::where('user_id', $user_id)
                ->pluck('movie_id')
                ->toArray();
            
            $payPerViewContent = [];

            // OPTIMIZATION: Cache module enable checks
            $moduleCache = cache()->remember('module_enable_status', 600, function() {
                return [
                    'movie' => isenablemodule('movie'),
                    'tvshow' => isenablemodule('tvshow'),
                    'video' => isenablemodule('video'),
                ];
            });
            
        $isMovieModuleEnabled = $moduleCache['movie'] == 1;
        $isTVShowModuleEnabled = $moduleCache['tvshow'] == 1;
        $isVideoModuleEnabled = $moduleCache['video'] == 1;

        // Determine which types to include based on filter
        $includeMovies = in_array($type, ['all', 'movie']);
        $includeTVShows = in_array($type, ['all', 'tvshow']);
        $includeVideos = in_array($type, ['all', 'video']);

        if ($isMovieModuleEnabled && $includeMovies) {
            // OPTIMIZATION: Add limit to prevent querying all 500k records
            $movies = Entertainment::where('movie_access', 'pay-per-view')
            ->select(['id','name', 'type', 'release_date', 'trailer_url', 'trailer_url_type', 'is_restricted', 'imdb_rating',  'poster_url',
                'thumbnail_url', 'poster_tv_url', 'movie_access'])
                ->where('type', 'movie')
                ->where('status', 1)
                ->when(request()->has('is_restricted'), function ($query) {
                    $query->where('is_restricted', request()->is_restricted);
                })
                ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                    $query->where('is_restricted', 0);
                })
                ->latest('id')
                ->take(20) // OPTIMIZATION: Limit to 20 records
                ->get();

            $movies->each(function ($movie) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds){
                $movie->movie_access = 'pay-per-view';
                $movie->user_id = $user_id;
                $movie->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $movie->access = 'pay-per-view';
                $movie = setContentAccess($movie, $user_id, $userPlanId, $purchasedIds ?? []);

                $movie->poster_image = $device_type == 'tv' ? $movie->poster_tv_url : $movie->poster_url ?? null;
            });

            $movies = MoviesResourceV3::collection($movies)->toArray(request());
            $payPerViewContent = array_merge($payPerViewContent, $movies);
        }

        if ($isTVShowModuleEnabled && $includeTVShows) {
            // OPTIMIZATION: Add limit to prevent querying all records
            $tvshows = Entertainment::where('movie_access', 'pay-per-view')
            ->select(['id','name', 'type', 'release_date', 'trailer_url', 'trailer_url_type', 'is_restricted', 'imdb_rating',  'poster_url',
            'thumbnail_url', 'poster_tv_url', 'movie_access'])
            ->where('type', 'tvshow')
            ->where('status', 1)
            ->when(request()->has('is_restricted'), function ($query) {
                $query->where('is_restricted', request()->is_restricted);
            })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            })
            ->latest('id')
            ->take(20) // OPTIMIZATION: Limit to 20 records
            ->get();


            $tvshows->each(function ($tvshows) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds){
                $tvshows->tvshows_access = 'pay-per-view';
                $tvshows->user_id = $user_id;
                $tvshows->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $tvshows->access = 'pay-per-view';
                $tvshows = setContentAccess($tvshows, $user_id, $userPlanId, $purchasedIds ?? []);
                $tvshows->poster_image = $device_type == 'tv' ? $tvshows->poster_tv_url : $tvshows->poster_url ?? null;
            });
            $tvshows = TvshowResourceV3::collection($tvshows)->toArray(request());
            $payPerViewContent = array_merge($payPerViewContent, $tvshows);
        }

        if ($isVideoModuleEnabled && $includeVideos) {
        // OPTIMIZATION: Add limit to prevent querying all records
        $videos = Video::where('access', 'pay-per-view')
        ->select(['id','name', 'slug', 'type', 'release_date', 'trailer_url', 'trailer_url_type', 'is_restricted', 'imdb_rating',  'poster_url',
        'thumbnail_url', 'poster_tv_url', 'access'])
            ->where('status', 1)
            ->when(request()->has('is_restricted'), function ($query) {
                $query->where('is_restricted', request()->is_restricted);
            })
            ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                $query->where('is_restricted', 0);
            })
            ->latest('id')
            ->take(20) // OPTIMIZATION: Limit to 20 records
            ->get();

        $videos->each(function ($video) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
            $video->access = 'pay-per-view';
            $video->user_id = $user_id;
            $video->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
            $video = setContentAccess($video, $user_id, $userPlanId, $purchasedIds ?? []);
            $video->poster_image = $device_type == 'tv' ? $video->poster_tv_url : $video->poster_url ?? null;
        });




        $videos = VideoResourceV3::collection($videos)->toArray(request());
        $payPerViewContent = array_merge($payPerViewContent, $videos);
    }

        if ($isTVShowModuleEnabled && $includeTVShows) {
            // OPTIMIZATION: Add limit to prevent querying all records
            $seasons = Season::where('access', 'pay-per-view')
            ->select(['id','name', 'slug', 'trailer_url', 'trailer_url_type','poster_url', 'poster_tv_url', 'access'])
                ->where('status', 1)
                ->latest('id')
                ->take(20) // OPTIMIZATION: Limit to 20 records
                ->get();

            $seasons->each(function ($season) use ($user_id,  $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                $season->access = 'pay-per-view';
                $season->user_id = $user_id;
                $season->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $season = setContentAccess($season, $user_id, $userPlanId, $purchasedIds ?? []);
                $season->poster_image = $device_type == 'tv' ? $season->poster_tv_url : $season->poster_url ?? null;
            });

            $seasons = SeasonResourceV3::collection($seasons)->toArray(request());
            $payPerViewContent = array_merge($payPerViewContent, $seasons);
        }

        if ($isTVShowModuleEnabled && $includeTVShows) {
            // OPTIMIZATION: Add limit to prevent querying all records
            $episodes = Episode::where('access', 'pay-per-view')
            ->select(['id', 'slug', 'name', 'release_date', 'trailer_url', 'trailer_url_type', 'is_restricted', 'imdb_rating', 'IMDb_rating', 'poster_url',
             'poster_tv_url', 'access', 'entertainment_id', 'season_id', 'duration', 'short_desc'])
                ->where('status', 1)
                ->with(['seasondata', 'entertainmentdata'])
                ->when(request()->has('is_restricted'), function ($query) {
                    $query->where('is_restricted', request()->is_restricted);
                })
                ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                    $query->where('is_restricted', 0);
                })
                ->latest('id')
                ->take(20) // OPTIMIZATION: Limit to 20 records
                ->get();

            $episodes->each(function ($episode) use ($user_id, $userPlanId, $deviceTypeResponse, $device_type, $purchasedIds) {
                $episode->access = 'pay-per-view';
                $episode->user_id = $user_id;
                $episode->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $episode->poster_image =  $device_type == 'tv' ? $episode->poster_tv_url : $episode->poster_url ?? null;
                $episode->download_data = [];
                $episode->tv_show_data = [
                    'id' => $episode->seasondata ? $episode->seasondata->id : null,
                    'season_id' => $episode->seasondata ? $episode->seasondata->id : null,
                ];
                $episode = setContentAccess($episode, $user_id, $userPlanId, $purchasedIds ?? []);
            });

            $episodes = EpisodeResourceV3::collection($episodes)->toArray(request());
            $payPerViewContent = array_merge($payPerViewContent, $episodes);
        }
        if ($per_page > 0) {
            $offset = ($page - 1) * $per_page;
            $paginatedContent = array_slice($payPerViewContent, $offset, $per_page);
        } else {
            $paginatedContent = $payPerViewContent;
        }

        if ($request->is('api/*')) {
            return [
                'status' => true,
                'data' => $paginatedContent,
            ];
        }

        return $paginatedContent;
        });

         $result = $cachedResult;
        if (isset($cachedResult['data']) && is_array($cachedResult['data'])) {
            $result = $cachedResult['data'];
        }

        if ($request->is('api/*')) {
            return ApiResponse::custom($result, 200);
        }

        return $result;
    }

    /**
     * Get slider data based on type and type_id
     */
    public function getSliderData($type, $typeId, $userId = null, $request = null)
    {
        $data = null;

        switch ($type) {
            case 'movie':
            case 'tvshow':
                $entertainment = Entertainment::with('plan')->select('*');

                isset($request->is_restricted) && $entertainment = $entertainment->where('is_restricted', $request->is_restricted);
                (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                    $entertainment = $entertainment->where('is_restricted',0);

                $entertainment = $entertainment->where('id',$typeId)->first();

                if ($entertainment) {
                    $entertainment['is_watch_list'] = \Modules\Entertainment\Models\Watchlist::where('entertainment_id', $typeId)
                        ->where('user_id', $userId)
                        ->where('profile_id',$request->profile_id ?? null)
                        ->exists();

                    $entertainment->user_id = $userId ?? null;
                    $data = $type === 'movie' ? new MoviesResource($entertainment) : new TvshowResource($entertainment);
                }
                break;

            case 'livetv':
                $livetv = LiveTvChannel::find($typeId);
                if ($livetv) {
                    $data = new LiveTvChannelResource($livetv);
                }
                break;

            case 'video':
                $video = Video::select('*');

                isset($request->is_restricted) && $video = $video->where('is_restricted', $request->is_restricted);
                (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                    $video = $video->where('is_restricted',0);

                $video = $video->where('id',$typeId)->first();
                if ($video) {
                    $video->user_id = $userId ?? null;
                    $data = new VideoResource($video);
                }
                break;
        }

        return $data;
    }
}
