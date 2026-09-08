<?php

namespace App\Http\Controllers\Auth\Trait;

use App\Events\Auth\UserLoginSuccess;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Http\Request;

trait AuthTrait
{
    protected function loginTrait(Request $request)
    {
        $email = $request->email;
        $password = $request->password;
        $remember = $request->remember_me;

        // Check if demo login is disabled and user is trying to login as demo/super admin
        $demoLoginEnabled = setting('demo_login', 0);
        if ($demoLoginEnabled != 1) {
            // Block known demo/super admin seeded credentials
            if ($email === 'demo@streamit.com') {
                return ['status' => 403, 'message' => __('messages.demo_login_disabled')];
            }

            // Also check by user_type if user exists
            $user = User::where('email', $email)->first();
            if ($user && $user->user_type === 'demo_admin') {
                return ['status' => 403, 'message' => __('messages.demo_login_disabled')];
            }
        }

        if (Auth::attempt(['email' => $email, 'password' => $password, 'status' => 1], $remember)) {
            $user = auth()->user();
            if($user->hasRole('user')){
                Auth::logout();
               return ['status' => 406, 'message' => 'Unauthorized role & The provided credentials do not match our records'];
            }

            Log::info('Login success (web)', ['user_id' => $user->id, 'ip' => $request->ip()]);
            event(new UserLoginSuccess($request, auth()->user()));
            return ['status' => 200, 'message' => 'Login successful!'];
        }
        Log::info('Login failed: invalid credentials (web)', ['email' => $email, 'ip' => $request->ip()]);
        return false;
    }

    protected function registerTrait(Request $request, $model = null)
    {

        try {
            $request->validate([
                'first_name' => ['required', 'string', 'max:191'],
                'last_name' => ['required', 'string', 'max:191'],
                'email' => ['required', 'string', 'email', 'max:191'],
                'password' => ['required', Rules\Password::defaults()],
                'mobile' => ['required'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();
            return response()->json([
                'status' => false,
                'message' => $firstError ?? 'Validation failed.'
            ], 422);
        }

        $emailExists = User::where('email', $request->email)->exists();

        if ($emailExists) {
            return response()->json(['message' => __('messages.email_already_taken')], 422);
        }

        $mobileExists = User::where('mobile', $request->mobile)->exists();

        if ($mobileExists) {
            return response()->json(['message' => __('messages.mobile_already_taken')], 422);
        }

        if($request->is_ajax == "1"){
            $request->mobile = '+'.$request->country_code.$request->mobile;
        }
        
        $arr = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'password' => Hash::make($request->password),
            'user_type' => 'user',
            'login_type' => $request->login_type,
            'gender' => $request->gender,
            'country_code' => $request->country_code,
        ];
        if (isset($model)) {
            $user = $model::create($arr);
        } else {
            $user = User::create($arr);
        }
        $user->createOrUpdateProfileWithAvatar();
        $user->assignRole($user->user_type);
        $user->save();
        // event(new Registered($user));
        // event(new UserRegistered($user));

        return $user;
    }
}
