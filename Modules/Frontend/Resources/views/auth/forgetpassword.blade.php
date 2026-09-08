@extends('frontend::layouts.auth_layout')

@section('title')
    {{ __('frontend.forgot_password_title') }}
@endsection

@section('content')
    <div>
        <div class="vh-100"
            style="background: url('{{ asset('/dummy-images/login_banner.jpg') }}'); background-size: cover; background-repeat: no-repeat; position: relative;min-height:500px; overflow-y:auto;">
            <div class="container">
                <div class="row justify-content-center align-items-center height-self-center vh-100">
                    <div class="col-lg-5 col-md-12 align-self-center">
                        <div class="user-login-card card my-5">


                            <div class="text-center auth-heading">

                                @php
                                    $logo = GetSettingValue('dark_logo') ? setBaseUrlWithFileName(GetSettingValue('dark_logo'),'image','logos') : asset('img/logo/dark_logo.png');
                                @endphp

                                <a href="{{ route('user.login') }}">
                                    <img src="{{ $logo }}" class="img-fluid logo h-4 mb-4">
                                </a>


                                <h5>{{ __('frontend.forgot_password') }}</h5>
                                <p class="fs-14">{{ __('messages.forgot_password_email_sent_description') }}</p>
                                <!-- <p class="fs-14">{!! __('frontend.email_prompt') !!}</p> -->
                            </div>

                            <p class="text-danger" id="forgetpassword_error_message"></p>
                            <form id="forgetpassword-form" class="requires-validation" data-toggle="validator" novalidate>
                                <div class="mb-3">
                                    <div class="input-group mb-0">
                                        <span class="input-group-text"><i class="ph ph-envelope"></i></span>
                                        <input type="email" name="email" class="form-control"
                                            placeholder="{{ __('messages.placeholder_enter_registerd_email') }}"
                                            aria-describedby="basic-addon1" required>
                                    </div>
                                    <div class="invalid-feedback" id="name-error">Email field is required.</div>
                                </div>
                                <div class="full-button text-center">
                                    <button type="submit" class="btn btn-primary w-100" id="forget_password_btn">
                                        {{ __('messages.lbl_send_reset_link') }}
                                    </button>
                                </div>
                                <div class="border p-4 rounded mt-5 d-none" id="forget_password_msg">
                                    <h6>{{ __('frontend.link_sent_to_email') }}!</h6>
                                    <small class="mb-0">{{ __('frontend.check_inbox') }}.</small>
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
        window.localisation = {}
    </script>
@endsection
