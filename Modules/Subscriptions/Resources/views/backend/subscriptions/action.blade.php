<div class="d-flex gap-2 align-items-center justify-content-end">
    @if (!$data->trashed())

        @hasPermission('view_subscriptions')
            <a href="{{ route('backend.subscriptions.download_invoice', $data->id) }}" class="btn btn-info-subtle btn-sm fs-4"
                data-bs-toggle="tooltip" title="{{ __('messages.download_invoice') }}"> <i
                    class="ph ph-download align-middle"></i></a>
        @endhasPermission

        @hasPermission('force_delete_subscriptions')
            <a href="{{ route('backend.subscriptions.force_delete', $data->id) }}"
                id="delete-subscriptions-{{ $data->id }}" class="btn btn-danger-subtle btn-sm fs-4" data-type="ajax"
                data-method="DELETE" data-token="{{ csrf_token() }}" data-bs-toggle="tooltip"
                title="{{ __('messages.force_delete') }}" data-confirm="{{ __('messages.are_you_sure?') }}"> <i
                    class="ph ph-trash align-middle"></i></a>
        @endhasPermission
    @endif
</div>
