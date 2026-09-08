<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileSetting;
use Modules\Entertainment\Models\Entertainment;
use Modules\Banner\Models\Banner;
use App\Models\Device;
use App\Models\User;
use Modules\Subscriptions\Models\Plan;
use Modules\Subscriptions\Models\Subscription;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Modules\Tax\Models\Tax;
use Modules\Constant\Models\Constant;
use Modules\FAQ\Models\FAQ;
use Modules\Coupon\Models\Coupon;
use App\Services\RecommendationServiceV3;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Subscriptions\Trait\SubscriptionTrait;
use Illuminate\Support\Facades\Cache;
use Modules\Frontend\Models\PayPerView;
use Modules\Genres\Models\Genres;
use Modules\Episode\Models\Episode;
use Modules\Video\Models\Video;
use Modules\LiveTV\Models\LiveTvChannel;
use App\Models\UserMultiProfile;
use Modules\User\Transformers\UserMultiProfileResource;
use Modules\Banner\Transformers\Backend\SliderResourceV3;
use Modules\Entertainment\Transformers\Backend\CommonContentResourceV3;
use Modules\Entertainment\Transformers\Backend\Top10ContentResourceV3;
use Modules\Entertainment\Transformers\Backend\EpisodeResourceV3;
use Modules\Video\Transformers\Backend\VideoResourceV3;
use Modules\LiveTV\Transformers\Backend\LiveTvChannelResourceV3;
use Modules\CastCrew\Models\CastCrew;
use Modules\Genres\Transformers\GenresResource;
use Modules\CastCrew\Transformers\CastCrewListResource;
use Modules\Entertainment\Transformers\Backend\ContinueWatchResourceV3;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Ad\Transformers\CustomAdsSettingResource;
use Modules\Ad\Models\CustomAdsSetting;
use Modules\Entertainment\Transformers\Backend\SeasonResourceV3;
use Modules\Season\Models\Season;
use Carbon\Carbon;
use Modules\Entertainment\Models\EntertainmentView as EntertainmentViewModel;

class FrontendController extends Controller
{
    use SubscriptionTrait;
    /**
     * Display a listing of the resource.
     */
    protected $recommendationService;
    public function __construct(RecommendationServiceV3 $recommendationService)
    {
        $this->recommendationService = $recommendationService;

    }

    /**
     * Translate section/tab name and cache it per-locale.
     * - First request: translate + cache
     * - Next requests: from cache
     * - When name is edited: backend flushes cache, and also key changes with new name
     */
    private function translateTabName(?string $name, ?string $locale = null): string
    {
        return translateTabName($name, $locale);
    }

