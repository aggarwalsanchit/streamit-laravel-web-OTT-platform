@extends('setting::backend.setting.index')

@section('title')
    {{ __('setting_sidebar.lbl_storage') }}
@endsection

@section('settings-content')
    <div class="col-md-12 mb-3 d-flex justify-content-between">
        <h3 class="mb-0"><i class="fa-solid fa-database"></i> {{ __('setting_sidebar.lbl_storage') }}</h3>
    </div>

    <form method="POST" action="{{ route('backend.setting.store') }}" id="form-submit" novalidate data-custom-handler="true">
        @csrf
        <input type="hidden" name="setting_tab" value="storage">

        <div class="form-group border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label m-0" for="local">{{ __('settings.lbl_local_storage') }}</label>
                <input type="hidden" value="0" name="local">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input storage-checkbox" value="1" name="local" id="local"
                        type="checkbox" {{ old('local', $settings['local'] ?? 1) == 1 ? 'checked' : '' }} />
                </div>
            </div>
        </div>

        <div class="form-group border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label m-0" for="s3">{{ __('settings.lbl_s3_storage') }}</label>
                <input type="hidden" value="0" name="s3">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input storage-checkbox" value="1" name="s3" id="s3"
                        type="checkbox" {{ old('s3', $settings['s3'] ?? 0) == 1 ? 'checked' : '' }} />
                </div>
            </div>
        </div>

        <div class="form-group border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label m-0" for="bunny">{{ __('settings.lbl_bunny_storage') }}</label>
                <input type="hidden" value="0" name="bunny">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input storage-checkbox" value="1" name="bunny" id="bunny"
                        type="checkbox" {{ old('bunny', $settings['bunny'] ?? 0) == 1 ? 'checked' : '' }} />
                </div>
            </div>
        </div>

        <div id="aws-s3-fields" style="display: none;">
            <div class="form-group">
                <label for="aws_access_key">{{ __('settings.lbl_aws_id') }} <span class="text-danger">*</span></label>
                <input type="text" name="aws_access_key" id="aws_access_key" class="form-control"
                    value="{{ old('aws_access_key', $settings['aws_access_key'] ?? '') }}">
                <div class="invalid-feedback" id="aws_access_key_error">{{ __('messages.aws_access_key_required') }}</div>
            </div>
            <div class="form-group">
                <label for="aws_secret_key">{{ __('settings.lbl_aws_secret_key') }} <span
                        class="text-danger">*</span></label>
                <input type="text" name="aws_secret_key" id="aws_secret_key" class="form-control"
                    value="{{ old('aws_secret_key', $settings['aws_secret_key'] ?? '') }}">
                <div class="invalid-feedback" id="aws_secret_key_error">{{ __('messages.aws_secret_key_required') }}</div>
            </div>
            <div class="form-group">
                <label for="aws_region">{{ __('settings.lbl_aws_region') }} <span class="text-danger">*</span></label>
                <input type="text" name="aws_region" id="aws_region" class="form-control"
                    value="{{ old('aws_region', $settings['aws_region'] ?? '') }}">
                <div class="invalid-feedback" id="aws_region_error">{{ __('messages.aws_region_required') }}</div>
            </div>
            <div class="form-group">
                <label for="aws_bucket">{{ __('settings.lbl_aws_bucket') }} <span class="text-danger">*</span></label>
                <input type="text" name="aws_bucket" id="aws_bucket" class="form-control"
                    value="{{ old('aws_bucket', $settings['aws_bucket'] ?? '') }}">
                <div class="invalid-feedback" id="aws_bucket_error">{{ __('messages.aws_bucket_required') }}</div>
            </div>
            <div class="form-group">
                <label for="aws_path_style">{{ __('settings.lbl_aws_endpoint') }} <span
                        class="text-danger">*</span></label>
                <select name="aws_path_style" id="aws_path_style" class="form-control">
                    <option value="false"
                        {{ old('aws_path_style', $settings['aws_path_style'] ?? 'false') == 'false' ? 'selected' : '' }}>
                        False</option>
                    <option value="true"
                        {{ old('aws_path_style', $settings['aws_path_style'] ?? 'false') == 'true' ? 'selected' : '' }}>
                        True</option>
                </select>
                <div class="invalid-feedback" id="aws_path_style_error">{{ __('messages.aws_endpoint_required') }}</div>
            </div>
        </div>

        <div id="bunny-fields" style="display: none;">
            <div class="form-group">
                <label for="bunny_storage_zone">{{ __('settings.lbl_bunny_storage_zone') }} <span
                        class="text-danger">*</span></label>
                <input type="text" name="bunny_storage_zone" id="bunny_storage_zone" class="form-control"
                    placeholder="{{ __('settings.placeholder_bunny_storage_zone') }}"
                    value="{{ old('bunny_storage_zone', $settings['bunny_storage_zone'] ?? '') }}">
                <div class="invalid-feedback" id="bunny_storage_zone_error">
                    {{ __('messages.bunny_storage_zone_required') }}</div>
            </div>
            <div class="form-group">
                <label for="bunny_api_key">{{ __('settings.lbl_bunny_api_key') }} <span
                        class="text-danger">*</span></label>
                <input type="text" name="bunny_api_key" id="bunny_api_key" class="form-control"
                    placeholder="{{ __('settings.placeholder_bunny_api_key') }}"
                    value="{{ old('bunny_api_key', $settings['bunny_api_key'] ?? '') }}">
                <div class="invalid-feedback" id="bunny_api_key_error">{{ __('messages.bunny_api_key_required') }}</div>
            </div>
            <div class="form-group">
                <label for="bunny_cdn_url">{{ __('settings.lbl_bunny_cdn_url') }} <span
                        class="text-danger">*</span></label>
                <input type="text" name="bunny_cdn_url" id="bunny_cdn_url" class="form-control"
                    placeholder="{{ __('settings.placeholder_bunny_cdn_url') }}"
                    value="{{ old('bunny_cdn_url', $settings['bunny_cdn_url'] ?? '') }}">
                <div class="invalid-feedback" id="bunny_cdn_url_error">{{ __('messages.bunny_cdn_url_required') }}</div>
            </div>
            <div class="form-group">
                <label for="bunny_region">{{ __('settings.lbl_bunny_region') }} <span
                        class="text-danger">*</span></label>
                <input type="text" name="bunny_region" id="bunny_region" class="form-control"
                    placeholder="{{ __('settings.placeholder_bunny_region') }}"
                    value="{{ old('bunny_region', $settings['bunny_region'] ?? '') }}">
                <div class="invalid-feedback" id="bunny_region_error">{{ __('messages.bunny_region_required') }}</div>
            </div>

            <div class="mt-4 mb-3">
                <h6 class="text-primary"><i class="fa-solid fa-stream"></i> {{ __('settings.lbl_bunny_stream') }}</h6>
            </div>

            <div class="form-group">
                <label for="bunny_stream_api_key">{{ __('settings.lbl_bunny_stream_api_key') }} <span
                        class="text-danger">*</span></label>
                <input type="text" name="bunny_stream_api_key" id="bunny_stream_api_key" class="form-control"
                    placeholder="{{ __('settings.placeholder_bunny_stream_api_key') }}"
                    value="{{ old('bunny_stream_api_key', $settings['bunny_stream_api_key'] ?? '') }}">
                <div class="invalid-feedback" id="bunny_stream_api_key_error">
                    {{ __('messages.bunny_stream_api_key_required') }}</div>
            </div>
            <div class="form-group">
                <label for="bunny_video_key">{{ __('settings.lbl_bunny_video_key') }} <span
                        class="text-danger">*</span></label>
                <input type="text" name="bunny_video_key" id="bunny_video_key" class="form-control"
                    placeholder="{{ __('settings.placeholder_bunny_video_key') }}"
                    value="{{ old('bunny_video_key', $settings['bunny_video_key'] ?? '') }}">
                <div class="invalid-feedback" id="bunny_video_key_error">{{ __('messages.bunny_video_key_required') }}
                </div>
            </div>
            <div class="form-group">
                <label for="bunny_cdn_hostname">{{ __('settings.lbl_bunny_cdn_hostname') }} <span
                        class="text-danger">*</span></label>
                <input type="text" name="bunny_cdn_hostname" id="bunny_cdn_hostname" class="form-control"
                    placeholder="{{ __('settings.placeholder_bunny_cdn_hostname') }}"
                    value="{{ old('bunny_cdn_hostname', $settings['bunny_cdn_hostname'] ?? '') }}">
                <div class="invalid-feedback" id="bunny_cdn_hostname_error">
                    {{ __('messages.bunny_cdn_hostname_required') }}</div>
            </div>
            <div class="form-group">
                <label for="bunny_stream_pull_zone">{{ __('settings.lbl_bunny_stream_pull_zone') }} <span
                        class="text-danger">*</span></label>
                <input type="text" name="bunny_stream_pull_zone" id="bunny_stream_pull_zone" class="form-control"
                    placeholder="{{ __('settings.placeholder_bunny_stream_pull_zone') }}"
                    value="{{ old('bunny_stream_pull_zone', $settings['bunny_stream_pull_zone'] ?? '') }}">
                <div class="invalid-feedback" id="bunny_stream_pull_zone_error">
                    {{ __('messages.bunny_stream_pull_zone_required') }}</div>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" id="submit-button" class="btn btn-primary">{{ __('messages.save') }}</button>
        </div>
    </form>
