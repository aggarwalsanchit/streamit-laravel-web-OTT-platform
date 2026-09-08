<?php

namespace Modules\Entertainment\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Genres\Transformers\GenresResource;
use Modules\Subscriptions\Transformers\PlanResource;
use Modules\Subscriptions\Models\Plan;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\Entertainment;
class MoviesResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $genre_data = [];
        $genres = $this->entertainmentGenerMappings;
        if(!empty($genres)){

            foreach($genres as $genre) {

                $genre_data[] = [
                    'id' => $genre->id,
                    'name' => $genre->genre->name ?? null,
                ];
            }

        }

       $userId = $request->input('user_id') ?? auth()->id();

        if($userId) {
            $profile_id = $request->input('profile_id') ?: getCurrentProfile($userId, $request);
            $contentType = $this->type ?? 'movie';
            $isInWatchList = WatchList::where('entertainment_id', $this->id)
                ->where('user_id', $userId)
                ->where('type', $contentType)
                ->where('profile_id', $profile_id)
                ->exists();
        }else{
            $isInWatchList = $this->is_watch_list ?? false;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'trailer_url_type' => $this->trailer_url_type,
            'type' => $this->type,
            'trailer_url' => $this->trailer_url_type=='Local' ? setBaseUrlWithFileName($this->trailer_url) : $this->trailer_url,
            'movie_access' => $this->movie_access,
            'plan_id' => $this->plan_id,
            'plan_level' => $this->plan_level ??  optional($this->plan)->level,
            'language' => $this->language,
            'imdb_rating' => $this->IMDb_rating ?? $this->imdb_rating,
            'content_rating' => $this->content_rating,
            'duration' => $this->duration,
            'release_date' => $this->release_date,
            'poster_image' => setBaseUrlWithFileName($this->poster_url ?? null,'image',$this->type),
            'is_watch_list' => $isInWatchList ? true : false,
            'genres' => $genre_data,

        ];
    }


}
