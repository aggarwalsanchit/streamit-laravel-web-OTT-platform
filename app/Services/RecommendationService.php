<?php
    namespace App\Services;

    use App\Models\User;
    use Modules\Entertainment\Models\Entertainment;
    use Modules\Entertainment\Models\EntertainmentGenerMapping;
    use Modules\Entertainment\Models\Like;
    use Modules\Entertainment\Models\EntertainmentView;
    use Modules\Entertainment\Models\Watchlist;
    use Modules\Entertainment\Transformers\WatchlistResource;
    use Modules\Entertainment\Models\EntertainmentTalentMapping;
    use Modules\Entertainment\Models\EntertainmentCountryMapping;
    use Modules\World\Models\Country;
    use Modules\Genres\Models\Genres;
    use Modules\CastCrew\Models\CastCrew;
    use libphonenumber\PhoneNumberUtil;
    use App\Models\UserWatchHistory;
    use Carbon\Carbon;

    class RecommendationService
    {
        /**
         * Get the most recent watch history of a user
         *
         * @param User $user
         * @param int $profileId
         * @return mixed
         */
        public function getRecentlyWatched($user, $profileId)
        {

            if($user){
                return UserWatchHistory::where('profile_id', $profileId)
                            ->where('user_id', $user->id)
                            ->where('entertainment_type', 'movie')
                            ->first();


            }
        }

        /**
         * Get the genre IDs associated with an entertainment ID
         *
         * @param int $entertainmentId
         * @return array
         */
        protected function getGenresByEntertainmentId($entertainmentId)
        {

            return EntertainmentGenerMapping::where('entertainment_id', $entertainmentId)
                                            ->pluck('genre_id')
                                            ->toArray();

        }
        public function recommendByLastHistory($user, $profileId)
        {
            $recentlyWatched = $this->getRecentlyWatched($user, $profileId);


            if (!$recentlyWatched) {
                return [];
            }

            $genres = $this->getGenresByEntertainmentId($recentlyWatched->entertainment_id);

            // OPTIMIZATION: Add eager loading to prevent N+1 queries
            $result = Entertainment::with([
                'plan:id,level',
                'genresdata:id,name'
            ])
            ->whereIn('id', function($query) use ($genres) {
                    $query->select('entertainment_id')
                          ->from('entertainment_gener_mapping')
                          ->whereIn('genre_id', $genres);
                });
            isset(request()->is_restricted) && $result = $result->where('is_restricted', request()->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $result = $result->where('is_restricted',0);

            $result = $result->where('id', '!=', $recentlyWatched->entertainment_id)
                ->where('type', 'movie')
                ->where('status',1)
                ->released()
                ->take(10)
                ->get();

            return $result;
        }
        public function recommendByLastHistoryV3($user, $profileId)
        {
            $recentlyWatched = $this->getRecentlyWatched($user, $profileId);


            if (!$recentlyWatched) {
                return [];
            }

            $genres = $this->getGenresByEntertainmentId($recentlyWatched->entertainment_id);

            // OPTIMIZATION: Add eager loading to prevent N+1 queries
            $result = Entertainment::with([
                'plan:id,level',
                'genresdata:id,name'
            ])
            ->select([
                'id',
                'name',
                'type',
                'release_date',
                'trailer_url_type',
                'is_restricted',
                'movie_access',
                'imdb_rating',
                'poster_url',
                'thumbnail_url',
                'poster_tv_url',
                'trailer_url',
                'video_url_input',
                'plan_id',
            ])
            ->whereIn('id', function($query) use ($genres) {
                    $query->select('entertainment_id')
                          ->from('entertainment_gener_mapping')
                          ->whereIn('genre_id', $genres);
                });
            isset(request()->is_restricted) && $result = $result->where('is_restricted', request()->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $result = $result->where('is_restricted',0);

            $result = $result->where('id', '!=', $recentlyWatched->entertainment_id)
                ->where('type', 'movie')
                ->where('status',1)
                ->where('deleted_at', null)
                ->released()
                ->take(10)
                ->get();

            return $result;
        }

        public function getLikedMovies($user, $profileId)
        {
            // Get IDs of movies liked by the user for a specific profile
            $likedEntertainmentIds = Like::where([
                ['user_id', '=', $user->id],
                ['profile_id', '=', $profileId],
                ['is_like', '=', true]
            ])->pluck('entertainment_id');

            $mostLikedMovies = Like::where('is_like', true)
                ->whereNotIn('entertainment_id', $likedEntertainmentIds)
                ->whereNotNull('entertainment_id')
                ->select('entertainment_id')
                ->groupBy('entertainment_id')
                ->orderByRaw('COUNT(*) DESC')
                ->pluck('entertainment_id');


            // OPTIMIZATION: Add eager loading to prevent N+1 queries
            $result = Entertainment::with([
                'plan:id,level',
                'genresdata:id,name'
            ])
            ->whereIn('id', $mostLikedMovies)
                    ->where('type', 'movie')
                    ->where('status',1);

            isset(request()->is_restricted) && $result = $result->where('is_restricted', request()->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $result = $result->where('is_restricted',0);

            return $result = $result
                ->limit(10)
                ->released()
                ->orderByRaw('FIELD(id, ' . $mostLikedMovies->implode(',') . ')') // Preserve the order of IDs
                ->get();
        }

        public function getLikedMoviesV3($user, $profileId)
        {
            // Get IDs of movies liked by anyone
            $mostLikedMovies = Like::where('is_like', true)
                ->whereIn('entertainment_id', function($query) {
                    $query->select('id')->from('entertainments')->where('type', 'movie')->where('status', 1)->whereNull('deleted_at');
                })
                ->whereNotNull('entertainment_id')
                ->select('entertainment_id')
                ->groupBy('entertainment_id')
                ->orderByRaw('COUNT(*) DESC')
                ->pluck('entertainment_id');


            // OPTIMIZATION: Add eager loading to prevent N+1 queries
            $result = Entertainment::with([
                'plan:id,level',
                'genresdata:id,name'
            ])
            ->select([
                'id',
                'name',
                'type',
                'release_date',
                'trailer_url_type',
                'is_restricted',
                'movie_access',
                'imdb_rating',
                'video_upload_type',
                'poster_url',
                'thumbnail_url',
                'poster_tv_url',
                'trailer_url',
                'video_url_input',
                'plan_id',
            ])
            ->whereIn('id', $mostLikedMovies)
                    ->where('type', 'movie')
                    ->where('status',1);

            isset(request()->is_restricted) && $result = $result->where('is_restricted', request()->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $result = $result->where('is_restricted',0);

            return $result = $result
                ->released()
                ->where('deleted_at', null)
                ->orderByRaw('FIELD(id, ' . $mostLikedMovies->implode(',') . ')') // Preserve the order of IDs
                ->get();
        }

        public function getEntertainmentViews($user, $profileId)
        {

            $viewedEntertainmentIds = EntertainmentView::where([
                ['user_id', '=', $user->id],
                ['profile_id', '=', $profileId]
            ])->pluck('entertainment_id')->toArray();


            $mostViewedMovies = EntertainmentView::whereNotIn('entertainment_id', $viewedEntertainmentIds)
                ->select('entertainment_id')
                ->groupBy('entertainment_id')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(10)
                ->pluck('entertainment_id');

            // OPTIMIZATION: Add eager loading to prevent N+1 queries
            $result = Entertainment::with([
                'plan:id,level',
                'genresdata:id,name'
            ])
            ->whereIn('id', $mostViewedMovies)
            ->where('type', 'movie')
            ->where('status',1);

            isset(request()->is_restricted) && $result = $result->where('is_restricted', request()->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $result = $result->where('is_restricted',0);

            return $result = $result
                ->orderByRaw('FIELD(id, ' . $mostViewedMovies->implode(',') . ')') // Preserve the order of IDs
                ->get();
        }

        public function getEntertainmentViewsV3($user, $profileId)
        {
            $result = Entertainment::with([
                'plan:id,level',
                'genresdata:id,name'
            ])
            ->select([
                'id',
                'name',
                'type',
                'release_date',
                'trailer_url_type',
                'is_restricted',
                'movie_access',
                'imdb_rating',
                'IMDb_rating',
                'video_upload_type',
                'poster_url',
                'thumbnail_url',
                'poster_tv_url',
                'trailer_url',
                'video_url_input',
                'plan_id',
            ])
            ->where('type', 'movie')
            ->where('deleted_at', null)
            ->where('status', 1)
            ->released()
            ->whereRaw('CAST(IMDb_rating AS DECIMAL(3,1)) >= ?', [5.0]); // Specific condition: Lower threshold (5.0) to match more content

            isset(request()->is_restricted) && $result = $result->where('is_restricted', request()->is_restricted);
            (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
                $result = $result->where('is_restricted', 0);

            return $result->orderByRaw('CAST(IMDb_rating AS DECIMAL(3,1)) DESC')
                ->orderBy('id', 'desc')
                ->get();
        }

    public function getUserWatchlist($user, $profileId)
    {
        $watchlist = Watchlist::where('user_id', $user->id)
                                ->where('profile_id', $profileId)
                                ->with('entertainment')
                                ->get();

        $watchlist= WatchlistResource::collection($watchlist);

        return $watchlist;
    }

    public function  getFavoriteGener($user, $profileId)
    {


        $entertainmentIds = $user->watchHistories()
        ->where('profile_id', $profileId)
        ->pluck('entertainment_id')
        ->merge(
            Like::where('profile_id', $profileId)
                ->where('user_id', $user->id)
                ->where('is_like', true)
                ->pluck('entertainment_id')
        )
        ->unique();


    $genreIds = EntertainmentGenerMapping::whereIn('entertainment_id', $entertainmentIds)
        ->distinct()
        ->pluck('genre_id');

        return Genres::whereIn('id', $genreIds)->where('status',1)->take(10)->inRandomOrder()->get();

}

public function  getFavoritePersonality($user, $profileId)
{
    $entertainmentIds = $user->watchHistories()
    ->where('profile_id', $profileId)
    ->pluck('entertainment_id')
    ->merge(
        Like::where('profile_id', $profileId)
            ->where('user_id', $user->id)
            ->where('is_like', true)
            ->pluck('entertainment_id')
    )
    ->unique();


   $talent_id = EntertainmentTalentMapping::whereIn('entertainment_id', $entertainmentIds)
    ->distinct()
    ->where('deleted_at', null)
    ->where('status', 1)
    ->pluck('talent_id');

return CastCrew::whereIn('id',$talent_id)->take(10)->inRandomOrder()->get();

}

public function getTrendingMoviesByCountry($user)
{
    $mobile = $user->mobile;

    $dialCode = null;


 if(!empty($mobile)) {

    try {

    $phoneUtil = PhoneNumberUtil::getInstance();

    $numberProto = $phoneUtil->parse($mobile, null);

    $dialCode = $numberProto->getCountryCode();

     }catch (\libphonenumber\NumberParseException $e) {
        // If region error occurs, set $dialCode to null
        $dialCode = null;
     }

    }
    $countryId = $dialCode ? Country::where('dial_code', $dialCode)->pluck('id')->toArray() : null;


    if (!$countryId) {
        return collect();
    }

    // OPTIMIZATION: Use join instead of whereHas for better performance
    $entertainmentIds = EntertainmentCountryMapping::whereIn('country_id', $countryId)
        ->pluck('entertainment_id')
        ->unique()
        ->toArray();
    
    if (empty($entertainmentIds)) {
        return collect();
    }
    
    // OPTIMIZATION: Add eager loading to prevent N+1 queries
    $result = Entertainment::with([
        'plan:id,level',
        'genresdata:id,name'
    ])
    ->withCount('entertainmentReviews')
    ->whereIn('id', $entertainmentIds)
    ->where('type', 'movie')
    ->where('status', 1)
    ->whereDate('release_date', '<=', Carbon::now());

    isset(request()->is_restricted) && $result = $result->where('is_restricted', request()->is_restricted);
    (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
        $result = $result->where('is_restricted',0);

    return $result = $result
        ->orderBy('entertainment_reviews_count', 'desc')
        ->take(10)
        ->get();
     }


     public function getTrendingTvShowByCountry($user)
{
    $mobile = $user->mobile;

    $dialCode = null;


 if(!empty($mobile)) {

    try {

    $phoneUtil = PhoneNumberUtil::getInstance();

    $numberProto = $phoneUtil->parse($mobile, null);

    $dialCode = $numberProto->getCountryCode();

     }catch (\libphonenumber\NumberParseException $e) {
        // If region error occurs, set $dialCode to null
        $dialCode = null;
     }

    }
    $countryId = $dialCode ? Country::where('dial_code', $dialCode)->pluck('id')->toArray() : null;


    if (!$countryId) {
        return collect();
    }
// dd($countryId);
   $result = Entertainment::whereHas('entertainmentCountryMappings', function ($query) use ($countryId) {
            $query->whereIn('country_id', $countryId);
        });
    isset(request()->is_restricted) && $result = $result->where('is_restricted', request()->is_restricted);
    (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
        $result = $result->where('is_restricted',0);

    $result = $result->withCount('entertainmentReviews')
        ->where('type', 'tvshow')
        ->where('status', 1)
        ->whereDate('release_date', '<=', Carbon::now())
        ->orderBy('entertainment_reviews_count', 'desc')
        ->take(10)
        ->get();

        return $result;
        // dd($test);
     }

 }
