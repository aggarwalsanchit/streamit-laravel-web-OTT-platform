@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <div class="card-main mb-5">
        <x-backend.section-header>
            <div class="d-flex flex-wrap gap-3">
                <button type="button" class="btn btn-dark" data-modal="export">
                    <i class="ph ph-export align-middle"></i> {{ __('messages.export') }}
                </button>
            </div>
            <x-slot name="toolbar">
                <button id="send-email-btn" class="btn btn-primary">{{ __('messages.send_reminder') }}</button>
                <div class="input-group flex-nowrap">
                    <span class="input-group-text pe-0" id="addon-wrapping"><i
                            class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control dt-search" placeholder="{{ __('messages.search') }}..."
                        aria-label="Search" aria-describedby="addon-wrapping">
                </div>
            </x-slot>
        </x-backend.section-header>
        <table id="datatable" class="table table-responsive">
        </table>
    </div>
    <!-- Success Message Container -->
    <div id="success-message" class="alert alert-success"
        style="display: none; text-align: center; width: auto; position: fixed; top: 0; right: 0; margin: 50px;">
        <strong>{{ __('messages.mail_success') }}</strong> {{ __('messages.mail_send') }}
    </div>

    @if (session('success'))
        <div class="snackbar" id="snackbar">
            <div class="d-flex justify-content-around align-items-center">
                <p class="mb-0">{{ session('success') }}</p>
                <a href="#" class="dismiss-link text-decoration-none text-success"
                    onclick="dismissSnackbar(event)">{{ __('messages.dismiss') }}</a>
            </div>
        </div>
    @endif
@endsection

@push('after-styles')
    <!-- DataTables Core and Extensions -->
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush
@push('after-scripts')
    <!-- DataTables Core and Extensions -->
    <script src="{{ asset('js/form-modal/index.js') }}" defer></script>
    <script src="{{ asset('js/form/index.js') }}" defer></script>
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>

    <script type="text/javascript" defer>
        const columns = [{
                name: 'check',
                data: 'check',
                title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="users" onclick="selectAllTable(this)">',
                width: '0%',
                exportable: false,
                orderable: false,
                searchable: false,
            },
            {
                data: 'name',
                name: 'name',
                title: "{{ __('users.lbl_user') }}"
            },
            {
                data: 'mobile',
                name: 'mobile',
                title: "{{ __('users.lbl_contact_number') }}"
            },
            {
                data: 'gender',
                name: 'gender',
                title: "{{ __('users.lbl_gender') }}"
            },
            {
                data: 'plan',
                name: 'plan',
                title: "{{ __('messages.plan') }}"
            },
            {
                data: 'duration',
                name: 'duration',
                title: "{{ __('dashboard.duration') }}"
            },
            {
                data: 'subscription_start_date',
                name: 'subscription_start_date',
                title: "{{ __('messages.start_date') }}"
            },
            {
                data: 'payment_method',
                name: 'payment_method',
                title: "{{ __('messages.payment_method') }}"
            },
            {
                data: 'subscription_amount',
                name: 'subscription_amount',
                title: "{{ __('dashboard.amount') }}"
            },
            {
                data: 'expire_date',
                name: 'expire_date',
                title: "{{ __('messages.end_date') }}"
            },
            {
                data: 'updated_at',
                name: 'updated_at',
                title: "{{ __('users.lbl_update_at') }}",
                orderable: true,
                visible: false,
            },
        ];

        const finalColumns = [...columns];

        document.addEventListener('DOMContentLoaded', (event) => {
            initDatatable({
                url: '{{ route('backend.soon-to-expire-users.index_data') }}',
                finalColumns,
                orderColumn: [
                    [10, "desc"]
                ],
            });

            const datatable = $('#datatable').DataTable();

            datatable.on('draw', function() {
                const rowCount = datatable.rows().count();
                if (rowCount === 0) {
                    document.getElementById('send-email-btn').style.display = 'none';
                }
            });
        });

        function showMessage(message) {
            Snackbar.show({
                text: message,
                pos: 'bottom-left'
            });
        }

        $(document).ready(function() {
            $('#send-email-btn').click(function() {
                const confirmationMessage = "{{ __('messages.sure_to_send_email') }}";
                Swal.fire({
                    title: confirmationMessage,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('messages.yes_send_it') }}",
                    cancelButtonText: "{{ __('messages.cancel') }}",
                    reverseButtons: true,
                    showClass: {
                        popup: 'animate__animated animate__zoomIn'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__zoomOut'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        sendEmail();
                    }
                });
            });

            function sendEmail() {
                var csrfToken = $('meta[name="csrf-token"]').attr('content');
                const $btn = $('#send-email-btn');
                const originalText = $btn.text();
                $btn.data('original-text', originalText);
                $btn.prop('disabled', true);
                $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + "{{ __('messages.loading') }}");
                $.ajax({
                    url: '{{ route('backend.soon-to-expire-users.send-email') }}',
                    type: 'POST',
                    data: {
                        _token: csrfToken
                    },
                    success: function(response) {
                        showMessage(response.message);
                        $btn.prop('disabled', false);
                        const orig = $btn.data('original-text') || "{{ __('messages.send _reminder') }}";
                        $btn.text(orig);
                    },
                    error: function(xhr, status, error) {
                        $btn.prop('disabled', false);
                        const orig = $btn.data('original-text') || "{{ __('messages.send _reminder') }}";
                        $btn.text(orig);
                    }
                });
            }
        });
    </script>
@endpush

