<?php

namespace Modules\LiveTV\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\LiveTV\Transformers\LiveTvChannelResource;

class LiveTvCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => strip_tags($this->description),
            'category_image' => setBaseUrlWithFileName($this->file_url, 'image', 'livetv'),
            'channel_data' => LiveTvChannelResource::collection($this->tvChannels),
            'status' => $this->status,
        ];
    }
}
