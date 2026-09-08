@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <div class="card-main mb-5">
        <x-backend.section-header>
            <div class="d-flex flex-wrap gap-3">

                <x-backend.quick-action url="{{ route('backend.' . $module_name . '.bulk_action') }}"  :entity_name="__('messages.lbl_video')" :entity_name_plural="__('messages.lbl_videos')">
                    <div class="">
                        <select name="action_type" class="form-control select2 col-12" id="quick-action-type"
                            style="width:100%">
                            <option value="">{{ __('messages.no_action') }}</option>

                            <option value="change-status">{{ __('messages.lbl_status') }}</option>

                            @hasPermission('delete_videos')
                                <option value="delete">{{ __('messages.delete') }}</option>
                            @endhasPermission
                            @hasPermission('restore_videos')
                                <option value="restore">{{ __('messages.restore') }}</option>
                            @endhasPermission
                            @hasPermission('force_delete_videos')
                                <option value="permanently-delete">{{ __('messages.permanent_dlt') }}</option>
                            @endhasPermission
                        </select>
                    </div>
                    <div class="select-status d-none quick-action-field" id="change-status-action">
                        <select name="status" class="form-control select2" id="status" style="width:100%">
                            <option value="" selected>{{ __('messages.select_status') }}</option>
                            <option value="1">{{ __('messages.active') }}</option>
                            <option value="0">{{ __('messages.inactive') }}</option>
                        </select>
                    </div>
                </x-backend.quick-action>


                <div>
                    <button type="button" class="btn btn-dark" data-modal="export">
                        <i class="ph ph-export align-middle"></i> {{ __('messages.export') }}
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#importModal"
                        data-type="video">
                        <i class="ph ph-download-simple align-middle"></i> {{ __('messages.import') }}
                    </button>
                </div>
            </div>

            <x-slot name="toolbar">

                <div>
                    <div class="datatable-filter">
                        <select name="column_status" id="column_status" class="select2 form-control" data-filter="select"
                            style="width: 100%">
                            <option value="">{{ __('messages.all') }}</option>
                            <option value="0" {{ $filter['status'] == '0' ? 'selected' : '' }}>
                                {{ __('messages.inactive') }}</option>
                            <option value="1" {{ $filter['status'] == '1' ? 'selected' : '' }}>
                                {{ __('messages.active') }}</option>
                        </select>
                    </div>
                </div>
                <div class="input-group flex-nowrap">
                    <span class="input-group-text pe-0" id="addon-wrapping"><i
                            class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" class="form-control dt-search" placeholder="{{ __('placeholder.lbl_search') }}"
                        aria-label="Search" aria-describedby="addon-wrapping">
                </div>
                <button class="btn btn-dark d-flex align-items-center gap-1 btn-group" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasExample" aria-controls="offcanvasExample"><i
                        class="ph ph-funnel"></i>{{ __('messages.advance_filter') }}</button>
                @hasPermission('add_videos')
                    <a href="{{ route('backend.' . $module_name . '.create') }}"
                        class="btn btn-primary d-flex align-items-center gap-1" id="add-post-button"><i
                            class="ph ph-plus-circle"></i>{{ __('messages.new') }}</a>
                @endhasPermission

            </x-slot>
        </x-backend.section-header>
        <table id="datatable" class="table table-responsive">
        </table>
    </div>

    <x-backend.advance-filter>
        <x-slot name="title">
            <h4 class="mb-0">{{ __('messages.advance_filter') }}</h4>
        </x-slot>

        <div class="form-group datatable-filter">
            <label class="form-label" for="video_access">{{ __('season.access') }}</label>
            <div class="d-flex flex-wrap flex-column gap-3">
                <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                    <input class="form-check-input" type="radio" name="video_access" id="video_paid" value="paid"
                        onchange="showPlanSelection(this.value === 'paid')">
                    <label class="form-check-label" for="video_paid">{{ __('movie.lbl_paid') }}</label>
                </label>
                <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                    <input class="form-check-input" type="radio" name="video_access" id="video_free" value="free"
                        onchange="showPlanSelection(this.value === 'paid')">
                    <label class="form-check-label" for="video_free">{{ __('movie.lbl_free') }}</label>
                </label>
                <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                    <input class="form-check-input" type="radio" name="video_access" id="video_pay_per_view"
                        value="pay-per-view" onchange="showPlanSelection(this.value === 'paid')">
                    <label class="form-check-label" for="video_pay_per_view">{{ __('messages.lbl_pay_per_view') }}</label>
                </label>
            </div>
        </div>

        <div class="form-group datatable-filter d-none" id="planSelection">
            <label class="form-label" for="plan_id">{{ __('season.plan') }}</label>
            <select name="plan_id" id="plan_id" class="form-control select2" data-filter="select">
                <option value="">{{ __('messages.all') }} {{ __('season.plan') }}</option>
                @foreach ($plan as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="text-end">
            <button type="reset" class="btn btn-dark" id="reset-filter">{{ __('messages.reset') }}</button>
        </div>
    </x-backend.advance-filter>

    @if (session('success'))
        <div class="snackbar" id="snackbar">
            <div class="d-flex justify-content-around align-items-center">
                <p class="mb-0">{{ session('success') }}</p>
                <a href="#" class="dismiss-link text-decoration-none text-success"
                    onclick="dismissSnackbar(event)">{{ __('messages.dismiss') }}</a>
            </div>
        </div>
    @endif
    @include('entertainment::components.import-modal')
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
                title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="video" onclick="selectAllTable(this)">',
                width: '0%',
                exportable: false,
                orderable: false,
                searchable: false,
            },
            {
                data: 'name',
                name: 'name',
                title: "{{ __('messages.name') }}",
                visible: false
            },
            {
                data: 'poster_url',
                name: 'poster_url',
                title: "{{ __('video.singular_title') }}",
                orderable: false,
                searchable: false,
            },
            {
                data: 'like_count',
                name: 'like_count',
                title: "{{ __('movie.likes') }}",
                orderable: true,
            },
            {
                data: 'watch_count',
                name: 'watch_count',
                title: "{{ __('movie.watch') }}",
                orderable: true,
            },
            {
                data: 'access',
                name: 'access',
                title: "{{ __('season.access') }}",
                render: function(data, type, row) {
                    let capitalizedData = "";
                    switch (data) {
                        case 'pay-per-view':
                            capitalizedData = "{{ __('messages.pay_per_view') }}";
                            break;
                        case 'paid':
                            capitalizedData = "{{ __('messages.paid') }}";
                            break;
                        case 'free':
                            capitalizedData = "{{ __('messages.free') }}";
                            break;
                        default:
                            capitalizedData = data.charAt(0).toUpperCase() + data.slice(1);
                            break;
                    }
                    let className = data == 'free' ? 'badge bg-info-subtle p-2'  : 'badge bg-success-subtle p-2';
                    return '<span class="' + className + '">' + capitalizedData + '</span>';
                }
            },
            {
                data: 'plan_id',
                name: 'plan_id',
                title: "{{ __('season.plan') }}"
            },
            {
                data: 'status',
                name: 'status',
                title: "{{ __('messages.lbl_status') }}",
                width: '5%',
            },
            {
                data: 'is_restricted',
                name: 'is_restricted',
                title: "{{ __('movie.lbl_restricted_content') }}",
                width: '5%',
            },

            {
                data: 'updated_at',
                name: 'updated_at',
                title: "{{ __('messages.update_at') }}",
                orderable: true,
                visible: false,
            },
        ]

        const actionColumn = [{
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false,
            title: "{{ __('messages.action') }}",
            width: '5%'
        }]

        let finalColumns = [
            ...columns,
            ...actionColumn
        ]

        document.addEventListener('DOMContentLoaded', (event) => {
            initDatatable({
                url: '{{ route("backend.$module_name.index_data") }}',
                finalColumns,
                orderColumn: [
                    [9, "desc"]
                ],
                advanceFilter: () => {
                    return {
                        video_access: $('input[name="video_access"]:checked').val(),
                        plan_id: $('#plan_id').val()
                    }
                }
            });
        })

        $('#reset-filter').on('click', function(e) {
            $('input[name="video_access"]').prop('checked', false);
            $('#plan_id').val(null).trigger('change');
            $('#planSelection').addClass('d-none');
            if (window.renderedDataTable) {
                window.renderedDataTable.ajax.reload(null, false);
            }
            // Update filter count badge after reset
            if (typeof updateFilterCountBadge === 'function') {
                setTimeout(function() {
                    updateFilterCountBadge();
                }, 200);
            }
        })

        function resetQuickAction() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');

                if (actionValue == 'change-status') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-status-action').removeClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        }

        $('#quick-action-type').change(function() {
            resetQuickAction()
        });

        // Add event listeners for filter changes
        $('#plan_id').on('change', function() {
            if (window.renderedDataTable) {
                window.renderedDataTable.ajax.reload(null, false);
            }
            // Update filter count badge
            if (typeof updateFilterCountBadge === 'function') {
                setTimeout(function() {
                    updateFilterCountBadge();
                }, 100);
            }
        });

        $('input[name="video_access"]').on('change', function() {
            if (window.renderedDataTable) {
                window.renderedDataTable.ajax.reload(null, false);
            }
            // Update filter count badge
            if (typeof updateFilterCountBadge === 'function') {
                setTimeout(function() {
                    updateFilterCountBadge();
                }, 100);
            }
        });

        function showPlanSelection(isPaid) {
            const planSelectionDiv = document.getElementById('planSelection');
            const planIdSelect = document.getElementById('plan_id');
            if (isPaid) {
                planSelectionDiv.classList.remove('d-none');
            } else {
                planSelectionDiv.classList.add('d-none');
                // Clear plan selection properly (handle select2)
                if ($(planIdSelect).hasClass('select2-hidden-accessible')) {
                    $(planIdSelect).val(null).trigger('change');
                } else {
                    planIdSelect.value = '';
                }
                // Update filter count badge after clearing plan
                if (typeof updateFilterCountBadge === 'function') {
                    setTimeout(function() {
                        updateFilterCountBadge();
                    }, 100);
                }
            }
        }
    </script>
@endpush
