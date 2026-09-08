<?php
    namespace App\Services;

    use App\Models\User;
    use Modules\Entertainment\Models\EntertainmentGenerMapping;
    use Modules\Entertainment\Models\Like;
    use Modules\Entertainment\Models\EntertainmentView;
    use Modules\World\Models\Country;
    use Modules\Genres\Models\Genres;
    use Modules\CastCrew\Models\CastCrew;
    use libphonenumber\PhoneNumberUtil;
    use Illuminate\Support\Facades\DB;

    use Modules\Entertainment\Models\EntertainmentCountryMapping;
    class RecommendationServiceV3
    {
        /**
         * Get the most recent watch history of a user
         *
         * @param User $user
         * @param int $profileId
         * @return mixed
         */

        public function recommendByLastHistory($user, $profileId)
        {
            if (!$user) {
                return [];
            }

           $recentlyWatched = $user->watchHistories()
            ->where('profile_id', $profileId)
            ->where('entertainment_type', 'movie')
            ->latest('id')
            ->with('entertainmentGenerMapping')
            ->first();

            if (!$recentlyWatched) {
                return [];
            }

            return EntertainmentGenerMapping::where('genre_id', $recentlyWatched->entertainmentGenerMapping->genre_id)
            ->pluck('entertainment_id')
            ->toArray();

        }

        public function getLikedMovies($user, $profileId)
        {

            if (!$user) {
                return [];
            }

            return Like::where('is_like', true)
                ->whereIn('entertainment_id', function($query) {
                    $query->select('id')->from('entertainments')->where('type', 'movie')->where('status', 1)->whereNull('deleted_at');
                })
                ->whereNotNull('entertainment_id')
                ->select('entertainment_id')
                ->groupBy('entertainment_id')
                ->orderByRaw('COUNT(*) DESC')
                ->pluck('entertainment_id');
        }

        public function getEntertainmentViews($user, $profileId)
        {
            return EntertainmentView::whereIn('entertainment_id', function($query) {
                    $query->select('id')->from('entertainments')
                        ->where('type', 'movie')
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->whereRaw('CAST(IMDb_rating AS DECIMAL(3,1)) >= ?', [5.0]);
                })
                ->select('entertainment_id')
                ->groupBy('entertainment_id')
                ->orderByRaw('COUNT(*) DESC')
                ->pluck('entertainment_id');
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

     $entertainmentIds = $countryId ? EntertainmentCountryMapping::whereIn('country_id', $countryId)->pluck('entertainment_id')->toArray() : [];

     return $entertainmentIds;
}


public function getFavoriteGener($user, $profileId)
{
    // Combine entertainment IDs from history and likes in a single collection
    $entertainmentIds = $user->watchHistories()
        ->where('profile_id', $profileId)
        ->pluck('entertainment_id')
        ->merge(
            Like::where('profile_id', $profileId)
                ->where('user_id', $user->id)
                ->where('is_like', true)
                ->pluck('entertainment_id')
        )
        ->unique()
        ->values();

    if ($entertainmentIds->isEmpty()) {
        return collect();
    }

    // Directly fetch genres with a single optimized query
    return Genres::select('genres.*')
        ->join('entertainment_gener_mapping', 'genres.id', '=', 'entertainment_gener_mapping.genre_id')
        ->whereIn('entertainment_gener_mapping.entertainment_id', $entertainmentIds)
        ->where('genres.status', 1)
        ->groupBy('genres.id')
        ->inRandomOrder()
        ->take(10)
        ->get();
}


public function getFavoritePersonality($user, $profileId)
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
        ->unique()
        ->values();

    if ($entertainmentIds->isEmpty()) {
        return collect();
    }

    return CastCrew::select('cast_crew.*')
        ->join('entertainment_talent_mapping', 'cast_crew.id', '=', 'entertainment_talent_mapping.talent_id')
        ->whereIn('entertainment_talent_mapping.entertainment_id', $entertainmentIds)
        ->groupBy('cast_crew.id')
        ->inRandomOrder()
        ->take(10)
        ->get();
}




}
