@extends('setting::backend.profile.profile-layout')

@section('profile-content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fa-solid fa-user"></i> {{ __('profile.info') }}</h2>
        </div>


        {{ html()->form('POST', route('backend.profile.information-update'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->class('requires-validation')->attribute('novalidate', 'novalidate')->open() }}
        @csrf
        <div class="row">
            <div class="col-12">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="form-label" for="first_name">{{ __('profile.lbl_first_name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                    id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}"
                                    placeholder="{{ __('placeholder.lbl_user_first_name') }}" required>
                                @error('first_name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="first_name-error">First Name field is required</div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="last_name">{{ __('profile.lbl_last_name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                    id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}"
                                    placeholder="{{ __('placeholder.lbl_user_last_name') }}" required>
                                @error('last_name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="last_name-error">Last Name field is required</div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label" for="email">{{ __('profile.lbl_email') }} <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control " id="email" name="email"
                                    value="{{ old('email', $user->email) }}"
                                    placeholder="{{ __('placeholder.lbl_user_email') }}" required>
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="email-error">Email field is required</div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="form-label d-block" for="mobile">{{ __('profile.lbl_contact_number') }} <span
                                        class="text-danger">*</span></label>
                                <div class="intl-tel-input">
                                    <input type="tel" class="form-control " id="mobile" name="mobile"
                                    value="{{ old('mobile', $user->mobile) }}"
                                    placeholder="{{ __('placeholder.lbl_user_conatct_number') }}" dir="ltr"
                                    style="text-align: left;" required>
                                @error('mobile')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="mobile-error">Contact Number field is required</div>
                                </div>
                            </div>
                            <input type="hidden" name="country_code" id="country_code">
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 position-relative">
                        <div class="input-group btn-file-upload">
                            {{ html()->button('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image'))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerCastcerw')->attribute('data-hidden-input', 'file_url_image')->style('height:10rem') }}

                            {{ html()->text('castcrew_input')->class('form-control')->placeholder('Select Image')->attribute('aria-label', 'Profile Image')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerCastcerw') }}
                        </div>
                        <div class="uploaded-image" id="selectedImageContainerCastcerw">
                            @if (old('file_url', isset($user) ? $user->file_url : ''))
                                <img id="profileImage"
                                    src="{{ old('file_url', isset($user) ? setBaseUrlWithFileName($user->file_url, 'image', 'users') : '') }}"
                                    class="img-fluid mb-2">
                                <span class="remove-media-icon" style="cursor: pointer; font-size: 24px; color: red;"
                                    onclick="removeProfileImage()">×</span>
                            @endif
                        </div>

                        {{ html()->hidden('file_url')->id('file_url_image')->value(old('file_url', isset($user) ? $user->file_url : '')) }}
                    </div>


                    <div class="col-md-4 mt-md-0">
                        <div class="form-group">
                            <label class="form-label" for="" class="w-100">{{ __('profile.lbl_gender') }}</label>
                            <div class="d-flex align-items-center flex-wrap gap-3">
                                <label class="form-check form-check-inline form-control w-auto flex-grow-1 mx-0 px-5 cursor-pointer">
                                    <div class="d-flex align-items-center">
                                        <input class="form-check-input me-2" type="radio" name="gender" id="male"
                                            value="male" {{ old('gender', $user->gender) == 'male' ? 'checked' : '' }} />
                                        <span class="form-check-label"> {{ __('messages.lbl_male') }} </span>
                                    </div>
                                </label>
                                <label class="form-check form-check-inline form-control w-auto flex-grow-1 mx-0 px-5 cursor-pointer">
                                    <div class="d-flex align-items-center">
                                        <input class="form-check-input me-2" type="radio" name="gender" id="female"
                                            value="female"
                                            {{ old('gender', $user->gender) == 'female' ? 'checked' : '' }} />
                                        <span class="form-check-label" for="female"> {{ __('messages.lbl_female') }}
                                        </span>
                                    </div>
                                </label>
                                <label class="form-check form-check-inline form-control w-auto flex-grow-1 mx-0 px-5 cursor-pointer">
                                    <div class="d-flex align-items-center">
                                        <input class="form-check-input me-2" type="radio" name="gender" id="other"
                                            value="other"
                                            {{ old('gender', $user->gender) == 'other' ? 'checked' : '' }} />
                                        <span class="form-check-label" for="other"> {{ __('messages.lbl_other') }}
                                        </span>
                                    </div>
                                </label>
                            </div>

                            @error('gender')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>


                <div class="form-group col-md-12 text-end">
                    {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}
                </div>
            </div>
        </div>
        </form>
    </div>
@endsection

@push('after-scripts')
    @include('components.media-modal', compact('page_type'))
    <link rel="stylesheet" href="{{ asset('vendor/intl-tel-input/css/intlTelInput.css') }}">
    <script src="{{ asset('vendor/intl-tel-input/js/intlTelInput.min.js') }}"></script>
    <script>
        var input = document.querySelector("#mobile");
        var iti = window.intlTelInput(input, {
            initialCountry: "in",
            separateDialCode: true,
            customContainer: "w-100",
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js" // To handle number formatting
        });

        function updateCountryCode() {
            if (iti && iti.getSelectedCountryData) {
                let dial = iti.getSelectedCountryData().dialCode;
                const countryCodeInput = document.getElementById('country_code');
                if (countryCodeInput) {
                    countryCodeInput.value = "+" + dial;
                }
            }
        }

        input.addEventListener("countrychange", updateCountryCode);
        input.addEventListener("input", updateCountryCode);

        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');

            if (iti.isValidNumber()) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
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

        input.addEventListener('countrychange', function() {
            if (input.value.length > 0) {
                input.dispatchEvent(new Event('input'));
            }
        });

        document.getElementById('form-submit').addEventListener('submit', function(e) {
            if (iti.isValidNumber()) {
                var fullNumber = iti.getNumber();
                var countryData = iti.getSelectedCountryData();
                var dialCode = countryData.dialCode;
                var numberWithoutCode = fullNumber.replace('+' + dialCode, '');
                input.value = '+' + dialCode + numberWithoutCode;
            } else {
                var enteredNumber = input.value;
                if (enteredNumber && !enteredNumber.startsWith('+')) {
                    var countryData = iti.getSelectedCountryData();
                    var dialCode = countryData.dialCode;
                    input.value = '+' + dialCode + enteredNumber;
                }
            }
        });

        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const container = document.getElementById('selectedImageContainerCastcerw');
                container.innerHTML = '';

                const imgElement = document.createElement('img');
                imgElement.src = reader.result;
                imgElement.classList.add('img-fluid', 'mb-2');

                const removeIcon = document.createElement('span');
                removeIcon.className = 'remove-media-icon';
                removeIcon.style.cursor = 'pointer';
                removeIcon.style.color = 'red';
                removeIcon.style.fontSize = '24px';
                removeIcon.textContent = '×';
                removeIcon.onclick = removeProfileImage;

                container.appendChild(imgElement);
                container.appendChild(removeIcon);
            }
            reader.readAsDataURL(event.target.files[0]);
        }

        function removeProfileImage() {
            const container = document.getElementById('selectedImageContainerCastcerw');
            const hiddenInput = document.getElementById('file_url_image');

            container.innerHTML = '';
            hiddenInput.value = '';
        }
    </script>
@endpush
