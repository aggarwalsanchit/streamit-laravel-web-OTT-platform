<?php

namespace Modules\Entertainment\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Artisan;
use Illuminate\Http\Request;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Transformers\WatchlistResource;
use Modules\Entertainment\Transformers\WatchlistResourceV3;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Transformers\ContinueWatchResource;
use Modules\Entertainment\Models\Entertainment;
use Modules\Video\Models\Video;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Entertainment\Transformers\ContinueWatchResourceV3;
use Modules\Subscriptions\Models\Subscription;
use Modules\Frontend\Models\PayPerView;

class WatchlistController extends Controller
{
    public function watchList(Request $request)
    {
        $user_id = auth()->user()->id;
        $perPage = $request->input('per_page', 10);
        $type = $request->input('type', 'all');

        $profile_id = $request->input('profile_id');
        if (!$profile_id || $profile_id == 'null' || $profile_id == '') {
            $profile_id = getCurrentProfile($user_id, $request);
        }
        
        
        $query = Watchlist::where('user_id', $user_id)->whereNull('deleted_at');
        
        if ($profile_id) {
            $query->where('profile_id', $profile_id);
        } else {
            $query->where(function($q) use ($user_id) {
                $q->whereNull('profile_id')
                  ->orWhereIn('profile_id', function($subQuery) use ($user_id) {
                      $subQuery->select('id')
                               ->from('user_multi_profiles')
                               ->where('user_id', $user_id);
                  });
            });
        }

        if ($type === 'movie') {
            $query->where('type', 'movie')
                  ->whereHas('entertainment', function ($subQuery) {
                      $subQuery->where('status', 1)->where('deleted_at', null);
                  });
        } elseif ($type === 'tvshow') {
            $query->where('type', 'tvshow')
                  ->whereHas('entertainment', function ($subQuery) {
                      $subQuery->where('status', 1)->where('deleted_at', null);
                  });
        } elseif ($type === 'video') {
            $query->where('type', 'video')
                  ->whereHas('video', function ($subQuery) {
                      $subQuery->where('status', 1)->where('deleted_at', null);
                  });
        } else {
            $query->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereIn('type', ['movie', 'tvshow']) // Entertainment types
                      ->whereHas('entertainment', function ($subQuery) {
                          $subQuery->where('status', 1)->where('deleted_at', null);
                      });
                })
                ->orWhere(function ($q) {
                    $q->where('type', 'video') // Video type
                      ->whereHas('video', function ($subQuery) {
                          $subQuery->where('status', 1)->where('deleted_at', null);
                      });
                });
            });
        }


        $watchList = $query->with('entertainment', 'video') //add karvanu
        ->orderBy('updated_at', 'desc')
        ->paginate($perPage);

        $responseData = WatchlistResource::collection($watchList);

        if ($request->has('is_ajax') && $request->input('is_ajax') == 1) {
            $html = '';

            foreach($responseData->toArray($request) as $watchData) {
                $userId = auth()->id();
                if ($userId) {
                    $isInWatchList = Watchlist::where('entertainment_id', $watchData['entertainment_id'])
                                               ->where('user_id', $userId);
                    
                    if ($profile_id) {
                        $isInWatchList->where('profile_id', $profile_id);
                    } else {
                        $isInWatchList->whereNull('profile_id');
                    }
                    
                    $watchData['is_watch_list'] = $isInWatchList->exists();

                    if ($watchData['entertainment_type'] === 'video') {
                        $videoData = Video::find($watchData['entertainment_id']);
                        if ($videoData) {
                            $watchData['name'] = $videoData->name;
                            $watchData['description'] = $videoData->description;
                            $watchData['duration'] = $videoData->duration;
                            $watchData['poster_image'] = setBaseUrlWithFileName($videoData->poster_url,'image','video');
                            $watchData['access'] = $videoData->access;
                            $watchData['id'] = $watchData['entertainment_id'];
                            $watchData['slug'] = $videoData->slug;
                        }
                        $html .= view('frontend::components.card.card_video', ['values' => [$watchData]])->render();
                    } else {
                        $watchData['id'] = $watchData['entertainment_id'];
                        $html .= view('frontend::components.card.card_movie', ['values' => [$watchData]])->render();
                    }
                }
            }

            return ApiResponse::success(
                null,
                __('movie.watch_list'),
                200,
                ['html' => $html, 'hasMore' => method_exists($watchList, 'hasMorePages') ? $watchList->hasMorePages() : false]
            );
        }

        return ApiResponse::success($responseData, __('movie.watch_list'), 200);
    }


    public function watchListV3(Request $request)
    {
        $user_id = auth()->user()->id ?? $request->user_id;
        $device_type = getDeviceType($request);

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $type = $request->input('type'); // optional: movie, tvshow, video, all

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user_id, $request);

        // Create unique cache key based on all relevant parameters including type and page
        $cacheKey = 'watchlist_v3_'. md5(json_encode([
            'user_id' => $user_id,
            'profile_id' => $profile_id,
            'per_page' => $perPage,
            'page' => $page,
            'type' => $type,
            'device_type' => $device_type,
            'is_ajax' => $request->input('is_ajax', 0)
        ]));

        $cacheVersionKey = 'watchlist_cache_version';
        $cacheVersion = \Illuminate\Support\Facades\Cache::get($cacheVersionKey, 0);
        $versionedCacheKey = $cacheKey . '_v' . $cacheVersion;

        // Use Redis caching with 5 minutes TTL
        $cachedResponse = cacheApiResponse($versionedCacheKey, 300, function () use ($request, $user_id, $profile_id, $perPage, $device_type, $type) {
            $query = Watchlist::with(['entertainment.season', 'video'])
                ->where('user_id', $user_id)
                ->where('profile_id', $profile_id)
                ->whereNull('deleted_at');

            // Handle bifurcation according to type
            if ($type && $type !== 'all') {
                $query->where('type', $type);
            }

            $watchList = $query->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereIn('type', ['movie', 'tvshow']) // Entertainment types
                      ->whereHas('entertainment', function ($subQuery) {
                          $subQuery->where('status', 1);
                      });
                })
                ->orWhere(function ($q) {
                    $q->where('type', 'video') // Video type
                      ->whereHas('video', function ($subQuery) {
                          $subQuery->where('status', 1);
                      });
                });
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

            $userId = $request->user_id ?? auth()->id();
            $getDeviceTypeData = Subscription::checkPlanSupportDevice($userId, $device_type);
            $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
            $userLevel = Subscription::select('plan_id')->where(['user_id' => $user_id, 'status' => 'active'])->latest()->first();
            $userPlanId = $userLevel->plan_id ?? 0;

            // Optimized: Fetch all purchased IDs grouped by type for setContentAccess
            $purchasedIds = PayPerView::where('user_id', $user_id)
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

            $watchList->getCollection()->transform(function($item) use ( $device_type, $deviceTypeResponse, $userPlanId, $profile_id, $user_id, $purchasedIds) {
                $entertainment = null;
                $video = null;

                if($item->type === 'movie' || $item->type === 'tvshow'){
                    $entertainment = $item->entertainment;
                    if ($entertainment) {
                        $item->access = $entertainment->movie_access;
                        $item->plan_id = $entertainment->plan_id ?? 0;
                        $item->imdb_rating = $entertainment->IMDb_rating ?? $entertainment->imdb_rating ?? null;
                    }
                } else {
                    $video = $item->video;
                    if ($video) {
                        $item->access = $video->access;
                        $item->plan_id = $video->plan_id ?? 0;
                        $item->imdb_rating = $video->IMDb_rating ?? $video->imdb_rating ?? null;
                    }
                }

                if ($item->type === 'video') {
                    // video type
                    if ($video) {
                        $posterUrl = $device_type == 'tv'
                            ? setBaseUrlWithFileName($video->poster_tv_url ,'image', $item->type)
                            : setBaseUrlWithFileName($video->poster_url, 'image', $item->type);
                        $poster_tv_url = setBaseUrlWithFileName($video->poster_tv_url ,'image', $item->type);
                    }
                } else {
                    // entertainment type
                    if ($entertainment) {
                        $posterUrl = $device_type == 'tv'
                            ? setBaseUrlWithFileName($entertainment->poster_tv_url, 'image', $item->type)
                            : setBaseUrlWithFileName($entertainment->poster_url, 'image', $item->type);
                        $poster_tv_url = setBaseUrlWithFileName($entertainment->poster_tv_url, 'image', $item->type);
                    }
                }

                $item->posterImage = $posterUrl ?? null;
                $item->poster_tv_image = $poster_tv_url ?? null;
            
                $item->isDeviceSupported = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0;

                // Set id for setContentAccess function
                $item->id = $item->entertainment_id;

                if ($item->type === 'tvshow' && $entertainment) {
                    $item->season_data = $entertainment->season->map(function ($season) {
                        return [
                            'id'            => $season->id,
                            'name'          => $season->name,
                            'season_id'     => $season->id,
                            'total_episode' => $season->episodes()->count(),
                        ];
                    })->values();
                } elseif ($item->type === 'episode' && isset($item->seasondata)) {
                    $item->season_data = [
                        'id'            => $item->seasondata->id,
                        'name'          => $item->seasondata->name,
                        'season_id'     => $item->seasondata->id,
                        'total_episode' => $item->seasondata->episodes()->count(),
                    ];
                } else {
                    $item->season_data = null;
                }
                
                $itemArray = $item->toArray();
                $itemArray = setContentAccess($itemArray, $user_id, $userPlanId, $purchasedIds);

                $item->has_content_access = $itemArray['has_content_access'] ?? 0;
                $item->required_plan_level = $itemArray['required_plan_level'] ?? 0;
               
                return $item;
            });

            $responseData = WatchlistResourceV3::collection($watchList);

            if ($request->has('is_ajax') && $request->input('is_ajax') == 1) {
                $html = '';

                foreach($responseData->toArray($request) as $watchData) {
                    $userId = auth()->id();
                    if ($userId) {
                        $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
                        $contentType = $watchData['entertainment_type'] ?? 'movie';
                        $isInWatchList = Watchlist::where('entertainment_id', $watchData['entertainment_id'])
                                                   ->where('user_id', $userId)
                                                   ->where('type', $contentType)
                                                   ->where('profile_id', $profile_id)
                                                   ->exists();
                        $watchData['is_watch_list'] = $isInWatchList;
                    }

                        if ($watchData['entertainment_type'] === 'video') {

                            $videoData = Video::find($watchData['entertainment_id']);
                            if ($videoData) {

                                $watchData['name'] = $videoData->name;
                                $watchData['description'] = $videoData->description;
                                $watchData['duration'] = $videoData->duration;
                                $watchData['poster_image'] = setBaseUrlWithFileName( $videoData->poster_url);
                                $watchData['access']=$videoData->access;
                                $watchData['id']=$watchData['entertainment_id'];

                            }
                            $html .= view('frontend::components.card.card_video', ['data' => $watchData])->render();

                        }
                        else{
                            $watchData['id']=$watchData['entertainment_id'];
                            $html .= view('frontend::components.card.card_entertainment', ['value' => $watchData])->render();

                        }
                    }

                $hasMore = $watchList->hasMorePages();

                return [
                    'status' => true,
                    'html' => $html,
                    'message' => __('movie.watch_list'),
                    'hasMore' => $hasMore,
                ];
            }

            return [
                'status' => true,
                'data' => $responseData,
                'message' => __('movie.watch_list'),
            ];
        });

        // Return cached response
        return ApiResponse::custom($cachedResponse['data'], 200);
    }


    public function saveWatchList(Request $request)
    {

        $user = auth()->user();
        
        $entertainmentId = $request->input('entertainment_id');

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user->id, $request);

        $watchlistData = $request->except('user_id');

        $watchlistData['profile_id'] = $profile_id;

        $type = null;
        if($request->has('type') && $request->type == 'video'){
            $type = 'video';
            $entertainment = Video::find($entertainmentId);
        }
        else{
            $entertainment = Entertainment::find($entertainmentId);
            $type = $request->input('entertainment_type');
            if (!$type && $entertainment) {
                $type = $entertainment->type ?? 'movie';
            }
        }
        Log::info($entertainment);

        if (!$entertainment) {
            return ApiResponse::error(__('movie.entertainment_not_found'), 404);
        }

        $watchlistData['user_id'] = $user->id;
        $watchlistData['type'] = $type;

        Watchlist::updateOrCreate(
            ['entertainment_id' => $entertainmentId, 'user_id' => $user->id, 'profile_id'=>$profile_id , 'type'=>$type],
            $watchlistData
        );
        
        // CONSISTENT CACHE CLEARING
        \Artisan::call('cache:clear');
        \Cache::flush();
        clearWatchlistCache();

        return ApiResponse::success(null, __('movie.watchlist_add'), 200);
    }


    public function deleteWatchList(Request $request)
    {
        try {
            $user = auth()->user();

            $profile_id = $request->has('profile_id') && $request->profile_id
                ? $request->profile_id
                : getCurrentProfile($user->id, $request);

            $id_param = $request->id;
            $ids = is_array($id_param) ? $id_param : explode(',', (string)$id_param);
            $ids = array_filter(array_map('trim', $ids));

            if (empty($ids)) {
                return ApiResponse::error(__('movie.id_required'), 400);
            }

            // Attempt to find by primary IDs first
            $watchlists = Watchlist::whereIn('id', $ids)
                ->where('user_id', $user->id)
                ->where('profile_id', $profile_id)
                ->get();

            // If not found by primary IDs, or if is_ajax=1 is explicitly passed (common for entertainment_id use)
            if ($watchlists->isEmpty() || $request->is_ajax == 1) {
                $query = Watchlist::whereIn('entertainment_id', $ids)
                    ->where('user_id', $user->id)
                    ->where('profile_id', $profile_id);
                
                // Only filter by type if explicitly provided
                if ($request->filled('type')) {
                    $query->where('type', $request->type);
                }
                
                $fallbackWatchlists = $query->get();
                
                // Merge if some were found by primary IDs and some by entertainment_ids
                if ($fallbackWatchlists->isNotEmpty()) {
                    $watchlists = $watchlists->merge($fallbackWatchlists);
                }
            }

            if ($watchlists->isEmpty()) {
                return ApiResponse::error(__('movie.watchlist_notfound'), 404);
            }

            // Perform permanent deletion
            Watchlist::whereIn('id', $watchlists->pluck('id'))->forceDelete();

            // CONSISTENT CACHE CLEARING
            \Artisan::call('cache:clear');
            \Cache::flush();
            clearWatchlistCache();

            return ApiResponse::success(null, "Removed from watchlist", 200);

        } catch (\Exception $e) {
            \Log::error('Watchlist Delete Error: ' . $e->getMessage());
            return ApiResponse::error('Error removing from watchlist', 500);
        }
    }

    /**
     * Remove a single item from watchlist by entertainment id.
     *
     * DELETE /api/watch-list/{entertainment_id}
     * Query/body:
     *  - type: movie|tvshow|video (optional)
     *  - profile_id: profile id (optional, defaults to current profile)
     */
    public function removeWatchList(Request $request, int $entertainment_id)
    {
        try {
            $user = auth()->user();
            $profile_id = $request->has('profile_id') && $request->profile_id
                ? $request->profile_id
                : getCurrentProfile($user->id, $request);

            $type = $request->input('type');

            $query = Watchlist::query()
                ->where('entertainment_id', $entertainment_id)
                ->where('user_id', $user->id)
                ->where('profile_id', $profile_id);

            if (!empty($type)) {
                $query->where('type', $type);
            }

            $watchlists = $query->get();

            if ($watchlists->isEmpty()) {
                return ApiResponse::error(__('movie.watchlist_notfound'), 404);
            }

            Watchlist::whereIn('id', $watchlists->pluck('id'))->forceDelete();

            return ApiResponse::success(null, __('movie.watchlist_delete'), 200);
        } catch (\Exception $e) {
            \Log::error('Watchlist Remove Error: ' . $e->getMessage());
            return ApiResponse::error('Error removing from watchlist', 500);
        }
    }

    public function continuewatchList(Request $request)
    {
        $user_id = auth()->user()->id;

        $perPage = $request->input('per_page', 10);

        $continuewatch_base = ContinueWatch::query()
                ->whereNotNull('watched_time')
                ->whereNotNull('total_watched_time')
                
                ->where(function($query) {
                    $query->whereHas('entertainment', function ($q) {
                        $q->where('status', 1)->whereNull('deleted_at');
                    })
                    ->orWhereHas('episode', function ($q) {
                        $q->whereNull('deleted_at');
                    })
                    ->orWhereHas('video', function ($q) {
                        $q->where('status', 1)->whereNull('deleted_at');
                    });
                })  
                ->with(['entertainment', 'episode', 'video','episode.seasondata']);
                // ->where(function ($query) {
                //     // For movie and tvshow, check entertainment relationship
                //     $query->where(function ($q) {
                //         $q->where('entertainment_type', 'movie')
                //         ->whereHas('entertainment', function ($subQuery) {
                //             $subQuery->where('status', 1)
                //                         ->whereNull('deleted_at');
                //         });
                //     })
                //     // For episode, check episode relationship
                //     ->orWhere(function ($q) {
                //         $q->where('entertainment_type', 'tvshow')
                //         ->whereNotNull('episode_id')
                //         ->whereHas('episode', function ($subQuery) {
                //             $subQuery->where('status', 1)
                //                         ->whereNull('deleted_at');
                //         });
                //     })
                //     // For video, check video relationship
                //     ->orWhere(function ($q) {
                //         $q->where('entertainment_type', 'video')
                //         ->whereHas('video', function ($subQuery) {
                //             $subQuery->where('status', 1)
                //                         ->whereNull('deleted_at');
                //         });
                //     });
                // })
                // ->with(['entertainment', 'episode.seasondata', 'video']);


        $continuewatchList = $continuewatch_base
            ->whereNotNull('watched_time')
            ->whereNotNull('total_watched_time')
            ->with(['entertainment', 'episode', 'video']);

        $profile_id = $request->has('profile_id') && $request->profile_id
            ? $request->profile_id
            : getCurrentProfile($user_id, $request);

        $continuewatch = $continuewatchList
            ->where('user_id', $user_id)
            ->where('profile_id', $profile_id)
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);

        $responseData = ContinueWatchResource::collection($continuewatch);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';
            foreach ($responseData->toArray($request) as $continuewatchData) {
                $html .= view('frontend::components.card.card_continue_watch', ['value' => $continuewatchData])->render();
            }

            $hasMore = $continuewatch->hasMorePages();

            return ApiResponse::success(
                null,
                __('movie.movie_list'),
                200,
                ['html' => $html, 'hasMore' => $hasMore]
            );
        }
        return ApiResponse::success($responseData, __('movie.watch_list'), 200);
    }

    public function saveContinueWatch(Request $request)
    {
        if($request->watched_time == null || $request->watched_time == '00:00:00'){
            return ;
        }
        $user = auth()->user();
        $watch_data = $request->all();
        $type = $request->entertainment_type == 'episode' ? 'tvshow' : $request->entertainment_type;
        \Log::info($watch_data);
        $watch_data['total_watched_time'] = $watch_data['total_watched_time'] ?? '00:00:01';
        $watch_data['watched_time'] = $watch_data['watched_time'] ?? '00:00:01';
        $watch_data['user_id'] = $user->id;
        $watch_data['entertainment_type'] = $type;

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user->id, $request);

        $watch_data['profile_id'] =  $profile_id;

        $result = ContinueWatch::updateOrCreate(['entertainment_id' => $request->entertainment_id, 'user_id' => $user->id, 'entertainment_type' => $type,'profile_id'=>$profile_id,'episode_id'=>$request->episode_id], $watch_data);

        // Instant notification trigger when setting is 0 days
        if ($result && intval(setting('continue_watch')) === 0) {
            // Only notify if not fully watched
            if ($result->watched_time < $result->total_watched_time) {
                // Throttle: one per day per item per user to avoid spam
                $cacheKey = "instant_continue_watch_{$user->id}_{$result->entertainment_id}_{$result->episode_id}_" . date('Y-m-d');
                if (!Cache::has($cacheKey)) {
                    Cache::put($cacheKey, true, now()->endOfDay());
                    
                    $content = null;
                    $contentType = 'movie';
                    if ($type == 'movie') {
                        $content = Entertainment::find($result->entertainment_id);
                        $contentType = 'movie';
                    } elseif ($type == 'tvshow' && $result->episode_id) {
                        $content = \Modules\Episode\Models\Episode::find($result->episode_id);
                        $contentType = 'episode';
                    } elseif ($type == 'video') {
                        $content = Video::find($result->entertainment_id);
                        $contentType = 'video';
                    }

                    if ($content) {
                        sendNotification([
                            'notification_type' => 'continue_watch',
                            'id' => $content->id,
                            'name' => $content->name,
                            'content_type' => $contentType,
                            'posterimage' => $content->poster_url,
                            'user_id' => $user->id,
                            'user_name' => $user->full_name,
                        ]);
                    }
                }
            }
        }

        if ($request->has('profile_id')) {
            $profile_id = $request->profile_id;
        } else {
            $profile_id = getCurrentProfile($user->id, $request);
        }

        clearDashboardCache();
        
        return ApiResponse::success(null, __('movie.save_msg'), 200);
    }
    public function deleteContinueWatch(Request $request)
    {
        $userId = auth()->id();
        $profileId = $request->has('profile_id') && $request->profile_id
            ? $request->profile_id
            : getCurrentProfile($userId, $request);

        $id_param = $request->id;
        if (!empty($id_param)) {
            // Support both single ID and array of IDs
            $ids = is_array($id_param) ? $id_param : explode(',', $id_param);
            
            // 1. Try deleting by ContinueWatch primary keys
            $watchlists = ContinueWatch::whereIn('id', $ids)
                ->where('user_id', $userId)
                ->where('profile_id', $profileId)
                ->get();

            if ($watchlists->isNotEmpty()) {
                ContinueWatch::whereIn('id', $watchlists->pluck('id'))->forceDelete();
            } else {
                // 2. Fallback: Try deleting by entertainment_id (common in web UI)
                $type = $request->input('entertainment_type');
                if ($type === 'episode') { $type = 'tvshow'; }

                $fallbackQuery = ContinueWatch::whereIn('entertainment_id', $ids)
                    ->where('user_id', $userId)
                    ->where('profile_id', $profileId);

                if ($request->filled('episode_id')) {
                    $fallbackQuery->where('episode_id', $request->episode_id);
                }
                if (!empty($type)) {
                    $fallbackQuery->where('entertainment_type', $type);
                }

                $fallbackRecords = $fallbackQuery->get();
                if ($fallbackRecords->isNotEmpty()) {
                    ContinueWatch::whereIn('id', $fallbackRecords->pluck('id'))->forceDelete();
                }
            }
        }

        // Always clear dashboard cache to ensure the removed item disappears immediately
        clearDashboardCache();

        return ApiResponse::success(null, "Continue watching item removed", 200);
    }

    public function continuewatchListV3(Request $request){
        $user_id = auth()->user()->id;
        $perPage = $request->input('per_page', 10);

        // Create cache key based on all request parameters
        // $cacheKey = 'v3_continuewatch_list_' . md5(json_encode($request->all()) . '_' . $user_id);


        // Use Redis caching with 5 minutes TTL
        // $cachedResponse = cacheApiResponse($cacheKey, 300, function () use ($request, $user_id, $perPage, $type) {
            $continuewatchList = ContinueWatch::query()
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

            $profile_id=$request->has('profile_id') && $request->profile_id
            ? $request->profile_id
            : getCurrentProfile($user_id, $request);

            $continuewatch = $continuewatchList
                ->where('user_id', $user_id)
                ->where('profile_id', $profile_id)
                ->orderBy('updated_at', 'desc')
                ->paginate($perPage);


            // Process each item to add tv_show_data for episodes
            $continuewatch->getCollection()->transform(function ($item) {
                // Prefer episode poster when continue watch entry is for an episode under a TV show
                if ($item->entertainment_type == 'tvshow' && $item->episode) {
                    $item->thumbnail_url = $item->episode->poster_url ?? null;
                } elseif ($item->entertainment_type == 'tvshow' && $item->entertainment) {
                    $item->thumbnail_url = $item->entertainment->thumbnail_url ?? null;
                } elseif ($item->entertainment_type == 'movie' && $item->entertainment) {
                    $item->thumbnail_url = $item->entertainment->thumbnail_url ?? null;
                } elseif ($item->entertainment_type == 'video' && $item->video) {
                    $item->thumbnail_url = $item->video->thumbnail_url ?? null;
                } else {
                    $item->thumbnail_url = null;
                }

                if ($item->entertainment_type == 'tvshow' && $item->episode && $item->episode->seasondata) {
                    $item->tv_show_data = [
                        'id' => $item->episode->seasondata->id,
                        'episode_name' => $item->episode->name,
                        'season_name' => $item->episode->seasondata->name,
                        'season_id' => $item->episode->seasondata->id,
                    ];
                } else {
                    $item->tv_show_data = null;
                }
                return $item;
            });

            $responseData = ContinueWatchResourceV3::collection($continuewatch);

            if ($request->has('is_ajax') && $request->is_ajax == 1) {
                $html = '';
                foreach ($responseData->toArray($request) as $continuewatchData) {
                    $html .= view('frontend::components.card.card_continue_watch', ['value' => $continuewatchData])->render();
                }

                $hasMore = $continuewatch->hasMorePages();

                return [
                    'status' => true,
                    'html' => $html,
                    'message' => __('movie.movie_list'),
                    'hasMore' => $hasMore,
                ];
            }

            return [
                'status' => true,
                'data' => $responseData,
                'message' => __('movie.watch_list'),
            ];
        // });

        // Return cached response
        // return response()->json($cachedResponse['data'], 200);
    }
}
