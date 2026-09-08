@extends('backend.layouts.app')
@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <x-back-button-component route="backend.constants.index" />
    {{ html()->form('POST', route('backend.constants.store'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->class('requires-validation')->attribute('novalidate', 'novalidate')->open() }}
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-6">
                    {{ html()->label(__('constant.lbl_name') . ' <span class="text-danger">*</span>', 'name')->class('form-label') }}
                    {{ html()->text('name')->value(old('name'))->placeholder(__('constant.lbl_name'))->class('form-control')->required() }}
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="name-error">{{ __('messages.name_field_required') }}</div>
                </div>

                <div class="col-md-6">
                    {{ html()->label(__('constant.lbl_type') . ' <span class="text-danger">*</span>', 'type')->class('form-label') }}
                    <select name="type" id="type" class="form-control select2" required>
                        <option value="">{{ __('constant.select_type') }}</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>
                                @php
                                    // Format type name for display
                                    $displayType = '';
                                    if ($type == 'video_quality') {
                                        $displayType = __('constant.video_quality');
                                    } elseif ($type == 'movie_language') {
                                        $displayType = __('constant.movie_language');
                                    } elseif ($type == 'language') {
                                        $displayType = __('messages.lbl_language');
                                    } elseif ($type == 'upload_type') {
                                        $displayType = __('messages.lbl_upload_type');
                                    } elseif ($type == 'STREAM_TYPE') {
                                        $displayType = __('movie.lbl_stream_type');
                                    } elseif ($type == 'notification_type') {
                                        $displayType = __('messages.lbl_notification_type');
                                    } elseif ($type == 'notification_param_button') {
                                        $displayType = __('messages.lbl_notification_param_button');
                                    } elseif ($type == 'notification_to') {
                                        $displayType = __('messages.lbl_notification_to');
                                    } elseif ($type == 'PAYMENT_STATUS') {
                                        $displayType = __('messages.lbl_payment_status');
                                    } elseif ($type == 'subtitle_language') {
                                        $displayType = __('messages.lbl_subtitle_language');
                                    } else {
                                        $displayType = ucwords(str_replace('_', ' ', $type));
                                    }
                                @endphp
                                {{ $displayType }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="type-error">{{ __('messages.type_field_required') }}</div>
                </div>

                <div class="col-md-6">
                    {{ html()->label(__('constant.lbl_value') . ' <span class="text-danger">*</span>', 'value')->class('form-label') }}
                    {{ html()->text('value')->value(old('value'))->placeholder(__('constant.lbl_value'))->class('form-control')->required() }}
                    @error('value')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="value-error">{{ __('messages.value_field_required') }}</div>
                </div>

                <!-- Language Image Upload Field (only visible when type is language) -->
                <div class="col-md-6 d-none" id="language_image_upload" >
                    <div class="position-relative">
                        
                        {{ html()->label(__('messages.lbl_language_image'), 'language_image')->class('form-label') }}
                        <div class="input-group btn-file-upload">
                            {{ html()->button('<i class="ph ph-image"></i> ' . __('messages.lbl_choose_image'))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerLanguage')->attribute('data-hidden-input', 'language_image')->style('height:13.6rem') }}
                            {{ html()->text('language_image_input')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Language Image')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerLanguage')->attribute('data-hidden-input', 'language_image') }}
                        </div>
                        <div class="uploaded-image" id="selectedImageContainerLanguage">
                            @php
                                $languageImageValue = old('language_image', '');
                                $languageImageUrl = $languageImageValue ? setBaseUrlWithFileName($languageImageValue, 'image', 'constant') : '';
                            @endphp
                            <img id="selectedLanguageImage"
                                src="{{ $languageImageUrl }}"
                                alt="language-image" class="img-fluid mb-2"
                                style="{{ $languageImageUrl ? '' : 'display:none;' }}" />
                        </div>
                        {{ html()->hidden('language_image')->id('language_image')->value($languageImageValue) }}
                    </div>
                    @error('language_image')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6">
                    {{ html()->label(__('plan.lbl_status'), 'status')->class('form-label') }}
                    <div class="d-flex align-items-center justify-content-between form-control">
                        {{ html()->label(__('messages.active'), 'status')->class('form-label mb-0 text-body') }}
                        <div class="form-check form-switch">
                            {{ html()->hidden('status', 0) }}
                            {{ html()->checkbox('status', old('status', 1))->class('form-check-input')->id('status')->value(1) }}
                        </div>
                    </div>
                    @error('status')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </div>
    <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mb-5">
        <button type="submit" class="btn btn-primary" id="submit-button">{{ __('messages.save') }}</button>
    </div>
    {{ html()->form()->close() }}
    @include('components.media-modal', compact('page_type'))

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const typeSelect = document.getElementById('type');
                const languageImageUpload = document.getElementById('language_image_upload');

                if (typeSelect && languageImageUpload) {
                    function toggleLanguageImageUpload() {
                        // Get the value - try Select2 first, then fallback to regular value
                        let selectedValue = '';
                        if (typeof $ !== 'undefined' && $(typeSelect).length > 0) {
                            selectedValue = $(typeSelect).val();
                        } else {
                            selectedValue = typeSelect.value;
                        }

                        if (selectedValue === 'movie_language') {
                            languageImageUpload.classList.remove('d-none');
                        } else {
                            languageImageUpload.classList.add('d-none');
                        }

                        // Clear validation once a valid type is selected (Select2 friendly)
                        if (selectedValue) {
                            typeSelect.classList.remove('is-invalid');
                            const errorEl = document.getElementById('type-error');
                            if (errorEl) errorEl.style.display = 'none';
                            if (typeof $ !== 'undefined') {
                                $(typeSelect).next('.select2-container').find('.select2-selection').removeClass(
                                    'is-invalid');
                            }
                        }
                    }

                    // Initial check
                    toggleLanguageImageUpload();

                    // Listen for regular select change
                    typeSelect.addEventListener('change', function() {
                        console.log('Regular change event fired');
                        toggleLanguageImageUpload();
                    });

                    // Listen for Select2 change events (jQuery must be available since Select2 is used)
                    if (typeof $ !== 'undefined' && $(typeSelect).length > 0) {
                        $(typeSelect).on('change', function() {
                            console.log('jQuery change event fired');
                            toggleLanguageImageUpload();
                        });

                        // Also listen for Select2 specific events
                        $(typeSelect).on('select2:select', function() {
                            console.log('Select2 select event fired');
                            toggleLanguageImageUpload();
                        });

                        $(typeSelect).on('select2:selecting', function() {
                            console.log('Select2 selecting event fired');
                            setTimeout(function() {
                                toggleLanguageImageUpload();
                            }, 100);
                        });
                    }
                }
            }, 500);
        });
    </script>
@endsection
