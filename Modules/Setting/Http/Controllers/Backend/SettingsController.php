<?php

namespace Modules\Setting\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Setting\Models\Setting;
use Illuminate\Http\Request;
use Modules\Setting\Http\Requests\SettingRequest;
use App\Traits\ModuleTrait;
use Illuminate\Support\Facades\Config;
use Modules\NotificationTemplate\Models\NotificationTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Modules\Currency\Models\Currency;
use Modules\World\Models\Country;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    protected string $exportClass = '\App\Exports\SettingExport';

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'settings.title', // module title
            'settings', // module name
            'fa-solid fa-clipboard-list' // module icon
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */

    public function index(Request $request)
    {
        $filter = [
            'status' => $request->status,
        ];

        $module_action = __('messages.list');

        $export_import = true;
        $export_columns = [
            [
                'value' => 'name',
                'text' => ' Name',
            ]
        ];
        $export_url = route('backend.settings.export');

        return view('setting::backend.setting.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url'));
    }


    public function generalSetting()
    {
        $fields = ['app_name', 'user_app_name', 'helpline_number', 'inquriy_email', 'short_description', 'logo', 'mini_logo', 'dark_logo', 'dark_mini_logo','light_logo', 'favicon','facebook_url','x_url','instagram_url','youtube_url','loader_gif','copyright_text'];
        $data = $this->fieldsData($fields);
        return view('setting::backend.setting.section-pages.general', compact('data'));
    }

    /**
     * Guard business settings routes in demo mode for demo_admin users.
     */
    public function moduleSetting()
    {
        $fields = ['movie', 'tvshow', 'livetv', 'video', 'demo_login', 'enable_tmdb_api', 'tmdb_api_key'];
        $settings = $this->fieldsData($fields);

        return view('setting::backend.setting.section-pages.module-setting', compact('settings'));
    }

    public function customCode()
    {
        $fields = ['custom_css_block', 'custom_js_block'];
        $data = $this->fieldsData($fields);
        return view('setting::backend.setting.section-pages.custom-code', compact('data'));
    }

    public function invoiceSetting()
    {
        $fields = ['inv_prefix', 'order_code_start', 'spacial_note'];
        $data = $this->fieldsData($fields);

        return view('setting::backend.setting.section-pages.invoice-setting', compact('data'));
    }

    public function customization()
    {
        $fields = ['customization_setting_1', 'customization_setting_2'];

        $data = $this->fieldsData($fields);

        return view('setting::backend.setting.section-pages.customization', compact('data'));
    }

    public function mail()
    {
        $fields = ['email', 'mail_driver', 'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_from', 'from_name', 'mail_password'];
        $data = $this->fieldsData($fields);

        return view('setting::backend.setting.section-pages.mail-setting', compact('data'));
    }

    public function notificationSetting()
    {

        $query_data = NotificationTemplate::with('defaultNotificationTemplateMap', 'constant')->get();
        $notificationTemplates = [];
        $notificationKeyChannels = array_keys(config('notificationtemplate.channels'));
        $arr = [];
        // For Channel Map Or Update Channel Value
        foreach ($notificationKeyChannels as $key => $value) {
            $arr[$value] = 0;
        }
        foreach ($query_data as $key => $value) {
            $notificationTemplates[$key] = [
                'id' => $value->id,
                'type' => $value->type,
                'template' => $value->defaultNotificationTemplateMap?->subject,
                'is_default' => false,
            ];
            if (isset($value->channels)) {
                $notificationTemplates[$key]['channels'] = $value->channels;
            } else {
                $notificationTemplates[$key]['channels'] = $arr;
            }
        }

        $channelsConfig = config('notificationtemplate.channels');
        $channels = [];
        foreach ($channelsConfig as $key => $value) {
            $channels[$key] = $value === 'Mail' ? __('messages.mail') : ($value === 'Mobile' ? __('messages.mobile') : $value);
        }

        return view('setting::backend.setting.section-pages.notification-setting', compact('channels', 'notificationTemplates'));
    }

    public function integration()
    {
        $fields = [
            'is_google_login',
            'is_one_signal_notification',
            'is_mobile_notification',
            'is_map_key',
            'isForceUpdate',
            'is_application_link',
            'is_custom_webhook_notification',
            'onesignal_app_id',
            'onesignal_rest_api_key',
            'onesignal_channel_id',
            'custom_webhook_content_key',
            'custom_webhook_url',
            'customer_app_play_store',
            'customer_app_app_store',
            'google_maps_key',
            'version_code',
            'google_secretkey',
            'google_publickey',
        ];
        $data = $this->fieldsData($fields);
        return view('setting::backend.setting.section-pages.integration', compact('data'));
    }

    public function otherSettings()
    {
        $fields = ['is_event', 'is_blog', 'is_user_push_notification', 'is_provider_push_notification', 'enable_chat_gpt', 'test_without_key', 'chatgpt_key', 'firebase_notification', 'firebase_key',];
        $settings = $this->fieldsData($fields);

        return view('setting::backend.setting.section-pages.other-settings', compact('settings'));
    }

    public function paymentMethod()
    {
        $fields = ['razor_payment_method', 'razorpay_secretkey', 'razorpay_publickey', 'str_payment_method', 'stripe_secretkey', 'stripe_publickey', 'paystack_payment_method', 'paystack_secretkey', 'paystack_publickey', 'paypal_payment_method', 'paypal_secretkey', 'paypal_clientid', 'flutterwave_payment_method', 'flutterwave_secretkey', 'flutterwave_publickey', 'cash_payment_method', 'cash_secretkey', 'cash_publickey', 'cinet_payment_method', 'cinet_siteid', 'cinet_api_key', 'cinet_Secret_key', 'sadad_payment_method', 'sadad_Sadadkey', 'sadad_id_key', 'sadad_Domain', 'airtel_payment_method', 'airtel_money_secretkey', 'airtel_money_client_id', 'phonepe_payment_method', 'phonepe_App_id', 'phonepe_Merchant_id', 'phonepe_salt_key', 'phonepe_salt_index', 'midtrans_payment_method', 'midtrans_client_key', 'midtrans_server_key','iap_payment_method','iap_entitlement_id','iap_apple_api_key','iap_google_api_key'];
        $settings = $this->fieldsData($fields);
        return view('setting::backend.setting.section-pages.payment-method', compact('settings'));
    }

    public function languageSettings()
    {
        $query_data = Config::get('app.available_locales');
        $languages = [];
        foreach ($query_data as $key => $value) {
            $languages[] = [
                'id' => $key,
                'name' => $value,
            ];
        }
        $fields = ['language_setting_1', 'language_setting_2'];
        $data = $this->fieldsData($fields);
        return view('setting::backend.setting.section-pages.language-settings', compact('data', 'languages'));
    }

    public function notificationConfiguration()
    {
        $fields = ['expiry_plan', 'upcoming', 'continue_watch'];
        $settings = $this->fieldsData($fields);
        return view('setting::backend.setting.section-pages.notification-configuration', compact('settings'));
    }

    public function ResetDatabase(){


        \Artisan::call('migrate:fresh --seed');

        $message = __('messages.data_reset');

        return response()->json(['message' => $message, 'status' => true], 200);


    }


    public function storageSettings()
    {

        $fields = [
            'local',
            's3',
            'aws_access_key',
            'aws_secret_key',
            'aws_region',
            'aws_bucket',
            'aws_path_style',
            'bunny',
            'bunny_storage_zone',
            'bunny_api_key',
            'bunny_cdn_url',
            'bunny_region',
            'bunny_stream_api_key',
            'bunny_video_key',
            'bunny_cdn_hostname',
            'bunny_stream_pull_zone'
        ];

        $settings = $this->fieldsData($fields);
        return view('setting::backend.setting.section-pages.storage-setting', compact('settings'));
    }

    public function databaseReset()
    {
        return view('setting::backend.setting.section-pages.database_reset');
    }


    public function dataload(){

        $this->setEnvValue('IS_DUMMY_DATA', 'true');

        $artisanPath = base_path('artisan');

        $output = [];
        $resultCode = 0;
        exec("php {$artisanPath} migrate:fresh --seed --force 2>&1", $output, $resultCode);

        // Log the full output of the command
        \Log::info('Migration Output: ' . implode("\n", $output));
        \Log::info('Migration Result Code: ' . $resultCode);

        if ($resultCode !== 0) {
            \Log::error('Migration failed with result code: ' . $resultCode);
            \Log::error('Migration error output: ' . implode("\n", $output));
            return redirect()->back()->with('error', __('settings.data_reset_failed'));
        }

        // Log the value of IS_DUMMY_DATA
        \Log::info('IS_DUMMY_DATA value: ' . env('IS_DUMMY_DATA'));

        \Log::info('Database reset completed.');

        $message = __('settings.data_reset');

        // Redirect back with success message
        return response()->json(['message' => $message, 'status' => true], 200);

    }

    public function datareset()
    {
        \Artisan::call('config:clear');

        $this->setEnvValue('IS_DUMMY_DATA', 'false');

        $artisanPath = base_path('artisan');

        $output = [];
        $resultCode = 0;
        exec("php {$artisanPath} migrate:fresh --seed --force 2>&1", $output, $resultCode);

        // Log the full output of the command
        \Log::info('Migration Output: ' . implode("\n", $output));
        \Log::info('Migration Result Code: ' . $resultCode);

        if ($resultCode !== 0) {
            \Log::error('Migration failed with result code: ' . $resultCode);
            \Log::error('Migration error output: ' . implode("\n", $output));
            return redirect()->back()->with('error', __('settings.data_reset_failed'));
        }

        // Log the value of IS_DUMMY_DATA
        \Log::info('IS_DUMMY_DATA value: ' . env('IS_DUMMY_DATA'));

        \Log::info('Database reset completed.');

        $message = __('settings.data_reset');

        return redirect()->back()->with('success', $message);
    }
    /**
     * Update a specific value in the .env file
     */
    private function setEnvValue(string $key, string $value)  
    {
        $envPath = base_path('.env');

        if (file_exists($envPath)) {
            // Read .env file
            $envContent = file_get_contents($envPath);

            // Update or add the key
            $pattern = "/^{$key}=.*/m";
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }

            // Save changes
            file_put_contents($envPath, $envContent);

            // Clear cache to apply changes
            \Artisan::call('config:clear');
            \Artisan::call('cache:clear');
        }

        return;
    }


    public function currencySettings()
    {
        $currencies = Currency::all(); // Fetch all currencies, adjust query as needed

        $query_data = Country::get();
        $curr_names = [];
        $unique_currencies = []; 
        
        foreach ($query_data as $row) {
            if (!isset($unique_currencies[$row->currency_name])) {
                $unique_currencies[$row->currency_name] = [
                    'id' => $row->id,
                    'name' => $row->name,
                    'currency_name' => $row->currency_name,
                    'symbol' => $row->symbol,
                    'currency_code' => $row->currency_code,
                ];
            }
        }
        
        $curr_names = array_values($unique_currencies);
        usort($curr_names, function($a, $b) {
            return strcmp($a['currency_name'], $b['currency_name']);
        });

        return view('setting::backend.setting.section-pages.currency-setting', compact('currencies', 'curr_names'));
    }

    public function miscSetting()
    {
        $query_data = Config::get('app.available_locales');
        $languages = [];

        foreach ($query_data as $key => $value) {
            $languages[] = [
                'id' => $key,
                'name' => $value,
            ];
        }
        $timezones = timeZoneList();
        $data = [];
        $i = 0;
        foreach ($timezones as $key => $row) {
            $data[$i] = [
                'id' => $key,
                'text' => $row,
            ];
            $i++;
        }
        $timezones = $data;
        $dateFormat = dateFormatList();
        $Date = [];
        $i = 0;
        foreach ($dateFormat as $key => $row) {
            $Date[$i] = [
                'id' => $key,
                'text' => $row,
            ];
            $i++;
        }
        $dateFormat = $Date;

         $items = timeFormatList();
            $data = [];
            $i = 0;
            foreach ($items as $timeFormat) {
                $data[$i] = [
                    'id' =>  $timeFormat['format'],
                    'text' => $timeFormat['time'],
                ];

                $i++;
            }
            $timeFormatList = $data;

        $fields = ['google_analytics', 'default_language', 'default_time_zone', 'data_table_limit', 'default_currency','default_date_format', 'default_time_format', 'forward_seconds', 'backward_seconds'];
        $settings = $this->fieldsData($fields);
        return view('setting::backend.setting.section-pages.misc-settings', compact('settings', 'languages', 'timezones','dateFormat','timeFormatList'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */


     public function store(Request $request)
    {
        // Block selected settings updates when demo login is enabled for demo admin
        $user = Auth::user();
        $restrictedTabs = [
            'business',                   // Business Settings
            'custom_code',                // Custom CSS/JS (XSS risk; restrict demo admin)
            'customization',              // Customization
            'mail',                       // Mail Settings
            'language',                   // Language Settings
            'notification_configuration', // Notification Configuration
            'storage',                    // Storage Settings
            'seo',                        // SEO Settings (handled in SEO module, shares same concept)
        ];

        // Determine which tab is being updated; for JSON customization requests,
        // setting_tab is not sent, but customization_json is.
        $settingTab = $request->input('setting_tab', '');
        if (empty($settingTab) && $request->isJson() && $request->has('customization_json')) {
            $settingTab = 'customization';
        }

        $isDemoAdmin = $user && ($user->hasRole('demo_admin') || $user->user_type === 'demo_admin');

        if (setting('demo_login', 0) == 1
            && $isDemoAdmin
            && in_array($settingTab, $restrictedTabs, true)
        ) {
            $message = __('messages.user_does_not_have_permission_to_change_password');

            if ($request->ajax() || $request->wantsJson() || $request->isJson()) {
                return response()->json(['status' => false, 'message' => $message], 403);
            }

            return redirect()->back()->with('error', $message);
        }
      
        // Handle JSON request body (for customization form)
        if ($request->isJson() && $request->has('customization_json')) {
            $jsonData = json_decode($request->input('customization_json'), true);
            if (isset($jsonData['setting'])) {
                foreach ($jsonData['setting'] as $key => $valueObj) {
                    if (isset($valueObj['value'])) {
                        $request->merge([$key => $valueObj['value']]);
                    }
                }
            }
            
            if ($request->has('root_colors')) {
                $rootColors = $request->input('root_colors');
                if (!is_string($rootColors)) {
                    $rootColors = json_encode($rootColors);
                }
                $request->merge(['root_colors' => $rootColors]);
            }
        }
        
        $rules = Setting::getSelectedValidationRules(array_keys($request->all()));
     
        // Override validation rules for media fields that come from media modal (URLs, not file uploads)
        $mediaFields = ['logo', 'mini_logo', 'dark_logo', 'favicon', 'loader_gif'];
        foreach ($mediaFields as $field) {
            if ($request->has($field)) {
                // Check if it's a file upload or a URL (string)
                if ($request->hasFile($field)) {
                    // It's a file upload, keep original validation rules from config
                    // Rules already set from getSelectedValidationRules
                } else {
                    // It's a URL/string from media modal, validate as string/URL
                    $rules[$field] = 'nullable|string|max:500';
                }
            }
        }
        
        // Add custom validation for loader_gif_remove and favicon_remove
        if ($request->has('loader_gif_remove')) {
            $rules['loader_gif_remove'] = 'nullable|in:0,1';
        }
        
        // Add validation for copyright_text field
        if ($request->has('copyright_text')) {
            $rules['copyright_text'] = 'required|string|max:255';
        }
        
        if ($request->has('root_colors')) {
            $rules['root_colors'] = 'nullable|string';
        }
       

        \Log::info("rules=".json_encode($rules));
        \Log::info("request_all=".json_encode($request->all()));

        // Custom validation messages
        $messages = [
            'expiry_plan.required' => __('messages.expiry_plan_required'),
            'expiry_plan.integer' => __('messages.expiry_plan_must_be_integer'),
            'expiry_plan.min' => __('messages.expiry_plan_min'),
            'upcoming.required' => __('messages.upcoming_required'),
            'upcoming.integer' => __('messages.upcoming_must_be_integer'),
            'upcoming.min' => __('messages.upcoming_min'),
            'continue_watch.required' => __('messages.continue_watch_required'),
            'continue_watch.integer' => __('messages.continue_watch_must_be_integer'),
            'continue_watch.min' => __('messages.continue_watch_min'),
        ];

        try {
            $data = $this->validate($request, $rules, $messages);
            \Log::info("data=".json_encode($data));
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error("Validation failed: ".json_encode($e->errors()));
            throw $e; // Re-throw to maintain normal validation behavior
        }
        $validSettings = array_keys($rules);


        if ($request->has('firebase_json_file')) {

            $file = $request->file('firebase_json_file');

            $fileName = $file->getClientOriginalName();
            $directoryPath = storage_path('app/data');

            if (!File::isDirectory($directoryPath)) {
                File::makeDirectory($directoryPath, 0777, true, true);
            }
            $files = File::files($directoryPath);

            foreach ($files as $existingFile) {
                $filePath = $existingFile->getPathname();

                if (strtolower($existingFile->getExtension()) === 'json') {
                    File::delete($filePath);
                }
            }
            $file->move($directoryPath, $fileName);
        }
        
        if($request->has('loader_gif')) {
            $data['loader_gif'] = !empty($data['loader_gif']) ? $data['loader_gif'] : extractFileNameFromUrl($data['loader_gif'],'loader_gif');
        }
        if($request->has('mini_logo')) {
            $data['mini_logo'] = !empty($data['mini_logo']) ? $data['mini_logo'] : extractFileNameFromUrl($data['mini_logo'],'mini_logo');
        }
        if($request->has('dark_logo')) {
            $data['dark_logo'] = !empty($data['dark_logo']) ? $data['dark_logo'] : extractFileNameFromUrl($data['dark_logo'],'dark_logo');
        }
        if($request->has('favicon')) {
            $data['favicon'] = !empty($data['favicon']) ? $data['favicon'] : extractFileNameFromUrl($data['favicon'],'favicon');
        }
        // Handle loader_gif removal separately
        if ($request->has('loader_gif_remove') && $request->input('loader_gif_remove') == '1') {

            $existingLoaderSetting = Setting::where('name', 'loader_gif')->first();
            $defaultLoaderUrl = asset('img/logo/loader.gif');

            if ($existingLoaderSetting) {
                $existingLoaderSetting->update([
                    'val' => $defaultLoaderUrl,
                    'type' => Setting::getType('loader_gif'),
                    'datatype' => Setting::getDataType('loader_gif')
                ]);
            } else {
                Setting::add('loader_gif', $defaultLoaderUrl, Setting::getDataType('loader_gif'), Setting::getType('loader_gif'));
            }
        }

        // Handle favicon removal separately
        if ($request->has('favicon_remove') && $request->input('favicon_remove') == '1') {

            $existingFaviconSetting = Setting::where('name', 'favicon')->first();
            $defaultFaviconUrl = asset('img/logo/favicon.png');

            if ($existingFaviconSetting) {
                $existingFaviconSetting->update([
                    'val' => $defaultFaviconUrl,
                    'type' => Setting::getType('favicon'),
                    'datatype' => Setting::getDataType('favicon')
                ]);
            } else {
                Setting::add('favicon', $defaultFaviconUrl, Setting::getDataType('favicon'), Setting::getType('favicon'));
            }
        }

        foreach ($data as $key => $val) {
            if (in_array($key, $validSettings)) {
                // Skip processing loader_gif_remove as a separate field
                $existingSetting = Setting::where('name', $key)->first();

                // Handle media fields (logo, mini_logo, dark_logo, favicon, loader_gif) using copyImageToFolder (like genres)
                $mediaFields = ['logo', 'mini_logo', 'dark_logo', 'favicon', 'loader_gif'];
                if (in_array($key, $mediaFields) && !empty($val) && is_string($val)) {
                    // Use copyImageToFolder to move file to correct folder structure
                    // All logo types (mini_logo, dark_logo, favicon, loader_gif) use "logos" as page_type
                    $pageType = 'logos'; // All logo types use "logos" folder
                    $val = copyImageToFolder($val, $pageType);
                }

                if (gettype($val) == 'object') {
                    if ($val->getType() == 'file') {
                        $val = validateUploadedFile($val, 'image');
                        if ($existingSetting) {
                            $mediaItems = $existingSetting->addMedia($val)->toMediaCollection($key);
                            $existingSetting->update(['val' => $mediaItems->getUrl()]);
                        } else {
                            $setting = Setting::add($key, '', Setting::getDataType($key), Setting::getType($key));
                            $mediaItems = $setting->addMedia($val)->toMediaCollection($key);
                            $setting->update(['val' => $mediaItems->getUrl()]);
                        }
                    }
                } else {
                    if ($existingSetting) {
                        // Update existing setting
                        $existingSetting->update([
                            'val' => $val,
                            'type' => Setting::getType($key),
                            'datatype' => Setting::getDataType($key)
                        ]);
                    } else {
                        // Create new setting
                        Setting::add($key, $val, Setting::getDataType($key), Setting::getType($key));
                    }

                    if ($key === 'midtrans_server_key' && $request->has('midtrans_server_key')) {
                        $val = $request->input('midtrans_server_key');
                    }
                    if ($key === 'midtrans_client_key' && $request->has('midtrans_client_key')) {
                        $val = $request->input('midtrans_client_key');
                    }
                    // $setting = Setting::add($key, $val, Setting::getDataType($key), Setting::getType($key));
                }
            }
        }
        
        
        // Get the tab name from request
        $tab = $request->input('setting_tab', '');

        // If business/general settings are updated, clear cached footer data
        if ($tab === 'business') {
            Cache::forget('footer_data');
        }
        $tabNames = [
            'business' => __('setting_sidebar.lbl_General'),
            'custom_code' => __('setting_sidebar.lbl_custom_code'),
            'module' => __('setting_sidebar.lbl_module-setting'),
            'misc' => __('setting_sidebar.lbl_misc_setting'),
            'customization' => __('setting_sidebar.lbl_customization'),
            'mail' => __('setting_sidebar.lbl_mail'),
            'payment' => __('setting_sidebar.lbl_payment'),
            'language' => __('setting_sidebar.lbl_language'),
            'storage' => __('setting_sidebar.lbl_storage'),
            'notification_configuration' => __('setting_sidebar.lbl_notification_configuration'),
            'invoice' => __('setting_sidebar.lbl_inv_setting'),
            'integration' => __('setting_sidebar.lbl_integration'),
            'other' => 'App Configuration'
        ];
        
        // Map tab names to routes
        $tabRoutes = [
            'business' => 'backend.settings.general',
            'custom_code' => 'backend.settings.custom-code',
            'module' => 'backend.settings.module',
            'misc' => 'backend.settings.misc',
            'customization' => 'backend.settings.customization',
            'mail' => 'backend.settings.mail',
            'payment' => 'backend.settings.payment-method',
            'language' => 'backend.settings.language-settings',
            'storage' => 'backend.settings.storage-settings',
            'notification_configuration' => 'backend.settings.notification-configuration',
            'invoice' => 'backend.settings.invoice-setting',
            'integration' => 'backend.settings.integration',
            'other' => 'backend.settings.other-settings'
        ];
        
        // Generate tab-specific message
        if (!empty($tab) && isset($tabNames[$tab])) {
            $message = $tabNames[$tab] . ' ' . __('messages.updated_successfully');
        } else {
            $message = __('settings.save_setting');
        }

        // When module settings are saved with demo_login off, invalidate all demo_admin sessions so they are logged out
        $demoLoginValue = (int) $request->input('demo_login', -1);
        if ($tab === 'module' && $demoLoginValue === 0) {
            $demoAdminUserIds = User::where('user_type', 'demo_admin')
                ->orWhereHas('roles', fn ($q) => $q->where('name', 'demo_admin'))
                ->pluck('id')
                ->toArray();
            if (!empty($demoAdminUserIds) && config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->whereIn('user_id', $demoAdminUserIds)
                    ->delete();
            }
        }

        // Check if it's an AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['message' => $message, 'status' => true], 200);
        } else {
            // Redirect to the specific setting page based on tab
            if (!empty($tab) && isset($tabRoutes[$tab])) {
                return redirect()->route($tabRoutes[$tab])->with('success', $message);
            } else {
                // Fallback to back if tab is not recognized
                return redirect()->back()->with('success', $message);
            }
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(int $id)
    {
        $data = Setting::findOrFail($id);
        return view('setting::backend.setting.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(SettingRequest $request, Setting $setting)
    {
        $requestData = $request->all();
        $setting->update($requestData);

        return redirect()->route('backend.settings.index', $setting->id)->with('success', 'Setting updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */

    public function destroy(int $id)
    {
        $data = Setting::findOrFail($id);
        $data->delete();
        $message = __('Taxes Deleted Successfully');
        return response()->json(['message' => $message, 'type' => 'DELETE_FORM']);
    }

    public function restore(int $id)
    {
        $data = Setting::withTrashed()->findOrFail($id);
        $data->restore();
        return response()->json(['message' => 'Tax entry restored successfully']);
    }

    public function forceDelete(int $id)
    {
        $data = Setting::withTrashed()->findOrFail($id);
        $data->forceDelete();
        return response()->json(['message' => 'Tax entry permanently deleted']);
    }

    public function clear_cache()
    {
        Setting::flushCache();

        $message = __('messages.cache_cleard');

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function fieldsData(array $fields)
    {

        $responseData = Setting::whereIn('name', $fields)->get();
        $data = [];

        foreach ($responseData as $setting) {
            $field = $setting->name;
            $value = $setting->val;

            if (in_array($field, ['logo', 'mini_logo', 'dark_logo', 'dark_mini_logo', 'favicon', 'loader_gif'])) {
                if($value){
                    $value = asset($value);
                }else{
                    $value = $value;
                }
            }

            $data[$field] = $value;
        }

        return $data;
    }

    public function change_password()
    {
        // Override module_title for profile pages to show "Profile" instead of "Settings"
        view()->share([
            // Use translation key here; breadcrumb partial calls __() on it.
            'module_title' => 'profile.title',
        ]);

        return view('setting::backend.profile.section-pages.change-password');
    }

    public function information()
    {
        // Override module_title for profile pages to show "Profile" instead of "Settings"
        view()->share([
            // Use translation key here; breadcrumb partial calls __() on it.
            'module_title' => 'profile.title',
        ]);

        $user = Auth::user();
        $page_type = 'information';

        return view('setting::backend.profile.section-pages.information-page', compact('user', 'page_type'));
    }

    public function userProfileUpdate(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'first_name'     => ['required'],
            'last_name'      => ['required'],
            'email'          => ['required', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
            'mobile'         => ['required', 'unique:users,mobile,' . Auth::id()],
            'gender'         => ['required'],
        ]);
        $user = Auth::user();
        $data = User::findOrFail($user->id);
        $request_data = $request->except('profile_image');
        $data->update($request_data);

        if ($request->custom_fields_data) {
            $data->updateCustomFieldData(json_decode($request->custom_fields_data));
        }

        if ($request->file('profile_image')) {
            storeMediaFile($data, $request->file('profile_image'), 'profile_image', 'avatar');
        }

        $message = __('messages.profile_update');

        return redirect()->back()->with('success', $message);
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user(); // Get the currently authenticated user

        // Prevent demo admin from changing password when demo login is enabled
        $isDemoAdmin = $user && ($user->hasRole('demo_admin') || $user->user_type === 'demo_admin');
        if (setting('demo_login', 0) == 1 && $isDemoAdmin) {
            $message = __('messages.user_does_not_have_permission_to_change_password');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => $message], 403);
            }

            return redirect()->back()->with('error', $message);
        }

        if (setting('demo_login')) {
            return redirect()->back()->with('error', __('messages.permission_denied'));
        }

        $user_id = $user->id; // Retrieve the user's ID
        $data = User::findOrFail($user_id);

        if ($data == "") {
            $message = __('messages.user_not_found');
            return redirect()->back()->with('error', $message);
        }

        $hashedPassword = $data->password;

        $match = Hash::check($request->old_password, $hashedPassword);

        $same_exits = Hash::check($request->new_password, $hashedPassword);

        if ($match) {
            if ($same_exits) {
                $message = __('messages.same_pass');
                return redirect()->back()->with('error', $message);
            }

            $data->fill([
                'password' => Hash::make($request->new_password)
            ])->save();
            $message = __('messages.pass_successfull');
            return redirect()->back()->with('success', $message);
        } else {
            $message = __('messages.check_old_password');
            return redirect()->back()->with('error', $message);
        }
    }



    // App config
    public function appConfig(Request $request)
    {
        $module_title = __('settings.add_title');
        $module_action = __('messages.list');
        $fields = ['is_social_login', 'is_google_login','google_client_id','google_client_secret','google_redirect_uri', 'is_otp_login', 'apiKey', 'authDomain', 'databaseURL', 'projectId', 'storageBucket', 'messagingSenderId', 'appId', 'measurementId', 'is_apple_login', 'is_firebase_notification','firebase_key', 'is_user_push_notification', 'is_application_link', 'ios_url', 'android_url', 'force_update', 'enter_app_version', 'app_version', 'message_text', 'is_ChatGPT_integration', 'ChatGPT_key', 'banner_ad_id', 'ios_banner_id','mobile_app','android_latest_version','android_minimum_required_version','ios_minimum_required_version','ios_latest_version','tv_app','android_tv_minimum_required_version','android_tv_latest_version','android_tv_url'];
        $data = $this->fieldsData($fields);
        return view('setting::backend.appconfig.index', compact('module_action', 'data', 'module_title'));
    }

    public function getCurrencyData(Request $request)
    {
        $currencyName = $request->input('currency_name');

        $currency = Country::where('currency_name', $currencyName)->first();

        if ($currency) {
            return response()->json([

                'currency_symbol' => $currency->symbol,
                'currency_code' => $currency->currency_code,
            ]);
        } else {
            return response()->json(['error' => 'Currency not found'], 404);
        }
    }
}
