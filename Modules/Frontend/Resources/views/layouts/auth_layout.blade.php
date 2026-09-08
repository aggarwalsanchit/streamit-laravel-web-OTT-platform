<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark"
    dir="{{ session()->has('dir') ? session()->get('dir') : 'ltr' }}"
    data-bs-theme-color="{{ getCustomizationSetting('theme_color') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>@yield('title') </title>

    <meta name="description" content="{{ $description ?? '' }}">
    <meta name="keywords" content="{{ $keywords ?? '' }}">
    <meta name="author" content="{{ $author ?? '' }}">
    <meta name="base-url" content="{{ url('/') }}">


    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100;1,300&amp;display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('modules/frontend/style.css') }}">

    <link rel="stylesheet" href="{{ asset('iconly/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/regular/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/fill/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/bold/style.css') }}">
    <link rel="icon" type="image/png" href="{{ GetSettingValue('favicon') ? setBaseUrlWithFileName(GetSettingValue('favicon'),'image','logos') :asset('img/logo/favicon.png') }}">
    <link rel="apple-touch-icon" sizes="76x76"
        href="{{ GetSettingValue('favicon') ? setBaseUrlWithFileName(GetSettingValue('favicon'),'image','logos') : asset('img/logo/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/customizer.css') }}">

    @include('frontend::components.partials.head.plugins')

</head>

<style>
    :root {
        <?php
        $rootColors = setting('root_colors'); // Assuming the setting() function retrieves the JSON string

        // Check if the JSON string is not empty and can be decoded
        if (!empty($rootColors) && is_string($rootColors)) {
            $colors = json_decode($rootColors, true);

            // Check if decoding was successful and the colors array is not empty
            if (json_last_error() === JSON_ERROR_NONE && is_array($colors) && count($colors) > 0) {
                foreach ($colors as $key => $value) {
                    echo $key . ': ' . $value . '; ';
                }
            } else {
                echo 'Invalid JSON or empty colors array.';
            }
        }
        ?>
    }
</style>

