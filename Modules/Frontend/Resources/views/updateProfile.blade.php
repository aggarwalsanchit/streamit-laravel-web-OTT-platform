@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.update_profile') }}
@endsection

@section('content')
    <div class="section-spacing">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-md-4">
                    @include('frontend::components.account-settings-sidebar')
                </div>
                <div class="col-lg-9 mt-lg-0 mt-5">
                    <div class="card bg-gray-900 border user-login-card p-5">
                        <div class="edit-profile-content">
                            <div class="edit-profile-details">
                                <!-- <div class="bg-body rounded p-5"> -->
                                    <h6 class="mb-3">{{ __('frontend.profiles_details') }}</h6>
                                    <div class="row">
                                        <div class="col-md-3 text-center">
                                            <div class="select-profile-card position-relative d-inline-block">
                                                <img id="profileImage"
                                                    src="{{ setBaseUrlWithFileName($user->file_url, 'image', 'users') ?? setDefaultImage() }}"
                                                    class="img-fluid rounded-circle object-cover" alt="select-profile-image"
                                                    style="cursor: pointer; width: 150px; height: 150px;">
                                                <input type="file" id="profileImageInput" class="d-none" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                                    onchange="previewImage(event)">
                                                <i class="ph ph-pencil pencil-icon" onclick="triggerFileInput()"></i>
                                            </div>
                                        </div>
                                        <div class="col-md-9 mt-md-0 mt-4">
                                            <form id="editProfileDetail">
                                                @csrf
                                                <div class="mb-3">
                                                    <div class="input-group mb-0">
                                                        <span class="input-style-text input-group-text"><i
                                                                class="ph ph-user"></i></span>
                                                        <input type="text" name="first_name"
                                                            class="form-control input-style-box" value="{{ $user->first_name }}"
                                                            placeholder="{{ __('frontend.enter_fname') }}">
                                                    </div>
                                                    <div class="invalid-feedback" id="first_name_error"></div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="input-group mb-0">
                                                        <span class="input-group-text input-style-text"><i
                                                                class="ph ph-user"></i></span>
                                                        <input type="text" name="last_name"
                                                            class="form-control input-style-box" value="{{ $user->last_name }}"
                                                            placeholder="{{ __('frontend.enter_lname') }}">
                                                    </div>
                                                    <div class="invalid-feedback" id="last_name_error">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="input-group mb-0">
                                                        <span class="input-group-text input-style-text"><i
                                                                class="ph ph-envelope"></i></span>
                                                        <input type="email" name="email"
                                                            class="form-control input-style-box" value="{{ $user->email }}"
                                                            placeholder="{{ __('placeholder.lbl_user_email') }}",
                                                            @if ($user->login == 'google') readonly @endif>
                                                    </div>
                                                    <div class="invalid-feedback" id="email_error">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="input-group mb-0 contact-number-intl-tel-input">
                                                        <span class="input-group-text input-style-text"><i
                                                                class="ph ph-phone"></i></span>
                                                        <input type="tel" class="form-control input-style-box" name="mobile"
                                                            value="{{ $user->mobile }}" id="mobileInput"
                                                            placeholder="{{ __('placeholder.lbl_user_conatct_number') }}",
                                                            @if ($user->login == 'otp') readonly @endif>
                                                        <input type="hidden" name="country_code" id="country_code"
                                                            value="{{ $user->country_code }}">
                                                    </div>
                                                    <div class="invalid-feedback" id="mobile_error">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="input-group mb-0">
                                                        <span class="input-group-text input-style-text"><i class="ph ph-map-pin"></i></span>
                                                        <textarea class="form-control input-style-box" name="address" id="address" rows="3" placeholder="{{ __('placeholder.lbl_user_address') }}">{{ old('address', $user->address ?? '') }}</textarea>
                                                    </div>
                                                    <div class="invalid-feedback" id="address_error"></div>
                                                </div>
                                                <div class="input-group mb-3 flex-nowrap">
                                                    <span class="input-group-text input-style-text"><i
                                                            class="ph ph-gender-neuter"></i></span>
                                                    <div
                                                        class="d-flex flex-wrap align-items-center input-style-box w-100 gap-2 px-2">
                                                        <label class="form-check form-check-inline cursor-pointer">
                                                            <input class="form-check-input me-2" type="radio"
                                                                name="gender" id="male" value="male"
                                                                {{ old('gender', isset($user) ? $user->gender : 'male') == 'male' ? 'checked' : 'checked' }}>
                                                            <span
                                                                class="form-check-label">{{ __('messages.lbl_male') }}</span>
                                                        </label>
                                                        <label class="form-check form-check-inline cursor-pointer">
                                                            <input class="form-check-input me-2" type="radio"
                                                                name="gender" id="female" value="female"
                                                                {{ old('gender', isset($user) ? $user->gender : 'male') == 'female' ? 'checked' : '' }}>
                                                            <span
                                                                class="form-check-label">{{ __('messages.lbl_female') }}</span>
                                                        </label>
                                                        <label class="form-check form-check-inline cursor-pointer">
                                                            <input class="form-check-input me-2" type="radio"
                                                                name="gender" id="other" value="other"
                                                                {{ old('gender', isset($user) ? $user->gender : 'male') == 'other' ? 'checked' : '' }}>
                                                            <span
                                                                class="form-check-label">{{ __('messages.lbl_other') }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="invalid-feedback d-block" id="gender_error" style="display:none"></div>

                                                <div class="mb-3">
                                                    <div class="input-group mb-0">
                                                        <span class="input-group-text input-style-text"><i
                                                                class="ph ph-calendar"></i></span>
                                                        <input type="text" name="date_of_birth" id="date_of_birth_input"
                                                            class="form-control input-style-box datetimepicker"
                                                            value="{{ old('date_of_birth', isset($user) && $user->date_of_birth ? \Carbon\Carbon::parse($user->date_of_birth)->format('Y-m-d') : '') }}"
                                                            placeholder="{{ __('placeholder.lbl_user_date_of_birth') }}"
                                                            required>

                                                    </div>
                                                    <div class="invalid-feedback" id="date_of_birth_error">
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <button type="button" id="updateProfileBtn"
                                                        class="btn btn-primary mt-5">{{ __('frontend.update') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <!-- </div> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="{{ asset('vendor/intl-tel-input/css/intlTelInput.css') }}">
    <script src="{{ asset('vendor/intl-tel-input/js/intlTelInput.min.js') }}"></script>
    <script src="{{ asset('vendor/flatpickr/flatpicker.min.js') }}"></script>
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
       
        var profileDobFlatpickr = null;
        document.addEventListener('DOMContentLoaded', function() {
            var dobEl = document.getElementById('date_of_birth_input');
            if (dobEl && typeof flatpickr !== 'undefined') {
                profileDobFlatpickr = flatpickr(dobEl, {
                    dateFormat: 'Y-m-d',
                    maxDate: 'today'
                });
            }
        });

        function triggerFileInput() {
            document.getElementById('profileImageInput').click();
        }

        function previewImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                window.errorSnackbar('{{ __("messages.lbl_please_select_valid_image") }}');
                event.target.value = '';
                return;
            }
            
            const maxSize = 5 * 1024 * 1024; 
            if (file.size > maxSize) {
                window.errorSnackbar('{{ __("messages.lbl_image_size_too_large") }}');
                event.target.value = '';
                return;
            }
            
            const image = document.getElementById('profileImage');
            image.src = URL.createObjectURL(file);
        }
        document.getElementById('profileImage').addEventListener('click', triggerFileInput);

        var input = document.querySelector('#mobileInput');
        var iti = null;
        if (window.intlTelInput && input) {
            iti = window.intlTelInput(input, {
                initialCountry: 'in',
                separateDialCode: true,
                customContainer: "w-100",
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
            });
            try {
                var initialVal = input.value ? String(input.value).trim() : '';
                if (initialVal) {
                    iti.setNumber(initialVal);
                }
            } catch (e) {
                console.error(e);
            }
        }

        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');

        $(document).ready(function() {
            const i18n = {
                mobile_required: "{{ __('frontend.mobile_required') }}",
                first_name_required: "{{ __('validation.required', ['attribute' => 'first name']) }}",
                last_name_required: "{{ __('validation.required', ['attribute' => 'last name']) }}",
                email_required: "{{ __('validation.required', ['attribute' => 'email']) }}",
                date_of_birth_required: "{{ __('validation.required', ['attribute' => __('castcrew.lbl_dob')]) }}",
                address_required: "{{ __('validation.required', ['attribute' => __('users.lbl_address')]) }}",
                gender_required: "{{ __('validation.required', ['attribute' => __('messages.lbl_gender')]) }}",
                updating: "{{ __('frontend.updating') }}",
                update: "{{ __('frontend.update') }}",
                error_updating_profile: "{{ __('frontend.error_updating_profile') }}",
            };
            $('#updateProfileBtn').on('click', function(e) {
                console.log('updateProfileBtn clicked');
                e.preventDefault();

                $('.invalid-feedback').hide();
                $('#gender_error').hide();
                $('input, textarea, select').removeClass('is-invalid');

                let valid = true;
                const textFields = [
                    { name: 'first_name', error: '#first_name_error', msg: i18n.first_name_required },
                    { name: 'last_name', error: '#last_name_error', msg: i18n.last_name_required },
                    { name: 'email', error: '#email_error', msg: i18n.email_required },
                    { name: 'date_of_birth', error: '#date_of_birth_error', msg: i18n.date_of_birth_required }
                ];

                textFields.forEach(f => {
                    const el = $(`input[name="${f.name}"]`);
                    const value = (el.val() || '').trim();
                    if (!value) {
                        $(f.error).show().text(f.msg);
                        el.addClass('is-invalid');
                        valid = false;
                    }
                });

                // Address textarea
                const addressEl = $('textarea[name="address"]');
                const addressVal = (addressEl.val() || '').trim();
                if (!addressVal) {
                    $('#address_error').show().text(i18n.address_required);
                    addressEl.addClass('is-invalid');
                    valid = false;
                }

                // Gender required
                if ($('input[name="gender"]:checked').length === 0) {
                    $('#gender_error').show().text(i18n.gender_required);
                    valid = false;
                }

                const mobileInput = $('#mobileInput');
                const mobileValue = mobileInput.val().trim();
                if (!mobileValue) {
                    $('#mobileInput').addClass('is-invalid');
                    $('#mobile_error').show().text(i18n.mobile_required);
                    valid = false;
                } else {
                    $('#mobile_error').hide();
                }

                if (!valid) return;

                var number = '';
                try {
                    var raw = (mobileValue || '').trim();
                    var intl = (iti && typeof iti.getNumber === 'function') ? (iti.getNumber() || '') : '';
                    if (intl) {
                        number = intl;
                    } else if (iti && raw) {
                        var cd = iti.getSelectedCountryData && iti.getSelectedCountryData();
                        var dial = cd && cd.dialCode ? '+' + cd.dialCode : '';
                        number = (dial ? (dial + raw) : raw);
                    } else {
                        number = raw;
                    }
                } catch (e) {
                    number = (mobileValue || '').trim();
                }
                var countryCode = '';
                if (iti && iti.getSelectedCountryData) {
                    var cd = iti.getSelectedCountryData();
                    if (cd && cd.dialCode) {
                        countryCode = '+' + cd.dialCode;
                    }
                }

                var formData = new FormData($('#editProfileDetail')[0]);
                
                
                if (number) {
                    formData.set('mobile', number);
                }
                if (countryCode) {
                    formData.set('country_code', countryCode);
                }
                var imageFile = $('#profileImageInput')[0].files[0];
                if (imageFile) {
                    formData.append('file_url', imageFile);
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text(i18n.updating);
                $.ajax({
                    url: `${baseUrl}/api/update-profile`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'Authorization': 'Bearer ' + '{{ auth()->user()->api_token }}'
                    },
                    success: function(response) {
                        if (response.status === true) {
                            $('input[name="first_name"]').val(response.data.first_name);
                            $('input[name="last_name"]').val(response.data.last_name);
                            $('input[name="email"]').val(response.data.email);
                            if (iti && iti.getSelectedCountryData) {
                                var cd = iti.getSelectedCountryData();
                                var dial = cd && cd.dialCode ? '+' + cd.dialCode : '';
                                $('input[name="mobile"]').val(
                                    dial ? response.data.mobile.replace(dial, '') : response.data.mobile
                                );
                            } else {
                                $('input[name="mobile"]').val(response.data.mobile);
                            }
                            if (response.data.country_code !== undefined) {
                                $('input[name="country_code"]').val(response.data.country_code);
                            }
                            if (response.data.address !== undefined) {
                                $('textarea[name="address"]').val(response.data.address || '');
                            }
                            var dobRaw = response.data.date_of_birth;
                            var dobStr = '';
                            if (dobRaw) {
                                if (typeof dobRaw === 'string' && /^\d{4}-\d{2}-\d{2}/.test(dobRaw)) {
                                    dobStr = dobRaw.substring(0, 10);
                                } else if (typeof dobRaw === 'string') {
                                    var d = new Date(dobRaw);
                                    if (!isNaN(d.getTime())) {
                                        dobStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                                    }
                                }
                            }
                            var dobInput = document.getElementById('date_of_birth_input') || document.querySelector('input[name="date_of_birth"]');
                            if (dobStr && dobInput) {
                                dobInput.value = dobStr;
                                if (typeof profileDobFlatpickr !== 'undefined' && profileDobFlatpickr) {
                                    profileDobFlatpickr.setDate(dobStr, false);
                                } else if (dobInput._flatpickr) {
                                    dobInput._flatpickr.setDate(dobStr, false);
                                }
                                setTimeout(function() {
                                    if (dobInput && dobInput.value !== dobStr) {
                                        dobInput.value = dobStr;
                                        if (profileDobFlatpickr) profileDobFlatpickr.setDate(dobStr, false);
                                    }
                                }, 0);
                            }
                            $('input[name="gender"][value="' + response.data.gender + '"]')
                                .prop('checked', true);
                            window.successSnackbar(response.message)
                            $btn.prop('disabled', false).text(i18n.update);
                        } else {
                            // Fix: Use errorSnackbar for errors
                            if (window.errorSnackbar) {
                                window.errorSnackbar(i18n.error_updating_profile);
                            } else if (window.successSnackbar) {
                                window.successSnackbar(i18n.error_updating_profile);
                            }
                            $btn.prop('disabled', false).text(i18n.update);
                        }
                    },
                    error: function(xhr) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                        } catch (_) {
                            response = {};
                        }
                        if (response && response.errors) {
                            Object.keys(response.errors).forEach(function(key){
                                var msg = Array.isArray(response.errors[key]) ? response.errors[key][0] : response.errors[key];
                                var $input = $('[name="' + key + '"]');
                                var $error = $('#' + key + '_error');
                                if ($error.length) {
                                    $error.show().text(msg);
                                }
                                if ($input.length) {
                                    $input.addClass('is-invalid');
                                } else if (key === 'gender') {
                                    $('#gender_error').show().text(msg);
                                }
                            });
                        } else if (response.message) {
                            // Fix: Use errorSnackbar for errors
                            if (window.errorSnackbar) {
                                window.errorSnackbar(response.message);
                            } else if (window.successSnackbar) {
                                window.successSnackbar(response.message);
                            }
                        }
                        $btn.prop('disabled', false).text(i18n.update);
                    }
                });
            });
        });
    </script>
@endsection
