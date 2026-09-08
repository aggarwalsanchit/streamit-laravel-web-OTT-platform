<?php

namespace Modules\Entertainment\Models;

use App\Models\BaseModel;
use App\Models\Clip;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Modules\Subscriptions\Models\Plan;
use Modules\Season\Models\Season;
use Modules\Episode\Models\Episode;
use Modules\Genres\Models\Genres;
use Modules\Frontend\Models\PayPerView;
use Illuminate\Support\Facades\DB;

class Entertainment extends BaseModel
{

    use SoftDeletes;

    protected $table = 'entertainments';
    protected $genres;
    public function __construct()
    {
        $responseData = Cache::get('genres_v2');
        if(empty($responseData))
        {
            $responseData = Genres::get()->keyBy('id')->toArray();
            Cache::put('genres_v2', $responseData);
        }
        $this->genres = $responseData;
    }

    protected $fillable = [
    'name',
    'tmdb_id',
    'slug',
    'description',
    'trailer_url_type',
    'trailer_url',
    'poster_url',
    'thumbnail_url',
    'movie_access',
    'type', // movie,tv_show
    'plan_id',
    'status',
    'language',
    'IMDb_rating',
    'content_rating',
    'duration',
    'start_time', // Skip intro start time
    'end_time', // Skip intro end time
    'release_date',
    'is_restricted',
    'video_upload_type',
    'enable_quality',
    'video_url_input',
    'download_status',
    'download_type',
    'download_url',
    'enable_download_quality',
    'video_quality_url',
    'poster_tv_url',
    'price',
    'purchase_type',
    'access_duration',
    'discount',
    'available_for',
    'enable_subtitle',
    'meta_title', // Add SEO fields
    'meta_keywords', // Add SEO fields
    'meta_description', // Add SEO fields
    'seo_image', // Add SEO fields
    'google_site_verification', // Add SEO fields
    'canonical_url', // Add SEO fields
    'short_description', // Add SEO fields
    'enable_clips',
    'bunny_video_url',
    'bunny_trailer_url',
];

    protected $casts = [
        'release_date' => 'date',
    ];



    public function scopeReleased($query)
      {
          return $query->where(function ($q) {
              $q->whereDate('release_date', '<=', now())
                ->orWhereNull('release_date');
          });
      }



    public function getGenresAttribute($value)
    {
        return !empty($value) ? self::genres($value) : NULL;
    }

    public function getBaseUrlAttribute($value)
    {
        return !empty($value) ? setBaseUrlWithFileNameV2() : NULL;
    }



    private function genres($value)
    {
        $result = [];

        if (is_array($value)) {
            foreach ($value as $v) {
                if (isset($this->genres[$v])) {
                    $result[] = $this->genres[$v];
                }
            }
        }

        return $result;
    }

    private static function normalizeIds($ids): array
    {
        if ($ids instanceof \Illuminate\Support\Collection) {
            $ids = $ids->toArray();
        }

        $ids = is_array($ids) ? $ids : (array) $ids;
        return array_values(array_filter($ids, static fn ($id) => $id !== null && $id !== ''));
    }




    public function entertainmentGenerMappings()
    {
        return $this->hasMany(EntertainmentGenerMapping::class,'entertainment_id','id')->with('genre');
    }

    public function genresdata()
    {
        return $this->belongsToMany(
            Genres::class,
            'entertainment_gener_mapping',
            'entertainment_id',
            'genre_id'
        );
    }
    public function entertainmentCountryMappings()
    {
        return $this->hasMany(EntertainmentCountryMapping::class,'entertainment_id','id')->with('country');
    }

    public function entertainmentStreamContentMappings()
    {
        return $this->hasMany(EntertainmentStreamContentMapping::class,'entertainment_id','id');
    }

    public function entertainmentDownloadMappings()
    {
        return $this->hasMany(EntertainmnetDownloadMapping::class,'entertainment_id','id');
    }


    public function EntertainmentDownload()
    {
        return $this->hasMany(EntertainmentDownload::class,'entertainment_id','id');
    }

    // Alias: supports withCount('downloads') in queries
    public function downloads()
    {
        return $this->hasMany(EntertainmentDownload::class, 'entertainment_id', 'id');
    }


    public function entertainmentTalentMappings()
    {
        return $this->hasMany(EntertainmentTalentMapping::class,'entertainment_id','id')->with('talentprofile');
    }

    public function plan()
    {
        return $this->hasOne(Plan::class, 'id', 'plan_id');
    }

    public function entertainmentReviews()
    {
        return $this->hasMany(Review::class,'entertainment_id','id');
    }

    // Alias: supports with('reviews') and 'reviews.user'
    public function reviews()
    {
        return $this->hasMany(Review::class, 'entertainment_id', 'id');
    }

    public function entertainmentLike()
    {
        return $this->hasMany(Like::class,'entertainment_id','id');
    }

    // Alias: supports withCount('likes') in queries
    public function likes()
    {
        return $this->hasMany(Like::class, 'entertainment_id', 'id');
    }

    public function entertainmentView()
    {
        return $this->hasMany(EntertainmentView::class, 'entertainment_id', 'id');
    }

    public function UserReminder()
    {
        return $this->hasMany(UserReminder::class,'entertainment_id','id');
    }

    public function UserRemind()
    {
        return $this->hasOne(UserReminder::class,'entertainment_id','id');
    }

    public function Watchlist()
    {
        return $this->hasMany(Watchlist::class,'entertainment_id','id');
    }


    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug on creating
        static::creating(function ($entertainment) {
            if (empty($entertainment->slug) && !empty($entertainment->name)) {
                $entertainment->slug = \Illuminate\Support\Str::slug(trim($entertainment->name));
            }
        });

        // Clear dashboard cache when entertainment is updated (status change, etc.)
        static::updated(function ($entertainment) {
            // Clear dashboard cache when status or deleted_at changes
            if ($entertainment->isDirty('status') || $entertainment->isDirty('deleted_at')) {
                if (function_exists('clearDashboardCache')) {
                    clearDashboardCache();
                }
            }
        });

