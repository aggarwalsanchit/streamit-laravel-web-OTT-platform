<?php

namespace Modules\Tax\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxRequest extends FormRequest
{
    public function rules()
    {
        // Get the tax ID from route parameter (for update) or null (for create)
        $tax = $this->route('tax');
        $taxId = $tax ? $tax->id : null;

        return [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('taxes', 'title')->ignore($taxId),
            ],
            'type' => 'required|in:Fixed,Percentage',
            'value' => ['required','numeric','min:0',
                function($attribute, $value, $fail) {
                    if ($this->type == 'Percentage' && ($value < 1 || $value > 100)) {
                        $fail('The ' . $attribute . ' Value must be between 1 and 100 for Percentage.');
                    }
                }
            ],

        ];
    }


    public function messages()
    {
        return [
            'title.required' => __('messages.title_required'),
            'title.unique' => __('messages.title_already_exists'),
            'type.required' => __('messages.type_field_required'),
            'type.in' => __('messages.type_invalid'),
            'value.required' => __('messages.value_field_required'),
            'value.numeric' => __('messages.value_must_be_numeric'),
            'value.min' => __('messages.value_min'),
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
