@extends('setting::backend.setting.index')

@section('title')
    {{ __('setting_sidebar.lbl_mail') }}
@endsection

@section('settings-content')
    <div class="mb-3">
        <h3><i class="fas fa-envelope"></i> {{ __('setting_sidebar.lbl_mail') }} </h3>
    </div>
    <form method="POST" action="{{ route('backend.setting.store') }}" id="form-submit" data-custom-handler="true">
        @csrf
        <input type="hidden" name="setting_tab" value="mail">
        <div class="row">
            <div class="form-group col-md-6">
                <label class="form-label">{{ __('setting_mail_page.lbl_email') }} <span class="text-danger">*</span></label>
                {!! html()->email('email')->class('form-control')->placeholder('info@example.com')->value(old('email', $data['email'] ?? ''))->id('email') !!}
                @error('email')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <div class="invalid-feedback" id="email-error"></div>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label">{{ __('setting_mail_page.lbl_driver') }} <span
                        class="text-danger">*</span></label>
                {!! html()->text('mail_driver')->class('form-control')->placeholder('smtp')->value(old('mail_driver', $data['mail_driver'] ?? ''))->id('mail_driver') !!}
                @error('mail_driver')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <div class="invalid-feedback" id="mail_driver-error"></div>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label">{{ __('setting_mail_page.lbl_host') }} <span class="text-danger">*</span></label>
                {!! html()->text('mail_host')->class('form-control')->placeholder('smtp.gmail.com')->value(old('mail_host', $data['mail_host'] ?? ''))->id('mail_host') !!}
                @error('mail_host')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <div class="invalid-feedback" id="mail_host-error"></div>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label">{{ __('setting_mail_page.lbl_port') }} <span class="text-danger">*</span></label>
                {!! html()->number('mail_port')->class('form-control')->placeholder('587')->value(old('mail_port', $data['mail_port'] ?? ''))->id('mail_port') !!}
                @error('mail_port')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <div class="invalid-feedback" id="mail_port-error"></div>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label">{{ __('setting_mail_page.lbl_encryption') }} <span
                        class="text-danger">*</span></label>
                {!! html()->text('mail_encryption')->class('form-control')->placeholder('tls')->value(old('mail_encryption', $data['mail_encryption'] ?? ''))->id('mail_encryption') !!}
                @error('mail_encryption')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <div class="invalid-feedback" id="mail_encryption-error"></div>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label">{{ __('setting_mail_page.lbl_username') }} <span
                        class="text-danger">*</span></label>
                {!! html()->text('mail_username')->class('form-control')->placeholder('youremail@gmail.com')->value(old('mail_username', $data['mail_username'] ?? ''))->id('mail_username') !!}
                @error('mail_username')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <div class="invalid-feedback" id="mail_username-error"></div>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label">{{ __('setting_mail_page.lbl_password') }} <span
                        class="text-danger">*</span></label>
                {!! html()->password('mail_password')->class('form-control')->placeholder('Password')->id('mail_password') !!}
                @error('mail_password')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <div class="invalid-feedback" id="mail_password-error"></div>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label">{{ __('setting_mail_page.lbl_mail') }} <span class="text-danger">*</span></label>
                {!! html()->email('mail_from')->class('form-control')->placeholder('youremail@gmail.com')->value(old('mail_from', $data['mail_from'] ?? ''))->id('mail_from') !!}
                @error('mail_from')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <div class="invalid-feedback" id="mail_from-error"></div>
            </div>
            <div class="form-group col-md-6">
                <label class="form-label">{{ __('setting_mail_page.lbl_from_name') }} <span
                        class="text-danger">*</span></label>
                {!! html()->text('from_name')->class('form-control')->placeholder('Streamit-Laravel')->value(old('from_name', $data['from_name'] ?? ''))->id('from_name') !!}
                @error('from_name')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                <div class="invalid-feedback" id="from_name-error"></div>
            </div>
        </div>
        <div class="form-group text-end">
            <button type="submit" id="submit-button" class="btn btn-primary float-right">
                {{ __('messages.save') }}
            </button>
        </div>
    </form>
@endsection

