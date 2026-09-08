@extends('backend.layouts.app')
@section('title')
    {{ __($module_title) }}
@endsection
@section('content')
    <x-back-button-component route="backend.users.index" />

    {{ html()->form('POST', isset($data) ? route('backend.users.update', $data->id) : route('backend.users.store'))->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->class('requires-validation')->attribute('novalidate', 'novalidate')->attribute('enctype', 'multipart/form-data')->open() }}
    @csrf
    @if (isset($data))
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-6 col-lg-4 position-relative">
                    {{ html()->label(__('messages.image') . '<span class="text-danger"> *</span>', 'Image')->class('form-label') }}
                    <div class="input-group btn-file-upload">
                        {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerThumbnail')->attribute('data-hidden-input', 'file_url_image')->style('height:13.5rem') }}

                        {{ html()->text('thumbnail_input')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Thumbnail Image')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerThumbnail') }}
                    </div>
                    <div class="uploaded-image" id="selectedImageContainerThumbnail">
                        @if (old('file_url', isset($data) ? $data->file_url : ''))
                            <img src="{{ old('file_url', isset($data) ? setBaseUrlWithFileName($data->file_url, 'image', 'users') : '') }}"
                                class="img-fluid mb-2" style="max-width: 100px; max-height: 100px;">
                            <span class="remove-media-icon"
                                style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                                onclick="removeImage('file_url_image', 'remove_image_flag')">×</span>
                        @endif
                    </div>
                </div>
                {{ html()->hidden('file_url')->id('file_url_image')->value(old('file_url', isset($data) ? $data->file_url : '')) }}
                {{ html()->hidden('remove_image')->id('remove_image_flag')->value(0) }}
                <div class="col-md-6 col-lg-8">
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">{{ __('users.lbl_first_name') }}<span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                value="{{ old('first_name', $data->first_name ?? '') }}" name="first_name" id="first_name"
                                placeholder="{{ __('placeholder.lbl_user_first_name') }}" required>
                            <div class="help-block with-errors text-danger"></div>
                            @error('first_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="first_name-error">First Name field is required</div>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">{{ __('users.lbl_last_name') }}<span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                value="{{ old('last_name', $data->last_name ?? '') }}" name="last_name" id="last_name"
                                placeholder="{{ __('placeholder.lbl_user_last_name') }}" required>
                            @error('last_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="name-error">Last Name field is required</div>
                        </div>

                        {{-- Show email field only for create mode --}}
                        @if (!isset($data->id))
                            <div class="col-md-6">
                                <label for="email" class="form-label">{{ __('users.lbl_email') }}<span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control" value="{{ old('email', $data->email ?? '') }}"
                                    name="email" id="email" placeholder="{{ __('placeholder.lbl_user_email') }}"
                                    required>
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="email-error-required">Email field is required.</div>
                                <div class="invalid-feedback d-none " id="email-error-format">Invalid email format.</div>
                            </div>
                        @else
                            {{-- For edit mode, show gender field where email was --}}
                            <div class="col-md-6">
                                <label class="form-label">{{ __('users.lbl_gender') }}<span
                                        class="text-danger">*</span></label>
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <label
                                        class="form-check form-check-inline flex-grow-1 w-auto mx-0 form-control px-5 cursor-pointer">
                                        <div class="d-flex align-items-center">
                                            <input class="form-check-input me-2" type="radio" name="gender" id="male"
                                                value="male"
                                                {{ old('gender', isset($data) && !empty($data->gender) ? $data->gender : 'male') == 'male' ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ __('messages.lbl_male') }}</span>
                                        </div>
                                    </label>
                                    <label
                                        class="form-check form-check-inline flex-grow-1 w-auto mx-0 form-control px-5 cursor-pointer">
                                        <div class="d-flex align-items-center">
                                            <input class="form-check-input me-2" type="radio" name="gender" id="female"
                                                value="female"
                                                {{ old('gender', isset($data) && !empty($data->gender) ? $data->gender : 'male') == 'female' ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ __('messages.lbl_female') }}</span>
                                        </div>
                                    </label>
                                    <label
                                        class="form-check form-check-inline flex-grow-1 w-auto mx-0 form-control px-5 cursor-pointer">
                                        <div class="d-flex align-items-center">
                                            <input class="form-check-input me-2" type="radio" name="gender" id="other"
                                                value="other"
                                                {{ old('gender', isset($data) && !empty($data->gender) ? $data->gender : 'male') == 'other' ? 'checked' : '' }}>
                                            <span class="form-check-label">{{ __('messages.lbl_other') }}</span>
                                        </div>
                                    </label>
                                </div>

                                @error('gender')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="name-error">Gender field is required</div>
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label for="mobile" class="form-label d-block">{{ __('users.lbl_contact_number') }}<span
                                    class="text-danger">*</span></label>
                            <input type="tel" class="form-control" value="{{ old('mobile', $data->mobile ?? '') }}"
                                name="mobile" id="mobile" placeholder="{{ __('placeholder.lbl_user_conatct_number') }}"
                                required>
                            @error('mobile')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="mobile-error">Contact Number field is required</div>
                        </div>
                        <input type="hidden" name="country_code" id="country_code">


                        {{-- Show password fields only for create mode --}}
                        @if (!isset($data->id))
                            <div class="col-md-6">
                                <label for="password" class="form-label">{{ __('users.lbl_password') }}<span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control"
                                    value="{{ old('password', $data->password ?? '') }}" name="password" id="password"
                                    placeholder="{{ __('placeholder.lbl_user_password') }}"
                                     required>
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="password-error">{{ __('messages.password_field_required') }}</div>

                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation"
                                    class="form-label">{{ __('users.lbl_confirm_password') }}<span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control"
                                    value="{{ old('password_confirmation', $data->password_confirmation ?? '') }}"
                                    name="password_confirmation" id="password_confirmation"
                                    placeholder="{{ __('placeholder.lbl_user_confirm_password') }}" required>
                                @error('password_confirmation')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="password_confirmation-error">{{ __('messages.confirm_password_field_required') }}</div>
                            </div>
                        @else
                            {{-- For edit mode, show date of birth field where password was --}}
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">{{ __('users.lbl_date_of_birth') }} <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control datetimepicker"
                                    value="{{ old('date_of_birth', isset($data) ? $data->date_of_birth : '') }}"
                                    name="date_of_birth" id="date_of_birth" max="{{ date('Y-m-d') }}"
                                    placeholder="{{ __('placeholder.lbl_user_date_of_birth') }}" required>
                                @error('date_of_birth')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="date_of_birth-error">Date Of Birth field is required
                                </div>
                            </div>
                            {{-- For edit mode, show status field where confirm password was --}}
                            <div class="col-md-6">
                                <label for="status" class="form-label"> {{ __('users.lbl_status') }}</label>
                                <div class="d-flex align-items-center justify-content-between form-control">
                                    <label for="status" class="form-label mb-0 text-body">
                                        {{ __('messages.active') }}</label>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="status" value="0"> <!-- Hidden input field -->
                                        <input class="form-check-input" type="checkbox" id="status" name="status"
                                            value="1" {{ old('status', $data->status ?? 1) == 1 ? 'checked' : '' }}>
                                    </div>
                                </div>
                                @error('status')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Show gender field for create mode only in sidebar --}}
                @if (!isset($data->id))
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">{{ __('users.lbl_gender') }}</label><span class="text-danger">*</span>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <label
                                class="form-check form-check-inline flex-grow-1 w-auto mx-0 form-control px-5 cursor-pointer">
                                <div class="d-flex align-items-center">
                                    <input class="form-check-input me-2" type="radio" name="gender" id="male"
                                        value="male"
                                        {{ old('gender', isset($data) && !empty($data->gender) ? $data->gender : 'male') == 'male' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ __('messages.lbl_male') }}</span>
                                </div>
                            </label>
                            <label
                                class="form-check form-check-inline flex-grow-1 w-auto mx-0 form-control px-5 cursor-pointer">
                                <div class="d-flex align-items-center">
                                    <input class="form-check-input me-2" type="radio" name="gender" id="female"
                                        value="female"
                                        {{ old('gender', isset($data) && !empty($data->gender) ? $data->gender : 'male') == 'female' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ __('messages.lbl_female') }}</span>
                                </div>
                            </label>
                            <label
                                class="form-check form-check-inline flex-grow-1 w-auto mx-0 form-control px-5 cursor-pointer">
                                <div class="d-flex align-items-center">
                                    <input class="form-check-input me-2" type="radio" name="gender" id="other"
                                        value="other"
                                        {{ old('gender', isset($data) && !empty($data->gender) ? $data->gender : 'male') == 'other' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ __('messages.lbl_other') }}</span>
                                </div>
                            </label>
                        </div>

                        @error('gender')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">Gender field is required</div>
                    </div>
                @endif

                {{-- Show date of birth field for create mode only in sidebar --}}
                @if (!isset($data->id))
                    <div class="col-md-6 col-lg-4">
                        <label for="date_of_birth" class="form-label">{{ __('users.lbl_date_of_birth') }} <span
                                class="text-danger">*</span></label>
                        <input type="date" class="form-control datetimepicker"
                            value="{{ old('date_of_birth', isset($data) ? $data->date_of_birth : '') }}"
                            name="date_of_birth" id="date_of_birth" max="{{ date('Y-m-d') }}"
                            placeholder="{{ __('placeholder.lbl_user_date_of_birth') }}" required>
                        @error('date_of_birth')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="date_of_birth-error">Date Of Birth field is required</div>
                    </div>
                @endif

                {{-- Show status field for create mode only in sidebar --}}
                @if (!isset($data->id))
                    <div class="col-md-6 col-lg-4">
                        <label for="status" class="form-label"> {{ __('users.lbl_status') }}</label>
                        <div class="d-flex align-items-center justify-content-between form-control">
                            <label for="status" class="form-label mb-0 text-body"> {{ __('messages.active') }}</label>
                            <div class="form-check form-switch">
                                <input type="hidden" name="status" value="0"> <!-- Hidden input field -->
                                <input class="form-check-input" type="checkbox" id="status" name="status"
                                    value="1" {{ old('status', $data->status ?? 1) == 1 ? 'checked' : '' }}>
                            </div>
                        </div>
                        @error('status')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
                <div class="col-md-12">
                    <label for="address" class="form-label">{{ __('users.lbl_address') }}</label>
                    <textarea class="form-control" name="address" id="address" rows="6"
                        placeholder="{{ __('placeholder.lbl_user_address') }}">{{ old('address', $data->address ?? '') }}</textarea>
                    @error('address')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mb-5">

        <button type="submit" class="btn btn-primary" id="submit-button">{{ __('messages.save') }}</button>
    </div>
    </form>

    @include('components.media-modal')
