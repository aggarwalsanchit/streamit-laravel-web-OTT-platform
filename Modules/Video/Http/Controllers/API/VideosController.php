<?php

namespace Modules\Video\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Modules\Video\Models\Video;
use Modules\Entertainment\Models\Watchlist;
use Modules\Video\Transformers\VideoResource;
use Modules\Video\Transformers\VideoDetailResource;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Models\Like;
use Modules\Entertainment\Models\EntertainmentDownload;
use Carbon\Carbon;
use Modules\Video\Transformers\Backend\VideoResourceV3;
use Modules\Frontend\Models\PayPerView;


class VideosController extends Controller
{
    public function videoList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
         $videoList = Video::where(function ($query) {
            $query->whereDate('release_date', '<=', Carbon::now())
                  ->orWhereNull('release_date');
        })
        ->where('status', 1)
        ->with('VideoStreamContentMappings', 'plan');

        isset($request->is_restricted) && $videoList = $videoList->where('is_restricted', $request->is_restricted);

        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $videoList = $videoList->where('is_restricted',0);

        $videoData = $videoList->orderBy('updated_at', 'desc')->paginate($perPage);

        $responseData = VideoResource::collection($videoData);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';
            foreach ($responseData->toArray($request) as $videosData) {
                $userId = auth()->id();
                if ($userId) {
                    $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
                    $isInWatchList = WatchList::where('entertainment_id', $videosData['id'])
                        ->where('user_id', $userId)
                        ->where('type', 'video')
                        ->where('profile_id', $profile_id)
                        ->exists();

                    // Set the flag in the video data
                    $videosData['is_watch_list'] = $isInWatchList ? true : false;
                }
                $html .= view('frontend::components.card.card_video', ['data' => $videosData])->render();
            }

            $hasMore = $videoData->hasMorePages();

            return ApiResponse::success(
                null,
                __('movie.tvshow_list'),
                200,
                ['html' => $html, 'hasMore' => $hasMore, 'nextCursor' => $videoData->nextCursor()?->encode(), 'nextPageUrl' => $videoData->nextPageUrl()]
            );
        }

        return ApiResponse::success($responseData, __('video.video_list'), 200);
    }

    public function videoListV3(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $user_id = $request->input('user_id') ?? auth()->id();
        $profile_id = getCurrentProfile($user_id, $request);

        $videoList = Video::select([
            'id', 'name', 'slug', 'description', 'type', 'trailer_url_type', 'trailer_url',
            'access', 'IMDb_rating', 'plan_id', 'duration', 'release_date', 'poster_url',
            'poster_tv_url', 'short_desc', 'is_restricted'
        ])
            ->where(function ($query) {
                $query->whereDate('release_date', '<=', now())
                      ->orWhereNull('release_date');
            })
            ->where('status', 1)
            ->whereNull('deleted_at');

        $videoList->with([
            'plan:id,level'
        ]);

        if ($user_id && $profile_id) {
            $videoList->withCount([
                'watchlist as is_watch_list' => function ($q) use ($user_id, $profile_id) {
                    $q->where('user_id', $user_id)
                      ->where('profile_id', $profile_id)
                      ->where('type', 'video');
                }
            ]);
        }

        if (isset($request->is_restricted)) {
            $videoList->where('is_restricted', $request->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile'))) {
            $videoList->where('is_restricted', 0);
        }

        $videos = $videoList->orderByDesc('id')->simplePaginate($perPage);

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

        $user = null;
        if ($user_id) {
            $user = \App\Models\User::with('subscriptionPackage:id,level')
                ->where('id', $user_id)
                ->first();
        }

        $userPlanLevel = $user?->subscriptionPackage?->level ?? 0;
        $planIds = $videos->getCollection()->pluck('plan_id')->filter()->unique();
        if ($planIds->isNotEmpty()) {
            $plans = \Modules\Subscriptions\Models\Plan::whereIn('id', $planIds)
                ->select('id', 'level')
                ->get()
                ->keyBy('id');
            $videos->getCollection()->transform(function($video) use ($plans, $userPlanLevel) {
                $video->preloaded_plan_level = $plans->get($video->plan_id)?->level ?? 0;
                $video->preloaded_user_plan_level = $userPlanLevel;
                return $video;
            });
        } else {
            $videos->getCollection()->transform(function($video) use ($userPlanLevel) {
                $video->preloaded_plan_level = 0;
                $video->preloaded_user_plan_level = $userPlanLevel;
                return $video;
            });
        }

        $videos->getCollection()->transform(function($video) use ($purchasedIds, $user) {
            $video->preloaded_purchased_ids = $purchasedIds;
            $video->preloaded_user = $user;
            return $video;
        });

        $responseData = VideoResourceV3::collection($videos);

        if ($request->boolean('is_ajax')) {
            $html = '';
            if (!empty($responseData)) {
                $html .= view('frontend::components.card.card_video', ['values' => $responseData->toArray($request)])->render();
            }

            // OPTIMIZATION: Return cursor pagination metadata for frontend
            return ApiResponse::success(
                $responseData,
                __('video.video_list'),
                200,
                [
                    'html' => $html, 
                    'hasMore' => $videos->hasMorePages(),
                    // 'nextCursor' => $videos->nextCursor()?->encode(),
                    // 'nextPageUrl' => $videos->nextPageUrl()
                ]
            );
        }

        return ApiResponse::success($responseData, __('video.video_list'), 200);
    }


  public function videoDetails(Request $request){

            $video = Video::with('VideoStreamContentMappings','plan','subtitles')->where('id', $request->video_id)->first();

            if($request->has('user_id')){
                $user_id = $request->user_id;
                $continueWatch = ContinueWatch::where('entertainment_id', $video->id)->where('user_id', $user_id)->where('entertainment_type', 'video')->first();
                $video['continue_watch'] = $continueWatch;
                $video['user_id'] = $user_id;
                $video['is_watch_list'] = WatchList::where('entertainment_id',$request->video_id )->where('user_id', $user_id)->where('profile_id', $request->profile_id)
                ->where('type', 'video')->exists();
                $video['is_likes'] = Like::where('entertainment_id', $request->video_id)->where('type', 'video')->where('user_id', $user_id)->where('profile_id', $request->profile_id)
                ->where('is_like', 1)->exists();
                $video['is_download'] = EntertainmentDownload::where('entertainment_id', $request->video_id)->where('device_id',$request->device_id)->where('user_id', $user_id)
                ->where('entertainment_type', 'video')->where('is_download', 1)->exists();
            }

            $responseData = new VideoDetailResource($video);


      return ApiResponse::success($responseData, __('video.video_details'), 200);
  }
}