        static::deleting(function ($entertainment) {
            // Clear dashboard cache when entertainment is soft deleted
            if (function_exists('clearDashboardCache')) {
                clearDashboardCache();
            }

            if ($entertainment->isForceDeleting()) {

                $entertainment->entertainmentGenerMappings()->forceDelete();
                $entertainment->entertainmentStreamContentMappings()->forceDelete();
                $entertainment->entertainmentTalentMappings()->forceDelete();
                $entertainment->entertainmentReviews()->forceDelete();
                $entertainment->entertainmentDownloadMappings()->forceDelete();
                $entertainment->EntertainmentDownload()->forceDelete();
                $entertainment->entertainmentLike()->forceDelete();
                $entertainment->UserReminder()->forceDelete();
                $entertainment->Watchlist()->forceDelete();


            } else {

                $entertainment->entertainmentGenerMappings()->delete();
                $entertainment->entertainmentStreamContentMappings()->delete();
                $entertainment->entertainmentTalentMappings()->delete();
                $entertainment->entertainmentReviews()->delete();
                $entertainment->entertainmentDownloadMappings()->delete();
                $entertainment->EntertainmentDownload()->delete();
                $entertainment->entertainmentLike()->delete();
                $entertainment->UserReminder()->delete();
                $entertainment->Watchlist()->delete();

            }

        });

        static::restoring(function ($entertainment) {

            $entertainment->entertainmentGenerMappings()->withTrashed()->restore();
            $entertainment->entertainmentStreamContentMappings()->withTrashed()->restore();
            $entertainment->entertainmentTalentMappings()->withTrashed()->restore();
            $entertainment->entertainmentReviews()->withTrashed()->restore();
            $entertainment->entertainmentDownloadMappings()->withTrashed()->restore();
            $entertainment->EntertainmentDownload()->withTrashed()->restore();
            $entertainment->entertainmentLike()->withTrashed()->restore();
            $entertainment->UserReminder()->withTrashed()->restore();
            $entertainment->Watchlist()->withTrashed()->restore();
        });

