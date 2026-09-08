@extends('setting::backend.setting.index')

@section('title')
    {{ __('messages.lbl_seo_settings') }}
@endsection

@section('settings-content')
    <div class="d-flex align-items-center justify-content-between">
        <h4 class="mb-0"><i class="fa fa-search fa-lg mr-2"></i>&nbsp;{{ __('messages.lbl_seo_settings') }}</h4>
    </div>

    {{ html()->form('POST', route('seo.store'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'seo-form')->class('requires-validation')->attribute('novalidate', 'novalidate')->open() }}
    @csrf

    <div class="card">
        <div class="card-body">
            <div class="row gy-3">
                <!-- SEO Fields Section -->
                <div>
                    <div class="row mb-3">
                        <!-- SEO Image -->
                        <input type="hidden" name="id" value="{{ $seo->id ?? '' }}">
                        <div class="col-md-4 position-relative">

                            {{ html()->hidden('seo_image')->id('seo_image')->value(old('seo_image', $seoData['seo_image'] ?? '')) }}

                            {!! html()->label(__('messages.lbl_seo_image') . ' <span class="required">*</span>', 'seo_image')->class('form-label')->attribute('for', 'seo_image') !!}

                            <div class="input-group btn-file-upload">
                                {{ html()->button('<i class="ph ph-image"></i> ' . __('messages.lbl_choose_image'))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerSeo')->attribute('data-hidden-input', 'seo_image')->id('seo-image-url-button')->style('height:13.6rem') }}

                                {{ html()->text('seo_image_input')->class('form-control ' . ($errors->has('seo_image') ? 'is-invalid' : ''))->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'SEO Image')->attribute('readonly', true)->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerSeo')->attribute('data-hidden-input', 'seo_image') }}
                            </div>

                            {{-- 👇 Moved this outside input-group --}}
                            <div class="invalid-feedback mt-1" id="seo_image_error" style="display: none;">
                                {{ __('messages.seo_image_required') }}
                            </div>

                            <div class="uploaded-image mt-2" id="selectedImageContainerSeo">
                                <img id="selectedSeoImage" src="{{ old('seo_image', $seoData['seo_image'] ?? '') }}"
                                    alt="seo-image-preview" class="img-fluid"
                                    style="{{ old('seo_image', $seoData['seo_image'] ?? '') ? '' : 'display:none;' }}" />
                            </div>

                            @error('seo_image')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>



                        <!-- Meta Title + Google Verification -->
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <div class="d-flex justify-content-between">
                                    {!! html()->label(__('messages.lbl_meta_title') . ' <span class="required">*</span>', 'meta_title')->class('form-label')->attribute('for', 'meta_title') !!}

                                    <div id="meta-title-char-count" class="text-muted">0/100 {{ __('messages.words') }}
                                    </div>
                                </div>

                                <input type="text" name="meta_title" id="meta_title"
                                    class="form-control @error('meta_title') is-invalid @enderror"
                                    value="{{ old('meta_title', $seo->meta_title ?? '') }}" maxlength="100"
                                    placeholder="{{ __('placeholder.lbl_meta_title') }}" required>

                                @error('meta_title')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback" id="embed-error">{{ __('messages.meta_title_required') }}
                                </div>
                            </div>



                            <div class="form-group mb-3">
                                {!! html()->label(
                                        __('messages.lbl_google_site_verification') . ' <span class="required">*</span>',
                                        'google_site_verification',
                                    )->class('form-label')->attribute('for', 'google_site_verification') !!}

                                <input type="text" name="google_site_verification" id="google_site_verification"
                                    class="form-control @error('google_site_verification') is-invalid @enderror"
                                    value="{{ old('google_site_verification', $seo->google_site_verification ?? '') }}"
                                    placeholder="{{ __('placeholder.lbl_google_site_verification') }}" required>
                                @error('google_site_verification')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback" id="embed-error">
                                    {{ __('messages.google_site_verification_required') }}</div>
                            </div>
                        </div>

                        @php
                            $metaKeywordsValue = old('meta_keywords', $seo->meta_keywords ?? '');
                            $metaKeywordsArray = is_array($metaKeywordsValue)
                                ? $metaKeywordsValue
                                : array_filter(array_map('trim', explode(',', (string) $metaKeywordsValue)));
                        @endphp

                        <!-- Meta Keywords + Canonical URL -->
                        <div class="col-md-4">


                            <div class="form-group mb-3">
                                {!! html()->label(__('messages.lbl_meta_keywords') . ' <span class="required">*</span>', 'meta_keywords_input')->class('form-label')->attribute('for', 'meta_keywords_input') !!}

                                <input type="text" id="meta_keywords_input"
                                    class="form-control @error('meta_keywords') is-invalid @enderror"
                                    placeholder="{{ __('placeholder.lbl_meta_keywords') }}"
                                    data-placeholder="{{ __('placeholder.lbl_meta_keywords') }}"
                                    value=""
                                    data-initial-tags='@json($metaKeywordsArray)' />

                                <div class="invalid-feedback" id="meta_keywords_error" style="display: none;">
                                    {{ __('messages.meta_keywords_required') }}
                                </div>

                                <div id="meta_keywords_hidden_inputs"></div>
                            </div>



                            <div class="form-group mb-3">
                                {!! html()->label(__('messages.lbl_canonical_url') . ' <span class="required">*</span>', 'canonical_url')->class('form-label')->attribute('for', 'canonical_url') !!}

                                <input type="text" name="canonical_url" id="canonical_url"
                                    class="form-control @error('canonical_url') is-invalid @enderror"
                                    value="{{ old('canonical_url', $seo->canonical_url ?? '') }}"
                                    placeholder="{{ __('placeholder.lbl_canonical_url') }}" required>
                                @error('canonical_url')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback" id="embed-error">{{ __('messages.canonical_url_required') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Short Description -->
                    <div class="row">
                        <div class="col-md-12 form-group mb-3">
                            <div class="d-flex justify-content-between">
                                {!! html()->label(__('messages.lbl_short_description') . ' <span class="required">*</span>', 'short_description')->class('form-label')->attribute('for', 'short_description') !!}

                                <div id="meta-description-char-count" class="text-muted">0/200 {{ __('messages.words') }}
                                </div>
                            </div>

                            <textarea name="short_description" id="short_description"
                                class="form-control @error('short_description') is-invalid @enderror" maxlength="200"
                                placeholder="{{ __('placeholder.lbl_short_description') }}" required>{{ old('short_description', $seo->short_description ?? '') }}</textarea>

                            @error('short_description')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                            <div class="invalid-feedback" id="embed-error">
                                {{ __('messages.site_meta_description_required') }}</div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mb-5">
        {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}
    </div>

    {{ html()->form()->close() }}


    @include('components.media-modal', compact('page_type'))
@endsection

@push('after-scripts')
    <script src="{{ asset('js/tagify.min.js') }}"></script>

    <script>
        (function() {
            'use strict';

            let tagifyInstance = null;

            // Validate SEO Image field
            function validateSeoImage() {
                const seoImageValue = document.getElementById('seo_image').value.trim();
                const selectedSeoImage = document.getElementById('selectedSeoImage');
                const errorDiv = document.getElementById('seo_image_error');
                const noImageSelected = !seoImageValue && (!selectedSeoImage.src || selectedSeoImage.style.display ===
                    'none');

                errorDiv.style.display = noImageSelected ? 'block' : 'none';
                return !noImageSelected;
            }

            // Validate Meta Keywords field
            function validateMetaKeywords(forceShow = false) {
                const errorMsg = document.getElementById('meta_keywords_error');
                const tagifyInput = document.getElementById('meta_keywords_input');
                const hasKeywords = tagifyInstance?.value?.length > 0;

                if (errorMsg) {
                    if (hasKeywords) {
                        errorMsg.style.display = 'none';
                    } else if (forceShow) {
                        errorMsg.style.display = 'block';
                    }
                }
                if (tagifyInput) {
                    tagifyInput.classList.toggle('is-invalid', forceShow && !hasKeywords);
                }

                return hasKeywords;
            }

            // Sync Tagify tags to hidden inputs
            function syncHiddenInputs() {
                const hiddenContainer = document.getElementById('meta_keywords_hidden_inputs');
                if (!hiddenContainer || !tagifyInstance) return;

                hiddenContainer.innerHTML = '';
                tagifyInstance.value.forEach(item => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'meta_keywords[]';
                    hiddenInput.value = typeof item === 'string' ? item : (item.value || item);
                    hiddenContainer.appendChild(hiddenInput);
                });
            }

            // Clear all validation errors
            function clearValidationErrors() {
                document.querySelectorAll('.invalid-feedback, .text-danger').forEach(el => {
                    if (el.id !== 'seo_image_error' && el.id !== 'meta_keywords_error') {
                        el.style.display = 'none';
                    }
                });
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            }

            // Display validation errors from server
            function displayValidationErrors(errors) {
                clearValidationErrors();

                Object.entries(errors).forEach(([field, messages]) => {
                    const input = document.querySelector(`[name="${field}"]`);
                    if (!input) return;

                    input.classList.add('is-invalid');
                    let errorDiv = input.parentElement.querySelector('.invalid-feedback');

                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback';
                        input.parentElement.appendChild(errorDiv);
                    }

                    errorDiv.textContent = Array.isArray(messages) ? messages[0] : messages;
                    errorDiv.style.display = 'block';
                });
            }

            // Initialize character counter
            function initCharCounter(inputField, charCountDisplay, limit) {
                if (!inputField || !charCountDisplay) return;

                const updateCount = () => {
                    const currentLength = inputField.value.length;
                    charCountDisplay.textContent = `${currentLength}/${limit}`;
                    charCountDisplay.style.color = currentLength > limit ? 'red' : 'green';
                };

                updateCount();
                inputField.addEventListener('input', updateCount);
            }

            // Initialize Tagify for Meta Keywords
            function initTagify() {
                const input = document.querySelector('#meta_keywords_input');
                if (!input) return;

                const placeholderText = input.getAttribute('data-placeholder') ||
                    input.getAttribute('placeholder') ||
                    '{{ __('placeholder.lbl_meta_keywords') }}';

                tagifyInstance = new Tagify(input, {
                    placeholder: placeholderText,
                    delimiters: ",",
                    dropdown: {
                        enabled: 0
                    },
                    transformTag: (tagData) => tagData.value
                });

                const onTagsChange = () => {
                    syncHiddenInputs();
                    validateMetaKeywords(false);
                };

                tagifyInstance.on('add', onTagsChange);
                tagifyInstance.on('remove', onTagsChange);
                tagifyInstance.on('change', onTagsChange);

                const initialTags = input.dataset.initialTags ? JSON.parse(input.dataset.initialTags) : [];
                if (Array.isArray(initialTags) && initialTags.length) {
                    tagifyInstance.addTags(initialTags);
                }

                syncHiddenInputs();
                validateMetaKeywords(false);
            }

            // Handle form submission with AJAX
            function handleFormSubmit(e) {
                e.preventDefault();
                e.stopPropagation();

                // Client-side validation
                const isValid = validateSeoImage() & validateMetaKeywords(true);
                syncHiddenInputs();

                if (!isValid) return false;

                clearValidationErrors();

                const form = document.getElementById('seo-form');
                const submitBtn = document.getElementById('submit-button');
                const originalBtnText = submitBtn.innerHTML;

                submitBtn.disabled = true;

                const permissionMessage = {!! json_encode(__('messages.user_does_not_have_permission_to_change_password')) !!};

                fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.text().then(text => {
                        let data = {};
                        try {
                            data = text ? JSON.parse(text) : {};
                        } catch (e) {}
                        return { ok: response.ok, status: response.status, data };
                    }))
                    .then(({
                        ok,
                        status,
                        data
                    }) => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;

                        if (ok && data.success) {
                            Snackbar.show({
                                text: data.message || 'SEO settings saved successfully!',
                                pos: 'bottom-left'
                            });

                            if (data.data?.id) {
                                const idInput = document.querySelector('input[name="id"]');
                                if (idInput) idInput.value = data.data.id;
                            }
                        } else {
                            if (data.errors) {
                                displayValidationErrors(data.errors);
                            } else {
                                const msg = (status === 403 && permissionMessage) ? permissionMessage : (data.message || 'An error occurred while saving.');
                                Snackbar.show({
                                    text: msg,
                                    pos: 'bottom-left'
                                });
                            }
                        }
                    })
                    .catch(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                        Snackbar.show({
                            text: permissionMessage || 'An unexpected error occurred. Please try again.',
                            pos: 'bottom-left'
                        });
                    });

                return false;
            }

            document.addEventListener('DOMContentLoaded', function() {
                initCharCounter(
                    document.getElementById('meta_title'),
                    document.getElementById('meta-title-char-count'),
                    100
                );
                initCharCounter(
                    document.getElementById('short_description'),
                    document.getElementById('meta-description-char-count'),
                    200
                );

                initTagify();

                const seoForm = document.getElementById('seo-form');
                if (seoForm) seoForm.addEventListener('submit', handleFormSubmit);
            });
        })();
    </script>
    <style>
        .required {
            color: red;
        }
    </style>
@endpush
