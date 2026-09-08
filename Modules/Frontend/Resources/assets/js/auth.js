const t = (key, fb) => (window.localisation && window.localisation[key]) || fb;
// ==========================
// Password toggle handlers
// ==========================
const togglePasswordVisibility = (toggleId, inputId) => {
    const toggle = document.querySelector(`#${toggleId}`);
    const input = document.querySelector(`#${inputId}`);
    if (toggle && input) {
        toggle.addEventListener('click', function () {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            toggle.classList.toggle('ph-eye');
            toggle.classList.toggle('ph-eye-slash');
        });
    }
};

togglePasswordVisibility('togglePassword', 'password');
togglePasswordVisibility('toggleConfirmPassword', 'confirm_password');

// ==========================
// Helpers
// ==========================
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePassword(password) {
    const hasLength = password.length >= 8 && password.length <= 14;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    const hasDigit = /\d/.test(password);

    return hasLength && hasUppercase && hasLowercase && hasSpecial && hasDigit;
}

function showValidationError(input, message) {
    const group = input.closest('.input-group');
    let feedback = group ? group.querySelector('.invalid-feedback') : null;

    if (!feedback && group?.nextElementSibling?.classList.contains('invalid-feedback')) {
        feedback = group.nextElementSibling;
    }

    if (feedback) {
        feedback.textContent = message;
        feedback.style.display = 'block';
    }

    input.classList.add('is-invalid');
}

function clearValidationError(input) {
    const group = input.closest('.input-group');
    let feedback = group ? group.querySelector('.invalid-feedback') : null;

    if (!feedback && group?.nextElementSibling?.classList.contains('invalid-feedback')) {
        feedback = group.nextElementSibling;
    }

    if (feedback) {
        feedback.textContent = '';
        feedback.style.display = 'none';
    }

    input.classList.remove('is-invalid');
}

function toggleButton(isSubmitting, button, submittingText, defaultText) {
    button.textContent = isSubmitting ? submittingText : defaultText;
    button.disabled = isSubmitting;
}

function attachLiveInputClear(form, fields) {
    fields.forEach(fieldName => {
        const input = form.querySelector(`input[name="${fieldName}"]`);
        if (input) {
            input.addEventListener('input', () => clearValidationError(input));
        }
    });
}

