<?php

namespace Modules\Entertainment\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;

class ContinueWatchResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $entertainment = null;
        $thumbnail_url = null;

        if($this->entertainment_type == 'movie' || $this->entertainment_type == 'tvshow'){
            $entertainment = $this->entertainmentdata;
            $thumbnail_url = $this->entertainmentdata->poster_url ?? null;
        }
        else if($this->entertainment_type == 'episode'){
            $entertainment = $this->episodedata;
            $thumbnail_url = $this->episodedata->poster_url ?? null ;
        }
        else if($this->entertainment_type == 'video'){
            $entertainment = $this->videodata;
            $thumbnail_url = $this->videodata->poster_url ?? null;
        }


        return [
            'id' => $this->id,
            'thumbnail_image' => setBaseUrlWithFileName( $thumbnail_url,'image', $this->entertainment_type),
             'id' => $this->entertainment_id,
             'poster_image' => $thumbnail_url ? setBaseUrlWithFileName($thumbnail_url,'image',$this->entertainment_type) : null,
             'name' => $entertainment->name ?? null,
             'slug' => $entertainment->slug ?? null,
             'entertainment_type'=> $this->entertainment_type,
             'watched_duration' => $this->watched_time ?? '00:00:01',
             'total_duration' => $this->total_watched_time ?? '00:00:01',
             'tv_show_data' => $this->tv_show_data ?? null,
             'episode_id' => $this->episode_id ?? null,
        ];
    }
}