    public function index(Request $request)
    {

        $user_id = auth()->id();
        $profile_id = $request->profile_id ?? getCurrentProfile($user_id, $request);
        $device_type = getDeviceType($request);

        // Create cache key based on request parameters
        $cacheKey = 'dashboard_detail_data_v3_'. md5(json_encode([
            'user_id' => $user_id,
            'profile_id' => $profile_id,
            'device_type' => $device_type,
            'timestamp' => now()->format('Y-m-d-H')
        ]));

        // Use Redis cache with 5 minutes TTL
        $cachedResult = Cache::remember($cacheKey, 1, function () use ($request, $user_id, $profile_id, $device_type) {
            // Get all settings with names and values in one call
            $settings = [
                'top-10' => MobileSetting::getNameAndValueBySlug('top-10'),
                'latest-movies' => MobileSetting::getNameAndValueBySlug('latest-movies'),
                'popular-movies' => MobileSetting::getNameAndValueBySlug('popular-movies'),
                'popular-tvshows' => MobileSetting::getNameAndValueBySlug('popular-tvshows'),
                'most-watched-videos' => MobileSetting::getNameAndValueBySlug('popular-videos'),
                'top-channels' => MobileSetting::getNameAndValueBySlug('top-channels'),
                'genre' => MobileSetting::getNameAndValueBySlug('genre'),
                '500-free-movies' => MobileSetting::getNameAndValueBySlug('500-free-movies'),
                'your-favorite-personality' => MobileSetting::getNameAndValueBySlug('your-favorite-personality'),
                'enjoy-in-your-native-tongue' => MobileSetting::getNameAndValueBySlug('enjoy-in-your-native-tongue'),
                'banner' => MobileSetting::getNameAndValueBySlug('banner'),
                'continue-watching' => MobileSetting::getNameAndValueBySlug('continue-watching'),
            ];


            $responseData = [];

            $sliderList = Cache::remember('home_banners', 300, function () {
                return Banner::where('banner_for', 'home')
                    ->where('status', 1)
                    ->orderBy('id', 'desc')
                    ->where('deleted_at', null)
                    ->get();
            });
            $sliders = $sliderList->map(function ($banner) use ($user_id) {
                return (new SliderResourceV3($banner, $user_id))->toArray(request());
            })->values()->all();


            $user = auth()->user();

            $responseData['custom_ads'] = [];
            $checkPlanLimit=1;

            if($user){

                $profile_id=getCurrentProfile($user->id, $request);

                $based_on_last_watch_ids = $this->recommendationService->recommendByLastHistory($user,$profile_id);

                $based_on_last_watch = (!empty($based_on_last_watch_ids)) ? Entertainment::get_recommended_movie($based_on_last_watch_ids) : collect();

                $based_on_last_watch = CommonContentResourceV3::collection($based_on_last_watch)->toArray($request);
                $responseData['based_on_last_watch'] = [
                    'name' => __('frontend.because_you_watch'),
                    'data' => $based_on_last_watch,
                ];

                $continuewatch_base = ContinueWatch::query()
                            ->whereNotNull('watched_time')
                            ->whereNotNull('total_watched_time')
                            ->where(function ($query) {
                                // For movie and tvshow, check entertainment relationship
                                $query->where(function ($q) {
                                    $q->where('entertainment_type', 'movie')
                                    ->whereHas('entertainment', function ($subQuery) {
                                        $subQuery->where('status', 1)
                                                    ->whereNull('deleted_at');
                                    });
                                })
                                // For episode, check episode relationship
                                ->orWhere(function ($q) {
                                    $q->where('entertainment_type', 'tvshow')
                                    ->whereNotNull('episode_id')
                                    ->whereHas('episode', function ($subQuery) {
                                        $subQuery->where('status', 1)
                                                    ->whereNull('deleted_at');
                                    });
                                })
                                // For video, check video relationship
                                ->orWhere(function ($q) {
                                    $q->where('entertainment_type', 'video')
                                    ->whereHas('video', function ($subQuery) {
                                        $subQuery->where('status', 1)
                                                    ->whereNull('deleted_at');
                                    });
                                });
                            })
                            ->with(['entertainment', 'episode.seasondata', 'video']);

                $continueWatchList = $continuewatch_base->where('user_id', $user_id)
                                                    ->where('profile_id',$profile_id)
                                                    ->where(function($query) {
                                                        $query->whereHas('entertainment', function($q) {
                                                            $q->where('status', 1)->whereNull('deleted_at');
                                                        })
                                                        ->orWhereHas('episode', function($q) {
                                                            $q->whereNull('deleted_at');
                                                        })
                                                        ->orWhereHas('video', function($q) {
                                                            $q->where('status', 1)->whereNull('deleted_at');
                                                        }); //add karvanu
                                                    })
                                                    ->orderBy('updated_at', 'desc')
                                                    ->get();

                 $continueWatch = ContinueWatchResourceV3::collection($continueWatchList);

                $responseData['continue_watch'] = $continueWatch->toArray($request);

                $likedMoviesIds = $this->recommendationService->getLikedMovies($user, $profile_id);
                $likedMovies = (!empty($likedMoviesIds)) ? Entertainment::get_recommended_movie($likedMoviesIds) : collect();
                $likedMovies = CommonContentResourceV3::collection($likedMovies)->toArray($request);
                $responseData['liked_movie'] = [
                    'name' => __('frontend.liked_movie'),
                    'data' => $likedMovies,
                ];

                // Top-rated movies on IMDB (rating >= 8)
                $topRatedMovies = Entertainment::with([
                    'genresdata:id,name',
                    'plan:id,level'
                ])
                ->select([
                    'id','name','slug','type','plan_id','description','trailer_url_type',
                    'is_restricted','language','IMDb_rating','content_rating',
                    'duration','video_upload_type','trailer_url','video_url_input',
                    'poster_url','thumbnail_url','poster_tv_url','movie_access',
                    'price','purchase_type','access_duration','discount',
                    'available_for','release_date'
                ])
                ->where('type', 'movie')
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->whereRaw('CAST(IMDb_rating AS DECIMAL(3,1)) >= ?', [8.0])
                ->whereDate('release_date', '<=', now())
                ->orderByRaw('CAST(IMDb_rating AS DECIMAL(3,1)) DESC')
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();

                if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
                    $topRatedMovies = $topRatedMovies->where('is_restricted', 0);
                }

                $topRatedMovies = CommonContentResourceV3::collection($topRatedMovies)->toArray($request);
                $responseData['viewed_movie'] = [
                    'name' => __('frontend.top_rated_movies'),
                    'data' => $topRatedMovies,
                ];

                $trendingMovies = $this->recommendationService->getTrendingMoviesByCountry($user);
                $trendingMovies = (!empty($trendingMovies)) ? Entertainment::get_recommended_movie($trendingMovies) : collect();
                $trendingMovies = CommonContentResourceV3::collection($trendingMovies)->toArray($request);
                $responseData['trending_movie'] = [
                    'name' => __('frontend.tranding_in_country'),
                    'data' => $trendingMovies,
                ];


                $FavoriteGener = $this->recommendationService->getFavoriteGener($user, $profile_id);
                $FavoriteGener = GenresResource::collection($FavoriteGener);

                $responseData['favorite_gener'] = [
                    'name' => __('frontend.favorite_gener'),
                    'data' =>$FavoriteGener->toArray($request),
                ];


                $favorite_personality = $this->recommendationService->getFavoritePersonality($user, $profile_id);
                 $favorite_personality = CastCrewListResource::collection($favorite_personality)->toArray($request);
                 $responseData['user_favorite_personality'] = [
                    'name' => __('frontend.favorite_personality'),
                    'data' => $favorite_personality,
                ];

            }


            if ($settings['top-10'] && $settings['top-10']['value'] && empty($settings['top-10']['type'])) {
                $topMovieIdsArray = json_decode($settings['top-10']['value'], true);
                $top_10 = (!empty($topMovieIdsArray)) ? Entertainment::get_top_movie($topMovieIdsArray) : collect();
                $top_10 = Top10ContentResourceV3::collection($top_10)->toArray($request);

                $responseData['top_10'] = [
                    'name' => $this->translateTabName($settings['top-10']['name'] ?? ''),
                    'data' => $top_10,
                ];
            }


            if ($settings['latest-movies'] && $settings['latest-movies']['value'] && empty($settings['latest-movies']['type'])) {
                $latestMovieIdsArray = json_decode($settings['latest-movies']['value'], true);
                $latest_movie = (!empty($latestMovieIdsArray)) ? Entertainment::get_latest_movie($latestMovieIdsArray) : collect();
                $latest_movie = CommonContentResourceV3::collection($latest_movie)->toArray($request);


                $responseData['latest_movie'] = [
                    'name' => $this->translateTabName($settings['latest-movies']['name'] ?? ''),
                    'data' => $latest_movie,
                ];
            }


            $payperview_movie  =  Entertainment::get_pay_per_view_movie();
            
            // Separate movies and tvshows from the combined list to match API sequence
            $movies = $payperview_movie->where('type', 'movie')->values();
            $tvshows = $payperview_movie->where('type', 'tvshow')->values();

            $payperview_movieArray = CommonContentResourceV3::collection($movies)->resolve();
            $tvshowArray = CommonContentResourceV3::collection($tvshows)->resolve();

            $payperview_videos = Video::get_pay_per_view_videos();
            $videosArray = VideoResourceV3::collection($payperview_videos)->resolve();

            // Fetch seasons directly as we cannot add helper functions to the Season model
            $payperview_seasons = collect();
            if (isenablemodule('tvshow') == 1) {
                $payperview_seasons = Season::where('status', 1)
                    ->where('access', 'pay-per-view')
                    ->latest('id')
                    ->take(5)
                    ->get();
            }
            $seasonsArray = SeasonResourceV3::collection($payperview_seasons)->resolve();

            $payperview_episodes = Episode::get_pay_per_view_episodes();
            $episodesArray = EpisodeResourceV3::collection($payperview_episodes)->resolve();

            // API merge order: movies, tvshows, videos, seasons, episodes
            $payperview = array_merge($payperview_movieArray, $tvshowArray, $videosArray, $seasonsArray, $episodesArray);

            // shuffle($payperview);
            $payperview = array_slice($payperview, 0, 10);

            if ($settings['enjoy-in-your-native-tongue'] && $settings['enjoy-in-your-native-tongue']['value'] && empty($settings['enjoy-in-your-native-tongue']['type'])) {
                $popularLanguageIdsArray = json_decode($settings['enjoy-in-your-native-tongue']['value'], true);

                $popular_language = (!empty($popularLanguageIdsArray)) ? Constant::whereIn('id', $popularLanguageIdsArray)->where('type', 'movie_language')->select('id', 'name', 'language_image')->where('status', 1)->where('deleted_at', null)->get() : collect();

                $responseData['popular_language'] = [
                    'name' => $this->translateTabName($settings['enjoy-in-your-native-tongue']['name'] ?? ''),
                    'data' => $popular_language,
                ];
            }

            $checkPlanLimit =checkPlanLimit($user_id,'ads');



            if($checkPlanLimit === 0){
                $responseData['custom_ads'] = [];
            } else {
                $today = Carbon::now()->toDateString();
                $custom_ads = CustomAdsSetting::where('status', 1)->where('deleted_at', null)->where('placement', 'home_page')->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->get();
                $custom_ads = CustomAdsSettingResource::collection($custom_ads)->toArray($request);
                $responseData['custom_ads'] = $custom_ads;
            } 


            if ($settings['popular-movies'] && $settings['popular-movies']['value'] && empty($settings['popular-movies']['type'])) {
                $popularMovieIdsArray = json_decode($settings['popular-movies']['value'], true);
                $popular_movie = (!empty($popularMovieIdsArray)) ? Entertainment::get_popular_movie($popularMovieIdsArray) : collect();
                $popular_movie = CommonContentResourceV3::collection($popular_movie)->toArray($request);

                $responseData['popular_movie'] = [
                    'name' => $this->translateTabName($settings['popular-movies']['name'] ?? ''),
                    'data' => $popular_movie,
                ];
            }

            if ($settings['top-channels'] && $settings['top-channels']['value'] && empty($settings['top-channels']['type'])) {
                $TopChannelIdsArray = json_decode($settings['top-channels']['value'], true);
                $TopChannel = (!empty($TopChannelIdsArray)) ? LiveTvChannel::get_top_channel($TopChannelIdsArray) : collect();
                $TopChannel = LiveTvChannelResourceV3::collection($TopChannel)->toArray($request);

                $responseData['top-channels'] = [
                    'name' => $this->translateTabName($settings['top-channels']['name'] ?? ''),
                    'data' => $TopChannel,
                ];
            }

            if ($settings['popular-tvshows'] && $settings['popular-tvshows']['value'] && empty($settings['popular-tvshows']['type'])) {
                $popularTvshowIdsArray = json_decode($settings['popular-tvshows']['value'], true);
                $popularTvshow = (!empty($popularTvshowIdsArray)) ? Entertainment::get_popular_tvshow($popularTvshowIdsArray) : collect();
                $popularTvshow = CommonContentResourceV3::collection($popularTvshow)->toArray($request);

                $responseData['popular_tvshow'] = [
                    'name' => $this->translateTabName($settings['popular-tvshows']['name'] ?? ''),
                    'data' => $popularTvshow,
                ];
            }

            if ($settings['your-favorite-personality'] && $settings['your-favorite-personality']['value'] && empty($settings['your-favorite-personality']['type'])) {
                $castIdsArray = json_decode($settings['your-favorite-personality']['value'], true) ?: [];
                $personality =(!empty($castIdsArray)) ? CastCrew::getFrontendCardsByIds($castIdsArray) : collect();
                $responseData['popular_personality'] = [
                    'name' => $this->translateTabName($settings['your-favorite-personality']['name'] ?? ''),
                    'data' => $personality,
                ];
            }


            if($settings['500-free-movies'] && $settings['500-free-movies']['value'] && empty($settings['500-free-movies']['type'])) {
                $freeMovieIdsArray = json_decode($settings['500-free-movies']['value'], true);
                $free_movie = (!empty($freeMovieIdsArray)) ? Entertainment::get_free_movie($freeMovieIdsArray) : collect();
                $free_movie = CommonContentResourceV3::collection($free_movie)->toArray($request);

                $responseData['free_movie'] = [
                    'name' => $this->translateTabName($settings['500-free-movies']['name'] ?? ''),
                    'data' => $free_movie,
                ];
            }

            // if($settings['500-free-movies'] && $settings['500-free-movies']['value']) {
            //     $freeMovieIdsArray = json_decode($settings['500-free-movies']['value'], true);
            //     $free_movie = (!empty($freeMovieIdsArray)) ? Entertainment::get_free_movie($freeMovieIdsArray) : collect();
            //     $free_movie = CommonContentResourceV3::collection($free_movie)->toArray($request);

            //     $responseData['free_movie'] = [
            //         'name' => $this->translateTabName($settings['500-free-movies']['name'] ?? ''),
            //         'data' => $free_movie,
            //     ];
            // }

            if($settings['genre'] && $settings['genre']['value'] && empty($settings['genre']['type'])) {
                $genreIdsArray = json_decode($settings['genre']['value'], true);
                $genres = (!empty($genreIdsArray)) ? Genres::whereIn('id', $genreIdsArray)
                    ->where('status', 1)
                    ->get()
                    ->map(function ($genre) {
                        return [
                            'id' => $genre->id,
                            'name' => $genre->name,
                            'poster_image' => !empty($genre->file_url) ? setBaseUrlWithFileName($genre->file_url, 'image', 'genres') : null,
                        ];
                    })->toArray() : [];


                $responseData['genre'] = [
                    'name' => $this->translateTabName($settings['genre']['name'] ?? ''),
                    'data' => $genres,
                ];
            }



            if($settings['most-watched-videos'] && $settings['most-watched-videos']['value'] && empty($settings['most-watched-videos']['type'])) {
                // Get selected video IDs from backend settings for homepage display
                $PopularVideoIdsArray = json_decode($settings['most-watched-videos']['value'], true);
                $popular_video = (!empty($PopularVideoIdsArray)) ? Video::get_popular_videos($PopularVideoIdsArray) : collect();
                $popular_video = VideoResourceV3::collection($popular_video)->toArray($request);

                $responseData['popular_video'] = [
                    'name' => $this->translateTabName($settings['most-watched-videos']['name'] ?? ''),
                    'data' => $popular_video,
                ];
            }

            $mobile_settings = MobileSetting::where('type', '!=', null)->get();

            $responseData['dynamic_data'] = [];

            if($mobile_settings->count() > 0){

              foreach($mobile_settings as $mobile_setting){

                if($mobile_setting->type == 'movie'){

                    $movie_ids = json_decode($mobile_setting->value);
                    $movie = Entertainment::get_latest_movie($movie_ids);
                    $moivedata = CommonContentResourceV3::collection($movie)->toArray($request);

                    $responseData['dynamic_data'][$mobile_setting->slug]= [
                        'name' => $this->translateTabName($mobile_setting->name),
                        'data' => $moivedata,
                        'type' => 'movie',
                    ];


                }

                if($mobile_setting->type == 'tvshow'){
                  $tvshow_ids = json_decode($mobile_setting->value);
                  $tvshow = Entertainment::get_popular_tvshow($tvshow_ids);
                  $tvshowdata = CommonContentResourceV3::collection($tvshow)->toArray($request);

                  $responseData['dynamic_data'][$mobile_setting->slug]= [
                    'name' => $this->translateTabName($mobile_setting->name),
                    'data' => $tvshowdata,
                    'type' => 'tvshow',
                  ];
                }

                if($mobile_setting->type == 'video'){
                  $video_ids = json_decode($mobile_setting->value);
                  $video = Video::get_popular_videos($video_ids);
                  $videodata = VideoResourceV3::collection($video)->toArray($request);

                  $responseData['dynamic_data'][$mobile_setting->slug]= [
                    'name' => $this->translateTabName($mobile_setting->name),
                    'data' => $videodata,
                    'type' => 'video',
                  ];
                }

                if($mobile_setting->type == 'channel'){
                  $channel_ids = json_decode($mobile_setting->value);
                  $channel = LiveTvChannel::get_top_channel($channel_ids);
                  $channeldata = LiveTvChannelResourceV3::collection($channel)->toArray($request);

                  $responseData['dynamic_data'][$mobile_setting->slug]= [
                    'name' => $this->translateTabName($mobile_setting->name),
                    'data' => $channeldata,
                    'type' => 'channel',
                  ];

                }
              }
            }

            $responseData['payperview'] = $payperview;
            $responseData['sliders'] = $sliders;

            return $responseData;
        });



