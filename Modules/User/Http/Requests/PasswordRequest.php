<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class PasswordRequest extends FormRequest
{
   public function rules()
    {
        $rules = [
            'old_password' => ['required'],
            'password' => [
                'required',
                'min:8',
                'max:14',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_+\-=\[\]{};\':"\\|,.<>\/])[A-Za-z\d@$!%*?&#^()_+\-=\[\]{};\':"\\|,.<>\/]{8,14}$/',
                'confirmed'
            ]
        ];

        return $rules;
    }


    public function messages()
    {
        return [
            'old_password.required' => 'Old password is required.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.max' => 'Password must not exceed 14 characters.',
            'password.regex' => 'Password must be 8-14 characters with at least one uppercase, one lowercase, one digit, and one special character.',
            'password.confirmed' => 'Password confirmation does not match.',
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
