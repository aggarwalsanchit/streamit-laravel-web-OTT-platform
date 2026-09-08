@extends('setting::backend.profile.profile-layout')

@section('profile-content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-key"></i> {{ __('messages.change_password') }}</h2>
    </div>

    <form method="POST" action="{{ route('backend.profile.change_password') }}" class="requires-validation" novalidate id="form-submit">
        @csrf

        <div class="form-group">
            <label class="form-label" for="old_password">{{ __('users.lbl_old_password') }}</label>
            <input type="password" class="form-control @error('old_password') is-invalid @enderror" id="old_password" name="old_password" placeholder="{{__('messages.enter_old_password')}}" required>
            @error('old_password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
            <div class="invalid-feedback" id="old-pass-error">Old password field is required</div>
        </div>

        <div class="form-group">
            <label class="form-label" for="new_password">{{ __('messages.lbl_new_password') }}</label>
            <input type="password" class="form-control @error('new_password') is-invalid @enderror" id="new_password" name="new_password" placeholder="{{__('messages.enter_new_password')}}" required>
            @error('new_password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
            <div class="text-danger small mt-1" id="new-pass-error" style="display: none;">New password field is required</div>
         
        </div>

        <div class="form-group">
            <label class="form-label" for="confirm_new_password">{{ __('users.lbl_confirm_new_password') }}</label>
            <input type="password" class="form-control @error('confirm_new_password') is-invalid @enderror" id="confirm_new_password" name="confirm_new_password" placeholder="{{__('messages.enter_confirm_password')}}" required>
            @error('confirm_new_password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
            <div class="invalid-feedback" id="confirm-pass-error">Confirm password field is required</div>
            <div class="invalid-feedback d-none " id="confirm-pass-match-error" >Confirm password not match.</div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-primary" id="submit-button" disabled>
                {{ __('dashboard.lbl_submit') }}
            </button>
        </div>
    </form>
</div>

<script>
    const newPasswordField = document.getElementById('new_password');
    const confirmPasswordField = document.getElementById('confirm_new_password');
    const submitButton = document.getElementById('submit-button');
    const confirmError = document.getElementById('confirm-pass-error');
    const confirmMatchError = document.getElementById('confirm-pass-match-error');
    const newPassError = document.getElementById('new-pass-error');
    const originalButtonText = submitButton ? submitButton.innerHTML : '';
    
    // Password validation regex
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_+\-=\[\]{};':"\\|,.<>\/])[A-Za-z\d@$!%*?&#^()_+\-=\[\]{};':"\\|,.<>\/]{8,14}$/;
    
    // Validate new password format
    function validateNewPassword() {
        const newPassword = newPasswordField.value;
        
        // Clear previous error styling
        newPasswordField.classList.remove('is-valid', 'is-invalid');
        
        if (newPassword.length === 0) {
            newPassError.textContent = 'New password field is required';
            newPassError.style.display = 'block';
            newPasswordField.classList.add('is-invalid');
            return false;
        } else if (newPassword.length < 8) {
            newPassError.textContent = 'Password must be at least 8 characters long.';
            newPassError.style.display = 'block';
            newPasswordField.classList.add('is-invalid');
            return false;
        } else if (newPassword.length > 14) {
            newPassError.textContent = 'Password must not exceed 14 characters.';
            newPassError.style.display = 'block';
            newPasswordField.classList.add('is-invalid');
            return false;
        } else if (!passwordRegex.test(newPassword)) {
            newPassError.textContent = 'Password must be 8-14 characters with at least one uppercase, one lowercase, one digit, and one special character.';
            newPassError.style.display = 'block';
            newPasswordField.classList.add('is-invalid');
            return false;
        } else {
            newPassError.textContent = '';
            newPassError.style.display = 'none';
            newPasswordField.classList.remove('is-invalid');
            newPasswordField.classList.add('is-valid');
            return true;
        }
    }
    
    function validatePasswords() {
        const newPassword = newPasswordField.value;
        const confirmPassword = confirmPasswordField.value;
        let isValid = true;

        // Validate new password format first
        const isNewPasswordValid = validateNewPassword();
        if (!isNewPasswordValid) {
            isValid = false;
        }

        // Validate password match - show only one error at a time
        if (confirmPassword === '') {
            // Field is empty - show required error only
            confirmError.style.display = 'block';
            confirmMatchError.style.display = 'none';
            confirmMatchError.classList.add('d-none');
            confirmPasswordField.classList.remove('is-valid');
            confirmPasswordField.classList.add('is-invalid');
            isValid = false;
        } else if (newPassword && confirmPassword) {
            // Field has value - check if passwords match
            if (newPassword === confirmPassword) {
                // Passwords match - hide both errors
                confirmError.style.display = 'none';
                confirmMatchError.style.display = 'none';
                confirmMatchError.classList.add('d-none');
                confirmPasswordField.classList.remove('is-invalid');
                confirmPasswordField.classList.add('is-valid');
            } else {
                // Passwords don't match - show match error only
                confirmError.style.display = 'none';
                confirmMatchError.style.display = 'block';
                confirmMatchError.classList.remove('d-none');
                confirmPasswordField.classList.remove('is-valid');
                confirmPasswordField.classList.add('is-invalid');
                isValid = false;
            }
        } else {
            // New password is empty but confirm password has value
            confirmError.style.display = 'none';
            confirmMatchError.style.display = 'none';
            confirmMatchError.classList.add('d-none');
        }

        // Enable/disable submit button based on validation
        if (isValid && newPassword && confirmPassword && newPassword === confirmPassword && passwordRegex.test(newPassword)) {
            submitButton.disabled = false;
        } else {
            submitButton.disabled = true;
        }
        
        return isValid;
    }

    // Add oninput event listeners to both fields
    newPasswordField.addEventListener('input', function() {
        validateNewPassword();
        validatePasswords();
    });
    
    confirmPasswordField.addEventListener('input', validatePasswords);
    
    // Validate on form submit
    const form = document.getElementById('form-submit');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validatePasswords()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                // Reset submit button if it was changed to loading
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = originalButtonText;
                }
                return false;
            }
        }, true); // Use capture phase to run before global handler
    }
</script>

@endsection



