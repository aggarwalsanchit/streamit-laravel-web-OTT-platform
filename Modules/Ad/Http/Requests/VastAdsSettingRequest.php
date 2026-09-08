<?php

namespace Modules\Ad\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class VastAdsSettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'type' => ['required'],
            'url' => ['required'],
            'target_type' => ['required'],
            'target_selection' => ['required'],
            'status' => ['required'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after:start_date'
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('messages.ad_name_required'),
            'name.unique' => __('messages.name_already_exists'),
            'type.required' => __('messages.ad_type_required'),
            'url.required' => __('messages.ad_url_required'),
            'target_type.required' => __('messages.target_type_required'),
            'target_selection.required' => __('messages.target_selection_required'),
            'start_date.required' => __('messages.start_date_required'),
            'start_date.date' => __('messages.start_date_must_be_valid_date'),
            'start_date.date_format' => __('messages.start_date_must_be_yyyy_mm_dd_format'),
            'end_date.required' => __('messages.end_date_required'),
            'end_date.date' => __('messages.end_date_must_be_valid_date'),
            'end_date.date_format' => __('messages.end_date_must_be_yyyy_mm_dd_format'),
            'end_date.after' => __('messages.end_date_after_start_date'),
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
