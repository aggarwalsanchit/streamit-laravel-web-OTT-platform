@extends('setting::backend.setting.index')
@section('title')
    {{ __('setting_sidebar.lbl_custom_code') }}
@endsection

@section('settings-content')
    <form method="POST" action="{{ route('backend.setting.store') }}" id="form-submit" class="requires-validation" novalidate
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="setting_tab" value="custom_code">
        <div>
            <div class="d-flex justify-content-between align-items-center card-title">
                <h3 class="mb-3">
                    <i class="fa-solid fa-file-code"></i> {{ __('setting_sidebar.lbl_custom_code') }}
                    <i class="ph ph-info text-primary cursor-pointer ms-2" 
                       data-bs-toggle="tooltip" 
                       data-bs-placement="top"
                       title="{{ __('setting_custom_code.lbl_css_tooltip') }}"></i>
                </h3>
            </div>
        </div>
        <div class="form-group">

            <label for="custom_css_block" class="form-label">{{ __('setting_custom_code.lbl_css_name') }} </label>
            {{ html()->textarea('custom_css_block')->class('form-control' . ($errors->has('custom_css_block') ? ' is-invalid' : ''))->value($data['custom_css_block'] ?? old('custom_css_block'))->placeholder(__('setting_custom_code.lbl_css_name'))->rows('5') }}
            @error('custom_css_block')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
        <div class="form-group">
            <label for="custom_js_block" class="form-label">{{ __('setting_custom_code.lbl_js_name') }} </label>
            {{ html()->textarea('custom_js_block')->class('form-control' . ($errors->has('custom_js_block') ? ' is-invalid' : ''))->value($data['custom_js_block'] ?? old('custom_js_block'))->placeholder(__('setting_custom_code.lbl_js_name'))->rows('5') }}
            @error('custom_js_block')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
        <div class="form-group text-end">
            <button type="submit" id="submit-button" class="btn btn-primary">{{ __('messages.save') }}</button>
        </div>
    </form>
@endsection

@push('after-scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        var form = document.getElementById('form-submit');
        var submitBtn = document.getElementById('submit-button');

        // Submit via AJAX so demo permission errors show as toast instead of modal
        if (form && submitBtn) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '{{ __("messages.loading") }}...';

                var formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function (response) {
                    return response.text().then(function (text) {
                        var data = {};
                        try { data = text ? JSON.parse(text) : {}; } catch (err) {}
                        return { ok: response.ok, status: response.status, data: data };
                    });
                })
                .then(function (result) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;

                    // Session expired / logged out – redirect to login
                    if (result.status === 419 && result.data.redirect) {
                        if (typeof window.errorSnackbar === 'function') {
                            window.errorSnackbar(result.data.message || '{{ __("messages.session_expired_please_login") }}');
                        }
                        window.location.href = result.data.redirect;
                        return;
                    }

                    if (result.ok && result.data.status) {
                        if (typeof window.successSnackbar === 'function') {
                            window.successSnackbar(result.data.message || '{{ __("messages.updated_successfully") }}');
                        }

                        var mainContentArea = document.querySelector('.offcanvas-body .card-body');
                        if (mainContentArea && typeof window.reloadSettingsContent === 'function') {
                            window.reloadSettingsContent(window.location.href, mainContentArea);
                        }
                    } else {
                        var msg = (result.status === 403 && result.data.message)
                            ? result.data.message
                            : (result.data.message || '{{ __("messages.error_occurred") }}');

                        if (typeof window.errorSnackbar === 'function') {
                            window.errorSnackbar(msg);
                        }
                    }
                })
                .catch(function () {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;

                    if (typeof window.errorSnackbar === 'function') {
                        window.errorSnackbar('{{ __("messages.user_does_not_have_permission_to_change_password") }}');
                    }
                });

                return false;
            });
        }
    });
</script>
@endpush

