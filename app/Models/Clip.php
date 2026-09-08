<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Video\Models\Video;
use Modules\Entertainment\Models\Entertainment;
use Modules\Episode\Models\Episode;

class Clip extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'content_id',
        'content_type',
        'type',
        'url',
        'poster_url',
        'tv_poster_url',
        'title',
    ];

    protected $casts = [
        'content_id' => 'integer',
    ];

    /**
     * Get the parent content (video, entertainment, or episode)
     */
    public function entertainment()
    {
        return $this->belongsTo(Entertainment::class, 'content_id');
    }

    public function video()
    {
        return $this->belongsTo(Video::class, 'content_id');
    }
}
