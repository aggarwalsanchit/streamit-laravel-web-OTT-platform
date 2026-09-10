class PinVerification {
  constructor() {
      this.targetProfileId = null;
      this.currentProfileData = null;
      this.modalInstance = null;
      this.init();
  }

  init() {
      this.createModal();
      this.bindEvents();
  }

  createModal() {
      if (document.getElementById('pinVerificationModal')) return;

      const modalHTML = `
          <div class="modal fade add-profile-modal" id="pinVerificationModal" tabindex="-1" aria-labelledby="pinVerificationModalLabel" aria-modal="true" role="dialog">
              <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content position-relative">
                      <button type="button" class="btn btn-primary custom-close-btn rounded-2" id="pinModalCloseBtn" aria-label="${this.trans('messages.lbl_close')}">
                          <i class="ph ph-x text-white fw-bold align-middle"></i>
                      </button>
                      <form id="pinVerificationForm" class="requires-validation" novalidate>
                          <div class="modal-body text-center">
                              <div class="mb-3">
                                  <h5 id="pinVerificationModalLabel">${this.trans('messages.parental_lock')}</h5>
                                  <p class="mb-5">${this.trans('messages.lbl_pin_description')}</p>

                                  <div id="pin-form" class="d-flex justify-content-center align-items-center gap-md-3 gap-2 mb-5">
                                      <input type="text" name="pin[]" class="pin-input form-control text-center fs-4 fw-bold" maxlength="1" required  aria-label="${this.trans('messages.lbl_pin_digit')} 1" autocomplete="off">
                                      <input type="text" name="pin[]" class="pin-input form-control text-center fs-4 fw-bold" maxlength="1" required  aria-label="${this.trans('messages.lbl_pin_digit')} 2" autocomplete="off">
                                      <input type="text" name="pin[]" class="pin-input form-control text-center fs-4 fw-bold" maxlength="1" required  aria-label="${this.trans('messages.lbl_pin_digit')} 3" autocomplete="off">
                                      <input type="text" name="pin[]" class="pin-input form-control text-center fs-4 fw-bold" maxlength="1" required  aria-label="${this.trans('messages.lbl_pin_digit')} 4" autocomplete="off">
                                  </div>

                                  <div class="invalid-feedback text-center" id="pin_error" role="alert" aria-live="polite">${this.trans('messages.lbl_pin_required')}</div>
                                  <p class="text-danger text-center" id="pin_backend_error" role="alert" aria-live="polite"></p>
                              </div>

                              <div>
                                  <button type="submit" id="verifyPinBtn" class="btn btn-primary mt-3">${this.trans('messages.lbl_verify_pin')}</button>
                              </div>
                          </div>
                      </form>
                  </div>
              </div>
          </div>
      `;

      document.body.insertAdjacentHTML('beforeend', modalHTML);
  }

  // Translation helper method
  trans(key) {
      // Check if Laravel translations are available in window object
      if (window.translations && window.translations[key]) {
          return window.translations[key];
      }

      // Fallback to default English text
      const fallbacks = {
          'messages.lbl_close': 'Close',
          'messages.lbl_enter_pin': 'Enter PIN',
          'messages.lbl_pin_description': 'Please enter your 4-digit PIN to continue',
          'messages.lbl_pin_digit': 'PIN digit',
          'messages.lbl_pin_required': 'PIN is required',
          'messages.lbl_verify_pin': 'Verify PIN',
          'messages.lbl_verifying': 'Verifying...',
          'messages.lbl_cancel': 'Cancel',
          'messages.lbl_invalid_pin': 'Invalid PIN',
          'messages.lbl_enter_all_digits': 'Please enter all 4 digits',
          'messages.lbl_error_occurred': 'An error occurred. Please try again.'
      };

      return fallbacks[key] || key;
  }

  bindEvents() {
      document.addEventListener('input', (e) => {
          if (e.target.classList.contains('pin-input')) {
              this.handlePinInput(e);
          }
      });

      document.addEventListener('keydown', (e) => {
          if (e.target.classList.contains('pin-input') && e.key === 'Backspace') {
              this.handleBackspace(e);
          }
      });

      document.addEventListener('submit', (e) => {
          if (e.target.id === 'pinVerificationForm') {
              e.preventDefault();
              this.verifyPin();
          }
      });

      document.addEventListener('click', (e) => {
          if (e.target.id === 'pinModalCloseBtn' || e.target.closest('#pinModalCloseBtn')) {
              this.hideModal();
          }
      });

      document.addEventListener('click', (e) => {
          if (e.target.id === 'pinVerificationModal') {
              this.hideModal();
          }
      });

      document.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') {
              const modal = document.getElementById('pinVerificationModal');
              if (modal && modal.classList.contains('show')) {
                  this.hideModal();
              }
          }
      });
  }

  handlePinInput(e) {
      const value = e.target.value;
      const inputs = document.querySelectorAll('.pin-input');
      const currentIndex = Array.from(inputs).indexOf(e.target);

      if (!/^\d$/.test(value)) {
          e.target.value = '';
          return;
      }

      e.target.style.borderColor = '';

      if (value && currentIndex < inputs.length - 1) {
          inputs[currentIndex + 1].focus();
      }
  }

  handleBackspace(e) {
      const inputs = document.querySelectorAll('.pin-input');
      const currentIndex = Array.from(inputs).indexOf(e.target);

      if (e.target.value === '' && currentIndex > 0) {
          inputs[currentIndex - 1].focus();
      }
  }

  getCurrentProfileData() {
      return {
          is_child_profile: window.currentProfileData?.is_child_profile || 0,
          user_has_pin: window.currentProfileData?.user_has_pin || false,
          is_parental_lock_enable: window.currentProfileData?.is_parental_lock_enable || 0
      };
  }

  isPinRequired(targetProfileId, currentProfileData) {
      const targetProfile = document.querySelector(`[data-profile-id="${targetProfileId}"]`);
      if (!targetProfile) return false;

      const isTargetChild = targetProfile.getAttribute('data-is-child') == '1';
      const isTargetParent = !isTargetChild;
      const hasPin = currentProfileData.user_has_pin;
      const isParentalLockEnabled = currentProfileData.is_parental_lock_enable == 1;

      // Require PIN whenever selecting a parent profile if parental lock is enabled and PIN exists
      return isTargetParent && hasPin && isParentalLockEnabled;
  }

  showModal(targetProfileId) {
      this.targetProfileId = targetProfileId;
      const modal = document.getElementById('pinVerificationModal');

      if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          this.modalInstance = new bootstrap.Modal(modal, {
              backdrop: 'static',
              keyboard: false
          });
          this.modalInstance.show();
      } else {
          modal.style.display = 'block';
          modal.classList.add('show');
          document.body.classList.add('modal-open');

          if (!document.getElementById('pinModalBackdrop')) {
              const backdrop = document.createElement('div');
              backdrop.className = 'modal-backdrop fade show';
              backdrop.id = 'pinModalBackdrop';
              document.body.appendChild(backdrop);
          }
      }

      document.querySelectorAll('.pin-input').forEach(input => {
          input.value = '';
          input.style.borderColor = '';
      });
      document.getElementById('pin_error').textContent = '';
      document.getElementById('pin_backend_error').textContent = '';

      setTimeout(() => {
          const firstInput = document.querySelector('.pin-input');
          if (firstInput) {
              firstInput.focus();
          }
      }, 400);
  }

  hideModal() {
      const modal = document.getElementById('pinVerificationModal');

      if (this.modalInstance) {
          this.modalInstance.hide();
          this.modalInstance = null;
      } else {
          modal.style.display = 'none';
          modal.classList.remove('show');
          document.body.classList.remove('modal-open');

          const backdrop = document.getElementById('pinModalBackdrop');
          if (backdrop) {
              backdrop.remove();
          }
      }

      const form = document.getElementById('pinVerificationForm');
      if (form) {
          form.reset();
      }
  }

  async verifyPin() {
    const formData = new FormData(document.getElementById('pinVerificationForm'));
    const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
    const verifyBtn = document.getElementById('verifyPinBtn');
    const errorElement = document.getElementById('pin_backend_error');

    const pinInputs = document.querySelectorAll('.pin-input');
    const allFilled = Array.from(pinInputs).every(input => input.value.trim() !== '');

    if (!allFilled) {
        errorElement.textContent = this.trans('messages.lbl_enter_all_digits');
        return;
    }

    verifyBtn.disabled = true;
    verifyBtn.textContent = this.trans('messages.lbl_verifying');
    errorElement.textContent = '';

    try {
        const response = await fetch(`${baseUrl}/api/verify-pin`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Authorization': 'Bearer ' + window.userApiToken
            }
        });

        const data = await response.json();

        if (data.status === true) {
            const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
            await this.switchProfile(this.targetProfileId, true ,formData);
            window.location.href = baseUrl;
        } else {
            errorElement.textContent = data.message || this.trans('messages.lbl_invalid_pin');
            document.querySelectorAll('.pin-input').forEach(input => {
                input.style.borderColor = '#dc3545';
                input.value = '';
            });
            document.querySelector('.pin-input').focus();
        }
    } catch (error) {
        errorElement.textContent = this.trans('messages.lbl_error_occurred');
        console.error('PIN verification error:', error);
    } finally {
        verifyBtn.disabled = false;
        verifyBtn.textContent = this.trans('messages.lbl_verify_pin');
    }
}

switchProfile(id, skipRedirect = false, formData = null) {
    const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
    const apiUrl = `${baseUrl}/api/select-userprofile/${id}`;

    return fetch(apiUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Authorization': 'Bearer ' + window.userApiToken
        }
    })
        .then(response => response.json())
        .then(response => {
            if (response.status && !skipRedirect) {
                window.location.href = baseUrl;
            }
            return response;
        })
        .catch(error => {
            console.error('Profile switch error:', error);
            throw error;
        });
}

  checkAndSwitchProfile(id) {
      const currentProfile = this.getCurrentProfileData();

      if (this.isPinRequired(id, currentProfile)) {
          this.showModal(id);
      } else {
          this.switchProfile(id);
      }
  }
}

const pinVerification = new PinVerification();

function SelectProfile11(id) {
  pinVerification.checkAndSwitchProfile(id);
}