        // Clear dashboard cache when entertainment is restored
        static::restored(function ($entertainment) {
            if (function_exists('clearDashboardCache')) {
                clearDashboardCache();
            }
        });
    }

    public function season()
    {
        return $this->hasMany(Season::class, 'entertainment_id')->orderBy('season_index', 'asc')->orderBy('id', 'asc')->with('plan', 'episodes');
    }

    public function episodeV2()
    {
        return $this->hasMany(Episode::class,'entertainment_id');
    }


    public function episode()
    {
        return $this->hasMany(Episode::class,'entertainment_id')->with('plan','EpisodeStreamContentMapping');
    }

 public static function get_latest_movie($latestMovieIdsArray)
    {
        $latestMovieIdsArray = self::normalizeIds($latestMovieIdsArray);
        if (count($latestMovieIdsArray) === 0) {
            return collect();
        }

        $query = Entertainment::select([
            'id','name','slug','type','release_date','trailer_url','plan_id','description',
            'trailer_url_type','is_restricted','language','imdb_rating','content_rating',
            'duration','video_upload_type','poster_url','thumbnail_url','poster_tv_url',
            'video_url_input','movie_access','price','purchase_type','access_duration',
            'discount','available_for'
        ])
        ->with([
            'plan:id,level',
            'genresdata:id,name'
        ])
        ->whereIn('id', $latestMovieIdsArray)
        ->where('status', 1)
        ->where('deleted_at', null)
        ->whereDate('release_date', '<=', Carbon::now());

        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }
        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        $items = $query->latest('id')->get();

        $userId = loggedUserId();
        $profileId = getRequestedProfileId();
        $ids = $items->pluck('id');

        $watchedTimes = \DB::table('continue_watch')
            ->whereIn('entertainment_id', $ids)
            ->where('profile_id', $profileId)
            ->where('user_id', $userId)
            ->pluck('watched_time', 'entertainment_id');

        $watchlisted = \DB::table('watchlists')
            ->whereIn('entertainment_id', $ids)
            ->where('user_id', $userId)
            ->pluck('entertainment_id')
            ->flip();

        return $items->map(function ($item) use ($watchedTimes, $watchlisted) {
            $item->plan_level = optional($item->plan)->level;
            $item->base_url = $item->trailer_url;
            $item->watched_time = $watchedTimes[$item->id] ?? null;
            $item->is_watch_list = $watchlisted->has($item->id) ? 1 : 0;
            return $item;
        });
    }

    public static function get_latest_movieV3($latestMovieIdsArray)
    {
        $latestMovieIdsArray = self::normalizeIds($latestMovieIdsArray);
        if (count($latestMovieIdsArray) === 0) {
            return collect();
        }

        $query = Entertainment::select([
            'id','name','type','release_date','trailer_url',
            'trailer_url_type','is_restricted','imdb_rating','poster_url','thumbnail_url','poster_tv_url','movie_access','plan_id'
        ])
        ->with([
            'plan:id,level',
            'genresdata:id,name'
        ])
        ->whereIn('id', $latestMovieIdsArray)
        ->where('status', 1)
        ->where('deleted_at', null)
        ->whereDate('release_date', '<=', Carbon::now());

        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }
        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        $items = $query->latest('id')->get();

        $userId = loggedUserId();
        $profileId = getRequestedProfileId();
        $ids = $items->pluck('id');

        $watchedTimes = \DB::table('continue_watch')
            ->select('entertainment_id','watched_time')
            ->whereIn('entertainment_id', $ids)
            ->where('profile_id', $profileId)
            ->where('user_id', $userId)
            ->pluck('watched_time', 'entertainment_id');

        $watchlisted = \DB::table('watchlists')
            ->select('entertainment_id')
            ->whereIn('entertainment_id', $ids)
            ->where('user_id', $userId)
            ->pluck('entertainment_id')
            ->flip();

        return $items->map(function ($item) use ($watchedTimes, $watchlisted) {
            $item->plan_level = optional($item->plan)->level;
            $item->base_url = $item->trailer_url;
            $item->watched_time = $watchedTimes[$item->id] ?? null;
            $item->is_watch_list = $watchlisted->has($item->id) ? 1 : 0;
            return $item;
        });
    }

    public static function get_pay_per_view_movie()
    {
        if (isenablemodule('movie') != 1) {
            return collect();
        }

        $query = Entertainment::with([
            'genresdata:id,name',
            'plan:id,level'
        ])
        ->select([
            'id',
            'name',
            'slug',
            'type',
            'release_date',
            'trailer_url',
            'plan_id',
            'description',
            'trailer_url_type',
            'is_restricted',
            'language',
            'imdb_rating',
            'content_rating',
            'duration',
            'video_upload_type',
            'poster_url',
            'thumbnail_url',
            'poster_tv_url',
            'trailer_url as base_url',
            'video_url_input',
            'movie_access',
            'price',
            'purchase_type',
            'access_duration',
            'discount',
            'available_for',
        ])
        ->withCount(['watchlist as is_watch_list' => function ($q) {
            $q->where('user_id', loggedUserId());
        }])
        ->addSelect([
            'watched_time' => ContinueWatch::select('watched_time')
                ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
                ->where('profile_id', getRequestedProfileId())
                ->where('user_id', loggedUserId())
                ->limit(1)
        ])
        ->where('movie_access', 'pay-per-view')
        ->where('status', 1)
        ->where('deleted_at', null)
        ->whereDate('release_date', '<=', now());

        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        return $query->latest('id')->get();
    }




    public static function get_popular_movie($popularMovieIdsArray)
    {
        $popularMovieIdsArray = self::normalizeIds($popularMovieIdsArray);
        if (count($popularMovieIdsArray) === 0) {
            return collect();
        }

        $query = Entertainment::with([
            'genresdata:id,name',
            'plan:id,level'
        ])
        ->select([
            'id',
            'name',
            'slug',
            'type',
            'release_date',
            'plan_id',
            'description',
            'trailer_url_type',
            'is_restricted',
            'language',
            'imdb_rating',
            'content_rating',
            'duration',
            'video_upload_type',
            'poster_url',
            'thumbnail_url',
            'poster_tv_url',
            'trailer_url as base_url',
            'trailer_url',
            'video_url_input',
            'movie_access',
            'price',
            'purchase_type',
            'access_duration',
            'discount',
            'available_for',
        ])
        ->addSelect([
            'watched_time' => ContinueWatch::select('watched_time')
                ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
                ->where('profile_id', getRequestedProfileId())
                ->where('user_id', loggedUserId())
                ->limit(1)
        ])
        ->withCount(['watchlist as is_watch_list' => function ($q) {
            $q->where('user_id', loggedUserId());
        }])
        ->whereIn('id', $popularMovieIdsArray)
        ->where('status', 1)
        ->where('deleted_at', null)
        ->whereDate('release_date', '<=', now());

        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        return $query->latest('id')->get();
    }

    public static function get_popular_movieV3($popularMovieIdsArray)
    {
        $popularMovieIdsArray = self::normalizeIds($popularMovieIdsArray);
        if (count($popularMovieIdsArray) === 0) {
            return collect();
        }

        $query = Entertainment::with([
            'genresdata:id,name',
            'plan:id,level'
        ])
        ->select([
            'id',
            'name',
            'type',
            'release_date',
            'trailer_url',
            'trailer_url_type',
            'is_restricted',
            'imdb_rating',
            'poster_url',
            'thumbnail_url',
            'poster_tv_url',
            'trailer_url',
            'movie_access',           
        ])
        ->addSelect([
            'watched_time' => ContinueWatch::select('watched_time')
                ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
                ->where('profile_id', getRequestedProfileId())
                ->where('user_id', loggedUserId())
                ->limit(1)
        ])
        ->withCount(['watchlist as is_watch_list' => function ($q) {
            $q->where('user_id', loggedUserId());
        }])
        ->whereIn('id', $popularMovieIdsArray)
        ->where('status', 1)
        ->where('deleted_at', null)
        ->whereDate('release_date', '<=', now());

        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        return $query->latest('id')->get();
    }


    public static function get_free_movie($movieIdsArray)
    {
        $movieIdsArray = self::normalizeIds($movieIdsArray);
        if (count($movieIdsArray) === 0) {
            return collect();
        }

        $query = Entertainment::with([
            'genresdata:id,name',
            'plan:id,level'
        ])
        ->select([
            'id',
            'name',
            'slug',
            'type',
            'release_date',
            'plan_id',
            'description',
            'trailer_url_type',
            'is_restricted',
            'language',
            'imdb_rating',
            'content_rating',
            'duration',
            'video_upload_type',
            'poster_url',
            'thumbnail_url',
            'poster_tv_url',
            'trailer_url as base_url',
            'trailer_url',
            'video_url_input',
            'movie_access',
        ])
        ->addSelect([
            'watched_time' => ContinueWatch::select('watched_time')
                ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
                ->where('profile_id', getRequestedProfileId())
                ->where('user_id', loggedUserId())
                ->limit(1)
        ])
        ->withCount(['watchlist as is_watch_list' => function ($q) {
            $q->where('user_id', loggedUserId());
        }])
        ->whereIn('id', $movieIdsArray)
        ->where('status', 1)
        ->where('deleted_at', null)
        ->where('movie_access', 'free')
        ->whereDate('release_date', '<=', now());

        // Restriction filters
        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        if (!empty($movieIdsArray)) {
            $idsString = implode(',', array_map('intval', (array)$movieIdsArray));
            if (!empty($idsString)) {
                $query->orderByRaw("FIELD(id, $idsString)");
            }
        } else {
            $query->latest('id');
        }

        return $query->get();
    }

    public static function get_free_movieV3($movieIdsArray)
    {
        $movieIdsArray = self::normalizeIds($movieIdsArray);
        if (count($movieIdsArray) === 0) {
            return collect();
        }

        $query = Entertainment::with([
            'genresdata:id,name',
            'plan:id,level'
        ])
        ->select([
            'id',
            'name',
            'type',
            'release_date',
            'trailer_url',
            'trailer_url_type',
            'is_restricted',
            'imdb_rating',
            'poster_url',
            'thumbnail_url',
            'poster_tv_url',
            'video_url_input',
            'movie_access',
        ])
        ->addSelect([
            'watched_time' => ContinueWatch::select('watched_time')
                ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
                ->where('profile_id', getRequestedProfileId())
                ->where('user_id', loggedUserId())
                ->limit(1)
        ])
        ->withCount(['watchlist as is_watch_list' => function ($q) {
            $q->where('user_id', loggedUserId());
        }])
        ->whereIn('id', $movieIdsArray)
        ->where('status', 1)
        ->where('deleted_at', null)
        ->where('movie_access', 'free')
        ->whereDate('release_date', '<=', now());

        // Restriction filters
        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        if (!empty($movieIdsArray)) {
            $idsString = implode(',', array_map('intval', (array)$movieIdsArray));
            if (!empty($idsString)) {
                $query->orderByRaw("FIELD(id, $idsString)");
            }
        } else {
            $query->latest('id');
        }

        return $query->get();
    }


    public static function get_popular_tvshow($popular_tvshowIdsArray)
    {
        $popular_tvshowIdsArray = self::normalizeIds($popular_tvshowIdsArray);
        if (count($popular_tvshowIdsArray) === 0) {
            return collect();
        }

        $query = Entertainment::with([
            'genresdata:id,name',
            'plan:id,level'
        ])
        ->select([
            'id',
            'name',
            'slug',
            'type',
            'release_date',
            'plan_id',
            'description',
            'trailer_url_type',
            'is_restricted',
            'language',
            'IMDb_rating',
            'content_rating',
            'duration',
            'video_upload_type',
            'poster_url',
            'thumbnail_url',
            'poster_tv_url',
            'trailer_url as base_url',
            'trailer_url',
            'video_url_input',
            'movie_access',
            'price',
            'purchase_type',
            'access_duration',
            'discount',
            'available_for',
        ])
        ->addSelect([
            'watched_time' => ContinueWatch::select('watched_time')
                ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
                ->where('profile_id', getRequestedProfileId())
                ->where('user_id', loggedUserId())
                ->limit(1)
        ])
        ->withCount(['watchlist as is_watch_list' => function ($q) {
            $q->where('user_id', loggedUserId());
        }])
        ->whereIn('id', $popular_tvshowIdsArray)
        ->where('status', 1)
        ->where('deleted_at', null);

        // Restriction filters
        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        return $query->latest('id')->get();
    }

    public static function get_popular_tvshowV3($popular_tvshowIdsArray)
    {
        $popular_tvshowIdsArray = self::normalizeIds($popular_tvshowIdsArray);
        if (count($popular_tvshowIdsArray) === 0) {
            return collect();
        }

        $query = Entertainment::with([
            'genresdata:id,name',
            'plan:id,level'
        ])
        ->select([
            'id',
            'name',
            'type',
            'release_date',
            'trailer_url',
            'trailer_url_type',
            'is_restricted',
            'IMDb_rating',
            'poster_url',
            'thumbnail_url',
            'poster_tv_url',
            'video_url_input',
            'movie_access',           
        ])
        ->addSelect([
            'watched_time' => ContinueWatch::select('watched_time')
                ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
                ->where('profile_id', getRequestedProfileId())
                ->where('user_id', loggedUserId())
                ->limit(1)
        ])
        ->withCount(['watchlist as is_watch_list' => function ($q) {
            $q->where('user_id', loggedUserId());
        }])
        ->whereIn('id', $popular_tvshowIdsArray)
        ->where('status', 1)
        ->where('deleted_at', null);

        // Restriction filters
        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        return $query->latest('id')->get();
    }

    public static function get_entertainment_list()
{
    $query = Entertainment::with([
        'genresdata:id,name',
        'plan:id,level',
        'entertainmentReviews' => function ($q) {
            $q->whereBetween('rating', [4, 5])->take(6);
        }
    ])
    ->select([
        'id',
        'name',
        'slug',
        'type',
        'release_date',
        'plan_id',
        'description',
        'trailer_url_type',
        'is_restricted',
        'language',
        'imdb_rating',
        'content_rating',
        'duration',
        'video_upload_type',
        'poster_url',
        'thumbnail_url',
        'poster_tv_url',
        'trailer_url as base_url',
        'trailer_url',
        'video_url_input',
        'movie_access',
        'price',
        'purchase_type',
        'access_duration',
        'discount',
        'available_for',
    ])
    ->addSelect([
        'watched_time' => ContinueWatch::select('watched_time')
            ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
            ->where('profile_id', getRequestedProfileId())
            ->where('user_id', loggedUserId())
            ->limit(1)
    ])
    ->withCount(['watchlist as is_watch_list' => function ($q) {
        $q->where('user_id', loggedUserId());
    }])
    ->where('status', 1)
    ->where('deleted_at', null)
    ->where('type', 'movie')
    ->whereDate('release_date', '<=', now());

    // Restriction filters
    if (request()->has('is_restricted')) {
        $query->where('is_restricted', request()->is_restricted);
    }

    if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
        $query->where('is_restricted', 0);
    }

    return $query->latest('id')->get();
}

