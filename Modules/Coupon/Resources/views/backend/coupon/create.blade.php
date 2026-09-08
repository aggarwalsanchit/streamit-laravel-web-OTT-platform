@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@push('after-styles')
<style>select#subscription_plan_ids:not(.select2-hidden-accessible){position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);}</style>
@endpush

@section('content')
    <x-back-button-component route="backend.coupon.index" />
    <div class="card">
        <div class="card-body">
            <form action="{{ isset($coupon) ? route('backend.coupon.update', $coupon) : route('backend.coupon.store') }}"
                id="form-submit" method="POST">
                @csrf
                @if (isset($coupon))
                    @method('PUT')
                @endif

                <div class="row">
                    <!-- Coupon Code -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="code">{{ __('messages.code') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="code" name="code" maxlength="10" placeholder="{{ __('messages.code_placeholder') }}"
                                value="{{ old('code', $coupon->code ?? '') }}">
                            @error('code')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="code-error" data-default-message="{{ __('messages.code_required') }}">
                                {{ __('messages.code_required') }}
                            </div>
                        </div>
                    </div>

                    <!-- Start Date -->
                    <div class="col-md-4">
                        {{ html()->label(__('messages.start_date_coupon'), 'start_date')->class('form-label') }}
                        <span class="text-danger">*</span>
                        {{ html()->date('start_date')->id('start_date')->value(isset($coupon) ? $coupon->start_date : old('start_date'))->placeholder(__('messages.date_placeholder'))->class('form-control datetimepicker') }}
                        @error('start_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="start_date-error" data-default-message="{{ __('messages.start_date_required') }}">
                            {{ __('messages.start_date_required') }}
                        </div>
                    </div>

                    <!-- Expire Date -->
                    <div class="col-md-4">
                        {{ html()->label(__('messages.expire_date'), 'expire_date')->class('form-label') }}
                        <span class="text-danger">*</span>
                        {{ html()->date('expire_date')->id('expire_date')->value(isset($coupon) ? $coupon->expire_date : old('expire_date'))->placeholder(__('messages.date_placeholder'))->class('form-control datetimepicker') }}
                        @error('expire_date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="expire_date-error" data-default-message="{{ __('messages.expire_date_required') }}">
                            {{ __('messages.expire_date_required') }}
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            {{ html()->label(__('messages.description'), 'description')->class('form-label') }}
                            <span class="text-danger">*</span>
                            <small class="text-muted d-block" id="description-char-count"></small>
                            <textarea name="description" id="description" class="form-control"
                                placeholder="{{ __('messages.description_placeholder') }}" rows="5" maxlength="120">{{ old('description', $coupon->description ?? '') }}</textarea>
                            @error('description')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="description-error" data-default-message="{{ __('messages.description_required') }}">
                                {{ __('messages.description_required') }}
                            </div>

                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row">
                            <!-- Discount Type -->

                            <div class="col-md-6">
                                <div class="form-group">
                                    {{ html()->label(__('messages.discount_type'), 'discount_type')->class('form-label') }}
                                    <span class="text-danger">*</span>
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                                            <div>
                                                <input type="radio" name="discount_type" id="discount_type_percentage"
                                                    value="percentage" class="form-check-input"
                                                    {{ old('discount_type', $coupon->discount_type ?? 'percentage') == 'percentage' ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ __('messages.percentage') }}</span>
                                            </div>
                                        </label>
                                        <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                                            <div>
                                                <input type="radio" name="discount_type" id="discount_type_fixed"
                                                    value="fixed" class="form-check-input"
                                                    {{ old('discount_type', $coupon->discount_type ?? 'percentage') == 'fixed' ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ __('messages.fixed') }}</span>
                                            </div>
                                        </label>
                                    </div>
                                    @error('discount_type')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Discount Amount -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    {{ html()->label(__('messages.discount_amount'), 'discount')->class('form-label') }}
                                    <span class="text-danger">*</span>
                                    <input type="text" name="discount" id="discount" class="form-control"
                                        placeholder="{{ __('messages.discount_placeholder') }}"
                                        value="{{ old('discount', $coupon->discount ?? '') }}">
                                    @error('discount')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <div class="invalid-feedback" id="discount-error" data-default-message="{{ __('messages.discount_amount_required') }}">
                                        {{ __('messages.discount_amount_required') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Subscription Plans -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    {{ html()->label(__('messages.subscription_plan'), 'subscription_plan_ids')->class('form-label') }}
                                    <span class="text-danger">*</span>
                                    {{ html()->multiselect(
                                            'subscription_plan_ids[]',
                                            $plans->pluck('name', 'id'),
                                            isset($coupon) ? $coupon->subscriptionPlans->pluck('id') : old('subscription_plan_ids', []),
                                        )->class('form-control select2')->id('subscription_plan_ids')->attributes(['data-placeholder' => __('messages.selecte_plan')]) }}
                                    @error('subscription_plan_ids')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <div class="invalid-feedback" id="subscription_plan_ids-error" data-default-message="{{ __('messages.subscription_plan_required') }}">
                                        {{ __('messages.subscription_plan_required') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    {{ html()->label(__('messages.status'), 'status')->class('form-label') }}
                                    <div class="d-flex justify-content-between align-items-center form-control">
                                        <span id="status-text"></span>
                                        <div class="form-check form-switch">
                                            {{ html()->hidden('status', 0) }}
                                            {{ html()->checkbox('status')->class('form-check-input')->id('status')->value(1)->checked(isset($coupon) ? $coupon->status : old('status', 1)) }}
                                        </div>
                                    </div>
                                    @error('status')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

        </div>
    </div>
    <div class="d-flex justify-content-end">
        <button type="submit" id="coupon-submit-btn" class="btn btn-primary">
            {{ isset($coupon) ? __('messages.update') : __('messages.save') }}
        </button>
    </div>
    </form>
@endsection

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Note: form submit is handled via AJAX (see below) to avoid full page reloads.

        const codeInput = document.getElementById('code');
        const codeError = document.getElementById('code-error');
        const descriptionInput = document.getElementById('description');
        const descriptionCount = document.getElementById('description-char-count');
        const descriptionLimit = 120;

        // const validateCodeLength = () => {
        //     if (!codeInput) return;
        //     let value = codeInput.value || '';
        //     if (value.length > 10) {
        //         value = value.slice(0, 10);
        //         codeInput.value = value;
        //     }
        //     if (value.length > 0 && value.length < 6) {
        //         codeInput.classList.add('is-invalid');
        //         codeError.textContent = '{{ __('messages.the_code_must_be_at_least_6_characters') }}';
        //     } else {
        //         codeInput.classList.remove('is-invalid');
        //         codeError.textContent = '';
        //     }
        // };

        const updateDescriptionCount = () => {
            if (!descriptionInput || !descriptionCount) return;
            let value = descriptionInput.value || '';
            if (value.length > descriptionLimit) {
                value = value.slice(0, descriptionLimit);
                descriptionInput.value = value;
            }
            descriptionCount.textContent = `${value.length}/${descriptionLimit}`;
            descriptionCount.classList.toggle('text-danger', value.length >= descriptionLimit);
        };

        // if (codeInput) {
        //     codeInput.addEventListener('input', validateCodeLength);
        //     codeInput.addEventListener('blur', validateCodeLength);
        //     validateCodeLength();
        // }

        if (descriptionInput) {
            descriptionInput.addEventListener('input', updateDescriptionCount);
            updateDescriptionCount();
        }

        let startPicker = flatpickr("#start_date", {
            dateFormat: "Y-m-d",
            minDate: "today", // Disable past dates, allow today and future dates
            onChange: function(selectedDates) {
                let minEndDate = new Date(selectedDates[0]);
                minEndDate.setDate(minEndDate.getDate() +
                1); // Set end date at least one day after start date

                endPicker.set("minDate", minEndDate); // Update end date min selection
                endPicker.setDate(minEndDate); // Auto set end date to next day
            }
        });

        let endPicker = flatpickr("#expire_date", {
            dateFormat: "Y-m-d",
            minDate: new Date().fp_incr(1), // Default min end date as tomorrow
        });

    });
    document.addEventListener('DOMContentLoaded', function() {
        $('#subscription_plan_ids').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '{{ __('messages.selecte_plan') }}',
            allowClear: true,
            multiple: true
        });

        const discountTypeInputs = document.querySelectorAll('input[name="discount_type"]');
        const discountInput = document.getElementById('discount');

        discountTypeInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'percentage') {
                    discountInput.setAttribute('max', '99');
                } else {
                    discountInput.removeAttribute('max');
                }
            });
            flatpickr('input[name="expire_date"]', {
                dateFormat: "Y-m-d",
                minDate: document.querySelector('input[name="start_date"]').value || "today"
            });
        });

        // Set initial status text
        const statusCheckbox = document.getElementById('status');
        const statusText = document.getElementById('status-text');
        statusText.textContent = statusCheckbox.checked ? '{{ __('messages.active') }}' :
            '{{ __('messages.inactive') }}';
    });

    document.addEventListener('DOMContentLoaded', function() {
        // ... other existing code ...

        // Get the status checkbox
        const statusCheckbox = document.getElementById('status');
        const statusText = document.getElementById('status-text');

        // Set initial status based on existing data or default
        @if (isset($coupon))
            statusCheckbox.checked = {{ $coupon->status ? 'true' : 'false' }};
        @else
            statusCheckbox.checked = {{ old('status', true) ? 'true' : 'false' }};
        @endif

        // Set initial text based on checkbox state
        statusText.textContent = statusCheckbox.checked ? '{{ __('messages.active') }}' :
            '{{ __('messages.inactive') }}';
    });

    document.addEventListener('DOMContentLoaded', function() {
        const statusCheckbox = document.getElementById('status');
        const statusText = document.getElementById('status-text');

        // Initialize status text based on checkbox state
        function updateStatusText() {
            statusText.textContent = statusCheckbox.checked ?
                '{{ __('messages.active') }}' :
                '{{ __('messages.inactive') }}';
        }

        // Set initial status text
        updateStatusText();

        // Update status text when checkbox changes
        statusCheckbox.addEventListener('change', function() {
            updateStatusText();
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize start date picker
        const startDatePicker = flatpickr('input[name="start_date"]', {
            dateFormat: "Y-m-d",
            minDate: "today",
            disable: [
                function(date) {
                    return date < new Date().setHours(0, 0, 0, 0);
                }
            ],
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates[0]) {
                    // Update end date minimal date when start date changes
                    endDatePicker.set('minDate', selectedDates[0]);

                    // If end date is before new start date, reset it
                    const currentEndDate = endDatePicker.selectedDates[0];
                    if (currentEndDate && currentEndDate < selectedDates[0]) {
                        endDatePicker.setDate(selectedDates[0]);
                    }
                }
            }
        });

        // Initialize end date picker
        const endDatePicker = flatpickr('input[name="expire_date"]', {
            dateFormat: "Y-m-d",
            minDate: document.querySelector('input[name="start_date"]').value || "today",
            disable: [
                function(date) {
                    // Disable dates before start date
                    const startDate = startDatePicker.selectedDates[0] || new Date();
                    return date < startDate;
                }
            ],
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates[0]) {
                    // Validate that end date is after start date
                    const startDate = startDatePicker.selectedDates[0];
                    if (startDate && selectedDates[0] < startDate) {
                        instance.setDate(startDate);
                        alert('End date must be after start date');
                    }
                }
            }
        });

        // Form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const startDate = startDatePicker.selectedDates[0];
            const endDate = endDatePicker.selectedDates[0];

            if (!startDate || !endDate) {
                e.preventDefault();
                alert('Both start and end dates are required');
                return false;
            }

            if (endDate < startDate) {
                e.preventDefault();
                alert('End date must be after start date');
                return false;
            }
        });
    });

    // Add this to your existing JavaScript section or create a new script file
    document.addEventListener('DOMContentLoaded', function() {
        const discountInput = document.getElementById('discount');
        const discountTypePercentage = document.getElementById('discount_type_percentage');
        const discountTypeFixed = document.getElementById('discount_type_fixed');

        function validateDiscount() {
            const value = parseFloat(discountInput.value);
            const isPercentage = discountTypePercentage.checked;

            if (isPercentage && value > 99) {
                discountInput.classList.add('is-invalid');
                // Add error message div if it doesn't exist
                if (!discountInput.nextElementSibling || !discountInput.nextElementSibling.classList.contains(
                        'invalid-feedback')) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.id = 'discount-error';
                    discountInput.parentNode.appendChild(errorDiv);
                }
                document.getElementById('discount-error').textContent =
                @json(__('messages.discount_value_less_than_99'));
            } else {
                discountInput.classList.remove('is-invalid');
                const errorDiv = document.getElementById('discount-error');
                if (errorDiv) {
                    errorDiv.textContent = '';
                }
            }
        }

        // Validate on input blur
        discountInput.addEventListener('blur', validateDiscount);

        // Clear error on input focus
        discountInput.addEventListener('focus', function() {
            discountInput.classList.remove('is-invalid');
            const errorDiv = document.getElementById('discount-error');
            if (errorDiv) {
                errorDiv.textContent = '';
            }
        });

        // Validate when discount type changes
        discountTypePercentage.addEventListener('change', validateDiscount);
        discountTypeFixed.addEventListener('change', validateDiscount);
    });
