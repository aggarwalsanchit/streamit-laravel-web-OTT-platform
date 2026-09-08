<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Models\Entertainment;
use Modules\Entertainment\Transformers\MovieDetailResource;
use Modules\Entertainment\Models\Watchlist;
use Illuminate\Support\Facades\Cache;
use Modules\LiveTV\Models\LiveTvCategory;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\LiveTV\Transformers\LiveTvCategoryResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Modules\Genres\Models\Genres;
use Modules\Banner\Transformers\Backend\SliderResourceV3;
use App\Services\RecommendationService;
use Modules\Banner\Models\Banner;
use App\Models\UserSearchHistory;
use Modules\LiveTV\Transformers\Backend\LiveTvChannelResourceV3;
use Modules\Entertainment\Transformers\Backend\CommonContentResourceV3;
use Modules\LiveTV\Transformers\LiveTvChannelDetailsResource;
class MovieController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     protected $recommendationService;


     public function __construct(RecommendationService $recommendationService)
     {
         $this->recommendationService = $recommendationService;

     }

    public function movieList(Request $request,$language=null)
    {
        $access_type = $request->type;
        $user_id = Auth::id();
        $user = Auth::user();

        $featured_movies = Banner::where('banner_for', 'movie')
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();
        $featured_movies = $featured_movies->map(function ($banner) use ($user_id) {
            return (new SliderResourceV3($banner, $user_id))->toArray(request());
        })->values()->all();


        // $movies = Entertainment::where('type', 'movie')
        //     ->when($language, function($query) use ($language) {
        //         return $query->where('language', $language);
        //     })
        //     ->where('status', 1)
        //     ->get();

        // return view('frontend::movie', compact('movies', 'language', 'featured_movies','access_type'));
        return view('frontend::movie', compact('language', 'featured_movies','access_type'));

}

public function moviesListByGenre(int $genre_id)
{

    $genre = Genres::where('id',$genre_id)->first();
    return view('frontend::genres_content', compact('genre_id','genre'));
}

