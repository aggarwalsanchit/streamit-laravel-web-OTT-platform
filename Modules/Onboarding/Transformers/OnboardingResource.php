<?php

namespace Modules\Onboarding\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        // dd($this);
        return [
            'id' => $this->id ?? null,
            'title' => $this->title ?? null,
            'description' => $this->description ?? null,
            'image' => setBaseUrlWithFileName($this->file_url, 'image', 'onboarding'),
            'status' => $this->status ? 1 : 0,
        ];
    }
}
