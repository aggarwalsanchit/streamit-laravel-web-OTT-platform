@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <form method="POST" action="{{ route('backend.setting.store') }}" enctype="multipart/form-data">
    @csrf
        {{-- Social Login Section --}}
        <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-center form-control">
                <label class="form-label m-0" for="is_social_login">{{__('messages.lbl_social_login')}}</label>
                <input type="hidden" value="0" name="is_social_login">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input toggle-input" data-toggle-target="#social-login-section" value="1"
                        name="is_social_login" id="is_social_login" type="checkbox"
                        {{ old('is_social_login', $data['is_social_login'] ?? 0) == 1 ? 'checked' : '' }} />
                </div>
            </div>
        </div>

        <div id="social-login-section" class=" {{ old('is_social_login', $data['is_social_login'] ?? 0) == 1 ? '' : 'd-none' }}">
            <div class="card border">
                <div class="card-body">
                    <ul class="list-group">
                        {{-- Google Login --}}
                        <li class="list-group-item bg-transparent border-0 p-0 mb-3">
                            <div class="d-flex justify-content-between align-items-center form-control">
                                <label class="form-label m-0" for="is_google_login">{{ __('messages.lbl_google_login') }}</label>
                                <input type="hidden" value="0" name="is_google_login">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input toggle-input" data-toggle-target="#google-key-field"
                                        value="1" name="is_google_login" id="is_google_login" type="checkbox"
                                        {{ old('is_google_login', $data['is_google_login'] ?? 0) == 1 ? 'checked' : '' }} />
                                </div>
                            </div>
                            <div id="google-key-field"
                                class="{{ old('is_google_login', $data['is_google_login'] ?? 0) == 1 ? '' : 'd-none' }} mt-3">
                                <div class="card bg-body border">
                                    <div class="card-body">
                                        <div class="row gy-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="google_client_id">{{ __('messages.lbl_google_client_id') }}</label>
                                                <input type="text" class="form-control @error('google_client_id') is-invalid @enderror" name="google_client_id"
                                                    id="google_client_id" value="{{ old('google_client_id', $data['google_client_id'] ?? '') }}"
                                                    placeholder="{{ __('messages.lbl_google_client_id') }}">
                                                @error('google_client_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="google_client_secret">{{ __('messages.lbl_google_client_secret') }}</label>
                                                <input type="text" class="form-control @error('google_client_secret') is-invalid @enderror" name="google_client_secret"
                                                    id="google_client_secret" value="{{ old('google_client_secret', $data['google_client_secret'] ?? '') }}"
                                                    placeholder="{{ __('messages.lbl_google_client_secret') }}">
                                                @error('google_client_secret')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="google_redirect_uri">{{ __('messages.lbl_google_redirect_url') }}</label>
                                                <input type="text" class="form-control @error('google_redirect_uri') is-invalid @enderror" name="google_redirect_uri"
                                                    id="google_redirect_uri" value="{{ old('google_redirect_uri', $data['google_redirect_uri'] ?? '') }}"
                                                    placeholder="{{ __('messages.lbl_google_redirect_url') }}">
                                                @error('google_redirect_uri')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        {{-- OTP Login --}}
                        <li class="list-group-item bg-transparent border-0 p-0 mb-3">
                            <div class="d-flex justify-content-between align-items-center form-control">
                                <label class="form-label m-0" for="is_otp_login">{{ __('messages.lbl_otp_login') }}</label>
                                <input type="hidden" value="0" name="is_otp_login">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input toggle-input" data-toggle-target="#otp-key-field" value="1" name="is_otp_login" id="is_otp_login" type="checkbox"
                                    {{ old('is_otp_login', $data['is_otp_login'] ?? 0) == 1 ? 'checked' : '' }} />
                                </div>
                            </div>
                            <div id="otp-key-field" class="{{ old('is_otp_login', $data['is_otp_login'] ?? 0) == 1 ? '' : 'd-none' }} mt-3">
                                <div class="card border bg-body">
                                    <div class="card-body">
                                        <div class="row gy-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="apiKey">{{ __('messages.lbl_api_key') }}</label>
                                                <input type="text" class="form-control @error('apiKey') is-invalid @enderror" name="apiKey"
                                                    id="apiKey" value="{{ old('apiKey', $data['apiKey'] ?? '') }}" placeholder="{{ __('messages.lbl_api_key') }}">
                                                @error('apiKey')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="authDomain">{{ __('messages.lbl_auth_domain') }}</label>
                                                <input type="text" class="form-control @error('authDomain') is-invalid @enderror" name="authDomain"
                                                    id="authDomain" value="{{ old('authDomain', $data['authDomain'] ?? '') }}" placeholder="{{ __('messages.lbl_auth_domain') }}">
                                                @error('authDomain')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="databaseURL">{{ __('messages.lbl_database_url') }}</label>
                                                <input type="text" class="form-control @error('databaseURL') is-invalid @enderror" name="databaseURL"
                                                    id="databaseURL" value="{{ old('databaseURL', $data['databaseURL'] ?? '') }}" placeholder="{{ __('messages.lbl_database_url') }}">
                                                @error('databaseURL')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="projectId">{{ __('messages.lbl_project_id') }}</label>
                                                <input type="text" class="form-control @error('projectId') is-invalid @enderror" name="projectId"
                                                    id="projectId" value="{{ old('projectId', $data['projectId'] ?? '') }}" placeholder="{{ __('messages.lbl_project_id') }}">
                                                @error('projectId')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="storageBucket">{{ __('messages.lbl_storage_bucket') }}</label>
                                                <input type="text" class="form-control @error('storageBucket') is-invalid @enderror" name="storageBucket"
                                                    id="storageBucket" value="{{ old('storageBucket', $data['storageBucket'] ?? '') }}" placeholder="{{ __('messages.lbl_storage_bucket') }}">
                                                @error('storageBucket')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="messagingSenderId">{{ __('messages.lbl_messaging_sender_id') }}</label>
                                                <input type="text" class="form-control @error('messagingSenderId') is-invalid @enderror" name="messagingSenderId"
                                                    id="messagingSenderId" value="{{ old('messagingSenderId', $data['messagingSenderId'] ?? '') }}" placeholder="{{ __('messages.lbl_messaging_sender_id') }}">
                                                @error('messagingSenderId')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="appId">{{ __('messages.lbl_app_id') }}</label>
                                                <input type="text" class="form-control @error('appId') is-invalid @enderror" name="appId"
                                                    id="appId" value="{{ old('appId', $data['appId'] ?? '') }}" placeholder="{{ __('messages.lbl_app_id') }}">
                                                @error('appId')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="measurementId">{{ __('messages.lbl_measurement_id') }}</label>
                                                <input type="text" class="form-control @error('measurementId') is-invalid @enderror" name="measurementId"
                                                    id="measurementId" value="{{ old('measurementId', $data['measurementId'] ?? '') }}" placeholder="{{ __('messages.lbl_measurement_id') }}">
                                                @error('measurementId')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Firebase Notification --}}
        <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-center form-control">
                <label class="form-label m-0" for="is_firebase_notification">{{ __('messages.lbl_firebase_notification') }}</label>
                <input type="hidden" value="0" name="is_firebase_notification">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input toggle-input" data-toggle-target="#firebase-key-field" value="1"
                        name="is_firebase_notification" id="is_firebase_notification" type="checkbox"
                        {{ old('is_firebase_notification', $data['is_firebase_notification'] ?? 0) == 1 ? 'checked' : '' }} />
                </div>
            </div>
        </div>
        <div id="firebase-key-field" class="mb-3 {{ old('is_firebase_notification', $data['is_firebase_notification'] ?? 0) == 1 ? '' : 'd-none' }}">
            <div class="card border">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label class="form-label" for="category-tmdb_api_key">{{ __('messages.lbl_firebase_key') }}</label>
                            <input type="text" class="form-control @error('firebase_key') is-invalid @enderror"
                                name="firebase_key" id="firebase_key"
                                value="{{ old('firebase_key', $data['firebase_key'] ?? '') }}" placeholder="{{ __('messages.lbl_firebase_key') }}">
                            @error('firebase_key')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="json_file" class="form-label">
                                {{ __('messages.lbl_firebase_json_file') }}
                                <span class="ml-3">
                                    <a class="text-primary" href="https://console.firebase.google.com/">{{__('messages.download_json')}}</a>
                                </span>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="{{__('messages.upload_firebase_json')}}"
                                    readonly>
                                <label class="input-group-text" for="json_file">{{ __('messages.browse') }}</label>
                                <input type="file" class="d-none" id="json_file" name="firebase_json_file" accept=".json"
                                    aria-describedby="additionalFileHelp">
                            </div>
                            @foreach ($errors->get('json_file') as $msg)
                                <p class="text-danger">{{ $msg }}</p>
                            @endforeach

                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- User Push Notification --}}
        <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-center form-control">
                <label class="form-label m-0" for="is_user_push_notification">{{ __('messages.lbl_push_notification') }}</label>
                <input type="hidden" value="0" name="is_user_push_notification">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input toggle-input" data-toggle-target="#user-push-key-field" value="1"
                        name="is_user_push_notification" id="is_user_push_notification" type="checkbox"
                        {{ old('is_user_push_notification', $data['is_user_push_notification'] ?? 0) == 1 ? 'checked' : '' }} />
                </div>
            </div>
        </div>
        <div id="user-push-key-field"
            class="mb-3 {{ old('is_user_push_notification', $data['is_user_push_notification'] ?? 0) == 1 ? '' : 'd-none' }}">

        </div>

        {{-- Application Links --}}
        <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-center form-control">
                <label class="form-label m-0" for="is_application_link">{{ __('messages.lbl_application_link') }}</label>
                <input type="hidden" value="0" name="is_application_link">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input toggle-input" data-toggle-target="#application-links-section"
                        value="1" name="is_application_link" id="is_application_link" type="checkbox"
                        {{ old('is_application_link', $data['is_application_link'] ?? 0) == 1 ? 'checked' : '' }} />
                </div>
            </div>
        </div>

        <div id="application-links-section" class="mb-3 {{ old('is_application_link', $data['is_application_link'] ?? 0) == 1 ? '' : 'd-none' }}">
            <div class="card border">
                <div class="card-body">
                    <!-- Mobile App & TV App Section -->
                    <div class="d-flex justify-content-between align-items-center form-control">
                        <label class="form-label m-0" for="is_otp_login">{{ __('messages.lbl_mobile_app_tv_app') }}</label>
                    </div>
                    <div class="row gy-3">
                        <div class="col-md-6 mt-4">
                            <label class="form-label" for="ios_url">{{ __('messages.lbl_ios_url') }}</label>
                            <input type="text" class="form-control @error('ios_url') is-invalid @enderror" name="ios_url"
                                id="ios_url" value="{{ old('ios_url', $data['ios_url'] ?? '') }}" placeholder="{{ __('messages.lbl_ios_url') }}">
                            @error('ios_url')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 mt-4">
                            <label class="form-label" for="android_url">{{ __('messages.lbl_android_url') }}</label>
                            <input type="text" class="form-control @error('android_url') is-invalid @enderror"
                                name="android_url" id="android_url" value="{{ old('android_url', $data['android_url'] ?? '') }}"
                                placeholder="{{ __('messages.lbl_android_url') }}">
                            @error('android_url')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- TV App Section -->
                    <div class="d-flex justify-content-between align-items-center form-control mt-3">
                        <label class="form-label m-0" for="is_otp_login">{{ __('messages.lbl_tv_app') }}</label>
                    </div>
                    <div class="row gy-3">
                        <div class="col-md-6 mt-4">
                            <label class="form-label" for="android_tv_url">{{ __('messages.lbl_android_url') }}</label>
                            <input type="text" class="form-control @error('android_tv_url') is-invalid @enderror" name="android_tv_url"
                                id="android_tv_url" value="{{ old('android_url', $data['android_tv_url'] ?? '') }}" placeholder="{{ __('messages.lbl_android_url') }}">
                            @error('android_tv_url')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- Force Update --}}
        <div class="form-group mb-3 ">
            <div class="d-flex justify-content-between align-items-center form-control">
                <label class="form-label m-0" for="force_update">{{ __('messages.lbl_force_update') }}</label>
                <input type="hidden" value="0" name="force_update">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input toggle-input" data-toggle-target="#force-update-field" value="1"
                        name="force_update" id="force_update"
                        type="checkbox"{{ old('force_update', $data['force_update'] ?? 0) == 1 ? 'checked' : '' }} />
                </div>
            </div>
        </div>
      {{-- Mobile Application --}}
        <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-center form-control">
                <label class="form-label m-0" for="mobile_app">{{ __('messages.lbl_mobile_app') }} </label>
                <input type="hidden" value="0" name="mobile_app">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input toggle-input" data-toggle-target="#mobile-app-section"
                        value="1" name="mobile_app" id="mobile_app" type="checkbox"
                        {{ old('mobile_app', $data['mobile_app'] ?? 0) == 1 ? 'checked' : '' }} />
                </div>
            </div>
        </div>
        <div id="mobile-app-section"
            class="mb-3 {{ old('mobile_app', $data['mobile_app'] ?? 0) == 1 ? '' : 'd-none' }}">
            <div class="card border">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label class="form-label" for="android_minimum_required_version">{{ __('messages.lbl_android_minimum_required_version') }}</label>
                            <input type="text"
                                class="form-control @error('android_minimum_required_version') is-invalid @enderror" name="android_minimum_required_version"
                                id="android_minimum_required_version"
                                value="{{ old('android_minimum_required_version', $data['android_minimum_required_version'] ?? '') }}"
                                placeholder="{{ __('messages.lbl_android_minimum_required_version') }}">
                            @error('android_minimum_required_version')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="android_latest_version">{{ __('messages.lbl_android_latest_version') }}</label>
                            <input type="text"
                                class="form-control @error('android_latest_version') is-invalid @enderror" name="android_latest_version"
                                id="android_latest_version"
                                value="{{ old('android_latest_version', $data['android_latest_version'] ?? '') }}"
                                placeholder="{{ __('messages.lbl_android_latest_version') }}">
                            @error('android_latest_version')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ios_minimum_required_version">{{ __('messages.lbl_ios_minimum_required_version') }}</label>
                            <input type="text"
                                class="form-control @error('ios.minimum_required_version') is-invalid @enderror" name="ios_minimum_required_version"
                                id="ios_minimum_required_version"
                                value="{{ old('ios_minimum_required_version', $data['ios_minimum_required_version'] ?? '') }}"
                                placeholder="{{ __('messages.lbl_ios_minimum_required_version') }}">
                            @error('ios_minimum_required_version')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ios_latest_version">{{ __('messages.lbl_ios_latest_version') }}</label>
                            <input type="text"
                                class="form-control @error('ios.latest_version') is-invalid @enderror" name="ios_latest_version"
                                id="ios_latest_version"
                                value="{{ old('ios_latest_version', $data['ios_latest_version'] ?? '') }}"
                                placeholder="{{ __('messages.lbl_ios_latest_version') }}">
                            @error('ios_latest_version')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TV Application --}}
        <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-center form-control">
                <label class="form-label m-0" for="tv_app">{{ __('messages.lbl_tv_app') }}</label>
                <input type="hidden" value="0" name="tv_app">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input toggle-input" data-toggle-target="#tv-app-section"
                        value="1" name="tv_app" id="tv_app" type="checkbox"
                        {{ old('tv_app', $data['tv_app'] ?? 0) == 1 ? 'checked' : '' }} />
                </div>
            </div>
        </div>

        <div id="tv-app-section"
            class="mb-3 {{ old('tv_app', $data['tv_app'] ?? 0) == 1 ? '' : 'd-none' }}">
            <div class="card border">
                <div class="card-body">
                    <div class="row gy-3">
                        {{-- Android TV Minimum Version --}}
                        <div class="col-md-6">
                            <label class="form-label" for="android_tv_minimum_required_version">{{ __('messages.lbl_android_tv_minimum_required_version') }}</label>
                            <input type="text"
                                class="form-control @error('android_tv_minimum_required_version') is-invalid @enderror"
                                name="android_tv_minimum_required_version"
                                id="android_tv_minimum_required_version"
                                value="{{ old('android_tv_minimum_required_version', $data['android_tv_minimum_required_version'] ?? '') }}"
                                placeholder="{{ __('messages.lbl_android_tv_minimum_required_version') }}">
                            @error('android_tv_minimum_required_version')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Android TV Latest Version --}}
                        <div class="col-md-6">
                            <label class="form-label" for="android_tv_latest_version">{{ __('messages.lbl_android_tv_latest_version') }}</label>
                            <input type="text"
                                class="form-control @error('android_tv_latest_version') is-invalid @enderror"
                                name="android_tv_latest_version"
                                id="android_tv_latest_version"
                                value="{{ old('android_tv_latest_version', $data['android_tv_latest_version'] ?? '') }}"
                                placeholder="{{ __('messages.lbl_android_tv_latest_version') }}">
                            @error('android_tv_latest_version')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- ChatGPT Integration --}}
        <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-center form-control">
                <label class="form-label m-0" for="is_ChatGPT_integration">{{ __('messages.chat_gpt_integration') }}</label>
                <input type="hidden" value="0" name="is_ChatGPT_integration">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input toggle-input" data-toggle-target="#ChatGPT-key-field" value="1"
                           name="is_ChatGPT_integration" id="is_ChatGPT_integration" type="checkbox"
                           {{ old('is_ChatGPT_integration', $data['is_ChatGPT_integration'] ?? 0) == 1 ? 'checked' : '' }} />
                </div>
            </div>
        </div>
        <div id="ChatGPT-key-field"
             class="mb-3 {{ old('is_ChatGPT_integration', $data['is_ChatGPT_integration'] ?? 0) == 1 ? '' : 'd-none' }}">
             <div class="card border">
                <div class="card-body">
                    <div class="col-md-12">
                        <label class="form-label" for="category-tmdb_api_key">{{ __('messages.chat_gpt_key') }}</label>
                        <input type="text" class="form-control @error('ChatGPT_key') is-invalid @enderror" name="ChatGPT_key"
                            id="ChatGPT_key" value="{{ old('ChatGPT_key', $data['ChatGPT_key'] ?? '') }}"
                            placeholder="{{ __('messages.chat_gpt_key') }}">
                        @error('ChatGPT_key')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
             </div>
        </div>

        {{-- Banner Ad Settings --}}
        <div class="form-group mb-3">
            <div class="row gy-3">
                <div class="col-md-6">
                    <label class="form-label" for="banner_ad_id">{{ __('messages.lbl_android_banner_ad_id') }}</label>
                    <input type="text" class="form-control @error('banner_ad_id') is-invalid @enderror" name="banner_ad_id"
                           id="banner_ad_id" value="{{ old('banner_ad_id', $data['banner_ad_id'] ?? '') }}"
                           placeholder="{{ __('messages.lbl_android_banner_ad_id') }}">
                    @error('banner_ad_id')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="ios_banner_id">{{ __('messages.lbl_ios_banner_id') }}</label>
                    <input type="text" class="form-control @error('ios_banner_id') is-invalid @enderror" name="ios_banner_id"
                           id="ios_banner_id" value="{{ old('ios_banner_id', $data['ios_banner_id'] ?? '') }}"
                           placeholder="{{ __('messages.lbl_ios_banner_id') }}">
                    @error('ios_banner_id')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Submission --}}
        <div class="form-group mt-3 text-end">
            <button class="btn btn-primary w-sm" type="submit">{{ __('messages.save') }}</button>
        </div>
    </form>

    @if(session('success'))
    <div class="snackbar" id="snackbar">

        <div class="d-flex justify-content-around align-items-center">
            <p class="mb-0">{{ session('success') }}</p>
            <a href="#" class="dismiss-link text-decoration-none text-success" onclick="dismissSnackbar(event)">{{ __('messages.dismiss') }}</a>
        </div>
    </div>
    @endif