@endsection
@push('after-scripts')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr('.datetimepicker', {
                dateFormat: "Y-m-d",
                maxDate: 'today',
            });
        });

        var input = document.querySelector("#mobile");
        var iti = window.intlTelInput(input, {
            initialCountry: "in", // Automatically detect user's country
            separateDialCode: true, // Show the country code separately
            customContainer: "w-100",
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js" // To handle number formatting
        });

        function updateCountryCode() {
            if (iti && iti.getSelectedCountryData) {
                let dial = iti.getSelectedCountryData().dialCode;
                document.getElementById('country_code').value = "+" + dial;
            }
        }

        input.addEventListener("countrychange", updateCountryCode);
        input.addEventListener("input", updateCountryCode);

        // Set the country and number for edit mode
        @if (isset($data) && $data->mobile)
            var storedMobile = "{{ $data->mobile }}";

            if (storedMobile) {
                // Try to set the country based on the stored number
                try {
                    // Remove any existing spaces for proper parsing
                    var cleanNumber = storedMobile.replace(/\s+/g, '');
                    iti.setNumber(cleanNumber);
                } catch (e) {
                    // If setting number fails, just set the number as is
                    input.value = storedMobile;
                }
            }
        @endif

        // Add real-time validation and formatting
        input.addEventListener('input', function() {
            if (iti.isValidNumber()) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
                // Show formatted number as placeholder or tooltip with space
                var formattedNumber = iti.getNumber();
                var countryData = iti.getSelectedCountryData();
                var dialCode = countryData.dialCode;
                var numberWithoutCode = formattedNumber.replace('+' + dialCode, '');
                var formattedWithSpace = '+' + dialCode + ' ' + numberWithoutCode;
                input.title = 'Valid number: ' + formattedWithSpace;
            } else {
                input.classList.remove('is-valid');
                if (input.value.length > 0) {
                    input.classList.add('is-invalid');
                }
            }
        });

        // Update when country changes
        input.addEventListener('countrychange', function() {
            if (input.value.length > 0) {
                input.dispatchEvent(new Event('input'));
            }
        });


        function removeImage(hiddenInputId, removedFlagId) {
            var container = document.getElementById('selectedImageContainerThumbnail');

            var hiddenInput = document.getElementById(hiddenInputId);
            var removedFlag = document.getElementById(removedFlagId);

            container.innerHTML = '';
            hiddenInput.value = '';
            removedFlag.value = 1;
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileInput = document.getElementById('mobile');
            const form = document.getElementById('form-submit');

            mobileInput.addEventListener('input', function() {
                // Remove any non-numeric characters
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            // Handle form submission to store full phone number with country code
            form.addEventListener('submit', function(e) {
                if (iti.isValidNumber()) {
                    var fullNumber = iti.getNumber(); // This includes the country code with +
                    // Add space after the country code
                    var countryData = iti.getSelectedCountryData();
                    var dialCode = countryData.dialCode;
                    var numberWithoutCode = fullNumber.replace('+' + dialCode, '');
                    mobileInput.value = '+' + dialCode + numberWithoutCode;
                } else {
                    // If not valid, still try to get the number as entered
                    var enteredNumber = mobileInput.value;
                    if (enteredNumber && !enteredNumber.startsWith('+')) {
                        // Add country code if not present
                        var countryData = iti.getSelectedCountryData();
                        var dialCode = countryData.dialCode;
                        mobileInput.value = '+' + dialCode + enteredNumber;
                    }
                }
            });

            function showError(message) {
                const errorDiv = document.getElementById('mobile-error'); // Use correct ID
                errorDiv.style.display = 'block';
                errorDiv.textContent = message;
                mobileInput.classList.add('is-invalid');
            }

            function clearError() {
                const errorDiv = document.getElementById('mobile-error'); // Use correct ID
                errorDiv.style.display = 'none';
                errorDiv.textContent = '';
                mobileInput.classList.remove('is-invalid');
            }

        });

        // Password validation - must run before global form handler
        const passwordInput = document.getElementById('password');
        const passwordError = document.getElementById('password-error');

        if (passwordInput) {
            const passwordRegex =
                /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_+\-=\[\]{};':"\\|,.<>\/])[A-Za-z\d@$!%*?&#^()_+\-=\[\]{};':"\\|,.<>\/]{8,14}$/;

            function getPasswordErrorMessage(password) {
                if (!password || password.length === 0) return '{{ __('messages.password_field_required') }}';
                if (password.length < 8) return '{{ __('messages.password_min') }}';
                if (password.length > 14) return '{{ __('messages.password_max') }}';
                if (!passwordRegex.test(password)) return '{{ __('messages.password_requirements') }}';
                return '';
            }

            // Make password participate in normal HTML5 validation so all field errors show together.
            passwordInput.addEventListener('input', function() {
                const msg = getPasswordErrorMessage(passwordInput.value);
                passwordInput.setCustomValidity(msg);
                if (passwordError) passwordError.textContent = msg || '{{ __('messages.password_field_required') }}';
            });

            // Initialize custom validity once
            const initMsg = getPasswordErrorMessage(passwordInput.value);
            passwordInput.setCustomValidity(initMsg);
        }
    </script>
@endpush