@push('after-scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-submit');
    const submitButton = document.getElementById('submit-button');
    if (!form || !submitButton) return;

    // Store original button text
    const originalButtonText = submitButton.textContent || submitButton.innerText || '{{ __('messages.save') }}';
    let formSubmitted = false;
    
    // Reset button state on page load (in case it was stuck from previous attempt)
    submitButton.disabled = false;
    submitButton.textContent = originalButtonText;
    submitButton.innerText = originalButtonText;
    submitButton.innerHTML = originalButtonText;

    // Email validation regex
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    // Field labels for error messages
    const fieldLabels = {
        'email': '{{ __('setting_mail_page.lbl_email') }}',
        'mail_driver': '{{ __('setting_mail_page.lbl_driver') }}',
        'mail_host': '{{ __('setting_mail_page.lbl_host') }}',
        'mail_port': '{{ __('setting_mail_page.lbl_port') }}',
        'mail_encryption': '{{ __('setting_mail_page.lbl_encryption') }}',
        'mail_username': '{{ __('setting_mail_page.lbl_username') }}',
        'mail_password': '{{ __('setting_mail_page.lbl_password') }}',
        'mail_from': '{{ __('setting_mail_page.lbl_mail') }}',
        'from_name': '{{ __('setting_mail_page.lbl_from_name') }}'
    };

    // Prevent button click from triggering global handlers
    submitButton.addEventListener('click', function(e) {
        // Don't prevent default, but ensure our form handler runs first
        // The form submit event will handle validation
    }, true);

    // Clear validation errors when user starts typing
    const requiredFieldIds = ['email', 'mail_driver', 'mail_host', 'mail_port', 'mail_encryption', 'mail_username', 'mail_password', 'mail_from', 'from_name'];
    requiredFieldIds.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('is-invalid');
                    const errorElement = document.getElementById(fieldId + '-error');
                    if (errorElement) {
                        errorElement.textContent = '';
                        errorElement.style.display = 'none';
                    }
                }
            });
            field.addEventListener('blur', function() {
                validateField(fieldId);
            });
        }
    });

    // Form submission validation handler
    function handleFormSubmit(e) {
        // If form is already being submitted, allow it
        if (formSubmitted) {
            return true;
        }

        // Prevent default and stop propagation to prevent global handlers
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        let hasErrors = false;
        
        // Clear previous errors
        requiredFieldIds.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            const errorElement = document.getElementById(fieldId + '-error');
            if (field) {
                field.classList.remove('is-invalid');
            }
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }
        });

        // Validate all required fields
        requiredFieldIds.forEach(fieldId => {
            if (!validateField(fieldId)) {
                hasErrors = true;
            }
        });

        if (hasErrors) {
            // Reset button state if validation fails
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
            submitButton.innerText = originalButtonText;
            submitButton.innerHTML = originalButtonText;
            formSubmitted = false;
            return false;
        }

        // Only show loading state if validation passes
        submitButton.disabled = true;
        const loadingText = '{{ __('messages.loading') }}...';
        submitButton.textContent = loadingText;
        submitButton.innerText = loadingText;
        submitButton.innerHTML = loadingText;
        formSubmitted = true;
        
        // Submit via AJAX instead of normal form submission
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
            
            // Check if response is OK
            if (!response.ok) {
                if (contentType.includes('application/json')) {
                    return response.json().then(data => ({ type: 'json', data, error: true }));
                } else {
                    return response.text().then(html => ({ type: 'html', data: html, error: true }));
                }
            }
            
            // Handle successful responses
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
                
                // Restore button state
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
                submitButton.innerText = originalButtonText;
                submitButton.innerHTML = originalButtonText;
                formSubmitted = false;
                return;
            }
            
            if (result.type === 'json') {
                // Handle JSON response (success message)
                if (result.data.status || result.data.message) {
                    // Show success message
                    if (typeof window.showSuccessMessage === 'function') {
                        window.showSuccessMessage(result.data.message || 'Settings saved successfully!');
                    } else if (typeof window.successSnackbar === 'function') {
                        window.successSnackbar(result.data.message || 'Settings saved successfully!');
                    }
                    
                    // Reload the current page content
                    const currentUrl = window.location.href;
                    const mainContentArea = document.querySelector('.offcanvas-body .card-body');
                    if (mainContentArea && typeof window.reloadSettingsContent === 'function') {
                        window.reloadSettingsContent(currentUrl, mainContentArea);
                    }
                }
            } else {
                // Handle HTML response - extract and update content
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
            
            // Restore button state
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
            submitButton.innerText = originalButtonText;
            submitButton.innerHTML = originalButtonText;
            formSubmitted = false;
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            
            if (typeof window.showErrorMessage === 'function') {
                window.showErrorMessage('Error saving settings. Please try again.');
            } else if (typeof window.errorSnackbar === 'function') {
                window.errorSnackbar('Error saving settings. Please try again.');
            }
            
            // Restore button state
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
            submitButton.innerText = originalButtonText;
            submitButton.innerHTML = originalButtonText;
            formSubmitted = false;
        });
        
        return false;
    }

    // Use capture phase to run before global handlers
    form.addEventListener('submit', handleFormSubmit, true);

    function validateField(fieldId) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + '-error');
        
        if (!field || !errorElement) return true;

        const value = field.value.trim();
        const fieldLabel = fieldLabels[fieldId] || fieldId;
        let isValid = true;
        let errorMessage = '';

        // Check if field is empty
        if (!value) {
            errorMessage = fieldLabel + ' is required.';
            isValid = false;
        } else {
            // Email validation for email fields
            if (fieldId === 'email' || fieldId === 'mail_from') {
                if (!emailRegex.test(value)) {
                    errorMessage = 'Please enter a valid email address.';
                    isValid = false;
                }
            }
            
            // Port validation (must be a number)
            if (fieldId === 'mail_port') {
                const port = parseInt(value);
                if (isNaN(port) || port < 1 || port > 65535) {
                    errorMessage = 'Please enter a valid port number (1-65535).';
                    isValid = false;
                }
            }
        }

        if (!isValid) {
            field.classList.add('is-invalid');
            errorElement.textContent = errorMessage;
            errorElement.style.display = 'block';
            return false;
        } else {
            field.classList.remove('is-invalid');
            errorElement.textContent = '';
            errorElement.style.display = 'none';
            return true;
        }
    }
});
</script>
@endpush