</script>

<script>
    // Make coupon validation behave like other modules:
    // - when user fixes a field (type/select), remove invalid state + hide the shown message immediately.
    document.addEventListener('DOMContentLoaded', function() {
        const $form = $('#form-submit');
        if (!$form.length) return;

        function clearFieldError(el) {
            const $el = $(el);
            $el.removeClass('is-invalid');

            // Hide bootstrap invalid feedback in same group
            const $group = $el.closest('.form-group, .col-md-4, .col-md-6, .col-md-8');
            // No need to force hide: Bootstrap hides `.invalid-feedback` automatically once `is-invalid` is removed.
        }

        // Inputs & textarea
        $form.on('input change', 'input, textarea', function() {
            const val = ($(this).val() ?? '').toString().trim();
            if (!val) return;
            clearFieldError(this);
        });

        // Selects (including Select2)
        $form.on('change', 'select', function() {
            const val = $(this).val();
            if (val === null || val === '' || (Array.isArray(val) && val.length === 0)) return;
            clearFieldError(this);

            // Select2 renders a separate selection UI; clear invalid styling there too.
            $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
        });

        // Select2 multi-select events (needed because the original select is hidden)
        $('#subscription_plan_ids').on('select2:select select2:unselect', function() {
            const val = $(this).val() || [];
            if (val.length === 0) return;
            clearFieldError(this);
            $(this).next('.select2-container').find('.select2-selection').removeClass('is-invalid');
        });

        // Discount type radio buttons
        $form.on('change', 'input[name="discount_type"]', function() {
            clearFieldError(this);
        });
    });
