<?php

namespace Modules\User\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\UserSearchHistory;
use App\Models\UserMultiProfile;
use Modules\User\Transformers\UserSearchHistoryResource;
use Illuminate\Http\Request;
use Modules\Subscriptions\Models\Subscription;
use Modules\Entertainment\Models\Entertainment;
use Modules\Video\Models\Video;

class UserSearchHistoryController extends Controller
{
    public function searchHistoryList(Request $request)
    {
        $user_id = !empty($request->user_id)? $request->user_id :auth()->user()->id;

        $perPage = $request->input('per_page', 10);
        $search_data = UserSearchHistory::query();

        if ($request->has('search') && $request->search != '') {

            $profile_id = getCurrentProfile($user_id, $request);

            $search_data->where('user_id', $user_id)
                        ->where('profile_id', $profile_id)
                        ->where('search_query', 'like', "%{$request->search}%")
                        ->distinct('search_query');
        }

        if( $request->has('profile_id') && $request->profile_id !=''){

            $search_data = $search_data->where('profile_id', $request->profile_id);

          }

        $search_data = $search_data->where('user_id', operator: $user_id)->orderBy('id', 'desc')->paginate($perPage);

        $responseData = UserSearchHistoryResource::collection($search_data);

        return ApiResponse::success($responseData, __('movie.search_history_list'), 200);
    }

    public function searchHistoryListV3(Request $request)
    {
        $header = request()->headers->all();
        $user_id = !empty($request->user_id)? $request->user_id :auth()->user()->id;
        // $device_type = !empty($header['device-type'])? $header['device-type'][0] : []; //for tv
        $device_type = getDeviceType($request);

        $perPage = $request->input('per_page', 10);
        $searchQuery = $request->input('search', '');
        $page = $request->input('page', 1);

        $profile_id = $request->input('profile_id', '');
        $searchQuery = is_array($searchQuery) ? implode(',', $searchQuery) : $searchQuery;
        $cacheKey = "searchHistoryListV3:user:$user_id:profile:$profile_id:search:$searchQuery:device:$device_type:perPage:$perPage:page:$page";
        $ttl =300;

        $responseData = cacheApiResponse($cacheKey, $ttl, function () use ($user_id, $request, $perPage, $device_type) {
            $search_data = UserSearchHistory::query();

            if ($request->filled('search')) {
                $profile_id = getCurrentProfile($user_id, $request);
                $search_data->where('user_id', $user_id)
                            ->where('profile_id', $profile_id)
                            ->where('search_query', 'like', "%{$request->search}%")
                            ->distinct('search_query');
            }

            if ($request->filled('profile_id')) {
                $search_data->where('profile_id', $request->profile_id);
            }

            $search_data = $search_data->where('user_id', $user_id)
                                    ->orderBy('id', 'desc')
                                    ->paginate($perPage);

            $userLevel = Subscription::select('plan_id')
                            ->where(['user_id' => $user_id, 'status' => 'active'])
                            ->latest()
                            ->first();
            $userPlanId = optional($userLevel)->plan_id ?? 0;

            // Return only array of items with episode check
            return $search_data->getCollection()->map(function ($item) use ($device_type, $userPlanId, $user_id) {
                if($item->type === 'movie' || $item->type === 'tvshow'){
                    $entertainment = Entertainment::find($item->search_id);
                    $item->access = $entertainment?->movie_access ?? null;
                }else{
                    $video = Video::find($item->search_id);
                    $item->access = $video?->access ?? null;
                }
                $item = setContentAccess($item, $user_id, $userPlanId);
                if ($item->type === 'episode') {
                    $item->tv_show_data = [
                        'id' => optional($item->episode)->id,
                        'season_id' => optional($item->episode)->season_id,
                    ];
                } else {
                    $item->tv_show_data = null;
                }
                return new UserSearchHistoryResource($item, $device_type, $userPlanId);
            })->values();
        });

        return ApiResponse::success($responseData['data'], __('movie.search_history_list'), 200);
    }

