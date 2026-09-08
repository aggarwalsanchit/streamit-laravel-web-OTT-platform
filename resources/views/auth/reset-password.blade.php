@extends('frontend::layouts.auth_layout')

@section('title')
    {{ __('messages.lbl_reset_password') }}
@endsection

@section('content')
    <div class="vh-100"
        style="background: url('{{ asset('/dummy-images/login_banner.jpg') }}'); background-size: cover; background-repeat: no-repeat; position: relative; min-height: 500px; overflow-y:auto;">
        <div class="container">
            <div class="row justify-content-center align-items-center height-self-center vh-100">
                <div class="col-lg-5 col-md-8 col-sm-11 col-12 align-self-center">
                    <div class="user-login-card card my-5">
                        <div class="auth-heading text-center">
                            @php
                                $logo = GetSettingValue('dark_logo') ? setBaseUrlWithFileName(GetSettingValue('dark_logo'), 'image', 'logos') : asset('img/logo/dark_logo.png');
                            @endphp
                            <a href="{{ route('user.login') }}" class="d-inline-block">
                                <img src="{{ $logo }}" class="img-fluid logo h-4 mb-4" alt="{{ app_name() }}">
                            </a>
                            <h5 class="mb-2">{{ __('messages.reset_password_title') }}</h5>
                            <p class="text-muted">{{ __('messages.reset_password_description') }}</p>
                        </div>

                        <x-auth-validation-errors class="mb-3" :errors="$errors" />

                        <form method="POST" action="{{ route('password.update') }}" novalidate id="reset-password-form">
                            @csrf
                            <input type="hidden" name="token" value="{{ $request->route('token') }}">

                            <div class="mb-3">
                                <div class="input-group mb-0">
                                    <span class="input-group-text"><i class="ph ph-envelope"></i></span>
                                    <input id="email" type="email" name="email" class="form-control"
                                        value="{{ old('email', $request->email) }}"
                                        placeholder="{{ __('messages.enter_email') }}" required autofocus>
                                </div>
                                <div class="text-danger" id="email-error"></div>
                            </div>

                            <div class="mb-3">
                                <div class="input-group mb-0">
                                    <span class="input-group-text"><i class="ph ph-lock-key"></i></span>
                                    <input id="password" type="password" name="password" class="form-control pe-5"
                                        placeholder="{{ __('messages.placeholder_enter_new_password') }}" required>
                                    <span class="input-group-text" id="password-eye-icon" role="button">
                                        <i class="ph ph-eye-slash"></i>
                                    </span>
                                </div>
                                <div class="text-danger" id="password-error"></div>
                            </div>

                            <div class="mb-3">
                                <div class="input-group mb-0">
                                    <span class="input-group-text"><i class="ph ph-lock-key"></i></span>
                                    <input id="password_confirmation" type="password" name="password_confirmation"
                                        class="form-control pe-5"
                                        placeholder="{{ __('messages.placeholder_enter_new_confirm_password') }}" required>
                                    <span class="input-group-text" id="password_confirmation-eye-icon" role="button">
                                        <i class="ph ph-eye-slash"></i>
                                    </span>
                                </div>
                                <div class="text-danger" id="password_confirmation-error"></div>
                            </div>

                            <div class="full-button text-center mt-4">
                                <button class="btn btn-primary w-100" id="reset-password-btn" type="submit">
                                    <span id="reset-password-text">{{ __('messages.lbl_reset_password') }}</span>
                                    <span id="reset-password-spinner"
                                        class="spinner-border spinner-border-sm d-none ms-2"
                                        role="status" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<script>
    function bindToggleVisibility(inputId, toggleId) {
        const input = document.getElementById(inputId);
        const toggle = document.getElementById(toggleId);

        if (!input || !toggle) return;

        toggle.addEventListener('click', function() {
            const icon = this.querySelector('i');
            const toText = input.type === 'password';
            input.type = toText ? 'text' : 'password';
            if (icon) {
                icon.classList.toggle('ph-eye', toText);
                icon.classList.toggle('ph-eye-slash', !toText);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        bindToggleVisibility('password', 'password-eye-icon');
        bindToggleVisibility('password_confirmation', 'password_confirmation-eye-icon');

        const form = document.getElementById('reset-password-form');
        const emailInput = document.querySelector('#email');
        const passwordInput = document.querySelector('#password');
        const confirmPasswordInput = document.querySelector('#password_confirmation');

        // Prevent copy-paste on password fields
        [passwordInput, confirmPasswordInput].forEach(input => {
            if (input) {
                ['copy', 'paste', 'cut'].forEach(event => {
                    input.addEventListener(event, e => e.preventDefault());
                });
            }
        });

        function validatePassword(password) {
            const hasLength = password.length >= 8 && password.length <= 14;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            const hasDigit = /\d/.test(password);

            return hasLength && hasUppercase && hasLowercase && hasSpecial && hasDigit;
        }


        const messages = {
            email_required: '{{ __('messages.email_required') }}',
            email_invalid: '{{ __('messages.email_invalid') }}',
            password_required: '{{ __('messages.password_required') }}',
            password_validation: '{{ __('messages.password_validation') }}',
            confirm_password_required: '{{ __('messages.confirm_password_required') }}',
            passwords_not_match: '{{ __('messages.passwords_not_match') }}',
            password_same_as_current: '{{ __('messages.password_same_as_current') }}'
        };

        passwordInput.addEventListener('input', function() {
            clearError('password');

            if (this.value && !validatePassword(this.value)) {
                showError('password', messages.password_validation);
            }

            if (confirmPasswordInput.value.trim()) {
                validatePasswordMatch();
            }
        });

        confirmPasswordInput.addEventListener('input', function() {
            clearError('password_confirmation');
            validatePasswordMatch();
        });

        function validatePasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (confirmPassword.trim()) {
                if (password !== confirmPassword) {
                    showError('password_confirmation', messages.passwords_not_match);
                } else {
                    clearError('password_confirmation');
                }
            } else {
                clearError('password_confirmation');
            }
        }

        emailInput.addEventListener('input', function() {
            clearError('email');
            if (this.value.trim() && !isValidEmail(this.value)) {
                showError('email', messages.email_invalid);
            }
        });

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        form.addEventListener('submit', function(e) {
            clearError('email');
            clearError('password');
            clearError('password_confirmation');

            let isValid = true;

            if (!emailInput.value.trim()) {
                showError('email', messages.email_required);
                isValid = false;
            } else if (!isValidEmail(emailInput.value)) {
                showError('email', messages.email_invalid);
                isValid = false;
            }

            if (!passwordInput.value.trim()) {
                showError('password', messages.password_required);
                isValid = false;
            } else if (!validatePassword(passwordInput.value)) {
                showError('password', messages.password_validation);
                isValid = false;
            }

            if (!confirmPasswordInput.value.trim()) {
                showError('password_confirmation', messages.confirm_password_required);
                isValid = false;
            } else if (passwordInput.value.trim() !== confirmPasswordInput.value.trim()) {
                showError('password_confirmation', messages.passwords_not_match);
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            } else {
                const resetBtn = document.getElementById('reset-password-btn');
                const resetText = document.getElementById('reset-password-text');
                const resetSpinner = document.getElementById('reset-password-spinner');

                if (resetBtn && resetText && resetSpinner) {
                    resetBtn.disabled = true;
                    resetText.textContent = '{{ __('messages.resetting') }}';
                    resetSpinner.classList.remove('d-none');
                }
            }
        });

        function showError(fieldName, message) {
            const errorElement = document.getElementById(fieldName + '-error');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }
        }

        function clearError(fieldName) {
            const errorElement = document.getElementById(fieldName + '-error');
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }
        }
        @if (session('status'))
            window.successSnackbar('{{ session('message') }}');
            setTimeout(function() {
                window.location.href = '{{ route('login') }}';
            }, 1000);
        @endif
    });
</script>