// ==========================
// Register Form
// ==========================
const registerForm = document.querySelector('#registerForm');
if (registerForm) {
    const registerButton = document.querySelector('#register-button');
    const errorMessage = document.querySelector('#error_message');
    const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content');

    const firstNameInput = registerForm.querySelector('input[name="first_name"]');
    const lastNameInput = registerForm.querySelector('input[name="last_name"]');

    // Prevent numbers in first name and last name + immediate error + auto-remove numbers
    [firstNameInput, lastNameInput].forEach(input => {
        input.addEventListener('input', () => {
            const numberPattern = /[0-9]/g;
            if (numberPattern.test(input.value)) {
                input.value = input.value.replace(numberPattern, '');
                showValidationError(input, t('name_no_numbers', 'Name field does not allow numbers.'));
            } else if (!input.value.trim()) {
                showValidationError(input, t('field_required', 'This field is required.'));
            } else {
                clearValidationError(input);
            }
        });
    });

    attachLiveInputClear(registerForm, ['first_name', 'last_name', 'email', 'password', 'confirm_password']);

    const passwordInput = registerForm.querySelector('input[name="password"]');
    if (passwordInput) {
        // Prevent copy-paste on password field
        ['copy', 'paste', 'cut'].forEach(event => {
            passwordInput.addEventListener(event, e => e.preventDefault());
        });

        passwordInput.addEventListener('input', function () {
            const password = this.value;
            if (password && !validatePassword(password)) {
                showValidationError(this, t('password_requirements', 'Password length should be 8 to 14 Characters, at least one uppercase, one lowercase, one digit, and one special character'));
            } else if (password && validatePassword(password)) {
                clearValidationError(this);
            }


            const confirmPasswordInput = registerForm.querySelector('input[name="confirm_password"]');
            if (confirmPasswordInput && confirmPasswordInput.value) {
                checkPasswordMatch(confirmPasswordInput, password);
            }
        });

        const confirmPasswordInput = registerForm.querySelector('input[name="confirm_password"]');
        if (confirmPasswordInput) {
            // Prevent copy-paste on confirm password field
            ['copy', 'paste', 'cut'].forEach(event => {
                confirmPasswordInput.addEventListener(event, e => e.preventDefault());
            });

            confirmPasswordInput.addEventListener('input', function () {
                const password = passwordInput.value;
                const confirmPassword = this.value;
                checkPasswordMatch(this, password);
            });
        }

        function checkPasswordMatch(confirmInput, password) {
            const confirmPassword = confirmInput.value;

            if (confirmPassword && password !== confirmPassword) {
                showValidationError(confirmInput, t('passwords_do_not_match', 'Passwords do not match'));
            } else if (confirmPassword && password === confirmPassword) {
                clearValidationError(confirmInput);
            } else if (!confirmPassword) {
                clearValidationError(confirmInput);
            }
        }
    }

    registerForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!validateRegisterForm()) return;

        toggleButton(true, registerButton, t('signing_up', 'Signing Up...'), t('sign_up', 'Sign Up'));
        errorMessage.textContent = '';

        try {
            const formData = new FormData(this);
            const mobileInput = registerForm.querySelector('input[name="mobile"]');

            if (mobileInput && typeof window.iti !== 'undefined' && window.iti) {
                var fullNumber = window.iti.getNumber() || '';
                if (fullNumber) {
                    formData.set('mobile', fullNumber); // Store full number: +919856237845
                }
                if (window.iti.getSelectedCountryData) {
                    var cd = window.iti.getSelectedCountryData();
                    if (cd && cd.dialCode) {
                        formData.set('country_code', cd.dialCode); // Store: 91 (without +)
                    }
                }
            }
            const response = await fetch(`${baseUrl}/api/register?is_ajax=1`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.status) {
                if (mobileInput && typeof window.iti !== 'undefined' && window.iti) {
                    try {
                        var fullNumber = window.iti.getNumber() || '';
                        if (fullNumber) {
                            var cd = window.iti.getSelectedCountryData && window.iti.getSelectedCountryData();
                            var dial = cd && cd.dialCode ? '+' + cd.dialCode : '';
                            if (dial && fullNumber.startsWith(dial)) {
                                var localNumber = fullNumber.replace(dial, '');
                                window.iti.setNumber(localNumber); // Show only: 9856237845
                            }
                        }
                    } catch (err) {
                        console.error('Error resetting phone:', err);
                    }
                }

                let messages = [];
                if (data.errors && typeof data.errors === 'object') {
                    messages = Object.values(data.errors).flat();
                }
                else if (data.message && typeof data.message === 'object') {
                    messages = Object.values(data.message).flat();
                }
                else if (typeof data.message === 'string') {
                    messages = [data.message];
                }
                else {
                    messages = [t('register_error_generic', 'An error occurred during registration')];
                }

                errorMessage.textContent = messages.join('\n');
                return;
            }

            // Auto-login after registration
            const loginResponse = await fetch(`${baseUrl}/api/login?is_ajax=1`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            const loginData = await loginResponse.json();
            if (loginData.status === true) {
                if (window.successSnackbar) {
                    window.successSnackbar(t('register_success', 'Register successfully!'));
                }
                const registerRedirectUrl = (loginData.data && loginData.data.redirect_url) || `${baseUrl}/manage-profile`;
                setTimeout(() => {
                    window.location.href = registerRedirectUrl;
                }, 1000);
            } else {
                errorMessage.textContent = loginData.message || t('login_after_register_failed', 'Login after registration failed.');
            }

        } catch (error) {
            console.error('Registration error:', error);
            errorMessage.textContent = t('system_error', 'A system error occurred. Please try again later.');
        } finally {
            toggleButton(false, registerButton, '', t('sign_up', 'Sign Up'));
        }
    });

    function validateRegisterForm() {
        let isValid = true;
        const firstName = registerForm.querySelector('input[name="first_name"]');
        const lastName = registerForm.querySelector('input[name="last_name"]');
        const email = registerForm.querySelector('input[name="email"]');
        const password = registerForm.querySelector('input[name="password"]');
        const confirmPassword = registerForm.querySelector('input[name="confirm_password"]');
        const mobile = registerForm.querySelector('input[name="mobile"]');

        if (!firstName.value.trim()) {
            showValidationError(firstName, t('first_name_required', 'First Name field is required.'));
            isValid = false;
        }

        if (!lastName.value.trim()) {
            showValidationError(lastName, t('last_name_required', 'Last Name field is required.'));
            isValid = false;
        }

        if (!email.value.trim()) {
            showValidationError(email, t('email_required', 'Email field is required.'));
            isValid = false;
        } else if (!validateEmail(email.value)) {
            showValidationError(email, t('email_invalid', 'Enter a valid Email Address.'));
            isValid = false;
        }

        if (!mobile.value.trim()) {
            showValidationError(mobile, t('mobile_required', 'Mobile field is required.'));
            isValid = false;
        }

        if (!password.value.trim()) {
            showValidationError(password, t('password_required', 'Password field is required.'));
            isValid = false;
        } else if (!validatePassword(password.value)) {
            showValidationError(password, t('password_requirements', 'Password length should be 8 to 14 Characters, at least one uppercase, one lowercase, one digit, and one special character'));
            isValid = false;
        }

        if (!confirmPassword.value.trim()) {
            showValidationError(confirmPassword, t('confirm_password_required', 'Confirm Password field is required.'));
            isValid = false;
        }

        if (password.value !== confirmPassword.value) {
            showValidationError(confirmPassword, t('passwords_confirm_mismatch', 'Passwords and confirm password do not match.'));
            isValid = false;
        }

        return isValid;
    }
}

