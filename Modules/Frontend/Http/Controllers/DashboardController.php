<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Transformers\ContinueWatchResource;
use Modules\Entertainment\Models\Entertainment;
use Modules\Entertainment\Transformers\Backend\CommonContentResourceV3;
use Modules\Video\Transformers\Backend\VideoResourceV3;
use Modules\Video\Models\Video;
use Modules\Episode\Models\Episode;
use Modules\Entertainment\Transformers\Backend\EpisodeResourceV3;
use Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{


    public function getPinpopup(int $id)
    {
        $result = getLoggedUserPin($id);
        if (empty($result)) {
            return response()->json(['error' => "something went wrong"], 400);
        }

        return response()->json(['data' => $result]);
    }

    public function ContinuewatchListV2(Request $request)
    {
        $user=Auth::user();

        $profile_id = getCurrentProfile($user->id, $request);

        $html='';

        $cacheKey = 'ContinuewatchList_'.$user->id.'_'.$profile_id;
        $resultData = Cache::get($cacheKey);

        if(!empty($resultData))
        {
            return response()->json(['html' => $resultData]);
        }

        $continueWatchList = ContinueWatch::where('user_id', $user->id)
        ->whereNotNull('watched_time')
        ->whereNotNull('total_watched_time')
        ->where('profile_id', $profile_id)
        ->where(function($query) {
            $query->whereHas('entertainment', function ($q) {
                $q->where('status', 1)->whereNull('deleted_at');
                isset(request()->is_restricted) && $q->where('is_restricted', request()->is_restricted);
                (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                    $q->where('is_restricted',0);
            })
            ->orWhereHas('episode', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->orWhereHas('video', function ($q) {
                $q->where('status', 1)->whereNull('deleted_at');
            });
        })
        ->with(['entertainment', 'episode', 'video'])
        ->orderBy('id', 'desc')
        ->get();
         $continue_watch = $continueWatchList->map(function ($item) {
             return new ContinueWatchResource($item);
         })->toArray();

        if(!empty($continue_watch)){
            $html = view('frontend::components.section.continue_watch',  ['continuewatchData' =>  $continue_watch])->render();
        }

        Cache::put($cacheKey, $html, now()->addMinutes(60));
        return response()->json(['html' => $html]);

}

function moviePayperview(Request $request){

    // Validate pagination parameters individually
    if ($request->has('page') && (!is_numeric($request->page) || $request->page < 1)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid page parameter'
        ], 422);
    }

    if ($request->has('per_page') && (!is_numeric($request->per_page) || $request->per_page < 1)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid per_page parameter'
        ], 422);
    }

    $html='';

    // Get pagination parameters with defaults
    $perPage = $request->input('per_page', 10);
    $page = $request->input('page', 1);

    $movies =[];

    // Get paginated results instead of all records
    $allMovies = Entertainment::where('movie_access','pay-per-view')
        ->where('type','movie')
        ->where('status',1)
        ->where('deleted_at',null)
        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
            $query->where('is_restricted', 0);
        })
        ->paginate($perPage, ['*'], 'page', $page);

    // Filter purchased movies from paginated results
    $movies = $allMovies->filter(function ($movie) {
        return Entertainment::isPurchased($movie->id, 'movie');
    });

    $movies = CommonContentResourceV3::collection($movies);
    $movies =  $movies->toArray(request());

    if(!empty($movies)){
        $html = view('frontend::components.section.entertainment',  ['data' => $movies,'title' => __('frontend.movies'),'type' =>'movies-pay-per-view','slug'=>'movies_pay_per_view'])->render();
    }

    return response()->json(['html' => $html]);
}


 public function videosPayperview(Request $request)
 {

    // Validate pagination parameters individually
    if ($request->has('page') && (!is_numeric($request->page) || $request->page < 1)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid page parameter'
        ], 422);
    }

    if ($request->has('per_page') && (!is_numeric($request->per_page) || $request->per_page < 1)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid per_page parameter'
        ], 422);
    }

    // Get pagination parameters with defaults
    $perPage = $request->input('per_page', 10);
    $page = $request->input('page', 1);



    $html='';



      $genres=[];


    // Get paginated results instead of all records
    $allvideos = Video::where('access', 'pay-per-view')
        ->where('status', 1)
        ->where('deleted_at',null)
        ->when($request->has('is_restricted'), function ($query) use ($request) {
            $query->where('is_restricted', $request->is_restricted);
        })
        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
            $query->where('is_restricted', 0);
        })
        ->paginate($perPage, ['*'], 'page', $page);
         $videos = $allvideos->filter(function ($movie) {
           return Entertainment::isPurchased($movie->id, 'video');
         });
         $videos = VideoResourceV3::collection($videos);

    if($videos->isNotEmpty()){

      $html = view('frontend::components.section.video',  ['data' => $videos->toArray(request()),'title' => __('sidebar.videos')]) ->render();

     }

    return response()->json(['html' => $html]);

 }

 public function getEpisodesPayPerView(Request $request)
 {
    // Validate pagination parameters individually
    if ($request->has('page') && (!is_numeric($request->page) || $request->page < 1)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid page parameter'
        ], 422);
    }

    if ($request->has('per_page') && (!is_numeric($request->per_page) || $request->per_page < 1)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid per_page parameter'
        ], 422);
    }

    // Get pagination parameters with defaults
    $perPage = $request->input('per_page', 10);
    $page = $request->input('page', 1);

    $html='';

    $genres=[];
    // Get paginated results instead of all records
    $allvideos = Episode::where('access', 'pay-per-view')
        ->where('status', 1)
        ->where('deleted_at',null)
        ->when($request->has('is_restricted'), function ($query) use ($request) {
            $query->where('is_restricted', $request->is_restricted);
        })
        ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
            $query->where('is_restricted', 0);
        })
        ->paginate($perPage, ['*'], 'page', $page);
         $season = $allvideos->filter(function ($movie) {
           return Entertainment::isPurchased($movie->id, 'episode');
         });

         $season = EpisodeResourceV3::collection($season);

    if($season->isNotEmpty()){

     $html = view('frontend::components.section.episode',  ['data' => $season,'title' => __('sidebar.episodes')]) ->render();

    }

    return response()->json(['html' => $html]);
 }


}























