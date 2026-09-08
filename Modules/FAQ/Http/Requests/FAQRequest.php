<?php

namespace Modules\FAQ\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FAQRequest extends FormRequest
{
   public function rules()
    {

        return [
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
        ];
    }


    public function messages()
    {
        return [
            'question.required' => __('messages.question_field_required'),
            'answer.required' => __('messages.answer_field_required'),
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