@endsection

@push('after-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const localCheckbox = document.getElementById('local');
            const s3Checkbox = document.getElementById('s3');
            const bunnyCheckbox = document.getElementById('bunny');
            const awsS3Fields = document.getElementById('aws-s3-fields');
            const bunnyFields = document.getElementById('bunny-fields');
            const awsAccessKey = document.getElementById('aws_access_key');
            const awsSecretKey = document.getElementById('aws_secret_key');
            const awsRegion = document.getElementById('aws_region');
            const awsBucket = document.getElementById('aws_bucket');
            const awsPathStyle = document.getElementById('aws_path_style');
            const bunnyStorageZone = document.getElementById('bunny_storage_zone');
            const bunnyApiKey = document.getElementById('bunny_api_key');
            const bunnyCdnUrl = document.getElementById('bunny_cdn_url');
            const bunnyRegion = document.getElementById('bunny_region');
            const bunnyStreamApiKey = document.getElementById('bunny_stream_api_key');
            const bunnyVideoKey = document.getElementById('bunny_video_key');
            const bunnyCdnHostname = document.getElementById('bunny_cdn_hostname');
            const bunnyStreamPullZone = document.getElementById('bunny_stream_pull_zone');

            // Only show validation errors after submit is attempted
            let storageShowErrors = false;

            function updateStorageSettings() {
                if (s3Checkbox.checked) {
                    awsS3Fields.style.display = 'block';
                    awsAccessKey.setAttribute('required', 'required');
                    awsSecretKey.setAttribute('required', 'required');
                    awsRegion.setAttribute('required', 'required');
                    awsBucket.setAttribute('required', 'required');
                    awsPathStyle.setAttribute('required', 'required');
                    // Do not show errors on toggle; they will show on submit
                } else {
                    awsS3Fields.style.display = 'none';
                    awsAccessKey.removeAttribute('required');
                    awsSecretKey.removeAttribute('required');
                    awsRegion.removeAttribute('required');
                    awsBucket.removeAttribute('required');
                    awsPathStyle.removeAttribute('required');
                    clearErrors();
                }

                if (bunnyCheckbox.checked) {
                    bunnyFields.style.display = 'block';
                    bunnyStorageZone.setAttribute('required', 'required');
                    bunnyApiKey.setAttribute('required', 'required');
                    bunnyCdnUrl.setAttribute('required', 'required');
                    bunnyRegion.setAttribute('required', 'required');
                    bunnyStreamApiKey.setAttribute('required', 'required');
                    bunnyVideoKey.setAttribute('required', 'required');
                    bunnyCdnHostname.setAttribute('required', 'required');
                    bunnyStreamPullZone.setAttribute('required', 'required');
                    // Do not show errors on toggle; they will show on submit
                } else {
                    bunnyFields.style.display = 'none';
                    bunnyStorageZone.removeAttribute('required');
                    bunnyApiKey.removeAttribute('required');
                    bunnyCdnUrl.removeAttribute('required');
                    bunnyRegion.removeAttribute('required');
                    bunnyStreamApiKey.removeAttribute('required');
                    bunnyVideoKey.removeAttribute('required');
                    bunnyCdnHostname.removeAttribute('required');
                    bunnyStreamPullZone.removeAttribute('required');
                    clearBunnyErrors();
                    clearBunnyStreamErrors();
                }
            }

            function handleCheckboxChange() {
                if (localCheckbox.checked) {
                    s3Checkbox.checked = false;
                    bunnyCheckbox.checked = false;
                    awsS3Fields.style.display = 'none';
                    bunnyFields.style.display = 'none';
                    clearErrors();
                    clearBunnyErrors();
                } else {
                    s3Checkbox.checked = true; // Automatically check the S3 checkbox when Local is unchecked
                }
                updateStorageSettings();
            }

            function handleS3CheckboxChange() {
                if (s3Checkbox.checked) {
                    localCheckbox.checked = false;
                    bunnyCheckbox.checked = false;
                    bunnyFields.style.display = 'none';
                    clearBunnyErrors();
                } else {
                    localCheckbox.checked = true; // Automatically check the Local checkbox when S3 is unchecked
                }
                updateStorageSettings();
            }

            function handleBunnyCheckboxChange() {
                if (bunnyCheckbox.checked) {
                    localCheckbox.checked = false;
                    s3Checkbox.checked = false;
                    awsS3Fields.style.display = 'none';
                    clearErrors();
                } else {
                    localCheckbox.checked = true; // Automatically check the Local checkbox when Bunny is unchecked
                }
                updateStorageSettings();
            }

            function validateFields() {
                let isValid = true;

                function validateField(field, errorElementId) {
                    const errorElement = document.getElementById(errorElementId);
                    if (!field.value.trim()) {
                        if (storageShowErrors) {
                            errorElement.style.display = 'block';
                            field.classList.add('is-invalid');
                        } else {
                            errorElement.style.display = 'none';
                            field.classList.remove('is-invalid');
                        }
                        isValid = false;
                    } else {
                        errorElement.style.display = 'none';
                        field.classList.remove('is-invalid');
                    }
                }

                validateField(awsAccessKey, 'aws_access_key_error');
                validateField(awsSecretKey, 'aws_secret_key_error');
                validateField(awsRegion, 'aws_region_error');
                validateField(awsBucket, 'aws_bucket_error');
                validateField(awsPathStyle, 'aws_path_style_error');

                return isValid;
            }

            function validateBunnyFields() {
                let isValid = true;

                function validateField(field, errorElementId) {
                    const errorElement = document.getElementById(errorElementId);
                    if (!field.value.trim()) {
                        if (storageShowErrors) {
                            errorElement.style.display = 'block';
                            field.classList.add('is-invalid');
                        } else {
                            errorElement.style.display = 'none';
                            field.classList.remove('is-invalid');
                        }
                        isValid = false;
                    } else {
                        errorElement.style.display = 'none';
                        field.classList.remove('is-invalid');
                    }
                }

                validateField(bunnyStorageZone, 'bunny_storage_zone_error');
                validateField(bunnyApiKey, 'bunny_api_key_error');
                validateField(bunnyCdnUrl, 'bunny_cdn_url_error');
                validateField(bunnyRegion, 'bunny_region_error');

                return isValid;
            }

            function validateBunnyStreamFields() {
                let isValid = true;

                function validateField(field, errorElementId) {
                    const errorElement = document.getElementById(errorElementId);
                    if (!field.value.trim()) {
                        if (storageShowErrors) {
                            errorElement.style.display = 'block';
                            field.classList.add('is-invalid');
                        } else {
                            errorElement.style.display = 'none';
                            field.classList.remove('is-invalid');
                        }
                        isValid = false;
                    } else {
                        errorElement.style.display = 'none';
                        field.classList.remove('is-invalid');
                    }
                }

                validateField(bunnyStreamApiKey, 'bunny_stream_api_key_error');
                validateField(bunnyVideoKey, 'bunny_video_key_error');
                validateField(bunnyCdnHostname, 'bunny_cdn_hostname_error');
                validateField(bunnyStreamPullZone, 'bunny_stream_pull_zone_error');

                return isValid;
            }

            function clearErrors() {
                ['aws_access_key_error', 'aws_secret_key_error', 'aws_region_error', 'aws_bucket_error',
                    'aws_path_style_error'
                ].forEach(function(id) {
                    document.getElementById(id).style.display = 'none';
                });
                [awsAccessKey, awsSecretKey, awsRegion, awsBucket, awsPathStyle].forEach(function(field) {
                    field.classList.remove('is-invalid');
                });
            }

            function clearBunnyErrors() {
                ['bunny_storage_zone_error', 'bunny_api_key_error', 'bunny_cdn_url_error', 'bunny_region_error']
                .forEach(function(id) {
                    document.getElementById(id).style.display = 'none';
                });
                [bunnyStorageZone, bunnyApiKey, bunnyCdnUrl, bunnyRegion].forEach(function(field) {
                    field.classList.remove('is-invalid');
                });
            }

            function clearBunnyStreamErrors() {
                ['bunny_stream_api_key_error', 'bunny_video_key_error', 'bunny_cdn_hostname_error',
                    'bunny_stream_pull_zone_error'
                ].forEach(function(id) {
                    document.getElementById(id).style.display = 'none';
                });
                [bunnyStreamApiKey, bunnyVideoKey, bunnyCdnHostname, bunnyStreamPullZone].forEach(function(field) {
                    field.classList.remove('is-invalid');
                });
            }

            updateStorageSettings();

            localCheckbox.addEventListener('change', handleCheckboxChange);
            s3Checkbox.addEventListener('change', handleS3CheckboxChange);
            bunnyCheckbox.addEventListener('change', handleBunnyCheckboxChange);

            const form = document.getElementById('form-submit');
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton ? submitButton.innerHTML : '';
            
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                
                // Enable showing errors on submit attempt
                storageShowErrors = true;

                let allValid = true;
                if (s3Checkbox.checked && !validateFields()) {
                    allValid = false;
                }
                if (bunnyCheckbox.checked && !validateBunnyFields()) {
                    allValid = false;
                }
                if (bunnyCheckbox.checked && !validateBunnyStreamFields()) {
                    allValid = false;
                }

                if (!allValid) {
                    const firstInvalid = document.querySelector('.is-invalid');
                    if (firstInvalid && typeof firstInvalid.focus === 'function') {
                        firstInvalid.focus();
                    }
                    return false;
                }
                
                // Show loading state
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
                }
                
                // Submit via AJAX
                const formData = new FormData(form);
                const formAction = form.getAttribute('action');
                const formMethod = form.getAttribute('method') || 'POST';
                
                fetch(formAction, {
                    method: formMethod,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json, text/html'
                    }
                })
                .then(response => {
                    const contentType = response.headers.get('content-type') || '';
                    
                    if (!response.ok) {
                        if (contentType.includes('application/json')) {
                            return response.json().then(data => ({ type: 'json', data, error: true }));
                        } else {
                            return response.text().then(html => ({ type: 'html', data: html, error: true }));
                        }
                    }
                    
                    if (contentType.includes('application/json')) {
                        return response.text().then(text => {
                            try {
                                const data = JSON.parse(text);
                                return { type: 'json', data, error: false };
                            } catch (e) {
                                console.warn('Failed to parse JSON, treating as HTML:', e);
                                return { type: 'html', data: text, error: false };
                            }
                        });
                    } else {
                        return response.text().then(html => ({ type: 'html', data: html, error: false }));
                    }
                })
                .then(result => {
                    // Handle errors first
                    if (result.error) {
                        if (result.type === 'json' && result.data.message) {
                            if (typeof window.showErrorMessage === 'function') {
                                window.showErrorMessage(result.data.message);
                            } else if (typeof window.errorSnackbar === 'function') {
                                window.errorSnackbar(result.data.message);
                            }
                        } else {
                            if (typeof window.showErrorMessage === 'function') {
                                window.showErrorMessage('Error saving settings. Please check the form and try again.');
                            } else if (typeof window.errorSnackbar === 'function') {
                                window.errorSnackbar('Error saving settings. Please check the form and try again.');
                            }
                        }
                        
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalButtonText;
                        }
                        return;
                    }
                    
                    if (result.type === 'json') {
                        if (result.data.status || result.data.message) {
                            if (typeof window.showSuccessMessage === 'function') {
                                window.showSuccessMessage(result.data.message || 'Settings saved successfully!');
                            } else if (typeof window.successSnackbar === 'function') {
                                window.successSnackbar(result.data.message || 'Settings saved successfully!');
                            }
                            
                            const currentUrl = window.location.href;
                            const mainContentArea = document.querySelector('.offcanvas-body .card-body');
                            if (mainContentArea && typeof window.reloadSettingsContent === 'function') {
                                window.reloadSettingsContent(currentUrl, mainContentArea);
                            }
                        }
                    } else {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(result.data, 'text/html');
                        const mainContentArea = document.querySelector('.offcanvas-body .card-body');
                        
                        const offcanvasBody = doc.querySelector('.offcanvas-body');
                        let settingsContent = null;
                        if (offcanvasBody) {
                            const cardBody = offcanvasBody.querySelector('.card-body');
                            if (cardBody) {
                                settingsContent = cardBody;
                            }
                        }
                        
                        if (!settingsContent) {
                            settingsContent = doc.querySelector('.offcanvas-body .card-body') || 
                                           doc.querySelector('.card-body form') ||
                                           doc.querySelector('form#form-submit') ||
                                           doc.querySelector('form') ||
                                           doc.querySelector('.card-body');
                        }
                        
                        if (settingsContent && mainContentArea) {
                            const scripts = Array.from(settingsContent.querySelectorAll('script'));
                            const scriptsData = scripts.map(script => ({
                                html: script.outerHTML,
                                src: script.src,
                                type: script.type,
                                content: script.innerHTML
                            }));
                            
                            mainContentArea.innerHTML = settingsContent.innerHTML;
                            
                            setTimeout(function() {
                                scriptsData.forEach(function(scriptData) {
                                    if (scriptData.src) {
                                        const newScript = document.createElement('script');
                                        newScript.src = scriptData.src;
                                        if (scriptData.type) newScript.type = scriptData.type;
                                        mainContentArea.appendChild(newScript);
                                    } else if (scriptData.content) {
                                        const newScript = document.createElement('script');
                                        if (scriptData.type) newScript.type = scriptData.type;
                                        newScript.textContent = scriptData.content;
                                        mainContentArea.appendChild(newScript);
                                    }
                                });
                                
                                window.dispatchEvent(new Event('DOMContentLoaded'));
                            }, 100);
                        }
                        
                        if (typeof window.showSuccessMessage === 'function') {
                            window.showSuccessMessage('Settings saved successfully!');
                        } else if (typeof window.successSnackbar === 'function') {
                            window.successSnackbar('Settings saved successfully!');
                        }
                    }
                    
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                })
                .catch(error => {
                    console.error('Error submitting form:', error);
                    
                    if (typeof window.showErrorMessage === 'function') {
                        window.showErrorMessage('Error saving settings. Please try again.');
                    } else if (typeof window.errorSnackbar === 'function') {
                        window.errorSnackbar('Error saving settings. Please try again.');
                    }
                    
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                });
                
                return false;
            });
        });
    </script>
@endpush