public static function get_entertainment_listV3()
{
    $query = Entertainment::with([
        'genresdata:id,name',
        'plan:id,level',
        'entertainmentReviews' => function ($q) {
            $q->whereBetween('rating', [4, 5])->take(6);
        }
    ])
    ->select([
        'id',
        'name',
        'slug',
        'type',
        'release_date',
        'trailer_url_type',
        'is_restricted',
        'imdb_rating',
        'poster_url',
        'thumbnail_url',
        'poster_tv_url',
        'trailer_url',
        'video_url_input',
        'movie_access',       
    ])
    ->addSelect([
        'watched_time' => ContinueWatch::select('watched_time')
            ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
            ->where('profile_id', getRequestedProfileId())
            ->where('user_id', loggedUserId())
            ->limit(1)
    ])
    ->withCount(['watchlist as is_watch_list' => function ($q) {
        $q->where('user_id', loggedUserId());
    }])
    ->where('status', 1)
    ->where('deleted_at', null)
    ->where('type', 'movie')
    ->whereDate('release_date', '<=', now());

    // Restriction filters
    if (request()->has('is_restricted')) {
        $query->where('is_restricted', request()->is_restricted);
    }

    if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
        $query->where('is_restricted', 0);
    }

    return $query->latest('id')->simplePaginate(request('per_page', 10));
}


