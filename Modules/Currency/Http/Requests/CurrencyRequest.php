<?php

namespace Modules\Currency\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CurrencyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Adjust as per your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $currencyId = $this->route('id') ?? $this->route('currency');

        $rules = [
            'currency_name' => ['required', 'string', 'max:255'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'currency_code' => ['required', 'string', 'max:5'],
            'is_primary' => ['nullable', 'boolean'],
            'currency_position' => ['required', 'string', 'in:left,right,left_with_space,right_with_space'],
            'thousand_separator' => ['required', 'string', 'max:1'],
            'decimal_separator' => ['required', 'string', 'max:1'],
            'no_of_decimal' => ['required', 'integer'],
        ];

        // Add unique validation rules
        if ($currencyId) {
            // For update: exclude current record
            $rules['currency_name'][] = 'unique:currencies,currency_name,' . $currencyId . ',id,deleted_at,NULL';
            $rules['currency_code'][] = 'unique:currencies,currency_code,' . $currencyId . ',id,deleted_at,NULL';
            $rules['currency_symbol'][] = 'unique:currencies,currency_symbol,' . $currencyId . ',id,deleted_at,NULL';
        } else {
            // For create: check uniqueness
            $rules['currency_name'][] = 'unique:currencies,currency_name,NULL,id,deleted_at,NULL';
            $rules['currency_code'][] = 'unique:currencies,currency_code,NULL,id,deleted_at,NULL';
            $rules['currency_symbol'][] = 'unique:currencies,currency_symbol,NULL,id,deleted_at,NULL';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'currency_name.required' => __('currency.currency_name_required'),
            'currency_name.unique' => __('currency.currency_name_unique'),
            'currency_symbol.required' => __('currency.currency_symbol_required'),
            'currency_symbol.unique' => __('currency.currency_symbol_unique'),
            'currency_code.required' => __('currency.currency_code_required'),
            'currency_code.unique' => __('currency.currency_code_unique'),
            'currency_position.required' => __('currency.currency_position_required'),
            'currency_position.in' => __('currency.currency_position_in'),
            'thousand_separator.required' => __('currency.thousand_separator_required'),
            'decimal_separator.required' => __('currency.decimal_separator_required'),
            'no_of_decimal.required' => __('currency.no_of_decimal_required'),
            'no_of_decimal.integer' => __('currency.no_of_decimal_integer'),
        ];
    }
}