public function moviesListBylanguage(string $language)
{
    return view('frontend::language_content', compact('language'));
}
    public function livetvList()
    {   
        $channelData = LiveTvChannel::with('TvCategory','plan','TvChannelStreamContentMappings')->where('status',1)->orderBy('updated_at', 'desc')->take(6)->get();
       
        $categoryData = LiveTvCategory::with('tvChannels')->where('status',1)->orderBy('updated_at', 'desc')->get();

        // $responseData['slider'] = LiveTvChannelResourceV3::collection($channelData)->toArray(request());
        $featured_livetv_banners = Banner::where('banner_for', 'livetv')
        ->where('status', 1)
        ->orderBy('id', 'desc')
        ->limit(5)
        ->get();

        $user_id = Auth::id();
        $featured_livetvs = $featured_livetv_banners->map(function ($banner) use ($user_id) {
            return (new SliderResourceV3($banner, $user_id))->toArray(request());
        })->values()->all();
        
        $responseData['category_data'] = LiveTvCategoryResource::collection($categoryData)->toArray(request());

        return view('frontend::livetv',compact('responseData','featured_livetvs'));
    }


    public function movieDetails(Request $request, string $id)
    {
        $continue_watch = $request->boolean('continue_watch', false);
        $user_id = Auth::id();
        $cacheKey = "movie_details_{$id}_user_{$user_id}";

        $movieGuard = Entertainment::where('slug', $id)->first();

        if (empty($movieGuard) || (int) ($movieGuard->status) !== 1 || $movieGuard->deleted_at !== null) {
            return redirect()->route('user.login');
        }
        
        if($movieGuard->is_restricted == 1){
            $currentProfile = getCurrentProfileSession('is_child_profile');
            if($currentProfile == 1){
                return redirect()->route('user.login');
            }
        }
        
        if($movieGuard->release_date != null && $movieGuard->release_date->isFuture()){
          

            return redirect()->route('user.login');
        }
        $data = cacheApiResponse($cacheKey, 10, function () use ($id, $user_id) {

            if (!Cache::has('genres')) {
                $genresData = Genres::select('id', 'name')->get()->keyBy('id')->toArray();
                Cache::put('genres', $genresData, now()->addHours(2));
            }

            $movie = Entertainment::with([
                    'entertainmentGenerMappings.genre',
                    'plan',
                    'entertainmentReviews.user',
                    'entertainmentTalentMappings',
                    'entertainmentStreamContentMappings',
                    'entertainmentSubtitleMappings',
                    'clips' => fn($q) => $q->where('content_type', 'movie'),
                    'subtitles' => fn($q) => $q->where('type', 'movie'),
                    'entertainmentLike' => fn($q) => $q->where('user_id', $user_id)->where('is_like', 1),
                    'EntertainmentDownload' => fn($q) => $q->where('user_id', $user_id)
                        ->where('entertainment_type', 'movie')
                        ->where('is_download', 1),
                ])->where('slug','=', $id)->first();

                

            // ✅ Encrypt external URLs
            if (!empty($movie->trailer_url) && $movie->trailer_url_type !== 'Local') {
                $movie->trailer_url = Crypt::encryptString($movie->trailer_url);
            }
            if (!empty($movie->video_url_input) && $movie->video_upload_type !== 'Local') {
                $movie->video_url_input = Crypt::encryptString($movie->video_url_input);
            }

            // ✅ Personalized fields
            if ($user_id) {
                $profile_id = getCurrentProfile($user_id, request());
                $movie->is_watch_list = Watchlist::where('entertainment_id', $movie->id)
                    ->where('user_id', $user_id)
                    ->where('type', 'movie')
                    ->where('profile_id', $profile_id)
                    ->exists();
                $movie->subtitle_enable = $movie->subtitles->isNotEmpty();
                $movie->is_likes = $movie->entertainmentLike->isNotEmpty();
                $movie->is_download = $movie->EntertainmentDownload->isNotEmpty();

                $reviews = $movie->entertainmentReviews ?? collect();
                $yourReview = $reviews->where('user_id', $user_id)->first();

                $movie->your_review = $yourReview;
                $movie->reviews = $yourReview ? $reviews->where('user_id', '!=', $user_id) : $reviews;
                $movie->total_review = $reviews->count();

                $movie->continue_watch = ContinueWatch::where([
                    ['entertainment_id', $movie->id],
                    ['user_id', $user_id],
                    ['entertainment_type', 'movie'],
                ])->first();
            }

            $genreIds = $movie->entertainmentGenerMappings
                ->pluck('genre_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (!empty($genreIds)) {
                $related = Entertainment::query()
                    ->where('status', 1)
                    ->where('type', 'movie')
                    ->where('id', '!=', $movie->id)
                    ->whereHas('entertainmentGenerMappings', fn($q) => $q->whereIn('genre_id', $genreIds))
                    ->with(['entertainmentGenerMappings.genre', 'plan'])
                    ->limit(10)
                    ->get();
            } else {
                $related = collect();
            }
            $currentProfile = getCurrentProfileSession('is_child_profile');
            if($currentProfile == 1){
                $related = $related->where('is_restricted', 0);
            }
            $data = (new MovieDetailResource($movie))->toArray(request());
            $data['more_items'] = CommonContentResourceV3::collection($related)->toArray(request());
            $data['seoData'] = (object) [
                "seo_image" => $movie->seo_image,
                "google_site_verification" => $movie->google_site_verification,
                "canonical_url" => $movie->canonical_url,
                "short_description" => $movie->short_description,
                "meta_title" => $movie->meta_title,
                "meta_keywords" => $movie->meta_keywords,
            ];

            return $data;
        });


            $entertainment = $data['data']['seoData'];


            if ($request->boolean('is_search')) {
                $userId = auth()->id() ?? $request->user_id;

                if ($userId) {
                    $currentProfile = GetCurrentprofile($userId, $request);

                    if ($currentProfile) {
                        $searchName = $data['data']['name'] ?? '';
                        $searchId   = $data['data']['id'] ?? '';
                        $searchType = $data['data']['type'] ?? '';

                        if (!empty($searchName)) {
                            $exists = UserSearchHistory::where([
                                'user_id'     => $userId,
                                'profile_id'  => $currentProfile,
                                'search_query'=> $searchName,
                            ])->exists();

                            if (!$exists) {
                                UserSearchHistory::create([
                                    'user_id'     => $userId,
                                    'profile_id'  => $currentProfile,
                                    'search_query'=> $searchName,
                                    'search_id'   => $searchId,
                                    'type'        => $searchType,
                                ]);
                            }
                        }
                    }
                }
            }

            return view('frontend::movieDetail', compact('data','continue_watch', 'entertainment'));
    }



    public function liveTvDetails(string $id)
    {

        $livetvId = $id;
        $userId = Auth::id();

        // Check if channel exists and is active
        $livetvGuard = LiveTvChannel::where('slug', $livetvId)->first();
        if (empty($livetvGuard) || (int) ($livetvGuard->status) !== 1 || $livetvGuard->deleted_at !== null) {
            return redirect()->route('user.login');
        }

        $livetv = LiveTvChannel::where('slug','=',$livetvId)->with('TvCategory','plan','TvChannelStreamContentMappings')->first();
        $suggestions = LiveTvChannel::where('category_id', $livetv->category_id)
            ->where('slug', '!=', $livetvId) // Exclude the current channel
            ->where('status', 1)
            ->where('deleted_at', null) // Only show active suggestions
            ->with('TvCategory') // Eager load the category
            ->get();

        $suggestions = LiveTvChannelResourceV3::collection($suggestions)->toArray(request());


        $data = new LiveTvChannelDetailsResource($livetv);


        $data=$data->toArray(request());

        if (!empty($livetv->TvChannelStreamContentMappings['server_url'])) {
            $data['server_url'] = Crypt::encryptString($livetv->TvChannelStreamContentMappings['server_url']);
        }



        return view('frontend::livetvDetail', compact('data', 'suggestions'));
    }

    public function livetvChannelsList(Request $request, $id)
    {
        // Find category by slug
        $category = LiveTvCategory::where('slug', $id)->first();

        if (!$category) {
            // Fallback: try to find by ID if slug doesn't match
            $category = LiveTvCategory::find($id);
        }

        $categoryName = $category ? $category->name : __('frontend.tv_channels');
        $tvcategory_id = $category ? $category->id : $id;

        return view('frontend::tvchannelList', compact('categoryName', 'tvcategory_id'));
    }

    public function comingSoonList()
    {

        return view('frontend::comingsoon');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('frontend::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        return back();
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id)
    {
        return view('frontend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)   
    {
        return view('frontend::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        //
    }
}