public static function get_top_movie($topMovieIds)
{
    $query = Entertainment::with([
        'genresdata:id,name',
        'plan:id,level'
    ])
    ->select([
        'id',
        'name',
        'slug',
        'type',
        'release_date',
        'plan_id',
        'description',
        'trailer_url_type',
        'is_restricted',
        'language',
        'movie_access',
        'price',
        'purchase_type',
        'access_duration',
        'discount',
        'available_for',
        'imdb_rating',
        'content_rating',
        'duration',
        'video_upload_type',
        'poster_url',
        'thumbnail_url',
        'poster_tv_url',
        'trailer_url as base_url',
        'trailer_url',
        'video_url_input',
    ])
    ->addSelect([
        'watched_time' => ContinueWatch::select('watched_time')
            ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
            ->where('profile_id', getRequestedProfileId())
            ->where('user_id', loggedUserId())
            ->limit(1)
    ])
    ->withCount(['watchlist as is_watch_list' => function ($q) {
        $q->where('user_id', loggedUserId());
    }])
    ->whereIn('id', $topMovieIds)
    ->where('status', 1)
    ->where('deleted_at', null)
    ->whereDate('release_date', '<=', now());

    $isMovieModuleEnabled = isenablemodule('movie') == 1;
    $isTVShowModuleEnabled = isenablemodule('tvshow') == 1;

    if ($isMovieModuleEnabled && $isTVShowModuleEnabled) {
        $query->whereIn('type', ['movie', 'tvshow']);
    } elseif ($isMovieModuleEnabled) {
        $query->where('type', 'movie');
    } elseif ($isTVShowModuleEnabled) {
        $query->where('type', 'tvshow');
    }

    // Restriction filters
    if (request()->has('is_restricted')) {
        $query->where('is_restricted', request()->is_restricted);
    }

    if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
        $query->where('is_restricted', 0);
    }

    return $query->latest('id')->get();
}

public static function get_top_movie_v3($topMovieIds)
{
    $query = Entertainment::with([
        'genresdata:id,name',
        'plan:id,level'
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
    ->addSelect([
        'watched_time' => ContinueWatch::select('watched_time')
            ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
            ->where('profile_id', getRequestedProfileId())
            ->where('user_id', loggedUserId())
            ->limit(1)
    ])
    ->withCount(['watchlist as is_watch_list' => function ($q) {
        $q->where('user_id', loggedUserId());
    }])
    ->whereIn('id', $topMovieIds)
    ->where('status', 1)
    ->where('deleted_at', null)
    ->whereDate('release_date', '<=', now());

    $isMovieModuleEnabled = isenablemodule('movie') == 1;
    $isTVShowModuleEnabled = isenablemodule('tvshow') == 1;

    if ($isMovieModuleEnabled && $isTVShowModuleEnabled) {
        $query->whereIn('type', ['movie', 'tvshow']);
    } elseif ($isMovieModuleEnabled) {
        $query->where('type', 'movie');
    } elseif ($isTVShowModuleEnabled) {
        $query->where('type', 'tvshow');
    }

    // Restriction filters
    if (request()->has('is_restricted')) {
        $query->where('is_restricted', request()->is_restricted);
    }

    if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
        $query->where('is_restricted', 0);
    }

    return $query->latest('id')->get();
}

public static function get_more_items($episodeId, $genre_ids)
{
    $query = Entertainment::with([
        'genresdata:id,name',
        'plan:id,level'
    ])
    ->select([
        'id',
        'name',
        'slug',
        'type',
        'plan_id',
        'description',
        'trailer_url_type',
        'is_restricted',
        'language',
        'imdb_rating',
        'content_rating',
        'duration',
        'video_upload_type',
        'poster_url',
        'thumbnail_url',
        'poster_tv_url',
        'trailer_url as base_url',
        'trailer_url',
        'video_url_input',
        'movie_access',
    ])
    ->addSelect([
        'watched_time' => ContinueWatch::select('watched_time')
            ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
            ->where('profile_id', getRequestedProfileId())
            ->where('user_id', loggedUserId())
            ->limit(1)
    ])
    ->withCount(['watchlist as is_watch_list' => function ($q) {
        $q->where('user_id', loggedUserId());
    }])
    ->where('type', 'tvshow')
    ->where('id', '!=', $episodeId)
    ->whereHas('entertainmentGenerMappings', function ($q) use ($genre_ids) {
        $q->whereIn('genre_id', $genre_ids);
    });


    // Restriction filters
    if (request()->has('is_restricted')) {
        $query->where('is_restricted', request()->is_restricted);
    }

    if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
        $query->where('is_restricted', 0);
    }

    return $query->latest('id')->get();
}

