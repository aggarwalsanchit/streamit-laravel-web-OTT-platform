<?php

namespace Modules\Episode\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Entertainment\Models\Entertainment;
use Modules\Season\Models\Season;
use Modules\Subscriptions\Models\Plan;
use Modules\Entertainment\Models\Subtitle;
use Modules\Entertainment\Models\Like;
use Modules\Entertainment\Models\EntertainmentView;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Entertainment\Models\Review;

class Episode extends BaseModel
{

    use SoftDeletes;

    protected $table = 'episodes';
    protected $fillable=[ 'name',
                          'slug',
                          'entertainment_id',
                          'season_id',
                          'poster_url',
                          'trailer_url_type',
                          'trailer_url',
                          'access',
                          'plan_id',
                          'IMDb_rating',
                          'content_rating',
                          'duration',
                          'start_time', // Skip intro start time
                          'end_time', // Skip intro end time
                          'release_date',
                          'is_restricted',
                          'short_desc',
                          'description',
                          'enable_quality',
                          'video_upload_type',
                          'video_url_input',
                          'download_status',
                          'download_type',
                          'download_url',
                          'enable_download_quality',
                          'status',
                          'video_quality_url','tmdb_id','tmdb_season','episode_number','poster_tv_url','enable_subtitle',
                        'poster_tv_url',
                        'price',
                        'purchase_type',
                        'access_duration',
                        'discount',
                        'available_for',
                        'meta_title',
                        'meta_keywords',
                        'meta_description',
                        'seo_image',
                        'google_site_verification',
                        'canonical_url',
                        'short_description',
                        'bunny_trailer_url',
                        'bunny_video_url',
                    ];

    protected $casts = [
        'release_date' => 'date',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($episode) {
            if (empty($episode->slug) && !empty($episode->name)) {
                $episode->slug = \Illuminate\Support\Str::slug(trim($episode->name));
            }
        });

        static::updating(function ($episode) {
            if ($episode->isDirty('name') && !empty($episode->name)) {
                $episode->slug = \Illuminate\Support\Str::slug(trim($episode->name));
            }
        });

        static::deleting(function ($episode) {

         if ($episode->isForceDeleting()) {

             $episode->EpisodeStreamContentMapping()->forceDelete();
             $episode->episodeDownloadMappings()->forceDelete();

         } else {

             $episode->EpisodeStreamContentMapping()->delete();
             $episode->episodeDownloadMappings()->delete();
         }

        });

        static::restoring(function ($episode) {

            $episode->EpisodeStreamContentMapping()->withTrashed()->restore();
            $episode->episodeDownloadMappings()->delete();

        });
    }

    public function entertainmentdata()
    {
        return $this->belongsTo(Entertainment::class,'entertainment_id')->with('entertainmentGenerMappings', 'season');
    }


    public function seasondata()
    {
        return $this->belongsTo(Season::class,'season_id');
    }

    public function episodeDownloadMappings()
    {
        return $this->hasMany(EpisodeDownloadMapping::class, 'episode_id', 'id');
    }


    public function EpisodeStreamContentMapping()
    {
        return $this->hasMany(EpisodeStreamContentMapping::class,'episode_id','id');
    }

    public function plan()
    {
        return $this->hasOne(Plan::class, 'id', 'plan_id');
    }

    public function subtitles()
    {
        return $this->hasMany(Subtitle::class, 'entertainment_id', 'id')->where('type', 'episode');
    }

    public static function get_episode($movieId,$user_id,$profile_id,$device_id)
    {
        $query = Episode::select([
            'id','slug','season_id','entertainment_id','plan_id','video_url_input','trailer_url','trailer_url_type','video_upload_type',
            'poster_url','poster_tv_url','is_restricted','name','content_rating','duration','release_date','IMDb_rating','description',
            'enable_quality','download_status','download_type','download_url','enable_download_quality','access','price',
            'purchase_type','access_duration','discount','available_for','status','start_time','end_time','enable_subtitle',
            'subtitle_language','is_default_subtitle','tmdb_id','bunny_trailer_url','bunny_video_url',           
        ])
        ->with([
            'plan:id,level',
            'subtitles' => function($q) use ($movieId) {
                $q->where('type', 'episode')
                  ->where('entertainment_id', $movieId);
            },
            'seasondata' => function($q) {
                $q->withCount('episodes');
            },
            'seasondata.episodes',
            'entertainmentdata' => function($q) {
                $q->with([
                    'entertainmentGenerMappings.genre:id,name,status',
                    'season' => function($sq) {
                        $sq->where('status', 1)
                          ->whereNull('deleted_at')
                          ->withCount('episodes');
                    }
                ]);
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
            'watched_time' => \Illuminate\Support\Facades\DB::table('continue_watch')
                ->select('watched_time')
                ->whereColumn('continue_watch.episode_id', 'episodes.id')
                ->where('profile_id', $profile_id)
                ->where('user_id', $user_id)
                ->whereNull('continue_watch.deleted_at')
                ->limit(1)
        ])
        ->withCount([
            'Watchlist as is_watch_list' => function($q) use ($user_id, $profile_id, $movieId) {
                $q->where('user_id', $user_id)
                  ->where('profile_id', $profile_id)
                  ->where('entertainment_id', $movieId);
            },
            'entertainmentLike as is_likes' => function($q) use ($user_id, $profile_id, $movieId) {
                $q->where('user_id', $user_id)
                  ->where('profile_id', $profile_id)
                  ->where('entertainment_id', $movieId)
                  ->where('is_like', 1);
            },
            'entertainmentDownloads as is_download' => function($q) use ($user_id, $device_id, $movieId) {
                $q->where('user_id', $user_id)
                  ->where('device_id', $device_id)
                  ->where('entertainment_type', 'episode')
                  ->where('entertainment_id', $movieId)
                  ->where('is_download', 1);
            }
        ])
        ->where('id', $movieId);

        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }
        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        return $query;
    }

    public static function get_pay_per_view_episodes()
    {
        if (isenablemodule('tvshow') != 1) {
            return collect();
        }

        $query = Episode::select([
            'id', 'name', 'slug', 'poster_url', 'plan_id', 'status',
            'is_restricted', 'duration', 'release_date', 'description',
            'trailer_url', 'video_url_input', 'access', 'price'
        ])
        ->with(['plan:id,level'])
        ->where('access', 'pay-per-view')
        ->where('status', 1)
        ->where('deleted_at', null)
        ->orderBy('id', 'desc')
        ->take(5);

        if (request()->has('is_restricted')) {
            $query->where('is_restricted', request()->is_restricted);
        }

        if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
            $query->where('is_restricted', 0);
        }

        return $query->get();
    }

    public function entertainmentLike()
    {
        return $this->hasMany(Like::class,'entertainment_id','id');
    }

    public function entertainmentView()
    {
        return $this->hasMany(EntertainmentView::class, 'entertainment_id', 'id');
    }

    public function Watchlist()
    {
        return $this->hasMany(Watchlist::class, 'entertainment_id', 'id');
    }

    // Alias: supports withCount('entertainmentDownloads') in queries
    public function entertainmentDownloads()
    {
        return $this->hasMany(EntertainmentDownload::class, 'entertainment_id', 'id');
    }

    // Alias: supports with('reviews') and 'reviews.user'
    public function reviews()
    {
        return $this->hasMany(Review::class, 'entertainment_id', 'id');
    }

    public function entertainmentReviews()
    {
        return $this->hasMany(Review::class, 'entertainment_id', 'id');
    }

}
