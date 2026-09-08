<?php

namespace Modules\SEO\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SeoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {

        return [
             'meta_title' => 'required|string|max:255',
        'meta_description' => 'nullable|string',
        'meta_keywords' => 'required|array',
        'meta_keywords.*' => 'required|string',
        'google_site_verification' => 'required|string|max:255',
        'short_description' => 'required|string',   //  MUST be here
        'canonical_url' => 'required|string',       //  MUST be here (or use 'url')
            'seo_image' => 'required|string', // Image validation
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'meta_title.required' => __('messages.meta_title_required'),
            'meta_keywords.required' => __('messages.meta_keywords_required'),
            'meta_keywords.array' => __('messages.meta_keywords_required'),
            'meta_keywords.*.required' => __('messages.meta_keywords_required'),
            'google_site_verification.required' => __('messages.google_site_verification_required'),
            'short_description.required' => __('messages.site_meta_description_required'),
            'canonical_url.required' => __('messages.canonical_url_required'),
            'seo_image.required' => __('messages.seo_image_required'),
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
