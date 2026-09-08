@extends('setting::backend.setting.index')
@section('title')
    {{ __('setting_sidebar.lbl_currency_setting') }}
@endsection
@section('settings-content')


    <div>
        <div class="d-flex align-items-center justify-content-between">
            <h3 class="mb-0"><i class="fa fa-dollar fa-lg mr-2"></i>&nbsp;{{ __('setting_sidebar.lbl_currency_setting') }}
            </h3>
            @hasPermission('add_currency')
                <button class="btn btn-primary d-flex align-items-center gap-1" data-bs-toggle="modal"
                    data-bs-target="#currencyModal" onclick="openModal(0)">
                    <i class="ph ph-plus-circle"></i> {{ __('messages.new') }}
                </button>
            @endhasPermission
        </div>

        <!-- Currency Form Modal -->

        <div class="table-responsive mt-4">
            <table class="table table-condensed">
                <thead>
                    <tr>
                        <!-- <th>{{ __('currency.lbl_ID') }}</th> -->
                        <th>{{ __('currency.lbl_currency_name') }}</th>
                        <th>{{ __('currency.lbl_currency_symbol') }}</th>
                        <th>{{ __('currency.lbl_currency_code') }}</th>
                        <th>{{ __('currency.lbl_is_primary') }}</th>
                        <th>{{ __('currency.lbl_action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($currencies as $currency)
                        <tr>
                            <!-- <td>{{ $loop->index + 1 }}</td> -->
                            <td>{{ $currency->currency_name }}</td>
                            <td>{{ $currency->currency_symbol }}</td>
                            <td>{{ $currency->currency_code }}</td>
                            <td>
                                @if ($currency->is_primary)
                                    <span class="badge bg-success py-2">Default</span>
                                @else
                                    <span class="badge bg-primary">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2 align-items-center justify-content-end">

                                    @hasPermission('edit_currency')
                                        <button type="button" class="btn btn-warning-subtle btn-sm fs-4 currency-edit-btn"
                                            onclick="openModal({{ $currency->id }}, '{{ route('backend.currencies.edit', $currency->id) }}')"
                                            data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('messages.edit') }}"><i
                                                class="ph ph-pencil-simple-line align-middle"></i></button>
                                    @endhasPermission

                                    @hasPermission('delete_currency')
                                        <form action="{{ route('backend.currencies.destroy', $currency->id) }}" method="POST"
                                            class="d-inline m-0 delete-form" data-currency-name="{{ $currency->currency_name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-secondary-subtle btn-sm fs-4 delete-button currency-delete-btn"
                                                data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('messages.delete') }}">
                                                <i class="ph ph-trash align-middle"></i>
                                            </button>
                                        </form>
                                    @endhasPermission
                            </td>
                        </tr>
                    @empty
                        <tr class="text-center">
                            <td colspan="6" class="py-3">{{ __('messages.data_not_available') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @include('setting::backend.setting.section-pages.Forms.currency-form', ['curr_names' => $curr_names])

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function openModal(id = 0, editUrl = null) {
            const modalElement = document.getElementById('currencyModal');
            const modalTitle = document.getElementById('currencyModalLabel');
            const form = document.getElementById('currencyForm');
            form.reset();
            clearValidationErrors();

            // Ensure asterisks are visible (they might have been hidden by clearValidationErrors)
            const asterisks = document.querySelectorAll('.form-label .text-danger');
            asterisks.forEach(asterisk => {
                if (asterisk.textContent.trim() === '*') {
                    asterisk.style.display = '';
                }
            });

            // Reset form validation classes for all fields
            const formFields = ['currencyName', 'currencyCode', 'currencySymbol', 'thousandSeparator', 'decimalSeparator', 'noOfDecimal'];
            formFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.classList.remove('is-invalid');
                }
            });

            // Reset submission flag
            if (window.currencyFormSubmitFlag !== undefined) {
                window.currencyFormSubmitFlag = false;
            }

            if (id === 0) {
                modalTitle.innerText = '{{ __('currency.lbl_add') }}';
                form.action = '{{ route('backend.currencies.store') }}';
                form._method.value = 'POST';
            } else {
                modalTitle.innerText = '{{ __('currency.lbl_edit') }}';
                form.action = '{{ route('backend.currencies.update', ':id') }}'.replace(':id', id);
                form.querySelector('input[name="_method"]').value = 'PUT';
                fetch(editUrl)
                    .then(response => response.json())
                    .then(data => {

                        document.getElementById('currencyName').value = data.data.currency_name;
                        document.getElementById('currencySymbol').value = data.data.currency_symbol;
                        document.getElementById('currencyCode').value = data.data.currency_code;
                        document.getElementById('isPrimary').checked = data.data.is_primary;
                        document.getElementById('currencyPosition').value = data.data.currency_position;
                        document.getElementById('thousandSeparator').value = data.data.thousand_separator;
                        document.getElementById('decimalSeparator').value = data.data.decimal_separator;
                        document.getElementById('noOfDecimal').value = data.data.no_of_decimal;
                    });
            }
            // Open modal programmatically
            if (modalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
                modalInstance.show();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips for currency action buttons with reduced gap
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                // Wait a bit to ensure DOM is fully ready
                setTimeout(function() {
                    // First, dispose any incorrectly initialized tooltips outside the table
                    const allTooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                    allTooltipElements.forEach(function(element) {
                        // If it's not in the table tbody and has a tooltip instance, dispose it
                        if (!element.closest('.table-responsive tbody')) {
                            const tooltipInstance = bootstrap.Tooltip.getInstance(element);
                            if (tooltipInstance && (element.classList.contains('currency-edit-btn') || element.classList.contains('currency-delete-btn'))) {
                                tooltipInstance.dispose();
                            }
                        }
                    });

                    // Only select buttons within the table tbody to avoid selecting wrong elements
                    const tableBody = document.querySelector('.table-responsive tbody');
                    if (!tableBody) return;

                    const editButtons = tableBody.querySelectorAll('.currency-edit-btn[data-bs-toggle="tooltip"]');
                    const deleteButtons = tableBody.querySelectorAll('.currency-delete-btn[data-bs-toggle="tooltip"]');

                    // Initialize edit button tooltips - each button gets its own instance
                    editButtons.forEach(function(button) {
                        // Verify this is actually an edit button in the table
                        if (!button.classList.contains('currency-edit-btn') || !button.closest('tbody')) {
                            return;
                        }
                        // Dispose any existing tooltip instance
                        const existingTooltip = bootstrap.Tooltip.getInstance(button);
                        if (existingTooltip) {
                            existingTooltip.dispose();
                        }
                        // Create new tooltip instance for this specific button
                        try {
                            new bootstrap.Tooltip(button, {
                                placement: 'top',
                                offset: [0, 2],
                                trigger: 'hover focus',
                                boundary: 'viewport',
                                container: 'body'
                            });
                        } catch (e) {
                            console.warn('Failed to initialize tooltip for edit button:', e);
                        }
                    });

                    // Initialize delete button tooltips - each button gets its own instance
                    deleteButtons.forEach(function(button) {
                        // Verify this is actually a delete button in the table
                        if (!button.classList.contains('currency-delete-btn') || !button.closest('tbody')) {
                            return;
                        }
                        // Dispose any existing tooltip instance
                        const existingTooltip = bootstrap.Tooltip.getInstance(button);
                        if (existingTooltip) {
                            existingTooltip.dispose();
                        }
                        // Create new tooltip instance for this specific button
                        try {
                            new bootstrap.Tooltip(button, {
                                placement: 'top',
                                offset: [0, 2],
                                trigger: 'hover focus',
                                boundary: 'viewport',
                                container: 'body'
                            });
                        } catch (e) {
                            console.warn('Failed to initialize tooltip for delete button:', e);
                        }
                    });
                }, 100);

                // Re-initialize tooltips when dev tools inspect elements (only if tooltips are missing)
                let reinitTimeout = null;
                const observer = new MutationObserver(function(mutations) {
                    // Clear any pending reinit
                    if (reinitTimeout) {
                        clearTimeout(reinitTimeout);
                    }

                    // Debounce to avoid rapid re-initialization
                    reinitTimeout = setTimeout(function() {
                        // Only select buttons within the table tbody
                        const tableBody = document.querySelector('.table-responsive tbody');
                        if (!tableBody) return;

                        const editButtons = tableBody.querySelectorAll('.currency-edit-btn[data-bs-toggle="tooltip"]');
                        const deleteButtons = tableBody.querySelectorAll('.currency-delete-btn[data-bs-toggle="tooltip"]');

                        // Check if any tooltips are missing
                        let needsReinit = false;
                        editButtons.forEach(function(button) {
                            if (!button.closest('tbody')) return;
                            if (!bootstrap.Tooltip.getInstance(button)) {
                                needsReinit = true;
                            }
                        });
                        deleteButtons.forEach(function(button) {
                            if (!button.closest('tbody')) return;
                            if (!bootstrap.Tooltip.getInstance(button)) {
                                needsReinit = true;
                            }
                        });

                        // Only reinit if tooltips are actually missing
                        if (needsReinit) {
                            editButtons.forEach(function(button) {
                                if (!button.closest('tbody')) return;
                                if (!bootstrap.Tooltip.getInstance(button)) {
                                    try {
                                        new bootstrap.Tooltip(button, {
                                            placement: 'top',
                                            offset: [0, 2],
                                            trigger: 'hover focus',
                                            boundary: 'viewport',
                                            container: 'body'
                                        });
                                    } catch (e) {
                                        console.warn('Failed to reinit tooltip for edit button:', e);
                                    }
                                }
                            });

                            deleteButtons.forEach(function(button) {
                                if (!button.closest('tbody')) return;
                                if (!bootstrap.Tooltip.getInstance(button)) {
                                    try {
                                        new bootstrap.Tooltip(button, {
                                            placement: 'top',
                                            offset: [0, 2],
                                            trigger: 'hover focus',
                                            boundary: 'viewport',
                                            container: 'body'
                                        });
                                    } catch (e) {
                                        console.warn('Failed to reinit tooltip for delete button:', e);
                                    }
                                }
                            });
                        }
                    }, 500);
                });

                // Only observe the table body for changes
                const tableBody = document.querySelector('.table-responsive tbody');
                if (tableBody) {
                    observer.observe(tableBody, {
                        childList: true,
                        subtree: true  // Need subtree to watch for changes in rows
                    });
                }
            }

            const deleteForms = document.querySelectorAll('.delete-form');

            deleteForms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault(); // Prevent the form from submitting normally

                    // Get currency name from form data attribute
                    const currencyName = form.getAttribute('data-currency-name') || 'currency';
                    const confirmMessage = `Are you sure you want to delete this ${currencyName} currency?`;

                    Swal.fire({
                        title: '{{ __('messages.are_you_sure') }}',
                        text: confirmMessage,

                        icon: 'warning',
                        iconHtml: '<i class="fa fa-trash" style="color:#A52A2A;"></i>',

                        showCancelButton: true,
                        confirmButtonColor: '#A52A2A',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '{{ __('messages.yes_delete_it') }}',
                        cancelButtonText: '{{ __('messages.cancel') }}',
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Submit via AJAX
                            const formData = new FormData(form);
                            const formAction = form.getAttribute('action');
                            const formMethod = form.getAttribute('method') || 'POST';
                            
                            fetch(formAction, {
                                method: formMethod,
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status) {
                                    Swal.fire({
                                        title: window.localMessagesUpdate?.messages?.deleted || 'Deleted',
                                        text: data.message,
                                        icon: 'success',
                                        confirmButtonText: window.localMessagesUpdate?.messages?.ok || 'OK'
                                    }).then(() => {
                                        location.reload();
                                    });
                                } else {
                                    // Handle error with custom title if available
                                    const errorTitle = data.error_title || window.localMessagesUpdate?.messages?.error || 'Error';
                                    Swal.fire({
                                        title: errorTitle,
                                        text: data.message,
                                        icon: 'error',
                                        confirmButtonText: window.localMessagesUpdate?.messages?.ok || 'OK'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                Swal.fire({
                                    title: window.localMessagesUpdate?.messages?.error || 'Error',
                                    text: 'An error occurred while deleting the currency.',
                                    icon: 'error',
                                    confirmButtonText: window.localMessagesUpdate?.messages?.ok || 'OK'
                                });
                            });
                        }
                    });
                });
            });
        });



        function clearValidationErrors() {
            // Clear invalid-feedback divs
            const invalidFeedbackElements = document.querySelectorAll('.invalid-feedback');
            invalidFeedbackElements.forEach(element => {
                element.textContent = '';
                element.style.display = 'none';
            });

            // Clear text-danger error spans (but not asterisks)
            const errorElements = document.querySelectorAll('.text-danger');
            errorElements.forEach(element => {
                // Only hide if it's an error message (has text content or is in a form-group but not just an asterisk)
                if (element.closest('.form-group')) {
                    // Check if it's just an asterisk (contains only '*' or is empty)
                    const text = element.textContent.trim();
                    if (text && text !== '*') {
                        // It's an error message, clear it
                        element.textContent = '';
                        element.style.display = 'none';
                    } else if (text === '*') {
                        // It's an asterisk, make sure it's visible
                        element.style.display = '';
                    }
                }
            });
        }

        // Add form submission validation to check for duplicates
        document.addEventListener('DOMContentLoaded', function() {
            const currencyForm = document.getElementById('currencyForm');
            if (currencyForm) {
                // Use global flag to persist across modal opens
                window.currencyFormSubmitFlag = false;

                // Clear validation errors when user starts typing/selecting
                const fieldErrorMap = {
                    'currencyName': 'currency-name-error',
                    'currencySymbol': 'currency-symbol-error',
                    'currencyCode': 'currency-code-error',
                    'thousandSeparator': 'thousand-separator-error',
                    'decimalSeparator': 'decimal-separator-error',
                    'noOfDecimal': 'no-of-decimal-error'
                };

                Object.keys(fieldErrorMap).forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        const errorElementId = fieldErrorMap[fieldId];
                        field.addEventListener('input', function() {
                            if (this.value.trim()) {
                                this.classList.remove('is-invalid');
                                const errorElement = document.getElementById(errorElementId);
                                if (errorElement) {
                                    errorElement.textContent = '';
                                    errorElement.style.display = 'none';
                                }
                            }
                        });
                        field.addEventListener('change', function() {
                            if (this.value.trim()) {
                                this.classList.remove('is-invalid');
                                const errorElement = document.getElementById(errorElementId);
                                if (errorElement) {
                                    errorElement.textContent = '';
                                    errorElement.style.display = 'none';
                                }
                            }
                        });
                    }
                });

                currencyForm.addEventListener('submit', function(e) {
                    // If already submitting (after validation), allow normal submission
                    if (window.currencyFormSubmitFlag) {
                        return true;
                    }

                    e.preventDefault();

                    const currencyName = document.getElementById('currencyName').value.trim();
                    const currencyCode = document.getElementById('currencyCode').value.trim();
                    const currencySymbol = document.getElementById('currencySymbol').value.trim();
                    const thousandSeparator = document.getElementById('thousandSeparator').value.trim();
                    const decimalSeparator = document.getElementById('decimalSeparator').value.trim();
                    const noOfDecimal = document.getElementById('noOfDecimal').value.trim();

                    const formMethod = currencyForm.querySelector('input[name="_method"]').value;
                    const formAction = currencyForm.action;

                    // Extract currency ID from update URL if editing
                    let currencyId = null;
                    if (formMethod === 'PUT') {
                        const match = formAction.match(/\/currencies\/(\d+)/);
                        if (match) {
                            currencyId = match[1];
                        }
                    }

                    // Clear previous errors
                    clearValidationErrors();

                    // Remove invalid classes from all fields
                    const formFields = ['currencyName', 'currencyCode', 'currencySymbol', 'thousandSeparator', 'decimalSeparator', 'noOfDecimal'];
                    formFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (field) {
                            field.classList.remove('is-invalid');
                        }
                    });

                    // Validate required fields
                    let hasValidationErrors = false;
                    const requiredFields = {
                        'currencyName': '{{ __('currency.lbl_currency_name') }}',
                        'currencySymbol': '{{ __('currency.lbl_currency_symbol') }}',
                        'currencyCode': '{{ __('currency.lbl_currency_code') }}',
                        'thousandSeparator': '{{ __('currency.lbl_thousand_separatorn') }}',
                        'decimalSeparator': '{{ __('currency.lbl_decimal_separator') }}',
                        'noOfDecimal': '{{ __('currency.lbl_number_of_decimals') }}'
                    };

                    // Check currency name
                    if (!currencyName) {
                        const errorElement = document.getElementById('currency-name-error');
                        errorElement.textContent = '{{ __('messages.name_required') }}';
                        errorElement.style.display = 'block';
                        document.getElementById('currencyName').classList.add('is-invalid');
                        hasValidationErrors = true;
                    }

                    // Check currency symbol
                    if (!currencySymbol) {
                        const errorElement = document.getElementById('currency-symbol-error');
                        errorElement.textContent = '{{ __('currency.lbl_currency_symbol') }} is required.';
                        errorElement.style.display = 'block';
                        document.getElementById('currencySymbol').classList.add('is-invalid');
                        hasValidationErrors = true;
                    }

                    // Check currency code
                    if (!currencyCode) {
                        const errorElement = document.getElementById('currency-code-error');
                        errorElement.textContent = '{{ __('currency.lbl_currency_code') }} is required.';
                        errorElement.style.display = 'block';
                        document.getElementById('currencyCode').classList.add('is-invalid');
                        hasValidationErrors = true;
                    }

                    // Check thousand separator
                    if (!thousandSeparator) {
                        const errorElement = document.getElementById('thousand-separator-error');
                        errorElement.textContent = '{{ __('currency.lbl_thousand_separatorn') }} is required.';
                        errorElement.style.display = 'block';
                        document.getElementById('thousandSeparator').classList.add('is-invalid');
                        hasValidationErrors = true;
                    }

                    // Check decimal separator
                    if (!decimalSeparator) {
                        const errorElement = document.getElementById('decimal-separator-error');
                        errorElement.textContent = '{{ __('currency.lbl_decimal_separator') }} is required.';
                        errorElement.style.display = 'block';
                        document.getElementById('decimalSeparator').classList.add('is-invalid');
                        hasValidationErrors = true;
                    }

                    // Check number of decimals
                    if (!noOfDecimal) {
                        const errorElement = document.getElementById('no-of-decimal-error');
                        errorElement.textContent = '{{ __('currency.lbl_number_of_decimals') }} is required.';
                        errorElement.style.display = 'block';
                        document.getElementById('noOfDecimal').classList.add('is-invalid');
                        hasValidationErrors = true;
                    }

                    // If validation errors exist, stop here
                    if (hasValidationErrors) {
                        return false;
                    }

                    // Check for duplicates via AJAX
                    fetch('{{ route("backend.currencies.checkDuplicate") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            currency_name: currencyName,
                            currency_code: currencyCode,
                            currency_symbol: currencySymbol,
                            currency_id: currencyId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        let hasErrors = false;

                        if (data.exists.currency_name) {
                            const errorElement = document.getElementById('currency-name-error');
                            errorElement.textContent = 'This currency name already exists. Please choose a different currency.';
                            errorElement.style.display = 'block';
                            document.getElementById('currencyName').classList.add('is-invalid');
                            hasErrors = true;
                        }

                        if (data.exists.currency_code) {
                            const errorElement = document.getElementById('currency-code-error');
                            errorElement.textContent = 'This currency code already exists. Please choose a different code.';
                            errorElement.style.display = 'block';
                            document.getElementById('currencyCode').classList.add('is-invalid');
                            hasErrors = true;
                        }

                        if (data.exists.currency_symbol) {
                            const errorElement = document.getElementById('currency-symbol-error');
                            errorElement.textContent = 'This currency symbol already exists. Please choose a different symbol.';
                            errorElement.style.display = 'block';
                            document.getElementById('currencySymbol').classList.add('is-invalid');
                            hasErrors = true;
                        }

                        if (!hasErrors) {
                            // No duplicates found, allow form submission
                            window.currencyFormSubmitFlag = true;
                            currencyForm.submit();
                        }
                    })
                    .catch(error => {
                        console.error('Error checking duplicates:', error);
                        // If check fails, still submit the form (backend will validate)
                        window.currencyFormSubmitFlag = true;
                        currencyForm.submit();
                    });
                });
            }
        });
    </script>

@endsection
    