    public function popularSearchListV3(Request $request)
    {
        $user = auth()->user();
        $user_id = $request->input('user_id', $user?->id);
        $profile_id = $request->input('profile_id');
        if (!$profile_id && $user_id) {
            $profile_id = getCurrentProfile($user_id, $request);
        }

        $isChildProfile = false;
        if ($profile_id) {
            $profile = UserMultiProfile::find($profile_id);
            $isChildProfile = (bool) ($profile?->is_child_profile);
        }

        $perPage = max((int) $request->input('per_page', 5), 1);

        // Mirror web search page logic: aggregate top searches globally, respect profile restrictions, limit by count
        $topSearches = UserSearchHistory::query()
            ->whereNotNull('search_id')
            ->selectRaw('search_id, type, MAX(search_query) as search_query, COUNT(*) as total')
            ->groupBy('search_id', 'type')
            ->orderByDesc('total')
            ->with([
                'entertainment' => function ($query) use ($isChildProfile) {
                    $query->whereNull('deleted_at')->where('status', 1);
                    if ($isChildProfile) {
                        $query->where('is_restricted', 0);
                    }
                },
                'episode' => function ($query) use ($isChildProfile) {
                    $query->whereNull('deleted_at')->where('status', 1);
                    if ($isChildProfile) {
                        $query->where('is_restricted', 0);
                    }
                },
                'video' => function ($query) use ($isChildProfile) {
                    $query->whereNull('deleted_at')->where('status', 1);
                    if ($isChildProfile) {
                        $query->where('is_restricted', 0);
                    }
                },
            ])
            ->limit($perPage)
            ->get()
            ->filter(function ($item) {
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

                return [
                    'search_id' => (int) $item->search_id,
                    'type' => $item->type,
                    'search_query' => $searchQuery,
                ];
            })
            ->values();

        return ApiResponse::success($topSearches, __('movie.search_history_list'), 200);
    }

    public function saveSearchHistory(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();
        $profile_id = $data['profile_id'] ?? getCurrentProfile($user->id, $request);

        $existingSearch = UserSearchHistory::where('user_id', $user->id)
        ->where('profile_id',   $profile_id )
        ->where('search_query', $data['search_query'])
        ->first();





       if(!$existingSearch) {

           // fallback profile_id from current session/profile helper
           $currentProfileId = $data['profile_id'] ?? getCurrentProfile($user->id, $request);
           $searchQuery = $data['search_query'] ?? null;

            $search_id = null;
            $type = null;

            if ($searchQuery) {

                $entertainment = Entertainment::where('name', 'like', "%{$searchQuery}%")->first();
                if ($entertainment) {
                    $search_id = $entertainment->id;
                    $type = $entertainment->type === 'movie' ? 'movie' : 'tvshow';
                }

                if (!$search_id) {
                    $video = Video::where('name', 'like', "%{$searchQuery}%")->first();
                    if ($video) {
                        $search_id = $video->id;
                        $type = 'video';
                    }
                }
            }

           $search_data  = [
               'user_id' => $user->id,
               'search_query' => $data['search_query'] ?? '',
               'profile_id' => $currentProfileId,
               'search_id'=> $search_id ?? $data['search_id'] ?? null,
               'type'=> $type ?? $data['type'] ?? null,

              ];

              UserSearchHistory::create($search_data);

         }


        return ApiResponse::success(null, __('movie.search'), 200);
    }


    public function deleteSearchHistory(Request $request)
    {
        $user = auth()->user();

        $currentprofile=GetCurrentprofile($user->id, $request);

        $profile_id=$request->has('profile_id')?$request->profile_id:  $currentprofile;

        if($request->type == 'clear_all'){
            $search_history = UserSearchHistory::where('user_id', $user->id)
            ->where(column: 'profile_id', operator: $profile_id)->delete();
            $message = __('movie.clear_all');
            return ApiResponse::success(null, $message, 200);
        }

        $search_history = UserSearchHistory::where('user_id', $user->id)
        ->where('id', $request->id)->where('profile_id', $profile_id)->first();

        if ($search_history == null) {

            $message = __('movie.profile');

            return ApiResponse::error($message, 404);
        }
        $search_history->delete();
        $message = __('movie.delete_sucessfully');


        return ApiResponse::success(null, $message, 200);
    }

    /**
     * Return top N most searched items (by search_id), default for current user's profile.
     * Pass global=1 to aggregate across all users/profiles.
     */
    public function topSearched(Request $request)
    {
        $limit = (int) ($request->input('limit', 5));
        $limit = $limit > 0 ? $limit : 5;

        $query = UserSearchHistory::query()->whereNotNull('search_id');

        // Scope: by default current user + current profile
        $isGlobal = (string) $request->input('global', '0') === '1';
        if (!$isGlobal) {
            $user = auth()->user();
            $userId = $user?->id;
            if ($userId) {
                $profileId = getCurrentProfile($userId, $request);
                $query->where('user_id', $userId);
                if ($profileId) {
                    $query->where('profile_id', $profileId);
                }
            }
        }

        $rows = $query
            ->selectRaw('search_id, type, COUNT(*) as total')
            ->groupBy('search_id', 'type')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return ApiResponse::success($rows, __('movie.search_history_list'), 200);
    }
}
