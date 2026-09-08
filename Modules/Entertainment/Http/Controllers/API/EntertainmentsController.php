<?php

namespace Modules\Entertainment\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use App\Models\MobileSetting;
use Modules\Entertainment\Models\Entertainment;
use Modules\Entertainment\Transformers\SeasonResourceV3;
use Modules\Entertainment\Transformers\EpisodeResourceV3;
use Modules\Entertainment\Transformers\MovieDetailDataResource;;
use Modules\Entertainment\Transformers\TvshowDetailResource;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\Like;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Episode\Models\Episode;
use Modules\Entertainment\Transformers\EpisodeResource;
use Modules\Entertainment\Transformers\EpisodeDetailResource;
use Modules\Entertainment\Transformers\SearchResource;
use Carbon\Carbon;
use Modules\Entertainment\Models\UserReminder;
use Modules\Entertainment\Models\EntertainmentView;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Genres\Models\Genres;
use Modules\Video\Models\Video;
use Modules\Season\Models\Season;
use Modules\CastCrew\Models\CastCrew;
use Modules\CastCrew\Transformers\CastCrewListResource;
use Modules\Entertainment\Transformers\EpisodeDetailResourceV2;
use Modules\Entertainment\Transformers\MovieDetailDataResourceV2;
use Modules\Entertainment\Transformers\MoviesResourceV2;
use Modules\Entertainment\Transformers\TvshowDetailResourceV2;
use Modules\Entertainment\Transformers\TvshowResourceV2;
use Illuminate\Support\Facades\DB;
use Modules\Entertainment\Models\Subtitle;
use Modules\Entertainment\Transformers\CommonContentDetails;
use Modules\Subscriptions\Models\Subscription;
use Modules\Entertainment\Models\EntertainmentStreamContentMapping;
use Modules\Episode\Models\EpisodeStreamContentMapping;
use Modules\Entertainment\Models\EntertainmnetDownloadMapping;
use Modules\Episode\Models\EpisodeDownloadMapping;
use Modules\Ad\Models\CustomAdsSetting;
use Modules\Ad\Models\VastAdsSetting;
use Modules\Entertainment\Models\EntertainmentGenerMapping;
use Modules\Video\Models\VideoStreamContentMapping;
use Modules\Video\Models\VideoDownloadMapping;
use Modules\Entertainment\Transformers\CommonContentList;
use Modules\Entertainment\Transformers\MoviesResourceV3;
use Modules\Entertainment\Transformers\TvshowResourceV3;
use Modules\Video\Transformers\VideoResourceV3;
use Modules\Entertainment\Transformers\Backend\CommonContentResourceV3;
use Modules\Entertainment\Transformers\Backend\ComingSoonResourceV3;
use Illuminate\Support\Facades\Cache;
use Modules\Entertainment\Transformers\ContentDetailsCastCrewV3;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\LiveTV\Transformers\LiveTvChannelResourceV3;
use Modules\Frontend\Models\PayPerView;
use App\Services\RecommendationServiceV3;
use App\Models\User;
class EntertainmentsController extends Controller
{
    protected $recommendationService;

