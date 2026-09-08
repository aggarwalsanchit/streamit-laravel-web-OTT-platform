<?php

namespace Modules\Genres\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class GenresResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        // dd($this);
        return [
            'id' => $this->id ?? null,
            'name' => $this->name ?? $this->genre->name ?? null,
            'poster_image' => setBaseUrlWithFileName($this->file_url, 'image', 'genres'),
            'status' => $this->status ?? $this->genre->status ??  null,
        ];
    }
}