        return view('frontend::index', compact('user_id', 'cachedResult'));
    }



    public function searchList()
    {
        $gener=Genres::where('status', 1)->get();

        $topSearches = collect();

        $topSearches = \App\Models\UserSearchHistory::query()
                ->whereNotNull('search_id')
                ->selectRaw('search_id, type, MAX(search_query) as search_query, COUNT(*) as total')
                ->groupBy('search_id', 'type')
                ->orderByDesc('total')
                ->with(['entertainment' => function($query) {
                    $query->where('deleted_at', null)->where('status', 1);
                    if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
                        $query->where('is_restricted', 0);
                    }
                }, 'episode' => function($query) {
                    $query->where('deleted_at', null)->where('status', 1);
                    if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
                        $query->where('is_restricted', 0);
                    }
                }, 'video' => function($query) {
                    $query->where('deleted_at', null)->where('status', 1);
                    if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
                        $query->where('is_restricted', 0);
                    }
                }])
                ->limit(5)
                ->get()
                ->filter(function($item) {
                     return match ($item->type) {
                        'movie', 'tvshow' => $item->entertainment !== null
                            && !empty($item->entertainment->name)
                            && !empty($item->entertainment->slug),
                        'episode'        => $item->episode !== null,
                        'video'          => $item->video !== null
                            && !empty($item->video->name)
                            && !empty($item->video->slug),
                        default          => false,
                    };
                })
                ->sortByDesc('total')
                ->map(function ($item) {
                    $searchQuery = $item->search_query;
                    if (empty($searchQuery)) {
                        $searchQuery = match ($item->type) {
                            'movie', 'tvshow' => $item->entertainment?->name,
                            'episode'         => $item->episode?->name,
                            'video'           => $item->video?->name,
                            default           => null,
                        };
                    }
                    // Populate the model's search_query attribute so the view can access it transparently
                    $item->search_query = $searchQuery;
                    return $item;
                })
                ->values();


        return view('frontend::search', compact('gener','topSearches'));
    }


    public function getSearchV3(Request $request)
    {

        $searchTerm = $request->get('search', '');

        if (!empty($searchTerm) && auth()->user()) {
            $user = auth()->user();
            $profile_id = getCurrentProfile($user->id, $request);

            $existingSearch = \App\Models\UserSearchHistory::where('user_id', $user->id)
                ->where('profile_id',  $profile_id)
                ->where('search_query', $searchTerm)
                ->first();

            if (!$existingSearch) {
                $search_id = null;
                $logType = null;

                $entertainment = \Modules\Entertainment\Models\Entertainment::where('name', 'like', "%{$searchTerm}%")->first();
                if ($entertainment) {
                    $search_id = $entertainment->id;
                    $logType = $entertainment->type === 'movie' ? 'movie' : 'tvshow';
                }

                if (!$search_id) {
                    $video = \Modules\Video\Models\Video::where('name', 'like', "%{$searchTerm}%")->first();
                    if ($video) {
                        $search_id = $video->id;
                        $logType = 'video';
                    }
                }

                $search_data = [
                    'user_id' => $user->id,
                    'search_query' => $searchTerm,
                    'profile_id' => $profile_id,
                    'search_id' => $search_id,
                    'type' => $logType,
                ];

                \App\Models\UserSearchHistory::create($search_data);
            }
        }

        $types = collect(explode(',', (string)$request->get('type', '')))
            ->map(fn($t) => trim($t))
            ->filter()
            ->values();
        $genreIds = collect(explode(',', (string)$request->get('genre_id', '')))
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->values();



        if ( empty($searchTerm) && $types->isEmpty()) {

            $types = collect(['movie', 'tvshow']);
        }


        if ($types->isEmpty() && !empty($searchTerm)) {
            $types = collect(['movie', 'tvshow', 'video', 'season', 'episode', 'actor', 'director', 'livetv']);
        }

        $movieData = collect([]);


    if($types->contains('movie')){

        $movieList = Entertainment::query()
            ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews', 'entertainmentTalentMappings', 'entertainmentStreamContentMappings')
           ->where('status', 1)->where('deleted_at', null)->where('type', 'movie');

        if(!empty($searchTerm)){

            $movieList->where(function ($query) use ($searchTerm) {

                $normalizedTerm = str_replace(' ', '', $searchTerm);

                $query->whereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$normalizedTerm}%"])
                    ->orWhereHas('entertainmentGenerMappings.genre', function ($subQuery) use ($normalizedTerm) {
                        $subQuery->whereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$normalizedTerm}%"]);
                    });
            });

        }

        if ($genreIds->isNotEmpty()) {


            $movieList->whereHas('entertainmentGenerMappings', function ($q) use ($genreIds) {
                $q->whereIn('genre_id', $genreIds);
            });


        }

        if (isset($request->is_restricted)) {
            $movieList = $movieList->where('is_restricted', $request->is_restricted);
        }
        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $movieList = $movieList->where('is_restricted', 0);
        }

        $movieList = $movieList->orderBy('updated_at', 'desc')->get();

        $movieData = (isenablemodule('movie') == 1) ? CommonContentResourceV3::collection($movieList) : [];
    }

     $tvshowData = collect([]);

        if($types->contains('tvshow')){

        $tvshowList = Entertainment::where('status', 1)
            ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews',
            'entertainmentTalentMappings', 'season', 'episode')->whereHas('episode')->where('deleted_at', null);

        if (isset($request->is_restricted)) {
            $tvshowList = $tvshowList->where('is_restricted', $request->is_restricted);
        }
        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $tvshowList = $tvshowList->where('is_restricted', 0);
        }

            if (!empty($searchTerm)) {
                $normalizedTerm = str_replace(' ', '', $searchTerm);

                $tvshowList->where(function ($query) use ($normalizedTerm) {
                    $query->whereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$normalizedTerm}%"])
                        ->orWhereHas('entertainmentGenerMappings.genre', function ($subQuery) use ($normalizedTerm) {
                            $subQuery->whereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$normalizedTerm}%"]);
                        });
                });
            }


        if ($genreIds->isNotEmpty() ) {
            $tvshowList->whereHas('entertainmentGenerMappings', function ($q) use ($genreIds) {
                $q->whereIn('genre_id', $genreIds);
            });
        }

        $tvshowList = $tvshowList->orderBy('updated_at', 'desc')->where('type', 'tvshow')->get();
        $tvshowData = (isenablemodule('tvshow') == 1) ? CommonContentResourceV3::collection($tvshowList) : [];
    }
    $videoData = collect([]);

    if($types->contains('video')){

            $videoList = Video::query()->whereDate('release_date', '<=', Carbon::now())->with('VideoStreamContentMappings', 'plan');
            if (!empty($searchTerm)) {
                $normalizedTerm = str_replace(' ', '', $searchTerm);

                $videoList->where(function ($query) use ($normalizedTerm) {
                    $query->whereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$normalizedTerm}%"]);
                });
            }

            if (isset($request->is_restricted)) {
                $videoList = $videoList->where('is_restricted', $request->is_restricted);
            }
            if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
                $videoList = $videoList->where('is_restricted', 0);
            }

            $videoList = $videoList->where('status', 1)->orderBy('updated_at', 'desc')->get();
            $videoData = (isenablemodule('video') == 1) ? VideoResourceV3::collection($videoList) : collect([]);

    }

    $seasonData = collect([]);

    if($types->contains('season') ){
        $seasonData = collect([]);

            $seasonList = Season::query()->with('episodes', 'entertainmentdata');
            if (!empty($searchTerm)) {
                $normalizedTerm = str_replace(' ', '', $searchTerm);

                $seasonList->where(function ($query) use ($normalizedTerm) {
                    $query->whereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$normalizedTerm}%"]);
                });
            }
            $seasonList = $seasonList->where('status', 1)->orderBy('updated_at', 'desc')->get();
            $seasonData = (isenablemodule('tvshow') == 1) ? SeasonResourceV3::collection($seasonList) : collect([]);

    }


        // Build episode data only when not forcing default types
        $episodeData = collect([]);
     if($types->contains('episode') ){

            $episodeList = Episode::query()->whereDate('release_date', '<=', Carbon::now())->with('seasondata');

            if (!empty($searchTerm)) {
                $normalizedTerm = str_replace(' ', '', $searchTerm);

                $episodeList->where(function ($query) use ($normalizedTerm) {
                    $query->whereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$normalizedTerm}%"]);
                });
            }

            if (isset($request->is_restricted)) {
                $episodeList = $episodeList->where('is_restricted', $request->is_restricted);
            }
            if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
                $episodeList = $episodeList->where('is_restricted', 0);
            }

            $episodeList = $episodeList->where('status', 1)->orderBy('updated_at', 'desc')->get();
            $episodeData = (isenablemodule('tvshow') == 1) ? EpisodeResourceV3::collection($episodeList) : collect([]);

      }


        // Build actor data only when not forcing default types
        $actorData = collect([]);
        if ( $types->contains('actor')) {
            $actorList = CastCrew::query()->where('type', 'actor')->with('entertainmentTalentMappings');
            if (!empty($searchTerm)) {
                $normalizedTerm = str_replace(' ', '', $searchTerm);

                $actorList->where(function ($query) use ($normalizedTerm) {
                    $query->whereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$normalizedTerm}%"]);
                });
            }
            $actorList = $actorList->orderBy('updated_at', 'desc')->get();
            $actorData = CastCrewListResource::collection($actorList);
        }


        // Build director data only when not forcing default types
        $directorData = collect([]);
        if ($types->contains('director')) {
            $directorList = CastCrew::query()->where('type', 'director')->with('entertainmentTalentMappings');
            if (!empty($searchTerm)) {
                $normalizedTerm = str_replace(' ', '', $searchTerm);

                $directorList->where(function ($query) use ($normalizedTerm) {
                    $query->whereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$normalizedTerm}%"]);
                });
            }

            $directorList = $directorList->orderBy('updated_at', 'desc')->get();
            $directorData = CastCrewListResource::collection($directorList);
        }


        $liveTVData = collect([]);
        if ( $types->contains('livetv')) {
            $liveTVList = LiveTvChannel::where('status', 1);


            if (!empty($searchTerm)) {
                $normalizedTerm = str_replace(' ', '', $searchTerm);

                $liveTVList->where(function ($query) use ($normalizedTerm) {
                    $query->whereRaw("REPLACE(name, ' ', '') LIKE ?", ["%{$normalizedTerm}%"]);
                });
            }

            $liveTVList = $liveTVList->orderBy('updated_at', 'desc')->get();
            $liveTVData = LiveTvChannelResourceV3::collection($liveTVList);
        }



        if ($request->has('is_ajax') && $request->is_ajax == 1) {

            $html = '';

            if($movieData && $movieData->isNotEmpty()) {

              $html.= ' <h4 class="mb-5">'.__('frontend.movies').'</h4>';


               $html.= ' <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6" id="entertainment-list">';




                    $html .= view('frontend::components.card.card_movie', [
                        'values' =>$movieData->toArray($request),
                        'type' => 'movie',
                        'is_search'=>1,
                    ])->render();

                $html .= '</div>';
            }
            if ( $tvshowData && $tvshowData->isNotEmpty()) {


              $html.= ' <h4 class="mb-5 mt-5">'.__('frontend.tvshows').'</h4>';


              $html.= ' <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6" id="tvshow-list">';


                    $html .= view('frontend::components.card.card_tvshow', [
                        'values' =>$tvshowData->toArray($request),
                        'type' => 'tvshow',
                        'is_search'=>1,
                    ])->render();


                $html .= '</div>';
            }
            if ($videoData && $videoData->isNotEmpty()) {

                $html.= ' <h4 class="mb-5 mt-5">'.__('frontend.videos').'</h4>';

               $html.= ' <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6" id="video-list">';

                $html .= view('frontend::components.card.card_video', [
                    'values' =>$videoData->toArray($request),
                    'type' => 'video',
                    'is_search'=>1,
                ])->render();

                $html .= '</div>';
            }
            if ($seasonData && $seasonData->isNotEmpty()) {

                $html.= ' <h4 class="mb-5 mt-5">'.__('frontend.seasons').'</h4>';

               $html.= ' <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6" id="season-list">';

                foreach ($seasonData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_season', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }

                $html .= '</div>';
            }
            if ($episodeData && $episodeData->isNotEmpty()) {

                $html.= ' <h4 class="mb-5 mt-5">'.__('frontend.episodes').'</h4>';

                $html.= ' <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6" id="episode-list">';

                foreach ($episodeData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_season', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }

                $html .= '</div>';
            }
            if ($actorData && $actorData->isNotEmpty()) {

                $html.= ' <h4 class="mb-5 mt-5">'.__('frontend.actors').'</h4>';

                $html.= ' <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6" id="actor-list">';

                foreach ($actorData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_castcrew', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }

                $html .= '</div>';
            }
            if ($directorData && $directorData->isNotEmpty()) {

                $html.= ' <h4 class="mb-5 mt-5">'.__('frontend.directors').'</h4>';

                $html.= ' <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6" id="director-list">';

                foreach ($directorData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_castcrew', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }

                $html .= '</div>';
            }

            if ($liveTVData && $liveTVData->isNotEmpty()) {
                $html.= ' <h4 class="mb-5 mt-5">'.__('frontend.live_tv').'</h4>';
                $html.= ' <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6" id="live-tv-list">';
                foreach ($liveTVData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_tvchannel', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
                $html .= '</div>';
            }

            if (empty($movieData) && empty($tvshowData) && empty($videoData) && empty($seasonData) && empty($episodeData) && empty($actorData) && empty($directorData) && empty($liveTVData)) {
                $html .= '';
            }


            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.search_list'),

            ], 200);
        }

        return response()->json([
            'status' => true,
            'movieList' => $movieData,
            'tvshowList' => $tvshowData,
            'videoList' => $videoData,
            'seasonList' => $seasonData,
            'message' => __('movie.search_list'),
        ], 200);
    }


    public function tvshowList()
    {

        return view('frontend::movie');
    }

    public function continueWatchList()
    {
        return view('frontend::continueWatch');
    }

    public function languageList()
    {
        $languageIds = MobileSetting::getValueBySlug('enjoy-in-your-native-tongue');
        $popular_language = Constant::whereIn('id',json_decode($languageIds))->get();

        return view('frontend::language',compact('popular_language'));
    }
    public function languageData(Request $request){
        $perPage = $request->input('per_page', 10);
        $popular_language = Constant::where('type','movie_language')->where('status', 1)->where('deleted_at', null);

        $html = '';
        $popular_language = $popular_language->paginate($perPage);
            foreach($popular_language as $language) {
                $html .= view('frontend::components.card.card_language',['popular_language' => $language])->render();
            }
            $hasMore = $popular_language->hasMorePages();
            return response()->json([
                'status' => true,
                'html' => $html,
                'message' => __('movie.tvshow_list'),
                'hasMore' => $hasMore,
            ], 200);
    }
    public function topChannelList()
    {
        return view('frontend::topChannel');
    }
    public function genresList()
    {
        return view('frontend::genres');
    }

    public function comingsoon()
    {
        return view('frontend::comingsoon');
    }
    public function livetv()
    {
        return view('frontend::livetv');
    }
    public function subscriptionPlan()
    {
        if (getCurrentProfileSession('is_child_profile') == 1) {
            return redirect()->route('user.login');
        }

        $plans = Plan::with('planLimitation')->where('status',1)->get();
        $activeSubscriptions = Subscription::where('user_id', auth()->id())->where('status', 'active')->where('end_date', '>', now())->orderBy('id','desc')->first();
        $currentPlanId = $activeSubscriptions ? $activeSubscriptions->plan_id : null;
        $subscriptions = Subscription::where('user_id', auth()->id())
        ->with('subscription_transaction')
        ->where('end_date', '<', now())
        ->get();

        return view('frontend::subscriptionplan', compact('plans','currentPlanId','activeSubscriptions'));
    }
    public function watchList(Request $request)
    {
        if(!auth()->user()){
            return redirect()->route('user.login');
        }
        return view('frontend::watchlist');
    }

    public function accountSetting()
    {

        if(getCurrentProfileSession('is_child_profile') == 1){
            return redirect()->route('user.login');
        }
        $user = auth()->user();

         $subscriptions = Subscription::where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('id','desc')
            ->first();

        $devices = $user->devices;

        $your_device = null;
        $other_devices = [];

        // Use IP address as device_id (old code)
        $currentDeviceId = request()->getClientIp();

        // Use Agent to get browser name for current session
        $agent = new \Jenssegers\Agent\Agent();
        $currentBrowserName = $agent->browser();
        $currentPlatform = $agent->platform();

        foreach ($devices as $device) {
            if ($device->device_id == $currentDeviceId) {
                $your_device = $device;
            } else {
                // All other devices go to other_devices list
                $other_devices[] = $device;
            }
        }

        // If current device not found, create it
        if (!$your_device) {
            $profile = \App\Models\UserMultiProfile::where('user_id', $user->id)->first();
            $your_device = \App\Models\Device::create([
                'user_id' => $user->id,
                'device_id' => $currentDeviceId,
                'device_name' => $currentBrowserName,
                'platform' => $currentPlatform,
                'active_profile' => $profile->id ?? null,
            ]);
        } else {
            // Always update device info to ensure it's current (browser name/platform might have changed)
            $your_device->update([
                'device_name' => $currentBrowserName,
                'platform' => $currentPlatform,
            ]);
        }

        return view('frontend::accountSetting', compact('subscriptions', 'user', 'your_device', 'other_devices'));
    }

    /**
     * Profile Management (minimal) - passes only variables required by the blade
     */
    public function profileManagement()
    {
        if (getCurrentProfileSession('is_child_profile') == 1) {
            return redirect()->route('user.login');
        }

        $profiles = UserMultiProfile::where('user_id', auth()->id())->get();
        $userProfile = UserMultiProfileResource::collection($profiles);
        $profileCount = $profiles->count();
        $isChildProfile = getCurrentProfileSession('is_child_profile') == 1;

        return view('frontend::profileManagement', compact('userProfile', 'profileCount', 'isChildProfile'));
    }


    public function deviceLogout(Request $request)
    {
        $userId = auth()->user()->id;

        $deviceQuery = Device::where('user_id', $userId);

        if ($request->has('device_id')) {
            $deviceQuery->where('device_id', $request->device_id);
        }

        if ($request->has('id')) {
            $deviceQuery->orWhere('id', $request->id);
        }

        $device = $deviceQuery->first();
        if (!$device) {
            return redirect()->back()->with('error', __('users.device_not_found'));
        }

        $deviceIdToLogout = $device->device_id;

        // Revoke sanctum tokens for this specific device (token name = device_id)
        try {
            $user = User::find($userId);
            if ($user && class_exists('Laravel\\Sanctum\\PersonalAccessToken')) {
                \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $userId)
                    ->where('name', $deviceIdToLogout)
                    ->delete();
            }
        } catch (\Throwable $e) {
            // best-effort; ignore errors revoking tokens
        }

        $device->delete();

        try {
            DB::table('sessions')
                ->where('user_id', $userId)
                ->where('ip_address', $deviceIdToLogout)
                ->delete();
        } catch (\Throwable $e) {
            // ignore if sessions table not present
        }

        return redirect()->back();
    }


    public function faq()
    {
        $content = FAQ::where('status',1)->get();
        return view('frontend::faq',compact('content'));
    }


  public function PaymentHistory()
{

    if(getCurrentProfileSession('is_child_profile') == 1){
        return redirect()->route('user.login');
    }

    // $perPage = setting('data_table_limit',10);
    $perPage = 10;
    $subscriptions = Subscription::where('user_id', auth()->id())
        ->with('subscription_transaction', 'plan')
        ->orderBy('id', 'desc')
        ->paginate($perPage);

    $activeSubscriptions = Subscription::where('user_id', auth()->id())
        ->where('status', 'active')
        ->where('end_date', '>', now())
        ->orderBy('id', 'desc')
        ->first();

    return view('frontend::paymentHistory', compact('activeSubscriptions', 'subscriptions'));
}



    public function transactionHistory()
    {
        if(getCurrentProfileSession('is_child_profile') == 1){
            return redirect()->route('user.login');
        }
        $perPage = setting('data_table_limit',10);
        $payPerViews = PayPerView::where('user_id', auth()->id())
        ->with(['movie', 'episode', 'video'])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

        return view('frontend::transactionHistory', compact('payPerViews'));
    }

    public function payPerViewInvoice(int $id)
    {
        set_time_limit(0);
        $ppv = PayPerView::where('user_id', auth()->id())
            ->with(['movie', 'episode', 'video', 'user', 'PayperviewTransaction'])
            ->findOrFail($id);

        $pdf = PDF::loadView('frontend::components.partials.pay-per-view', ['ppv' => $ppv])
            ->setOptions([
                'defaultFont' => 'Noto Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ]);

        $fileName = 'ppv-invoice-' . $ppv->id . '.pdf';
        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            $fileName,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]
        );
    }

    public function allReview(int $id)
    {
        $entertainment = Entertainment::where('id', $id)->where('status', 1)->where('deleted_at', null)->first();
        if(!$entertainment){
            return redirect()->route('user.login');
        }
        $reviews = $entertainment->entertainmentReviews;
        $ratingCounts = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];
        foreach ($reviews as $review) {
            if (isset($ratingCounts[$review->rating])) {
                $ratingCounts[$review->rating]++;
            }
        }
        $totalRating = $reviews->sum('rating');
        $reviewCount = $reviews->count();
        $averageRating = $reviewCount > 0 ? $totalRating / $reviewCount : 0;

        return view('frontend::review', compact('entertainment', 'reviews', 'averageRating', 'ratingCounts', 'reviewCount'));
    }

    public function EpisodeDetails()
    {
        return view('frontend::episode_detail');
    }

    public function VideoDetails()
    {
     return view('frontend::video_detail');
    }

    public function profile()
    {
        return view('frontend::components.user.profile');
    }

    public function cancelSubscription(Request $request)
    {
        try {
            $planId = $request->input('plan_id');
            $subscription = Subscription::where('user_id', auth()->id())
                ->where('id', $request->id)
                ->where('status', 'active')
                ->first();

            if (!$subscription) {
                return response()->json(['success' => false, 'message' => 'Subscription not found'], 404);
            }

            $subscription->update(['status' => 'cancel']);

            $otherSubscription = Subscription::where('user_id', auth()->id())
                ->where('status', 'active')->get();

            if($otherSubscription->isEmpty()){
                $user = User::where('id', auth()->id())->first();
                $user->update(['is_subscribe' => 0]);
            }

            // Send notification for subscription cancellation
            $this->sendNotificationOnsubscription('cancle_subscription', $subscription);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function decryptUrl(Request $request)
  {
      $encryptedUrl = $request->input('encrypted_url');

      try {
          $decryptedUrl = Crypt::decryptString($encryptedUrl);
          return response()->json(['url' => $decryptedUrl], 200);
      } catch (\Exception $e) {
          return response()->json(['error' => 'Invalid URL'], 400);
      }
  }
public function getPaymentDetails(Request $request)
{
    $planId = $request->input('plan_id');
    $promotionId = $request->input('promotion_id'); // Optional promotion
    $plan = Plan::find($planId);
    $discount_percentage = $plan->discount_percentage;

    $discount_amount = ($discount_percentage * $plan->price) / 100;

    $taxes = Tax::where('status', 1)->get();
    $baseAmount = $plan->total_price;
    $totalTaxamount = 0;
    $taxesArray = [];

    $subtotalBeforePromotion = $baseAmount;
    $promotionDiscountAmount = 0;

    // Apply promotion if provided
    if ($promotionId) {
        $promotion = Coupon::where('id', $promotionId)
            ->where('status', 1)
            ->whereHas('subscriptionPlans', function ($query) use ($planId) {
                $query->where('subscription_plan_id', $planId);
            })
            ->first();

        if ($promotion) {
            if ($promotion->discount_type === 'percentage') {
                $promotionDiscountAmount = ($promotion->discount * $baseAmount) / 100;
            } elseif ($promotion->discount_type === 'fixed') {
                $promotionDiscountAmount = $promotion->discount;
            }
        } else {
            return response()->json(['error' => 'Invalid or expired promotion.'], 400);
        }
    }

    // Calculate the subtotal after applying the promotion discount
    $subtotalAfterPromotion = max(0, $subtotalBeforePromotion - $promotionDiscountAmount);

    // Recalculate taxes based on the updated subtotal
    foreach ($taxes as $tax) {
        $taxAmount = 0;

        if (strtolower($tax->type) == 'fixed') {
            $taxAmount = $tax->value;
        } elseif (strtolower($tax->type) == 'percentage') {
            $taxAmount = ($subtotalAfterPromotion * $tax->value) / 100;
        }

        $taxesArray[] = [
            'name' => $tax->title,
            'type' => $tax->type,
            'value' => $tax->value,
            'tax_amount' => $taxAmount
        ];

        $totalTaxamount += $taxAmount;
    }

    // Calculate the total amount
    $totalAfterPromotion = $subtotalAfterPromotion + $totalTaxamount;

    return response()->json([
        'price' => $plan->price,
        'total_price' => $plan->total_price,
        'subtotal' => $subtotalAfterPromotion,
        'discount_percentage' => $discount_percentage,
        'plan_discount_amount' => $discount_amount,
        'tax' => $totalTaxamount,
        'tax_array' => $taxesArray,
        'promotion_id' => $promotionId,
        'promotion_discount_amount' => $promotionDiscountAmount,
        'total' => $totalAfterPromotion,
    ]);
}

    public function checkSubscription($planId)
   {
    $user = auth()->user();
    
    // Check for subscriptions that are either active OR have a valid end date in the future
    $currentSubscription = Subscription::where('user_id', $user->id)
        ->where(function ($query) {
            $query->where('status', 'active')
                  ->orWhere('end_date', '>', now());
        })
        ->get();

    $planData = Plan::find($planId);

    if(!$planData) {
        return response()->json(['isActive' => false]);
    }

    $level = $planData->level;

    foreach($currentSubscription as $plan)
    {
        $currentLevel = $plan->level;
        
        if (is_null($currentLevel)) {
             $p = Plan::find($plan->plan_id);
             if($p) $currentLevel = $p->level;
        }

        if ($currentLevel >= $level) {
            return response()->json(['isActive' => true]);
        }
    }
    return response()->json(['isActive' => false]);
   }


   public function checkDeviceType() {
        $checkDeviceType = Subscription::checkPlanSupportDevice(auth()->id());
        return $checkDeviceType;
    }



    public function downloadInvoice(Request $request)
    {
        set_time_limit(0);

        $data = Subscription::where('user_id', auth()->id())
        ->with('plan','subscription_transaction','user')->find($request->id);
            if (!$data) {
                return response()->json(['status' => false, 'message' => 'subscription not found'], 404);
            }

        $pdf = PDF::loadView('frontend::components.partials.invoice', compact('data'))
        ->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
        ]);
        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            "invoice.pdf",
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="invoice.pdf"',
            ]
        );
    }

    /**
     * Display content list page (Latest Movies, Popular Movies, Popular TV Shows)
     */
    public function contentList(Request $request, $type)
    {
        // Validate type
        $validTypes = ['latest-movies', 'popular-movies', 'popular-tv-shows', 'top-rated-movies', 'most-watched-videos', 'trending-movies', 'free-movies', 'liked-movies'];
        if (!in_array($type, $validTypes)) {
            abort(404);
        }

        // Set title based on type
        $titles = [
            'latest-movies' => __('frontend.latest_movie'),
            'popular-movies' => __('frontend.popular_movie'),
            'popular-tv-shows' => __('frontend.popular_tvshow'),
            'top-rated-movies' => __('frontend.top_rated_movies'),
            'most-watched-videos' => __('frontend.most_watched_videos'),
            'trending-movies' => __('frontend.tranding_in_country'),
            'free-movies' => __('frontend.free_movie'),
            'liked-movies' => __('frontend.liked_movie'),
        ];

        return view('frontend::content-list', [
            'type' => $type,
            'title' => $titles[$type] ?? __('frontend.content'),
        ]);
    }

    /**
     * Display custom section list page (dashboard dynamic sections)
     */
    public function customSectionList(Request $request, $slug)
    {
        $section = MobileSetting::select('id', 'slug', 'type', 'value', 'name')
            ->where('slug', $slug)
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->first();

        $validTypes = ['movie', 'tvshow', 'video', 'channel'];
        if (!$section || !in_array($section->type, $validTypes, true)) {
            abort(404);
        }

        $ids = json_decode($section->value, true);
        if (empty($ids) || !is_array($ids)) {
            abort(404);
        }

        return view('frontend::content-list', [
            'type' => 'custom-section',
            'title' => $section->name ?? __('frontend.content'),
            'apiEndpoint' => "/api/content-list/custom-section?slug={$section->slug}",
        ]);
    }

    /**
     * Get content list data via AJAX
     */
    public function getContentListData(Request $request, $type)
    {
        $perPage = $request->get('per_page', 12);
        $page = $request->get('page', 1);
        $isAjax = $request->get('is_ajax', 0);

        $user_id = auth()->id();
        $profile_id = $request->profile_id ?? getCurrentProfile($user_id, $request);

        // Apply restriction filters
        $isRestricted = $request->has('is_restricted') ? $request->is_restricted : null;
        $isChildProfile = !empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0;

        // Handle custom sections (dashboard dynamic sections)
        if ($type === 'custom-section') {
            $slug = $request->get('slug');
            $section = MobileSetting::select('id', 'slug', 'type', 'value', 'name')
                ->where('slug', $slug)
                ->whereNotNull('type')
                ->where('type', '!=', '')
                ->first();

            $validTypes = ['movie', 'tvshow', 'video', 'channel'];
            if (!$section || !in_array($section->type, $validTypes, true)) {
                return response()->json([
                    'html' => '',
                    'hasMore' => false,
                    'currentPage' => (int) $page,
                ]);
            }

            $ids = json_decode($section->value, true);
            if (empty($ids) || !is_array($ids)) {
                return response()->json([
                    'html' => '',
                    'hasMore' => false,
                    'currentPage' => (int) $page,
                ]);
            }

            $ids = array_values(array_filter($ids, fn ($id) => !empty($id)));
            $ids = array_map('intval', $ids);
            if (empty($ids)) {
                return response()->json([
                    'html' => '',
                    'hasMore' => false,
                    'currentPage' => (int) $page,
                ]);
            }

            if ($section->type === 'video') {
                $videoQuery = Video::with(['plan:id,level'])
                    ->select([
                        'id', 'name', 'slug', 'poster_url', 'plan_id', 'status', 'thumbnail_url',
                        'is_restricted', 'duration', 'release_date', 'description',
                        'trailer_url', 'video_url_input', 'access', 'price', 'poster_tv_url'
                    ])
                    ->whereIn('id', $ids)
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->whereDate('release_date', '<=', now());

                if ($isRestricted !== null) {
                    $videoQuery->where('is_restricted', $isRestricted);
                }

                if ($isChildProfile) {
                    $videoQuery->where('is_restricted', 0);
                }

                $videoQuery->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
                $videos = $videoQuery->paginate($perPage);

                $transformedVideos = VideoResourceV3::collection($videos->items())->toArray($request);

                if ($isAjax) {
                    $html = '';
                    foreach ($transformedVideos as $video) {
                        $html .= view('frontend::components.card.card_video', ['values' => [$video]])->render();
                    }

                    return response()->json([
                        'html' => $html,
                        'hasMore' => $videos->hasMorePages(),
                        'currentPage' => $videos->currentPage(),
                    ]);
                }

                return response()->json([
                    'data' => $transformedVideos,
                    'pagination' => [
                        'current_page' => $videos->currentPage(),
                        'last_page' => $videos->lastPage(),
                        'per_page' => $videos->perPage(),
                        'total' => $videos->total(),
                        'has_more' => $videos->hasMorePages(),
                    ],
                ]);
            }

            if ($section->type === 'channel') {
                $channelQuery = LiveTvChannel::with(['plan:id,level', 'TvCategory:id,name'])
                    ->select(['id', 'name', 'slug', 'plan_id', 'poster_url', 'thumb_url', 'poster_tv_url', 'trailer_url', 'access', 'category_id'])
                    ->whereIn('id', $ids)
                    ->where('status', 1)
                    ->whereNull('deleted_at');

                $channelQuery->orderByRaw('FIELD(id, ' . implode(',', $ids) . ')');
                $channels = $channelQuery->paginate($perPage);
                $transformedChannels = LiveTvChannelResourceV3::collection($channels->items())->toArray($request);

                if ($isAjax) {
                    $html = '';
                    foreach ($transformedChannels as $channel) {
                        $html .= view('frontend::components.card.card_tvchannel', ['value' => $channel])->render();
                    }

                    return response()->json([
                        'html' => $html,
                        'hasMore' => $channels->hasMorePages(),
                        'currentPage' => $channels->currentPage(),
                    ]);
                }

                return response()->json([
                    'data' => $transformedChannels,
                    'pagination' => [
                        'current_page' => $channels->currentPage(),
                        'last_page' => $channels->lastPage(),
                        'per_page' => $channels->perPage(),
                        'total' => $channels->total(),
                        'has_more' => $channels->hasMorePages(),
                    ],
                ]);
            }

            $query = Entertainment::with([
                'genresdata:id,name',
                'plan:id,level'
            ])
            ->select([
                'entertainments.id',
                'entertainments.name',
                'entertainments.slug',
                'entertainments.type',
                'entertainments.release_date',
                'entertainments.plan_id',
                'entertainments.description',
                'entertainments.trailer_url_type',
                'entertainments.is_restricted',
                'entertainments.language',
                'entertainments.IMDb_rating',
                'entertainments.content_rating',
                'entertainments.duration',
                'entertainments.video_upload_type',
                'entertainments.poster_url',
                'entertainments.thumbnail_url',
                'entertainments.poster_tv_url',
                'entertainments.trailer_url as base_url',
                'entertainments.trailer_url',
                'entertainments.video_url_input',
                'entertainments.movie_access',
                'entertainments.price',
                'entertainments.purchase_type',
                'entertainments.access_duration',
                'entertainments.discount',
                'entertainments.available_for',
            ])
            ->addSelect([
                'watched_time' => ContinueWatch::select('watched_time')
                    ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
                    ->where('profile_id', $profile_id)
                    ->where('user_id', $user_id)
                    ->limit(1)
            ])
            ->withCount(['watchlist as is_watch_list' => function ($q) use ($user_id) {
                $q->where('user_id', $user_id);
            }])
            ->where('entertainments.status', 1)
            ->whereNull('entertainments.deleted_at')
            ->whereDate('entertainments.release_date', '<=', now())
            ->where('entertainments.type', $section->type)
            ->whereIn('entertainments.id', $ids);

            if ($isRestricted !== null) {
                $query->where('entertainments.is_restricted', $isRestricted);
            }

            if ($isChildProfile) {
                $query->where('entertainments.is_restricted', 0);
            }

            $query->orderByRaw('FIELD(entertainments.id, ' . implode(',', $ids) . ')');

            $items = $query->paginate($perPage);
            $transformedData = CommonContentResourceV3::collection($items->items())->toArray($request);

            if ($isAjax) {
                $html = '';
                foreach ($transformedData as $item) {
                    $itemType = $item['type'] ?? 'movie';
                    if ($itemType === 'tvshow') {
                        $html .= view('frontend::components.card.card_tvshow', ['values' => [$item]])->render();
                    } else {
                        $html .= view('frontend::components.card.card_movie', ['values' => [$item]])->render();
                    }
                }

                return response()->json([
                    'html' => $html,
                    'hasMore' => $items->hasMorePages(),
                    'currentPage' => $items->currentPage(),
                ]);
            }

            return response()->json([
                'data' => $transformedData,
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'has_more' => $items->hasMorePages(),
                ],
            ]);
        }

        // Handle videos separately as they use a different table
        if ($type === 'most-watched-videos') {
            // Most watched videos from user_watch_histories table where entertainment_type = 'video'
            $mostWatchedVideoIds = \App\Models\UserWatchHistory::select('entertainment_id', DB::raw('COUNT(*) as watch_count'))
                ->where('entertainment_type', 'video')
                ->groupBy('entertainment_id')
                ->orderByRaw('COUNT(*) DESC')
                ->pluck('entertainment_id');

            $videoQuery = Video::with(['plan:id,level'])
                ->select([
                    'id', 'name', 'slug', 'poster_url', 'plan_id', 'status', 'thumbnail_url',
                    'is_restricted', 'duration', 'release_date', 'description',
                    'trailer_url', 'video_url_input', 'access', 'price', 'poster_tv_url'
                ])
                ->whereIn('id', $mostWatchedVideoIds)
                ->where('status', 1)
                ->whereNull('deleted_at');

            if ($isRestricted !== null) {
                $videoQuery->where('is_restricted', $isRestricted);
            }

            if ($isChildProfile) {
                $videoQuery->where('is_restricted', 0);
            }

            if ($mostWatchedVideoIds->isNotEmpty()) {
                $videoQuery->orderByRaw('FIELD(id, ' . $mostWatchedVideoIds->implode(',') . ')');
            } else {
                $videoQuery->orderBy('id', 'desc');
            }

            $videos = $videoQuery->paginate($perPage);
            $transformedVideos = VideoResourceV3::collection($videos->items())->toArray($request);

            if ($isAjax) {
                $html = '';
                foreach ($transformedVideos as $video) {
                    $html .= view('frontend::components.card.card_video', ['values' => [$video]])->render();
                }

                return response()->json([
                    'html' => $html,
                    'hasMore' => $videos->hasMorePages(),
                    'currentPage' => $videos->currentPage(),
                ]);
            }

            return response()->json([
                'data' => $transformedVideos,
                'pagination' => [
                    'current_page' => $videos->currentPage(),
                    'last_page' => $videos->lastPage(),
                    'per_page' => $videos->perPage(),
                    'total' => $videos->total(),
                    'has_more' => $videos->hasMorePages(),
                ],
            ]);
        }

        $query = Entertainment::with([
            'genresdata:id,name',
            'plan:id,level'
        ])
        ->select([
            'entertainments.id',
            'entertainments.name',
            'entertainments.slug',
            'entertainments.type',
            'entertainments.release_date',
            'entertainments.plan_id',
            'entertainments.description',
            'entertainments.trailer_url_type',
            'entertainments.is_restricted',
            'entertainments.language',
            'entertainments.IMDb_rating',
            'entertainments.content_rating',
            'entertainments.duration',
            'entertainments.video_upload_type',
            'entertainments.poster_url',
            'entertainments.thumbnail_url',
            'entertainments.poster_tv_url',
            'entertainments.trailer_url as base_url',
            'entertainments.trailer_url',
            'entertainments.video_url_input',
            'entertainments.movie_access',
            'entertainments.price',
            'entertainments.purchase_type',
            'entertainments.access_duration',
            'entertainments.discount',
            'entertainments.available_for',
        ])
        ->addSelect([
            'watched_time' => ContinueWatch::select('watched_time')
                ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
                ->where('profile_id', $profile_id)
                ->where('user_id', $user_id)
                ->limit(1)
        ])
        ->withCount(['watchlist as is_watch_list' => function ($q) use ($user_id) {
            $q->where('user_id', $user_id);
        }])
        ->where('entertainments.status', 1)
        ->whereNull('entertainments.deleted_at')
        ->whereDate('entertainments.release_date', '<=', now());

        // Apply type-specific filters
        switch ($type) {
            case 'latest-movies':
                // Movies from last 1 year
                $sixMonthsAgo = Carbon::now()->subMonths(12);
                $query->where('entertainments.type', 'movie')
                      ->whereDate('entertainments.release_date', '>=', $sixMonthsAgo);
                break;

            case 'popular-movies':
                // Most viewed movies from entertainment_view table
                $mostViewedMovieIds = EntertainmentViewModel::select('entertainment_id', DB::raw('COUNT(*) as view_count'))
                    ->groupBy('entertainment_id')
                    ->orderByRaw('COUNT(*) DESC')
                    ->pluck('entertainment_id');

                $query->where('entertainments.type', 'movie')
                      ->whereIn('entertainments.id', $mostViewedMovieIds)
                      ->orderByRaw('FIELD(entertainments.id, ' . ($mostViewedMovieIds->isNotEmpty() ? $mostViewedMovieIds->implode(',') : '0') . ')');
                break;

            case 'popular-tv-shows':
                // Most viewed TV shows from entertainment_view table
                $mostViewedTvShowIds = EntertainmentViewModel::select('entertainment_id', DB::raw('COUNT(*) as view_count'))
                    ->groupBy('entertainment_id')
                    ->orderByRaw('COUNT(*) DESC')
                    ->pluck('entertainment_id');

                $query->where('entertainments.type', 'tvshow')
                      ->whereIn('entertainments.id', $mostViewedTvShowIds)
                      ->orderByRaw('FIELD(entertainments.id, ' . ($mostViewedTvShowIds->isNotEmpty() ? $mostViewedTvShowIds->implode(',') : '0') . ')');
                break;

            case 'top-rated-movies':
                // Top-rated movies with IMDb rating >= 8
                $query->where('entertainments.type', 'movie')
                      ->whereRaw('CAST(entertainments.IMDb_rating AS DECIMAL(3,1)) >= ?', [8.0])
                      ->orderByRaw('CAST(entertainments.IMDb_rating AS DECIMAL(3,1)) DESC')
                      ->orderBy('entertainments.id', 'desc');
                break;

            case 'trending-movies':
                // Trending movies by country
                $user = auth()->user();
                if ($user) {
                    $trendingMovieIds = $this->recommendationService->getTrendingMoviesByCountry($user);
                    if (!empty($trendingMovieIds)) {
                        $query->where('entertainments.type', 'movie')
                              ->whereIn('entertainments.id', $trendingMovieIds)
                              ->orderByRaw('FIELD(entertainments.id, ' . implode(',', $trendingMovieIds) . ')');
                    } else {
                        // If no country found, return empty result
                        $query->where('entertainments.id', 0);
                    }
                } else {
                    // If user not logged in, return empty result
                    $query->where('entertainments.id', 0);
                }
                break;

            case 'liked-movies':
                // Most liked movies
                $user = auth()->user();
                if ($user) {
                    $likedMovieIds = $this->recommendationService->getLikedMovies($user, $profile_id)->toArray();
                    if (!empty($likedMovieIds)) {
                        $query->where('entertainments.type', 'movie')
                              ->whereIn('entertainments.id', $likedMovieIds)
                              ->orderByRaw('FIELD(entertainments.id, ' . implode(',', $likedMovieIds) . ')');
                    } else {
                        $query->where('entertainments.id', 0);
                    }
                } else {
                    $query->where('entertainments.id', 0);
                }
                break;

            case 'free-movies':
                // Movies with movie_access = 'free'
                $query->where('entertainments.type', 'movie')
                      ->where('entertainments.movie_access', 'free');
                break;

        }

        // Apply restriction filters
        if ($isRestricted !== null) {
            $query->where('entertainments.is_restricted', $isRestricted);
        }

        if ($isChildProfile) {
            $query->where('entertainments.is_restricted', 0);
        }

        // Default ordering if not already set
        if ($type !== 'popular-movies' && $type !== 'popular-tv-shows' && $type !== 'top-rated-movies' && $type !== 'trending-movies') {
            $query->orderBy('entertainments.release_date', 'desc')
                  ->orderBy('entertainments.id', 'desc');
        }

        $items = $query->paginate($perPage);

        // Transform data
        $transformedData = CommonContentResourceV3::collection($items->items())->toArray($request);

        if ($isAjax) {
            // Generate HTML for AJAX response
            $html = '';
            foreach ($transformedData as $item) {
                $itemType = $item['type'] ?? 'movie';
                if ($itemType === 'tvshow') {
                    $html .= view('frontend::components.card.card_tvshow', ['values' => [$item]])->render();
                } else {
                    $html .= view('frontend::components.card.card_movie', ['values' => [$item]])->render();
                }
            }

            return response()->json([
                'html' => $html,
                'hasMore' => $items->hasMorePages(),
                'currentPage' => $items->currentPage(),
            ]);
        }

        return response()->json([
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'has_more' => $items->hasMorePages(),
            ],
        ]);
    }

}


