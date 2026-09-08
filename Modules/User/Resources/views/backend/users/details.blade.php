@extends('backend.layouts.app')
@section('title')
    {{ __('users.user_details') }}
@endsection

@section('content')
    <x-back-button-component route="backend.users.index" />
    <div class="card">
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-3">
                    <div class="poster">

                        <img src="{{ setBaseUrlWithFileName($data->file_url, 'image', 'users') }}"
                            alt="{{ $data->first_name }}" class="img-fluid w-100 rounded">
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="details">
                        <h1 class="mb-4">{{ $data->first_name ?? '--' }} {{ $data->last_name ?? '--' }}</h1>
                        <div class="d-flex align-items-center gap-3 gap-xl-5 flex-wrap">
                            @if (!empty($data->email))
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="m-0 d-flex align-items-center gap-1"><i class="ph ph-envelope"></i></h6>
                                    <p class="mb-0">{{ $data->email }}</p>
                                </div>
                            @endif
                            @if (!empty($data->mobile))
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="m-0 d-flex align-items-center gap-1"><i class="ph ph-phone-call"></i></h6>
                                    <p class="mb-0">{{ $data->mobile }}</p>
                                </div>
                            @endif
                        </div>
                        @if (!empty($data->gender) || !empty($data->date_of_birth))
                            <hr class="my-5 border-bottom-0">
                            <div class="user-info">
                                <div class="d-flex align-items-center gap-3 gap-xl-5 flex-wrap">
                                    @if (!empty($data->gender))
                                        <div class="d-flex align-items-center gap-2">
                                            <h6 class="m-0 d-flex align-items-center gap-1"><i
                                                    class="ph ph-gender-neuter"></i></h6>
                                            <p class="mb-0">{{ ucfirst($data->gender) }}</p>
                                        </div>
                                    @endif
                                    @if (!empty($data->date_of_birth))
                                        <div class="d-flex align-items-center gap-2">
                                            <h6 class="m-0 d-flex align-items-center gap-1"><i
                                                    class="ph ph-calendar"></i></h6>
                                            <p class="mb-0">
                                                {{ formatDate(date('Y-m-d', strtotime($data->date_of_birth))) }}
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if (!empty($data->address))
                            <hr class="my-5 border-bottom-0">
                            <div class="address d-flex align-items-center gap-2">
                                <h5 class="m-0 d-flex align-items-center gap-1"><i class="ph ph-map-pin"></i></h5>
                                <p class="mb-0 text-wrap">{{ $data->address }}</p>
                            </div>
                        @endif
                        @if (
                            !empty($data->email) ||
                                !empty($data->mobile) ||
                                !empty($data->gender) ||
                                !empty($data->date_of_birth) ||
                                !empty($data->address))
                            <hr class="my-5 border-bottom-0">
                        @endif
                    </div>
                </div>

                @if (!request('type'))
                    <div class="col-12">
                        <ul class="nav nav-pills" id="historyTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-2 px-3" id="subscription-tab" data-bs-toggle="tab" data-bs-target="#subscription" type="button" role="tab" aria-controls="subscription" aria-selected="true">
                                    {{ __('users.lbl_subscription_details') }}
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-2 px-3" id="rent-tab" data-bs-toggle="tab" data-bs-target="#rent" type="button" role="tab" aria-controls="rent" aria-selected="false" tabindex="-1">
                                    {{ __('messages.rent_details') }}
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content" id="historyTabsContent">
                            <div class="tab-pane fade show active" id="subscription" role="tabpanel">
                                <div class="mt-3">
                                    <table id="subscription-datatable" class="table table-responsive">
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="rent" role="tabpanel">
                                <div class="mt-3">
                                    <table id="rent-datatable" class="table table-responsive">
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif (request('type') != 'rent')
                    {{-- Show only subscription when type is not rent --}}
                    <div class="subscription-details">
                        <h5 class="mb-3">{{ __('users.lbl_subscription_details') }}</h5>
                        <table id="datatable" class="table table-responsive">
                        </table>
                    </div>
                @elseif (request('type') == 'rent')
                    {{-- Show only rent when type is rent --}}
                    <div class="rent-details">
                        <h5 class="mb-3">{{ __('messages.rent_details') }}</h5>
                        <table id="datatable" class="table table-responsive">
                        </table>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection

