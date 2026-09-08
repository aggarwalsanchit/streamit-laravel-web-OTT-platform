@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection


@push('after-styles')
    <link rel="stylesheet" href="{{ mix('modules/subscriptions/style.css') }}">
@endpush
@section('content')

<div class="card-main mb-5">
    <x-backend.section-header>
    <div class="d-flex flex-wrap gap-3">
        <x-backend.quick-action url="{{ route('backend.notifications.bulk_action') }}" :entity_name="__('messages.lbl_notification')" :entity_name_plural="__('messages.lbl_notifications')">
            <div class="">
                <select name="action_type" class="form-control select2 col-12" id="quick-action-type" style="width:100%">
                    <option value="">{{ __('messages.no_action') }}</option>
                    <option value="delete">{{ __('messages.delete') }}</option>
                    {{-- Notifications table does not use soft deletes, so restore/permanent delete are not applicable --}}
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
    </div>

        <x-slot name="toolbar">
            <div>
                <div class="datatable-filter">
                    <select name="column_type" id="column_type" class="select2 form-control"
                        data-filter="select" style="width: 100%">
                        <option value="">{{__('messages.all')}}</option>
                        @php
                            $notificationTypes = \Modules\Constant\Models\Constant::where('type', 'notification_type')->get();
                        @endphp
                        @foreach($notificationTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="input-group flex-nowrap">
                <span class="input-group-text pe-0" id="addon-wrapping"><i
                        class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" class="form-control dt-search" placeholder="{{ __('placeholder.lbl_search') }}"
                    aria-label="Search" aria-describedby="addon-wrapping">
            </div>
        </x-slot>
    </x-backend.section-header>
    <table id="datatable" class="table table-responsive">
    </table>
</div>

@if(session('success'))
<div class="snackbar" id="snackbar">
    <div class="d-flex justify-content-around align-items-center">
        <p class="mb-0">{{ session('success') }}</p>
        <a href="#" class="dismiss-link text-decoration-none text-success" onclick="dismissSnackbar(event)">{{ __('messages.dismiss') }}</a>
    </div>
</div>
@endif

@endsection

@push('after-styles')

    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
<script src="{{ asset('js/form-modal/index.js') }}" defer></script>
<script src="{{ asset('js/form/index.js') }}" defer></script>

    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
    <script type="text/javascript" defer>

const columns = [
            {
                name: 'check',
                data: 'check',
                title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="notifications"  onclick="selectAllTable(this)">',
                width: '0%',
                exportable: false,
                orderable: false,
                searchable: false,
            },
            // { data: 'id', name: 'id',title: "{{ __('notification.lbl_id') }}" ,orderable: false, searchable: false, },
            { data: 'type', name: 'type',title: "{{ __('notification.type') }}" ,orderable: true, searchable: false, },
            { data: 'text', name: 'text',title: "{{ __('notification.lbl_text') }}" ,orderable: false, searchable: false, },
            { data: 'customer', name: 'customer',title: "{{ __('notification.lbl_customer') }}" ,orderable: true, searchable: false, },
            { data: 'updated_at', name: 'updated_at',title: "{{ __('messages.update_at') }}" ,orderable: true, searchable: false, },

        ]

        const actionColumn = [
            { data: 'action', name: 'action', orderable: false, searchable: false, title: "{{ __('messages.action') }}" }
        ]


        let finalColumns = [
            ...columns,
            ...actionColumn
        ]

        document.addEventListener('DOMContentLoaded', (event) => {

            $('.dt-search').on('input', function() {
                window.renderedDataTable.ajax.reload(null, false);
            });

            $('#column_type').on('change', function() {
              window.renderedDataTable.ajax.reload(null, false);
             });

            initDatatable({
                url: '{{ route("backend.$module_name.index_data") }}',
                finalColumns,
                orderColumn: [
                    [4, "desc"]
             ],

             advanceFilter: () => {
                return {
                    search_user: $('.dt-search').val(),
                    type: $('#column_type').val(),
                };
            }
            });

            $('#reset-filter').on('click', function(e) {
                $('.dt-search').val('');
                $('#column_type').val('').trigger('change');
           window.renderedDataTable.ajax.reload(null, false);
          });

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


        })


    </script>
@endpush
