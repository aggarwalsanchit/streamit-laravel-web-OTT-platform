@extends('frontend::layouts.auth_layout')

@section('title')
    {{ __('frontend.sign_up') }}
@endsection

@section('content')
    <div id="login">
        <div class="vh-100"
            style="background: url('{{ asset('/dummy-images/login_banner.jpg') }}'); background-size: cover; background-repeat: no-repeat; position: relative; min-height: 500px; overflow-y:auto;">
            <div class="container">
                <div class="row justify-content-center align-items-center height-self-center vh-100">
                    <div class="col-lg-5 col-md-8 col-sm-11 col-12 align-self-center">
                        <div class="user-login-card card my-5">
                            <div class="auth-heading">

                                <div class="text-center auth-heading">
                                    @php
                                        $logo = GetSettingValue('dark_logo')  ? setBaseUrlWithFileName(GetSettingValue('dark_logo'),'image','logos') : asset('img/logo/dark_logo.png');
                                    @endphp

                                    <a href="{{ route('user.login') }}">
                                        <img src="{{ $logo }}" class="img-fluid logo h-4 mb-4">
                                    </a>


                                    <h5>{{ __('frontend.sign_up_title') }}</h5>
                                    <!-- <p class="font-size-14">{{ __('frontend.sign_sub_title') }}</p> -->
                                </div>
                                <p class="text-danger" id="error_message"></p>
                                <form id="registerForm" action="post" class="requires-validation" data-toggle="validator"
                                    novalidate>

                                    <div class="mb-3">
                                        <div class="input-group mb-0">
                                            <span class="input-group-text"><i class="ph ph-user"></i></span>
                                            <input type="text" name="first_name" class="form-control"
                                                placeholder="{{ __('frontend.first_name') }}" required>
                                        </div>
                                        <div class="invalid-feedback" id="first_name_error">First Name field
                                            is required</div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="input-group mb-0">
                                            <span class="input-group-text"><i class="ph ph-user"></i></span>
                                            <input type="text" name="last_name" class="form-control"
                                                placeholder="{{ __('frontend.last_name') }}" required>
                                        </div>
                                        <div class="invalid-feedback" id="last_name_error">Last Name field
                                            is required</div>
                                    </div>
                                    <div class="mb-3">
                                            <div class="input-group mb-0">
                                            <span class="input-group-text"><i class="ph ph-envelope"></i></span>
                                            <input type="text" name="email" class="form-control"
                                                placeholder="{{ __('frontend.email') }}" required>
                                        </div>
                                        <div class="invalid-feedback" id="email_error">Email field is
                                            required</div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="input-group mb-0">
                                            <input type="tel" class="form-control" name="mobile" id="mobile"
                                                placeholder="{{ __('placeholder.lbl_user_conatct_number') }}" required>
                                        </div>
                                        <div class="invalid-feedback" id="mobile-error">Contact Number field is required</div>
                                    </div>
                                    <input type="hidden" name="country_code" id="country_code">

                                    <div class="mb-3">
                                            <div class="input-group mb-0">
                                            <span class="input-group-text"><i class="ph ph-lock-key"></i></span>

                                            <input type="password" name="password" class="form-control" id="password"
                                                placeholder="{{ __('frontend.password') }}" required>

                                            <span class="input-group-text" style="cursor:pointer;">
                                                <i class="ph ph-eye-slash" id="togglePassword"></i>
                                                <!-- Eye icon with unique ID -->
                                            </span>

                                        </div>
                                        <div class="invalid-feedback" id="password_error">Password field is
                                            required</div>
                                    </div>
                                    <div class="mb-3">
                                            <div class="input-group mb-0">
                                            <span class="input-group-text"><i class="ph ph-lock-key"></i></span>
                                            <input type="password" name="confirm_password" class="form-control"
                                                id="confirm_password" placeholder="{{ __('frontend.confirm_password') }}"
                                                required>
                                            <span class="input-group-text"><i class="ph ph-eye-slash"
                                                    id="toggleConfirmPassword"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="confirm_password_error">Confirm
                                            Password field is
                                            required</div>
                                    </div>
                                    <div class="full-button text-center">
                                        <button type="submit" id="register-button" class="btn btn-primary w-100"
                                            data-signup-text="{{ __('frontend.sign_up') }}">
                                            {{ __('frontend.sign_up') }}
                                        </button>
                                        <p class="mt-5 mb-0 fw-normal"> {{ __('frontend.already_have_account') }} <a
                                                href="{{ route('login') }}"
                                                class="ms-1 btn btn-link">{{ __('frontend.signin') }}</a></p>
                                    </div>
                                </form>
                                <p class="mt-3 mb-0 small text-muted w-80">
                                    {{ __('messages.by_signing_you_agree_to_streamit') }} {{ app_name() }}
                                    <a href="{{ route('page.show', ['slug' => 'terms-conditions']) }}" class="text-primary">{{ __('messages.terms_and_conditions') }}</a>
                                    {{ __('messages.of_all_services_and') }}
                                    <a href="{{ route('page.show', ['slug' => 'privacy-policy']) }}" class="text-primary"> {{ __('messages.privacy_policy') }}</a> {{ __('messages.of_all_services_and') }}.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="{{ asset('js/auth.min.js') }}" defer></script>
        <link rel="stylesheet" href="{{ asset('vendor/intl-tel-input/css/intlTelInput.css') }}">
        <script src="{{ asset('vendor/intl-tel-input/js/intlTelInput.min.js') }}"></script>
        <script src="{{ asset('vendor/flatpickr/flatpicker.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const mobileInput = document.getElementById('mobile');
                const form = document.getElementById('registerForm');

                mobileInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            });

            var input = document.querySelector("#mobile");
            if (input && window.intlTelInput) {
                var savedMobile = sessionStorage.getItem('otp_redirect_mobile');
                var savedCountryIso = sessionStorage.getItem('otp_redirect_country_iso');
                
                var iti = window.intlTelInput(input, {
                    initialCountry: savedCountryIso || "in",
                    separateDialCode: true,
                    customContainer: "w-100",
                    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
                });
                
                // Make iti globally accessible for auth.js
                window.iti = iti;

                if (savedMobile) {
                    input.value = savedMobile;
                    sessionStorage.removeItem('otp_redirect_mobile');
                    sessionStorage.removeItem('otp_redirect_country_code');
                    sessionStorage.removeItem('otp_redirect_country_iso');
                }
            }
        </script>
    @endsection
