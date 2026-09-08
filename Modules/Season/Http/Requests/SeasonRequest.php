<?php

namespace Modules\Season\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeasonRequest extends FormRequest
{
   public function rules()
    {


        $id = request()->id;
        $rules = [
            'name' => [
                'required',
                Rule::unique('seasons', 'name')
                    ->where('entertainment_id', $this->entertainment_id)
                    ->ignore($id)
            ],
            'season_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('seasons', 'season_number')
                    ->where('entertainment_id', $this->entertainment_id)
                    ->ignore($id)
            ],
            'entertainment_id'=> ['required'],
            // 'access'=> ['required'],
            'description' => 'required|string',

        ];
        $movieAccess = $this->input('movie_access');

        // if ($movieAccess === 'paid') {
        //     $rules['plan_id'] = 'required';
        // } elseif ($movieAccess === 'pay-per-view') {
        //     $rules['price'] = 'required|numeric';
        //     $rules['available_for'] = 'required|integer|min:1';
        // }


        if ($this->has('enable_seo') && $this->enable_seo == 1) {
            $seasonId = $this->route('season');
            // Handle both array and string cases for route parameter
            if (is_array($seasonId)) {
                $seasonId = $seasonId['id'] ?? null;
            }

        $rules = array_merge($rules, [
            'meta_title' => 'required|string|max:100|unique:seasons,meta_title,' . ($seasonId ?: 'NULL') . ',id',
            'google_site_verification' => 'required',
            'meta_keywords' => 'required|max:255',
            'canonical_url' => 'required',
            'short_description' => 'required|string|max:200',
            'seo_image' => 'required',
        ]);
    }

    return $rules;
}




    public function messages()
    {
        return [
            'name.required' => __('messages.name_required'),
            'name.unique' => __('messages.season_name_exists_for_tvshow'),
            'season_number.required' => __('messages.season_number_required'),
            'season_number.integer' => __('messages.season_number_must_be_integer'),
            'season_number.min' => __('messages.season_number_must_be_at_least_one'),
            'season_number.unique' => __('messages.season_number_exists_for_tvshow'),
            'entertainment_id.required' => __('messages.tv_show_required'),
            'access.required' => __('messages.access_required'),

            'discount.required' => __('messages.discount_required'),
            'discount.min' => __('messages.discount_must_be_between_zero_and_ninety_nine'),
            'discount.max' => __('messages.discount_must_be_between_zero_and_ninety_nine'),
            'access_duration.integer' => __('messages.access_duration_integer'),
            'access_duration.min' => __('messages.access_duration_min'),
            'available_for.integer' => __('messages.available_for_integer'),
            'available_for.min' => __('messages.available_for_min'),
            'price.required' => __('messages.price_required'),
            'price.numeric' => __('messages.please_enter_valid_number'),
            'plan_id.required' => __('messages.plan_required'),

            'meta_title.unique' => __('messages.meta_title_already_taken'),
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
