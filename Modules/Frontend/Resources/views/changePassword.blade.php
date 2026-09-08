@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.change_password') }}
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
                                <h6 class="mb-3">{{ __('frontend.update_password') }}</h6>
                            <form id="update-password-form" method="POST" novalidate>
                                @csrf
                                <div class="mb-3">
                                    <div class="input-group custom-input-group mb-0">
                                        <input type="password" name="old_password" class="form-control"
                                            id="old_password" placeholder="{{ __('frontend.old_password') }}"
                                            required>
                                        <span class="input-group-text-1">
                                            <i class="ph ph-eye-slash" id="toggleOldPassword"></i>
                                        </span>
                                    </div>
                                    <div id="error-old-password" class="invalid-feedback mt-1" style="display: none;">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="input-group custom-input-group mb-0">
                                        <input type="password" name="new_password" class="form-control"
                                            id="new_password" placeholder="{{ __('frontend.new_password') }}"
                                            required>
                                        <span class="input-group-text-1">
                                            <i class="ph ph-eye-slash" id="toggleNewPassword"></i>
                                        </span>
                                    </div>
                                    <div id="error-new-password" class="invalid-feedback mt-1" style="display: none;">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="input-group custom-input-group mb-0">
                                        <input type="password" name="new_password_confirmation" class="form-control"
                                            id="new_password_confirmation"
                                            placeholder="{{ __('frontend.confirm_password') }}" required>
                                        <span class="input-group-text-1">
                                            <i class="ph ph-eye-slash" id="toggleConfirmPassword"></i>
                                        </span>
                                    </div>
                                    <div id="error-confirm-password" class="invalid-feedback mt-1" style="display: none;">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-md-end mt-5">
                                    <button type="submit"
                                        class="btn btn-primary">{{ __('frontend.update') }}</button>
                                </div>
                            </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            function togglePasswordVisibility(inputId, toggleId) {
                const passwordInput = document.getElementById(inputId);
                const toggleIcon = document.getElementById(toggleId);

                if (passwordInput && toggleIcon) {
                    toggleIcon.addEventListener('click', function() {
                        // Toggle password visibility
                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            toggleIcon.classList.add('ph-eye');
                            toggleIcon.classList.remove('ph-eye-slash');
                        } else {
                            passwordInput.type = 'password';
                            toggleIcon.classList.add('ph-eye-slash');
                            toggleIcon.classList.remove('ph-eye');
                        }
                    });
                }
            }

            const messages = {
                logout_all_title: '{{ __('messages.logout_all_title') }}',
                logout_all_text: '{{ __('messages.logout_all_text') }}',
                logout_all_button: '{{ __('messages.logout_all_button') }}',
                password_updated_successfully: '{{ __('messages.password_updated_successfully') }}',
            };

            // Initialize toggle functionality for all password fields
            togglePasswordVisibility('old_password', 'toggleOldPassword');
            togglePasswordVisibility('new_password', 'toggleNewPassword');
            togglePasswordVisibility('new_password_confirmation', 'toggleConfirmPassword');

            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('new_password_confirmation');
            const confirmPasswordError = document.getElementById('error-confirm-password');

            // Function to check password match
            function checkPasswordMatch() {
                if (confirmPassword.value) {
                    if (confirmPassword.value !== newPassword.value) {
                        confirmPassword.classList.add('is-invalid');
                        confirmPasswordError.textContent = '{{ __('messages.passwords_do_not_match') }}';
                        confirmPasswordError.style.display = 'block';
                        return false;
                    } else {
                        confirmPassword.classList.remove('is-invalid');
                        confirmPasswordError.style.display = 'none';
                        return true;
                    }
                }
                return true;
            }

            // Real-time validation for confirm password
            confirmPassword.addEventListener('input', function() {
                checkPasswordMatch();
                // Also show password validation if user types in confirm field
                if (newPassword.value) {
                    showPasswordValidation(newPassword.value);
                }
            });
            // Also check when new password changes
            newPassword.addEventListener('input', function() {
                if (confirmPassword.value) {
                    checkPasswordMatch();
                }
                // Show real-time password validation message
                showPasswordValidation(this.value);
            });

            function showPasswordValidation(password) {
                const errorElement = document.getElementById('error-new-password');
                if (!password) {
                    errorElement.style.display = 'none';
                    return;
                }

                if (!validatePassword(password)) {
                    errorElement.textContent = getPasswordErrorMessage(password);
                    errorElement.style.display = 'block';
                } else {
                    errorElement.style.display = 'none';
                }
            }

            function validatePassword(password) {
                const hasLength = password.length >= 8 && password.length <= 12;
                const hasUppercase = /[A-Z]/.test(password);
                const hasLowercase = /[a-z]/.test(password);
                const hasNumber = /[0-9]/.test(password);
                const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

                return hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;
            }

            function getPasswordErrorMessage(password) {
                if (!password) return '{{ __('messages.password_required') }}';

                const errors = [];
                if (password.length < 8) errors.push('{{ __('messages.password_min_length') }}');
                if (password.length > 12) errors.push('{{ __('messages.password_max_length') }}');
                if (!/[A-Z]/.test(password)) errors.push('{{ __('messages.password_uppercase') }}');
                if (!/[a-z]/.test(password)) errors.push('{{ __('messages.password_lowercase') }}');
                if (!/[0-9]/.test(password)) errors.push('{{ __('messages.password_number') }}');
                if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) errors.push('{{ __('messages.password_special') }}');

                return errors.join(', ');
            }

            // Form submit handler
            document.getElementById('update-password-form').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = e.target;
                const formData = new FormData(form);
                const submitButton = this.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.textContent;

                // Clear previous errors
                const errors = document.querySelectorAll('.text-danger');
                errors.forEach(error => error.style.display = 'none');
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

                let isValid = true;

                // Validate old password
                const oldPassword = document.getElementById('old_password');
                if (!oldPassword.value.trim()) {
                    document.getElementById('error-old-password').textContent =
                        '{{ __('messages.old_password_required') }}';
                    document.getElementById('error-old-password').style.display = 'block';
                    oldPassword.classList.add('is-invalid');
                    isValid = false;
                }

                // Validate new password
                if (!newPassword.value.trim()) {
                    document.getElementById('error-new-password').textContent =
                        '{{ __('messages.new_password_required') }}';
                    document.getElementById('error-new-password').style.display = 'block';
                    newPassword.classList.add('is-invalid');
                    isValid = false;
                } else if (!validatePassword(newPassword.value)) {
                    document.getElementById('error-new-password').textContent = getPasswordErrorMessage(
                        newPassword.value);
                    document.getElementById('error-new-password').style.display = 'block';
                    newPassword.classList.add('is-invalid');
                    isValid = false;
                }

                // Validate confirm password
                if (!confirmPassword.value.trim()) {
                    confirmPasswordError.textContent = '{{ __('messages.confirm_password_required') }}';
                    confirmPasswordError.style.display = 'block';
                    confirmPassword.classList.add('is-invalid');
                    isValid = false;
                } else if (!checkPasswordMatch()) {
                    isValid = false;
                }

                if (!isValid) {
                    return;
                }

                // Show loading state
                submitButton.disabled = true;
                submitButton.textContent = 'Updating...';

                // Clear previous errors before submission
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.text-danger').forEach(el => {
                    el.style.display = 'none';
                    el.textContent = '';
                });

                const logoutMessage = '{{ __('messages.after_password_update_logout_message') }}';

                fetch("{{ route('account.password.update') }}", {
                        method: "POST",
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData,
                    })
                    .then(response => {
                        return response.json().then(data => ({
                            status: response.status,
                            data: data
                        }));
                    })
                    .then(({ status, data }) => {
                        if (data.success) {
                            window.successSnackbar(messages.password_updated_successfully);

                            // Clear all validation errors
                            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                            document.querySelectorAll('.text-danger').forEach(el => {
                                el.style.display = 'none';
                                el.textContent = '';
                            });

                            // Reset password visibility toggles
                            document.getElementById('toggleOldPassword').classList.remove('ph-eye');
                            document.getElementById('toggleOldPassword').classList.add('ph-eye-slash');
                            document.getElementById('toggleNewPassword').classList.remove('ph-eye');
                            document.getElementById('toggleNewPassword').classList.add('ph-eye-slash');
                            document.getElementById('toggleConfirmPassword').classList.remove('ph-eye');
                            document.getElementById('toggleConfirmPassword').classList.add('ph-eye-slash');

                            // Reset password input types
                            oldPassword.type = 'password';
                            newPassword.type = 'password';
                            confirmPassword.type = 'password';

                            // Reset form
                            form.reset();

                            // Call logout API and redirect to login with message
                            const baseUrl = document.querySelector('meta[name="baseUrl"]')?.getAttribute('content') || '';
                            fetch(baseUrl + '/api/logout-all-data', {
                                    method: 'GET',
                                    credentials: 'same-origin'
                                })
                                .then(function() {
                                    const loginUrl = baseUrl + '/login';
                                    localStorage.setItem('logout_message', logoutMessage);
                                    window.location.href = loginUrl;
                                })
                                .catch(function(error) {
                                    console.error('Error:', error);
                                    // Still redirect to login even if API call fails
                                    const loginUrl = baseUrl + '/login';
                                    localStorage.setItem('logout_message', logoutMessage);
                                    window.location.href = loginUrl;
                                });
                        } else {
                            // Clear previous errors first
                            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                            document.querySelectorAll('.text-danger').forEach(el => {
                                el.style.display = 'none';
                                el.textContent = '';
                            });

                            // Display server-side validation errors in form fields
                            if (data.errors) {
                                if (data.errors.old_password) {
                                    const errorMsg = Array.isArray(data.errors.old_password)
                                        ? data.errors.old_password[0]
                                        : data.errors.old_password;
                                    document.getElementById('error-old-password').textContent = errorMsg;
                                    document.getElementById('error-old-password').style.display = 'block';
                                    oldPassword.classList.add('is-invalid');
                                }

                                if (data.errors.new_password) {
                                    const errorMsg = Array.isArray(data.errors.new_password)
                                        ? data.errors.new_password[0]
                                        : data.errors.new_password;
                                    document.getElementById('error-new-password').textContent = errorMsg;
                                    document.getElementById('error-new-password').style.display = 'block';
                                    newPassword.classList.add('is-invalid');
                                }

                                if (data.errors.new_password_confirmation) {
                                    const errorMsg = Array.isArray(data.errors.new_password_confirmation)
                                        ? data.errors.new_password_confirmation[0]
                                        : data.errors.new_password_confirmation;
                                    document.getElementById('error-confirm-password').textContent = errorMsg;
                                    document.getElementById('error-confirm-password').style.display = 'block';
                                    confirmPassword.classList.add('is-invalid');
                                }
                            } else if (data.message) {
                                // Fix: Use errorSnackbar for errors
                                if (window.errorSnackbar) {
                                    window.errorSnackbar(data.message);
                                } else if (window.successSnackbar) {
                                    window.successSnackbar(data.message);
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Fix: Use errorSnackbar for errors
                        const errorMsg = 'An error occurred while updating password';
                        if (window.errorSnackbar) {
                            window.errorSnackbar(errorMsg);
                        } else if (window.successSnackbar) {
                            window.successSnackbar(errorMsg);
                        }
                    })
                    .finally(() => {
                        // Reset button state
                        submitButton.disabled = false;
                        submitButton.textContent = originalButtonText;
                    });
            });
        });
    </script>
@endsection
