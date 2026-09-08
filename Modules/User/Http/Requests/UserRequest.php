<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UserRequest extends FormRequest
{
   public function rules()
    {
        $rules = [
            'first_name' => ['required'],
            'last_name' => ['required'],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($this->route('user'))
            ],
            'mobile' => ['required'], 
            'gender' => ['required', 'in:male,female,other'],
            'date_of_birth' => ['required']
        ];


        if ($this->isMethod('post')) {
            $rules['password'] = [
                'required', 
                'min:8', 
                'max:14',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_+\-=\[\]{};\':"\\|,.<>\/])[A-Za-z\d@$!%*?&#^()_+\-=\[\]{};\':"\\|,.<>\/]{8,14}$/',
                'confirmed'
            ];
        }

        return $rules;
    }


    public function messages()
    {
        return [
            'first_name.required' => __('messages.first_name_required'),
            'last_name.required' => __('messages.last_name_required'),
            'email.required' => __('messages.email_required'),
            'email.email' => __('messages.email_invalid'),
            'email.unique' => __('messages.email_unique'),
            'password.required' => __('messages.password_field_required'),
            'password.min' => __('messages.password_min'),
            'password.max' => __('messages.password_max'),
            'password.regex' => __('messages.password_requirements'),
            'password.confirmed' => __('messages.passwords_do_not_match'),
            'gender.required' => __('messages.gender_required'),
            'mobile.required' => __('messages.mobile_required'),
            'gender.in' => __('messages.gender_invalid'),
            'date_of_birth.required' => __('messages.date_of_birth_required'),
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
