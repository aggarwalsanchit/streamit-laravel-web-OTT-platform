<?php

namespace App\Http\Controllers\Auth\API;

use App\Http\Controllers\Auth\Trait\AuthTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\LoginResource;
use App\Http\Resources\RegisterResource;
use App\Http\Resources\SocialLoginResource;
use App\Models\User;
use Auth;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Models\Device;
use Illuminate\Support\Facades\Mail;
use App\Mail\sendOtp;
use Illuminate\Support\Facades\RateLimiter;
use Jenssegers\Agent\Agent;
use Modules\Subscriptions\Models\Subscription;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Entertainment\Models\UserReminder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\TvLoginSession;
use Modules\NotificationTemplate\Jobs\SendBulkNotification;
use Modules\NotificationTemplate\Notifications\CommonNotification;
use Spatie\Image\Image;
use Exception;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    use AuthTrait;

    
    /**
     * Short Drama is an optional addon. Do not break root auth flows
     * when the addon module/classes are missing.
     */
    private function issueShortDramaSessionTokenIfAvailable(int $userId): ?string
    {
        $serviceClass = \Modules\ShortDrama\Services\DRMService::class;

        try {
            if (! class_exists($serviceClass)) {
                return null;
            }

            $service = app($serviceClass);
            if (! method_exists($service, 'issueSessionToken')) {
                return null;
            }

            return (string) $service->issueSessionToken($userId);
        } catch (\Throwable $e) {
            Log::warning('Short drama session token skipped', [
                'user_id' => $userId,
                'reason' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function register(Request $request)
    {
        $user = $this->registerTrait($request);

        if ($user instanceof \Illuminate\Http\JsonResponse && $user->status() == 422) {
            $responseData = $user->original;
            // If already in correct format, return as is
            if (isset($responseData['status']) && isset($responseData['message'])) {
                return $user;
            }
            // Otherwise format it
            $message = $responseData['message'] ?? 'The email has already been taken.';
            return ApiResponse::error(
                is_array($message) ? collect($message)->flatten()->first() : $message,
                422
            );
        }

        $success['token'] = $user->createToken(setting('app_name'))->plainTextToken;
        $success['name'] = $user->name;
        $userResource = new RegisterResource($user);

        return $this->sendResponse($userResource, __('messages.register_successfull'));
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
   public function login(LoginRequest $request){
    
    if (RateLimiter::tooManyAttempts($request->throttleKey(), 6)) {
        $seconds = RateLimiter::availableIn($request->throttleKey());
        return response()->json([
            'message' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
            'retry_after' => $seconds
        ], 429);
    }

    try {
        \DB::table('sessions')
        ->whereNull('user_id')
        ->where('last_activity', '<', now()->subMinutes(1)->timestamp)
        ->delete();
    } catch (\Exception $e) {
        Log::warning('Session cleanup failed during login', [
            'email' => $request->email ?? null,
            'ip' => $request->ip(),
            'exception' => $e->getMessage(),
        ]);
    }

    $user = User::with(['subscriptionPackage', 'userMultiProfile'])->where('email', request('email'))->first();
        if ($user == null) {
            RateLimiter::hit($request->throttleKey());
            Log::info('Login failed: user not found', ['email' => $request->input('email'), 'ip' => $request->ip()]);
            return ApiResponse::error(__('messages.register_before_login'), 400);
        }

    // Check if demo login is disabled and user is trying to login as demo/super admin
    $demoLoginEnabled = setting('demo_login', 0);
    if ($demoLoginEnabled != 1) {
        // Block known demo/super admin seeded credentials
        if (request('email') === 'demo@streamit.com') {
            RateLimiter::hit($request->throttleKey());
            return ApiResponse::error(__('messages.demo_login_disabled'), 403);
        }

        // Also check by user_type if user exists
        if ($user && $user->user_type === 'demo_admin') {
            RateLimiter::hit($request->throttleKey());
            return ApiResponse::error(__('messages.demo_login_disabled'), 403);
        }
    }

    $remember = $request->boolean('remember');
    if (Auth::attempt(['email' => request('email'), 'password' => request('password')], $remember)) {
        RateLimiter::clear($request->throttleKey());
        $user = Auth::user();
        Log::info('Login success', ['user_id' => $user->id, 'ip' => $request->ip()]);

        if ($request->has('is_ajax') && $request->is_ajax == 1) {
            $agent = new Agent();
            $device_id = $request->getClientIp();
            $device_name = $agent->browser();
            $platform = $agent->platform();
        } else {
            $device_id = $request->device_id;
            $device_name = $request->device_name;
            $platform = $request->platform;
        }
        $response = $this->checkDeviceLimit($user, $device_id, true);
        if ($response) {
            return $response;
        }

    
        if ($user->is_banned == 1 || $user->status == 0) {
            return ApiResponse::error(__('messages.login_error'), 400);
        }

        // Save the user
        $user->save();
        // Name token by device_id so we can revoke per-device later
        $tokenName = !empty($device_id) ? (string)$device_id : setting('app_name');
        $user['api_token'] = $user->createToken($tokenName)->plainTextToken;
        $user['short_drama_session_token'] = $this->issueShortDramaSessionTokenIfAvailable((int) $user->id);

        if ($user->is_subscribe == 1) {
            $user['plan_details'] = $user->subscriptionPackage;
            if (isSmtpConfigured()) {
                // if ($user->subscriptionPackage->device_id != $request->device_id) {
                //     Mail::to($user->email)->send(new DeviceEmail($user));
                // }
            }
        }



        $profile = $user->userMultiProfile->first();
        

        if (!empty($device_id) && !empty($device_name)) {
            $device = Device::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_id' => $device_id
                ],
                [
                    'device_name' => $device_name,
                    'platform' => $platform,
                    'active_profile' => $profile->id ?? null,
                    'session_id' => session()->getId(),
                    'last_activity' => now(),
                ]
            );
        }

        $loginResource = new LoginResource($user);
        $message = __('messages.user_login');

        setCurrentProfileSession();

        if ($request->has('is_ajax') && $request->is_ajax == 1) {

            return $this->sendResponse($loginResource, $message);
        }

        return $this->sendResponse($loginResource, $message);
    } else {
        RateLimiter::hit($request->throttleKey());
        Log::info('Login failed: invalid credentials', ['email' => $request->input('email'), 'ip' => $request->ip()]);
        return $this->sendError(__('messages.not_matched'), ['error' => __('messages.unauthorised')], 200);
    }
}


  public function socialLogin(Request $request)
{
    $input = $request->except('file_url');

    
    if ($input['login_type'] === 'otp') {
        $user_data = User::where('mobile', $input['mobile'])->first();
    } else {
        $user_data = User::where('email', $input['email'])->first();
    }


    if ($user_data != null) {

        $response = $this->checkDeviceLimit($user_data, $request->device_id, false);
        if ($response) {
            return $response;
        }


        if (!isset($user_data->login_type) || $user_data->login_type == '') {
            if ($request->login_type === 'google') {
                $message = __('validation.unique', ['attribute' => 'email']);
            } 

            if(isset($message) && $message != ''){
                return $this->sendError($message,[], 400);
            }
        }
        $message = __('messages.login_success');
    } else {
        if ($request->login_type === 'google' || $request->login_type === 'apple') {
            $key = 'email';
            $value = $request->email;
        } else {
            $key = 'mobile';
            $value = $request->mobile;
        }

        $trashed_user_data = User::with('subscriptionPackage')->where($key, $value)->whereNotNull('login_type')->withTrashed()->first();

        if ($trashed_user_data != null && $trashed_user_data->trashed()) {
            if ($request->login_type === 'google') {
                $message = __('validation.unique', ['attribute' => 'email']);
            } else {
                $message = __('validation.unique', ['attribute' => 'username']);
            }

            return $this->sendError($message, [],400);
        }

        if ($request->login_type === 'otp' && $user_data == null) {
            $otp_response = [
                'status' => true,
                'is_user_exist' => false,
            ];

            return $this->sendError($otp_response,[],400);
        }

        if ($request->login_type === 'otp' && $user_data != null) {
            $otp_response = [
                'status' => true,
                'is_user_exist' => true,
            ];

            return $this->sendError($otp_response,[],400);
        }

        $password = !empty($input['password']) ? $input['password'] : $input['email'];

        $input['user_type'] = $request->user_type;
        $input['display_name'] = $input['first_name'] . ' ' . $input['last_name'];
        $input['password'] = Hash::make($password);
        $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'user';

        $user = User::create($input);

        $user->assignRole($user->user_type);
        $user->save();
        $user->createOrUpdateProfileWithAvatar();
        // if(!empty($input['file_url'])){
        //     $input['file_url'] = $input['file_url'];
        // $user->update(['file_url' => $input['file_url']]);

        // }
        $user_data = User::where('id', $user->id)->first();

        $message = trans('messages.save_form', ['form' => $input['user_type']]);
    }

    if (!empty($request->device_id) && !empty($request->device_name)) {
        $device = Device::updateOrCreate(
            [
                'user_id' => $user_data->id,
                'device_id' => $request->device_id,
            ],
            [
                'device_name' => $request->device_name,
                'platform' => $request->platform,
                'session_id' => session()->getId(),
                'last_activity' => now(),
            ]
        );
    }


    $tokenName = $request->device_id ?: 'auth_token';
    $user_data['api_token'] = $user_data->createToken($tokenName)->plainTextToken;
    $user_data['short_drama_session_token'] = $this->issueShortDramaSessionTokenIfAvailable((int) $user_data->id);

    if ($user_data->is_subscribe == 1) {
        $user_data['plan_details'] = $user_data->subscriptionPackage;
    }

    $socialLogin = new SocialLoginResource($user_data);

    return $this->sendResponse($socialLogin, $message);
}

protected function checkDeviceLimit(User $user, string $deviceId = null, bool $isLoginFlow = true): ?JsonResponse 
{
    // For login flow: delete old devices first, then check if existing device exists
    if ($isLoginFlow) {
        Device::where('user_id', $user->id)
            ->whereIn('platform', ['Windows', 'Linux', 'Mac', 'web'])
            ->where('updated_at', '<', now()->subDays(2))
            ->delete();

        // Check if device already exists - if yes, skip limit check
        if ($deviceId) {
            $existingDevice = Device::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->first();
            
            if ($existingDevice) {
                return null; // Allow login for existing device
            }
        }
    }

    $count = Device::where('user_id', $user->id)->count();

    $devicesQuery = Device::where('user_id', $user->id);

    // For socialLogin: exclude current device from query
    if (!$isLoginFlow && $deviceId) {
        $devicesQuery->where('device_id', '!=', $deviceId);
    }

    $devices = $devicesQuery->get();

    // Format devices - include session_id and last_activity for login flow
    $other_device = $devices->map(function ($device) use ($isLoginFlow) {
        $formatted = [
            'id' => $device->id,
            'user_id' => $device->user_id,
            'device_id' => $device->device_id,
            'device_name' => $device->device_name,
            'active_profile' => $device->active_profile,
            'platform' => $device->platform,
            'created_at' => formatDateTimeWithTimezone($device->created_at),
            'updated_at' => formatDateTimeWithTimezone($device->updated_at),
            'deleted_at' => $device->deleted_at,
        ];
        
        // Add session_id and last_activity for login flow (matching original logic)
        if ($isLoginFlow) {
            $formatted['session_id'] = session()->getId();
            $formatted['last_activity'] = now();
        }
        
        return $formatted;
    });


    if ($user->subscriptionPackage) {
        $subscription = $user->subscriptionPackage;
        
        if (isset($subscription->plan_type) && !empty($subscription->plan_type)) {
            $planLimitations = json_decode($subscription->plan_type, true);

            if (is_array($planLimitations)) {
                foreach ($planLimitations as $limitation) {
                    if (isset($limitation['slug']) && $limitation['slug'] === 'device-limit') {
                        if (isset($limitation['limitation_value']) && $limitation['limitation_value'] == 1) {
                            $limitData = $limitation['limit'] ?? null;
                            $limit = 0;
                          
                            
                            if (is_array($limitData) && isset($limitData['value'])) {
                                $limit = (int)$limitData['value'];
                            } elseif (is_string($limitData) || is_numeric($limitData)) {
                                $limit = (int)$limitData;
                            }
                            
                            if ($count >= $limit) {
                                Auth::logout();

                                // Use response()->json for login flow to match original format
                                if ($isLoginFlow) {
                                    return response()->json([
                                        'error' => __('messages.device_limit_reached'),
                                        'other_device' => $other_device
                                    ], 406);
                                } else {
                                    return ApiResponse::error(__('messages.your_device_limit_has_been_reached'),
                                        406,
                                        null,
                                        ['other_device' => $other_device]
                                    );
                                }
                            }
                        }
                        break;
                    }
                }
            }
        }
    } else {
        // No subscription: check default limit
        $defaultLimit = $isLoginFlow ? 1 : config('constant.DEVICE_LIMIT', 1);
        $shouldCheck = $isLoginFlow ? ($count >= $defaultLimit) : ($count >= $defaultLimit && $other_device->isNotEmpty());
        
        if ($shouldCheck) {
            Auth::logout();

            // Use response()->json for login flow to match original format
            if ($isLoginFlow) {
                return response()->json([
                    'error' => __('messages.device_limit_reached'),
                    'other_device' => $other_device
                ], 406);
            } else {
                return ApiResponse::error(__('messages.device_limit_reached'), 406, null, ['other_device' => $other_device]);
            }
        }
    }
    
    return null; // ✅ allow login
}

    /**
     * Get fresh CSRF token for AJAX requests
     * This endpoint works for stateful requests (same-origin) where sessions are available
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCsrfToken(Request $request)
    {
        try {
            if ($request->hasSession()) {
                return response()->json([
                    'csrf_token' => csrf_token()
                ]);
            } else {
                try {
                    $request->session()->start();
                    return response()->json([
                        'csrf_token' => csrf_token()
                    ]);
                } catch (\Exception $e) {
                    // Session not available (e.g., from mobile app)
                    // Mobile apps don't need CSRF tokens, they use Sanctum tokens
                    return response()->json([
                        'csrf_token' => null,
                        'message' => 'CSRF token not available for this request type'
                    ], 200);
                }
            }
        } catch (\Exception $e) {
            return response()->json([
                'csrf_token' => null,
                'message' => 'Could not generate CSRF token'
            ], 200);
        }
    }

    public function logout(Request $request)
    {
        // Check if the user is authenticated
        if (!Auth::guard('sanctum')->check()) {
            return ApiResponse::error(__('messages.user_not_logged_in'), 400);
        }

        $user = Auth::guard('sanctum')->user();

        $user->tokens()->delete();


        $tvsession = TvLoginSession::where('user_id', $user->id)->first();
        if($tvsession){
            $tvsession->delete();
        }

        if ($request->has('device_id') && !empty($request->device_id)) {
            // Delete the specific device record if device_id is provided
            Device::where('user_id', $user->id)->where('device_id', $request->device_id)->delete();
        } else {
            // Delete all devices associated with the user
            Device::where('user_id', $user->id)->delete();
        }

        if ($request->is('api*')) {
            return ApiResponse::success(null, __('messages.user_logout'), 200);
        }
    }


    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
   
        if (!$user) {
            return ApiResponse::error(
                __('messages.this_email_is_not_registered_please_check_your_email_address'),
                404
            );
        }
        
        if ($user->login_type == 'otp' || $user->login_type =='google') {
           
            return ApiResponse::error(
                __('messages.user_does_not_have_permission_to_change_password'),
                404
            );
        }


        // try {
            $response = Password::sendResetLink($request->only('email'));

            return $response === Password::RESET_LINK_SENT
                ? ApiResponse::success(null, __($response), 200)
                : ApiResponse::error(__($response), 400);
        // } catch (Exception $e) {

        //     return response()->json([
        //         'message' => 'There was an issue sending the email. Please check your SMTP configuration.',
        //         'status' => false
        //     ], 500);
        // }
    }



    // public function forgotPassword(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|email|exists:users,email',
    //     ]);

    //     try {
    //         $response = Password::sendResetLink($request->only('email'));

    //         if ($response === Password::RESET_LINK_SENT) {
    //             return response()->json(['message' => 'Password reset link sent successfully.', 'status' => true], 200);
    //         }

    //         return response()->json(['message' => 'Failed to send password reset link.', 'status' => false], 400);
    //     } catch (Exception $e) {
    //         Log::error('SMTP Error: ' . $e->getMessage());

    //         return response()->json([
    //             'message' => 'There was an issue sending the email. Please check your SMTP configuration.',
    //             'status' => false
    //         ], 500);
    //     }
    // }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8',
        ]);
        $user = \Auth::user();
        $user = User::where('id', $user->id)->first();
        if ($user == '') {
            return ApiResponse::error(__('messages.user_notfound'), 400);
        }

        $hashedPassword = $user->password;

        $match = Hash::check($request->old_password, $hashedPassword);

        if ($request->new_password == $request->old_password) {
            $message = __('messages.old_new_pass_same');

            return ApiResponse::error(__('messages.same_pass'), 400);
        }
        
        if ($match) {

            $user->fill([
                'password' => Hash::make($request->new_password),
            ])->save();

            $success['api_token'] = $user->createToken(setting('app_name'))->plainTextToken;
            $success['name'] = $user->name;

            $data = [
                'notification_type' => 'change_password', // Use your template type
                'user_id' => $user->id,
                'user_name' => $user->full_name,
            ];
            SendBulkNotification::dispatch($data)->onQueue('notifications');


            return ApiResponse::success($success, __('messages.pass_successfull'), 200);
        } else {
            return ApiResponse::error(__('messages.check_old_password'), 200);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        // if ($request->has('id') && !empty($request->id)) {
        //     $user = User::where('id', $user->id)->first();
        // }
        if ($user == null) {

            return ApiResponse::error(__('messages.no_record'), 400);
        }

        try {
            $request->validate([
                'email' => 'required|email|unique:users,email,' . $user->id,
                'mobile' => 'required|unique:users,mobile,' . $user->id,
            'date_of_birth' => 'required|date|before_or_equal:today',
            ]);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();
            
            return ApiResponse::error(
                $firstError ?? __('messages.validation_error'),
                422,
                $errors
            );
        }

        $data = $request->except(['is_banned', 'status', 'user_type', 'password', 'id', 'email_verified_at', 'is_subscribe','pin','otp','is_parental_lock_enable','deleted_at','created_at','updated_at','file_url']);

        $user->update($data);
        if ($request->hasFile('file_url')) {
            $file = $request->file('file_url');

            $file = $this->stripExif($file);
           $activeDisk = env('ACTIVE_STORAGE', 'local');

           $filename = $file->getClientOriginalName();

           if ($activeDisk == 'local') {
            $destinationPath = 'streamit-laravel';
            $filePath = $file->storeAs($destinationPath, $filename, 'public');
            $file_url = '/storage/' . $filePath;

        } else {

            $folderPath = 'streamit-laravel/' .  $filename ;
            Storage::disk( $activeDisk )->put($folderPath, file_get_contents($file));
            $baseUrl = env('DO_SPACES_URL');
            $file_url = $baseUrl . '/' . $folderPath;
        }

            $data['file_url']=extractFileNameFromUrl($file_url,'users');

        } else {
            $data['file_url'] = $user->file_url;
        }
        $user->update(['file_url' => $data['file_url']]);
        $user_data = User::find($user->id);
        $user_data->save();

        $message = __('messages.profile_update');
        $user_data['user_role'] = $user->getRoleNames();
        $user_data['file_url'] = setBaseUrlWithFileName($user->file_url, 'image', 'users');

        unset($user_data['roles']);
        unset($user_data['media']);

        $data = $user_data->toArray();
        $data['date_of_birth'] = $user_data->date_of_birth
            ? \Carbon\Carbon::parse($user_data->date_of_birth)->format('Y-m-d')
            : null;
        unset($data['roles'], $data['media']);

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => $message,
        ], 200);
    }

    public function userDetails(Request $request)
    {
        $userID = $request->id;
        $user = User::find($userID);
        $user['about_self'] = $user->profile->about_self ?? null;
        $user['expert'] = $user->profile->expert ?? null;
        $user['facebook_link'] = $user->profile->facebook_link ?? null;
        $user['instagram_link'] = $user->profile->instagram_link ?? null;
        $user['twitter_link'] = $user->profile->twitter_link ?? null;
        $user['dribbble_link'] = $user->profile->dribbble_link ?? null;

        if (!$user) {
            return ApiResponse::error(__('messages.user_notfound'), 404);
        }

        return ApiResponse::success($user, __('messages.user_details_successfull'), 200);
    }
    private function stripExif($file)
{
    try {
        if (!str_starts_with($file->getMimeType(), 'image/')) {
            return $file;
        }

        $tempPath = sys_get_temp_dir() . '/' . uniqid('clean_') . '_' . $file->getClientOriginalName();

        Image::load($file->getRealPath())
            ->optimize()
            ->save($tempPath);
        return new \Illuminate\Http\UploadedFile(
            $tempPath,
            $file->getClientOriginalName(),
            $file->getMimeType(),
            null,
            true
        );

    } catch (\Throwable $e) {
        \Log::warning("Could not strip EXIF: " . $e->getMessage());
        return $file;
    }
}

    public function deleteAccount(Request $request)
    {
        $user_id = \Auth::user()->id;
        $user = User::with('userMultiProfile')->where('id', $user_id)->first();
        if ($user == null) {
            $message = __('messages.user_not_found');

            return ApiResponse::error($message, 200);
        }
        Device::where('user_id', $user->id)->forceDelete();
        $user->userMultiProfile()->forceDelete();
        Subscription::where('user_id', $user->id)->update(['status' => 'deactivated']);
        User::where('id', $user->id)->forceDelete();
        ContinueWatch::where('user_id', $user->id)->delete();
        Watchlist::where('user_id',$user->id)->delete();
        EntertainmentDownload::where('user_id',$user->id)->delete();
        UserReminder::where('user_id', $user->id)->delete();

        $user->forceDelete();

        $message = __('messages.delete_account');

        return ApiResponse::success(null, $message, 200);
    }


    // public function changePin(Request $request)
    // {
    //     (isset($request->pin) && is_array($request->pin)) &&
    //     $request->merge([
    //         'pin' => isset($request->pin) ? implode("",$request->pin): NULL,
    //         'confirm_pin' => isset($request->confirm_pin) ? implode("",$request->confirm_pin) : NULL,
    //     ]);

    //     $request->validate([
    //         'pin' => 'required|min:4|max:4',
    //         'confirm_pin' => 'required_with:pin|same:pin|min:4|max:4'
    //     ]);

    //     $userId = isset($request->user_id) ? $request->user_id : auth()->user()->id;

    //     if(empty($userId)){
    //         return response()->json(['status' => false, 'message' => __('frontend.something_went_wrong')]);
    //     }
    //     $user = User::find($userId);

    //     if (!empty($user->pin) && $user->pin === $request->pin) {
    //         return response()->json(['status' => false, 'message' => __('frontend.new_pin_must_be_different')]);
    //     }

    //     $message = (!empty($user->otp)) ? __('messages.change_pin_successfull') : __('messages.set_pin_successfull');

    //     $user->update([
    //         'pin' => $request->pin,
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => $message,
    //     ], 200);
    // }
    public function changePin(Request $request)
    {
        (isset($request->pin) && is_array($request->pin)) &&
        $request->merge([
            'pin' => isset($request->pin) ? implode("",$request->pin): NULL,
            'confirm_pin' => isset($request->confirm_pin) ? implode("",$request->confirm_pin) : NULL,
        ]);

        $request->validate([
            'pin' => 'required|min:4|max:4',
            'confirm_pin' => 'required_with:pin|same:pin|min:4|max:4'
        ]);

        $userId = isset($request->user_id) ? $request->user_id : auth()->user()->id;

        if(empty($userId)){
            return ApiResponse::error(__('frontend.something_went_wrong'), 400);
        }
        $user = User::find($userId);

        if (!empty($user->pin) && $user->pin === $request->pin) {
            return ApiResponse::error(__('frontend.new_pin_must_be_different'), 400);
        }

        $message = (!empty($user->otp)) ? __('messages.change_pin_successfull') : __('messages.set_pin_successfull');
        
        $isNewPin = empty($user->pin);
        
        $updateData = [
            'pin' => $request->pin,
        ];
        
        if ($isNewPin) {
            $updateData['is_parental_lock_enable'] = 1;
        }
        
        $user->update($updateData);

        return ApiResponse::success(
            null,
            $message,
            200,
            ['is_parental_lock_enable' => $user->is_parental_lock_enable]
        );
    }

    /**
     * otp resend/send on email
     * @param $request
     * @return \Illuminate\Http\Response
     */
    public function sendOtp(Request $request)
    {
        $userId = isset($request->user_id) ? $request->user_id : auth()->user()->id;
        if(empty($userId)){
            return ApiResponse::error('Something went wrong please try again.!', 422);
        }
        $user = User::find($userId);
        if($user){
            
            $otp = rand(config('constant.OTP_MIN'),config('constant.OTP_MAX'));
         
            $user->update(['otp' => $otp]);

            try {
                $notificationData = [
                    'user_type' => 'user',
                    'user_id' => $user->id,
                    'user_name' => $user->full_name ?? $user->first_name . ' ' . $user->last_name,
                    'otp' => $otp,
                    'notification_type' => 'parental_control_otp',
                ];

                $user->notify((new CommonNotification('parental_control_otp', $notificationData))->onQueue('notifications'));
            } catch (\Exception $e) {
                \Log::error('OTP Notification Error: ' . $e->getMessage());
                $bodyData = ['body' => 'Change Your Pin OTP is : '. $otp];
                Mail::to($user->email)->send(new sendOtp($bodyData));
            }

            return ApiResponse::success(null, "OTP sent successfully", 200);
        }else{
            return ApiResponse::error('Something went wrong please try again.!', 422);
        }
    }

    /**
     * verify email otp
     * @param $request
     * @return \Illuminate\Http\Response
     */
    public function verifyOtp(Request $request)
    {
        $userId = isset($request->user_id) ? $request->user_id : auth()->user()->id;
        if(empty($userId)){
            return ApiResponse::error(__('messages.something_went_wrong'), 422);
        }

        (isset($request->otp) && is_array($request->otp)) &&
        $request->merge([
            'otp' => isset($request->otp) ? implode("",$request->otp): NULL
        ]);

        $request->validate([
            'otp' => 'required|min:4|max:4'
        ]);

        $user  = User::where([['id','=',$userId],['otp','=',$request->otp]])->first();
        if($user){

            return ApiResponse::success(null, __('messages.otp_verified_successfully'), 200);
        } else{
            return ApiResponse::error(__('messages.invalid_otp'), 200);
        }
    }

    /**
     * verify pin
     * @param $request
     * @return \Illuminate\Http\Response
     */
    public function verifyPin(Request $request)
    {

        $userId = isset($request->user_id) ? $request->user_id : auth()->user()->id;
        if(empty($userId)){
            return ApiResponse::error('Something went wrong please try again.!', 422);
        }

        (isset($request->pin) && is_array($request->pin)) &&
        $request->merge([
            'pin' => isset($request->pin) ? implode("",$request->pin): NULL
        ]);

        $request->validate([
            'pin' => 'required'
        ]);

        $user  = User::where([['id','=',$userId],['pin','=',$request->pin]])->first();
        if($user){
            return ApiResponse::success(null, "Pin verified successfully", 200);
        } else{
            return ApiResponse::error('Invalid Pin', 400);
        }
    }

    /**
     * change parental pin flag
     * @param $request
     * @return \Illuminate\Http\Response
     */
    public function changeParentalLock(Request $request)
    {
        $userId = isset($request->user_id) ? $request->user_id : auth()->user()->id;
        if(empty($userId)){
            return ApiResponse::error('Something went wrong please try again.!', 422);
        }

        $request->validate([
            'is_parental_lock_enable' => 'required|in:0,1'
        ]);

        $user  = User::where('id',$userId)->update([
            'is_parental_lock_enable' => $request->is_parental_lock_enable
        ]);
        if($user){
            $message = $request->is_parental_lock_enable == 1
                ? __('messages.parental_lock_active_successfully')
                : __('messages.parental_lock_inactive_successfully');

            return ApiResponse::success(null, $message, 200);
        } else{
            return ApiResponse::error('Something went wrong please try again.!', 422);
        }
    }
}