    public function __construct(RecommendationServiceV3 $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function movieListV3(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $user_id = $request->input('user_id') ?? auth()->id();
        $profile_id = getCurrentProfile($user_id, $request);
       
        // OPTIMIZATION: Limit select columns to reduce data transfer
        $movieList = Entertainment::select([
            'id', 'name', 'slug', 'description', 'type', 'trailer_url_type', 'trailer_url',
            'movie_access', 'IMDb_rating', 'imdb_rating', 'plan_id', 'language',
            'duration', 'release_date', 'poster_url', 'bunny_video_url', 'is_restricted'
        ])
            ->where('deleted_at', null)
            ->where('status', 1)
            ->released();

        if (empty($request->language) && empty($request->genre_id) && empty($request->actor_id)) {
            $movieList->where('type', 'movie');
        }

        if ($request->has('is_restricted')) {
            $movieList->where('is_restricted', $request->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile'))) {
            $movieList->where('is_restricted', 0);
        }

        // OPTIMIZATION: Eager load relationships with nested relationships to avoid N+1
        $movieList->with([
            'entertainmentGenerMappings.genre:id,name', // Eager load genre to avoid N+1 in transformer
            'plan:id,level',
            'entertainmentTalentMappings.talentprofile:id,name,type,file_url'
        ]);

        // OPTIMIZATION: Batch load watchlist status using withCount to avoid N+1 in transformer
        if ($user_id && $profile_id) {
            $movieList->withCount([
                'watchlist as is_watch_list' => function ($q) use ($user_id, $profile_id) {
                    $q->where('user_id', $user_id)
                      ->where('profile_id', $profile_id)
                      ->where('type', 'movie');
                }
            ]);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $movieList->where('name', 'like', "%{$searchTerm}%");
        }

        // OPTIMIZATION: Use whereIn with subquery instead of whereHas for better performance
        if ($request->filled('genre_id')) {
            $genreId = $request->genre_id;
            $movieList->whereIn('id', function($query) use ($genreId) {
                $query->select('entertainment_id')
                    ->from('entertainment_gener_mapping')
                    ->where('genre_id', $genreId);
            });
        }

        // OPTIMIZATION: Use whereIn with subquery instead of whereHas for better performance
        if ($request->filled('actor_id')) {
            $actorId = $request->actor_id;

            $movieList->whereIn('id', function($query) use ($actorId) {
                $query->select('entertainment_id')
                    ->from('entertainment_talent_mapping')
                    ->where('talent_id', $actorId);
            });

            $allowedTypes = [];
            if (isenablemodule('movie')) $allowedTypes[] = 'movie';
            if (isenablemodule('tvshow')) $allowedTypes[] = 'tvshow';

            if (!empty($allowedTypes)) {
                $movieList->whereIn('type', $allowedTypes);
            }
        }

        if ($request->filled('language')) {
            $movieList->where('language', $request->language);
        }

        // OPTIMIZATION: Use cursorPaginate for better performance with large datasets and instant loading on scroll
        // Cursor pagination is more efficient than offset-based pagination for large datasets
        $movies = $movieList->orderByDesc('id')->simplePaginate($perPage);

        // OPTIMIZATION: Batch load purchased IDs to avoid N+1 in transformer
        $purchasedIds = [];
        if ($user_id) {
            $purchasedItems = PayPerView::where('user_id', $user_id)
                ->where(function($q) {
                    $q->whereNull('view_expiry_date')
                      ->orWhere('view_expiry_date', '>', now());
                })
                ->where(function($q) {
                    $q->whereNull('first_play_date')
                      ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL available_for DAY) > ?', [now()]);
                })
                ->select('movie_id', 'type')
                ->get();
            
            // Group by type for efficient lookup
            foreach ($purchasedItems as $item) {
                if (!isset($purchasedIds[$item->type])) {
                    $purchasedIds[$item->type] = [];
                }
                $purchasedIds[$item->type][] = $item->movie_id;
            }
        }

        // OPTIMIZATION: Eager load user's subscription package to avoid N+1 in transformer
        $user = null;
        if ($user_id) {
            $user = \App\Models\User::with('subscriptionPackage:id,level')
                ->where('id', $user_id)
                ->first();
        }

        // Pass purchasedIds and user to transformer via collection
        $movies->getCollection()->transform(function($movie) use ($purchasedIds, $user) {
            $movie->preloaded_purchased_ids = $purchasedIds;
            $movie->preloaded_user = $user;
            return $movie;
        });

        $responseData = CommonContentResourceV3::collection($movies);

        if ($request->boolean('is_ajax')) {
            $html = '';

            if (!empty($responseData)) {
                $html .= view('frontend::components.card.card_movie', ['values' => $responseData->toArray($request)])->render();
            }

            // OPTIMIZATION: Return cursor pagination metadata for frontend
            return ApiResponse::success(
                $responseData,
                __('movie.movie_list'),
                200,
                [
                    'html' => $html, 
                    'hasMore' => $movies->hasMorePages(),
                    // 'nextCursor' => $movies->nextCursor()?->encode(),
                    // 'nextPageUrl' => $movies->nextPageUrl()
                ]
            );
        }

        return ApiResponse::success($responseData, __('movie.movie_list'), 200);
    }

    public function tvshowListV3(Request $request)
    {
        $perPage   = $request->input('per_page', 10);
        $userId    = $request->input('user_id') ?? auth()->id();
        $profileId = $request->input('profile_id') ?? getCurrentProfile($userId, $request);
    
        /**
         * IMPORTANT:
         * - Genre mapping MUST exist (same as original)
         * - At least 1 episode MUST exist (same as whereHas('episodeV2'))
         * - Do NOT filter episode status / deleted_at (same behavior)
         */
        $tvshowList = Entertainment::query()
            ->select([
                'entertainments.id',
                'entertainments.name',
                'entertainments.slug',
                'entertainments.description',
                'entertainments.type',
                'entertainments.plan_id',
                'entertainments.language',
                'entertainments.imdb_rating',
                'entertainments.content_rating',
                'entertainments.release_date',
                'entertainments.is_restricted',
                'entertainments.status',
                'entertainments.poster_url',
                'entertainments.poster_tv_url',
                'entertainments.thumbnail_url',
                'entertainments.trailer_url',
                'entertainments.trailer_url_type',
                'entertainments.movie_access',
            ])
    
            // SAME as whereHas('episodeV2') but much faster
            ->join('episodes', 'episodes.entertainment_id', '=', 'entertainments.id')
    
            // SAME as original (genre mapping must exist)
            ->join('entertainment_gener_mapping as egm', 'egm.entertainment_id', '=', 'entertainments.id')
    
            ->leftJoin('plan', 'plan.id', '=', 'entertainments.plan_id')
    
            // Eager loading to remove N+1
            ->with([
                'plan:id,level',
                'entertainmentGenerMappings.genre:id,name',
                // Load only ONE episode (display purpose only)
                'episodeV2' => function ($q) {
                    $q->select('id', 'entertainment_id', 'name')->limit(1);
                },
            ])
    
            ->where('entertainments.type', 'tvshow')
            ->where('entertainments.release_date', '<=', now()->format('Y-m-d'))
            ->where('entertainments.status', 1)
            ->whereNull('entertainments.deleted_at')
    
            // Avoid duplicates due to joins
            ->groupBy('entertainments.id')
            ->orderByDesc('entertainments.id');
    
        /* ---------------- SEARCH ---------------- */
        if ($request->filled('search')) {
            $search = $request->search;
    
            $tvshowList->where(function ($q) use ($search) {
                $q->where('entertainments.name', 'like', "%{$search}%")
                  ->orWhereExists(function ($sub) use ($search) {
                      $sub->select(DB::raw(1))
                          ->from('entertainment_gener_mapping as egm2')
                          ->join('genres as g', 'g.id', '=', 'egm2.genre_id')
                          ->whereColumn('egm2.entertainment_id', 'entertainments.id')
                          ->where('g.name', 'like', "%{$search}%");
                  });
            });
        }
    
        /* ---------------- RESTRICTION FILTERS ---------------- */
        if ($request->has('is_restricted')) {
            $tvshowList->where('entertainments.is_restricted', $request->is_restricted);
        }
    
        if (!empty(getCurrentProfileSession('is_child_profile'))) {
            $tvshowList->where('entertainments.is_restricted', 0);
        }
    
        /* ---------------- PAGINATION ---------------- */
        $tvshows = $tvshowList->simplePaginate($perPage);
    
        /* ---------------- EARLY EXIT ---------------- */
        if ($tvshows->isEmpty()) {
            if ($request->boolean('is_ajax')) {
                return ApiResponse::success(
                    null,
                    __('movie.tvshow_list'),
                    200,
                    ['html' => '', 'hasMore' => false]
                );
            }
            return ApiResponse::success([], __('movie.tvshow_list'), 200);
        }
    
        /* ---------------- WATCHLIST (NO N+1) ---------------- */
        $watchlistIds = [];
        if ($userId) {
            $watchlistIds = Watchlist::whereIn(
                    'entertainment_id',
                    $tvshows->pluck('id')->toArray()
                )
                ->where('user_id', $userId)
                ->where('profile_id', $profileId)
                ->where('type', 'tvshow')
                ->pluck('entertainment_id')
                ->toArray();
        }
    
        // Inject computed values to models
        $tvshows->getCollection()->transform(function ($tvshow) use ($watchlistIds) {
            $tvshow->plan_level   = $tvshow->plan->level ?? null;
            $tvshow->is_watch_list = in_array($tvshow->id, $watchlistIds);
            return $tvshow;
        });
    
        /* ---------------- RESPONSE ---------------- */
        $responseData = CommonContentResourceV3::collection($tvshows)->toArray($request);
    
        if ($request->boolean('is_ajax')) {
            $html = view('frontend::components.card.card_tvshow', [
                'values' => $responseData
            ])->render();
    
            return ApiResponse::success(
                null,
                __('movie.tvshow_list'),
                200,
                [
                    'html'    => $html,
                    'hasMore' => $tvshows->hasMorePages(),
                ]
            );
        }
    
        return ApiResponse::success($responseData, __('movie.tvshow_list'), 200);
    }




    public function movieDetails(Request $request)
    {


        $movieId = $request->movie_id;

        $cacheKey = 'movie_' . $movieId . '_'.$request->profile_id;


            $movie = Entertainment::where('id', $movieId)->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews', 'entertainmentTalentMappings', 'entertainmentStreamContentMappings', 'entertainmentDownloadMappings', 'entertainmentSubtitleMappings')->first();
            $movie['reviews'] = $movie->entertainmentReviews ?? null;

            if ($request->has('user_id')) {

                $user_id = $request->user_id;
                $movie['is_watch_list'] = (int) WatchList::where('entertainment_id', $movieId)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->exists();
                $movie['is_likes'] = Like::where('entertainment_id', $movieId)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->where('is_like', 1)->exists();
                $movie['is_download'] = EntertainmentDownload::where('entertainment_id', $movieId)->where('device_id',$request->device_id)->where('user_id', $user_id)
                ->where('entertainment_type', 'movie')->where('is_download', 1)->exists();
                $movie['your_review'] = $movie->entertainmentReviews ? optional($movie->entertainmentReviews)->where('user_id', $user_id)->first() : null;

                if ($movie['your_review']) {
                    $movie['reviews'] = $movie['reviews']->where('user_id', '!=', $user_id);
                }

                $continueWatch = ContinueWatch::where('entertainment_id', $movie->id)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->where('entertainment_type', 'movie')->first();
                $movie['continue_watch'] = $continueWatch;
            }
            $responseData = new MovieDetailDataResource($movie);


        return ApiResponse::success($responseData, __('movie.movie_details'), 200);
    }



    public function tvshowDetails(Request $request)
    {

        $tvshow_id = $request->tvshow_id;


            $tvshow = Entertainment::where('id', $tvshow_id)->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews', 'entertainmentTalentMappings', 'season', 'episode')->first();
            $tvshow['reviews'] = $tvshow->entertainmentReviews ?? null;

            if ($request->has('user_id')) {
                $user_id = $request->user_id;
                $tvshow['user_id'] = $user_id;
                $tvshow['is_watch_list'] = (int) WatchList::where('entertainment_id', $request->tvshow_id)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->exists();
                $tvshow['is_likes'] = Like::where('entertainment_id', $request->tvshow_id)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->where('is_like', 1)->exists();
                $tvshow['your_review'] =  $tvshow->entertainmentReviews ? $tvshow->entertainmentReviews->where('user_id', $user_id)->first() :null;

                if ($tvshow['your_review']) {
                    $tvshow['reviews'] = $tvshow['reviews']->where('user_id', '!=', $user_id);
                }
            }

            $responseData = new TvshowDetailResource($tvshow);

        return ApiResponse::success($responseData, __('movie.tvshow_details'), 200);
    }

    public function saveDownload(Request $request)
    {
        $user = auth()->user();
        $download_data = $request->all();
        $download_data['user_id'] = $user->id;

        $download = EntertainmentDownload::where('entertainment_id', $request->entertainment_id)->where('user_id', $user->id)->where('entertainment_type', $request->entertainment_type)->first();

        if (!$download) {
            $result = EntertainmentDownload::create($download_data);

            if ($request->entertainment_type == 'movie') {

                clearRelatedCache(['movie_'.$request->entertainment_id], 'entertainment');

            } else if ($request->entertainment_type == 'episode') {
                clearRelatedCache('episode_'.$request->entertainment_id, 'episode');

            }

            return ApiResponse::success(null, __('movie.movie_download'), 200);
        } else {
            return ApiResponse::success(null, __('movie.already_download'), 200);
        }
    }

    public function episodeList(Request $request)
    {

        $perPage = $request->input('per_page', 10);
        $user_id = $request->user_id;
        $episodeList = Episode::where('status', 1)->with('entertainmentdata', 'plan', 'EpisodeStreamContentMapping', 'episodeDownloadMappings');

        if ($request->has('tvshow_id')) {
            $episodeList = $episodeList->where('entertainment_id', $request->tvshow_id);
        }
        if ($request->has('season_id')) {
            $episodeList = $episodeList->where('season_id', $request->season_id);
        }

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $episodeList->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%");
            });
        }

        $shouldSortByEpisodeNumber = false;
        if ($request->has('season_id')) {
            $allEpisodesInSeason = Episode::where('season_id', $request->season_id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->get();

            $shouldSortByEpisodeNumber = $allEpisodesInSeason->count() > 0 &&
                $allEpisodesInSeason->every(function ($episode) {
                    return !is_null($episode->episode_number);
                });
        }

        if ($shouldSortByEpisodeNumber) {
            $episodes = $episodeList
                        ->orderByRaw('CAST(episode_number AS UNSIGNED) ASC')
                        ->paginate($perPage);
        } else {
            $episodes = $episodeList->orderBy('id', 'asc')->paginate($perPage);
        }

        $responseData = EpisodeResource::collection(
            $episodes->map(function ($episode) use ($user_id) {
                return new EpisodeResource($episode, $user_id);
            })
        );

        if ($request->has('is_ajax') && $request->is_ajax == 1) {

            $html = '';

            foreach ($responseData->toArray($request) as $index => $value) {
                $html .= '<div class="col">';
                $html .= view('frontend::components.card.card_episode', [
                    'data' => $value,
                    'index' => $index
                ])->render();
                $html .= '</div>';
            }

            $hasMore = $episodes->hasMorePages();

            return ApiResponse::success(
                null,
                __('movie.episode_list'),
                200,
                ['html' => $html, 'hasMore' => $hasMore]
            );
        }


        return ApiResponse::success($responseData, __('movie.episode_list'), 200);
    }

    public function episodeListV3(Request $request)
    {
        $device_type = getDeviceType($request);
        $perPage = $request->input('per_page', 10);
        $user_id = $request->user_id;
        $tvshow_id = $request->input('tv_show_id');

        // Create unique cache key based on all relevant parameters
        $cacheKey = 'episode_list_v3_'. md5(json_encode([
            'user_id' => $user_id,
            'device_type' => $device_type,
            'per_page' => $perPage,
            'tvshow_id' => $request->input('tvshow_id'),
            'season_id' => $request->input('season_id'),
            'search' => $request->input('search'),
            'download_quality' => $request->input('download_quality'),
            'page' => $request->input('page', 1),
            'is_ajax' => $request->input('is_ajax', 0)
        ]));

        // Use cacheApiResponse helper with 5 minutes TTL
        $cachedResult = cacheApiResponse($cacheKey, 300, function() use ($request, $device_type, $perPage, $user_id) {
            $episodeList = Episode::where('status', 1)
                ->with('entertainmentdata', 'plan', 'EpisodeStreamContentMapping', 'episodeDownloadMappings');

            if ($request->has('tvshow_id')) {
                $episodeList->where('entertainment_id', $request->tvshow_id);
            }
            if ($request->has('season_id')) {
                $episodeList->where('season_id', $request->season_id);
            }
            if ($request->has('search')) {
                $searchTerm = $request->search;
                $episodeList->where('name', 'like', "%{$searchTerm}%");
            }
            if (isset($request->is_restricted)) {
                $episodeList->where('is_restricted', $request->is_restricted);
            }

            // Check if all episodes have episode_number (only when season_id is provided)
            $shouldSortByEpisodeNumber = false;
            if ($request->has('season_id')) {
                $allEpisodesInSeason = Episode::where('season_id', $request->season_id)
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->get();

                $shouldSortByEpisodeNumber = $allEpisodesInSeason->count() > 0 &&
                    $allEpisodesInSeason->every(function ($episode) {
                        return !is_null($episode->episode_number);
                    });
            }

            // Sort by episode_number if all episodes have it, otherwise by id
            if ($shouldSortByEpisodeNumber) {
                $episodes = $episodeList
                ->orderByRaw('CAST(episode_number AS UNSIGNED) ASC')
                ->paginate($perPage);
            } else {
                $episodes = $episodeList->orderBy('id', 'asc')->paginate($perPage);
            }
            // Get device support info
            $getDeviceTypeData = Subscription::checkPlanSupportDevice($user_id, $device_type);
            $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);

            // Get user's active plan
            $userLevel = Subscription::select('plan_id')
                ->where(['user_id' => $user_id, 'status' => 'active'])
                ->latest()
                ->first();
            $userPlanId = $userLevel->plan_id ?? null;
            $profile_id = getCurrentProfile($user_id, $request);

            // Map over paginator collection safely
            $episodes->getCollection()->transform(function ($episode) use ($device_type, $deviceTypeResponse, $userPlanId, $user_id, $profile_id, $request) {
                // Poster image
                $episode->poster_image = $device_type == 'tv'
                    ? setBaseUrlWithFileName($episode->poster_tv_url, 'image', 'episode')
                    : setBaseUrlWithFileName($episode->poster_url, 'image', 'episode');

                // Device supported
                $episode->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] ? 1 : 0;

                // Required plan level
                if (!is_null($episode->plan_id) && !is_null($userPlanId)) {
                    $episode->required_plan_level = $userPlanId >= $episode->plan_id ? 1 : 0;
                } else {
                    $episode->required_plan_level = 0;
                }
                $episode->type = 'episode';
                $episode = setContentAccess($episode, $user_id, $userPlanId);

                if(isset($episode->access) && $episode->access == 'pay-per-view'){
                    $rental = [
                        'price' => (float)$episode->price,
                        'discount' => (int)$episode->discount,
                        'access_duration' => $episode->access_duration,
                        'availability_days' => $episode->available_for,
                        'access' => $episode->purchase_type,
                    ];
                    if ($rental['price'] > 0 && $rental['discount'] > 0) {
                        $rental['discounted_price'] = round(
                            $rental['price'] - ($rental['price'] * $rental['discount'] / 100),
                            2
                        );
                    } else {
                        $rental['discounted_price'] = $rental['price'];
                    }
                    $episode->rental = $rental;
                } else {
                    $episode->rental = [];
                }

                $continuewatch = ContinueWatch::where('user_id', $user_id)
                    ->where('profile_id', $profile_id)
                    ->where('entertainment_type', 'tvshow')
                    ->where('episode_id', $episode->id)
                    ->first();


                $episode->watched_time = $continuewatch->watched_time ?? '00:00:01';
                $episode->total_watched_time = $continuewatch->total_watched_time ?? '00:00:01';

                $downloadMappingsQuery = EpisodeDownloadMapping::where('episode_id', $episode->id);

                if ($request->download_quality) {
                    $downloadMappingsQuery->where('quality', $request->download_quality);
                }

                $downloadMappings = $downloadMappingsQuery->get();

                $defaultDownload = [];

                if (!empty($episode->download_type) || !empty($episode->download_url)) {
                    $defaultDownload[] = [
                        'id'        => $episode->id,
                        'url_type'  => $episode->download_type ?? null,
                        'url'       => ($episode->download_type === 'Local')
                                        ? setBaseUrlWithFileName($episode->download_url ?? null, 'video', 'episode')
                                        : ($episode->download_url ?? null),
                        'quality'   => 'default_quality',
                    ];
                }

                $mappingDownloads = [];

                if (!empty($downloadMappings)) {
                    foreach ($downloadMappings as $mapping) {
                        $mappingDownloads[] = [
                            'id'        => $mapping->id,
                            'url_type'  => $mapping->type,
                            'url'       => ($mapping->type === 'Local')
                                            ? setBaseUrlWithFileName($mapping->url, 'video', 'episode')
                                            : $mapping->url,
                            'quality'   => $mapping->quality,
                        ];
                    }
                }

                $mergedDownloads = array_merge($defaultDownload, $mappingDownloads);

                $episode->download_data = [
                    'download_enable' => $episode->download_status ?? 0,
                    'download_quality' => $mergedDownloads
                ];
                $episode->tv_show_data = [
                    'id' => $episode->seasondata->id,
                    'season_id' => $episode->seasondata->id,
                ];

                $episode->season_data = $episode->entertainmentdata?->season?->map(function ($season) {
                    return [
                        'id' => $season->id,
                        'name' => $season->name,
                        'season_id' => $season->id,
                        'total_episode' => $season->episodes()->count(),
                    ];
                }) ?? collect();

                return $episode;
            });

            // Wrap in resource
            $responseData = EpisodeResourceV3::collection($episodes);

            // Handle AJAX rendering
            if ($request->has('is_ajax') && $request->is_ajax == 1) {
                $html = '';
                foreach ($responseData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_episode', [
                        'data' => $value,
                        'index' => $index
                    ])->render();
                }

                return [
                    'status' => true,
                    'html' => $html,
                    'message' => __('movie.episode_list'),
                    'hasMore' => $episodes->hasMorePages(),
                ];
            }

            return [
                'status' => true,
                'data' => $responseData,
                'message' => __('movie.episode_list'),
            ];
        });

        // Return cached response
        return ApiResponse::custom($cachedResult['data'], 200);
    }




    public function episodeDetails(Request $request)
    {
        $user_id = $request->user_id;
        $episode_id = $request->episode_id;


            $episode = Episode::where('id', $episode_id)->with('entertainmentdata', 'plan', 'EpisodeStreamContentMapping', 'episodeDownloadMappings','subtitles')->first();

            if ($request->has('user_id')) {
                $continueWatch = ContinueWatch::where('episode_id', $episode->id)->where('user_id', $user_id)->where('profile_id', $request->profile_id)->where('entertainment_type', 'tvshow')->first();
                $episode['continue_watch'] = $continueWatch;

                $episode['is_download'] = EntertainmentDownload::where('entertainment_id', $episode->id)->where('user_id',  $user_id)->where('entertainment_type', 'episode')->where('is_download', 1)->exists();

                $genre_ids = $episode->entertainmentData->entertainmentGenerMappings->pluck('genre_id');

                $moreItems = Entertainment::where('type', 'tvshow')
                    ->whereHas('entertainmentGenerMappings', function ($query) use ($genre_ids) {
                        $query->whereIn('genre_id', $genre_ids);
                    });

                isset($request->is_restricted) && $moreItems = $moreItems->where('is_restricted', $request->is_restricted);
                (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $moreItems = $moreItems->where('is_restricted',0);

                $episode['moreItems'] = $moreItems->where('id', '!=', $episode->id)
                    ->orderBy('id', 'desc')
                    ->get();

                $episode['genre_data'] = Genres::whereIn('id', $genre_ids)->get();
            }


            $genre_ids = $episode->entertainmentData->entertainmentGenerMappings->pluck('genre_id');

            $episode['moreItems'] = Entertainment::where('type', 'tvshow')
                ->whereHas('entertainmentGenerMappings', function ($query) use ($genre_ids) {
                    $query->whereIn('genre_id', $genre_ids);
                })
                ->where('id', '!=', $episode->id)
                ->orderBy('id', 'desc')
                ->get();

            $episode['genre_data'] = Genres::whereIn('id', $genre_ids)->get();

            $responseData = new EpisodeDetailResource($episode);




        return ApiResponse::success($responseData, __('movie.episode_details'), 200);
    }

    public function searchList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $movieList = Entertainment::query()
        ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews',
         'entertainmentTalentMappings', 'entertainmentStreamContentMappings')
         ->where('type', 'movie');

        $movieList = $movieList->where('status', 1);

        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $movieList = $movieList->where('is_restricted',0);

        isset($request->is_restricted) && $movieList = $movieList->where('is_restricted', $request->is_restricted);

        $movies = $movieList->orderBy('updated_at', 'desc');
        $movies = $movies->paginate($perPage);

        $responseData = new SearchResource($movies);
        if(isenablemodule('movie') == 1){
            $responseData = $responseData;

        }else{
            $responseData = [];
        }

        return ApiResponse::success($responseData, __('movie.search_list'), 200);
    }

    public function getSearch(Request $request)
    {

        $movieList = Entertainment::query()->whereDate('release_date', '<=', Carbon::now())
            ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews', 'entertainmentTalentMappings', 'entertainmentStreamContentMappings')
            ->where('type', 'movie')->where('status', 1)->where('deleted_at', null);


        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;

            if (strtolower($searchTerm) == 'movie' || strtolower($searchTerm) == 'movies') {
                $movieList->where('type', 'movie');
            } else {
                $movieList->where(function($movieList) use($searchTerm) {
                    $movieList->where('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('entertainmentGenerMappings.genre', function ($genreQuery) use ($searchTerm) {
                        $genreQuery->where('name', 'like', "%{$searchTerm}%");
                    });
                });
            }

        }

        isset($request->is_restricted) && $movieList = $movieList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $movieList = $movieList->where('is_restricted',0);

        $movieList = $movieList->orderBy('updated_at', 'desc')->get();


        $movieData = (isenablemodule('movie') == 1) ? CommonContentResourceV3::collection($movieList) : [];
        $tvshowList = Entertainment::where('status', 1)->where('type', 'tvshow')
            ->whereDate('release_date', '<=', Carbon::now())
            ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews',
            'entertainmentTalentMappings', 'season', 'episode')->whereHas('episode')->where('deleted_at', null);

        isset($request->is_restricted) && $tvshowList = $tvshowList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $tvshowList = $tvshowList->where('is_restricted',0);

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $tvshowList->where('name', 'like', "%{$searchTerm}%")
            ->orWhereHas('entertainmentGenerMappings.genre', function ($query) use ($searchTerm) {
                $query->where('name', '=', "%{$searchTerm}%");
            });
        }

        $tvshowList = $tvshowList->orderBy('updated_at', 'desc')->where('type', 'tvshow')->get();
        $tvshowData = (isenablemodule('tvshow') == 1) ? CommonContentResourceV3::collection($tvshowList) : [];


        $videoList = Video::query()->whereDate('release_date', '<=', Carbon::now())->with('VideoStreamContentMappings', 'plan');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $videoList->where('name', 'like', "%{$searchTerm}%");
        }

        $videoList = $videoList->where('status', 1)->orderBy('updated_at', 'desc')->take(6)->get();
        $videoData = (isenablemodule('video') == 1) ? VideoResourceV3::collection($videoList) : [];


        $seasonList = Season::query()->with('episodes');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $seasonList->where('name', 'like', "%{$searchTerm}%");
        }

        $seasonList = $seasonList->where('status', 1)->orderBy('updated_at', 'desc')->get();
        $seasonData = (isenablemodule('tvshow') == 1) ? SeasonResourceV3::collection($seasonList) : [];


        $episodeList = Episode::query()->whereDate('release_date', '<=', Carbon::now())->with('seasondata');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $episodeList->where('name', 'like', "%{$searchTerm}%");
        }

        $episodeList = $episodeList->where('status', 1)->orderBy('updated_at', 'desc')->get();
        $episodeData = (isenablemodule('tvshow') == 1) ? EpisodeResourceV3::collection($episodeList) : [];


        $actorList = CastCrew::query()->where('type', 'actor')->with('entertainmentTalentMappings');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $actorList->where('name', 'like', "%{$searchTerm}%");
        }

        $actorList = $actorList->orderBy('updated_at', 'desc')->get();
        $actorData = CastCrewListResource::collection($actorList);


        $directorList = CastCrew::query()->where('type', 'director')->with('entertainmentTalentMappings');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $directorList->where('name', 'like', "%{$searchTerm}%");
        }

        $directorList = $directorList->orderBy('updated_at', 'desc')->take(6)->get();
        $directorData = CastCrewListResource::collection($directorList);



        if ($request->has('is_ajax') && $request->is_ajax == 1) {

            $html = '';

            if($movieData && $movieData->isNotEmpty()) {

                foreach ($movieData->toArray($request) as $index => $value) {

                    $html .= view('frontend::components.card.card_entertainment', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($tvshowData && $tvshowData->isNotEmpty()) {

                foreach ($tvshowData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_entertainment', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($videoData && $videoData->isNotEmpty()) {

                foreach ($videoData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_video', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($seasonData && $seasonData->isNotEmpty()) {

                foreach ($seasonData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_season', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($episodeData && $episodeData->isNotEmpty()) {

                foreach ($episodeData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_season', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($actorData && $actorData->isNotEmpty()) {

                foreach ($actorData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_castcrew', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($directorData && $directorData->isNotEmpty()) {

                foreach ($directorData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_castcrew', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }

            if (empty($movieData) && empty($tvshowData) && empty($videoData) && empty($seasonData) && empty($episodeData) && empty($actorData) && empty($directorData)) {
                $html .= '';
            }


            return ApiResponse::success(
                null,
                __('movie.search_list'),
                200,
                ['html' => $html]
            );
        }

        return ApiResponse::success(
            null,
            __('movie.search_list'),
            200,
            ['movieList' => $movieData, 'tvshowList' => $tvshowData, 'videoList' => $videoData, 'seasonList' => $seasonData, 'episodeList' => $episodeData, 'actorList' => $actorList, 'directorList' => $directorList]
        );
    }


    public function getSearchV3(Request $request)
    {
        $device_type = getDeviceType($request);

        // Create cache key based on request parameters
        $cacheKey = 'search_v3_' . md5(json_encode($request->all())) . '_' . $device_type;
        
        // Check cache version to invalidate cache when actors/directors are updated
        // When version changes, old cache keys become invalid
        $cacheVersionKey = 'search_v3_cache_version';
        $cacheVersion = Cache::get($cacheVersionKey, 0);
        $versionedCacheKey = $cacheKey . '_v' . $cacheVersion;
        
        // Also clear the non-versioned key if it exists (for backward compatibility)
        Cache::forget($cacheKey);

        // Check if search term is provided
        if (
            (empty($request->search) || trim($request->search) === '') &&
            empty($request->genre_id) &&
            empty($request->actor_id) &&
            empty($request->director_id) &&
            empty($request->access) &&
            empty($request->language)
        ){
            return ApiResponse::success(
                null,
                __('movie.search_list'),
                200,
                ['movieList' => [], 'tvshowList' => [], 'videoList' => [], 'seasonList' => [], 'episodeList' => [], 'channelList' => []]
            );
        }

        // OPTIMIZATION: Remove time() from cache key to enable proper caching
        $cachedResult = cacheApiResponse($versionedCacheKey, 300, function() use ($request, $device_type) {
            $perPage = $request->input('per_page', 10);
            $buildPaginationMeta = function ($paginator) {
                return [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'has_more' => $paginator->hasMorePages(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                ];
            };
            $user_id = $request->user_id;
            $purchasedIds = []; // Initialize for non-user case
            $moviePagination = null;
            $tvshowPagination = null;
            
            if($user_id){
                $profile_id = getCurrentProfile($user_id, $request);
                
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
                
                // OPTIMIZATION: Bulk fetch PayPerView purchases grouped by type
                $purchasedIds = PayPerView::where('user_id', $user_id)
                    ->get()
                    ->groupBy('type')
                    ->map(function($items) {
                        return $items->pluck('movie_id')->toArray();
                    })
                    ->toArray();
            }else{
                $deviceTypeResponse = [];
                $userPlanId = 0;
                $profile_id = null;
            }

            $defaultIncludedTypes = collect(['movie', 'tvshow', 'video', 'season', 'episode', 'livetv', 'channel']);
            $searchTypes = collect(explode(',', strtolower((string) ($request->search_type ?? ''))))
                ->map(fn ($type) => trim($type))
                ->filter()
                ->values();

            $shouldIncludeType = function (string $type, array $aliases = [], bool $defaultInclude = false) use ($searchTypes, $defaultIncludedTypes) {
                if ($searchTypes->isEmpty()) {
                    return $defaultInclude ?: $defaultIncludedTypes->contains($type);
                }

                $targets = collect(array_merge([$type], $aliases))
                    ->map(fn ($target) => strtolower($target))
                    ->filter();

                return $searchTypes->contains(function ($value) use ($targets) {
                    return $targets->contains($value);
                });
            };
            if ($shouldIncludeType('movie', [], true)) {
            $movieList = Entertainment::query()
            ->select(['id', 'name', 'type', 'video_upload_type', 'release_date', 'trailer_url', 'trailer_url_type', 'is_restricted', 'imdb_rating', 'poster_url', 'thumbnail_url', 'poster_tv_url','trailer_url', 'video_url_input', 'movie_access'])
                ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews', 'entertainmentTalentMappings', 'entertainmentStreamContentMappings')
                ->where('type', 'movie')->where('status', 1)->where('deleted_at', null);

            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;

                if (strtolower($searchTerm) == 'movie' || strtolower($searchTerm) == 'movies') {
                    $movieList->where('type', 'movie');
                } else {
                    $movieList->where(function($query) use($searchTerm) {
                        $query->where('name', 'like', "%{$searchTerm}%")
                        ->orWhereHas('entertainmentGenerMappings.genre', function ($genreQuery) use ($searchTerm) {
                            $genreQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                    });
                }
            }

            isset($request->is_restricted) && $movieList = $movieList->where('is_restricted', $request->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                    $movieList = $movieList->where('is_restricted',0);

            // Filter by director_id if provided
            if ($request->has('director_id') && !empty($request->director_id)) {

                $movieList->whereHas('entertainmentTalentMappings', function ($query) use ($request) {
                    $query->whereIn('talent_id', explode(',', $request->director_id))
                          ->whereHas('talentprofile', function ($subQuery) {
                              $subQuery->where('type', 'director')
                                       ->where('status', 1)
                                       ->whereNull('deleted_at');
                          });
                });
            }

            if ($request->has('is_released') && !empty($request->is_released)) {
                $movieList->where('release_date', '<=', Carbon::now());
            }

            if ($request->has('actor_id') && !empty($request->actor_id)) {

                $movieList->whereHas('entertainmentTalentMappings', function ($query) use ($request) {
                    $query->whereIn('talent_id', explode(',', $request->actor_id))
                          ->whereHas('talentprofile', function ($subQuery) {
                              $subQuery->where('type', 'actor')
                                       ->where('status', 1)
                                       ->whereNull('deleted_at');
                          });
                });
            }
            if ($request->has('genre_id') && !empty($request->genre_id)) {
                $genreIds = array_filter(array_map('intval', explode(',', $request->genre_id)));
                if (!empty($genreIds)) {
                    $movieList->whereHas('entertainmentGenerMappings', function ($query) use ($genreIds) {
                        $query->whereIn('genre_id', $genreIds);
                    });
                }
            }
            if ($request->has('access') && !empty($request->access)) {
                $movieList->where('movie_access', $request->access);
            }
            if($request->has('language') && !empty($request->language)) {
                $movieList->whereIn('language', explode(',', $request->language));
            }

            $moviePaginator = $movieList->orderBy('updated_at', 'desc')->simplePaginate($perPage);
            $movieCollection = $moviePaginator->getCollection();

            if($user_id){
                $movieCollection = $movieCollection->map(function($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId) {
                    $item->poster_image = $request->device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item->access = $item->movie_access;
                    $item = setContentAccess($item, $user_id, $userPlanId);
                    $item['isDeviceSupported'] = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    return $item;
                });

            }else{
                $movieCollection = $movieCollection->map(function($item) use ($device_type ) {
                    $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item->isDeviceSupported = 0;
                    $item->access = $item->movie_access;
                    $item = setContentAccess($item, null, null);
                    return $item;
                });

            }
            $moviePaginator->setCollection($movieCollection);
            $moviePagination = $buildPaginationMeta($moviePaginator);
            $movieData = (isenablemodule('movie') == 1) ? MoviesResourceV3::collection($movieCollection) : [];
        }else{
            $movieData = [];
        }

        if ($shouldIncludeType('tvshow', [], true)) {
            $tvshowList = Entertainment::where('status', 1)->where('type', 'tvshow')
            ->select(['id', 'name', 'type', 'video_upload_type', 'release_date', 'trailer_url', 'trailer_url_type', 'is_restricted', 'imdb_rating', 'poster_url', 'thumbnail_url', 'poster_tv_url','trailer_url', 'video_url_input', 'movie_access'])
            ->with('entertainmentGenerMappings', 'plan', 'entertainmentReviews',
            'entertainmentTalentMappings', 'season', 'episode')->whereHas('episode')->where('deleted_at', null);




        isset($request->is_restricted) && $tvshowList = $tvshowList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $tvshowList = $tvshowList->where('is_restricted',0);

        // Filter by director_id if provided
        if ($request->has('director_id') && !empty($request->director_id)) {
            $tvshowList->whereHas('entertainmentTalentMappings', function ($query) use ($request) {
                $query->whereIn('talent_id', explode(',', $request->director_id))
                      ->whereHas('talentprofile', function ($subQuery) {
                          $subQuery->where('type', 'director')
                                   ->where('status', 1)
                                   ->whereNull('deleted_at');
                      });
            });
        }

        if ($request->has('is_released') && !empty($request->is_released)) {
            $tvshowList->where('release_date', '<=', Carbon::now());
        }

        if ($request->has('actor_id') && !empty($request->actor_id)) {
            $tvshowList->whereHas('entertainmentTalentMappings', function ($query) use ($request) {
                $query->whereIn('talent_id', explode(',', $request->actor_id))
                      ->whereHas('talentprofile', function ($subQuery) {
                          $subQuery->where('type', 'actor')
                                   ->where('status', 1)
                                   ->whereNull('deleted_at');
                      });
            });
        }
        if ($request->has('genre_id') && !empty($request->genre_id)) {
            $genreIds = array_filter(array_map('intval', explode(',', $request->genre_id)));
            if (!empty($genreIds)) {
                $tvshowList->whereHas('entertainmentGenerMappings', function ($query) use ($genreIds) {
                    $query->whereIn('genre_id', $genreIds);
                });
            }
        }
        if ($request->has('access') && !empty($request->access)) {
            $tvshowList->where('movie_access', $request->access);
        }
        if($request->has('language') && !empty($request->language)) {
            $tvshowList->whereIn('language', explode(',', $request->language));
        }

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $tvshowList->where(function($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('entertainmentGenerMappings.genre', function ($genreQuery) use ($searchTerm) {
                        $genreQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $tvshowPaginator = $tvshowList->orderBy('updated_at', 'desc')->where('type', 'tvshow')->simplePaginate($perPage);
        $tvshowCollection = $tvshowPaginator->getCollection();
            if($user_id){
                $tvshowCollection = $tvshowCollection->map(function($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId, $purchasedIds) {
                    $item->poster_image = $request->device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item->access = $item->movie_access;
                    // OPTIMIZATION: Pass purchasedIds to setContentAccess
                    $item = setContentAccess($item, $user_id, $userPlanId, $purchasedIds ?? []);
                    $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    $item->season_data = $item->season->map(function($season) {
                        return [
                            'id' => $season->id,
                            'name' => $season->name,
                            'season_id' => $season->id,
                            'total_episode' => $season->episodes()->count(),
                        ];
                    })->values();
                    return $item;
            });
            }else{
                $tvshowCollection = $tvshowCollection->map(function($item) use ($device_type ) {
                    $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                    $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                    $item->access = $item->movie_access;
                    $item = setContentAccess($item, null, null);
                    $item->has_content_access = 0;
                    $item->required_plan_level = $item->plan_id ?? 0;
                    $item->isDeviceSupported = 0;
                    $item->season_data = $item->season->map(function($season) {
                        return [
                            'id' => $season->id,
                            'name' => $season->name,
                            'season_id' => $season->id,
                            'total_episode' => $season->episodes()->count(),
                        ];
                    })->values();
                    return $item;
                });
            }

        $tvshowPaginator->setCollection($tvshowCollection);
        $tvshowPagination = $buildPaginationMeta($tvshowPaginator);
        $tvshowData = (isenablemodule('tvshow') == 1) ? TvshowResourceV3::collection($tvshowCollection) : [];
        }else{
            $tvshowData = [];
        }
        if ($shouldIncludeType('video', [], true)) {
        $videoList = Video::query()->with('VideoStreamContentMappings', 'plan');

        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $videoList->where('name', 'like', "%{$searchTerm}%");
        }
        if ($request->has('access') && !empty($request->access)) {
            $videoList->where('access', $request->access);
        }

        if ($request->has('is_released') && !empty($request->is_released)) {
            $videoList->where('release_date', '<=', Carbon::now());
        }

        isset($request->is_restricted) && $videoList = $videoList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $videoList = $videoList->where('is_restricted',0);


        $videoList = $videoList->where('status', 1)->orderBy('updated_at', 'desc')->select(['id', 'name', 'type', 'video_upload_type', 'release_date', 'trailer_url', 'trailer_url_type', 'is_restricted', 'imdb_rating', 'poster_url', 'thumbnail_url', 'poster_tv_url','trailer_url', 'video_url_input', 'access'])->take(6)->get();

        if ($user_id) {
            $videoList = $videoList->map(function($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId, $purchasedIds) {
                    $item->poster_image = $request->device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
                    // OPTIMIZATION: Pass purchasedIds to setContentAccess
                    $item = setContentAccess($item, $user_id, $userPlanId, $purchasedIds ?? []);
                    $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                    return $item;
            });
        }else{
            $videoList = $videoList->map(function($item) use ($device_type ) {
                    $item->poster_image = $device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
                    $item->has_content_access = 0;
                    $item = setContentAccess($item, null, null);
                    $item->required_plan_level = $item->plan_id ?? 0;
                    $item->isDeviceSupported = 0;
                    return $item;
            });
        }
        if($request->has('language') && !empty($request->language) || $request->has('genre_id') && !empty($request->genre_id) || $request->has('actor_id') && !empty($request->actor_id) || $request->has('director_id') && !empty($request->director_id)) {
            $videoList = [];
        }

    $videoData = (isenablemodule('video') == 1) ? VideoResourceV3::collection($videoList) : [];
    }else{
        $videoData = [];
    }

        $channelData = [];
        if (isenablemodule('livetv') == 1 && $shouldIncludeType('livetv', ['channel'], true)) {
            $channelList = LiveTvChannel::query()->where('status', 1)->whereNull('deleted_at');

            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $channelList->where('name', 'like', "%{$searchTerm}%");
            }

            if ($request->has('genre_id') && !empty($request->genre_id)) {
                $categoryIds = array_filter(array_map('intval', explode(',', $request->genre_id)));
                if (!empty($categoryIds)) {
                    $channelList->whereIn('category_id', $categoryIds);
                }
            }

            if ($request->has('access') && !empty($request->access)) {
                $channelList->where('access', $request->access);
            }
            $channelList = $channelList->orderBy('updated_at', 'desc')->get();

            if ($user_id) {
                $channelList = $channelList->map(function ($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId, $purchasedIds) {
                    $item->poster_image = $request->device_type == 'tv'
                        ? setBaseUrlWithFileName($item->poster_tv_url, 'image', 'livetv')
                        : setBaseUrlWithFileName($item->poster_url, 'image', 'livetv');
                    // OPTIMIZATION: Pass purchasedIds to setContentAccess
                    $item = setContentAccess($item, $user_id, $userPlanId, $purchasedIds ?? []);
                    $item->isDeviceSupported = ($deviceTypeResponse['isDeviceSupported'] ?? false) ? 1 : 0;
                    return $item;
                });
            } else {
                $channelList = $channelList->map(function ($item) use ($device_type) {
                    $item->poster_image = $device_type == 'tv'
                        ? setBaseUrlWithFileName($item->poster_tv_url, 'image', 'livetv')
                        : setBaseUrlWithFileName($item->poster_url, 'image', 'livetv');
                    $item = setContentAccess($item, null, null);
                    $item->isDeviceSupported = 0;
                    return $item;
                });
            }
            if($request->has('language') && !empty($request->language) || $request->has('genre_id') && !empty($request->genre_id) || $request->has('actor_id') && !empty($request->actor_id) || $request->has('director_id') && !empty($request->director_id)) {
                $channelList = [];
            }
            $channelData = LiveTvChannelResourceV3::collection($channelList);
        }

        if ($shouldIncludeType('season', [], true)) {
        $seasonList = Season::query()->with('episodes','entertainmentdata');
        if ($request->has('search') && $request->search !='') {

            $searchTerm = $request->search;
            $seasonList->where('name', 'like', "%{$searchTerm}%");
        }

        // Filter seasons by genre_id based on entertainment_id
        if ($request->has('genre_id') && !empty($request->genre_id)) {
            $genreIds = array_filter(array_map('intval', explode(',', $request->genre_id)));
            if (!empty($genreIds)) {
                $seasonList->whereHas('entertainmentdata', function ($query) use ($genreIds) {
                    $query->whereHas('entertainmentGenerMappings', function ($subQuery) use ($genreIds) {
                        $subQuery->whereIn('genre_id', $genreIds);
                    });
                });
            }
        }

        // Filter seasons by actor_id based on entertainment_id
        if ($request->has('actor_id') && !empty($request->actor_id)) {
            $seasonList->whereHas('entertainmentdata', function ($query) use ($request) {
                $query->whereHas('entertainmentTalentMappings', function ($subQuery) use ($request) {
                    $subQuery->whereIn('talent_id', explode(',', $request->actor_id))
                             ->whereHas('talentprofile', function ($talentQuery) {
                                 $talentQuery->where('type', 'actor');
                             });
                });
            });
        }

        // Filter seasons by director_id based on entertainment_id
        if ($request->has('director_id') && !empty($request->director_id)) {
            $seasonList->whereHas('entertainmentdata', function ($query) use ($request) {
                $query->whereHas('entertainmentTalentMappings', function ($subQuery) use ($request) {
                    $subQuery->whereIn('talent_id', explode(',', $request->director_id))
                             ->whereHas('talentprofile', function ($talentQuery) {
                                 $talentQuery->where('type', 'director');
                             });
                });
            });
        }

        // Filter seasons by language based on entertainment_id
        if ($request->has('language') && !empty($request->language)) {
            $seasonList->whereHas('entertainmentdata', function ($query) use ($request) {
                $query->whereIn('language', explode(',', $request->language));
            });
        }

        $seasonList = $seasonList->where('status', 1)->orderBy('updated_at', 'desc')->get();

        if ($user_id) {
            $seasonList = $seasonList->map(function($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId) {
                $item->poster_image = $request->device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
                $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                $item = setContentAccess($item, $user_id, $userPlanId);
                $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                $item->setAttribute('tv_show_data', [
                    'id' => $item->entertainmentdata->id,
                    'name' => $item->entertainmentdata->name,
                ]);
                return $item;
            });
        } else {
            $seasonList = $seasonList->map(function($item) use ($device_type ) {
                $item->poster_image = $device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
                $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                $item = setContentAccess($item, null, null);
                $item->isDeviceSupported = 0;
                $item->setAttribute('tv_show_data', [
                    'id' => $item->entertainmentdata->id,
                    'name' => $item->entertainmentdata->name,
                ]);
                return $item;
             });
        }
        if($request->has('access') && !empty($request->access)) {
            $seasonList->where('access', $request->access);
        }

        $seasonData = (isenablemodule('tvshow') == 1) ? SeasonResourceV3::collection($seasonList) : [];
    }else{
        $seasonData = [];
    }

        if ($shouldIncludeType('episode', [], true)) {
        // OPTIMIZATION: Add proper select and eager loading with nested relationships
        $episodeList = Episode::query()
            ->select([
                'id', 'name', 'slug', 'season_id', 'entertainment_id', 'release_date', 
                'trailer_url', 'trailer_url_type', 'is_restricted', 'imdb_rating', 'IMDb_rating',
                'poster_url', 'poster_tv_url', 'access', 'duration', 'short_desc'
            ])
            ->with([
                'seasondata:id,name,entertainment_id',
                'seasondata.entertainmentdata:id,name',
                'entertainmentdata:id,name,language',
                'entertainmentdata.entertainmentGenerMappings.genre:id,name,status'
            ])
            ->where('status', 1)
            ->whereNull('deleted_at');

        if ($request->has('search') && $request->search !='') {
            $searchTerm = $request->search;
            $episodeList->where('name', 'like', "%{$searchTerm}%");
        }

        // Filter episodes by genre_id based on season's entertainment_id
        if ($request->has('genre_id') && !empty($request->genre_id)) {
            $genreIds = array_filter(array_map('intval', explode(',', $request->genre_id)));
            if (!empty($genreIds)) {
                $episodeList->whereHas('seasondata.entertainmentdata', function ($query) use ($genreIds) {
                    $query->whereHas('entertainmentGenerMappings', function ($subQuery) use ($genreIds) {
                        $subQuery->whereIn('genre_id', $genreIds);
                    });
                });
            }
        }

        // Filter episodes by actor_id based on season's entertainment_id
        if ($request->has('actor_id') && !empty($request->actor_id)) {
            $episodeList->whereHas('seasondata.entertainmentdata', function ($query) use ($request) {
                $query->whereHas('entertainmentTalentMappings', function ($subQuery) use ($request) {
                    $subQuery->whereIn('talent_id', explode(',', $request->actor_id))
                             ->whereHas('talentprofile', function ($talentQuery) {
                                 $talentQuery->where('type', 'actor');
                             });
                });
            });
        }

        // Filter episodes by director_id based on season's entertainment_id
        if ($request->has('director_id') && !empty($request->director_id)) {
            $episodeList->whereHas('seasondata.entertainmentdata', function ($query) use ($request) {
                $query->whereHas('entertainmentTalentMappings', function ($subQuery) use ($request) {
                    $subQuery->whereIn('talent_id', explode(',', $request->director_id))
                             ->whereHas('talentprofile', function ($talentQuery) {
                                 $talentQuery->where('type', 'director');
                             });
                });
            });
        }

        // Filter episodes by language based on season's entertainment_id
        if ($request->has('language') && !empty($request->language)) {
            $episodeList->whereHas('seasondata.entertainmentdata', function ($query) use ($request) {
                $query->whereIn('language', explode(',', $request->language));
            });
        }

        // Filter episodes by access
        if ($request->has('access') && !empty($request->access)) {
            $episodeList->where('access', $request->access);
        }

        $episodeList = $episodeList->orderBy('updated_at', 'desc')->get();
        
        // OPTIMIZATION: Bulk fetch ContinueWatch data to avoid N+1 queries
        $continueWatchMap = [];
        if ($user_id && $profile_id) {
            $episodeIds = $episodeList->pluck('id')->toArray();
            $entertainmentIds = $episodeList->pluck('entertainment_id')->unique()->toArray();
            
            $continueWatchList = ContinueWatch::where('user_id', $user_id)
                ->where('profile_id', $profile_id)
                ->where('entertainment_type', 'tvshow')
                ->whereIn('entertainment_id', $entertainmentIds)
                ->whereIn('episode_id', $episodeIds)
                ->select('episode_id', 'entertainment_id', 'watched_time', 'total_watched_time')
                ->get();
            
            // Create map: episode_id => continueWatch data
            foreach ($continueWatchList as $cw) {
                $continueWatchMap[$cw->episode_id] = $cw;
            }
        }
        
        // OPTIMIZATION: Bulk fetch episode counts per season to avoid N+1 queries
        $seasonIds = $episodeList->pluck('seasondata.id')->filter()->unique()->toArray();
        $episodeCounts = [];
        if (!empty($seasonIds)) {
            $counts = \Modules\Episode\Models\Episode::whereIn('season_id', $seasonIds)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->selectRaw('season_id, COUNT(*) as total')
                ->groupBy('season_id')
                ->pluck('total', 'season_id')
                ->toArray();
            
            $episodeCounts = $counts;
        }
        
        if ($user_id) {
            $episodeList = $episodeList->map(function($item) use ($request, $deviceTypeResponse, $user_id, $userPlanId, $continueWatchMap, $episodeCounts, $purchasedIds) {
                // OPTIMIZATION: Use pre-fetched ContinueWatch data
                $continuewatch = $continueWatchMap[$item->id] ?? null;
                
                if($continuewatch && $item->id == $continuewatch->episode_id){
                    $item->watched_time = $continuewatch->watched_time ?? '00:00:01';
                    $item->total_watched_time = $continuewatch->total_watched_time ?? '00:00:01';
                }else{
                    $item->watchedtime = '00:00:01';
                    $item->total_watched_time = '00:00:01';
                }
                $item->poster_image = $request->device_type == 'tv' ? $item->poster_tv_url :$item->poster_url;
                $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                // OPTIMIZATION: Pass purchasedIds to setContentAccess
                $item = setContentAccess($item, $user_id, $userPlanId, $purchasedIds ?? []);
                $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
                
                // OPTIMIZATION: Use pre-fetched episode count
                $totalEpisodes = $episodeCounts[$item->seasondata->id ?? 0] ?? 0;
                
                $item->setAttribute('tv_show_data', [
                    'id' => $item->entertainmentdata->id ?? null,
                    'name' => $item->entertainmentdata->name ?? null,
                    'season_id' => $item->seasondata->id ?? null,
                    'total_episode' => $totalEpisodes,
                ]);
                $item->setAttribute('season_data', [
                    'id' => $item->seasondata->id ?? null,
                    'name' => $item->seasondata->name ?? null,
                    'season_id' => $item->seasondata->id ?? null,
                    'total_episode' => $totalEpisodes,
                ]);
                return $item;
            });
        } else {
            $episodeList = $episodeList->map(function($item) use ($device_type, $episodeCounts) {
                $item->poster_image = $device_type == 'tv' ? $item->poster_tv_url : $item->poster_url;
                $item->trailer_url =  $item->trailer_url_type == 'Local' ? setBaseUrlWithFileName($item->trailer_url, 'video', $item->type) : $item->trailer_url;
                $item = setContentAccess($item, null, null, []);
                $item->isDeviceSupported = 0;
                
                // OPTIMIZATION: Use pre-fetched episode count
                $totalEpisodes = $episodeCounts[$item->seasondata->id ?? 0] ?? 0;
                
                $item->setAttribute('tv_show_data', [
                    'id' => $item->entertainmentdata->id ?? null,
                    'name' => $item->entertainmentdata->name ?? null,
                    'season_id' => $item->seasondata->id ?? null,
                    'total_episode' => $totalEpisodes,
                ]);
                $item->setAttribute('season_data', [
                    'id' => $item->seasondata->id ?? null,
                    'name' => $item->seasondata->name ?? null,
                    'season_id' => $item->seasondata->id ?? null,
                    'total_episode' => $totalEpisodes,
                ]);
                return $item;
            });
        }
        $episodeData = (isenablemodule('tvshow') == 1) ? EpisodeResourceV3::collection($episodeList) : [];
    }else{
        $episodeData = [];
    }

        $actorsList = [];
        $directorsList = [];
        $actorData = collect([]);
        $directorData = collect([]);

        if ($shouldIncludeType('actor',[], true)) {
            $actorList = CastCrew::query()->where('type', 'actor')->where('status', 1)->where('deleted_at', null)->with('entertainmentTalentMappings');
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $actorList->where('name', 'like', "%{$searchTerm}%");
            }
            $actorData = $actorList->orderBy('updated_at', 'desc')->get();
            foreach ($actorData as $actor) {
                $actorsList[] = [
                    'id' => $actor->id,
                    'name' => $actor->name,
                    'role' => 'actor',
                    'profile_image' => $actor->file_url ? setBaseUrlWithFileName($actor->file_url,'image','castcrew') : null,
                ];
            }
        }

        if ($shouldIncludeType('director',[], true)) {
            $directorList = CastCrew::query()->where('type', 'director')->where('status', 1)->where('deleted_at', null)->with('entertainmentTalentMappings');
            if ($request->has('search') && $request->search != '') {
                $searchTerm = $request->search;
                $directorList->where('name', 'like', "%{$searchTerm}%");
            }
            $directorData = $directorList->orderBy('updated_at', 'desc')->get();
            foreach ($directorData as $director) {
                $directorsList[] = [
                    'id' => $director->id,
                    'name' => $director->name,
                    'role' => 'director',
                    'profile_image' => $director->file_url ? setBaseUrlWithFileName($director->file_url,'image','castcrew') : null,
                ];
            }
        }

        if ($request->has('is_ajax') && $request->is_ajax == 1) {

            $html = '';

            if($movieData && $movieData->isNotEmpty()) {

                foreach ($movieData->toArray($request) as $index => $value) {

                    $html .= view('frontend::components.card.card_entertainment', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($tvshowData && $tvshowData->isNotEmpty()) {

                foreach ($tvshowData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_entertainment', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($channelData && $channelData->isNotEmpty()) {
                foreach ($channelData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_tvchannel', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($videoData && $videoData->isNotEmpty()) {

                foreach ($videoData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_video', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($seasonData && $seasonData->isNotEmpty()) {

                foreach ($seasonData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_season', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($episodeData && $episodeData->isNotEmpty()) {

                foreach ($episodeData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_season', [
                        'value' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($actorData && $actorData->isNotEmpty()) {

                foreach ($actorData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_castcrew', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }
            if ($directorData && $directorData->isNotEmpty()) {

                foreach ($directorData->toArray($request) as $index => $value) {
                    $html .= view('frontend::components.card.card_castcrew', [
                        'data' => $value,
                        'index' => $index,
                        'is_search'=>1,
                    ])->render();
                }
            }

            if (empty($movieData) && empty($tvshowData) && empty($videoData) && empty($channelData) && empty($seasonData) && empty($episodeData) && empty($actorData) && empty($directorData)) {
                $html .= '';
            }

            return [
                'status' => true,
                'html' => $html,
                'message' => __('movie.search_list'),
            ];
        }

        return [
            'status' => true,
            'movieList' => $movieData,
            'tvshowList' => $tvshowData,
            'videoList' => $videoData,
            'channelList' => $channelData,
            'seasonList' => $seasonData,
            'episodeList' => $episodeData,
            'actors_list' => $actorsList,
            'directors_list' => $directorsList,
            'moviePagination' => $moviePagination,
            'tvshowPagination' => $tvshowPagination,
            'message' => __('movie.search_list'),
        ];

        });

        return ApiResponse::custom($cachedResult['data'], 200);
    }


    public function comingSoon(Request $request)
    {

        $perPage = $request->input('per_page', 10);
        $todayDate = Carbon::today()->toDateString();
        $device_type = getDeviceType($request);


        $cacheKey = 'coming_soon_'. md5(json_encode($request->all())).($request->is_ajax ? '_html' : '_json');


        $responseData = cache()->remember($cacheKey, 10, function () use ($request, $perPage, $todayDate,$device_type) {

            switch ($request->type) {
                case 'all':
                    // Get Entertainment items (movie, tvshow)
                    $movieList = Entertainment::where('release_date', '>', $todayDate)
                        ->whereIn('type', ['movie', 'tvshow'])
                        ->where('status', 1)
                        ->where('deleted_at',null);

                    // Get Video items
                    $videoList = Video::where('release_date', '>', $todayDate)
                        ->where('status', 1)
                        ->where('deleted_at',null);

                    isset($request->is_restricted) && $movieList = $movieList->where('is_restricted', $request->is_restricted);
                    isset($request->is_restricted) && $videoList = $videoList->where('is_restricted', $request->is_restricted);

                    (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                        $movieList = $movieList->where('is_restricted', 0);
                    (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                        $videoList = $videoList->where('is_restricted', 0);

                    // Apply relationships to both queries
                    $movieList = $movieList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                        'entertainmentGenerMappings',
                        'plan',
                        'entertainmentReviews',
                        'entertainmentTalentMappings',
                        'entertainmentStreamContentMappings',
                        'season'
                    ])->withCount(['WatchList as is_in_watchlist' => function ($query) use ($request) {
                        $query->where('user_id', $request->user_id)
                            ->where('profile_id', $request->profile_id);
                    }]);



                    $videoList = $videoList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                        'plan',
                    ])->withCount(['WatchList as is_in_watchlist' => function ($query) use ($request) {
                        $query->where('user_id', $request->user_id)
                            ->where('profile_id', $request->profile_id);
                    }]);

                    // Get all items and merge them
                    $allEntertainment = $movieList->get();
                    $allVideos = $videoList->get();
                    $entertainmentList = $allEntertainment
                        ->merge($allVideos)
                        ->sortBy('release_date')
                        ->values();

                    // Manual pagination for merged collection
                    $total = $entertainmentList->count();
                    $currentPage = $request->input('page', 1);
                    $offset = ($currentPage - 1) * $perPage;
                    $items = $entertainmentList->slice($offset, $perPage)->values();

                    $entertainment = new \Illuminate\Pagination\LengthAwarePaginator(
                        $items,
                        $total,
                        $perPage,
                        $currentPage,
                        ['path' => $request->url(), 'pageName' => 'page']
                    );
                    break;

                case 'movie':
                case 'tvshow':
                    $entertainmentList = Entertainment::where('release_date', '>', $todayDate)
                        ->where('status', 1)
                        ->where('type', $request->type)
                        ->where('deleted_at',null);
                    $entertainmentList = $entertainmentList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                    ]);
                    $entertainmentList = $entertainmentList->when($request->has('is_restricted'), function($q) use ($request) {
                        $q->where('is_restricted', $request->is_restricted);
                    });
                    break;

                case 'video':
                    $entertainmentList = Video::where('release_date', '>', $todayDate)
                        ->where('status', 1)
                        ->where('deleted_at',null);
                    $entertainmentList = $entertainmentList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                    ]);
                    $entertainmentList = $entertainmentList->when($request->has('is_restricted'), function($q) use ($request) {
                        $q->where('is_restricted', $request->is_restricted);
                    });
                    break;
                default:
                return [
                    'status' => false,
                    'message' => 'Invalid type'
                ];
            }

            // Only apply these filters if we don't already have a paginated result (i.e., not 'all' type)
            if ($request->type !== 'all') {
                // Don't apply type filter for video since we're already querying Video model
                if ($request->filled('type') && $request->type !== 'video') {
                    $entertainmentList->where('type', $request->type);
                }

                isset($request->is_restricted) && $entertainmentList = $entertainmentList->where('is_restricted', $request->is_restricted);
                (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                    $entertainmentList = $entertainmentList->where('is_restricted', 0);

                if($request->type == 'video'){
                    $entertainmentList = $entertainmentList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                        'plan',
                    ])->withCount(['WatchList as is_in_watchlist' => function ($query) use ($request) {
                        $query->where('user_id', $request->user_id)
                            ->where('profile_id', $request->profile_id);
                    }]);
                }else{

                    $entertainmentList = $entertainmentList->with([
                        'UserReminder' => function ($query) use ($request) {
                            $query->where('user_id', $request->user_id);
                        },
                        'entertainmentGenerMappings',
                        'plan',
                        'entertainmentReviews',
                        'entertainmentTalentMappings',
                        'entertainmentStreamContentMappings',
                        'season'
                    ])->withCount(['WatchList as is_in_watchlist' => function ($query) use ($request) {
                        $query->where('user_id', $request->user_id)
                            ->where('profile_id', $request->profile_id);
                    }]);
                }
                $entertainment = $entertainmentList->paginate($perPage);
            }
            $entertainment->setCollection(
                $entertainment->getCollection()->map(function($item) use ($device_type) {
                    $item->posterImage = $device_type == 'tv'
                        ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$item->type)
                        : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                    return $item;
                })
            );
            $html = '';
            if ($request->has('is_ajax') && $request->is_ajax == 1) {

                $responseCollection = ComingSoonResourceV3::collection($entertainment);



                foreach ($responseCollection->toArray($request) as $comingSoonData) {
                    if (isenablemodule($comingSoonData['type']) == 1) {
                        $userId = auth()->id();
                        if ($userId) {
                            $contentType = $comingSoonData['type'] ?? 'movie';
                            $isInWatchList = WatchList::where('entertainment_id', $comingSoonData['id'])
                                ->where('user_id', $userId)
                                ->where('type', $contentType)
                                ->where('profile_id', getCurrentProfile($userId, $request))
                                ->exists();
                            $comingSoonData['is_watch_list'] = $isInWatchList ? true : false;
                        }
                        $html .= view('frontend::components.card.card_comingsoon', ['data' => $comingSoonData])->render();
                    }
                }
            }

            return [
                'data' => ComingSoonResourceV3::collection($entertainment),
                'html' => $html,
                'hasMore' => $entertainment->hasMorePages()
            ];
        });

        // Safe access to keys
        $html = $responseData['html'] ?? '';
        $hasMore = $responseData['hasMore'] ?? false;
        $data = $responseData['data'] ?? [];


        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            return ApiResponse::success(
                null,
                __('movie.coming_soon_list'),
                200,
                ['html' => $html, 'hasMore' => $hasMore]
            );
        }

        return ApiResponse::success($data, __('movie.coming_soon_list'), 200);
    }

    public function saveReminder(Request $request)
    {
        $user = auth()->user();
        $reminderData = $request->all();
        $reminderData['user_id'] = $user->id;

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user->id, $request);

        $reminderData['profile_id'] = $profile_id;



        $entertainment = $request->entertainment_id ? Entertainment::where('id', $request->entertainment_id)->first() : null;

        if($entertainment != null){
            $reminderData['release_date'] = $request->release_date ?? $entertainment->release_date;
        }


        $reminders = UserReminder::updateOrCreate(
            ['entertainment_id' => $request->entertainment_id, 'user_id' => $user->id, 'profile_id'=>$profile_id],
            $reminderData
        );

        // Reminders are user-specific, no cache clearing needed

        $message = $reminders->wasRecentlyCreated ? __('movie.reminder_add') : __('movie.reminder_update');
        $result = $reminders;

        return ApiResponse::success(null, $message, 200);
    }

    public function saveEntertainmentViews(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();
        $data['user_id'] = $user->id;
        $viewData = EntertainmentView::where('entertainment_id', $request->entertainment_id)->where('user_id', $user->id)->first();

        // Views are user-specific, no cache clearing needed

        if (!$viewData) {
            $views = EntertainmentView::create($data);
            $message = __('movie.view_add');
        } else {
            $message = __('movie.already_added');
        }

        return ApiResponse::success(null, $message, 200);
    }
    public function deleteReminder(Request $request)
    {
        $user = auth()->user();

        $ids = $request->is_ajax == 1 ? $request->id : explode(',', $request->id);

        $entertainment = Entertainment::whereIn('id',$ids)->get();

        $reminders = UserReminder::whereIn('entertainment_id', $ids)->where('user_id', $user->id)->forceDelete();

        // Reminders are user-specific, no cache clearing needed

        if ($reminders == null) {

            $message = __('movie.reminder_add');

            return ApiResponse::error($message, 400);
        }

        $message = __('movie.reminder_remove');


        return ApiResponse::success(null, $message, 200);
    }
    public function deleteDownload(Request $request)
    {
        $user = auth()->user();

        $ids = explode(',', $request->id);

        $download = EntertainmentDownload::whereIn('id', $ids)->forceDelete();

        // Downloads are user-specific, no cache clearing needed

        if ($download == null) {

            $message = __('movie.download');

            return ApiResponse::error($message, 400);
        }

        $message = __('movie.download');


        return ApiResponse::success(null, $message, 200);
    }

    public function episodeDetailsV2(Request $request)
    {
        $user_id = $request->user_id;
        $episode_id = $request->episode_id;

        $cacheKey = 'episode_v2' . $episode_id .'_'.$request->profile_id;
        $responseData = Cache::get($cacheKey);

        if (!$responseData) {
            $episode = Episode::selectRaw('episodes.*,
                    (select id from entertainment_downloads where entertainment_id = episodes.id
                    AND user_id = ?
                    AND entertainment_type = "episode"
                    AND is_download = 1
                    limit 1) download_id,
                    e.language,
                    plan.level as plan_level,
                    GROUP_CONCAT(egm.genre_id) as genre_ids
                ', [$user_id])
                ->leftJoin('entertainments as e','episodes.entertainment_id','=','e.id')
                ->leftJoin('plan','episodes.plan_id','=','plan.id')
                ->join('entertainment_gener_mapping as egm','egm.entertainment_id','=','e.id');
               
                
            isset(request()->is_restricted) && $episode = $episode->where('is_restricted', request()->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $episode = $episode->where('is_restricted',0);

            $episode = $episode->where('episodes.id', $episode_id)
                ->with('EpisodeStreamContentMapping')
                ->first();

            if ($request->has('user_id')) {
                $continueWatch = ContinueWatch::where('episode_id', $episode->id)
                ->where('user_id', $user_id)->where('profile_id', $request->profile_id)
                ->where('entertainment_type', 'tvshow')
                ->first();
                $episode['continue_watch'] = $continueWatch;

                $genre_ids = isset($episode->genre_ids) ? explode(",",$episode->genre_ids) : NULL;
                $episode['user_id'] = $user_id;
                $episodeId = isset($episode->id) ? $episode->id : 0;
                $episode['moreItems'] = Entertainment::get_more_items($episodeId,$genre_ids);
                $episode['genre_data'] = Genres::whereIn('id', $genre_ids)->get();
            }

            $genre_ids = isset($episode->genre_ids) ? explode(",",$episode->genre_ids) : NULL;
           

            $episodeId = isset($episode->id) ? $episode->id : 0;
            $episode['moreItems'] = Entertainment::get_more_items($episodeId,$genre_ids);
            $episode['genre_data'] = Genres::whereIn('id', $genre_ids)->get();
            $episode['genre_data'] = Genres::whereIn('id', $genre_ids)->get();
            $episode['subtitles'] = Subtitle::where('entertainment_id',$episode->id)->where('type','episode')->get();

            $responseData = new EpisodeDetailResourceV2($episode);
            Cache::put($cacheKey, $responseData);
        }

        return ApiResponse::success($responseData, __('movie.episode_details'), 200);
    }

    public function tvshowDetailsV2(Request $request)
    {

        $tvshow_id = $request->tvshow_id;

        $cacheKey = 'tvshow_v2' . $tvshow_id . '_' . $request->profile_id;

        $responseData = Cache::get($cacheKey);


        if (empty($responseData))
        {
            $user_id = isset($request->user_id) ? $request->user_id : 0;
            $profile_id = isset($request->user_id) ? $request->profile_id : 0;

            $tvshow = Entertainment::get_first_tvshow($tvshow_id,$user_id,$profile_id);
            if ($tvshow) {
                $tvshow->load('entertainmentGenerMappings.genre', 'entertainmentTalentMappings.talentprofile', 'season');
            }
           
            $tvshow['reviews'] = $tvshow->entertainmentReviews ?? null;

            if ($request->has('user_id')) {
                $user_id = $request->user_id;
                $tvshow['user_id'] = $user_id;
                $tvshow['is_watch_list'] = (int) WatchList::where('entertainment_id', $request->tvshow_id)->where('user_id', $user_id)->where('type', 'tvshow')->where('profile_id', $request->profile_id)->exists();
                $tvshow['your_review'] =  $tvshow->entertainmentReviews ? $tvshow->entertainmentReviews->where('user_id', $user_id)->first() :null;

                if ($tvshow['your_review']) {
                    $tvshow['reviews'] = $tvshow['reviews']->where('user_id', '!=', $user_id);
                }
            }

            $responseData = new TvshowDetailResourceV2($tvshow);
            Cache::put($cacheKey, $responseData);
        }

        return ApiResponse::success($responseData, __('movie.tvshow_details'), 200);
    }

    public function movieDetailsV2(Request $request)
    {

        $movieId = $request->movie_id;

        $cacheKey = 'movie_v2' . $movieId . '_'.$request->profile_id;

        $responseData = Cache::get($cacheKey);

        if (!$responseData)
        {
            $user_id = isset($request->user_id) ? $request->user_id : 0;
            $profile_id = isset($request->profile_id) ? $request->profile_id : 0;
            $device_id = isset($request->device_id) ? $request->device_id : 0;

            $movie = Entertainment::get_movie($movieId,$user_id,$profile_id,$device_id)->first();

            $movie['reviews'] = $movie->entertainmentReviews ?? null;

            $movie['subtitles'] = $movie->subtitles ?? null;

            if ($request->has('user_id')) {

                $user_id = $request->user_id;

                $movie->user_id = $user_id;
                $movie['is_watch_list'] = (int) WatchList::where('entertainment_id', $request->movie_id)->where('user_id', $user_id)->where('type', 'movie')->where('profile_id', $request->profile_id)->exists();
                if ($movie['your_review_id']) {
                    $movie['reviews'] = $movie['reviews']->where('user_id', '!=', $user_id);
                }



            }

            $responseData = new MovieDetailDataResourceV2($movie);
            Cache::put($cacheKey, $responseData);
        }

        return ApiResponse::success($responseData, __('movie.movie_details'), 200);
    }

    public function tvshowListV2(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $tvshowList = Entertainment::query()
        ->selectRaw('entertainments.id,entertainments.name,entertainments.description,entertainments.type,entertainments.price,entertainments.purchase_type,entertainments.access_duration,entertainments.discount,entertainments.available_for,entertainments.trailer_url_type,entertainments.plan_id,plan.level as plan_level,entertainments.movie_access,entertainments.language,entertainments.imdb_rating,entertainments.content_rating,entertainments.duration,entertainments.release_date,entertainments.is_restricted,entertainments.video_upload_type,entertainments.video_url_input,entertainments.enable_quality,entertainments.download_url,entertainments.poster_url as poster_image,entertainments.poster_tv_url as poster_tv_image,entertainments.thumbnail_url as thumbnail_image,GROUP_CONCAT(egm.genre_id) as genre_ids,GROUP_CONCAT(egm.genre_id) as genres,entertainments.trailer_url,entertainments.trailer_url as base_url,entertainments.status,entertainments.created_by,entertainments.updated_by,entertainments.deleted_by,entertainments.created_at,entertainments.updated_at,entertainments.deleted_at')
        ->join('entertainment_gener_mapping as egm','egm.entertainment_id','=','entertainments.id')
        ->leftJoin('plan','plan.id','=','entertainments.plan_id')
        ->with('episodeV2')
        ->where('entertainments.type', 'tvshow')
        ->where('entertainments.release_date', '<=', Carbon::now()->format('Y-m-d'))
        ->groupBy('entertainments.id')
        ->whereHas('episodeV2');



        if ($request->has('search')) {
            $searchTerm = $request->search;
            $tvshowList->where(function ($query) use ($searchTerm) {
                $query->where('entertainments.name', 'like', "%{$searchTerm}%");
            });
        }

        isset(request()->is_restricted) && $tvshowList = $tvshowList->where('is_restricted', request()->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $tvshowList = $tvshowList->where('is_restricted',0);

        $tvshowList = $tvshowList->where('entertainments.status', 1);

        $tvshows = $tvshowList->orderBy('entertainments.id', 'desc');
        $tvshows = $tvshows->paginate($perPage);

        $userId = auth()->id() ?? $request->user_id;
        if ($userId) {
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $tvshows->getCollection()->transform(function ($tvshow) use ($userId, $profile_id) {
                $isInWatchList = WatchList::where('entertainment_id', $tvshow->id)
                    ->where('user_id', $userId)
                    ->where('type', 'tvshow')
                    ->where('profile_id', $profile_id)
                    ->exists();
                $tvshow->is_watch_list = (int) $isInWatchList;
                return $tvshow;
            });
        }
        $responseData = TvshowResourceV2::collection($tvshows);


        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';

            foreach($responseData->toArray($request) as $tvShowData) {
                $html .= view('frontend::components.card.card_entertainment', ['value' => $tvShowData])->render();
            }

            $hasMore = $tvshows->hasMorePages();

            return ApiResponse::success(
                null,
                __('movie.tvshow_list'),
                200,
                ['html' => $html, 'hasMore' => $hasMore]
            );
        }


        return ApiResponse::success($responseData, __('movie.tvshow_list'), 200);
    }

    public function movieListV2(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $accessType = $request->input('access_type');

        $movieList = Entertainment::selectRaw('entertainments.id,entertainments.id as e_id,entertainments.name,entertainments.type,entertainments.price,entertainments.purchase_type,entertainments.access_duration,entertainments.discount,entertainments.available_for,entertainments.plan_id,plan.level as plan_level,entertainments.description,entertainments.trailer_url_type,entertainments.is_restricted,entertainments.language,entertainments.imdb_rating,entertainments.content_rating,entertainments.duration,entertainments.video_upload_type,GROUP_CONCAT(egm.genre_id) as genres,entertainments.release_date,entertainments.trailer_url,entertainments.video_url_input, entertainments.poster_url as poster_image, entertainments.poster_tv_url as poster_tv_image, entertainments.thumbnail_url as thumbnail_image,entertainments.trailer_url as base_url,entertainments.movie_access')
        ->join('entertainment_gener_mapping as egm','egm.entertainment_id','=','entertainments.id')
        ->leftJoin('plan','plan.id','=','entertainments.plan_id')

        ->when(in_array($accessType, ['pay-per-view', 'purchased']), function ($query) {
            return $query->where('entertainments.movie_access', 'pay-per-view');
        }, function ($query) use ($request) {
            if ($request->filled('actor_id')) {
                return $query->whereIn('entertainments.type', ['movie', 'tvshow']);
            }
            return $query->where('entertainments.type', 'movie');
        });

        if ($accessType === 'purchased' && auth()->check()) {
            $userId = auth()->id();
            $movieList->whereExists(function ($subQuery) use ($userId) {
                $subQuery->select(DB::raw(1))
                    ->from('pay_per_views')
                    ->whereColumn('pay_per_views.movie_id', 'entertainments.id')
                    ->where('pay_per_views.user_id', $userId);
            });
        }

        isset($request->is_restricted) && $movieList = $movieList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $movieList = $movieList->where('is_restricted',0);

       $movieList = $movieList->where('entertainments.status', 1)
            ->where(function ($query) {
                $query->where('release_date', '<=', Carbon::now()->format('Y-m-d'))
                      ->orWhereNull('release_date');
            });

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $movieList->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%");
            });
        }
        if ($request->filled('genre_id')) {
            $genreId = $request->genre_id;
            $movieList->where('egm.genre_id',$genreId);

        }


        if ($request->filled('actor_id'))
        {
            $actorId = $request->actor_id;

            $isMovieModuleEnabled = isenablemoduleV2('movie');
            $isTVShowModuleEnabled = isenablemoduleV2('tvshow');

            $movies = $movieList->where(function ($query) use ($actorId, $isMovieModuleEnabled, $isTVShowModuleEnabled)
            {
                if ($isMovieModuleEnabled && $isTVShowModuleEnabled)
                {
                    $query->where('entertainments.type', 'movie')
                          ->orWhere('entertainments.type', 'tvshow');
                } elseif ($isMovieModuleEnabled) {
                    $query->where('entertainments.type', 'movie');
                } elseif ($isTVShowModuleEnabled) {
                    $query->where('entertainments.type', 'tvshow');
                }
            })
            ->join('entertainment_talent_mapping as etm', function($q) use ($actorId)
            {
                $q->on('etm.entertainment_id','=','entertainments.id')
                ->where('etm.talent_id', $actorId);
            });
        }
        if ($request->filled('language')) {
            $movieList->where('entertainments.language', $request->language);
        }

        $movies = $movieList->whereNull('entertainments.deleted_at')->groupBy('entertainments.id')->orderBy('entertainments.id', 'desc')->paginate($perPage);

        $userId = auth()->id() ?? $request->user_id;
        if ($userId) {
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $movies->getCollection()->transform(function ($movies) use ($userId, $profile_id) {
                $isInWatchList = WatchList::where('entertainment_id', $movies->id)
                    ->where('user_id', $userId)
                    ->where('type', 'movie')
                    ->where('profile_id', $profile_id)
                    ->exists();
                $movies->is_watch_list = (int) $isInWatchList;
                return $movies;
            });
        }

         $responseData = MoviesResourceV2::collection($movies);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';
            foreach ($responseData->toArray($request) as $movieData)
            {
                if(isenablemoduleV2($movieData['type']) == 1)
                {
                    $html .= view('frontend::components.card.card_entertainment', ['value' => $movieData])->render();

                }
            }

            $hasMore = $movies->hasMorePages();

            return ApiResponse::success(
                null,
                __('movie.movie_list'),
                200,
                ['html' => $html, 'hasMore' => $hasMore]
            );
        }

        return ApiResponse::success($responseData, __('movie.movie_list'), 200);
    }

    public function genreContentList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $accessType = $request->input('access_type');
        $genreId = $request->input('genre_id');
        $actorId = $request->input('actor_id');
        $directorId = $request->input('director_id');
        $type = $request->input('type');

        $contentList = Entertainment::select([
            'entertainments.id',
            'entertainments.slug',
            'entertainments.name',
            'entertainments.type',
            'entertainments.price',
            'entertainments.purchase_type',
            'entertainments.access_duration',
            'entertainments.discount',
            'entertainments.available_for',
            'entertainments.plan_id',
            'entertainments.description',
            'entertainments.trailer_url_type',
            'entertainments.is_restricted',
            'entertainments.language',
            'entertainments.imdb_rating',
            'entertainments.content_rating',
            'entertainments.duration',
            'entertainments.video_upload_type',
            'entertainments.release_date',
            'entertainments.trailer_url',
            'entertainments.video_url_input',
            'entertainments.poster_url',
            'entertainments.poster_tv_url',
            'entertainments.thumbnail_url',
            'entertainments.movie_access',
        ])
        ->with([
            'plan:id,level',
            'genresdata:id,name'
        ]);

        // Filter by genre_id (required)
        if ($genreId) {
            $contentList->whereHas('entertainmentGenerMappings', function ($q) use ($genreId) {
                $q->where('genre_id', $genreId);
            });
        }

        // Filter by type (movie, tvshow, or both)
        if ($type) {
            if ($type === 'both') {
                $contentList->whereIn('entertainments.type', ['movie', 'tvshow']);
                // For 'both', only show TV shows that have at least one season with at least one episode
                $contentList->where(function ($query) {
                    $query->where('entertainments.type', 'movie')
                        ->orWhere(function ($q) {
                            $q->where('entertainments.type', 'tvshow')
                                ->whereHas('season', function ($seasonQuery) {
                                    $seasonQuery->where('status', 1)
                                        ->whereNull('deleted_at')
                                        ->whereHas('episodes', function ($episodeQuery) {
                                            $episodeQuery->where('status', 1)
                                                ->whereNull('deleted_at');
                                        });
                                });
                        });
                });
            } else {
                $contentList->where('entertainments.type', $type);
                // For TV shows, only show those that have at least one season with at least one episode
                if ($type === 'tvshow') {
                    $contentList->whereHas('season', function ($seasonQuery) {
                        $seasonQuery->where('status', 1)
                            ->whereNull('deleted_at')
                            ->whereHas('episodes', function ($episodeQuery) {
                                $episodeQuery->where('status', 1)
                                    ->whereNull('deleted_at');
                            });
                    });
                }
            }
        }

        // Filter by actor_id if provided
        if ($actorId) {
            $contentList->whereHas('entertainmentTalentMappings', function ($q) use ($actorId) {
                $q->where('talent_id', $actorId);
            });
        }

        // Filter by director_id if provided
        if ($directorId) {
            $contentList->whereHas('entertainmentTalentMappings', function ($q) use ($directorId) {
                $q->where('talent_id', $directorId);
            });
        }

        // Filter by access type
        if (in_array($accessType, ['pay-per-view', 'purchased'])) {
            $contentList->where('entertainments.movie_access', 'pay-per-view');
        }

        // Filter for purchased content if access_type is 'purchased'
        if ($accessType === 'purchased' && auth()->check()) {
            $userId = auth()->id();
            $contentList->whereExists(function ($subQuery) use ($userId) {
                $subQuery->select(DB::raw(1))
                    ->from('pay_per_views')
                    ->whereColumn('pay_per_views.movie_id', 'entertainments.id')
                    ->where('pay_per_views.user_id', $userId);
            });
        }

        // Apply other filters
        isset($request->is_restricted) && $contentList = $contentList->where('is_restricted', $request->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $contentList = $contentList->where('is_restricted',0);

        $contentList = $contentList->where('entertainments.status', 1)
            ->where(function ($query) {
                $query->whereDate('release_date', '<=', Carbon::now())
                      ->orWhereNull('release_date');
            });

        // Search filter
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $contentList->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%");
            });
        }

        // Language filter
        if ($request->filled('language')) {
            $contentList->where('entertainments.language', $request->language);
        }

        $contents = $contentList->whereNull('entertainments.deleted_at')
            ->orderBy('entertainments.id', 'desc')
            ->paginate($perPage);


        $userId = auth()->id() ?? $request->user_id;
        if ($userId) {
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $contents->getCollection()->transform(function ($content) use ($userId, $profile_id) {
                $isInWatchList = WatchList::where('entertainment_id', $content->id)
                    ->where('user_id', $userId)
                    ->where('type', $content->type)
                    ->where('profile_id', $profile_id)
                    ->exists();
                $content->is_watch_list = (int) $isInWatchList;

                // Preserve previously aliased fields for downstream consumers
                $content->e_id = $content->id;
                $content->poster_image = setBaseUrlWithFileName($content->poster_url, 'image', $content->type);
                $content->poster_tv_image = setBaseUrlWithFileName($content->poster_tv_url, 'image', $content->type);
                $content->thumbnail_image = setBaseUrlWithFileName($content->thumbnail_url, 'image', $content->type);
                $content->base_url = $content->trailer_url;
                $content->plan_level = optional($content->plan)->level ?? null;

                return $content;
            });
        } else {
            $contents->getCollection()->transform(function ($content) {
                // Preserve previously aliased fields for downstream consumers
                $content->e_id = $content->id;
                $content->poster_image = setBaseUrlWithFileName($content->poster_url, 'image', $content->type);
                $content->poster_tv_image = setBaseUrlWithFileName($content->poster_tv_url, 'image', $content->type);
                $content->thumbnail_image = setBaseUrlWithFileName($content->thumbnail_url, 'image', $content->type);
                $content->base_url = $content->trailer_url;
                $content->plan_level = optional($content->plan)->level ?? null;
                return $content;
            });
        }

        $responseData = commonContentResourceV3::collection($contents);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';
            foreach ($responseData->toArray($request) as $contentData) {
                if(isenablemoduleV2($contentData['type']) == 1 ) {
                    if($contentData['type'] == 'movie'){
                        $html .= view('frontend::components.card.card_movie', ['values' => [$contentData]])->render();
                    }elseif($contentData['type'] == 'tvshow'){
                        $html .= view('frontend::components.card.card_tvshow', ['values' => [$contentData]])->render();
                    }
                }
            }

            $hasMore = $contents->hasMorePages();

            return ApiResponse::success(
                null,
                __('movie.movie_list'),
                200,
                ['html' => $html, 'hasMore' => $hasMore]
            );
        }

        return ApiResponse::success($responseData, __('movie.movie_list'), 200);
    }

    public function contentDetailsV3(Request $request)
    {

        $id = $request->id;
        $device_type = getDeviceType($request);

        // Create unique cache key based on all relevant parameters including user_id
        $userId = $request->user_id ?? auth()->id() ;
        $cacheKey = 'common_content_detail_v3_'.$id . '_' . $request->profile_id . '_' . $request->type . '_' . ($userId ?? 0);

         $responseData = cacheApiResponse($cacheKey, 10, function () use ($request, $id,$device_type, $userId) {


            $user_id = isset($request->user_id) ? $request->user_id : 0;
            $profile_id = isset($request->profile_id) ? $request->profile_id : 0;
            $device_id = isset($request->device_id) ? $request->device_id : 0;

            $getDeviceTypeData = Subscription::checkPlanSupportDevice($userId,$device_type);
            $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true); // Decode to associative array
            $isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;
            $userPlanLevel = 0;
            $purchasedIds = []; // Initialize purchasedIds array
            
            if ($userId) {
                $userLevel = Subscription::select('plan_id')
                    ->where(['user_id' => $userId, 'status' => 'active'])
                    ->latest()
                    ->first();
                $userPlanLevel = $userLevel->plan_id ?? 0;
                
                // Optimized: Fetch all purchased IDs in one query, grouped by type for setContentAccess
                $purchasedIds = PayPerView::where('user_id', $userId)
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

           switch ($request->type) {
                case 'movie':
                    $movie = Entertainment::get_movie($id, $user_id, $profile_id, $device_id)
                        ->with(['clips' => function ($q) use ($request) { 
                            $q->select('id', 'content_id', 'title', 'content_type','type', 'url','poster_url','tv_poster_url')
                              ->where('content_type', $request->type);
                        }])->first();
                  
                    if (!$movie) {
                        return [
                            'status' => false,
                            'message' => 'Content not found.'
                        ];
                    }

                    // Check if movie is inactive
                    if ($movie->status != 1) {
                        return [
                            'status' => false,
                            'message' => 'This content is no longer available.'
                        ];
                    }

                    // Check type after loading (avoid separate query)
                    if ($movie->type !== $request->type) {
                        return [
                            'status' => false,
                            'message' => 'Content type mismatch. Requested type: '.$request->type.', actual type: '.$movie->type
                        ];
                    }

                    // Optimized: Batch load all related data in parallel
                    [$videoQualities, $downloadMappings] = [
                        EntertainmentStreamContentMapping::where('entertainment_id', $id)
                            ->select('id', 'type as url_type', 'url', 'quality')->get(),
                        EntertainmnetDownloadMapping::where('entertainment_id', $id)
                            ->when($request->download_quality, fn($q) => $q->where('quality', $request->download_quality))
                            ->get()
                    ];

                    $videoDefaultQuality = [
                        'quality' => "default_quality",
                        'url' => $movie['video_upload_type'] == 'Local' ? setBaseUrlWithFileName(trim($movie['video_url_input']),'video', $request->type ) : trim($movie['video_url_input']),
                        'url_type' => trim($movie['video_upload_type']),
                    ];

                    // Optimized: watched_time already loaded via addSelect in get_movie
                    $watched_time = $movie->watched_time ?? null;
                    
                    // Optimized: Use already loaded entertainmentGenerMappings instead of whereHas query
                    $activeGenres = $movie->entertainmentGenerMappings
                        ->where('genre.status', 1)
                        ->pluck('genre')
                        ->filter()
                        ->unique('id');

                    $genre_data = $activeGenres->pluck('name')->toArray();
                    $genre_ids  = $activeGenres->pluck('id')->toArray();

                    $movie['genre_data'] = $genre_data;
                    if($genre_ids){
                        // Optimized: Use subquery with proper exclusion and limit
                        $more_items = Entertainment::whereIn('id', function($query) use ($genre_ids, $id) {
                            $query->select('entertainment_id')
                                ->from('entertainment_gener_mapping')
                                ->whereIn('genre_id', $genre_ids)
                                ->where('entertainment_id', '!=', $id)
                                ->groupBy('entertainment_id');
                        })
                        ->select('id', 'name', 'type', 'trailer_url_type', 'IMDb_rating as imdb_rating', 'poster_url', 'thumbnail_url', 'poster_tv_url', 'trailer_url', 'movie_access', 'plan_id')
                        ->where('type', 'movie')
                        ->where('id', '!=', $id) // Optimized: Exclude in query instead of after fetch
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->when(isset(request()->is_restricted), fn($q) => $q->where('is_restricted', request()->is_restricted))
                        ->when(!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0, fn($q) => $q->where('is_restricted', 0))
                        ->when($request->has('is_released') && !empty($request->is_released), fn($q) => $q->where('release_date', '<=', Carbon::now()))
                        ->limit(10)
                        ->get();
                            // dd($more_items);

                        // Optimized: Use already calculated userPlanLevel from line 2576 (avoid duplicate query)
                        // $userPlanLevel already set above

                        $more_items = $more_items->map(function($item) use ($userId, $userPlanLevel, $isDeviceSupported, $device_type, $purchasedIds) {

                            $access = in_array($item->type, ['movie', 'tvshow']) ? $item->movie_access : $item->access;
                            $itemArray = [
                                'id' => $item->id,
                                'access' => $access,
                                'plan_id' => $item->plan_id,
                                'type' => $item->type,
                            ];

                            $itemArray = setContentAccess($itemArray, $userId, $userPlanLevel, $purchasedIds);

                            // Set the access control properties on the model
                            $item->has_content_access = $itemArray['has_content_access'];
                            $item->required_plan_level = $itemArray['required_plan_level'];
                            $item->isDeviceSupported = $isDeviceSupported;
                            $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                            return $item;
                        });
                    }else{
                        $more_items =[];
                    };
                    $movie['more_items'] = $more_items;
                    
                    // Optimized: Use already loaded entertainmentTalentMappings (no N+1)
                    // Filter by status = 1 and deleted_at = null to exclude disabled actors/directors
                    $actors = $movie->entertainmentTalentMappings
                        ->filter(function($mapping) {
                            return $mapping->talentprofile && 
                                   $mapping->talentprofile->type === 'actor' &&
                                   $mapping->talentprofile->status === 1 &&
                                   empty($mapping->talentprofile->deleted_at);
                        })
                        ->pluck('talentprofile')
                        ->filter();
                    
                    $directors = $movie->entertainmentTalentMappings
                        ->filter(function($mapping) {
                            return $mapping->talentprofile && 
                                   $mapping->talentprofile->type === 'director' &&
                                   $mapping->talentprofile->status === 1 &&
                                   empty($mapping->talentprofile->deleted_at);
                        })
                        ->pluck('talentprofile')
                        ->filter();

                    $movie['actors'] = $actors;
                    $movie['directors'] = $directors;
                    
                    // Optimized: Use withCount to avoid N+1 query problem
                    $seasonData = $movie->type === 'tvshow' && $movie->relationLoaded('season')
                        ? $movie->season
                            ->filter(fn($season) => $season->status == 1 && is_null($season->deleted_at))
                            ->map(function ($season) {
                                return [
                                    'id'            => $season->id,
                                    'name'          => $season->name,
                                    'season_id'     => $season->id,
                                    'total_episode' => isset($season->episodes_count) ? $season->episodes_count : 0,
                                ];
                            })->values()
                        : null;

                    // dd($movie->type,$seasonData);

                     $movie['season_data'] = $seasonData ;
                      $movie['access'] = $movie->movie_access ;
                      
                      // Optimized: Use already loaded reviews (already sorted by created_at desc in query)
                      if ($movie->relationLoaded('entertainmentReviews') && $movie->relationLoaded('reviews')) {
                          $totalReviews = $movie->entertainmentReviews->count();
                          $myReview = $movie->reviews->first();
                          $myReviewData = $myReview ? [
                              "id" => $myReview->id,
                              "rating" => (float) $myReview->rating,
                              "review" => $myReview->review,
                              "username" => optional($myReview->user)->full_name ?? '',
                              "profile_image" => setBaseUrlWithFileName(optional($myReview->user)->file_url, 'image', 'users'),
                              "updated_at" => $myReview->updated_at ?? null,
                          ] : null;
                          
                          // Optimized: Use already sorted data (sorted by created_at desc in query), filter and take
                          $otherReviews = $movie->entertainmentReviews
                              ->where('user_id', '!=', $user_id)
                              ->take(3)
                              ->map(function($data) {
                                  return [
                                      "id" => $data->id,
                                      "rating" => (float) $data->rating,
                                      "review" => $data->review,
                                      "username" => optional($data->user)->full_name ?? '',
                                      "profile_image" => setBaseUrlWithFileName(optional($data->user)->file_url, 'image', 'users'),
                                      "updated_at" => $data->updated_at ?? null,
                                  ];
                              })->values()->toArray();
                          
                          $movie['review'] = [
                              'total_reviews' => $totalReviews,
                              'my_review' => $myReviewData,
                              'other_reviews' => $otherReviews
                          ];
                      } else {
                          // Fallback: should not happen if eager loading works correctly
                          $movie['review'] = Entertainment::getReviewData($id, $user_id);
                      }

                    break;

                case 'tvshow':

                    $movie = Entertainment::get_tvshowV3($id, $user_id, $profile_id, $device_id)
                        ->with(['clips' => function ($q) use ($request) { 
                            $q->select('id', 'content_id','title', 'content_type','type', 'url','poster_url','tv_poster_url')
                              ->where('content_type','tv_show');
                        }])->first();

                    if (!$movie) {
                        return [
                            'status' => false,
                            'message' => 'TV Show not found.'
                        ];
                    }

                    // Check if TV show is inactive
                    if ($movie->status != 1) {
                        return [
                            'status' => false,
                            'message' => 'This content is no longer available.'
                        ];
                    }

                    // Check type after loading (avoid separate query)
                    if ($movie->type !== $request->type) {
                        return [
                            'status' => false,
                            'message' => 'Content type mismatch. Requested type: '.$request->type.', actual type: '.$movie->type
                        ];
                    }

                    // Optimized: Batch load all related data in parallel
                    [$videoQualities, $downloadMappings] = [
                        EntertainmentStreamContentMapping::where('entertainment_id', $id)
                            ->select('id', 'type as url_type', 'url', 'quality')->get(),
                        EntertainmnetDownloadMapping::where('entertainment_id', $id)
                            ->when($request->download_quality, fn($q) => $q->where('quality', $request->download_quality))
                            ->get()
                    ];

                    $videoDefaultQuality = [
                        'quality' => "default_quality",
                        'url' => ($movie->video_upload_type ?? '') == 'Local' ? setBaseUrlWithFileName(trim($movie->video_url_input ?? ''),'video', $request->type ) : trim($movie->video_url_input ?? ''),
                        'url_type' => trim($movie->video_upload_type ?? ''),
                    ];

                    // Optimized: watched_time already loaded via addSelect in get_tvshow
                    $watched_time = $movie->watched_time ?? null;
                    
                    // Optimized: Use already loaded entertainmentGenerMappings instead of whereHas query
                    $activeGenres = $movie->entertainmentGenerMappings
                        ->where('genre.status', 1)
                        ->pluck('genre')
                        ->filter()
                        ->unique('id');

                    $genre_data = $activeGenres->pluck('name')->toArray();
                    $genre_ids  = $activeGenres->pluck('id')->toArray();

                    $movie['genre_data'] = $genre_data;

                    if($genre_ids){
                        // Optimized: Use subquery instead of multiple queries
                        $more_items = Entertainment::whereIn('id', function($query) use ($genre_ids, $id) {
                            $query->select('entertainment_id')
                                ->from('entertainment_gener_mapping')
                                ->whereIn('genre_id', $genre_ids)
                                ->where('entertainment_id', '!=', $id)
                                ->groupBy('entertainment_id');
                        })
                        ->select('id', 'name', 'type', 'trailer_url_type', 'IMDb_rating as imdb_rating', 'poster_url', 'thumbnail_url', 'poster_tv_url', 'trailer_url', 'movie_access', 'plan_id')
                        ->where('type', 'tvshow')
                        ->where('id', '!=', $id) // Optimized: Exclude in query instead of after fetch
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->when(isset(request()->is_restricted), fn($q) => $q->where('is_restricted', request()->is_restricted))
                        ->when(!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0, fn($q) => $q->where('is_restricted', 0))
                        ->when($request->has('is_released') && !empty($request->is_released), fn($q) => $q->where('release_date', '<=', Carbon::now()))
                        ->limit(10)
                        ->get();

                        // Optimized: Use already calculated userPlanLevel from line 2576 (avoid duplicate query)
                        $more_items = $more_items->map(function($item) use ($userId, $userPlanLevel, $isDeviceSupported, $device_type, $purchasedIds) {
                            $access = in_array($item->type, ['movie', 'tvshow']) ? $item->movie_access : $item->access;
                            $itemArray = [
                                'id' => $item->id,
                                'access' => $access,
                                'plan_id' => $item->plan_id,
                                'type' => $item->type,
                            ];

                            $itemArray = setContentAccess($itemArray, $userId, $userPlanLevel, $purchasedIds);

                            // Set the access control properties on the model
                            $item->has_content_access = $itemArray['has_content_access'];
                            $item->required_plan_level = $itemArray['required_plan_level'];
                            $item->isDeviceSupported = $isDeviceSupported;
                            $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                            return $item;
                        });
                    }else{
                        $more_items =[];
                    };
                    $movie['more_items'] = $more_items;
                    
                    // Optimized: Use already loaded entertainmentTalentMappings (no N+1)
                    // Filter by status = 1 and deleted_at = null to exclude disabled actors/directors
                    $actors = $movie->entertainmentTalentMappings
                        ->filter(function($mapping) {
                            return $mapping->talentprofile && 
                                   $mapping->talentprofile->type === 'actor' &&
                                   $mapping->talentprofile->status === 1 &&
                                   empty($mapping->talentprofile->deleted_at);
                        })
                        ->pluck('talentprofile')
                        ->filter();
                    
                    $directors = $movie->entertainmentTalentMappings
                        ->filter(function($mapping) {
                            return $mapping->talentprofile && 
                                   $mapping->talentprofile->type === 'director' &&
                                   $mapping->talentprofile->status === 1 &&
                                   empty($mapping->talentprofile->deleted_at);
                        })
                        ->pluck('talentprofile')
                        ->filter();

                    $movie['actors'] = $actors;
                    $movie['casts'] = $actors; // Keep both for compatibility
                    $movie['directors'] = $directors;
                    
                    // Optimized: Use already loaded reviews instead of querying again
                    if ($movie->relationLoaded('entertainmentReviews') && $movie->relationLoaded('reviews')) {
                        $totalReviews = $movie->entertainmentReviews->count();
                        $myReview = $movie->reviews->first();
                        $myReviewData = $myReview ? [
                            "id" => $myReview->id,
                            "rating" => (float) $myReview->rating,
                            "review" => $myReview->review,
                            "username" => optional($myReview->user)->full_name ?? '',
                            "profile_image" => setBaseUrlWithFileName(optional($myReview->user)->file_url, 'image', 'users'),
                            "updated_at" => $myReview->updated_at ?? null,
                        ] : null;
                        
                        // Optimized: Use already sorted data (sorted by created_at desc in query), filter and take
                        $otherReviews = $movie->entertainmentReviews
                            ->where('user_id', '!=', $user_id)
                            ->take(3)
                            ->map(function($data) {
                                return [
                                    "id" => $data->id,
                                    "rating" => (float) $data->rating,
                                    "review" => $data->review,
                                    "username" => optional($data->user)->full_name ?? '',
                                    "profile_image" => setBaseUrlWithFileName(optional($data->user)->file_url, 'image', 'users'),
                                    "updated_at" => $data->updated_at ?? null,
                                ];
                            })->values()->toArray();
                        
                        $movie['review'] = [
                            'total_reviews' => $totalReviews,
                            'my_review' => $myReviewData,
                            'other_reviews' => $otherReviews
                        ];
                    } else {
                        // Fallback: should not happen if eager loading works correctly
                        $movie['review'] = Entertainment::getReviewData($id, $user_id);
                    }
                    
                    // Optimized: Use withCount to avoid N+1 query problem
                    $seasonData = $movie->type === 'tvshow' && $movie->relationLoaded('season')
                        ? $movie->season
                            ->filter(fn($season) => $season->status == 1 && is_null($season->deleted_at))
                            ->map(function ($season) {
                                return [
                                    'id'            => $season->id,
                                    'name'          => $season->name,
                                    'season_id'     => $season->id,
                                    'total_episode' => isset($season->episodes_count) ? $season->episodes_count : 0,
                                ];
                            })->values()
                        : null;

                    $movie['season_data'] = $seasonData;
                    $movie['access'] = $movie->movie_access;

                    break;


                case 'episode':

                    $movie = Episode::get_episode($id, $user_id, $profile_id, $device_id)
                        ->first();

                    if(!$movie){
                        return [
                            'status' => false,
                            'message' => 'Episode not found.'
                        ];
                    }

                    // Optimized: Use already loaded entertainmentGenerMappings instead of separate query
                    $activeGenres = $movie->entertainmentdata && $movie->entertainmentdata->relationLoaded('entertainmentGenerMappings')
                        ? $movie->entertainmentdata->entertainmentGenerMappings
                            ->where('genre.status', 1)
                            ->pluck('genre')
                            ->filter()
                            ->unique('id')
                        : collect();

                    $genre_data = $activeGenres->pluck('name')->toArray();
                    $genre_ids  = $activeGenres->pluck('id')->toArray();

                    $movie['genre_data'] = $genre_data;

                    if($genre_ids){
                        // Optimized: Use subquery instead of multiple queries
                        $more_items = Entertainment::whereIn('id', function($query) use ($genre_ids, $id) {
                            $query->select('entertainment_id')
                                ->from('entertainment_gener_mapping')
                                ->whereIn('genre_id', $genre_ids)
                                ->where('entertainment_id', '!=', $id)
                                ->groupBy('entertainment_id');
                        })
                        ->select('id', 'name', 'type', 'trailer_url_type', 'IMDb_rating as imdb_rating', 'poster_url', 'thumbnail_url', 'poster_tv_url', 'trailer_url', 'movie_access', 'plan_id')
                        ->where('type', 'tvshow')
                        ->where('id', '!=', $id) // Optimized: Exclude in query instead of after fetch
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->when(isset(request()->is_restricted), fn($q) => $q->where('is_restricted', request()->is_restricted))
                        ->when(!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0, fn($q) => $q->where('is_restricted', 0))
                        ->when($request->has('is_released') && !empty($request->is_released), fn($q) => $q->where('release_date', '<=', Carbon::now()))
                        ->limit(10)
                        ->get();

                        // Optimized: Use already calculated userPlanLevel from line 2576 (avoid duplicate query)
                        $more_items = $more_items->map(function($item) use ($userId, $userPlanLevel, $isDeviceSupported, $device_type, $purchasedIds) {
                            $access = in_array($item->type, ['movie', 'tvshow']) ? $item->movie_access : $item->access;
                            $itemArray = [
                                'id' => $item->id,
                                'access' => $access,
                                'plan_id' => $item->plan_id,
                                'type' => $item->type,
                            ];

                            $itemArray = setContentAccess($itemArray, $userId, $userPlanLevel, $purchasedIds);

                            // Set the access control properties on the model
                            $item->has_content_access = $itemArray['has_content_access'];
                            $item->required_plan_level = $itemArray['required_plan_level'];
                            $item->isDeviceSupported = $isDeviceSupported;
                            $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                            return $item;
                        });
                    }else{
                        $more_items =[];
                    };
                    $movie['access'] = $movie->access ?? null;
                    $movie['language'] = $movie->entertainmentdata->language ?? null;
                    $movie['more_items'] = $more_items;

                    // Optimized: Batch load all related data in parallel
                    [$videoQualities, $downloadMappings] = [
                        EpisodeStreamContentMapping::where('episode_id', $id)
                            ->select('id', 'type as url_type', 'url', 'quality')->get(),
                        EpisodeDownloadMapping::where('episode_id', $id)
                            ->when($request->download_quality, fn($q) => $q->where('quality', $request->download_quality))
                            ->get()
                    ];

                    $videoDefaultQuality = [
                        'quality' => "default_quality",
                        'url' => ($movie->video_upload_type ?? '') == 'Local' ? setBaseUrlWithFileName(trim($movie->video_url_input ?? ''),'video', $request->type ) : trim($movie->video_url_input ?? ''),
                        'url_type' => trim($movie->video_upload_type ?? ''),
                    ];

                    // Optimized: watched_time already loaded via addSelect in get_episode
                    $watched_time = $movie->watched_time ?? null;
                    $movie['type'] = 'episode';

                    // Optimized: Use withCount to avoid N+1 query problem
                    $seasonCollection = collect();
                    if ($movie->entertainmentdata && $movie->entertainmentdata->relationLoaded('season')) {
                        $seasonCollection = $movie->entertainmentdata->season
                            ->filter(fn($season) => $season->status == 1 && is_null($season->deleted_at))
                            ->map(function ($season) {
                                return [
                                    'id' => $season->id,
                                    'name' => $season->name,
                                    'season_id' => $season->id,
                                    'total_episode' => isset($season->episodes_count) ? $season->episodes_count : 0,
                                ];
                            })
                            ->values();
                    }
                    
                    if ($movie->seasondata && $movie->seasondata->relationLoaded('episodes_count')) {
                        $tv_show_data = [
                            'id' => $movie->entertainmentdata->id ?? null,
                            'name' => $movie->entertainmentdata->name ?? null,
                            'season_id' => $movie->season_id ?? null,
                            'total_episode' => isset($movie->seasondata->episodes_count) ? $movie->seasondata->episodes_count : ($movie->seasondata->relationLoaded('episodes') ? $movie->seasondata->episodes->count() : 0),
                        ];
                    } else {
                        $tv_show_data = null; // no season linked
                    }

                    $movie['season_data'] = $seasonCollection;
                    $movie['tv_show_data'] = $tv_show_data;
                    break;

                case 'video':
                    $movie = Video::get_video($id, $user_id, $profile_id, $device_id)
                       ->with(['clips' => function ($q) use ($request) { 
                           $q->select('id', 'content_id', 'content_type','title','type', 'url','poster_url','tv_poster_url')
                             ->where('content_type', $request->type);
                       }])->first();

                    if(!$movie){
                        return [
                            'status' => false,
                            'message' => 'Video not found.'
                        ];
                    }

                    // Check if video is inactive
                    if ($movie->status != 1) {
                        return [
                            'status' => false,
                            'message' => 'This content is no longer available.'
                        ];
                    }

                    // Use helper function to set content access
                    // Convert to array format for setContentAccess
                    $movieArray = [
                        'id' => $movie->id,
                        'access' => $movie->access ?? 'free',
                        'plan_id' => $movie->plan_id ?? 0,
                        'type' => 'video',
                        'price' => $movie->price ?? null,
                        'discount' => $movie->discount ?? null,
                        'access_duration' => $movie->access_duration ?? null,
                        'available_for' => $movie->available_for ?? null,
                        'purchase_type' => $movie->purchase_type ?? null,
                    ];
                    $movieArray = setContentAccess($movieArray, $user_id, $userPlanLevel, $purchasedIds);
                    $movie->has_content_access = $movieArray['has_content_access'];
                    $movie->required_plan_level = $movieArray['required_plan_level'];
                    $movie['access'] = $movie->access ?? null;

                    // Optimized: Batch load all related data in parallel
                    [$videoQualities, $downloadMappings] = [
                        VideoStreamContentMapping::where('video_id', $id)
                            ->select('id', 'type as url_type', 'url', 'quality')->get(),
                        VideoDownloadMapping::where('video_id', $id)
                            ->when($request->download_quality, fn($q) => $q->where('quality', $request->download_quality))
                            ->get()
                    ];

                    $videoDefaultQuality = [
                        'quality' => "default_quality",
                        'url' => ($movie->video_upload_type ?? '') == 'Local' ? setBaseUrlWithFileName(trim($movie->video_url_input ?? ''),'video', $request->type ) : trim($movie->video_url_input ?? ''),
                        'url_type' => trim($movie->video_upload_type ?? ''),
                    ];

                    // Optimized: watched_time already loaded via addSelect in get_video
                    $watched_time = $movie->watched_time ?? null;

                    // Optimized: Use subquery and exclude in query instead of after fetch
                    $more_items = Video::where('status', 1)
                        ->where('deleted_at', null)
                        ->where('id', '!=', $id) // Optimized: Exclude in query instead of after fetch
                        ->when(request()->has('is_restricted'), fn($q) => $q->where('is_restricted', request()->is_restricted))
                        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, fn($q) => $q->where('is_restricted', 0))
                        ->when($request->has('is_released') && !empty($request->is_released), fn($q) => $q->where('release_date', '<=', Carbon::now()))
                        ->select('id', 'name', 'type', 'trailer_url_type', 'IMDb_rating', 'poster_url', 'thumbnail_url', 'poster_tv_url', 'trailer_url', 'access', 'plan_id')
                        ->limit(6)
                        ->get()
                        ->map(function($item) use ($userId, $userPlanLevel, $isDeviceSupported, $device_type, $purchasedIds) {
                            $itemArray = [
                                'id' => $item->id,
                                'access' => $item->access,
                                'plan_id' => $item->plan_id,
                                'type' => 'video',
                            ];
                            $itemArray = setContentAccess($itemArray, $userId, $userPlanLevel, $purchasedIds);
                            $item->has_content_access = $itemArray['has_content_access'];
                            $item->required_plan_level = $itemArray['required_plan_level'];
                            $item->isDeviceSupported = $isDeviceSupported;
                            $item->type = 'video';
                            $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url,'image','video') : setBaseUrlWithFileName($item->poster_url,'image','video');
                            return $item;
                        });

                    $movie['more_items'] = $more_items;
                    $movie['type'] = 'video';

                    break;

                case 'actor':
                case 'director':
                    $content = CastCrew::select('id', 'type')->where('id', $id)->first();

                    if (!$content) {
                        return [
                            'status' => false,
                            'message' => 'Cast/Crew not found.'
                        ];
                    }

                    // Check type
                    if ($content->type !== $request->type) {
                        return [
                            'status' => false,
                            'message' => 'Content type mismatch. Requested type: '.$request->type.', actual type: '.$content->type
                        ];
                    }

                    $castCrew = CastCrew::with('entertainmentTalentMappings')
                        ->where('id', $id)
                        ->where('type', $request->type)
                        ->first();

                    if (!$castCrew) {
                        return [
                            'status' => false,
                            'message' => ucfirst($request->type) . ' not found.'
                        ];
                    }

                    // Get movie and TV show counts
                    $movieCount = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($id) {
                        $query->where('talent_id', $id);
                    })->where('type', 'movie')->where('status', 1)->count();

                    $tvshowCount = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($id) {
                        $query->where('talent_id', $id);
                    })->where('type', 'tvshow')->where('status', 1)->count();

                    // Get average rating
                    $averageRating = \Modules\Entertainment\Models\Review::whereHas('entertainment.entertainmentTalentMappings', function ($query) use ($id) {
                        $query->where('talent_id', $id);
                    })->avg('rating');

                    // Get top genres
                    $topGenres = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($id) {
                        $query->where('talent_id', $id);
                    })->where('status', 1)->with(['entertainmentGenerMappings.genre:id,name'])->get()
                        ->pluck('entertainmentGenerMappings')->flatten()->pluck('genre.name')
                        ->filter()
                        ->countBy()->sortDesc()->take(3)->keys()->implode(', ');

                    // Get related content (movies and TV shows)
                    $relatedContent = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($id) {
                        $query->where('talent_id', $id);
                    })
                    ->where('status', 1)
                    ->where('deleted_at', null)
                    ->when(isset($request->is_restricted), function ($q) {
                        $q->where('is_restricted', request()->is_restricted);
                    })
                    ->when(!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0, function ($q) {
                        $q->where('is_restricted', 0);
                    })
                    ->limit(10)
                    ->get()
                    ->map(function($item) use ($userId, $userPlanLevel, $isDeviceSupported, $device_type, $purchasedIds) {
                        $access = in_array($item->type, ['movie', 'tvshow']) ? $item->movie_access : $item->access;
                        $itemArray = [
                            'id' => $item->id,
                            'access' => $access,
                            'plan_id' => $item->plan_id,
                        ];
                        $itemArray = setContentAccess($itemArray, $userId, $userPlanLevel, $purchasedIds);
                        $item->has_content_access = $itemArray['has_content_access'];
                        $item->required_plan_level = $itemArray['required_plan_level'];
                        $item->isDeviceSupported = $isDeviceSupported;
                        $item->poster_image = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url,'image',$item->type) : setBaseUrlWithFileName($item->poster_url,'image',$item->type);
                        return $item;
                    });

                    $movie = [
                        'id' => $castCrew->id,
                        'name' => $castCrew->name,
                        'type' => $castCrew->type,
                        'bio' => $castCrew->bio,
                        'dob' => $castCrew->dob,
                        'place_of_birth' => $castCrew->place_of_birth,
                        'profile_image' => $castCrew->file_url ? setBaseUrlWithFileName($castCrew->file_url, 'image', 'castcrew') : null,
                        'rating' => round($averageRating, 1),
                        'top_genres' => $topGenres,
                        'movie_count' => $movieCount,
                        'tvshow_count' => $tvshowCount,
                        'more_items' => $relatedContent,
                    ];

                    break;

                default:
                    // fallback
                    return ApiResponse::error('Invalid type', 400);
            }

                // Skip common processing for actor/director types as they don't have video/content properties
                if (!in_array($request->type, ['actor', 'director'])) {
                    $today = Carbon::now()->toDateString();
                    $placement = ['player','banner'];
                    $movie['posterImage'] = $device_type == 'tv' ? setBaseUrlWithFileName($movie->poster_tv_url ?? null) : setBaseUrlWithFileName($movie->poster_url ?? null);
                    $userId = $request->user_id ?? auth()->id() ;
                    $getDeviceTypeData = Subscription::checkPlanSupportDevice($userId,$device_type);
                    $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true); // Decode to associative array
                    $movie['isDeviceSupported'] = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;


                    // Optimized: Use already calculated userPlanLevel from line 2576 (avoid duplicate query)
                    // $userPlanLevel already set above

                    // Use helper function to set content access
                    // Convert to array format for setContentAccess
                    $movieArray = [
                        'id' => $movie->id,
                        'access' => $movie->movie_access ?? $movie->access ?? 'free',
                        'plan_id' => $movie->plan_id ?? 0,
                        'type' => $movie->type,
                        'price' => $movie->price ?? null,
                        'discount' => $movie->discount ?? null,
                        'access_duration' => $movie->access_duration ?? null,
                        'available_for' => $movie->available_for ?? null,
                        'purchase_type' => $movie->purchase_type ?? null,
                    ];
                    $movieArray = setContentAccess($movieArray, $user_id, $userPlanLevel, $purchasedIds);
                    $movie->has_content_access = $movieArray['has_content_access'];
                    $movie->required_plan_level = $movieArray['required_plan_level'];
                    
                    if(isset($movieArray['access']) && $movieArray['access']  == 'pay-per-view'){
                       $rental = [
                            'price' => (float)($movie->price ?? 0),
                            'discount' => (int)($movie->discount ?? 0),
                            'access_duration' => $movie->access_duration ?? null,
                            'availability_days' => $movie->available_for ?? null,
                            'access' => $movie->purchase_type ?? null,
                       ];
                       if ($rental['price'] > 0 && $rental['discount'] > 0) {
                            $rental['discounted_price'] = round(
                                $rental['price'] - ($rental['price'] * $rental['discount'] / 100),
                                2
                            );
                        } else {
                            $rental['discounted_price'] = $rental['price'];
                        }
                       $movie['rental'] = $rental;
                    }else{
                        $movie['rental'] = [];
                    };

                    // Initialize trailer_data array
                    $trailer_data = [];

                    if(isset($movie['trailer_url_type']) && isset($movie['trailer_url'])) {
                        $trailer_data[] =  [
                            'id'=> $movie['id'],
                            'title'    => 'default trailer',
                            'url_type' => trim($movie['trailer_url_type']),
                            'url' =>  $movie['trailer_url_type'] == 'Local' ? setBaseUrlWithFileName(trim($movie['trailer_url']),'video', $request->type) : trim($movie['trailer_url']),
                            'poster_image' =>isset($movie['poster_url']) ? setBaseUrlWithFileName($movie['poster_url'],'image', $request->type) : null,
                        ];
                    }

                    if (isset($movie->clips) && $movie->relationLoaded('clips') && $movie->clips->count() > 0) {
                        foreach ($movie->clips as $clip) {
                            $clipData = [
                                'id'           => $clip->id,
                                'title'        => $clip->title,
                                'url_type'     => $clip->type,
                                'url'          => $clip->type == 'Local' ? setBaseUrlWithFileName(trim($clip->url),'video',$request->type) : trim($clip->url),
                                'poster_image' =>  $device_type == 'tv' ? setBaseUrlWithFileName($clip->tv_poster_url ?? null ,'image',$request->type) : setBaseUrlWithFileName($clip->poster_url ?? null,'image',$request->type),
                            ];
                            $trailer_data[] = $clipData;
                        }
                    };
                    $movie['trailer_data'] = $trailer_data;
                    // Optimized: Batch load ads queries and use better date filtering
                    [$customAds, $vastAds] = [
                        CustomAdsSetting::where('status', 1)
                            ->whereIn('placement', $placement)
                            ->where('start_date', '<=', $today)
                            ->where('end_date', '>=', $today)
                            ->whereJsonContains('target_categories', (int) $id)
                            ->select('type','media','redirect_url','placement')
                            ->get()
                            ->map(function($ad) {
                                return [
                                    'type' => $ad->type,
                                    'placement' => $ad->placement,
                                    'url' => $ad->media ? setBaseUrlWithFileName(trim($ad->media) ,$ad->type,'ads') : null,
                                    'redirect_url' => $ad->redirect_url,
                                ];
                            }),
                        VastAdsSetting::where('status', 1)
                            ->where('start_date', '<=', $today)
                            ->where('end_date', '>=', $today)
                            ->whereJsonContains('target_selection', (int) $id)
                            ->where('target_type', $request->type =='episode' ? 'tvshow' : $request->type)
                            ->select('type', 'url')
                            ->get()
                    ];
                    // Prepare vast_ads array
                    $vastAdsData = [
                        'pre_role_ad_url' => $vastAds->where('type', 'pre-roll')->pluck('url')->toArray(),
                        'mid_role_ad_url' => $vastAds->where('type', 'mid-roll')->pluck('url')->toArray(),
                        'post_role_ad_url' => $vastAds->where('type', 'post-roll')->pluck('url')->toArray(),
                        'overlay_ad_url' => $vastAds->where('type', 'overlay')->pluck('url')->toArray(),
                    ];

                if($request->type != 'video'){
                    $movie['reviews'] = isset($movie->entertainmentReviews) ? $movie->entertainmentReviews : null;
                }

                $movie['subtitles'] = isset($movie->subtitles) ? $movie->subtitles : null;

                if(isset($videoQualities)) {
                    $videoQualities = $videoQualities->map(function($quality) use ($request) {
                        return [
                            'id' => $quality->id,
                            'quality' => trim($quality->quality),
                            'url_type' =>trim($quality->url_type),
                            'url' => $quality->url_type == 'Local' ? setBaseUrlWithFileName(trim($quality->url), 'video', $request->type) : trim($quality->url),
                        ];
                    });

                    $movie['video_qualities'] = array_merge([$videoDefaultQuality],$videoQualities->toArray() );
                }

                $defaultDownload = [];

                if (!empty($movie->download_type) || !empty($movie->download_url)) {
                    $defaultDownload[] = [
                        'id'        => $movie->id,
                        'url_type'  => $movie->download_type ?? null,
                        'url'       => ($movie->download_type === 'Local')
                                        ? setBaseUrlWithFileName($movie->download_url ?? null, 'video', $request->type)
                                        : ($movie->download_url ?? null),
                        'quality'   => 'default_quality',
                    ];
                }

                $mappingDownloads = [];

                if (!empty($downloadMappings)) {
                    foreach ($downloadMappings as $mapping) {
                        $mappingDownloads[] = [
                            'id'        => $mapping->id,
                            'url_type'  => $mapping->type,
                            'url'       => ($mapping->type === 'Local')
                                            ? setBaseUrlWithFileName($mapping->url, 'video', $request->type)
                                            : $mapping->url,
                            'quality'   => $mapping->quality,
                        ];
                    }
                }

                $mergedDownloads = array_merge($defaultDownload, $mappingDownloads);

                $movie['download_data'] = [
                    'download_enable' => $movie->download_status ?? 0,
                    'download_quality' => $mergedDownloads
                ];


                $movie['customAds'] = isset($customAds) ? $customAds : [];
                $movie['vast_ads'] = isset($vastAdsData) ? $vastAdsData : [];
                $movie['watched_time'] = isset($watched_time) ? $watched_time : null;
                } else {
                    // For actor/director, set minimal required properties
                    $movie['isDeviceSupported'] = $isDeviceSupported;
                    $movie['trailer_data'] = [];
                    $movie['video_qualities'] = [];
                    $movie['download_data'] = ['download_enable' => 0];
                    $movie['customAds'] = [];
                    $movie['vast_ads'] = [];
                    $movie['watched_time'] = null;
                    $movie['reviews'] = null;
                    $movie['subtitles'] = null;
                }

            if ($request->has('user_id')) {

                $user_id = $request->user_id;

                $movie['user_id'] = $user_id;

                // For actor/director, skip watchlist and likes as they don't apply
                if (!in_array($request->type, ['actor', 'director'])) {
                    // Optimized: Use already calculated withCount values instead of new queries
                    $movie['is_watch_list'] = isset($movie->is_watch_list) ? (int)$movie->is_watch_list : 0;
                    $movie['is_likes'] = isset($movie->is_likes) ? (int)$movie->is_likes : 0;

                    if (isset($movie['your_review_id']) && $movie['your_review_id']) {
                        if (isset($movie['reviews']) && $movie['reviews']) {
                            $movie['reviews'] = $movie['reviews']->where('user_id', '!=', $user_id);
                        }
                    }
                } else {
                    // For actor/director, set these to null or 0
                    $movie['is_watch_list'] = 0;
                    $movie['is_likes'] = 0;
                }


            }

            // Convert array to object if needed (for actor/director types)
            if (is_array($movie)) {
                $movie = (object) $movie;
            }
                if($request->type == 'actor' || $request->type == 'director'){
                    $responseData = new ContentDetailsCastCrewV3($movie);
                }else{
                    $responseData = new CommonContentDetails($movie);
                }
            // $responseData =  new CommonContentDetails($movie);
                     return $responseData;
         });

        return ApiResponse::success($responseData['data'], __($request->type.'.'. $request->type.'_details' ), 200);
    }

    public function contentListV3(Request $request){
        $device_type = getDeviceType($request);

        $user_id = isset($request->user_id) ? $request->user_id : 0;
        $profile_id = getCurrentProfile($user_id, $request);

        // Support both `content_type` (new) and `type` (backward compatibility)
        if (!$request->has('type') && !$request->has('content_type')) {
            return ApiResponse::error('Type parameter is required', 400);
        }

        $requestedType = $request->get('content_type', $request->type);

        // Normalize aliases coming from mobile/web
        $normalizedType = match ($requestedType) {
            'latest_movie'      => 'latest-movies',
            'popular_movie'     => 'popular-movies',
            'popular_tvshow'    => 'popular-tv-shows',
            'popular_video'     => 'most-watched-videos',
            'based_on_likes'    => 'based-on-likes',
            'based_on_imdb'     => 'top-rated-movies',
            'trending_in_country' => 'trending-movies',
            default             => $requestedType,
        };

        // Determine base content type used for access/image helpers
        $baseType = match ($normalizedType) {
            'latest-movies',
            'popular-movies',
            'top-rated-movies',
            'based-on-likes',
            'trending-movies'      => 'movie',

            'popular-tv-shows'    => 'tvshow',
            'most-watched-videos' => 'video',

            'movie',
            'tvshow',
            'episode',
            'video'               => $normalizedType,
            default               => null,
        };

        $isCustomSection = false;
        $customSectionIds = [];
        $customSection = null;
        if ($baseType === null) {
            $customSection = MobileSetting::select('slug', 'type', 'value')
                ->where('slug', $normalizedType)
                ->whereNotNull('type')
                ->where('type', '!=', '')
                ->first();

            if ($customSection && in_array($customSection->type, ['movie', 'tvshow', 'video', 'channel'], true)) {
                $isCustomSection = true;
                $baseType = $customSection->type;
                $normalizedType = $baseType;
                $customSectionIds = json_decode($customSection->value, true);
                $customSectionIds = is_array($customSectionIds)
                    ? array_values(array_filter(array_map('intval', $customSectionIds)))
                    : [];
            }
        }

        if ($baseType === null) {
            return ApiResponse::error('Invalid content type.', 400);
        }

        $perPage = $request->input('per_page', 10);
        $customSectionCacheToken = $isCustomSection ? md5((string) ($customSection->value ?? '')) : 'none';
        // Create cache key based on all request parameters
        $cacheKey = 'v3_content_list_'.md5(json_encode($request->all()) . '_' . $device_type . '_' . $user_id . '_' . $profile_id . '_' . $perPage . '_' . $customSectionCacheToken);

        // Use Redis caching with 5 minutes TTL
        $cachedResponse = cacheApiResponse($cacheKey, 300, function () use ($request, $normalizedType, $baseType, $device_type, $user_id, $profile_id, $perPage, $isCustomSection, $customSectionIds) {
            $list = collect();
            try {
            if ($isCustomSection && empty($customSectionIds)) {
                return [
                    'status' => true,
                    'data' => CommonContentList::collection(collect()),
                    'message' => __('movie.'.$baseType.'_list'),
                ];
            }
            switch ($normalizedType) {
                case 'movie':
                case 'tvshow':
                case 'latest-movies':
                case 'popular-movies':
                case 'popular-tv-shows':
                case 'top-rated-movies':
                case 'based-on-likes':
                case 'trending-movies':
                    // OPTIMIZATION: Base query setup
                    $list = Entertainment::select([
                        'entertainments.id', 'entertainments.name', 'entertainments.type', 
                        'entertainments.is_restricted', 'entertainments.plan_id', 
                        'entertainments.release_date', 'entertainments.IMDb_rating',
                        'entertainments.poster_url', 'entertainments.poster_tv_url', 
                        'entertainments.movie_access as access', 'entertainments.trailer_url_type'
                    ]);
                    
                    // OPTIMIZATION: For TV shows, use whereExists instead of JOIN to avoid groupBy overhead
                    if ($baseType === 'tvshow') {
                        $list->whereExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('seasons')
                                ->whereColumn('seasons.entertainment_id', 'entertainments.id')
                                ->where('seasons.status', 1)
                                ->whereNull('seasons.deleted_at')
                                ->whereExists(function ($episodeQuery) {
                                    $episodeQuery->select(DB::raw(1))
                                        ->from('episodes')
                                        ->whereColumn('episodes.season_id', 'seasons.id')
                                        ->where('episodes.status', 1)
                                        ->whereNull('episodes.deleted_at')
                                        ->limit(1); // Stop after finding first match
                                })
                                ->limit(1); // Stop after finding first match
                        });
                    }
                    
                    // OPTIMIZATION: Eager load only essential relationships to prevent N+1 queries
                    $eagerLoads = [
                        'plan:id,level', // Load plan to prevent N+1
                    ];
                    
                    // OPTIMIZATION: Only load entertainmentLike if user_id exists
                    if ($user_id && $profile_id) {
                        $eagerLoads['entertainmentLike'] = function($q) use ($user_id, $profile_id) {
                            $q->where('user_id', $user_id)
                              ->where('profile_id', $profile_id)
                              ->where('is_like', 1)
                              ->limit(1); // Only need to know if exists
                        };
                    }
                    
                    // OPTIMIZATION: Only load seasons for TV shows - batch load episode counts separately
                    if ($baseType === 'tvshow') {
                        $eagerLoads['season'] = function($q) {
                            $q->where('status', 1)
                              ->whereNull('deleted_at')
                              ->select('id', 'name', 'entertainment_id', 'status', 'deleted_at');
                              // Note: withCount removed - will batch load episode counts after pagination
                        };
                    }
                    
                    $list->with($eagerLoads)
                    ->where('entertainments.type', $baseType)
                    ->when($request->has('is_restricted'), function($q) use ($request) {
                        $q->where('entertainments.is_restricted', $request->is_restricted);
                    })
                    ->where('entertainments.status', 1);

                    if ($isCustomSection) {
                        $list->whereIn('entertainments.id', $customSectionIds)
                             ->orderByRaw('FIELD(entertainments.id, ' . implode(',', $customSectionIds) . ')');
                    }

                    if ($request->has('is_released') && !empty($request->is_released)) {
                        $list->where('entertainments.release_date', '<=', Carbon::now());
                    }

                    if ($request->has('search') && !empty($request->search)) {
                        $list->where('entertainments.name', 'like', "%{$request->search}%");
                    }

                    if ($request->has('language') && !empty($request->language) && $request->language != 'null') {
                        $list->where('entertainments.language', $request->language);
                    }

                    // OPTIMIZATION: Use JOIN instead of whereIn subquery for better performance on large datasets
                    if ($request->has('genre_id') && !empty($request->genre_id) && $request->genre_id != 'null') {
                        if ($baseType === 'tvshow') {
                            // For TV shows with JOIN, use whereExists to avoid duplicate joins
                            $list->whereExists(function($query) use ($request) {
                                $query->select(DB::raw(1))
                                    ->from('entertainment_gener_mapping')
                                    ->whereColumn('entertainment_gener_mapping.entertainment_id', 'entertainments.id')
                                    ->where('entertainment_gener_mapping.genre_id', $request->genre_id);
                            });
                        } else {
                            $list->whereIn('entertainments.id', function($query) use ($request) {
                                $query->select('entertainment_id')
                                    ->from('entertainment_gener_mapping')
                                    ->where('genre_id', $request->genre_id);
                            });
                        }
                    }

                    // OPTIMIZATION: Use whereExists for better performance (simpler than JOIN for filters)
                    if ($request->has('actor_id') && !empty($request->actor_id) && $request->actor_id != 'null') {
                        $list->whereExists(function($query) use ($request) {
                            $query->select(DB::raw(1))
                                ->from('entertainment_talent_mapping as etm')
                                ->join('cast_crew as cc', 'cc.id', '=', 'etm.talent_id')
                                ->whereColumn('etm.entertainment_id', 'entertainments.id')
                                ->where('etm.talent_id', $request->actor_id)
                                ->where('cc.type', 'actor');
                        });
                    }

                    if ($request->has('director_id') && !empty($request->director_id) && $request->director_id != 'null') {
                        $list->whereExists(function($query) use ($request) {
                            $query->select(DB::raw(1))
                                ->from('entertainment_talent_mapping as etm')
                                ->join('cast_crew as cc', 'cc.id', '=', 'etm.talent_id')
                                ->whereColumn('etm.entertainment_id', 'entertainments.id')
                                ->where('etm.talent_id', $request->director_id)
                                ->where('cc.type', 'director');
                        });
                    }

                    // Apply additional filters based on normalizedType (web-style content types)
                    switch ($normalizedType) {
                        case 'latest-movies':
                            // Movies from last 12 months
                            $oneYearAgo = Carbon::now()->subMonths(12);
                            $list->where('entertainments.type', 'movie')
                                 ->whereDate('entertainments.release_date', '>=', $oneYearAgo);
                            break;

                        case 'popular-movies':
                            // Most viewed movies from entertainment_view table
                            $mostViewedMovieIds = EntertainmentView::select('entertainment_id', DB::raw('COUNT(*) as view_count'))
                                ->groupBy('entertainment_id')
                                ->orderByRaw('COUNT(*) DESC')
                                ->pluck('entertainment_id');

                            $list->where('entertainments.type', 'movie')
                                 ->whereIn('entertainments.id', $mostViewedMovieIds)
                                 ->orderByRaw('FIELD(entertainments.id, ' . ($mostViewedMovieIds->isNotEmpty() ? $mostViewedMovieIds->implode(',') : '0') . ')');
                            break;

                        case 'popular-tv-shows':
                            // Most viewed TV shows from entertainment_view table
                            $mostViewedTvShowIds = EntertainmentView::select('entertainment_id', DB::raw('COUNT(*) as view_count'))
                                ->groupBy('entertainment_id')
                                ->orderByRaw('COUNT(*) DESC')
                                ->pluck('entertainment_id');

                            $list->where('entertainments.type', 'tvshow')
                                 ->whereIn('entertainments.id', $mostViewedTvShowIds)
                                 ->orderByRaw('FIELD(entertainments.id, ' . ($mostViewedTvShowIds->isNotEmpty() ? $mostViewedTvShowIds->implode(',') : '0') . ')');
                            break;

                        case 'top-rated-movies':
                            // Top-rated movies with IMDb rating >= 8.0
                            $list->where('entertainments.type', 'movie')
                                 ->whereRaw('CAST(entertainments.IMDb_rating AS DECIMAL(3,1)) >= ?', [8.0])
                                 ->orderByRaw('CAST(entertainments.IMDb_rating AS DECIMAL(3,1)) DESC')
                                 ->orderBy('entertainments.id', 'desc');
                            break;

                        case 'based-on-likes':
                            // Movies based on likes count
                            $mostLikedMovieIds = Like::select('entertainment_id', DB::raw('COUNT(*) as like_count'))
                                ->where('type', 'movie')
                                ->where('is_like', 1)
                                ->groupBy('entertainment_id')
                                ->orderByRaw('COUNT(*) DESC')
                                ->pluck('entertainment_id');

                            $list->where('entertainments.type', 'movie')
                                 ->whereIn('entertainments.id', $mostLikedMovieIds)
                                 ->orderByRaw('FIELD(entertainments.id, ' . ($mostLikedMovieIds->isNotEmpty() ? $mostLikedMovieIds->implode(',') : '0') . ')');
                            break;

                        case 'trending-movies':
                            // Trending movies by country
                            if ($user_id) {
                                $user = User::find($user_id);
                                if ($user) {
                                    $trendingMovieIds = $this->recommendationService->getTrendingMoviesByCountry($user);
                                    if (!empty($trendingMovieIds)) {
                                        $list->where('entertainments.type', 'movie')
                                             ->whereIn('entertainments.id', $trendingMovieIds)
                                             ->orderByRaw('FIELD(entertainments.id, ' . implode(',', $trendingMovieIds) . ')');
                                    } else {
                                        // If no country found, return empty result
                                        $list->where('entertainments.id', 0);
                                    }
                                } else {
                                    // If user not found, return empty result
                                    $list->where('entertainments.id', 0);
                                }
                            } else {
                                // If user not logged in, return empty result
                                $list->where('entertainments.id', 0);
                            }
                            break;
                    }

                    $list = $list->whereNull('entertainments.deleted_at');

                    // Default ordering if not already explicitly set
                    if (!in_array($normalizedType, ['popular-movies', 'popular-tv-shows', 'based-on-likes', 'trending-movies']) && !$isCustomSection) {
                        $list->orderBy('entertainments.id', 'desc');
                    }

                    $list = $list->simplePaginate($perPage);

                    // OPTIMIZATION: Batch load episode counts for all seasons to avoid N+1 withCount queries
                    if ($baseType === 'tvshow' && $list->isNotEmpty()) {
                        $entertainmentIds = $list->pluck('id')->toArray();
                        $seasonIds = \Modules\Season\Models\Season::whereIn('entertainment_id', $entertainmentIds)
                            ->where('status', 1)
                            ->whereNull('deleted_at')
                            ->pluck('id')
                            ->toArray();
                        
                        if (!empty($seasonIds)) {
                            $episodeCounts = \Modules\Episode\Models\Episode::whereIn('season_id', $seasonIds)
                                ->where('status', 1)
                                ->whereNull('deleted_at')
                                ->selectRaw('season_id, COUNT(*) as episodes_count')
                                ->groupBy('season_id')
                                ->pluck('episodes_count', 'season_id')
                                ->toArray();
                            
                            // Attach episode counts to seasons
                            $list->getCollection()->each(function($item) use ($episodeCounts) {
                                if ($item->relationLoaded('season')) {
                                    $item->season->each(function($season) use ($episodeCounts) {
                                        $season->episodes_count = $episodeCounts[$season->id] ?? 0;
                                    });
                                }
                            });
                        }
                    }
                    break;

                case 'episode':
                    $list = Episode::select([
                        'id', 'name', 'is_restricted', 'release_date', 'plan_id',
                        'poster_url', 'poster_tv_url', 'access', 'trailer_url',
                        'trailer_url_type', 'entertainment_id', 'season_id'
                    ])
                    ->with([
                        'entertainmentLike' => function($q) use ($user_id, $profile_id) {
                            $q->where('user_id', $user_id)
                              ->where('profile_id', $profile_id)
                              ->where('is_like', 1);
                        },
                        'seasondata' => function($q) {
                            $q->select('id', 'name', 'entertainment_id')
                              ->withCount('episodes');
                        },
                        'entertainmentdata' => function($q) {
                            $q->select('id', 'name', 'language')
                              ->with('entertainmentTalentMappings.talentprofile:id,name,type,file_url');
                        }
                    ])
                    ->when($request->has('is_restricted'), function($q) use ($request) {
                        $q->where('is_restricted', $request->is_restricted);
                    })
                    ->where('status', 1);

                    if ($request->has('is_released') && !empty($request->is_released)) {
                        $list->where('release_date', '<=', Carbon::now());
                    }

                    if ($request->has('search') && !empty($request->search)) {
                        $list->where('name', 'like', "%{$request->search}%");
                    }

                    // Optimized: Use whereIn with subquery instead of whereRelation
                    if ($request->has('language') && !empty($request->language) && $request->language != 'null') {
                        $list->whereIn('entertainment_id', function($query) use ($request) {
                            $query->select('id')
                                ->from('entertainments')
                                ->where('language', $request->language);
                        });
                    }

                    if ($request->has('genre_id') && !empty($request->genre_id) && $request->genre_id != 'null') {
                        $list->whereIn('entertainment_id', function($query) use ($request) {
                            $query->select('entertainment_id')
                                ->from('entertainment_gener_mapping')
                                ->where('genre_id', $request->genre_id);
                        });
                    }

                    if ($request->has('actor_id') && !empty($request->actor_id) && $request->actor_id != 'null') {
                        $list->whereIn('entertainment_id', function($query) use ($request) {
                            $query->select('entertainment_id')
                                ->from('entertainment_talent_mapping')
                                ->where('talent_id', $request->actor_id);
                        });
                    }

                    if ($request->has('director_id') && !empty($request->director_id) && $request->director_id != 'null') {
                        $list->whereIn('entertainment_id', function($query) use ($request) {
                            $query->select('entertainment_id')
                                ->from('entertainment_talent_mapping')
                                ->where('talent_id', $request->director_id);
                        });
                    }
                    $list = $list->whereNull('episodes.deleted_at')
                        ->orderBy('episodes.id', 'desc')
                        ->paginate($perPage);

                    break;

                case 'video':
                    $list = Video::select([
                        'id', 'name', 'type', 'is_restricted', 'plan_id', 'release_date', 'IMDb_rating',
                        'poster_url', 'poster_tv_url', 'access', 'trailer_url_type','trailer_url'
                    ])
                    ->with([
                        'entertainmentLike' => function($q) use ($user_id, $profile_id) {
                            $q->where('user_id', $user_id)
                              ->where('profile_id', $profile_id)
                              ->where('is_like', 1);
                        }
                    ])
                    ->when($request->has('is_restricted'), function($q) use ($request) {
                        $q->where('is_restricted', $request->is_restricted);
                    })
                    ->where('status', 1);

                    if ($request->has('is_released') && !empty($request->is_released)) {
                        $list->where('release_date', '<=', Carbon::now());
                    }

                    if ($request->has('search') && !empty($request->search)) {
                        $list->where('name', 'like', "%{$request->search}%");
                    }

                    if ($isCustomSection) {
                        $list->whereIn('id', $customSectionIds)
                             ->orderByRaw('FIELD(id, ' . implode(',', $customSectionIds) . ')');
                    }

                    $list = $list->whereNull('videos.deleted_at')
                        ->orderBy('videos.id', 'desc')
                        ->simplePaginate($perPage);

                    break;

                case 'channel':
                    $list = LiveTvChannel::select([
                        'id', 'name', 'plan_id', 'poster_url', 'poster_tv_url', 'access'
                    ])
                    ->where('status', 1)
                    ->whereNull('deleted_at');

                    if ($isCustomSection) {
                        $list->whereIn('id', $customSectionIds)
                             ->orderByRaw('FIELD(id, ' . implode(',', $customSectionIds) . ')');
                    }

                    $list = $list->simplePaginate($perPage);
                    break;

                case 'most-watched-videos':
                    // Most watched videos based on user watch history (entertainment_type = video)
                    $mostWatchedVideoIds = \App\Models\UserWatchHistory::select('entertainment_id', DB::raw('COUNT(*) as watch_count'))
                        ->where('entertainment_type', 'video')
                        ->groupBy('entertainment_id')
                        ->orderByRaw('COUNT(*) DESC')
                        ->pluck('entertainment_id');

                    $list = Video::select([
                            'id', 'name', 'type', 'is_restricted', 'plan_id', 'release_date', 'IMDb_rating',
                            'poster_url', 'poster_tv_url', 'access', 'trailer_url_type','trailer_url'
                        ])
                        ->with([
                            'entertainmentLike' => function($q) use ($user_id, $profile_id) {
                                $q->where('user_id', $user_id)
                                  ->where('profile_id', $profile_id)
                                  ->where('is_like', 1);
                            }
                        ])
                        ->when($request->has('is_restricted'), function($q) use ($request) {
                            $q->where('is_restricted', $request->is_restricted);
                        })
                        ->where('status', 1)
                        ->whereIn('id', $mostWatchedVideoIds)
                        ->whereNull('videos.deleted_at');

                    if ($mostWatchedVideoIds->isNotEmpty()) {
                        $list->orderByRaw('FIELD(id, ' . $mostWatchedVideoIds->implode(',') . ')');
                    } else {
                        $list->orderBy('videos.id', 'desc');
                    }

                    $list = $list->simplePaginate($perPage);

                    break;

                default:
                    return ApiResponse::error('Invalid content type. Supported types: movie, tvshow, episode, video, channel, latest-movies, popular-movies, popular-tv-shows, most-watched-videos, top-rated-movies, based-on-likes', 400);
            }

            $userId = $request->user_id ?? auth()->id();
            if($userId){
            // OPTIMIZATION: Cache device support check (10 minutes)
            $deviceTypeResponse = cache()->remember(
                "device_support_{$userId}_{$device_type}",
                600,
                function() use ($userId, $device_type) {
                    $getDeviceTypeData = Subscription::checkPlanSupportDevice($userId, $device_type);
                    return json_decode($getDeviceTypeData->getContent(), true);
                }
            );
            
            // OPTIMIZATION: Fetch user plan level once
            $userLevel = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();
            $userPlanLevel = $userLevel->plan_id ?? 0;
            
            // Optimized: Fetch all purchased IDs grouped by type in one query to avoid N+1 in setContentAccess
            $purchasedIds = [];
            if ($user_id) {
                $purchasedItems = PayPerView::where('user_id', $user_id)
                    ->where(function($q) {
                        $q->whereNull('view_expiry_date')
                          ->orWhere('view_expiry_date', '>', now());
                    })
                    ->where(function($q) {
                        $q->whereNull('first_play_date')
                          ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL available_for DAY) > ?', [now()]);
                    })
                    ->select('movie_id', 'type')
                    ->get();
                
                // Group by type for efficient lookup
                foreach ($purchasedItems as $item) {
                    if (!isset($purchasedIds[$item->type])) {
                        $purchasedIds[$item->type] = [];
                    }
                    $purchasedIds[$item->type][] = $item->movie_id;
                }
            }

            $listData = $list->map(function($item) use ($device_type, $deviceTypeResponse, $user_id, $profile_id, $normalizedType, $baseType, $userPlanLevel, $purchasedIds) {
                // OPTIMIZATION: Use already loaded relationship instead of querying
                $item->is_likes = ($user_id && $item->relationLoaded('entertainmentLike') && $item->entertainmentLike->isNotEmpty()) ? 1 : 0;
                $item->e_id = $item->id;
                if ($baseType === 'video') {
                    $item->type = 'video';
                } elseif ($baseType === 'episode') {
                    $item->type = 'episode';
                }

                // Use helper function to set content access
                $item = setContentAccess($item, $user_id, $userPlanLevel, $purchasedIds);
                $imageType = $baseType === 'channel' ? 'livetv' : $baseType;
                $item->posterImage = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$imageType) : setBaseUrlWithFileName($item->poster_url,'image',$imageType);
                $item->poster_tv_image = setBaseUrlWithFileName($item->poster_tv_url ,'image',$imageType);
                $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;

                // Optimized: Use withCount result instead of episodes()->count()
                if ($baseType === 'tvshow' && isset($item->season)) {
                    $item->season_data = $item->season->map(function ($season) {
                        return [
                            'id'            => $season->id,
                            'name'          => $season->name,
                            'season_id'     => $season->id,
                            'total_episode' => isset($season->episodes_count) ? $season->episodes_count : 0,
                        ];
                    })->values();
                } elseif ($baseType === 'episode' && isset($item->seasondata)) {
                    $item->season_data = [
                        'id'            => $item->seasondata->id,
                        'name'          => $item->seasondata->name,
                        'season_id'     => $item->seasondata->id,
                        'total_episode' => isset($item->seasondata->episodes_count) ? $item->seasondata->episodes_count : 0,
                    ];
                } else {
                    $item->season_data = null;
                }


                return $item;
            });
        }else{
            // Optimized: For non-logged in users, set is_likes to 0 without querying
            $listData = $list->map(function($item) use ($device_type, $normalizedType, $baseType) {
                // Set is_likes to 0 for non-logged in users (no relationship loaded)
                $item->is_likes = 0;
                $item->e_id = $item->id;
                if ($baseType === 'video') {
                    $item->type = 'video';
                } elseif ($baseType === 'episode') {
                    $item->type = 'episode';
                }

                $imageType = $baseType === 'channel' ? 'livetv' : $baseType;
                $item->posterImage = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image',$imageType) : setBaseUrlWithFileName($item->poster_url,'image',$imageType);

                $item->isDeviceSupported = 0;
                $item->has_content_access = 0;
                $item->required_plan_level = $item->plan_id ?? 0;
                $item = setContentAccess($item, null, null);
                $imageType = $baseType === 'channel' ? 'livetv' : $baseType;
                $item->posterImage = $device_type == 'tv' ? setBaseUrlWithFileName($item->poster_tv_url ,'image', $imageType) : setBaseUrlWithFileName($item->poster_url,'image',$imageType);
                $item->isDeviceSupported =  0;

                $item->poster_tv_image =  setBaseUrlWithFileName($item->poster_tv_url ,'image', $imageType);
                // Optimized: Use withCount result instead of episodes()->count()
                if ($baseType === 'tvshow' && isset($item->season)) {
                    $item->season_data = $item->season->map(function ($season) {
                        return [
                            'id'            => $season->id,
                            'name'          => $season->name,
                            'season_id'     => $season->id,
                            'total_episode' => isset($season->episodes_count) ? $season->episodes_count : 0,
                        ];
                    })->values();
                } elseif ($baseType === 'episode' && isset($item->seasondata)) {
                    $item->season_data = [
                        'id'            => $item->seasondata->id,
                        'name'          => $item->seasondata->name,
                        'season_id'     => $item->seasondata->id,
                        'total_episode' => isset($item->seasondata->episodes_count) ? $item->seasondata->episodes_count : 0,
                    ];
                } else {
                    $item->season_data = null;
                }
                return $item;
            });
        }
                $responseData = CommonContentList::collection($listData);

                return [
                    'status' => true,
                    'data' => $responseData,
                    'message' => __('movie.'.$baseType.'_list'),
                ];

            } catch (\Exception $e) {
                return [
                    'status' => false,
                    'message' => 'An error occurred while fetching content: ' . $e->getMessage()
                ];
            }
        });

        // Return cached response
        return ApiResponse::custom($cachedResponse['data'], $cachedResponse['data']['status'] ? 200 : 500);
    }


}