public static function get_first_tvshow($tvshow_id, $user_id, $profile_id)
{
    $query = Entertainment::with([
        'genresdata:id,name',
        'plan:id,level'
    ])
    ->select([
        'id',
        'name',
        'slug',
        'description',
        'type',
        'trailer_url_type',
        'plan_id',
        'movie_access',
        'price',
        'purchase_type',
        'access_duration',
        'discount',
        'available_for',
        'language',
        'imdb_rating',
        'content_rating',
        'duration',
        'release_date',
        'is_restricted',
        'video_upload_type',
        'video_url_input',
        'enable_quality',
        'download_url',
        'poster_url as poster_image',
        'thumbnail_url as thumbnail_image',
        'trailer_url as base_url',
        'trailer_url',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ])
    ->withCount([
        'watchlist as is_watch_list' => function ($q) use ($user_id, $profile_id, $tvshow_id) {
            $q->where('user_id', $user_id)
              ->where('profile_id', $profile_id)
              ->where('entertainment_id', $tvshow_id);
        },
        'likes as is_likes' => function ($q) use ($user_id, $profile_id, $tvshow_id) {
            $q->where('user_id', $user_id)
              ->where('profile_id', $profile_id)
              ->where('entertainment_id', $tvshow_id)
              ->where('is_like', 1);
        }
    ])
    ->where('id', $tvshow_id);

    // Restriction filters
    if (request()->has('is_restricted')) {
        $query->where('is_restricted', request()->is_restricted);
    }

    if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
        $query->where('is_restricted', 0);
    }

    return $query->first();
}


public static function get_recommended_movie($movieIdsArray)
{
    $movieIdsArray = self::normalizeIds($movieIdsArray);
    if (count($movieIdsArray) === 0) {
        return collect();
    }

    $query = Entertainment::with([
        'genresdata:id,name',
        'plan:id,level'
    ])
    ->select([
        'id',
        'name',
        'description',
        'type',
        'slug',
        'trailer_url_type',
        'trailer_url',
        'movie_access',
        'imdb_rating',
        'plan_id',
        'language',
        'duration',
        'poster_url',
        'poster_tv_url',
    ])
    ->withCount([
        'watchlist as is_watch_list' => function ($q) {
            $q->where('user_id', loggedUserId());
        }
    ])
    ->whereIn('id', $movieIdsArray)
    ->where('type', 'movie')
    ->where('status', 1)
    ->where('deleted_at', null)
    ->released();

    // Restriction filters
    if (request()->has('is_restricted')) {
        $query->where('is_restricted', request()->is_restricted);
    }

    if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
        $query->where('is_restricted', 0);
    }

    if (!empty($movieIdsArray)) {
        if ($movieIdsArray instanceof \Illuminate\Support\Collection) {
            $ids = $movieIdsArray->map(function ($item) {
                return is_object($item) ? ($item->id ?? $item) : $item;
            })->toArray();
        } elseif (is_array($movieIdsArray)) {
            $ids = $movieIdsArray;
        } else {
            $ids = (array) $movieIdsArray;
        }
        
        $ids = array_filter($ids);
        $idsString = implode(',', array_map('intval', $ids));
        if (!empty($idsString)) {
            $query->orderByRaw("FIELD(id, $idsString)");
        }
    } else {
        $query->latest('id');
    }

    return $query->get();
}



