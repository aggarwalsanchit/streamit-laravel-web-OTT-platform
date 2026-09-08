<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Entertainment\Transformers\TvshowDetailResource;
use Modules\Entertainment\Models\Watchlist;
use Illuminate\Support\Facades\Cache;
use Modules\Entertainment\Models\Entertainment;
use Modules\Episode\Models\Episode;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Genres\Models\Genres;
use Modules\Entertainment\Transformers\EpisodeDetailResource;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use App\Models\UserSearchHistory;
use Modules\Season\Models\Season;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Storage;
use Modules\Banner\Models\Banner;

use Modules\Banner\Transformers\Backend\SliderResourceV3;
use App\Services\RecommendationService;

use Modules\Frontend\Models\PayPerView;


class TvShowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function index()
    {
        return view('frontend::index');
    }

    public function tvShowList($language = null)
    {
        $user_id = auth()->id();
        $user = Auth::user();

        $featured_tvshow = Banner::where('banner_for', 'tv_show')
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();
        $sliders = $featured_tvshow->map(function ($banner) use ($user_id) {
            return (new SliderResourceV3($banner, $user_id))->toArray(request());
        })->values()->all();

        return view('frontend::tvShow', compact(

            'sliders',

        ));
    }

    public function tvshowDetail(Request $request, string $slug)
    {
        $user_id = Auth::id();
        $cacheKey = "tvshow_details_{$slug}_user_{$user_id}";
        $is_search = $request->boolean('is_search', false);

        $movieGuard = Entertainment::where('slug', $slug)->first();
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

        $season = Season::where('entertainment_id', $movieGuard->id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

        if (!$season) {
            return redirect()->route('user.login');
        }

        $episode = Episode::where('entertainment_id', $movieGuard->id)
            ->where('season_id', $season->id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();

        if (!$episode) {
            return redirect()->route('user.login');
        }

        $data = cacheApiResponse($cacheKey, 10, function () use ($slug, $user_id) {

            if (!Cache::has('genres')) {
                $genresData = Genres::select('id', 'name')->get()->keyBy('id')->toArray();
                Cache::put('genres', $genresData, now()->addHours(2));
            }


            $tvshow = Entertainment::with([
                    'entertainmentGenerMappings.genre',
                    'plan',
                    'entertainmentReviews.user',
                    'entertainmentTalentMappings',
                    'season',
                    'episode',
                    'subtitles' => fn($q) => $q->where('type', 'tvshow'),
                    'entertainmentLike' => fn($q) => $q->where('user_id', $user_id)->where('is_like', 1),
                ])
                ->where('slug', $slug)
                ->first();

            if (!$tvshow) {
                abort(404, 'TV show not found.');
            }

            if (!empty($tvshow->trailer_url) && $tvshow->trailer_url_type !== 'Local') {
                $tvshow->trailer_url = Crypt::encryptString($tvshow->trailer_url);
            }

            if ($user_id) {
                $profile_id = getCurrentProfile($user_id, request());
                $tvshow->is_watch_list = Watchlist::where('entertainment_id', $tvshow->id)
                    ->where('user_id', $user_id)
                    ->where('type', 'tvshow')
                    ->where('profile_id', $profile_id)
                    ->exists();
                $tvshow->subtitle_enable = $tvshow->subtitles->isNotEmpty();
                $tvshow->is_likes = $tvshow->entertainmentLike->isNotEmpty();

                $reviews = $tvshow->entertainmentReviews ?? collect();
                $yourReview = $reviews->where('user_id', $user_id)->first();

                $tvshow->your_review = $yourReview;
                $tvshow->reviews = $yourReview ? $reviews->where('user_id', '!=', $user_id) : $reviews;
                $tvshow->total_review = $reviews->count();
            }


            $season_id = Season::where('entertainment_id', $tvshow->id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->value('id');
            $episode = Episode::where('entertainment_id', $tvshow->id)
                ->where('season_id', $season_id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->with(['entertainmentdata', 'plan', 'EpisodeStreamContentMapping', 'episodeDownloadMappings'])
                ->first();

            if (!$episode) {
                abort(404, 'No episode found.');
            }

            $genre_ids = $tvshow->entertainmentGenerMappings->pluck('genre_id')->filter()->unique()->values();

            $episode->genre_data = Genres::whereIn('id', $genre_ids)->get();

            $episode->moreItems = Entertainment::where('type', 'tvshow')
                ->where('status', 1)
                ->where('id', '!=', $tvshow->id)
                ->whereHas('entertainmentGenerMappings', fn($q) => $q->whereIn('genre_id', $genre_ids))
                ->orderByDesc('id')
                ->limit(10)
                ->get();

            $data = (new TvshowDetailResource($tvshow))->toArray(request());
            $data['episodeData'] = (new EpisodeDetailResource($episode))->toArray(request());
            $data['seoData'] = (object) [
                "seo_image" => $tvshow->seo_image,
                "google_site_verification" => $tvshow->google_site_verification,
                "canonical_url" => $tvshow->canonical_url,
                "short_description" => $tvshow->short_description,
                "meta_title" => $tvshow->meta_title,
                "meta_keywords" => $tvshow->meta_keywords,
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

        return view('frontend::tvshowDetail', compact('data', 'entertainment'));
    }

    public function episodeDetail(Request $request, string $slug)
    {
        $user_id = auth()->id();
        $continue_watch = $request->boolean('continue_watch', false);
        $cacheKey = "episode_details_{$slug}_user_{$user_id}";
        $is_search = $request->boolean('is_search', false);

        $episodeGuard = Episode::where('slug', $slug)->with('entertainmentdata')->first();
        if (empty($episodeGuard) || (int) ($episodeGuard->status) !== 1 || $episodeGuard->deleted_at !== null) {
            return redirect()->route('user.login');
        }

        if (empty($episodeGuard->entertainmentdata) ||
            (int) ($episodeGuard->entertainmentdata->status) !== 1 ||
            $episodeGuard->entertainmentdata->deleted_at !== null) {
            return redirect()->route('user.login');
        }

        if($episodeGuard->is_restricted == 1){
            $currentProfile = getCurrentProfileSession('is_child_profile');
            if($currentProfile == 1){
                return redirect()->route('user.login');
            }
        }

        // ✅ Cache episode details using Redis
        $data = cacheApiResponse($cacheKey, 10, function () use ($slug, $user_id) {

            // Load episode with relationships
            $episode = Episode::with([
                    'entertainmentdata.entertainmentGenerMappings.genre',
                    'plan',
                    'EpisodeStreamContentMapping',
                    'episodeDownloadMappings',
                ])
                ->where('slug', $slug)
                ->firstOrFail();

            // Encrypt external URLs
            if (!empty($episode->trailer_url) && $episode->trailer_url_type !== 'Local') {
                $episode->trailer_url = Crypt::encryptString($episode->trailer_url);
            }

            if (!empty($episode->video_url_input) && $episode->video_upload_type !== 'Local') {
                $episode->video_url_input = Crypt::encryptString($episode->video_url_input);
            }

            $genreIds = $episode->entertainmentData
                ->entertainmentGenerMappings
                ->pluck('genre_id')->toArray();


            $episode->moreItems = !empty($genreIds)
                ? Entertainment::where('type', 'tvshow')
                    ->whereHas('entertainmentGenerMappings', fn($q) => $q->whereIn('genre_id', $genreIds))
                    ->where('id', '!=', $episode->id)
                    ->orderByDesc('id')
                    ->get()
                : collect();

            $episode->genre_data = Genres::whereIn('id', $genreIds)->get();


            if ($user_id) {
                $episode->continue_watch = ContinueWatch::where([
                    ['episode_id', $episode->id],
                    ['user_id', $user_id],
                    ['entertainment_type', 'tvshow'],
                ])->first();

                $episode->is_download = EntertainmentDownload::where([
                    ['entertainment_id', $episode->id],
                    ['user_id', $user_id],
                    ['entertainment_type', 'episode'],
                    ['is_download', 1],
                ])->exists();
            }

            $data = (new EpisodeDetailResource($episode))->toArray(request());
            $data['seoData'] = (object) [
                "seo_image" => $episode->seo_image,
                "google_site_verification" => $episode->google_site_verification,
                "canonical_url" => $episode->canonical_url,
                "short_description" => $episode->short_description,
                "meta_title" => $episode->meta_title,
                "meta_keywords" => $episode->meta_keywords,
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

        return view('frontend::episode_detail', compact('data', 'continue_watch', 'entertainment'));
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
        //
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
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        //
    }

    public function stream(string $encryptedUrl)
    {
        $result = decryptVideoUrl($encryptedUrl);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json($result, 200, [], JSON_UNESCAPED_SLASHES);
    }

    public function streamLocal(string $encryptedUrl, HttpRequest $request)
    {
        $url = Crypt::decryptString($encryptedUrl);

        if (!Storage::disk('local')->exists('test.mp4')) {
            abort(404, 'Video not found.');
        }

        return response()->stream(function () {
            $stream = Storage::disk('local')->readStream('test.mp4');

            fpassthru($stream);
            fclose($stream);
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'video/mp4',
            'Content-Length' => Storage::disk('local')->size('test.mp4'),
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="test.mp4"'
        ]);
    }

    public function checkEpisodePurchase(Request $request)
    {
        $episodeId = $request->episode_id;
        $userId = auth()->id();

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated',
                'is_purchased' => false
            ]);
        }

        $episode = Episode::find($episodeId);

        if (!$episode) {
            return response()->json([
                'status' => false,
                'message' => 'Episode not found',
                'is_purchased' => false
            ]);
        }

        // Check if episode is pay-per-view
        $isPayPerView = $episode->access === 'pay-per-view';

        if (!$isPayPerView) {
            return response()->json([
                'status' => true,
                'message' => 'Episode is not pay-per-view',
                'is_purchased' => true
            ]);
        }

        // Check if user has purchased the episode
        $hasPurchased = PayPerView::where('user_id', $userId)
            ->where('movie_id', $episodeId)
            ->where('type', 'episode')
            ->where(function ($query) {
                $query->whereNull('view_expiry_date')
                    ->orWhere('view_expiry_date', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('first_play_date')
                    ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
            })
            ->exists();

        return response()->json([
            'status' => true,
            'message' => $hasPurchased ? 'Episode is purchased' : 'Episode is not purchased',
            'is_purchased' => $hasPurchased,
            'is_pay_per_view' => true,
            'episode_id' => $episodeId
        ]);
    }

    public function checkMoviePurchase(Request $request)
    {
        $movieId = $request->movie_id;
        $userId = auth()->id();

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated',
                'is_purchased' => false
            ]);
        }

        $movie = Entertainment::find($movieId);

        if (!$movie) {
            return response()->json([
                'status' => false,
                'message' => 'Movie not found',
                'is_purchased' => false
            ]);
        }

        // Check if movie is pay-per-view
        $isPayPerView = $movie->movie_access === 'pay-per-view';

        if (!$isPayPerView) {
            return response()->json([
                'status' => true,
                'message' => 'Movie is not pay-per-view',
                'is_purchased' => true
            ]);
        }

        // Check if user has purchased the movie
        $hasPurchased = PayPerView::where('user_id', $userId)
            ->where('movie_id', $movieId)
            ->where('type', 'movie')
            ->where(function ($query) {
                $query->whereNull('view_expiry_date')
                    ->orWhere('view_expiry_date', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('first_play_date')
                    ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL access_duration DAY) > ?', [now()]);
            })
            ->exists();

        return response()->json([
            'status' => true,
            'message' => $hasPurchased ? 'Movie is purchased' : 'Movie is not purchased',
            'is_purchased' => $hasPurchased,
            'is_pay_per_view' => true,
            'movie_id' => $movieId
        ]);
    }
}