<body>

    @yield('content')

    @include('frontend::components.partials.scripts.plugins')

    <script src="{{ mix('modules/frontend/script.js') }}"></script>

    <script>
        const currencyFormat = (amount) => {
            const DEFAULT_CURRENCY = JSON.parse(@json(json_encode(Currency::getDefaultCurrency(true))))
            const noOfDecimal = DEFAULT_CURRENCY.no_of_decimal
            const decimalSeparator = DEFAULT_CURRENCY.decimal_separator
            const thousandSeparator = DEFAULT_CURRENCY.thousand_separator
            const currencyPosition = DEFAULT_CURRENCY.currency_position
            const currencySymbol = DEFAULT_CURRENCY.currency_symbol
            return formatCurrency(amount, noOfDecimal, decimalSeparator, thousandSeparator, currencyPosition,
                currencySymbol)
        }
        window.currencyFormat = currencyFormat
        window.defaultCurrencySymbol = @json(Currency::defaultSymbol())

        window.localisation = {
            sign_in: "{{ __('messages.sign_in') }}",
            signing_in: "{{ __('messages.signing_in') }}",
            sign_up: "{{ __('messages.sign_up') }}",
            signing_up: "{{ __('messages.signing_up') }}",
            email_required: "{{ __('messages.email_required') }}",
            email_invalid: "{{ __('messages.email_invalid') }}",
            password_required: "{{ __('messages.password_required') }}",
            password_requirements: "{{ __('messages.password_requirements') }}",
            first_name_required: "{{ __('messages.first_name_required') }}",
            last_name_required: "{{ __('messages.last_name_required') }}",
            register_error_generic: "{{ __('messages.register_error_generic') }}",
            login_after_register_failed: "{{ __('messages.login_after_register_failed') }}",
            system_error: "{{ __('messages.system_error') }}",
            passwords_do_not_match: "{{ __('messages.passwords_do_not_match') }}",
            passwords_confirm_mismatch: "{{ __('messages.passwords_confirm_mismatch') }}",
            field_required: "{{ __('messages.field_required') }}",
            name_no_numbers: "{{ __('messages.name_no_numbers') }}",
            login_failed: "{{ __('messages.login_failed') }}",
            device_limit_reached: "{{ __('messages.device_limit_reached') }}",
            device_limit_instruction: "{{ __('messages.device_limit_instruction') }}",
            logout_all_devices: "{{ __('messages.logout_all_devices') }}",
            logging_out_all_devices: "{{ __('messages.logging_out_all_devices') }}",
            unable_to_determine_user: "{{ __('messages.unable_to_determine_user') }}",
            failed_logout_all: "{{ __('messages.failed_logout_all') }}",
            all_devices_logged_out: "{{ __('messages.all_devices_logged_out') }}",
            no_devices_found: "{{ __('messages.no_devices_found') }}",
            logout: "{{ __('messages.logout') }}",
            logging_out: "{{ __('messages.logging_out') }}",
            failed_delete_device: "{{ __('messages.failed_delete_device') }}",
            device_removed_info: "{{ __('messages.device_removed_info') }}",
            delete: "{{ __('messages.delete') }}",
            sending: "{{ __('messages.sending') }}",
            submit: "{{ __('messages.submit') }}",
            password_reset_failed: "{{ __('messages.password_reset_failed') }}",
            login_success: "{{ __('messages.logged_in_success') }}",
            register_success: "{{ __('messages.register_success') }}",
            confirm_password_required: "{{ __('messages.confirm_password_required') }}",
            mobile_required: "{{ __('messages.mobile_required') }}",
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('form-submit');
            const submitButton = document.getElementById('submit-button');
            let formSubmitted = false;
            if (form) {
                const requiredFields = form.querySelectorAll('[required]');

                requiredFields.forEach(field => {
                    field.addEventListener('input', () => validateField(field));
                    field.addEventListener('change', () => validateField(field));
                });
                form.addEventListener('submit', function(event) {
                    if (formSubmitted) {
                        event.preventDefault();
                        return;
                    }

                    const isValid = validateForm();

                    if (!isValid) {
                        event.preventDefault();
                        submitButton.disabled = false;
                        formSubmitted = false; // Reset the flag
                        return;
                    }

                    submitButton.disabled = true;
                    submitButton.innerText = 'Save';
                    formSubmitted = true;
                });
            }

            function validateForm() {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });

                const emailInput = form.querySelector('input[type="email"]');
                if (emailInput && emailInput.required && emailInput.value.trim() && !isValidEmail(emailInput
                        .value)) {
                    isValid = false;
                    showValidationError(emailInput, 'Enter a valid Email Address.');
                }
                const mobileInput = form.querySelector('input[name="mobile"]');
                if (mobileInput && mobileInput.required && mobileInput.value.trim() && !validatePhoneNumber(
                        mobileInput.value)) {
                    isValid = false;
                    showValidationError(mobileInput, 'Enter a valid contact number.');
                }

                const helplineInput = form.querySelector('input[name="helpline_number"]');
                if (helplineInput && helplineInput.required && helplineInput.value.trim() && !validatePhoneNumber(
                        helplineInput.value)) {
                    isValid = false;
                    showValidationError(helplineInput, 'Enter a valid helpline number.');
                }

                const inquiryemailInput = form.querySelector('input[name="inquriy_email"]');
                if (inquiryemailInput && inquiryemailInput.required && inquiryemailInput.value.trim() && !
                    isValidEmail(inquiryemailInput.value)) {
                    isValid = false;
                    showValidationError(inquiryemailInput, 'Enter a valid Inquiry email address.');
                }
                return isValid;
            }

            function showValidationError(input, message) {
                const errorFeedback = input.nextElementSibling;
                if (errorFeedback && errorFeedback.classList.contains('invalid-feedback')) {
                    errorFeedback.textContent = message;
                    input.classList.add('is-invalid');
                }
            }

            function validateField(field) {
                const fieldId = field.id; // Use id for error message display
                const fieldError = document.getElementById(`${fieldId}-error`);
                let isValid = true;

                if (!field.value.trim()) {
                    if (fieldError) {
                        fieldError.style.display = 'block';
                    }
                    isValid = false;
                } else {
                    if (fieldError) {
                        fieldError.style.display = 'none';
                    }
                }

                return isValid;
            }

            function isValidEmail(email) {
                // Simple regex for email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            function validatePhoneNumber(phoneNumber) {
                const phonePattern = /^[\d\s\-,+]+$/; // Allows digits, spaces, hyphens, commas, and plus signs
                return phonePattern.test(phoneNumber);
            }

        });
    </script>
    <script>
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.requires-validation');
            Array.from(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    // Check if TinyMCE is defined before calling triggerSave
                    if (typeof tinymce !== 'undefined') {
                        tinymce.triggerSave();
                    }

                    // Check form validity
                    let isValid = form.checkValidity();

                    if (!isValid) {
                        event.preventDefault();
                        event.stopPropagation();
                    }

                    form.classList.add('was-validated');
                }, false);
            });

        })();
    </script>
    @stack('scripts')
</body>

</html>
