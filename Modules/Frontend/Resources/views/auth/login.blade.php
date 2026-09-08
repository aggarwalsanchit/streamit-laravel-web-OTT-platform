@extends('frontend::layouts.auth_layout')

@section('title')
    {{ __('frontend.sign_in') }}
@endsection

@section('content')
    <div id="login">

        <div class="vh-100"
            style="background: url('{{ asset('/dummy-images/login_banner.jpg') }}'); background-size: cover; background-repeat: no-repeat; position: relative;min-height:500px; overflow-y:auto;">
            <div class="container">
                <div class="row justify-content-center align-items-center height-self-center vh-100">
                    <div class="col-lg-5 col-md-8 col-sm-11 col-12 align-self-center">
                        <div class="user-login-card card my-5">
                            <div class="text-center auth-heading">
                                @php
                                    $logo = GetSettingValue('dark_logo')  ? setBaseUrlWithFileName(GetSettingValue('dark_logo'),'image','logos') : asset('img/logo/dark_logo.png');
                                @endphp

                                <a href="{{ route('user.login') }}" class="d-inline-block">
                                    <img src="{{ $logo }}" class="img-fluid logo h-4 mb-4">
                                </a>

                                <h5>{{ __('frontend.sign_in_title') }} {{ app_name() }}!</h5>
                                <!-- <p class="fs-14">{{ __('frontend.sign_in_sub_title') }}</p> -->
                                {{-- @if (session()->has('error'))
                                    <span class="text-danger">{{ session()->get('error') }}</span>
                                @endif --}}
                            </div>

                            <div id="logout_message_alert" class="alert alert-warning alert-dismissible fade show" role="alert" style="display: none;">
                                <span id="logout_message_text"></span>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>

                            <p class="text-danger" id="login_error_message"></p>
                            <form action="post" id="login-form" class="requires-validation" data-toggle="validator"
                                novalidate>
                            <div class="mb-3">
                                <div class="input-group mb-0">
                                    <span class="input-group-text"><i class="ph ph-envelope"></i></span>
                                    <input type="email" name="email" id="email" class="form-control"
                                        placeholder="{{ __('frontend.enter_email') }}" aria-describedby="basic-addon1"
                                        required>
                                </div>
                                <div class="invalid-feedback" id="name-error">Email field is required.</div>
                            </div>

                            <div class="mb-3">
                                <div class="input-group mb-0">
                                    <span class="input-group-text"><i class="ph ph-lock-key"></i></span>
                                    <input type="password" name="password" class="form-control" id="password"
                                        placeholder="{{ __('messages.enter_password') }}" required>

                                    <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
                                        <i class="ph ph-eye-slash" id="toggleIcon"></i>
                                    </span>
                                </div>
                                <div class="invalid-feedback" id="password-error">Password field is required.</div>
                            </div>
                                <div class="d-flex flex-wrap align-items-center justify-content-between">
                                    <label class="list-group-item d-flex align-items-center"><input
                                            class="form-check-input m-0 me-2"
                                            type="checkbox" name="remember" id="remember">{{ __('frontend.remember_me') }}</label>
                                    <a href="{{ url('/forget-password') }}"
                                        class="btn btn-link">{{ __('frontend.forgot_password') }}</a>
                                </div>
                                <div class="full-button text-center">
                                    <button type="submit" id="login-button" class="btn btn-primary w-100">
                                        {{ __('frontend.sign_in') }}
                                    </button>
                                    <p class="mt-2 mb-0 fw-normal">{{ __('frontend.not_have_account') }}
                                        <a href="{{ route('register-page') }}"
                                            class="ms-1 btn btn-link">{{ __('frontend.sign_up') }}</a>
                                    </p>
                                </div>

                                @if (setting('is_otp_login') == 1 || setting('is_google_login') == 1)
                                    <div class="border-style">
                                        <span>Or</span>
                                    </div>
                                @endif
                                <div class="full-button text-center">
                                    <div class="d-flex align-items-center gap-3">
                                        @if (setting('is_google_login') == 1)
                                            <a href="{{ route('auth.google') }}" class="d-block w-100">
                                                <span id="google-login" class="btn btn-dark w-100">
                                                    <svg class="me-2" width="16" height="16" viewBox="0 0 16 16"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M3.4473 8.00005C3.4473 7.48042 3.5336 6.98224 3.68764 6.51496L0.991451 4.45605C0.465978 5.52296 0.169922 6.72515 0.169922 8.00005C0.169922 9.27387 0.465614 10.4753 0.990358 11.5415L3.68509 9.4786C3.53251 9.01351 3.4473 8.51715 3.4473 8.00005Z"
                                                            fill="#FBBC05" />
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M8.18202 3.27273C9.3109 3.27273 10.3305 3.67273 11.1317 4.32727L13.4622 2C12.042 0.763636 10.2213 0 8.18202 0C5.01608 0 2.29513 1.81055 0.992188 4.456L3.68838 6.51491C4.30962 4.62909 6.0805 3.27273 8.18202 3.27273Z"
                                                            fill="#EB4335" />
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M8.18202 12.7275C6.0805 12.7275 4.30962 11.3712 3.68838 9.48535L0.992188 11.5439C2.29513 14.1897 5.01608 16.0003 8.18202 16.0003C10.1361 16.0003 12.0016 15.3064 13.4018 14.0064L10.8425 12.0279C10.1204 12.4828 9.21112 12.7275 8.18202 12.7275Z"
                                                            fill="#34A853" />
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M15.8289 7.99996C15.8289 7.52723 15.756 7.01814 15.6468 6.54541H8.18164V9.63632H12.4786C12.2638 10.6901 11.679 11.5003 10.8421 12.0276L13.4014 14.0061C14.8722 12.641 15.8289 10.6076 15.8289 7.99996Z"
                                                            fill="#4285F4" />
                                                    </svg>
                                                    {{ __('frontend.continue_with_google') }}
                                                </span>
                                            </a>
                                        @endif
                                        @if (setting('is_otp_login') == 1)
                                            <a href="{{ route('otp-login') }}" class="d-block w-100">
                                                <span id="otp-login" class="btn btn-dark w-100">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="me-2" width="20"
                                                        height="20" viewBox="0 0 34 34" fill="none">
                                                        <g clip-path="url(#clip0_11736_30488)">
                                                            <path d="M4 26H17V27H4V26Z" fill="#999797" />
                                                            <path
                                                                d="M10.5 30.5C9.67163 30.5 9 29.8284 9 29C9 28.1716 9.67163 27.5 10.5 27.5C11.3284 27.5 12 28.1716 12 29C12 29.8284 11.3284 30.5 10.5 30.5ZM10.5 28.5C10.2239 28.5 10 28.7239 10 29C10 29.2761 10.2239 29.5 10.5 29.5C10.7761 29.5 11 29.2761 11 29C11 28.7239 10.7761 28.5 10.5 28.5Z"
                                                                fill="#999797" />
                                                            <path
                                                                d="M18 29.5C18 30.3284 17.3284 31 16.5 31H4.5C3.67163 31 3 30.3284 3 29.5V4.5C3 3.67163 3.67163 3 4.5 3H5.5V3.5C5.5 4.32837 6.17163 5 7 5H14C14.8284 5 15.5 4.32837 15.5 3.5V3H16.5C17.3284 3 18 3.67163 18 4.5V8H19V4.5C18.9983 3.11987 17.8801 2.00171 16.5 2H4.5C3.11987 2.00171 2.00171 3.11987 2 4.5V29.5C2.00171 30.8801 3.11987 31.9983 4.5 32H16.5C17.8801 31.9983 18.9983 30.8801 19 29.5V18H18V29.5ZM6.5 3H14.5V3.5C14.5 3.77612 14.2761 4 14 4H7C6.72387 4 6.5 3.77612 6.5 3.5V3Z"
                                                                fill="#999797" />
                                                            <path d="M9 9V17H32V9H9ZM31 16H10V10H31V16Z" fill="#999797" />
                                                            <path
                                                                d="M11.5176 14.4331L12.4995 13.866V15H13.4995V13.866L14.4814 14.4331L14.9814 13.5669L13.9995 13L14.9814 12.4331L14.4814 11.5669L13.4995 12.134V11H12.4995V12.134L11.5176 11.5669L11.0176 12.4331L11.9995 13L11.0176 13.5669L11.5176 14.4331Z"
                                                                fill="#999797" />
                                                            <path
                                                                d="M16.5176 14.4331L17.4995 13.866V15H18.4995V13.866L19.4814 14.4331L19.9814 13.5669L18.9995 13L19.9814 12.4331L19.4814 11.5669L18.4995 12.134V11H17.4995V12.134L16.5176 11.5669L16.0176 12.4331L16.9995 13L16.0176 13.5669L16.5176 14.4331Z"
                                                                fill="#999797" />
                                                            <path
                                                                d="M21.5176 14.4331L22.4995 13.866V15H23.4995V13.866L24.4814 14.4331L24.9814 13.5669L23.9995 13L24.9814 12.4331L24.4814 11.5669L23.4995 12.134V11H22.4995V12.134L21.5176 11.5669L21.0176 12.4331L21.9995 13L21.0176 13.5669L21.5176 14.4331Z"
                                                                fill="#999797" />
                                                            <path
                                                                d="M26.5176 14.4331L27.4995 13.866V15H28.4995V13.866L29.4814 14.4331L29.9814 13.5669L28.9995 13L29.9814 12.4331L29.4814 11.5669L28.4995 12.134V11H27.4995V12.134L26.5176 11.5669L26.0176 12.4331L26.9995 13L26.0176 13.5669L26.5176 14.4331Z"
                                                                fill="#999797" />
                                                        </g>
                                                        <defs>
                                                            <clipPath id="clip0_11736_30488">
                                                                <rect width="30" height="30" fill="white"
                                                                    transform="translate(2 2)" />
                                                            </clipPath>
                                                        </defs>
                                                    </svg>
                                                    {{ __('frontend.login_with_otp') }}
                                                </span>
                                            </a>
                                        @endif
                                    </div>

                                    @if (setting('demo_login') == 1)
                                        <div> <h6 class="text-center border-top py-3 mt-3">Demo Accounts</h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <p class="mb-0" id="user_email">john@gmail.com</p>
                                                    <p class="mb-0" id="user_password">12345678</p>
                                                </div>
                                                <div>
                                                    <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-title="Click To Copy"
                                                        onclick="setLoginCredentials('user')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                            stroke-width="1.5" stroke="currentColor" width="18" height="18">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                                                        </svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('admin-login') }}" class="d-block mt-5 btn btn-link">
                                            {{ __('installer_messages.final.admin_panel') }}</a>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/auth.min.js') }}" defer></script>
    <script>
        function domId(name) {
            return document.getElementById(name);
        }

        function setLoginCredentials(type) {
            domId('email').value = domId(type + '_email').textContent;
            domId('password').value = domId(type + '_password').textContent;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const rememberCheckbox = document.getElementById('remember');
            const loginForm = document.getElementById('login-form');

            const logoutMessage = localStorage.getItem('logout_message');
            if (logoutMessage) {
                const alertDiv = document.getElementById('logout_message_alert');
                const messageText = document.getElementById('logout_message_text');
                if (alertDiv && messageText) {
                    messageText.textContent = logoutMessage;
                    alertDiv.style.display = 'block';
                    localStorage.removeItem('logout_message');
                }
            }

            const savedEmail = localStorage.getItem('remembered_email');
            const savedPassword = localStorage.getItem('remembered_password');
            if (savedEmail && savedPassword) {
                emailInput.value = savedEmail;
                passwordInput.value = savedPassword;
                rememberCheckbox.checked = true;
            }

            loginForm.addEventListener('submit', function() {
                if (rememberCheckbox.checked) {
                    localStorage.setItem('remembered_email', emailInput.value);
                    localStorage.setItem('remembered_password', passwordInput.value);
                } else {
                    localStorage.removeItem('remembered_email');
                    localStorage.removeItem('remembered_password');
                }
            });

            rememberCheckbox.addEventListener('change', function() {
                if (!this.checked) {
                    localStorage.removeItem('remembered_email');
                    localStorage.removeItem('remembered_password');
                }
            });
            setInterval(() => {
                const passwordInput = document.getElementById('password');
                const toggleSpan = document.getElementById('togglePassword');
                const toggleIcon = document.getElementById('toggleIcon');

                if (passwordInput && toggleSpan && toggleIcon) {
                    // Replace existing toggle span to remove any previous event handlers
                    const newToggleSpan = toggleSpan.cloneNode(true);
                    toggleSpan.parentNode.replaceChild(newToggleSpan, toggleSpan);

                    newToggleSpan.addEventListener('click', function() {
                        const isHidden = passwordInput.type === 'password';
                        passwordInput.type = isHidden ? 'text' : 'password';

                        const icon = newToggleSpan.querySelector('i');
                        if (icon) {
                            icon.classList.remove(isHidden ? 'ph-eye-slash' : 'ph-eye');
                            icon.classList.add(isHidden ? 'ph-eye' : 'ph-eye-slash');
                        }
                    });
                }
            }, 500); // Adjust time if needed

            @if(session()->has('device_limit_error') && session()->has('device_limit_devices') && session()->get('error') !== __('messages.not_matched'))
                const loginErrorMsg = document.getElementById('login_error_message');
                if (loginErrorMsg) {
                    loginErrorMsg.textContent = @json(session()->get('error', ''));
                }

                const devices = @json(session()->get('device_limit_devices', []));
                if (devices && devices.length > 0) {
                    // Wait for auth.js to load (it's loaded with defer)
                    function checkAndRenderDeviceLimit() {
                        if (typeof renderDeviceLimitUI === 'function') {
                            renderDeviceLimitUI(devices);
                        } else {
                            setTimeout(checkAndRenderDeviceLimit, 100);
                        }
                    }
                    setTimeout(checkAndRenderDeviceLimit, 300);
                }
            @endif

            @if(session()->has('error') && !session()->has('device_limit_error'))
                const loginErrorMsgSimple = document.getElementById('login_error_message');
                if (loginErrorMsgSimple) {
                    loginErrorMsgSimple.textContent = @json(session()->get('error', ''));
                }
            @endif
        });
    </script>
@endsection
