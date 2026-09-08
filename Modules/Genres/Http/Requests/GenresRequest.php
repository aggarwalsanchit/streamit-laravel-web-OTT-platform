<?php

namespace Modules\Genres\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class GenresRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [
           'name' => 'required|string|max:255|unique:genres,name,' . $this->route('genre'),
            'status' => 'sometimes|boolean',

        ];

        return $rules;

    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function messages()
    {
        return [
            'name.required' => __('messages.name_required'),
            'name.string' => __('messages.name_must_be_a_string'),
            'name.max' => __('messages.name_cannot_exceed_255_characters'),
            'name.unique' => __('messages.name_already_exists'),
            'status.boolean' => __('messages.status_must_be_true_or_false'),

        ];
    }
}
