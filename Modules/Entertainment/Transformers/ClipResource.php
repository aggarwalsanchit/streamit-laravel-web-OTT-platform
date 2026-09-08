<?php

namespace Modules\Entertainment\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class ClipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        $clipType = $this->type;
        $clipUrl = $this->url;

        $posterContentType = ($this->content_type === 'tv_show') ? 'tvshow' : $this->content_type;

        if ($clipType === 'Local') {
            $clipUrl = setBaseUrlWithFileName($clipUrl, 'video', $posterContentType);
        } else {
            $clipUrl = Crypt::encryptString($this->url);
        }


        return [
            'id' => $this->id,
            'content_id' => $this->content_id,
            'content_type' => $this->content_type,
            'title' => $this->title,
            'poster_url' => setBaseUrlWithFileName($this->poster_url, 'image', $posterContentType),
            'tv_poster_url' => setBaseUrlWithFileName($this->tv_poster_url, 'image', $posterContentType),
            'type' => $clipType,
            'url' => $clipUrl,
        ];
    }
}


