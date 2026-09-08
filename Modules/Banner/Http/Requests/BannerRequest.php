<?php

namespace Modules\Banner\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
   public function rules()
    {
        $rules = [
            'banner_for' => 'required|string|in:movie,tv_show,livetv,video,promotional,home',
        ];

        // Require type and name_id for non-promotional banner_for
        if ($this->input('banner_for') !== 'promotional') {
            $rules['type'] = 'required|string|in:movie,tvshow,livetv,video';
            $rules['name_id'] = 'required';
        }

        // Require title for promotional banner_for only
        if ($this->input('banner_for') === 'promotional') {
            $rules['title'] = 'required|string|max:120';
        }

        return $rules;
    }


    public function messages()
    {
        return [
            'banner_for.required' => __('messages.banner_for_field_required'),
            'banner_for.in' => __('messages.banner_for_invalid'),
            'type.required' => __('messages.type_field_required'),
            'type.in' => __('messages.type_invalid'),
            'name_id.required' => __('messages.name_field_required'),
            'title.required' => __('messages.title_field_required'),
            'title.max' => __('messages.banner_title_max_120_characters'),
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
