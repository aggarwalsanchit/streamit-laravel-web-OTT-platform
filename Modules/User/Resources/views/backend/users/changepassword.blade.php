@extends('backend.layouts.app')
@section('title')
    {{ __($module_title) }}
@endsection
@section('content')
    <x-back-button-component route="backend.users.index" />

    {{ html()->form('POST', route('backend.users.update_password', $id))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->class('requires-validation')->attribute('novalidate', 'novalidate')->open() }}
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-6 col-lg-4">
                    <label for="old_password" class="form-label">{{ __('users.lbl_old_password') }}<span
                            class="text-danger">*</span></label>
                    <input type="password" class="form-control" value="{{ old('old_password', $data->old_password ?? '') }}"
                        name="old_password" id="old_password" placeholder="{{ __('messages.enter_old_password') }}"
                        required>
                    <div class="invalid-feedback" id="name-error">Old password field is required</div>
                    @error('old_password')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6 col-lg-4">
                    <label for="password" class="form-label">{{ __('users.lbl_new_password') }}<span
                            class="text-danger">*</span></label>
                    <input type="password" class="form-control" value="{{ old('password', $data->password ?? '') }}"
                        name="password" id="password" placeholder="{{ __('messages.enter_new_password') }}" required>
                    <div class="text-danger small mt-1" id="password-error" style="display: none;">New Password field is required</div>
                    @error('password')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                  
                </div>
                <div class="col-md-6 col-lg-4">
                    <label for="password_confirmation" class="form-label">{{ __('users.lbl_confirm_password') }}<span
                            class="text-danger">*</span></label>
                    <input type="password" class="form-control"
                        value="{{ old('password_confirmation', $data->password_confirmation ?? '') }}"
                        name="password_confirmation" id="password_confirmation"
                        placeholder="{{ __('messages.enter_confirm_password') }}" required>
                    <div class="text-danger small mt-1" id="confirm-password-error" style="display: none;">Confirm Password field is required</div>
                    <div class="text-danger small mt-1" id="confirm-password-match-error" style="display: none;">Passwords do not match</div>
                    @error('password_confirmation')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mb-5">
        {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}
    </div>

    {{ html()->form()->close() }}
@endsection

@push('after-scripts')
<script>
    // Password validation - must run before global form handler
    const passwordInput = document.getElementById('password');
    const passwordConfirmationInput = document.getElementById('password_confirmation');
    const passwordError = document.getElementById('password-error');
    const confirmPasswordError = document.getElementById('confirm-password-error');
    const confirmPasswordMatchError = document.getElementById('confirm-password-match-error');
    const submitButton = document.getElementById('submit-button');
    const originalButtonText = submitButton ? submitButton.innerHTML : '';
    
    if (passwordInput && passwordConfirmationInput) {
        // Password validation regex
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_+\-=\[\]{};':"\\|,.<>\/])[A-Za-z\d@$!%*?&#^()_+\-=\[\]{};':"\\|,.<>\/]{8,14}$/;
        
        // Validate password format
        function validatePassword() {
            const password = passwordInput.value;
            
            // Clear previous error styling
            passwordInput.classList.remove('is-valid', 'is-invalid');
            
            if (password.length === 0) {
                passwordError.textContent = 'New Password field is required';
                passwordError.style.display = 'block';
                passwordInput.classList.add('is-invalid');
                return false;
            } else if (password.length < 8) {
                passwordError.textContent = 'Password must be at least 8 characters long.';
                passwordError.style.display = 'block';
                passwordInput.classList.add('is-invalid');
                return false;
            } else if (password.length > 14) {
                passwordError.textContent = 'Password must not exceed 14 characters.';
                passwordError.style.display = 'block';
                passwordInput.classList.add('is-invalid');
                return false;
            } else if (!passwordRegex.test(password)) {
                passwordError.textContent = 'Password must be 8-14 characters with at least one uppercase, one lowercase, one digit, and one special character.';
                passwordError.style.display = 'block';
                passwordInput.classList.add('is-invalid');
                return false;
            } else {
                passwordError.textContent = '';
                passwordError.style.display = 'none';
                passwordInput.classList.remove('is-invalid');
                passwordInput.classList.add('is-valid');
                return true;
            }
        }
        
        // Validate password match
        function validatePasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = passwordConfirmationInput.value;
            
            // Clear previous error styling
            passwordConfirmationInput.classList.remove('is-valid', 'is-invalid');
            confirmPasswordError.style.display = 'none';
            confirmPasswordMatchError.style.display = 'none';
            
            // Check if confirm password is empty
            if (confirmPassword.length === 0) {
                confirmPasswordError.textContent = 'Confirm Password field is required';
                confirmPasswordError.style.display = 'block';
                passwordConfirmationInput.classList.add('is-invalid');
                return false;
            }
            
            // Check if passwords match
            if (password && confirmPassword) {
                if (password !== confirmPassword) {
                    confirmPasswordMatchError.textContent = 'Passwords do not match';
                    confirmPasswordMatchError.style.display = 'block';
                    passwordConfirmationInput.classList.add('is-invalid');
                    return false;
                } else {
                    passwordConfirmationInput.classList.remove('is-invalid');
                    passwordConfirmationInput.classList.add('is-valid');
                    return true;
                }
            }
            
            return true;
        }
        
        // Real-time validation as user types
        passwordInput.addEventListener('input', function() {
            validatePassword();
            if (passwordConfirmationInput.value.length > 0) {
                validatePasswordMatch();
            }
        });
        
        passwordConfirmationInput.addEventListener('input', function() {
            validatePasswordMatch();
        });
        
        // Validate on form submit - use capture phase to run before other handlers
        const form = document.getElementById('form-submit');
        if (form) {
            form.addEventListener('submit', function(e) {
                const isPasswordValid = validatePassword();
                const isMatchValid = validatePasswordMatch();
                
                if (!isPasswordValid || !isMatchValid) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    // Focus on the first invalid field
                    if (!isPasswordValid) {
                        passwordInput.focus();
                    } else if (!isMatchValid) {
                        passwordConfirmationInput.focus();
                    }
                    // Reset submit button if it was changed to loading
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    }
                    return false;
                }
            }, true); // Use capture phase
        }
    }
</script>
@endpush
