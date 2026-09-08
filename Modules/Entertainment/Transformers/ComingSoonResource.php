<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Genres\Transformers\GenresResource;
use Modules\Season\Models\Season;
use Modules\Entertainment\Models\UserReminder;
use Modules\Entertainment\Transformers\ClipResource;
use Modules\CastCrew\Transformers\CastCrewListResource;
use Auth;

class ComingSoonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $genre_data = [];
        $genres = $this->entertainmentGenerMappings;
        foreach($genres as $genre){
            $genre_data[] = $genre->genre;
        }

        // --- Casts & Directors (limit 11 each) ---
        $casts = $this->entertainmentTalentMappings
            ->filter(fn($mapping) => $mapping->talentprofile && $mapping->talentprofile->type === 'actor')
            ->take(11)
            ->pluck('talentprofile');

        $directors = $this->entertainmentTalentMappings
            ->filter(fn($mapping) => $mapping->talentprofile && $mapping->talentprofile->type === 'director')
            ->take(11)
            ->pluck('talentprofile');

        $user_id=Auth::id();

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user_id, $request);


        $season = Season::where('entertainment_id', $this->id)->latest()->first();
        $is_reminder = UserReminder::where('entertainment_id',$this->id)->where('profile_id',$profile_id)->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => strip_tags($this->description),
            'trailer_url_type' => $this->trailer_url_type,
            'episode_id' => $entertainment->id ?? null,
            'type' => $this->type,
            'trailer_url' => $this->trailer_url_type=='Local' ? setBaseUrlWithFileName($this->trailer_url,'video',$this->type) : $this->trailer_url,
            'language' => $this->language,
            'imdb_rating' => $this->IMDb_rating,
            'content_rating' => $this->content_rating,
            'release_date' => $this->release_date ? formatDate($this->release_date) : null,
            'is_restricted' => $this->is_restricted,
            'season_name' => $season->name ?? null,
            'thumbnail_image' => setBaseUrlWithFileName($this->thumbnail_url,'image',$this->type),
            'is_remind' => !empty($is_reminder) ? $is_reminder->is_remind :0,
            'genres' => GenresResource::collection($genre_data),
            'is_in_watchlist' => $this->is_in_watchlist > 0 ? 1 : 0,
            'duration' => $this->duration,
            'poster_image' => $this->posterImage,
            'meta_title' => !empty($this->meta_title) ? $this->meta_title : '',
            'short_description' => !empty($this->short_description) ? $this->short_description : '',
            'meta_keywords' => !empty($this->meta_keywords) ? $this->meta_keywords : '',
            'meta_description' => !empty($this->meta_description) ? $this->meta_description : '',
            'seo_image' => !empty($this->seo_image) ? $this->seo_image : '',
            'google_site_verification' => !empty($this->google_site_verification) ? $this->google_site_verification : '',
            'canonical_url' => !empty($this->canonical_url) ? $this->canonical_url : '',
            'is_clips_enabled' => $this->enable_clips,
            'clips' => ClipResource::collection(
                ($this->clips ?? collect())
                    ->when(in_array($this->type, ['movie','tvshow']), function($q){
                        $mapType = $this->type === 'movie' ? 'movie' : 'tv_show';
                        return $q->where('content_type', $mapType);
                    })
                    ->values()
            ),
            'casts' => CastCrewListResource::collection($casts),
            'directors' => CastCrewListResource::collection($directors),
        ];
    }
}
