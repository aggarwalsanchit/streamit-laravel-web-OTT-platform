<?php

namespace Modules\Onboarding\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OnboardingRequest extends FormRequest
{
   public function rules()
    {

        return [
            'title' => ['required', 'max:50'],
            'description' => ['required', 'max:120'],
            'file_url' => ['required'],

        ];
    }


    public function messages()
    {
        return [
            'title.required' => __('messages.title_required'),
            'title.max' => __('messages.title_max_50_characters'),
            'description.required' => __('messages.description_required'),
            'description.max' => __('messages.description_max_120_characters'),
            'file_url.required' => __('messages.image_url_required'),

        ];
    }
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
