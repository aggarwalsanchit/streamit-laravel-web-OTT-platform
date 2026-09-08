<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Setting;
use Illuminate\Http\Request;
use Modules\Currency\Models\Currency;
use Modules\Tax\Models\Tax;
use App\Models\MobileSetting;
use Modules\Subscriptions\Models\Subscription;
use App\Models\Device;
use Modules\Subscriptions\Models\PlanLimitation;

class SettingController extends Controller
{
    public function appConfiguraton(Request $request)
    {
        $header = request()->headers->all();
        $device_type = !empty($header['device-type'])? $header['device-type'][0] : []; //for tv
        $settings = Setting::all()->pluck('val', 'name');

        $response = [];
        // Define the specific names you want to include
        $specificNames = ['app_name', 'footer_text', 'primary','razorpay_secretkey', 'razorpay_publickey', 'stripe_secretkey', 'stripe_publickey', 'paystack_secretkey', 'paystack_publickey', 'paypal_secretkey', 'paypal_clientid', 'flutterwave_secretkey', 'flutterwave_publickey', 'onesignal_app_id', 'onesignal_rest_api_key', 'onesignal_channel_id', 'google_maps_key', 'helpline_number', 'copyright', 'inquriy_email', 'site_description', 'customer_app_play_store', 'customer_app_app_store', 'isForceUpdate', 'version_code','cinet_siteid','cinet_api_key','cinet_Secret_key','sadad_Sadadkey','sadad_id_key','sadad_Domain','airtel_money_secretkey','airtel_money_client_id','phonepe_App_id','phonepe_Merchant_id','phonepe_salt_key','phonepe_salt_index','midtrans_client_id','midtrans_server_key','iap_entitlement_id','iap_apple_api_key','iap_google_api_key'];

        foreach ($settings as $name => $value) {
            if (in_array($name, $specificNames)) {
                if (strpos($name, 'razorpay_') === 0 && $settings['razor_payment_method'] == 1) {
                    $nestedKey = 'razor_pay';

                    $nestedName = str_replace('', 'razorpay_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'stripe_') === 0 && $settings['str_payment_method'] == 1) {
                    $nestedKey = 'stripe_pay';
                    $nestedName = str_replace('', 'stripe_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'paystack_') === 0 && $settings['paystack_payment_method'] == 1) {
                    $nestedKey = 'paystack_pay';
                    $nestedName = str_replace('', 'paystack_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'paypal_') === 0 && $settings['paypal_payment_method'] == 1) {
                    $nestedKey = 'paypal_pay';
                    $nestedName = str_replace('', 'paypal_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'flutterwave_') === 0 && $settings['flutterwave_payment_method'] == 1) {
                    $nestedKey = 'flutterwave_pay';
                    $nestedName = str_replace('', 'flutterwave_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;

                }elseif (strpos($name, 'cinet_') === 0 && $settings['cinet_payment_method'] == 1) {
                    $nestedKey = 'cinet_pay';
                    $nestedName = str_replace('', 'cinet_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'sadad_') === 0 && $settings['sadad_payment_method'] == 1) {
                    $nestedKey = 'sadad_pay';
                    $nestedName = str_replace('', 'sadad_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'airtel_') === 0 && $settings['airtel_payment_method'] == 1) {
                    $nestedKey = 'airtel_pay';
                    $nestedName = str_replace('', 'airtel_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'phonepe_') === 0 && $settings['phonepe_payment_method'] == 1) {
                    $nestedKey = 'phonepe_pay';
                    $nestedName = str_replace('', 'phonepe_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'midtrans_') === 0 && $settings['midtrans_payment_method'] == 1) {
                    $nestedKey = 'midtrans_pay';
                    $nestedName = str_replace('', 'midtrans_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }

                if (!strpos($name, 'onesignal_') === 0) {
                    $response[$name] = $value;
                } elseif (!strpos($name, 'stripe_') === 0) {
                    $response[$name] = $value;
                } elseif (!strpos($name, 'razorpay_') === 0) {
                    $response[$name] = $value;
                }
            }
        }

        // Fetch currency data
        $currency = Currency::where('is_primary',1)->first();

        $currencyData = null;
        if ($currency) {

            $currencyData = [
                'currency_name' => $currency->currency_name,
                'currency_symbol' => $currency->currency_symbol,
                'currency_code' => $currency->currency_code,
                'currency_position' => $currency->currency_position,
                'no_of_decimal' => $currency->no_of_decimal,
                'thousand_separator' => $currency->thousand_separator,
                'decimal_separator' => $currency->decimal_separator,
            ];
        }

        $taxes = Tax::active()->get();
        $ads_val= MobileSetting::where('slug', 'banner')->first();
        $rate_our_app= MobileSetting::where('slug', 'rate-our-app')->first();
        $ads_val= MobileSetting::where('slug', 'banner')->first();
        $continue_watch= MobileSetting::where('slug', 'continue-watching')->first();
        $VideoCast= PlanLimitation::where('slug','video-cast')->first();
        $downloadOption= PlanLimitation::where('slug','download-status')->first();


        // if (isset($settings['isForceUpdate']) && isset($settings['version_code'])) {
        //     $response['isForceUpdate'] = intval($settings['isForceUpdate']);

        //     $response['version_code'] = intval($settings['version_code']);
        // } else {
        //     $response['isForceUpdate'] = 0;

        //     $response['version_code'] = 0;
        // }
        if(!empty($request->user_id)){
            $getDeviceTypeData = Subscription::checkPlanSupportDevice($request->user_id , $device_type);
            $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
        }
        if(isset($settings['mobile_app']) && $settings['mobile_app']== 1){
           $mobileAppVersion['mobile_app_versions'] = [
                'android' => [
                    'minimum_required_version' => $settings['android_minimum_required_version'] ?? null,
                    'latest_version' => $settings['android_latest_version'] ?? null,
                ],
                'ios' => [
                    'minimum_required_version' => $settings['ios_minimum_required_version'] ?? null,
                    'latest_version' => $settings['ios_latest_version'] ?? null,
                ],
            ];
        }


        if(isset($settings['tv_app']) && $settings['tv_app']== 1){
           $response['tv_app_versions'] = [
                'android_tv' => [
                    'minimum_required_version' => $settings['android_tv_minimum_required_version'] ?? null,
                    'latest_version' => $settings['android_tv_latest_version'] ?? null,
                ]
            ];
        }

        $response['tax'] = $taxes;

        $response['currency'] = $currencyData;
        $response['google_login_status'] = (int)$settings['is_google_login'] ?? 0;
        $response['apple_login_status'] = (int)$settings['is_apple_login'] ?? 0;
        $response['otp_login_status'] = (int)$settings['is_otp_login'] ?? 0;
        $response['site_description'] = $settings['site_description'] ?? null;
        $response['enable_social_login'] = isset($settings['is_social_login']) ? ($settings['is_social_login'] == '1') : false;
        // Add locale language to the response
        $response['application_language'] = app()->getLocale();
        $response['status'] = true;
        $response['enable_movie'] = isset($settings['movie']) ? intval($settings['movie']) : 0;
        $response['enable_livetv'] = isset($settings['livetv']) ? intval($settings['livetv']) : 0;
        $response['enable_tvshow'] = isset($settings['tvshow']) ? intval($settings['tvshow']) : 0;
        $response['enable_video'] = isset($settings['video']) ? intval($settings['video']) : 0;
        $response['enable_ads'] = isset($ads_val->value) ? (int) $ads_val->value : 0;
        $response['continue_watch'] = isset($continue_watch->value) ? (int) $continue_watch->value : 0;
        $response['enable_rate_us'] = isset($rate_our_app->value) ? (int) $rate_our_app->value : 0;
        $response['is_login'] = 0;
        $response['is_casting_available'] = isset($VideoCast) ? ($VideoCast['status'] ?? 0) : 0;
        $response['is_download_available'] = isset($downloadOption) ? ($downloadOption['status'] ?? 0) : 0;
        $response['enable_in_app'] = isset($settings['iap_payment_method']) ? intval($settings['iap_payment_method']) : 0;
        $response['entitlement_id'] = isset($settings['iap_entitlement_id']) ? $settings['iap_entitlement_id'] : null;
        $response['apple_api_key'] = isset($settings['iap_apple_api_key']) ? $settings['iap_apple_api_key'] : null;
        $response['google_api_key'] = isset($settings['iap_google_api_key']) ? $settings['iap_google_api_key'] : null;
        $response['banner_ad_id'] = isset($settings['banner_ad_id']) ? $settings['banner_ad_id'] : null;
        $response['ios_banner_id'] = isset($settings['ios_banner_id']) ? $settings['ios_banner_id'] : null;
        $response['force_update'] = isset($settings['force_update']) ? (int)$settings['force_update'] : 0;
        $response['mobile_app'] = isset($settings['mobile_app']) ? $mobileAppVersion['mobile_app_versions'] ?? null : null;
        $response['tv_app'] = isset($settings['tv_app']) ? $response['tv_app_versions'] ?? null : null;


        if ($request->has('device_id') && $request->device_id != null && $request->has('user_id') && $request->user_id) {
            $device = Device::where('user_id', $request->user_id)
                ->where('device_id', $request->device_id)
                ->first();

            $response['is_login'] = $device ? 1 : 0;
        }
        if(!empty($request->user_id)){
            $response['is_device_supported'] = $deviceTypeResponse['isDeviceSupported'];
        }
        return ApiResponse::custom($response, 200);
    }
     public function appConfiguratonV3(Request $request)
    {
        $header = request()->headers->all();
        $device_type = !empty($header['device-type'])? $header['device-type'][0] : []; //for tv
        $settings = Setting::all()->pluck('val', 'name');

        $response = [];

        // Fetch currency data
        $currency = Currency::where('is_primary',1)->first();

        $currencyData = null;
        if ($currency) {

            $currencyData = [
                'currency_name' => $currency->currency_name,
                'currency_symbol' => $currency->currency_symbol,
                'currency_code' => $currency->currency_code,
                'currency_position' => $currency->currency_position,
                'no_of_decimal' => $currency->no_of_decimal,
                'thousand_separator' => $currency->thousand_separator,
                'decimal_separator' => $currency->decimal_separator,
            ];
        }

        $taxes = Tax::select('id','title','type','value','status')->active()->get()->makeHidden(['feature_image']);
        $ads_val= MobileSetting::where('slug', 'banner')->first();
        $rate_our_app= MobileSetting::where('slug', 'rate-our-app')->first();
        $ads_val= MobileSetting::where('slug', 'banner')->first();
        $continue_watch= MobileSetting::where('slug', 'continue-watching')->first();
        $VideoCast= PlanLimitation::where('slug','video-cast')->first();
        $downloadOption= PlanLimitation::where('slug','download-status')->first();


        // if (isset($settings['isForceUpdate']) && isset($settings['version_code'])) {
        //     $response['isForceUpdate'] = intval($settings['isForceUpdate']);

        //     $response['version_code'] = intval($settings['version_code']);
        // } else {
        //     $response['isForceUpdate'] = 0;

        //     $response['version_code'] = 0;
        // }
        if(!empty($request->user_id)){
            $getDeviceTypeData = Subscription::checkPlanSupportDevice($request->user_id , $device_type);
            $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
        }
        if(isset($settings['mobile_app']) && $settings['mobile_app']== 1){
           $mobileAppVersion['mobile_app_versions'] = [
                'android' => [
                    'minimum_required_version' => $settings['android_minimum_required_version'] ?? null,
                    'latest_version' => $settings['android_latest_version'] ?? null,
                ],
                'ios' => [
                    'minimum_required_version' => $settings['ios_minimum_required_version'] ?? null,
                    'latest_version' => $settings['ios_latest_version'] ?? null,
                ],
            ];
        }


        if(isset($settings['tv_app']) && $settings['tv_app']== 1){
           $response['tv_app_versions'] = [
                'android_tv' => [
                    'minimum_required_version' => $settings['android_tv_minimum_required_version'] ?? null,
                    'latest_version' => $settings['android_tv_latest_version'] ?? null,
                ]
            ];
        }

        $bannerIds = [
            'ios_banner_ad_id' => isset($settings['ios_banner_id'] ) ? $settings['ios_banner_id'] : null,
            'android_banner_ad_id' => isset($settings['banner_ad_id'] )? $settings['banner_ad_id'] : null
        ];
        $application_url =  [
            'mobile_app' =>[
                'playstore_url' => $settings['android_url'] ?? "",
                'appStore_url' => $settings['ios_url'] ?? "",
            ],
             'tv_app' => [
                'playstore_url'  => $settings['android_tv_url'] ?? "",
            ]
        ];

        $response['tax'] = $taxes;

        $response['currency'] = $currencyData;
        $response['google_login_status'] = (int)$settings['is_google_login'] ?? 0;
        $response['apple_login_status'] = (int)$settings['is_apple_login'] ?? 0;
        $response['otp_login_status'] = (int)$settings['is_otp_login'] ?? 0;
        $response['site_description'] = $settings['site_description'] ?? null;
        $response['enable_social_login'] = isset($settings['is_social_login']) && ($settings['is_social_login'] == '1') ? 1 : 0;
        // Add locale language to the response
        $response['application_language'] = app()->getLocale();
        $response['status'] = true;
        $response['enable_movie'] = isset($settings['movie']) ? intval($settings['movie']) : 0;
        $response['enable_livetv'] = isset($settings['livetv']) ? intval($settings['livetv']) : 0;
        $response['enable_tvshow'] = isset($settings['tvshow']) ? intval($settings['tvshow']) : 0;
        $response['enable_video'] = isset($settings['video']) ? intval($settings['video']) : 0;
        $response['enable_ads'] = isset($ads_val->value) ? (int) $ads_val->value : 0;
        $response['continue_watch'] = isset($continue_watch->value) ? (int) $continue_watch->value : 0;
        $response['enable_rate_us'] = isset($rate_our_app->value) ? (int) $rate_our_app->value : 0;
        $response['is_login'] = 0;
        $response['is_casting_available'] = isset($VideoCast) ? ($VideoCast['status'] ?? 0) : 0;
        $response['is_download_available'] = isset($downloadOption) ? ($downloadOption['status'] ?? 0) : 0;
        $response['application_url'] = $application_url;
        $response['banner_ads'] = $bannerIds;
        $response['app_mini_logo'] = isset($settings['mini_logo']) ? $settings['mini_logo'] : asset('img/logo/mini_logo.png');
        $response['app_logo'] = isset($settings['dark_logo']) ? $settings['dark_logo'] : asset('img/logo/dark_logo.png');
        $response['app_favicon'] = isset($settings['favicon']) ? $settings['favicon'] : asset('img/logo/favicon.png');
        $response['app_loader'] = isset($settings['loader_gif']) ? $settings['loader_gif'] : asset('img/logo/loader.gif');
        $response['force_update'] = isset($settings['force_update']) ? (int)$settings['force_update'] : 0;
        $response['mobile_app'] = isset($settings['mobile_app']) ? $mobileAppVersion['mobile_app_versions'] ?? null : null;
        $response['tv_app'] = isset($settings['tv_app']) ? $response['tv_app_versions'] ?? null : null;
        $response['enable_demo_login'] = isset($settings['demo_login']) ? (int)$settings['demo_login'] : 0;
        $response['video_forward_seek_seconds'] = isset($settings['forward_seconds']) ? (int)$settings['forward_seconds'] : 0;
        $response['video_backward_seek_seconds'] = isset($settings['backward_seconds']) ? (int)$settings['backward_seconds'] : 0;
        $response['date_format'] = isset($settings['default_date_format']) ? $settings['default_date_format'] : 'Y-m-d';
        $response['time_format'] = isset($settings['default_time_format']) ? $settings['default_time_format'] : 'H:i:s';
        $response['default_timezone'] = isset($settings['default_time_zone']) ? $settings['default_time_zone'] : 'UTC';
        if ($request->has('device_id') && $request->device_id != null && $request->has('user_id') && $request->user_id) {
            $device = Device::where('user_id', $request->user_id)
                ->where('device_id', $request->device_id)
                ->first();

            $response['is_login'] = $device ? 1 : 0;
        }
        if(!empty($request->user_id)){
            $response['is_device_supported'] = $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0 ;
        }
        return ApiResponse::custom($response, 200);
    }

    public function Configuraton(Request $request)
    {
        $googleMeetSettings = Setting::whereIn('name', ['google_meet_method', 'google_clientid', 'google_secret_key'])
            ->pluck('val', 'name');
        $settings = $googleMeetSettings->toArray();
        return $settings;
    }

    public function getPaymentMethods(Request $request){


        $settings = Setting::all()->pluck('val', 'name');

        $response = [];
        // Define the specific names you want to include
        $specificNames = ['app_name', 'footer_text', 'primary','razorpay_secretkey', 'razorpay_publickey', 'stripe_secretkey', 'stripe_publickey', 'paystack_secretkey', 'paystack_publickey', 'paypal_secretkey', 'paypal_clientid', 'flutterwave_secretkey', 'flutterwave_publickey', 'onesignal_app_id', 'onesignal_rest_api_key', 'onesignal_channel_id', 'google_maps_key', 'helpline_number', 'copyright', 'inquriy_email', 'site_description', 'customer_app_play_store', 'customer_app_app_store', 'isForceUpdate', 'version_code','cinet_siteid','cinet_api_key','cinet_Secret_key','sadad_Sadadkey','sadad_id_key','sadad_Domain','airtel_money_secretkey','airtel_money_client_id','phonepe_App_id','phonepe_Merchant_id','phonepe_salt_key','phonepe_salt_index','midtrans_client_id','midtrans_server_key','iap_entitlement_id','iap_apple_api_key','iap_google_api_key'];

        foreach ($settings as $name => $value) {
            if (in_array($name, $specificNames)) {
                if (strpos($name, 'razorpay_') === 0 && $settings['razor_payment_method'] == 1) {
                    $nestedKey = 'razor_pay';

                    $nestedName = str_replace('', 'razorpay_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'stripe_') === 0 && $settings['str_payment_method'] == 1) {
                    $nestedKey = 'stripe_pay';
                    $nestedName = str_replace('', 'stripe_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'paystack_') === 0 && $settings['paystack_payment_method'] == 1) {
                    $nestedKey = 'paystack_pay';
                    $nestedName = str_replace('', 'paystack_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'paypal_') === 0 && $settings['paypal_payment_method'] == 1) {
                    $nestedKey = 'paypal_pay';
                    $nestedName = str_replace('', 'paypal_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                } elseif (strpos($name, 'flutterwave_') === 0 && $settings['flutterwave_payment_method'] == 1) {
                    $nestedKey = 'flutterwave_pay';
                    $nestedName = str_replace('', 'flutterwave_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;

                }elseif (strpos($name, 'cinet_') === 0 && $settings['cinet_payment_method'] == 1) {
                    $nestedKey = 'cinet_pay';
                    $nestedName = str_replace('', 'cinet_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'sadad_') === 0 && $settings['sadad_payment_method'] == 1) {
                    $nestedKey = 'sadad_pay';
                    $nestedName = str_replace('', 'sadad_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'airtel_') === 0 && $settings['airtel_payment_method'] == 1) {
                    $nestedKey = 'airtel_pay';
                    $nestedName = str_replace('', 'airtel_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'phonepe_') === 0 && $settings['phonepe_payment_method'] == 1) {
                    $nestedKey = 'phonepe_pay';
                    $nestedName = str_replace('', 'phonepe_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'midtrans_') === 0 && $settings['midtrans_payment_method'] == 1) {
                    $nestedKey = 'midtrans_pay';
                    $nestedName = str_replace('', 'midtrans_', $name);
                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }elseif (strpos($name, 'iap_') === 0 && ($settings['iap_payment_method'] ?? 0) == 1) {
                    $nestedKey = 'in_app_purchase';
                    $nestedName = str_replace('iap_', '', $name); // remove prefix

                    if (!isset($response[$nestedKey])) {
                        $response[$nestedKey] = [];
                    }
                    $response[$nestedKey][$nestedName] = $value;
                }

                if (!strpos($name, 'onesignal_') === 0) {
                    $response[$name] = $value;
                } elseif (!strpos($name, 'stripe_') === 0) {
                    $response[$name] = $value;
                } elseif (!strpos($name, 'razorpay_') === 0) {
                    $response[$name] = $value;
                }
            }
        }
        return ApiResponse::success($response, __('messages.payment_methods'), 200);
    }
}