@push('after-styles')
    <!-- DataTables Core and Extensions -->
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
    <!-- DataTables Core and Extensions -->
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
    <!-- DataTables Core and Extensions -->
    <script src="{{ asset('js/form-modal/index.js') }}" defer></script>
    <script src="{{ asset('js/form/index.js') }}" defer></script>


    <script type="text/javascript" defer>
        const subscriptionColumns = [{
                data: 'name',
                name: 'name',
                title: "{{ __('dashboard.plan') }}",
                orderable: false,
            },
            {
                data: 'start_date',
                name: 'start_date',
                title: "{{ __('users.date') }}",
                orderable: false,
            },
            {
                data: 'amount',
                name: 'amount',
                title: "{{ __('dashboard.amount') }}",
                orderable: false,
            },
            {
                data: 'coupon_discount',
                name: 'coupon_discount',
                title: "{{ __('frontend.coupon_discount') }}",
                orderable: false,
            },
            {
                data: 'tax_amount',
                name: 'tax_amount',
                title: "{{ __('frontend.tax') }}",
                orderable: false,
            },
            {
                data: 'total_amount',
                name: 'total_amount',
                title: "{{ __('frontend.total') }}",
                orderable: false,
            },
            {
                data: 'duration',
                name: 'duration',
                title: "{{ __('dashboard.duration') }}",
                orderable: false,
            },
            {
                data: 'payment_method',
                name: 'payment_method',
                title: "{{ __('dashboard.payment_method') }}",
                orderable: false,
            },
            {
                data: 'transaction_id',
                name: 'transaction_id',
                title: "{{ __('dashboard.txn_id') }}",
                orderable: false,
            },
            {
                data: 'status',
                name: 'status',
                title: "{{ __('dashboard.status') }}",
                orderable: false,
                render: function(data, type, row) {
                    // Display "Canceled" for cancel/cancelled status
                    if (data && (data.toLowerCase() === 'cancel' || data.toLowerCase() === 'cancelled')) {
                        return '';
                    }
                    return data || '--';
                }
            }
        ];

        const rentColumns = [{
                data: 'name',
                name: 'name',
                title: "{{ __('messages.lbl_content') }}",
                orderable: false,
            },
            {
                data: 'duration',
                name: 'duration',
                title: "{{ __('dashboard.duration') }}",
                orderable: false,
            },
            {
                data: 'payment_method',
                name: 'payment_method',
                title: "{{ __('messages.payment_method') }}",
                orderable: false,
            },
            {
                data: 'start_date',
                name: 'start_date',
                title: "{{ __('messages.start_date') }}",
                orderable: false,
            },
            {
                data: 'end_date',
                name: 'end_date',
                title: "{{ __('messages.end_date') }}",
                orderable: false,
            },
            {
                data: 'amount',
                name: 'amount',
                title: "{{ __('messages.price') }}",
                orderable: false,
            },
            {
                data: 'coupon_discount',
                name: 'coupon_discount',
                title: "{{ __('frontend.discount') }}",
                orderable: false,
            },
            {
                data: 'total_amount',
                name: 'total_amount',
                title: "{{ __('messages.total_amount') }}",
                orderable: false,
            },
        ];

        document.addEventListener('DOMContentLoaded', (event) => {
            const data_table_limit = parseInt($('meta[name="data_table_limit"]').attr('content'), 10) || 10;
            
            const initTable = (tableId, url, columns, orderCol) => {
                const dtMessages = window.localMessagesUpdate?.messages || {};
                const showing = dtMessages.showing || "Showing";
                const to = dtMessages.to || "to";
                const of = dtMessages.of || "of";
                const entries = dtMessages.entries || "entries";
                const show = dtMessages.show || "Show";
                return $('#' + tableId).DataTable({
                    processing: true,
                    serverSide: true,
                    autoWidth: false,
                    responsive: true,
                    fixedHeader: true,
                    pageLength: data_table_limit,
                    lengthMenu: [[10, 20, 25, 50, 100], [10, 20, 25, 50, 100]],
                    language: {
                        processing: window.localMessagesUpdate?.messages?.processing || "Processing...",
                        emptyTable: window.localMessagesUpdate?.messages?.emptyTable || "No data available in table",
                        paginate: {
                            previous: dtMessages.previous || "Previous",
                            next: dtMessages.next || "Next"
                        },
                        lengthMenu: show + " _MENU_ " + entries,
                        info: `${showing} _START_ ${to} _END_ ${of} _TOTAL_ ${entries}`,
                        infoEmpty: `${showing} 0 ${to} 0 ${of} 0 ${entries}`,
                        infoFiltered: ""
                    },
                    dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
                    ajax: {
                        type: "GET",
                        url: url,
                        data: function(d) {
                            d.search = { value: $('.dt-search').val() };
                            d.filter = { column_status: $('#column_status').val() };
                            if ($('#user_name').val()) {
                                d.filter = { ...d.filter, name: $('#user_name').val() };
                            }
                        }
                    },
                    columns: columns,
                    drawCallback: function() {
                        if (typeof window.laravel !== 'undefined') {
                            window.laravel.initialize();
                        }
                        $('#' + tableId + '_wrapper .select2').each(function() {
                            const $select = $(this);
                            if (!$select.hasClass('select2-hidden-accessible')) {
                                $select.select2();
                            }
                        });
                        $('#' + tableId + '_wrapper').find('.dataTables_info').addClass('p-0');
                    }
                });
                $('#' + tableId + '_wrapper .dataTables_filter').addClass('d-none');
            };

            @if (!request('type'))
                // Initialize both tables for tabs
                let subscriptionTable = null;
                let rentTable = null;
                
                // Initialize subscription table immediately (active tab)
                subscriptionTable = initTable('subscription-datatable', '{{ route('backend.users.subscription_data', ['id' => $data->id]) }}', subscriptionColumns, [[1, "desc"]]);
                
                // Initialize rent table when rent tab is shown
                const rentTab = document.getElementById('rent-tab');
                if (rentTab) {
                    rentTab.addEventListener('shown.bs.tab', function() {
                        if (!rentTable) {
                            rentTable = initTable('rent-datatable', '{{ route('backend.users.rent_data', ['id' => $data->id]) }}', rentColumns, [[3, "desc"]]);
                        }
                    });
                }
            @elseif (request('type') == 'rent')
                // Show only rent table
                initDatatable({
                    url: '{{ route('backend.users.rent_data', ['id' => $data->id]) }}',
                    finalColumns: rentColumns,
                    advanceFilter: () => {
                        return {
                            name: $('#user_name').val()
                        }
                    }
                });
            @else
                // Show only subscription table
                initDatatable({
                    url: '{{ route('backend.users.subscription_data', ['id' => $data->id]) }}',
                    finalColumns: subscriptionColumns,
                    advanceFilter: () => {
                        return {
                            name: $('#user_name').val()
                        }
                    }
                });
            @endif
        });
    </script>
@endpush
