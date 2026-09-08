<?php


namespace Modules\Frontend\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Hash;
use Auth;
use Str;
use App\Models\Device;
use App\Models\Setting;
use Modules\Frontend\Trait\LoginTrait;
use App\Models\WebQrSession;


class OTPController extends Controller
{
    use LoginTrait;

    public function otpLogin()
    {
        $userId = auth()->id();

        $settings = Setting::getAllSettings($userId);
        $isOtpLoginEnabled = Setting::where('name', 'is_otp_login')->value('val') == 1;

         // Generate QR token
        $qrSession = WebQrSession::create([
            'session_id' => Str::uuid(),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(5) // expires in 5 mins
        ]);

        // URL for mobile app scan
        // $qrUrl = route('api.web-qr.scan', ['session_id' => $qrSession->session_id]);

        // Generate QR code
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($qrSession->session_id);

        return view('frontend::auth.otp_login', compact('settings', 'isOtpLoginEnabled', 'qrCode', 'qrSession'));
    }


    public function otpLoginStore(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
            'mobile' => 'required|string|max:255|unique:users,mobile',
        ], [
            'email.required' => __('frontend.email_required'),
            'email.email' => __('frontend.email_invalid_format'),
            'email.unique' => __('frontend.email_already_taken'),
            'mobile.required' => __('frontend.mobile_required'),
            'mobile.unique' => __('frontend.mobile_already_exists'),
        ]);

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' =>  $request->email,
            'mobile' =>  $request->mobile,
            'password' => Hash::make(Str::random(8)),
            'user_type' => 'user',
            'login_type' => 'otp',
            'country_code' => $request->country_code
        ];

        $user=User::where('email', $request->email)->first();

        $user = User::create($data);

        $request->session()->regenerate();

        $user->createOrUpdateProfileWithAvatar();

        $user->assignRole($data['user_type']);

        $user->save();

        if($user->login_type == 'otp' )
        {
            Auth::login($user);
            $this->setDevice($user, $request);
        }
        else
        {
            $user=Auth::user();
            Auth::logout();
            $this->removeDevice($user, $request);
           return Redirect::to('/login')->with('error', 'Something went wrong! During login');
        }

        return redirect('/'); // Redirect to intended page
    }

    public function checkUserExists(Request $request)
    {
        $data = $request->all();

        // Use IP address as device_id (old code)
        $current_device = $request->has('device_id') ? $request->device_id : $request->getClientIp();

        $flag = 0;
        $user = User::where('mobile', $request->mobile)->with('subscriptionPackage')->first();

        if(!empty($user))
        {

            if($user->user_type !='user'){

                return response()->json(['message'=>"Admin doesn't have access to login", 'status' => 406]);
            }

            $response=$this->CheckDeviceLimit($user, $current_device);

            if(isset($response['error'])) {
                $devices = Device::where('user_id', $user->id)->get();
                $other_device = $devices->toArray();

                return response()->json([
                    'message' => $response['error'],
                    'status' => 406,
                    'other_device' => $other_device
                ]);
            }

            $this->setDevice($user, $request);

            Auth::login($user);
            $flag = 1;
        }

        return response()->json(['is_user_exists' => $flag, 'url' => route('manage-profile')]);
    }

    /**
     * Simple API to check if mobile number exists before sending OTP
     * Returns: { exists: true/false }
     */
    public function checkMobileExists(Request $request)
    {
        $mobile = $request->input('mobile');
        
        if (empty($mobile)) {
            return response()->json([
                'status' => false,
                'message' => __('frontend.mobile_required')
            ]);
        }

        // Check if mobile exists in database (for OTP login users)
        $user = User::where('mobile', $mobile)->first();

        if ($user) {
            return response()->json([
                'status' => true,
                'message' => __('messages.mobile_is_registered')
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => __('messages.mobile_is_not_registered')
        ]);
    }
}