</script>

<script>
    // AJAX submit (no full page reload) + consistent bootstrap validation UI
    document.addEventListener('DOMContentLoaded', function() {
        const $form = $('#form-submit');
        const $submitBtn = $('#coupon-submit-btn');
        if (!$form.length) return;

        function resetAllErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').each(function() {
                const $el = $(this);
                const def = $el.data('default-message');
                if (def) $el.text(def);
            });
            // Select2 UI
            $form.find('.select2-container .select2-selection').removeClass('is-invalid');
        }

        function showFieldError(field, message) {
            // Laravel will return "subscription_plan_ids" but input name is "subscription_plan_ids[]"
            const fieldId = field === 'subscription_plan_ids' ? 'subscription_plan_ids' : field;
            const $field = $('#' + fieldId);

            if ($field.length) {
                $field.addClass('is-invalid');
                // Select2
                $field.next('.select2-container').find('.select2-selection').addClass('is-invalid');
            } else {
                // fallback by name
                $form.find(`[name="${field}"], [name="${field}[]"]`).addClass('is-invalid');
            }

            const $error = $('#' + fieldId + '-error');
            if ($error.length) {
                $error.text(Array.isArray(message) ? message[0] : message);
            }
        }

        $form.on('submit', function(e) {
            e.preventDefault();

            resetAllErrors();

            const formData = new FormData(this);

            $submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm"></span> {{ __('messages.loading') }}'
            );

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                success: function(response) {
                    window.successSnackbar(response.message || '{{ __('messages.create_form') }}');
                    if (response.redirect) {
                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 800);
                    } else {
                        $submitBtn.prop('disabled', false).html('{{ __('messages.save') }}');
                    }
                },
                error: function(xhr) {
                    $submitBtn.prop('disabled', false).html('{{ isset($coupon) ? __('messages.update') : __('messages.save') }}');

                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.entries(xhr.responseJSON.errors).forEach(([field, messages]) => {
                            showFieldError(field, messages);
                        });
                        return;
                    }

                    window.errorSnackbar(xhr.responseJSON?.message || '{{ __('messages.something_went_wrong') }}');
                }
            });
        });
    });
