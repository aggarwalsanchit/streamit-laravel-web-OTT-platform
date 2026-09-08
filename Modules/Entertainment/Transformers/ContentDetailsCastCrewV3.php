<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ContentDetailsCastCrewV3  extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
  
        return [
            'id' => $this->id,
            'details'=>[
                'name' => $this->name,
                'type' => $this->type,
                'bio' => $this->bio,
                'dob' => $this->dob,
                'place_of_birth' => $this->place_of_birth,
                'profile_image' => $this->profile_image,
                'designation' => $this->designation ?? null,
                'total_movies' => $this->movie_count ?? 0,
                'total_tv_show' => $this->tvshow_count ?? 0,
                'rating' => $this->rating ?? 0,
                'role' => $this->type,
                'top_genres' => $this->top_genres ?? null,
            ],  

                'suggested_content' => CommanResourceV3::collection($this->more_items ?? []),
        ];

    }


}

