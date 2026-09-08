@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <div class="card-main mb-5">
        <x-backend.section-header>
            <div class="d-flex flex-wrap gap-3">
                <x-backend.quick-action url="{{ route('backend.' . $module_name . '.bulk_action') }}"  :entity_name="__('messages.lbl_season')" :entity_name_plural="__('messages.lbl_seasons')">
                    <div class="">
                        <select name="action_type" class="form-control select2 col-12" id="quick-action-type"
                            style="width:100%">
                            <option value="">{{ __('messages.no_action') }}</option>
                            <option value="change-status">{{ __('messages.lbl_status') }}</option>
                            @hasPermission('delete_seasons')
                                <option value="delete">{{ __('messages.delete') }}</option>
                            @endhasPermission
                            @hasPermission('restore_seasons')
                                <option value="restore">{{ __('messages.restore') }}</option>
                            @endhasPermission
                            @hasPermission('force_delete_seasons')
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
                        data-type="season">
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
                    <input type="text" class="form-control dt-search" placeholder={{ __('placeholder.lbl_search') }}
                        aria-label="Search" aria-describedby="addon-wrapping">
                </div>
                <button class="btn btn-dark d-flex align-items-center gap-1 btn-group" data-bs-toggle="offcanvas"
                    data-bs-target="#offcanvasExample" aria-controls="offcanvasExample"><i
                        class="ph ph-funnel"></i>{{ __('messages.advance_filter') }}</button>
                @hasPermission('add_' . $module_name)
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

        <div class="form-group">



            <div class="form-group datatable-filter">
                <label class="form-label" for="entertainment_id">{{ __('movie.lbl_tv_show') }}</label>
                <select name="entertainment_id" id="entertainment_id" class="form-control select2" data-filter="select">
                    <option value="">{{ __('messages.all') }} {{ __('movie.lbl_tv_show') }}</option>
                    @foreach ($tvshows as $tvshow)
                        <option value="{{ $tvshow->id }}">{{ $tvshow->name }}</option>
                    @endforeach
                </select>
            </div>

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
                title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="season" onclick="selectAllTable(this)">',
                width: '0%',
                exportable: false,
                orderable: false,
                searchable: false,
            },
            {
                data: 'poster_url',
                name: 'poster_url',
                title: "{{ __('episode.lbl_season') }}",
                searchable: false,
            },
            {
                data: 'name',
                name: 'name',
                title: "{{ __('messages.name') }}",
                visible: false
            },
            {
                data: 'entertainment_id',
                name: 'entertainment_id',
                title: "{{ __('movie.lbl_tv_show') }}"
            },
            {
                data: 'status',
                name: 'status',
                title: "{{ __('messages.lbl_status') }}",
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

            $('#name').on('input', function() {
                window.renderedDataTable.ajax.reload(null, false);
            });


            $('#entertainment_id').on('change', function() {
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

            initDatatable({
                url: '{{ route("backend.$module_name.index_data") }}',
                finalColumns,
                // Order by updated_at column (index 5: check, poster_url, name, tv show, status, updated_at)
                orderColumn: [
                    [5, "desc"]
                ],
                advanceFilter: () => {
                    return {
                        name: $('#name').val(),
                        entertainment_id: $('#entertainment_id').val(),
                    }
                }
            });
        })

        $('#reset-filter').on('click', function(e) {
            $('#entertainment_id').val(null).trigger('change');
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



    </script>
@endpush