</script>
<script>
    const plansData = @json($plans->mapWithKeys(fn($plan) => [$plan->id => $plan->price]));
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const subscriptionPlanSelect = $('#subscription_plan_ids');
        const discountInput = $('#discount');
        const discountTypeInputs = $('input[name="discount_type"]');
        const errorContainerId = 'plan-discount-error';

        // Create or get error container for plan-discount validation
        let $errorContainer = $('#' + errorContainerId);
        if (!$errorContainer.length) {
            $errorContainer = $('<div>', {
                id: errorContainerId,
                class: 'text-danger mt-2'
            });
            subscriptionPlanSelect.parent().append($errorContainer);
        }

        function getDiscountValue() {
            let val = parseFloat(discountInput.val());
            if (isNaN(val) || val <= 0) return 0;
            return val;
        }

        function getDiscountType() {
            return discountTypeInputs.filter(':checked').val();
        }

        function validateSelection() {
            const discount = getDiscountValue();
            const discountTypeVal = getDiscountType();
            const selectedPlans = subscriptionPlanSelect.val() || [];
            let errorMsg = '';

                // Check if discount exceeds any selected plan price
                for (const planId of selectedPlans) {
                    const planPrice = plansData[planId] ?? 0;
                    if (discountTypeVal === 'fixed' && discount > planPrice) {
                        errorMsg =
                        '{{ __('messages.discount_greater_than_plan_price') }}'; // Add this translation key
                        break;
                    }
                }

            if (errorMsg) {
                $errorContainer.text(errorMsg);
                // Also prevent selecting the invalid plans visually by disabling them
                disableInvalidPlans(discount, discountTypeVal, selectedPlans);
                return false;
            } else {
                $errorContainer.text('');
                enableAllPlans();
                return true;
            }
        }

        function disableInvalidPlans(discount, discountType, selectedPlans) {
            subscriptionPlanSelect.find('option').each(function() {
                const planId = $(this).val();
                const planPrice = plansData[planId] ?? 0;

                if (discountType === 'fixed' && discount > planPrice) {
                    if (selectedPlans.includes(planId)) {
                        // Deselect invalid plans
                        $(this).prop('selected', false);
                    }
                    $(this).prop('disabled', true);
                } else {
                    $(this).prop('disabled', false);
                }
            });
            subscriptionPlanSelect.trigger('change.select2');
        }

        function enableAllPlans() {
            subscriptionPlanSelect.find('option').prop('disabled', false);
            subscriptionPlanSelect.trigger('change.select2');
        }

        // Listen to discount input changes and discount type changes
        discountInput.on('input', function() {
            validateSelection();
        });

        discountTypeInputs.on('change', function() {
            validateSelection();
        });

        // Listen to plan selection changes
        subscriptionPlanSelect.on('change', function() {
            validateSelection();
        });

        // Initial validation on page load
        validateSelection();
    });
</script>
