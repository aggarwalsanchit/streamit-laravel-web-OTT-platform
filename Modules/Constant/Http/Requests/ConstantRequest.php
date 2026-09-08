<?php

namespace Modules\Constant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Constant\Models\Constant;

class ConstantRequest extends FormRequest
{
    public function rules()
    {
        $constantId = $this->route('id') ?? $this->route('constant');
        
        return [
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('constants', 'name')
                    ->where('type', $this->input('type'))
                    ->whereNull('deleted_at')
                    ->ignore($constantId)
            ],
            'type' => 'required|string|max:255',
            'value' => 'required',
            'language_image' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('messages.name_field_required'),
            'name.unique' => __('messages.name_type_already_exists'),
            'type.required' => __('messages.type_field_required'),
            'value.required' => __('messages.value_field_required'),
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
