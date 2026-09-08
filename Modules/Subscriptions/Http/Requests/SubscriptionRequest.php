<?php

namespace Modules\Subscriptions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'required',
            'plan_id' => 'required',
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

    public function messages()
    {
        return [
            'user_id.required' => __('messages.user_field_required'),
            'plan_id.required' => __('messages.plan_field_required'),
            'payment_date.required' => __('messages.payment_date_required'),
            'payment_date.date' => __('messages.payment_date_must_be_date'),
        ];
    }
}