public static function get_coming_soon_movie()
{
    $query = Entertainment::with([
                'genresdata:id,name',
                'plan:id,level',
        'watchlist' => function ($q) {
            if (loggedUserId()) {
                $q->where('user_id', loggedUserId());
            }
        }
    ])
    ->select([
        'id',
        'name',
        'slug',
        'description',
        'type',
        'trailer_url_type',
        'trailer_url',
        'movie_access',
        'imdb_rating',
        'plan_id',
        'language',
        'duration',
        'thumbnail_url',
        'release_date',
        'is_restricted',
    ])
    ->addSelect(['plan_level' => DB::raw('COALESCE(plan.level, 0)')])
    ->leftJoin('plan', 'plan.id', '=', 'entertainments.plan_id')
    ->where('status', 1)
    ->whereDate('release_date', '>', now())
    ->when(request()->has('is_restricted'), function ($q) {
        $q->where('is_restricted', request()->is_restricted);
    })
    ->when(!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0, function ($q) {
        $q->where('is_restricted', 0);
    })
    ->groupBy('entertainments.id')
    ->orderByDesc('release_date')
    ->get();

    return $query;
}
public static function get_movie($movieId, $user_id, $profile_id, $device_id)
{
    $query = Entertainment::select([
        'entertainments.id', 'entertainments.name', 'entertainments.slug', 'entertainments.type', 'entertainments.release_date',
        'entertainments.trailer_url_type', 'entertainments.is_restricted', 'entertainments.IMDb_rating',
        'entertainments.poster_url', 'entertainments.thumbnail_url', 'entertainments.poster_tv_url',
        'entertainments.trailer_url', 'entertainments.video_upload_type', 'entertainments.video_url_input', 'entertainments.movie_access',
        'entertainments.language', 'entertainments.duration', 'entertainments.start_time', 'entertainments.end_time', 'entertainments.content_rating',
        'entertainments.download_url', 'entertainments.description', 'entertainments.download_type', 'entertainments.enable_quality',
        'entertainments.video_quality_url', 'entertainments.download_status', 'entertainments.enable_download_quality',
        'entertainments.status', 'entertainments.enable_subtitle', 'entertainments.subtitle_language', 'entertainments.is_default_subtitle',
        'entertainments.price', 'entertainments.purchase_type', 'entertainments.access_duration', 'entertainments.discount', 'entertainments.available_for',
        'entertainments.plan_id', 'entertainments.tmdb_id', 'entertainments.bunny_trailer_url', 'entertainments.bunny_video_url',
        'entertainments.enable_clips'
    ])
    ->with([
        'genresdata:id,name',
        'plan:id,level',
        'entertainmentTalentMappings.talentprofile:id,name,type,file_url,bio,dob,place_of_birth,status,deleted_at',
        'entertainmentGenerMappings.genre:id,name,status',
        'season' => function($q) {
            $q->where('status', 1)
              ->whereNull('deleted_at')
              ->withCount('episodes');
        },
        'subtitles' => function($q) use ($movieId) {
            $q->where('type', 'movie')
              ->where('entertainment_id', $movieId);
        },
        'reviews' => function($q) use ($user_id) {
            $q->where('user_id', $user_id)
              ->whereNull('deleted_at')
              ->select(['id', 'entertainment_id', 'user_id', 'review', 'rating', 'created_at', 'updated_at']);
        },
        'reviews.user:id,first_name,last_name,file_url',
        'entertainmentReviews' => function($q) {
            $q->whereNull('deleted_at')
              ->with('user:id,first_name,last_name,file_url')
              ->orderBy('created_at', 'desc')
              ->limit(10);
        }
    ])
    ->addSelect([
        'watched_time' => DB::table('continue_watch')
            ->select('watched_time')
            ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
            ->where('profile_id', $profile_id)
            ->where('user_id', $user_id)
            ->limit(1)
    ])
    ->withCount([
        'watchlist as is_watch_list' => function($q) use ($user_id, $profile_id, $movieId) {
            $q->where('user_id', $user_id)
              ->where('profile_id', $profile_id)
              ->where('entertainment_id', $movieId);
        },
        'likes as is_likes' => function($q) use ($user_id, $profile_id, $movieId) {
            $q->where('user_id', $user_id)
              ->where('profile_id', $profile_id)
              ->where('entertainment_id', $movieId)
              ->where('is_like', 1);
        },
        'downloads as is_download' => function($q) use ($user_id, $device_id, $movieId) {
            $q->where('user_id', $user_id)
              ->where('device_id', $device_id)
              ->where('entertainment_type', 'movie')
              ->where('entertainment_id', $movieId)
              ->where('is_download', 1);
        }
    ])
    ->where('id', $movieId)
    ->where('entertainments.type', 'movie');

        isset(request()->is_restricted) && $query = $query->where('is_restricted', request()->is_restricted);
        (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) &&
            $query = $query->where('is_restricted',0);

        return $query;
    }

    public static function get_tvshow($movieId,$user_id,$profile_id,$device_id)
    {
        $builder = Entertainment::selectRaw('entertainments.id,entertainments.slug, entertainments.video_url_input, entertainments.video_upload_type, entertainments.type, entertainments.thumbnail_url, entertainments.poster_url as poster_url,entertainments.poster_tv_url as poster_tv_url, entertainments.trailer_url_type, entertainments.trailer_url, entertainments.plan_id, entertainments.name,entertainments.language, entertainments.content_rating,  entertainments.duration, entertainments.release_date, entertainments.IMDb_rating, entertainments.description, entertainments.enable_quality,entertainments.download_status,entertainments.download_type,entertainments.download_url,entertainments.enable_download_quality,entertainments.movie_access,entertainments.price,entertainments.purchase_type,entertainments.access_duration,entertainments.discount,entertainments.available_for,entertainments.status, entertainments.is_restricted, entertainments.start_time,entertainments.end_time,entertainments.enable_subtitle, (select watched_time from  continue_watch where continue_watch.entertainment_id = entertainments.id and profile_id = '.getRequestedProfileId().' AND user_id = '.loggedUserId().' LIMIT 1) as watched_time,  (CASE WHEN (select id from `watchlists` where `entertainment_id` = '.$movieId.' and `user_id` = '.$user_id.' and `profile_id` = '.$profile_id.' and `watchlists`.`deleted_at` is null LIMIT 1) THEN 1 ELSE 0 END) AS is_watch_list,(CASE WHEN EXISTS (select id from `likes` where `entertainment_id` = '.(int)$movieId.' and `user_id` = '.(int)$user_id.' and `profile_id` = '.(int) $profile_id.' and is_like = 1  and `likes`.`deleted_at` is null LIMIT 1) THEN 1 ELSE 0 END) AS is_likes,(CASE WHEN (select id from `entertainment_downloads` where `entertainment_id` = '.$movieId.' and `device_id` = "'.$device_id.'" and `user_id` = '.$user_id.' and entertainment_type = "movie" and is_download = 1  and entertainment_downloads.`deleted_at` is null LIMIT 1) THEN 1 ELSE 0 END) AS is_download,reviews.id as your_review_id,reviews.review as your_review,reviews.rating as your_review_rating,reviews.updated_at as your_review_updated_at,reviews.created_at as your_review_created_at,reviews.user_id as your_review_user_id,users.first_name as your_review_first_name,users.last_name as your_review_last_name,users.file_url as your_review_file_url,GROUP_CONCAT(egm.genre_id) as genre_ids')
        ->join('entertainment_gener_mapping as egm','egm.entertainment_id','=','entertainments.id')
        ->leftJoin('reviews', function($q) use ($user_id) {
            $q->on('reviews.entertainment_id', '=', 'entertainments.id')
              ->where('reviews.user_id', $user_id)
              ->whereNull('reviews.deleted_at');
        })
        ->leftJoin('subtitles', function($q) use ($movieId) {
            $q->on('subtitles.entertainment_id', '=', 'entertainments.id')
              ->where('subtitles.type', 'tvshow')
              ->where('subtitles.entertainment_id', $movieId);
        })
        ->leftJoin('users','reviews.user_id','=','users.id')
        ->where('entertainments.id', $movieId)
        ->where('entertainments.type', 'tvshow');

    // Restriction filters
    if (request()->has('is_restricted')) {
        $builder->where('is_restricted', request()->is_restricted);
    }

    if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
        $builder->where('is_restricted', 0);
    }

    return $builder;
}

    public static function get_tvshowV3($movieId,$user_id,$profile_id,$device_id)
    {
        $query = Entertainment::select([
            'entertainments.id', 'entertainments.name', 'entertainments.slug', 'entertainments.type', 'entertainments.release_date',
            'entertainments.trailer_url_type', 'entertainments.is_restricted', 'entertainments.IMDb_rating',
            'entertainments.poster_url', 'entertainments.thumbnail_url', 'entertainments.poster_tv_url',
            'entertainments.trailer_url', 'entertainments.video_upload_type', 'entertainments.video_url_input', 'entertainments.movie_access',
            'entertainments.language', 'entertainments.duration', 'entertainments.start_time', 'entertainments.end_time', 'entertainments.content_rating',
            'entertainments.download_url', 'entertainments.description', 'entertainments.download_type', 'entertainments.enable_quality',
            'entertainments.video_quality_url', 'entertainments.download_status', 'entertainments.enable_download_quality',
            'entertainments.status', 'entertainments.enable_subtitle', 'entertainments.subtitle_language', 'entertainments.is_default_subtitle',
            'entertainments.price', 'entertainments.purchase_type', 'entertainments.access_duration', 'entertainments.discount', 'entertainments.available_for',
            'entertainments.plan_id', 'entertainments.tmdb_id', 'entertainments.bunny_trailer_url', 'entertainments.bunny_video_url',
            'entertainments.enable_clips'
        ])
        ->with([
            'genresdata:id,name',
            'plan:id,level',
'entertainmentTalentMappings.talentprofile:id,name,type,file_url,bio,dob,place_of_birth,status,deleted_at',
        'entertainmentGenerMappings.genre:id,name,status',
        'season' => function($q) {
            $q->where('status', 1)
              ->whereNull('deleted_at')
              ->withCount('episodes');
        },
        'subtitles' => function($q) use ($movieId) {
            $q->where('type', 'tvshow')
                  ->where('entertainment_id', $movieId);
            },
            'reviews' => function($q) use ($user_id) {
                $q->where('user_id', $user_id)
                  ->whereNull('deleted_at')
                  ->select(['id', 'entertainment_id', 'user_id', 'review', 'rating', 'created_at', 'updated_at']);
            },
            'reviews.user:id,first_name,last_name,file_url',
            'entertainmentReviews' => function($q) {
                $q->whereNull('deleted_at')
                  ->with('user:id,first_name,last_name,file_url')
                  ->orderBy('created_at', 'desc')
                  ->limit(10);
            }
        ])
        ->addSelect([
            'watched_time' => DB::table('continue_watch')
                ->select('watched_time')
                ->whereColumn('continue_watch.entertainment_id', 'entertainments.id')
                ->where('profile_id', $profile_id)
                ->where('user_id', $user_id)
                ->limit(1)
        ])
        ->withCount([
            'watchlist as is_watch_list' => function($q) use ($user_id, $profile_id, $movieId) {
                $q->where('user_id', $user_id)
                  ->where('profile_id', $profile_id)
                  ->where('entertainment_id', $movieId);
            },
            'likes as is_likes' => function($q) use ($user_id, $profile_id, $movieId) {
                $q->where('user_id', $user_id)
                  ->where('profile_id', $profile_id)
                  ->where('entertainment_id', $movieId)
                  ->where('is_like', 1);
            },
            'downloads as is_download' => function($q) use ($user_id, $device_id, $movieId) {
                $q->where('user_id', $user_id)
                  ->where('device_id', $device_id)
                  ->where('entertainment_type', 'tvshow')
                  ->where('entertainment_id', $movieId)
                  ->where('is_download', 1);
            }
        ])
        ->where('id', $movieId)
        ->where('entertainments.type', 'tvshow');

        // Restriction filters
        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        return $query;
    }


    public static function isPurchased($movieId,$type=null, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        if (!$userId || !$movieId) return false;

        return PayPerView::where('user_id', $userId)
            ->where('movie_id', $movieId)
            ->where('type', $type)
            ->where(function ($query) {
                $query->whereNull('view_expiry_date')
                    ->orWhere('view_expiry_date', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('first_play_date')
                    ->orWhereRaw('DATE_ADD(first_play_date, INTERVAL available_for DAY) > ?', [now()]);
            })
            ->exists();
    }

    /**
     * Get formatted review data for entertainment
     * @param int $entertainment_id
     * @param int|null $user_id
     * @return array
     */
    public static function getReviewData($entertainment_id, $user_id = null)
    {
        $reviewQuery = Review::where('entertainment_id', $entertainment_id);
        $totalReviews = $reviewQuery->count();
        
        $myReview = null;
        if ($user_id) {
            $myReviewModel = (clone $reviewQuery)->where('user_id', $user_id)->first();
            if ($myReviewModel) {
                $myReview = [
                    "id" => $myReviewModel->id,
                    "rating" => (float) $myReviewModel->rating,
                    "review" => $myReviewModel->review,
                    "username" => optional($myReviewModel->user)->full_name ?? '',
                    "profile_image" => setBaseUrlWithFileName(optional($myReviewModel->user)->file_url, 'image', 'users'),
                    "updated_at" => $myReviewModel->updated_at ?? null,
                ];
            }
        }
        
        $otherReviews = (clone $reviewQuery)
            ->where('user_id', '!=', $user_id)
            ->with('user')
            ->orderBy('updated_at', 'desc')
            ->take(3)
            ->get()
            ->map(function($data) {
                return [
                    "id" => $data->id,
                    "rating" => (float) $data->rating,
                    "review" => $data->review,
                    "username" => optional($data->user)->full_name ?? '',
                    "profile_image" => setBaseUrlWithFileName(optional($data->user)->file_url, 'image', 'users'),
                    "updated_at" => $data->updated_at ?? null,
                ];
            })->values()->toArray();

        return [
            'total_reviews' => $totalReviews,
            'my_review' => $myReview,
            'other_reviews' => $otherReviews
        ];
    }



   public function entertainmentSubtitleMappings()
   {

       return $this->hasMany(Subtitle::class, 'entertainment_id', 'id');
   }

    public function subtitles()
    {
        return $this->entertainmentSubtitleMappings();
    }

    public function clips()
    {
        return $this->hasMany(Clip::class, 'content_id', 'id');
    }

    /**
     * Generate and set slug from name
     */
    public function generateSlug()
    {
        if (!empty($this->name) && empty($this->slug)) {
            $this->slug = slug_format(trim($this->name));
        }
        return $this;
    }

    /**
     * Override save method to auto-generate slug
     */
    public function save(array $options = [])
    {
        // Auto-generate slug if empty and name exists
        if (empty($this->slug) && !empty($this->name)) {
            $this->slug = slug_format(trim($this->name));
        }

        return parent::save($options);
    }

}
