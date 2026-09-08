<?php

namespace Modules\CastCrew\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Modules\CastCrew\Models\CastCrew;
use Modules\CastCrew\Transformers\CastCrewListResource;
use Modules\Entertainment\Models\EntertainmentTalentMapping;
use Modules\CastCrew\Transformers\CastDetailResourceV3;
use Modules\Entertainment\Models\Entertainment;
use \Modules\Entertainment\Models\Review;
use Carbon\Carbon;
class CastCrewController extends Controller
{
    public function castCrewList(Request $request){

        $perPage = $request->input('per_page', 10);
        $castcrew_list = CastCrew::query();

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $castcrew_list->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%{$searchTerm}%");
            });
        }

        if($request->has('type')){
            $castcrew_list->where('type', $request->type);
        }

        if($request->has('entertainment_id') && $request->entertainment_id !=null){
            $talentIds=EntertainmentTalentMapping::where('entertainment_id',$request->entertainment_id)->pluck('talent_id');
            $castcrew_list->whereIn('id',$talentIds);
        }
        if($request->has('entertainment_id') && $request->entertainment_id =='all'){
            $castcrew_list = CastCrew::query();
        }

        $castcrew = $castcrew_list->where('deleted_at',null)->orderBy('updated_at', 'desc');
        $castcrew = $castcrew->paginate($perPage);

        $responseData = CastCrewListResource::collection($castcrew);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';

            foreach ($responseData->toArray($request) as $castcrewData) {
                $html .= view('frontend::components.card.card_castcrew_details', ['data' => $castcrewData])->render();
            }

            $hasMore = $castcrew->hasMorePages();

            return ApiResponse::success(
                null,
                __('movie.movie_list'),
                200,
                ['html' => $html, 'hasMore' => $hasMore]
            );
        }

        return ApiResponse::success($responseData, __('castcrew.castcrew_list'), 200);
    }
    public function castCrewDetailsV3(Request $request){
        $castcrewId = $request->id;
        $type = $request->type;
        $userId = $request->user_id ?? auth()->id();
        $cacheKey = 'cast_crew_details_v3_'. md5(json_encode([
            'castcrew_id' => $castcrewId,
            'user_id' => $userId,
            'type' => $type
        ]));
        $cachedResponse = cacheApiResponse($cacheKey, 300, function () use ($castcrewId, $type) {
            $query = CastCrew::with('entertainmentTalentMappings')
                ->where('id',$castcrewId)
                ->where('type', $type)
                ->first();
            if($query){
                $movieCount = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($castcrewId) {
                        $query->where('talent_id', $castcrewId);
                    })
                    ->where('type', 'movie')
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->where(function ($query) {
                        $query->whereDate('release_date', '<=', Carbon::now())
                              ->orWhereNull('release_date');
                    })
                    ->count();

                $tvshowCount = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($castcrewId) {
                        $query->where('talent_id', $castcrewId);
                    })
                    ->where('type', 'tvshow')
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->where(function ($query) {
                        $query->whereDate('release_date', '<=', Carbon::now())
                              ->orWhereNull('release_date');
                    })
                    ->whereHas('season', function ($seasonQuery) {
                        $seasonQuery->where('status', 1)
                            ->whereNull('deleted_at')
                            ->whereHas('episodes', function ($episodeQuery) {
                                $episodeQuery->where('status', 1)
                                    ->whereNull('deleted_at');
                            });
                    })
                    ->count();


                $averageRating = Review::whereHas('entertainment', function ($query) use ($castcrewId) {
                        $query->whereHas('entertainmentTalentMappings', function ($subQuery) use ($castcrewId) {
                            $subQuery->where('talent_id', $castcrewId);
                        })
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->where(function ($dateQuery) {
                            $dateQuery->whereDate('release_date', '<=', Carbon::now())
                                      ->orWhereNull('release_date');
                        });
                    })->avg('rating');


                $topGenres = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($castcrewId) {
                        $query->where('talent_id', $castcrewId);
                    })
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->where(function ($query) {
                        $query->whereDate('release_date', '<=', Carbon::now())
                              ->orWhereNull('release_date');
                    })
                    ->with(['entertainmentGenerMappings.genre:id,name'])
                    ->get()
                    ->pluck('entertainmentGenerMappings')->flatten()->pluck('genre.name')
                    ->filter()
                    ->countBy()->sortDesc()->take(1)->keys()->implode(', ');
                $query->profile_image = $query->file_url ? setBaseUrlWithFileName($query->file_url, 'image', 'castcrew') : null;
                $query->rating = $averageRating;
                $query->top_genres = $topGenres;
                $query->movie_count = $movieCount;
                $query->tvshow_count = $tvshowCount;
                return new CastDetailResourceV3($query);
            }else{
                return false;
            }
        });
        if($cachedResponse['data'] == false){
            return ApiResponse::error('Cast crew not found.', 404);
        }
        return ApiResponse::success($cachedResponse['data'], null, 200);
    }
}