@endsection

@push('after-scripts')

<script src="{{ asset('js/form-modal/index.js') }}" defer></script>
<script src="{{ asset('js/form/index.js') }}" defer></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            function toggleSection(checkbox) {
                const targetId = checkbox.getAttribute('data-toggle-target');
                const targetElement = document.querySelector(targetId);

                if (!targetElement) return;

                // Show/hide the target section
                if (checkbox.checked) {
                    targetElement.classList.remove('d-none');
                    // Enable required fields inside this section
                    targetElement.querySelectorAll('input, select, textarea').forEach(el => {
                        if (el.hasAttribute('data-toggle-required')) {
                            el.setAttribute('required', 'required');
                        }
                    });
                } else {
                    targetElement.classList.add('d-none');
                    // Disable required attribute
                    targetElement.querySelectorAll('input, select, textarea').forEach(el => {
                        el.removeAttribute('required');
                    });
                }
            }

            document.querySelectorAll('.toggle-input').forEach(function(checkbox) {
                toggleSection(checkbox);
                checkbox.addEventListener('change', function() {
                    toggleSection(checkbox);
                });
            });

            const firebaseCheckbox = document.getElementById('is_firebase_notification');
            firebaseCheckbox.addEventListener('change', function() {
                toggleSection(firebaseCheckbox);
                if (firebaseCheckbox.checked) {
                    document.getElementById('force_update').checked = false;
                    document.getElementById('enter_app_version').checked = false;
                    document.getElementById('message').checked = false;
                    document.getElementById('force-update-field').classList.add('d-none');
                    document.getElementById('enter-app-version-field').classList.add('d-none');
                    document.getElementById('message-field').classList.add('d-none');
                }
            });
        });
    </script>
@endpush