// ==========================
// Login Form
// ==========================
const loginForm = document.querySelector('#login-form');
if (loginForm) {
    const loginButton = document.querySelector('#login-button');
    const loginError = document.querySelector('#login_error_message');
    const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content');

    attachLiveInputClear(loginForm, ['email', 'password']);

    // Prevent copy-paste on login password field
    const loginPasswordInput = loginForm.querySelector('input[name="password"]');
    if (loginPasswordInput) {
        ['copy', 'paste', 'cut'].forEach(event => {
            loginPasswordInput.addEventListener(event, e => e.preventDefault());
        });
    }

    loginForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!validateLoginForm()) return;

        toggleButton(true, loginButton, t('signing_in', 'Signing In...'), t('sign_in', 'Sign In'));
        loginError.textContent = '';

        try {
            const formData = new FormData(this);
            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const fetchFreshCsrfToken = async () => {
                try {
                    const tokenResp = await fetch(`${baseUrl}/api/csrf-token`, {
                        method: 'GET',
                        credentials: 'same-origin'
                    });
                    if (tokenResp.ok) {
                        const tokenData = await tokenResp.json().catch(() => ({}));
                        if (tokenData && tokenData.csrf_token) {
                            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                            if (csrfMeta) {
                                csrfMeta.setAttribute('content', tokenData.csrf_token);
                                return tokenData.csrf_token;
                            }
                        }
                    }
                } catch (e) {
                    console.error('Failed to fetch CSRF token:', e);
                }
                return csrfToken;
            };

            const performLogin = async (token) => {
                return await fetch(`${baseUrl}/api/login?is_ajax=1`, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    body: formData
                });
            };

            let response = await performLogin(csrfToken);

            if (response.status === 419) {
                const freshToken = await fetchFreshCsrfToken();
                if (freshToken && freshToken !== csrfToken) {
                    response = await performLogin(freshToken);
                }
            }

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json') ? await response.json() : {};
            if (response.status === 406) {
                loginError.textContent = (data && (data.message || data.error)) ? (data.message || data.error) : t('device_limit_reached', 'Your device limit has been reached.');
                const devices = (data && data.other_device) ? data.other_device : [];
                renderDeviceLimitUI(devices);
                return;
            }

            if (!response.ok || data.status === false) {
                const message = data && (data.errors ? Object.values(data.errors).flat()[0] : data.message) || t('login_failed', 'Login failed');
                loginError.textContent = message;
                return;
            }

            if (window.successSnackbar) {
                window.successSnackbar(t('login_success', 'Login successfully!'));
            }
            const redirectUrl = (data.data && data.data.redirect_url) || `${baseUrl}/manage-profile`;
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 1000);
        } catch (error) {
            console.error('Login error:', error);
            loginError.textContent = t('system_error', 'A system error occurred. Please try again later.');
        } finally {
            toggleButton(false, loginButton, '', t('sign_in', 'Sign In'));
        }
    });

    window.renderDeviceLimitUI = function renderDeviceLimitUI(devices) {
        let section = document.getElementById('device-limit-section');
        if (!section) {
            section = document.createElement('div');
            section.id = 'device-limit-section';
            section.className = 'mt-3';
            const formBottom = loginForm.querySelector('.full-button');
            (formBottom && formBottom.parentNode) ? formBottom.parentNode.insertBefore(section, formBottom.nextSibling) : loginForm.appendChild(section);
        }
        section.innerHTML = '';
        const alert = document.createElement('div');
        alert.className = 'alert alert-warning mb-2 device-limit-alert';
        alert.textContent = t('device_limit_instruction', 'Select a device to logout, then click Sign In again.');


        const logoutAllBtnContainer = document.createElement('div');
        logoutAllBtnContainer.className = 'text-end mb-3';
        const logoutAllBtn = document.createElement('button');
        logoutAllBtn.type = 'button';
        logoutAllBtn.className = 'btn btn-danger btn-sm';
        logoutAllBtn.textContent = t('logout_all_devices', 'Logout All Devices');
        logoutAllBtnContainer.appendChild(logoutAllBtn);
        logoutAllBtn.addEventListener('click', async function () {
            logoutAllBtn.disabled = true;
            logoutAllBtn.textContent = t('logging_out_all_devices', 'Logging out all devices...');
            loginError.textContent = '';

            try {
                const userId = devices && devices.length > 0 ? devices[0].user_id : null;
                if (!userId) {
                    loginError.textContent = t('unable_to_determine_user', 'Unable to determine user ID.');
                    logoutAllBtn.disabled = false;
                    logoutAllBtn.textContent = t('logout_all_devices', 'Logout All Devices');
                    return;
                }

                const resp = await fetch(`${baseUrl}/api/logout-all-data?user_id=${encodeURIComponent(userId)}`, {
                    method: 'GET',
                    credentials: 'include'
                });
                const json = await resp.json().catch(() => ({}));

                if (!resp.ok || (json && json.status === false)) {
                    loginError.textContent = (json && json.message) ? json.message : t('failed_logout_all', 'Failed to logout all devices.');
                    logoutAllBtn.disabled = false;
                    logoutAllBtn.textContent = t('logout_all_devices', 'Logout All Devices');
                    return;
                }

                if (json && json.data && json.data.csrf_token) {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta) {
                        csrfMeta.setAttribute('content', json.data.csrf_token);
                    }
                } else {
                    try {
                        const tokenResp = await fetch(`${baseUrl}/api/csrf-token`, {
                            method: 'GET',
                            credentials: 'same-origin'
                        });
                        if (tokenResp.ok) {
                            const tokenData = await tokenResp.json().catch(() => ({}));
                            if (tokenData && tokenData.csrf_token) {
                                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                                if (csrfMeta) {
                                    csrfMeta.setAttribute('content', tokenData.csrf_token);
                                }
                            }
                        }
                    } catch (e) {
                            console.warn('Could not fetch CSRF token after logout:', e);
                    }
                }

                section.innerHTML = '';
                const successAlert = document.createElement('div');
                successAlert.className = 'alert alert-success';
                successAlert.textContent = t('all_devices_logged_out', 'All devices have been logged out. Click Sign In again to continue.');
                section.appendChild(successAlert);

            } catch (err) {
                loginError.textContent = t('system_error', 'A system error occurred. Please try again later.');
                logoutAllBtn.disabled = false;
                logoutAllBtn.textContent = t('logout_all_devices', 'Logout All Devices');
            }
        });

        const list = document.createElement('div');
        list.className = 'list-group mb-2 logout-other-screen';
        if (!devices || devices.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'text-muted';
            empty.textContent = t('no_devices_found', 'No devices found.');
            list.appendChild(empty);
        } else {
            devices.forEach(function (d) {
                const row = document.createElement('div');
                row.className = 'list-group-item d-flex align-items-center justify-content-between gap-3';
                const left = document.createElement('div');
                left.className = 'd-flex flex-column';
                const name = document.createElement('span');
                name.textContent = `${d.device_name || 'Device'} (${d.platform || 'unknown'})`;
                const sub = document.createElement('small');
                sub.className = 'text-muted breck-anywhere';
                sub.textContent = d.device_id || '';
                left.appendChild(name);
                left.appendChild(sub);
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-outline-danger';
                btn.textContent = t('logout', 'Logout');
                btn.addEventListener('click', async function () {
                    btn.disabled = true;
                    const originalText = btn.textContent;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + t('logging_out', 'Logging out...');
                    loginError.textContent = '';
                    try {
                        const uId = d.user_id;
                        const resp = await fetch(`${baseUrl}/api/device-logout-data?user_id=${encodeURIComponent(uId)}&id=${encodeURIComponent(d.id)}`, {
                            method: 'GET',
                            credentials: 'include'
                        });
                        const json = await resp.json().catch(() => ({}));
                        if (!resp.ok || (json && json.status === false)) {
                            loginError.textContent = (json && json.message) ? json.message : t('failed_delete_device', 'Failed to delete device.');
                            btn.disabled = false;
                            btn.textContent = originalText;
                            return;
                        }
                        row.remove();

                        const remainingDevices = list.querySelectorAll('.list-group-item');
                        if (remainingDevices.length === 0) {
                            const alert = section.querySelector('.device-limit-alert');
                            if (alert) {
                                alert.remove();
                            }
                            if (logoutAllBtnContainer && logoutAllBtnContainer.parentNode) {
                                logoutAllBtnContainer.remove();
                            }
                            section.innerHTML = '';
                            const successAlert = document.createElement('div');
                            successAlert.className = 'alert alert-success';
                            successAlert.textContent = t('all_devices_logged_out', 'All devices have been logged out. Click Sign In again to continue.');
                            section.appendChild(successAlert);
                            return;
                        }
                        const existingSuccessMsg = section.querySelector('.text-success, .alert-success');
                        if (existingSuccessMsg) {
                            existingSuccessMsg.remove();
                        }

                        const info = document.createElement('div');
                        info.className = 'text-success mb-2';
                        info.textContent = t('device_removed_info', 'Device removed. Click Sign In again to continue.');
                        section.insertBefore(info, section.firstChild);
                    } catch (err) {
                        loginError.textContent = t('system_error', 'A system error occurred. Please try again later.');
                        btn.disabled = false;
                        btn.textContent = t('logout', 'Logout');
                    }
                });
                row.appendChild(left);
                row.appendChild(btn);
                list.appendChild(row);
            });
        }
        section.appendChild(alert);
        section.appendChild(logoutAllBtnContainer);
        section.appendChild(list);
    }

    function validateLoginForm() {
        let isValid = true;
        const email = loginForm.querySelector('input[name="email"]');
        const password = loginForm.querySelector('input[name="password"]');

        if (!email.value.trim()) {
            showValidationError(email, t('email_required', 'Email field is required.'));
            isValid = false;
        } else if (!validateEmail(email.value)) {
            showValidationError(email, t('email_invalid', 'Enter a valid Email Address.'));
            isValid = false;
        }

        if (!password.value.trim()) {
            showValidationError(password, t('password_required', 'Password field is required.'));
            isValid = false;
        }

        return isValid;
    }
}
// ==========================
// Forgot Password Form
// ==========================
const forgetPasswordForm = document.querySelector('#forgetpassword-form');
if (forgetPasswordForm) {
    const forgetPasswordButton = document.querySelector('#forget_password_btn');
    const forgetPasswordError = document.querySelector('#forgetpassword_error_message');
    const forgetPasswordMessage = document.querySelector('#forget_password_msg');
    const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content');

    attachLiveInputClear(forgetPasswordForm, ['email']);

    forgetPasswordForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!validateForgetPasswordForm()) return;

        toggleButton(true, forgetPasswordButton, t('sending', 'Sending...'), t('submit', 'Submit'));
        forgetPasswordError.textContent = '';
        forgetPasswordMessage.classList.add('d-none');

        try {
            const formData = new FormData(this);
            const response = await fetch(`${baseUrl}/api/forgot-password?is_ajax=1`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.status) {
                const message = data.errors ? Object.values(data.errors).flat()[0] : data.message || t('password_reset_failed', 'Password reset failed');
                forgetPasswordError.textContent = message;
                return;
            }

            forgetPasswordMessage.classList.remove('d-none');
            forgetPasswordForm.reset();
        } catch (error) {
            console.error('Forgot password error:', error);
            forgetPasswordError.textContent = t('system_error', 'A system error occurred. Please try again later.');
        } finally {
            toggleButton(false, forgetPasswordButton, '', t('submit', 'Submit'));
        }
    });

    function validateForgetPasswordForm() {
        let isValid = true;
        const email = forgetPasswordForm.querySelector('input[name="email"]');

        if (!email.value.trim()) {
            showValidationError(email, t('email_required', 'Email field is required.'));
            isValid = false;
        } else if (!validateEmail(email.value)) {
            showValidationError(email, t('email_invalid', 'Enter a valid Email Address.'));
            isValid = false;
        }

        return isValid;
    }
}
