<?php

namespace Modules\Ad\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CustomAdsSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {


        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'url_type' => $this->url_type,
            'placement' => $this->placement,
            'media' => $this->url_type =='local' ? setBaseUrlWithFileName($this->media,$this->type,'ads') : $this->media,
            'redirect_url' => $this->redirect_url,
            'target_content_type' => $this->target_content_type,
            'target_categories' => $this->target_categories,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];
    }
}
