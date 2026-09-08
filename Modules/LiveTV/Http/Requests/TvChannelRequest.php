<?php

namespace Modules\LiveTV\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TvChannelRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $id = $this->route('tv-channel') 
            ?? $this->route('tv_channel') 
            ?? $this->route('id')
            ?? request()->id
            ?? null;
        // If it's a model instance (model binding), get the ID
        if ($id && is_object($id) && method_exists($id, 'getKey')) {
            $id = $id->getKey();
        }
        $rules = [
            'name' => ['required', Rule::unique('live_tv_channel', 'name')->ignore($id)],
            'access' => 'required|in:paid,free',
            'plan_id' => 'required_if:access,paid',
            'description' => 'required|string',
        ];

        // Validate server_url when type is "t_url" and stream_type is selected (any stream type: URL, HLS, YouTube, Vimeo, x265, etc.)
        if ($this->input('type') === 't_url' && !empty($this->input('stream_type'))) {
            $rules['server_url'] = ['required', 'string', 'regex:/^https?:\/\/.+$/'];
        }

        // Validate embedded when type is "t_embedded" and stream_type is selected
        if ($this->input('type') === 't_embedded' && !empty($this->input('stream_type'))) {
            $rules['embedded'] = ['required', 'string', 'regex:/<iframe\b[^>]*\bsrc\s*=\s*["\'"](.*?)["\'"].*?>.*?<\/iframe>/i'];
        }

        return $rules;
    }
    public function messages()
    {
        return [
            'name.required' => __('messages.name_field_required'),
            'name.unique' => __('messages.name_already_exists'),
            'plan_id.required' => __('messages.plan_field_required'),
            'description.required' => __('messages.description_field_required'),
            'stream_type.required' => __('messages.stream_type_field_required'),
            'server_url.required' => __('messages.server_url_field_required'),
            'server_url.regex' => __('messages.server_url_regex'),
            'embedded.required' => __('messages.embedded_field_required'),
            'embedded.regex' => __('messages.embedded_regex'),
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
