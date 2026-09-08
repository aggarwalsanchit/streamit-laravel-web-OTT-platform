<?php

namespace Modules\LiveTV\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class LiveTvCategory extends BaseModel
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'live_tv_category';

    protected $fillable = [
        'name',
        'slug',
        'file_url',
        'description',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($liveTvCategory) {
            if (empty($liveTvCategory->slug) && !empty($liveTvCategory->name)) {
                $liveTvCategory->slug = \Illuminate\Support\Str::slug(trim($liveTvCategory->name));
            }
        });
    }

    public function getFileUrlAttribute($value)
    {
        return setBaseUrlWithFileName($value, 'image', 'livetv');
    }


    public function tvChannels()
    {
        return $this->hasMany(LiveTvChannel::class,'category_id')->where('status', 1)->with('TvChannelStreamContentMappings');
    }
}
