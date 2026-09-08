@extends('frontend::layouts.guest')

@section('title')
    {{ __('frontend.manage_profile') }}
@endsection
@section('content')
    <!-- <div class="section-spacing"> -->
    <div class="container">
        <div class="d-flex flex-column justify-content-center vh-100">
            <div class="text-center">
                <h1 class="mb-4">{{ __('messages.lbl_who_watching') }}</h1>
            </div>
            <div class="profile-card-lists">
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-6 gy-4 justify-content-center">
                    @foreach ($userProfile as $profile)
                        <div class="col">
                            <div class="profile-card is-edit" onclick="SelectProfile11({{ $profile->id }})"
                                data-profile-id="{{ $profile->id }}" data-is-child="{{ $profile->is_child_profile }}">
                                <div class="profile-card-image">
                                    <img src="{{ $profile->avatar ?? asset('images/default-profile.png') }}"
                                        alt="profile card Image" class="img-fluid" />
                                </div>
                                <h5>{{ $profile->name }}</h5>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @if (getCurrentProfileSession('is_child_profile') == 0)
            <div class="text-center mt-5">
                <a href="{{ route('profile-management') }}" class="btn btn-dark py-3 h4 m-0 fw-normal">{{ __('messages.lbl_manage_profile') }}</a>
            </div>
            @endif
        </div>
    </div>
    <!-- </div> -->

    <div class="modal fade add-profile-modal" id="selectOTPModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content position-relative">
                <button type="button" class="btn btn-primary custom-close-btn rounded-2" data-bs-dismiss="modal">
                    <i class="ph ph-x text-white fw-bold align-middle"></i>
                </button>
                <form id="otpVerification" action="Post" class="requires-validation" data-toggle="validator" novalidate>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            @csrf
                            <h5>{{ __('frontend.otp_verification_title') }}</h5>
                            <p class="mb-5">{{ __('frontend.otp_sent_message') }}</p>

                            <div id="otp-form" class="d-flex justify-content-center align-items-center gap-md-3 gap-2 otp-form mb-5">
                                <input type="text" id="otp1" name="otp[]" class="otp-input mr-2" maxlength="1"
                                    required>
                                <input type="text" name="otp[]" class="otp-input" maxlength="1" required>
                                <input type="text" name="otp[]" class="otp-input" maxlength="1" required>
                                <input type="text" name="otp[]" class="otp-input" maxlength="1" required>
                            </div>

                            <div class="invalid-feedback text-center" id="otp_error">{{ __('frontend.otp_required_error') }}
                            </div>
                            <p class="text-danger text-center" id="otp_bk_error"></p>
                        </div>

                        <div>
                            <button type="button" id="otpBtn"
                                class="btn btn-primary mt-5">{{ __('frontend.verify_otp') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Set current profile data for PIN verification
        window.currentProfileData = {
            is_child_profile: {{ getCurrentProfileSession('is_child_profile') ?? 0 }},
            user_has_pin: {{ auth()->user()->pin ? 'true' : 'false' }},
            is_parental_lock_enable: {{ auth()->user()->is_parental_lock_enable ?? 0 }}
        };
        window.translations = {
            'messages.lbl_close': "{{ __('messages.lbl_close') }}",
            'messages.lbl_enter_pin': "{{ __('messages.lbl_enter_pin') }}",
            'messages.lbl_pin_description': "{{ __('messages.lbl_pin_description') }}",
            'messages.lbl_pin_digit': "{{ __('messages.lbl_pin_digit') }}",
            'messages.lbl_pin_required': "{{ __('messages.lbl_pin_required') }}",
            'messages.lbl_verify_pin': "{{ __('messages.lbl_verify_pin') }}",
            'messages.lbl_verifying': "{{ __('messages.lbl_verifying') }}",
            'messages.lbl_cancel': "{{ __('messages.lbl_cancel') }}",
            'messages.lbl_invalid_pin': "{{ __('messages.lbl_invalid_pin') }}",
            'messages.lbl_enter_all_digits': "{{ __('messages.lbl_enter_all_digits') }}",
            'messages.lbl_error_occurred': "{{ __('messages.lbl_error_occurred') }}",
            'messages.parental_lock': "{{ __('messages.parental_lock') }}"
        };
        window.userApiToken = '{{ auth()->user()->api_token }}';
    </script>
    <script src="{{ asset('js/pin-verification.js') }}"></script>
@endsection
