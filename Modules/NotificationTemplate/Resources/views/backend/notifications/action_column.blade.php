<div class="d-flex gap-2 align-items-center justify-content-end">
    @if($data)
        <a href="javascript:void(0);" 
           class="btn btn-secondary-subtle btn-sm fs-4 delete-btn" 
           data-bs-toggle="tooltip" 
           title="{{ __('messages.delete') }}"
           data-id="{{ $data->id }}"  
           onclick="deleteNotification(this)">
            <i class="ph ph-trash align-middle"></i>
        </a>
    @endif
</div>


   <script>
   function deleteNotification(element) {
    const id = element.getAttribute('data-id');
    const deleteUrl = '{{ url("/app/notification-remove") }}/' + encodeURIComponent(id);
    confirmSwal('{{ __('messages.are_you_sure?') }}').then((result) => {
        if (result.isConfirmed) {
            fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Request failed: ' + response.status);
                return response.json();
            })
            .then(data => {
                handleDeleteResponse(data);
            })
            .catch(handleError);
        }
    });
}
function handleDeleteResponse(data) {
    if (data && data.status) {
        Swal.fire({
            title: '{{ __('messages.delete') }}',
            text: data.message || '{{ __("notification.notification_deleted") }}',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        }).then(() => {
            if (typeof window.renderedDataTable !== 'undefined' && window.renderedDataTable) {
                window.renderedDataTable.ajax.reload(null, false);
            } else {
                location.reload();
            }
        });
    } else {
        Swal.fire({
            title: '{{ __('messages.error') }}',
            text: (data && data.message) || '{{ __("messages.something_went_wrong") }}',
            icon: 'error'
        });
    }
}

function handleError(error) {
    console.error('Delete error:', error);
    Swal.fire({
        title: '{{ __('messages.error') }}',
        text: '{{ __("messages.something_went_wrong") }}',
        icon: 'error'
    });
}
</script>
