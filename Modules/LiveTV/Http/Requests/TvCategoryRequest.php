<?php

namespace Modules\LiveTV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class TvCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Try multiple ways to get the route parameter ID
        $id = $this->route('tv-category') 
            ?? $this->route('tv_category') 
            ?? $this->route('id')
            ?? request()->id
            ?? null;
        // If it's a model instance (model binding), get the ID
        if ($id && is_object($id) && method_exists($id, 'getKey')) {
            $id = $id->getKey();
        }
      
        return [
            'name' => ['required', Rule::unique('live_tv_category', 'name')->ignore($id)],
            'description' => 'required|string',
        ];
    }
    public function messages()
    {
        return [
            'name.required' => __('messages.name_field_required'),
            'name.unique' => __('messages.name_already_exists'),
            'description.required' => __('messages.description_field_required'),
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
