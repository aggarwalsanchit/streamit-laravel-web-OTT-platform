@extends('frontend::layouts.master')

@section('title')
    {{ __('messages.all_notifications') }}
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class=" my-4">
                    <div class="d-flex justify-content-between align-items-center p-3 bg-transparent border-0">
                        <h5 class="mb-0 fw-semibold py-2">{{ __('messages.all_notifications') }}</h5>
                        @if ($notifications->count() > 0)
                            <div class="d-flex gap-2 align-items-center">
                                <button type="button" id="selectAllBtn" class="btn btn-sm btn-outline-primary d-none" onclick="toggleSelectAll()">
                                    <i class="ph ph-check-square"></i> {{ __('messages.select_all') }}
                                </button>
                                <button type="button" id="deselectAllBtn" class="btn btn-sm btn-outline-primary d-none" onclick="toggleDeselectAll()">
                                    <i class="ph ph-square"></i> {{ __('messages.deselect_all') }}
                                </button>
                                <button type="button" id="deleteSelectedBtn" class="btn btn-sm btn-danger d-none" onclick="deleteSelectedNotifications()">
                                    <i class="ph ph-trash"></i> {{ __('messages.delete_selected') }}
                                </button>
                                <button type="button" id="deleteAllBtn" class="btn btn-sm btn-danger" onclick="deleteAllNotifications()">
                                    <i class="ph ph-trash"></i> {{ __('messages.delete_all') }}
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="overflow-auto  p-0 card-body-list max-17 scroll-thin">
                        <div class="dropdown-menu-1 overflow-y-auto list-style-1 mb-0 notification-height">
                            @if ($notifications->count() > 0)
                                @foreach ($notifications as $notification)
                                    @php
                                        $timezone =
                                            App\Models\Setting::where('name', 'default_time_zone')->value('val') ??
                                            'UTC';
                                        $notification->created_at = $notification->created_at->setTimezone($timezone);
                                        $notification->updated_at = $notification->updated_at->setTimezone($timezone);
                                        $notification_type = $notification->data['data']['type'] ?? '';
                                        $message = $notification->data['data']['message'] ?? '';
                                        $user_initial = strtoupper(
                                            substr($notification->data['data']['user_name'] ?? 'U', 0, 1),
                                        );

                                        // Get notification data
                                        $innerData = $notification->data['data'] ?? [];
                                        $notificationType = $innerData['notification_type'] ?? '';
                                        $thumbnailImage = null;
                                        
                                        $thumbnailImage = $innerData['posterimage'] ?? null;
                                        
                                        if (!$thumbnailImage || !filter_var($thumbnailImage, FILTER_VALIDATE_URL)) {
                                            // Get thumbnail image based on notification type (matching API logic)
                                            switch ($notificationType) {
                                                case 'movie_add':
                                                    $thumbnailImage = getThumbnail($innerData['movie_name'] ?? null, 'movie');
                                                    break;
                                                case 'episode_add':
                                                    $thumbnailImage = getThumbnail($innerData['episode_name'] ?? null, 'episode');
                                                    break;
                                                case 'season_add':
                                                    $thumbnailImage = getThumbnail($innerData['season_name'] ?? null, 'season');
                                                    break;
                                                case 'tv_show_add':
                                                    $thumbnailImage = getThumbnail($innerData['tvshow_name'] ?? null, 'tv_show');
                                                    break;
                                                case 'purchase_video':
                                                case 'rent_video':
                                                case 'one_time_purchase_content':
                                                case 'rental_content':
                                                    $contentType = $innerData['content_type'] ?? 'movie';
                                                    $thumbnailImage = getThumbnail($innerData['name'] ?? null, $contentType);
                                                    break;
                                                case 'new_subscription':
                                                case 'cancle_subscription':
                                                    $thumbnailImage = url('default-image/Default-Subscription-Image.png');
                                                    break;
                                                case 'upcoming':
                                                    $contentType = $innerData['content_type'] ?? 'movie';
                                                    $thumbnailImage = getThumbnail($innerData['name'] ?? null, $contentType);
                                                    break;
                                                case 'continue_watch':
                                                    $contentType = $innerData['content_type'] ?? 'movie';
                                                    $thumbnailImage = getThumbnail($innerData['name'] ?? null, $contentType);
                                                    break;

                                            }
                                        }
                                        
                                        // If no specific thumbnail, use loader GIF from settings
                                        if (!$thumbnailImage) {
                                            $thumbnailImage = GetSettingValue('loader_gif') ? setBaseUrlWithFileName(GetSettingValue('loader_gif'), 'image', 'logos') : asset('img/logo/loader.gif');
                                        }

                                        // Default icon and button - now always use image
                                        $buttonContent = "<img src='{$thumbnailImage}' alt='Notification' class='rounded-circle notification-user'>";
                                    @endphp

                                    <div class="mb-3">
                                        <div
                                            class="dropdown-item-1 float-none p-3 bg-gray-900 list-unstyled iq-sub-card {{ $notification->read_at ? '' : 'notify-list-bg' }} position-relative">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0">{{ $notification_type }}</h6>
                                                <input type="checkbox" class="notification-checkbox form-check-input" 
                                                       value="{{ $notification->id }}" 
                                                       id="notification_{{ $notification->id }}"
                                                       onchange="handleCheckboxChange()"
                                                       style="min-width: 18px; min-height: 18px; cursor: pointer; margin-top: 2px;">
                                            </div>
                                            <div class="list-item d-flex gap-3 flex-md-nowrap flex-wrap">
                                                <div>
                                                    {!! $buttonContent !!}
                                                </div>
                                                <div class="list-style-detail flex-grow-1">
                                                    <h6 class="mb-2">
                                                        <span class="body-color">{!! $message !!}</span>
                                                    </h6>
                                                    <div class="d-flex justify-content-between">
                                                        <small
                                                            class="text-body">{{ formatDate($notification->created_at) }}</small>
                                                        <small
                                                            class="text-body">{{ formatTime($notification->created_at) }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="list-unstyled dropdown-item-1 float-none p-5">
                                    <div class="list-item d-flex flex-column justify-content-center align-items-center text-center gap-3">
                                        <span class="btn btn-primary-subtle btn-icon rounded-circle p-4">
                                            <i class="ph ph-bell-slash fs-3"></i>
                                        </span>
                                        <div class="list-style-detail">
                                            <h6 class="font-weight-bold mb-0">{{ __('messages.no_notification') }}</h6>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    @if($notifications instanceof \Illuminate\Pagination\LengthAwarePaginator && $notifications->total() > 0)
                        <div class="d-flex justify-content-between align-items-center mt-4 px-3">
                            <div class="text-muted">
                                {{ __('messages.showing') }} {{ $notifications->firstItem() ?? 0 }} {{ __('messages.to') }}
                                {{ $notifications->lastItem() ?? 0 }} {{ __('messages.of') }} {{ $notifications->total() }}
                                {{ __('messages.records') }}
                            </div>
                            <div>
                                {{ $notifications->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        const baseUrl = document.querySelector('meta[name="baseUrl"]')?.getAttribute('content') || '{{ url("/") }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        function handleCheckboxChange() {
            const checked = document.querySelectorAll('.notification-checkbox:checked').length;
            const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
            const selectAllBtn = document.getElementById('selectAllBtn');
            const deselectAllBtn = document.getElementById('deselectAllBtn');
            
            if (checked > 0) {
                deleteSelectedBtn?.classList.remove('d-none');
                selectAllBtn?.classList.add('d-none');
                deselectAllBtn?.classList.remove('d-none');
            } else {
                deleteSelectedBtn?.classList.add('d-none');
                selectAllBtn?.classList.remove('d-none');
                deselectAllBtn?.classList.add('d-none');
            }
        }

        function toggleSelectAll() {
            document.querySelectorAll('.notification-checkbox').forEach(cb => cb.checked = true);
            handleCheckboxChange();
        }

        function toggleDeselectAll() {
            document.querySelectorAll('.notification-checkbox').forEach(cb => cb.checked = false);
            handleCheckboxChange();
        }

        function showConfirmDialog(message, callback) {
            Swal.fire({
                title: '{{ __('messages.are_you_sure') }}',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--bs-primary)',
                cancelButtonColor: '#858482',
                confirmButtonText: '{{ __('messages.yes_delete_it') }}',
                cancelButtonText: '{{ __('messages.cancel') }}',
                background: '#1e1e1e',
                color: '#ffffff',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) callback();
            });
        }

        function showSuccessMessage(message) {
            Swal.fire({
                title: '{{ __('messages.deleted') }}',
                text: message,
                icon: 'success',
                background: '#1e1e1e',
                color: '#ffffff',
                confirmButtonColor: 'var(--bs-primary)',
                timer: 2000,
                showConfirmButton: false
            }).then(() => window.location.reload());
        }

        function showErrorMessage(message) {
            Swal.fire({
                title: '{{ __('messages.error') }}',
                text: message,
                icon: 'error',
                background: '#1e1e1e',
                color: '#ffffff',
                confirmButtonColor: 'var(--bs-primary)'
            });
        }

        function setButtonLoading(btnId, loadingText) {
            const btn = document.getElementById(btnId);
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${loadingText}`;
            }
            return btn;
        }

        function resetButton(btnId, originalHtml) {
            const btn = document.getElementById(btnId);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }

        function deleteAllNotifications() {
            showConfirmDialog('{{ __('messages.delete_all_notifications_confirmation') }}', performDeleteAll);
        }

        function performDeleteAll() {
            const btn = setButtonLoading('deleteAllBtn', '{{ __('messages.deleting') }}');
            const originalHtml = '<i class="ph ph-trash"></i> {{ __('messages.delete_all') }}';

            fetch(`${baseUrl}/notifications/delete-all`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccessMessage(data.message);
                } else {
                    showErrorMessage(data.message || '{{ __('messages.error_occurred') }}');
                    resetButton('deleteAllBtn', originalHtml);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('{{ __('messages.error_occurred') }}');
                resetButton('deleteAllBtn', originalHtml);
            });
        }

        function deleteSelectedNotifications() {
            const selectedIds = Array.from(document.querySelectorAll('.notification-checkbox:checked')).map(cb => cb.value);

            if (selectedIds.length === 0) {
                if (window.successSnackbar) {
                    window.successSnackbar('{{ __('messages.no_notifications_selected') }}');
                }
                return;
            }

            showConfirmDialog('{{ __('messages.delete_selected_notifications_confirmation') }}', () => performDeleteSelected(selectedIds));
        }

        function performDeleteSelected(ids) {
            const btn = setButtonLoading('deleteSelectedBtn', '{{ __('messages.deleting') }}');
            const originalHtml = '<i class="ph ph-trash"></i> {{ __('messages.delete_selected') }}';

            fetch(`${baseUrl}/notifications/delete-selected`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ ids: ids })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showSuccessMessage(data.message);
                } else {
                    showErrorMessage(data.message || '{{ __('messages.error_occurred') }}');
                    resetButton('deleteSelectedBtn', originalHtml);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorMessage('{{ __('messages.error_occurred') }}');
                resetButton('deleteSelectedBtn', originalHtml);
            });
        }

        document.addEventListener('DOMContentLoaded', handleCheckboxChange);
    </script>
@endsection
