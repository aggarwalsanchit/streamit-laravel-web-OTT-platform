<?php

namespace Modules\Page\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PageRequest extends FormRequest
{
   public function rules()
    {

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }


    public function messages()
    {
        return [
            'name.required' => __('messages.name_field_required'),
            'name.string' => __('messages.name_must_be_string'),
            'name.max' => __('messages.name_max_length'),
            'description.required' => __('messages.description_field_required'),
            'description.string' => __('messages.description_must_be_string'),
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
