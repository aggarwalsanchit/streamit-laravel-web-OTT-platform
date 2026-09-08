<?php

namespace Modules\CastCrew\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Modules\Entertainment\Models\Entertainment;
use Modules\Genres\Models\Genres;
class CastDetailResourceV3 extends JsonResource
{
    public function toArray($request)
    {

        return [
            'name' => $this->name,
            'birth_date' => $this->dob ? formatDate($this->dob) : null,
            'birth_place' => $this->place_of_birth,
            'total_movies' => $this->movie_count,
            'total_tv_show' => $this->tvshow_count,
            'rating' => round($this->rating, 1) ?? 0,
            'role' => $this->type,
            'top_genres' => $this->top_genres ?? null,
            'profile_image' => $this->profile_image,
            'bio' => $this->bio,
        ];
    }
}
