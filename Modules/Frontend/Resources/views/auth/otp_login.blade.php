@extends('frontend::layouts.auth_layout')

@section('title')
    {{ __('frontend.sign_in') }}
@endsection

@section('content')
    <div id="login">

        <div class="vh-100" style="background-image: url('{{ asset('/dummy-images/login_banner.jpg') }}')">
            <div class="container">
                <div class="row justify-content-center align-items-center height-self-center vh-100">

                    <div class="col-lg-8 col-md-10 col-11 align-self-center">
                        <div class="user-login-card card my-5">
                            <div class="text-center auth-heading">
                                @php
                                    $logo = GetSettingValue('dark_logo') ? setBaseUrlWithFileName(GetSettingValue('dark_logo'),'image','logos') : asset('img/logo/dark_logo.png');
                                @endphp
                                <a href="{{ route('user.login') }}" class="d-inline-block">
                                    <img src="{{ $logo }}" class="img-fluid logo h-4 mb-4">
                                </a>
                                <h5>{{ __('frontend.sign_in_title') }} {{ app_name() }}!</h5>
                                <!-- <p class="fs-14">{{ __('frontend.sign_in_sub_title') }}</p> -->
                                @if (session()->has('error'))
                                    <span class="text-danger">{{ session()->get('error') }}</span>
                                @endif
                            </div>

                            <div class="row gy-3">
                                <div class="col-md-5">
                                    <div class="text-center">
                                        <div class="scanner">
                                            {!! $qrCode !!}
                                        </div>
                                        <h5>{{ __('messages.use_camera_app_to_scan_qr') }}</h5>
                                        <p>{{ __('messages.click_on_the_link_generated_to_redirect_to_mobile_app', ['app' => setting('app_name')]) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="center-divider">
                                        <span>{{ __('frontend.or_text') }}</span>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <p class="text-danger" id="otp_error_message"></p>
                                    <p class="text-success" id="otp_success_message"></p>
                                    <p class="fs-14" id="otp_subtitle"></p>


                                    <!-- Mobile Number Form -->
                                    <div id="mobile-form">
                                        <form id="send-otp-form" class="requires-validation" data-toggle="validator"
                                            novalidate onsubmit="handleMobileFormSubmit(event)">
                                           <div class="mb-3">
                                                <div class="input-group mb-0 flex-nowrap">
                                                    <span class="input-group-text"><i class="ph ph-phone"></i></span>
                                                    <input type="tel" id="mobile"
                                                        @if (setting('demo_login') == 1) value="1234567890" @endif
                                                        class="form-control" pattern="[0-9]{10}"
                                                        placeholder="{{ __('frontend.enter_mobile') }}" required
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                                </div>
                                                <div class="invalid-feedback" id="mobile-error">{{ __('frontend.mobile_required') }}
                                                </div>
                                            </div>
                                            <div id="recaptcha-container" class="d-none"></div>
                                            <div class="full-button text-center">
                                                <button type="submit" id="send-otp-button" class="btn btn-primary">
                                                    <span id="send-button-text">
                                                        <i class="fa-solid fa-paper-plane"></i>
                                                        {{ __('frontend.send_otp') }}
                                                    </span>
                                                    <span id="send-button-spinner" class="d-none">
                                                        <span class="spinner-border spinner-border-sm" role="status"
                                                            aria-hidden="true"></span>
                                                        {{ __('messages.loading') }}
                                                    </span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- OTP Verification Form -->
                                    <div id="otp-form" style="display: none;">
                                        <p>{{ __('messages.demo_otp') }} 123456</p>
                                        <form id="verify-otp-form" class="requires-validation" data-toggle="validator"
                                            novalidate onsubmit="handleOtpFormSubmit(event)">
                                            <div class="mb-3 text-center">
                                                <div class="d-flex justify-content-between gap-2 mx-auto"
                                                    style="max-width: 260px;">
                                                    @for ($i = 0; $i < 6; $i++)
                                                        <input type="text" inputmode="numeric" maxlength="1"
                                                            class="form-control text-center otp-input border"
                                                            style="max-width: 42px; padding: .375rem .25rem;">
                                                    @endfor
                                                </div>
                                                <!-- Hidden field that will contain the full OTP -->
                                                <input type="hidden" name="otp" id="otp" required>
                                            </div>
                                            <div class="invalid-feedback" id="otp-error">{{ __('messages.otp_required') }}</div>
                                            <div id="otp-timer" style="color: red; display: none;">{!! __('frontend.resend_otp_timer', ['seconds' => '<span id="timer"></span>']) !!}
                                            </div>
                                            <div class="full-button text-center">
                                                <button type="submit" id="verify-otp-button" class="btn btn-primary w-100">
                                                    <span id="button-text">
                                                        <i class="fa-solid fa-floppy-disk"></i>
                                                        {{ __('frontend.verify_otp') }}
                                                    </span>
                                                    <span id="button-spinner" class="d-none">
                                                        <span class="spinner-border spinner-border-sm" role="status"
                                                            aria-hidden="true"></span>
                                                        {{ __('messages.loading') }}
                                                    </span>
                                                </button>
                                                <div id="resend_code">
                                                    <p class="mt-2 mb-0 fw-normal">{{ __('frontend.not_receive_otp') }}
                                                        <a type="button" href="#" class="ms-1" id="resend-otp"
                                                            onclick="resendCode()">{{ __('frontend.resend_otp') }}</a>
                                                    </p>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>


                            <div id="registerForm" @if ($errors->any()) style="display: block;" @else style="display: none;" @endif>
                                <form action="{{ route('auth.otp-login-store') }}" method="post"
                                    class="requires-validation" data-toggle="validator" novalidate>
                                    @csrf
                                    <div class="mb-3">
                                        <div class="input-group mb-0">
                                            <span class="input-group-text   "><i class="ph ph-phone"></i></span>
                                            <input type="text" name="mobile" id="mobile_number" class="form-control"
                                                placeholder="{{ __('frontend.enter_mobile') }}"
                                                aria-describedby="basic-addon1" value="{{ old('mobile') }}" required
                                                readonly>
                                        </div>
                                        <div class="invalid-feedback" id="mobile-error">
                                            {{ __('frontend.mobile_required') }}
                                        </div>
                                    </div>
                                    <input type="hidden" name="country_code" id="country_code"
                                        value="{{ old('country_code') }}">

                                    <div class="mb-3">
                                        <div class="input-group mb-0">
                                            <span class="input-group-text   "><i class="ph ph-user"></i></span>
                                            <input type="text" name="first_name" class="form-control"
                                                placeholder="{{ __('frontend.enter_fname') }}"
                                                value="{{ old('first_name') }}" required>
                                        </div>
                                        <div class="invalid-feedback" id="first_name_error">
                                            {{ __('frontend.first_name_required') }}
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="input-group mb-0">
                                            <span class="input-group-text   "><i class="ph ph-user"></i></span>
                                            <input type="text" name="last_name" class="form-control"
                                                placeholder="{{ __('frontend.enter_lname') }}"
                                                value="{{ old('last_name') }}" required>
                                        </div>
                                        <div class="invalid-feedback" id="last_name_error">
                                            {{ __('frontend.last_name_required') }}
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="input-group mb-0">
                                            <span class="input-group-text   "><i class="ph ph-envelope"></i></span>
                                            <input type="text" name="email" class="form-control"
                                                placeholder="{{ __('frontend.enter_email') }}"
                                                value="{{ old('email') }}" required>
                                        </div>
                                        <div class="invalid-feedback @error('email') d-block @enderror"
                                            id="email_error">
                                            @error('email')
                                                {{ $message }}
                                            @else
                                                {{ __('frontend.email_required') }}
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="full-button text-center">
                                        <button type="submit" id="register-button" class="btn btn-primary w-100"
                                            data-signup-text="{{ __('frontend.sign_up') }}">
                                            {{ __('frontend.sign_up') }}
                                        </button>
                                    </div>
                                </form>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://www.gstatic.com/firebasejs/6.0.2/firebase.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <script>
        var isOtpLoginEnabled = {{ json_encode($isOtpLoginEnabled) }};

        if (isOtpLoginEnabled) {
            var firebaseConfig = {
                @foreach ($settings as $setting)
                    @if (in_array($setting->name, [
                            'apiKey',
                            'authDomain',
                            'databaseURL',
                            'projectId',
                            'storageBucket',
                            'messagingSenderId',
                            'appId',
                            'measurementId',
                        ]))
                        '{{ $setting->name }}': '{{ $setting->val }}',
                    @endif
                @endforeach
            };


            firebase.initializeApp(firebaseConfig);
        } else {
            console.log('OTP login is disabled. Firebase not initialized.');
        }
    </script>

    <script type="text/javascript">
        const qrId = "{{ $qrSession->id }}";
        const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
        const homepage = "{{ route('user.login') }}";

        window.onload = function() {
            render();
            checkQrStatus();

        }

        function checkQrStatus() {
            axios.get(baseUrl + '/web-qr-status/' + qrId)
                .then(res => {
                    if (res.data.status === 'authenticated') {
                        window.location.href = homepage; // redirect after login
                    } else {
                        setTimeout(checkQrStatus, 2000); // poll every 2 sec
                    }
                })
                .catch(err => console.error(err));
        }

        function render() {
            window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
                size: 'invisible'
            });
            recaptchaVerifier.render();
        }
        var input = document.querySelector("#mobile");
        var iti = window.intlTelInput(input, {
            initialCountry: "in", // Automatically detect user's country
            separateDialCode: true, // Show the country code separately
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js" // To handle number formatting
        });

        let timerInterval;
        var number = '';

        // Handle mobile form submission (Enter key press)
        function handleMobileFormSubmit(event) {
            event.preventDefault();
            sendCode();
        }

        // Handle OTP form submission (Enter key press)
        function handleOtpFormSubmit(event) {
            event.preventDefault();
            verifyCode();
        }

        function updateCountryCode() {
            if (iti && iti.getSelectedCountryData) {
                let dial = iti.getSelectedCountryData().dialCode;
                document.getElementById('country_code').value = "+" + dial;
            }
        }

        input.addEventListener("countrychange", updateCountryCode);
        input.addEventListener("input", updateCountryCode);

        document.addEventListener('DOMContentLoaded', function() {
            const otpInputs = document.querySelectorAll('#otp-form .otp-input');
            const hiddenOtp = document.getElementById('otp');
            if (!otpInputs.length || !hiddenOtp) return;

            const demoLoginEnabled = {{ setting('demo_login') == 1 ? 'true' : 'false' }};

            function updateHiddenOtp() {
                hiddenOtp.value = Array.from(otpInputs).map(i => i.value).join('');
            }

            otpInputs.forEach((el, index) => {
                el.addEventListener('input', function(e) {
                    this.value = this.value.replace(/\D/g, '').slice(0, 1);

                    if (this.value && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                        otpInputs[index + 1].select();
                    }
                    updateHiddenOtp();
                });

                el.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        otpInputs[index - 1].focus();
                        otpInputs[index - 1].value = '';
                        updateHiddenOtp();
                    }
                });

                // Handle pasting full OTP
                el.addEventListener('paste', function(e) {
                    const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                    if (!paste) return;
                    e.preventDefault();
                    paste.split('').slice(0, otpInputs.length).forEach((ch, i) => {
                        otpInputs[i].value = ch;
                    });
                    const last = Math.min(paste.length, otpInputs.length) - 1;
                    if (last >= 0) {
                        otpInputs[last].focus();
                        otpInputs[last].select();
                    }
                    updateHiddenOtp();
                });
            });
        });

        function sendCode() {
            var number = iti.getNumber();
            var mobileOnly = document.getElementById('mobile').value;

            if (iti.isValidNumber()) {
                const deviceLimitSection = document.getElementById('device-limit-section');
                if (deviceLimitSection) {
                    deviceLimitSection.remove();
                }
                // Disable the button and show spinner while processing
                document.getElementById('send-otp-button').disabled = true;
                document.getElementById('send-button-text').classList.add('d-none');
                document.getElementById('send-button-spinner').classList.remove('d-none');

                // First check if mobile number exists in database
                $.ajax({
                    url: '{{ route("check.mobile.exists") }}',
                    type: 'POST',
                    data: {
                        mobile: number,  // Full number with country code like +911234567890
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.status === true) {
                            // User exists - send Firebase OTP
                            sendFirebaseOTP(number);
                        } else {
                            // User does not exist - store data and redirect to signup
                            document.getElementById('send-otp-button').disabled = false;
                            document.getElementById('send-button-text').classList.remove('d-none');
                            document.getElementById('send-button-spinner').classList.add('d-none');
                            
                            // Store mobile data in sessionStorage for registration page
                            var countryData = iti.getSelectedCountryData();
                            var mobileOnly = document.getElementById('mobile').value;
                            sessionStorage.setItem('otp_redirect_mobile', mobileOnly);
                            sessionStorage.setItem('otp_redirect_country_code', '+' + countryData.dialCode);
                            sessionStorage.setItem('otp_redirect_country_iso', countryData.iso2);
                            
                            window.location.href = '{{ route("register-page") }}';
                        }
                    },
                    error: function(xhr) {
                        // On error, show message and re-enable button
                        var errorMsg = @json(__('messages.lbl_error_occurred'));
                        $('#otp_error_message').text(errorMsg).show();
                        document.getElementById('send-otp-button').disabled = false;
                        document.getElementById('send-button-text').classList.remove('d-none');
                        document.getElementById('send-button-spinner').classList.add('d-none');
                    }
                });
            } else {
                // Invalid phone number case
                $('#mobile-error').text(@json(__('messages.invalid_phone_number')));
                $('#mobile-error').show();
            }
        }

        // Firebase OTP sending logic
        function sendFirebaseOTP(number) {
            firebase.auth().signInWithPhoneNumber(number, window.recaptchaVerifier)
                .then(function(confirmationResult) {
                    // Successfully sent OTP
                    window.confirmationResult = confirmationResult;
                    coderesult = confirmationResult;

                    $('#mobile-form').hide();
                    $('#otp_error_message').text("");
                    $('#otp-form').show();

                    $('#otp_title').text(@json(__('messages.verify_otp')));
                    $('#otp_subtitle').text(@json(__('messages.otp_sent_sub_title')));

                    startOtpTimer();
                })
                .catch(function(error) {
                    // Initialize error message variable
                    let errorMessage;

                        // Handle invalid phone number error
                        if (error.code === 'auth/invalid-phone-number') {
                            errorMessage = @json(__('messages.enter_valid_mobile_number'));
                        } else {
                            // Handle other errors, especially when error.message is a JSON string
                            try {
                                const errorData = JSON.parse(error.message);
                                if (errorData.error) {
                                    // Check for CONSUMER_SUSPENDED error
                                    if (errorData.error.status === 'PERMISSION_DENIED' || 
                                        (errorData.error.details && errorData.error.details.some(detail => 
                                            detail.reason === 'CONSUMER_SUSPENDED'))) {
                                        errorMessage = @json(__('messages.firebase_api_key_suspended'));
                                    }
                                    // Check for other permission denied errors
                                    else if (errorData.error.status === 'PERMISSION_DENIED') {
                                        errorMessage = @json(__('messages.firebase_permission_denied'));
                                    }
                                    // Check for BILLING_NOT_ENABLED error
                                    else if (errorData.error.errors && Array.isArray(errorData.error.errors)) {
                                        const billingError = errorData.error.errors.find(err => err.message === "BILLING_NOT_ENABLED");
                                        if (billingError) {
                                            errorMessage = @json(__('messages.demo_login_credentials'));
                                        }
                                        // Check for CONSUMER_SUSPENDED in errors array
                                        const suspendedError = errorData.error.errors.find(err => 
                                            err.message && err.message.includes('suspended') ||
                                            err.reason === 'CONSUMER_SUSPENDED'
                                        );
                                        if (suspendedError) {
                                            errorMessage = @json(__('messages.firebase_api_key_suspended'));
                                        }
                                    }
                                }
                            } catch (e) {
                                // JSON parsing failed, check if error message contains suspension keywords
                                if (error.message && (
                                    error.message.includes('suspended') || 
                                    error.message.includes('CONSUMER_SUSPENDED') ||
                                    error.message.includes('PERMISSION_DENIED')
                                )) {
                                    errorMessage = @json(__('messages.firebase_api_key_suspended'));
                                } else {
                                    console.error("Error parsing error.message:", e);
                                }
                            }

                            // Fallback to the original error message if no specific error is found
                            if (!errorMessage) {
                                errorMessage = error.message || @json(__('messages.lbl_error_occurred'));
                                // If error message is still a JSON string, show generic error
                                if (errorMessage && errorMessage.trim().startsWith('{')) {
                                    errorMessage = @json(__('messages.firebase_permission_denied'));
                                }
                            }
                        }

                    // Display the error message
                    $('#otp_error_message').text(errorMessage).show();
                })
                .finally(function() {
                    // Re-enable the button and hide the spinner after the process completes
                    document.getElementById('send-otp-button').disabled = false;
                    document.getElementById('send-button-text').classList.remove('d-none');
                    document.getElementById('send-button-spinner').classList.add('d-none');
                });
        }



        function startOtpTimer() {
            let timeLeft = 60;
            $('#otp-timer').show();
            $('#resend_code').addClass('d-none');
            $('#timer').text(60);

            timerInterval = setInterval(function() {
                timeLeft--;
                $('#timer').text(timeLeft);

                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    $('#otp-timer').hide();
                    $('#resend_code').removeClass('d-none');
                }
            }, 1000);
        }


        function verifyCode() {
            var code = $('#otp').val();

            if (code == '') {

                $('.invalid-feedback').css('display', 'block');
                $('#otp-error').text(@json(__('messages.otp_required')));
                return;
            }

            // Show loading spinner and disable the button
            document.getElementById('verify-otp-button').disabled = true;
            document.getElementById('button-text').classList.add('d-none');
            document.getElementById('button-spinner').classList.remove('d-none');

            var numbervalue = iti.getNumber()

            coderesult.confirm(code).then(function(result) {
                var user = result.user;
                $.ajax({
                    url: '{{ route('check.user.exists') }}', // Replace with your API URL
                    type: 'get', // Use POST method
                    data: {
                        user_id: user.uid, // Example of sending the user data
                        mobile: numbervalue, // Send the mobile number as well
                    },
                    success: function(response) {

                        if (response.is_user_exists == 0) {
                            $('#otp-form').hide();
                            $('#otp_title').text(@json(__('messages.personal_details')));
                            $('#otp_subtitle').text(@json(__('messages.provide_additional_details')));
                            $('#mobile_number').val(numbervalue);
                            $('#otp_error_message').text('');
                            $('#registerForm').show();
                        }

                        if (response.status == 406) {

                            $('#mobile-form').show();
                            $('#otp-form').hide();
                            $('#otp_error_message').text(response.message);
                            $('#otp_error_message').show();

                            const devices = (response && response.other_device) ? response.other_device : [];
                            if (devices.length > 0) {
                                renderDeviceLimitUI(devices);
                            }

                        }

                        if (response.url && response.is_user_exists == 1) {
                            window.location = response.url;
                        }
                    },
                    error: function(error) {
                        $('#otp_error_message').text(error);
                        $('#otp_error_message').show();
                    },
                    complete: function() {
                        // Re-enable the button and hide the spinner after the request is complete
                        document.getElementById('verify-otp-button').disabled = false;
                        document.getElementById('button-text').classList.remove('d-none');
                        document.getElementById('button-spinner').classList.add('d-none');
                    }
                });

            }).catch(function(error) {
                var errorMessage = error.message;
                if (error.message && error.message.includes('SMS verification code used to create the phone auth credential is invalid')) {
                    errorMessage = @json(__('messages.invalid_otp'));
                }

                $('#otp_error_message').text(errorMessage);
                $('#otp_error_message').show();
                document.getElementById('verify-otp-button').disabled = false;
                document.getElementById('button-text').classList.remove('d-none');
                document.getElementById('button-spinner').classList.add('d-none');
            });

        }

        setTimeout(function() {
            $('#otp_error_message').hide(); // Hide the error message after 2 seconds (2000 milliseconds)
            $('#otp_success_message').hide();
        }, 2000);

        function renderDeviceLimitUI(devices) {
            const baseUrl = document.querySelector('meta[name="base-url"]')?.getAttribute('content') || '';
            const deviceLimitInstruction = @json(__('messages.device_limit_instruction'));
            const logoutAllDevices = @json(__('messages.logout_all_devices'));
            const loggingOutAllDevices = @json(__('messages.logging_out_all_devices'));
            const unableToDetermineUser = @json(__('messages.unable_to_determine_user'));
            const failedLogoutAll = @json(__('messages.failed_logout_all'));
            const allDevicesLoggedOut = @json(__('messages.all_devices_logged_out'));
            const systemError = @json(__('messages.system_error'));
            const noDevicesFound = @json(__('messages.no_devices_found'));
            const logout = @json(__('messages.logout'));
            const loggingOut = @json(__('messages.logging_out'));
            const failedDeleteDevice = @json(__('messages.failed_delete_device'));
            const deviceRemovedInfo = @json(__('messages.device_removed_info'));
            const deviceLabel = @json(__('messages.device'));
            const unknownLabel = @json(__('messages.unknown'));

            let section = document.getElementById('device-limit-section');
            if (!section) {
                section = document.createElement('div');
                section.id = 'device-limit-section';
                section.className = 'mt-3';
                const mobileForm = document.getElementById('mobile-form');
                if (mobileForm) {
                    mobileForm.appendChild(section);
                }
            }
            section.innerHTML = '';
            const alert = document.createElement('div');
            alert.className = 'alert alert-warning mb-2 device-limit-alert';
            alert.textContent = deviceLimitInstruction;

            const logoutAllBtnContainer = document.createElement('div');
            logoutAllBtnContainer.className = 'text-end mb-3';
            const logoutAllBtn = document.createElement('button');
            logoutAllBtn.type = 'button';
            logoutAllBtn.className = 'btn btn-danger btn-sm';
            logoutAllBtn.textContent = logoutAllDevices;
            logoutAllBtnContainer.appendChild(logoutAllBtn);
            logoutAllBtn.addEventListener('click', async function() {
                logoutAllBtn.disabled = true;
                logoutAllBtn.textContent = loggingOutAllDevices;
                $('#otp_error_message').text('').hide();

                try {
                    const userId = devices && devices.length > 0 ? devices[0].user_id : null;
                    if (!userId) {
                        $('#otp_error_message').text(unableToDetermineUser).show();
                        logoutAllBtn.disabled = false;
                        logoutAllBtn.textContent = logoutAllDevices;
                        return;
                    }

                    const resp = await fetch(`${baseUrl}/api/logout-all-data?user_id=${encodeURIComponent(userId)}`, {
                        method: 'GET',
                        credentials: 'same-origin'
                    });
                    const json = await resp.json().catch(() => ({}));

                    if (!resp.ok || (json && json.status === false)) {
                        $('#otp_error_message').text((json && json.message) ? json.message : failedLogoutAll).show();
                        logoutAllBtn.disabled = false;
                        logoutAllBtn.textContent = logoutAllDevices;
                        return;
                    }

                    section.innerHTML = '';
                    const successAlert = document.createElement('div');
                    successAlert.className = 'alert alert-success';
                    successAlert.textContent = allDevicesLoggedOut;
                    section.appendChild(successAlert);

                } catch (err) {
                    $('#otp_error_message').text(systemError).show();
                    logoutAllBtn.disabled = false;
                    logoutAllBtn.textContent = logoutAllDevices;
                }
            });

            const list = document.createElement('div');
            list.className = 'list-group mb-2 logout-other-screen';
            if (!devices || devices.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'text-muted';
                empty.textContent = noDevicesFound;
                list.appendChild(empty);
            } else {
                devices.forEach(function (d) {
                    const row = document.createElement('div');
                    row.className = 'list-group-item d-flex align-items-center justify-content-between gap-3';
                    const left = document.createElement('div');
                    left.className = 'd-flex flex-column';
                    const name = document.createElement('span');
                    name.textContent = `${d.device_name || deviceLabel} (${d.platform || unknownLabel})`;
                    const sub = document.createElement('small');
                    sub.className = 'text-muted breck-anywhere';
                    sub.textContent = d.device_id || '';
                    left.appendChild(name);
                    left.appendChild(sub);
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-sm btn-outline-danger';
                    btn.textContent = logout;
                    btn.addEventListener('click', async function () {
                        btn.disabled = true;
                        const originalText = btn.textContent;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + loggingOut;
                        $('#otp_error_message').text('').hide();
                        try {
                            const uId = d.user_id;
                            const resp = await fetch(`${baseUrl}/api/device-logout-data?user_id=${encodeURIComponent(uId)}&device_id=${encodeURIComponent(d.device_id)}`, { method: 'GET' });
                            const json = await resp.json().catch(() => ({}));
                            if (!resp.ok || (json && json.status === false)) {
                                $('#otp_error_message').text((json && json.message) ? json.message : failedDeleteDevice).show();
                                btn.disabled = false;
                                btn.textContent = originalText;
                                return;
                            }
                            row.remove();

                            const remainingDevices = list.querySelectorAll('.list-group-item');
                            if (remainingDevices.length === 0) {
                                const alert = section.querySelector('.device-limit-alert');
                                if (alert) {
                                    alert.remove();
                                }
                                if (logoutAllBtnContainer && logoutAllBtnContainer.parentNode) {
                                    logoutAllBtnContainer.remove();
                                }
                                section.innerHTML = '';
                                const successAlert = document.createElement('div');
                                successAlert.className = 'alert alert-success';
                                successAlert.textContent = allDevicesLoggedOut;
                                section.appendChild(successAlert);
                                return;
                            }
                            const existingSuccessMsg = section.querySelector('.text-success, .alert-success');
                            if (existingSuccessMsg) {
                                existingSuccessMsg.remove();
                            }

                            const info = document.createElement('div');
                            info.className = 'text-success mb-2';
                            info.textContent = deviceRemovedInfo;
                            section.insertBefore(info, section.firstChild);
                        } catch (err) {
                            $('#otp_error_message').text(systemError).show();
                            btn.disabled = false;
                            btn.textContent = logout;
                        }
                    });
                    row.appendChild(left);
                    row.appendChild(btn);
                    list.appendChild(row);
                });
            }
            section.appendChild(alert);
            section.appendChild(logoutAllBtnContainer);
            section.appendChild(list);
        }


        function resendCode() {
            var number = iti.getNumber();
            
            if (!iti.isValidNumber()) {
                $('#otp_error_message').text(@json(__('messages.invalid_phone_number_format'))).show();
                return;
            }

            $('#otp_error_message').text("").hide();

            if (!window.recaptchaVerifier) {
                window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('recaptcha-container', {
                    size: 'invisible'
                });
            }

            window.recaptchaVerifier.render().then(function() {
                firebase.auth().signInWithPhoneNumber(number, window.recaptchaVerifier)
                    .then(function(confirmationResult) {
                        window.confirmationResult = confirmationResult;

                        startOtpTimer();
                    })
                    .catch(function(error) {
                        // Initialize error message variable
                        let errorMessage;

                        // Handle invalid phone number error
                        if (error.code === 'auth/invalid-phone-number') {
                            errorMessage = @json(__('messages.enter_valid_mobile_number'));
                        } else {
                            // Handle other errors, especially when error.message is a JSON string
                            try {
                                const errorData = JSON.parse(error.message);
                                if (errorData.error) {
                                    // Check for CONSUMER_SUSPENDED error
                                    if (errorData.error.status === 'PERMISSION_DENIED' || 
                                        (errorData.error.details && errorData.error.details.some(detail => 
                                            detail.reason === 'CONSUMER_SUSPENDED'))) {
                                        errorMessage = @json(__('messages.firebase_api_key_suspended'));
                                    }
                                    // Check for other permission denied errors
                                    else if (errorData.error.status === 'PERMISSION_DENIED') {
                                        errorMessage = @json(__('messages.firebase_permission_denied'));
                                    }
                                    // Check for BILLING_NOT_ENABLED error
                                    else if (errorData.error.errors && Array.isArray(errorData.error.errors)) {
                                        const billingError = errorData.error.errors.find(err => err.message === "BILLING_NOT_ENABLED");
                                        if (billingError) {
                                            errorMessage = @json(__('messages.demo_login_credentials'));
                                        }
                                        // Check for CONSUMER_SUSPENDED in errors array
                                        const suspendedError = errorData.error.errors.find(err => 
                                            err.message && err.message.includes('suspended') ||
                                            err.reason === 'CONSUMER_SUSPENDED'
                                        );
                                        if (suspendedError) {
                                            errorMessage = @json(__('messages.firebase_api_key_suspended'));
                                        }
                                    }
                                }
                            } catch (e) {
                                // JSON parsing failed, check if error message contains suspension keywords
                                if (error.message && (
                                    error.message.includes('suspended') || 
                                    error.message.includes('CONSUMER_SUSPENDED') ||
                                    error.message.includes('PERMISSION_DENIED')
                                )) {
                                    errorMessage = @json(__('messages.firebase_api_key_suspended'));
                                } else {
                                    console.error("Error parsing error.message:", e);
                                }
                            }

                            // Fallback to the original error message if no specific error is found
                            if (!errorMessage) {
                                errorMessage = error.message || @json(__('messages.lbl_error_occurred'));
                                // If error message is still a JSON string, show generic error
                                if (errorMessage && errorMessage.trim().startsWith('{')) {
                                    errorMessage = @json(__('messages.firebase_permission_denied'));
                                }
                            }
                        }

                        // Display the error message
                        $('#otp_error_message').text(errorMessage).show();
                    });
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            const registerFormWrapper = document.getElementById('registerForm');
            if (!registerFormWrapper) return;

            const form = registerFormWrapper.querySelector('form');
            const button = document.getElementById('register-button');
            if (!form || !button) return;

            form.addEventListener('submit', function (e) {
                let isValid = true;

                const firstName = form.querySelector('input[name="first_name"]');
                const lastName = form.querySelector('input[name="last_name"]');
                const email = form.querySelector('input[name="email"]');
                const mobile = form.querySelector('input[name="mobile"]');

                const fields = [
                    { input: firstName, errorId: 'first_name_error' },
                    { input: lastName, errorId: 'last_name_error' },
                    { input: email, errorId: 'email_error' },
                    { input: mobile, errorId: 'mobile-error' },
                ];

                fields.forEach(({ input, errorId }) => {
                    if (!input) return;
                    const errorEl = document.getElementById(errorId);
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('is-invalid');
                        if (errorEl) {
                            errorEl.style.display = 'block';
                        }
                    } else {
                        input.classList.remove('is-invalid');
                        if (errorEl) {
                            errorEl.style.display = 'none';
                        }
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    return;
                }

                button.disabled = true;

                const originalHtml = button.innerHTML;
                button.dataset.originalHtml = originalHtml;
                button.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    {{ __('messages.loading') }}
                `;
            });
        });
    </script>
@endsection
