<?php

namespace Modules\Video\Models;

use App\Models\BaseModel;
use App\Models\Clip;
use App\Models\Scopes\VideoScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Subscriptions\Models\Plan;
use Modules\Entertainment\Models\Subtitle;
use Modules\Entertainment\Models\EntertainmentView;
use Modules\Entertainment\Models\Like;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\UserReminder;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Entertainment\Models\Review;
class Video extends BaseModel
{

    use SoftDeletes;

    protected $table = 'videos';
    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'poster_url',
        'short_desc',
        'thumbnail_url',
        'trailer_url_type',
        'trailer_url',
        'access',
        'plan_id',
        'status',
        'duration',
        'start_time', // Skip intro start time
        'end_time', // Skip intro end time
        'release_date',
        'is_restricted',
        'video_upload_type',
        'video_url_input',
        'download_status',
        'enable_quality',
        'download_type',
        'download_url',
        'enable_download_quality',
        'poster_tv_url',
        'price',
        'purchase_type',
        'access_duration',
        'discount',
        'available_for',
        'enable_subtitle',
        'subtitle_file',
        'subtitle_language',
        'subtitle_file_exists',
        'IMDb_rating',
        'content_rating',
        'meta_title',
        'meta_keywords',
        'meta_description',
        'seo_image',
        'google_site_verification',
        'canonical_url',
        'short_description',
        'enable_clips',
        'bunny_trailer_url',
        'bunny_video_url',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];


    public function getBaseUrlAttribute($value)
    {
        return !empty($value) ? setBaseUrlWithFileNameV2() : NULL;
    }


     protected static function boot()
     {
         parent::boot();

         static::creating(function ($video) {
            if (empty($video->slug) && !empty($video->name)) {
                $video->slug = \Illuminate\Support\Str::slug(trim($video->name));
            }
        });

         static::updating(function ($video) {
            if ($video->isDirty('name') && !empty($video->name)) {
                $video->slug = \Illuminate\Support\Str::slug(trim($video->name));
            }
        });

         static::deleting(function ($video) {

             if ($video->isForceDeleting()) {

                $video->VideoStreamContentMappings()->withTrashed()->each(function ($mapping) {
                    $mapping->forceDelete();
                });

             } else {

                 $video->VideoStreamContentMappings()->delete();
             }

         });

         static::restoring(function ($video) {

           $video->VideoStreamContentMappings()->withTrashed()->restore();

         });
     }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
      //  static::addGlobalScope(new VideoScope);
    }

    public function VideoStreamContentMappings()
    {
        return $this->hasMany(VideoStreamContentMapping::class,'video_id','id');
    }

    public function plan()
    {
        return $this->hasOne(Plan::class, 'id', 'plan_id');
    }

    public function Watchlist()
    {
        return $this->hasMany(Watchlist::class,'entertainment_id','id','type');
    }

    public function UserReminder()
    {
        return $this->hasMany(UserReminder::class,'entertainment_id','id');
    }

    public function video()
    {
        return $this->hasOne(\Modules\Video\Models\Video::class, 'id', 'entertainment_id');
    }

    public function entertainmentReviews()
    {
        return $this->hasMany(Review::class,'entertainment_id','id');
    }

    public function entertainmentLike()
    {
        return $this->hasMany(Like::class,'entertainment_id','id');
    }

    public function entertainmentView()
    {
        return $this->hasMany(EntertainmentView::class, 'entertainment_id', 'id');
    }

    public function videoDownloadMappings()
    {
        return $this->hasMany(VideoDownloadMapping::class,'video_id','id');
    }

    // Alias: supports withCount('entertainmentDownloads') in queries
    public function entertainmentDownloads()
    {
        return $this->hasMany(\Modules\Entertainment\Models\EntertainmentDownload::class, 'entertainment_id', 'id');
    }

    // Alias: supports with('reviews') and 'reviews.user'
    public function reviews()
    {
        return $this->hasMany(\Modules\Entertainment\Models\Review::class, 'entertainment_id', 'id');
    }


    public function subtitles()
    {
        return $this->hasMany(Subtitle::class, 'entertainment_id', 'id')->where('type', 'video');
    }


    public function clips()
    {
        return $this->hasMany(Clip::class, 'content_id', 'id');
    }

        public static function get_popular_videos($videoIdsArray)
        {
            $videoIdsArray = is_array($videoIdsArray) ? $videoIdsArray : (array) $videoIdsArray;
            $videoIdsArray = array_values(array_filter($videoIdsArray, static fn ($id) => $id !== null && $id !== ''));

            if (count($videoIdsArray) === 0) {
                return collect();
            }

            $query = Video::select([
                'id', 'name', 'slug', 'poster_url', 'plan_id', 'status', 'thumbnail_url',
                'is_restricted', 'duration', 'release_date', 'description',
                'trailer_url', 'video_url_input', 'access', 'price', 'poster_tv_url'
            ])
            ->with(['plan:id,level'])
            ->whereIn('id', $videoIdsArray)
            ->where('status', 1)
            ->where('deleted_at', null);

            if (request()->has('is_restricted')) {
                $query->where('is_restricted', request()->is_restricted);
            }

            if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
                $query->where('is_restricted', 0);
            }

            return $query->get();
        }

    public static function get_pay_per_view_videos()
    {
        if (isenablemodule('video') != 1) {
            return collect();
        }

        $query = Video::select([
            'id', 'name', 'slug', 'poster_url', 'plan_id', 'status', 'thumbnail_url',
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

     public static function get_video($movieId,$user_id,$profile_id,$device_id)
    {
        $query = Video::select([
            'id', 'name', 'type', 'release_date',
            'trailer_url_type', 'is_restricted', 'IMDb_rating',
            'poster_url', 'thumbnail_url', 'poster_tv_url',
            'trailer_url', 'video_upload_type', 'video_url_input', 'access',
             'duration', 'content_rating','start_time', 'end_time',
            'download_url', 'description', 'download_type', 'enable_quality',
            'download_status', 'enable_download_quality',
            'status', 'enable_subtitle', 'subtitle_language', 'is_default_subtitle',
            'price', 'purchase_type', 'access_duration', 'discount', 'available_for',
            'plan_id','bunny_video_url', 'enable_clips'
        ])
        ->with([
            'plan:id,level',
            'subtitles' => function($q) use ($movieId) {
                $q->where('type', 'video')
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
            'watched_time' => \Illuminate\Support\Facades\DB::table('continue_watch')
                ->select('watched_time')
                ->whereColumn('continue_watch.entertainment_id', 'videos.id')
                ->where('profile_id', $profile_id)
                ->where('user_id', $user_id)
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
                  ->where('entertainment_type', 'video')
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

}
