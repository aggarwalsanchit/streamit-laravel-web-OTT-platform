<?php

namespace Modules\Subscriptions\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        
            $rules = [
                'name' => ['required'],
                'duration' => ['required'],
                'description' => ['required'],
                'duration_value' => ['required', 'numeric', 'min:1',  Rule::when($this->duration === 'month', ['max:11'])],
                'price' => ['required', 'numeric', 'min:1'],
            ];

            // Add discount_percentage validation when discount is enabled
            if ($this->has('discount') && $this->discount == 1) {
                $rules['discount_percentage'] = [
                    'required',
                    'numeric',
                    'gt:0',  // Greater than 0 (not equal to 0)
                    'max:99'
                ];
            }

            if ($this->isMethod('put')) {
                $rules['level'] = ['required'];
            }

            return $rules;

    }

    public function messages()
    {
        return [
            'name.required' => __('messages.name_field_required'),
            'level.required' => __('messages.level_required'),
            'duration.required' => __('messages.duration_required'),
            'duration_value.required' => __('messages.duration_value_required'),
            'duration_value.numeric' => __('messages.duration_value_must_be_numeric'),
            'duration_value.min' => __('messages.duration_value_min'),
            'duration_value.max' => __('messages.duration_value_month_max_11'),
            'price.required' => __('messages.price_required'),
            'price.numeric' => __('messages.price_numeric'),
            'price.min' => __('messages.price_min'),
            'description.required' => __('messages.description_field_required'),
            'discount_percentage.required' => __('messages.discount_percentage_required'),
            'discount_percentage.numeric' => __('messages.discount_percentage_between_0_and_99'),
            'discount_percentage.gt' => __('messages.discount_percentage_greater_than_zero'),
            'discount_percentage.max' => __('messages.discount_percentage_cannot_exceed_99'),
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ];

        if (request()->wantsJson() || request()->ajax()) {
            throw new HttpResponseException(response()->json($data, 422));
        }

        throw new HttpResponseException(
            redirect()->back()
                ->withInput()
                ->withErrors($validator)
        );
    }
}
