<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Entertainment\Models\Entertainment;
use Modules\Episode\Models\Episode;
use Modules\Video\Models\Video;
use Modules\CastCrew\Models\CastCrew;


class UserSearchHistory extends Model
{
    use HasFactory;

    protected $table = 'user_search_histories';

    protected $fillable = [
        'user_id',
        'profile_id',
        'search_query',
        'search_id',
        'type'
    ];

    public function entertainment()
    {
        return $this->belongsTo(Entertainment::class, 'search_id')->select('id', 'name', 'poster_url', 'release_date','slug');
    }


    public function episode()
    {
        return $this->belongsTo(Episode::class, 'search_id')->select('id', 'name', 'poster_url', 'release_date','slug');
    }


    public function video()
    {
        return $this->belongsTo(Video::class, 'search_id')->select('id', 'name', 'poster_url','slug');
    }


    public function castcrew()
    {
        return $this->belongsTo(CastCrew::class, 'search_id');
    }

    public function getPosterImageAttribute($value){
        return match ($this->type) {
            'movie','tvshow' => setBaseUrlWithFileName(optional($this->entertainment)->poster_url,'image',$this->type),
            'episode'         => setBaseUrlWithFileName(optional($this->episode)->poster_url,'image',$this->type),
            'video'           => setBaseUrlWithFileName(optional($this->video)->poster_url,'image',$this->type),
            'castcrew'        => setBaseUrlWithFileName(optional($this->castcrew)->file_url,'image',$this->type),
            default           => null,
        };
    }

    public function getReleaseDateAttribute()
    {
        return match ($this->type) {
            'movie', 'tvshow' => optional($this->entertainment)->release_date,
            'episode'         => optional($this->episode)->release_date,
            'video'           => optional($this->video)->release_date,
            default           => null,
        };
    }



}
