<?php

namespace Modules\Banner\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Entertainment\Models\Entertainment;

class Banner extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'banners';
    protected $fillable = ['title', 'file_url','poster_url','type', 'type_id','type_name','description', 'status', 'created_by','banner_for','poster_tv_url'];
    const CUSTOM_FIELD_MODEL = 'Modules\Banner\Models\Banner';

    /**
     * Human-friendly label for banner content type (stored in `type`).
     */
    public function getTypeLabelAttribute(): string
    {
        if (empty($this->type)) {
            return '-';
        }

        return match (strtolower((string) $this->type)) {
            'tvshow' => 'TV Show',
            'livetv', 'live_tv' => 'Live TV',
            default => label_case((string) $this->type),
        };
    }

    /**
     * Human-friendly label for where the banner is shown (stored in `banner_for`).
     */
    public function getBannerForLabelAttribute(): string
    {
        if (empty($this->banner_for)) {
            return '-';
        }

        return match (strtolower((string) $this->banner_for)) {
            'tv_show', 'tvshow' => 'TV Show',
            'livetv', 'live_tv' => 'Live TV',
            default => label_case(str_replace('_', ' ', (string) $this->banner_for)),
        };
    }

    /**
     * Translated label for `type` using cached translation helper.
     */
    public function getTypeLabelTranslatedAttribute(): string
    {
        $label = $this->type_label;
        if ($label === '-') {
            return $label;
        }

        // Fast path: if app locale is English, return as-is (no translation needed)
        if (strtolower((string) app()->getLocale()) === 'en') {
            return $label;
        }

        // Use local Laravel translation keys (instant) for known enum-like values.
        // This avoids slow network translation during datatable rendering.
        return match (strtolower((string) $this->type)) {
            'movie' => __('messages.movie'),
            'tvshow' => __('messages.tvshow'),
            'video' => __('messages.video'),
            'livetv', 'live_tv' => __('frontend.live_tv'),
            default => $label,
        };
    }

    /**
     * Translated label for `banner_for` using cached translation helper.
     */
    public function getBannerForLabelTranslatedAttribute(): string
    {
        $label = $this->banner_for_label;
        if ($label === '-') {
            return $label;
        }

        // Fast path: if app locale is English, return as-is (no translation needed)
        if (strtolower((string) app()->getLocale()) === 'en') {
            return $label;
        }

        // Use local translation keys for known values.
        return match (strtolower((string) $this->banner_for)) {
            'home' => __('messages.home'),
            'movie' => __('messages.movie'),
            'tv_show', 'tvshow' => __('messages.tvshow'),
            'video' => __('messages.video'),
            'livetv', 'live_tv' => __('frontend.live_tv'),
            'promotional' => __('banner.lbl_promotional'),
            default => $label,
        };
    }

    // public static function get_sliderList($type=null)
    // {
    //     $query = Banner::select([
    //         'id', 'banner_for', 'title', 'poster_url', 'file_url', 'type', 'type_id'
    //     ])
    //     ->with([
    //         'entertainment' => function($q) {
    //             $q->select([
    //                 'id', 'name', 'type', 'plan_id', 'description', 'trailer_url_type',
    //                 'is_restricted', 'language', 'imdb_rating', 'content_rating',
    //                 'duration', 'video_upload_type', 'release_date', 'trailer_url',
    //                 'video_url_input', 'poster_url', 'movie_access', 'download_status',
    //                 'enable_quality', 'download_url', 'status'
    //             ])
    //             ->with([
    //                 'plan:id,level',
    //                 'genresdata:id,name'
    //             ]);

    //             if (request()->has('is_restricted')) {
    //                 $q->where('is_restricted', request()->is_restricted);
    //             }

    //             if (!empty(getCurrentProfileSession('is_child_profile')) && getCurrentProfileSession('is_child_profile') != 0) {
    //                 $q->where('is_restricted', 0);
    //             }
    //         },
    //         'liveTvChannel' => function($q) {
    //             $q->select([
    //                 'id', 'name', 'plan_id', 'description', 'status', 'access', 'category_id'
    //             ])
    //             ->with([
    //                 'plan:id,level',
    //                 'category:id,name',
    //                 'streamContentMappings:id,tv_channel_id,stream_type,embedded,server_url,server_url1'
    //             ]);
    //         }
    //     ])
    //     ->where('status', 1);

    //     if (!empty($type)) {
    //         $query->where('banner_for', $type);
    //     }

    //     return $query->get();
    // }

    public function entertainment()
    {
        return $this->belongsTo(Entertainment::class, 'type_id')->where('type', 'entertainment');
    }

    public function liveTvChannel()
    {
        return $this->belongsTo(\Modules\LiveTv\Models\LiveTvChannel::class, 'type_id')->where('type', 'live_tv');
    }
}
