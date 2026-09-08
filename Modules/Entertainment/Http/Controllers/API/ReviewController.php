<?php

namespace Modules\Entertainment\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Modules\Entertainment\Models\Review;
use Modules\Entertainment\Transformers\ReviewResource;
use Modules\Entertainment\Models\Like;
use Modules\Video\Models\Video;
use Modules\Entertainment\Models\Entertainment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
class ReviewController extends Controller
{
    public function getRating(Request $request)
    {

        $perPage = $request->input('per_page', 10);

        $reviews = Review::query();

        if ($request->has('entertainment_id')) {
            $reviews = $reviews->where('entertainment_id', $request->entertainment_id);
        }
        if ($request->has('movie_id')) {
            $reviews = $reviews->where('entertainment_id', $request->movie_id);
        }

        if ($request->has('sort') && $request->sort==='top_star') {
             $reviews = $reviews->orderBy('rating', 'desc')
                           ->paginate($perPage);
        }else{
            $reviews = $reviews->orderBy('updated_at', 'desc')->paginate($perPage);
        }

        $review =   ReviewResource::collection($reviews);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $html = '';
            foreach ($review->toArray($request) as $reviewData) {
                $html .= '<li>' . view('frontend::components.card.card_review_list', ['data' => $reviewData])->render() . '</li>';;
            }

            $hasMore = $reviews->hasMorePages();

            return ApiResponse::success(
                $review,
                __('movie.movie_list'),
                200,
                ['html' => $html, 'hasMore' => $hasMore]
            );
        }
        return ApiResponse::success($review, __('movie.review_list'), 200);
    }

    public function saveRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entertainment_id' => 'required',
            'rating' => 'required|numeric|between:1,5',
            'review' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $user = auth()->user();
        $rating_data = $request->all();
        $rating_data['user_id'] = $user->id ?? $request->user_id ?? null;
        $entertainment = Entertainment::where('id', $request->entertainment_id)->first();
        $result = Review::updateOrCreate(['user_id' => $user->id , 'entertainment_id' => $request->entertainment_id ], $rating_data);

      
        // Reviews affect entertainment cache
        if ($request->has('entertainment_id')) {
            clearRelatedCache(['movie_'.$request->entertainment_id, 'tvshow_'.$request->entertainment_id], 'entertainment');
        }

        $message = __('movie.rating_update');
        if ($result->wasRecentlyCreated) {
            $message = __('movie.rating_add');
        }
        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $review = Review::with('user')->find($result->id);
            $reviewResource = new ReviewResource($review);
            $reviewResource->created_at = formatDateTimeWithTimezone($reviewResource->created_at);
            return ApiResponse::success($reviewResource, $message, 200);
        }
        return ApiResponse::success(null, $message, 200);
    }

    public function update(Request $request)
    {
        $rating = Review::find($request->id);
        $rating->update($request->all());

        return ApiResponse::success($rating, 'Rating updated successfully!', 200);
    }


    public function deleteRating(Request $request)
    {
        $rating = Review::where('id', $request->id)->first();

        if ($rating == null) {

            $message = __('movie.rating_notfound');

            return ApiResponse::error($message, 404);
        }
        $message = __('movie.rating_delete');

        $entertainment_id=$rating->entertainment_id;

        $rating->delete();

        $rating_count=Review::where('entertainment_id',  $entertainment_id)->count();

        // Reviews affect entertainment cache
        clearRelatedCache(['movie_'.$entertainment_id, 'tvshow_'.$entertainment_id], 'entertainment');


        return ApiResponse::success(null, $message, 200, ['rating_count' => $rating_count]);
    }

    public function saveLikes(Request $request)
    {
        $user = auth()->user();

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user->id, $request);

        $likes_data = $request->all();

        $likes_data['profile_id']= $profile_id;

        $likes_data['user_id'] = $user->id;

        if($request->type == 'video'){
            $entertainment = Video::where('id', $request->entertainment_id)->first();

        }else{
            $entertainment = Entertainment::where('id', $request->entertainment_id)->first();
        }

        $likes = Like::updateOrCreate(
            ['entertainment_id' => $request->entertainment_id, 'user_id' => $user->id],
            $likes_data
        );
        // Likes affect entertainment cache
        if ($entertainment->type == 'movie') {
            clearRelatedCache('movie_'.$request->entertainment_id, 'entertainment');

        } else if ($entertainment->type == 'tvshow') {
            clearRelatedCache('tvshow_'.$request->entertainment_id, 'entertainment');

            $message = $likes->is_like == 1 ? __('movie.like_msg') : __('movie.unlike_msg');

            return ApiResponse::success(null, $message, 200);
        }


        $message = $likes->is_like == 1 ? __('movie.like_msg') : __('movie.unlike_msg');

        return ApiResponse::success(null, $message, 200);
    }

}
