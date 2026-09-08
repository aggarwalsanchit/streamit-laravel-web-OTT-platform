<div class="card-header border-bottom p-3">
    <h5 class="mb-0">{{ __('messages.all_notifications') }} ({{ $all_unread_count }})</h5>
</div>

<div class="card-body overflow-auto card-header-border p-0 card-body-list max-17 scroll-thin">
    <div class="dropdown-menu-1 overflow-y-auto list-style-1 mb-0 notification-height">
        @if(isset($notifications) && count($notifications) > 0)

        @foreach($notifications->sortByDesc('created_at')->take(5) as $notification)
            @php
                $created_at_diff = $notification->created_at->diffForHumans();
                $timezone = App\Models\Setting::where('name', 'default_time_zone')->value('val') ?? 'UTC';
                $notification->created_at = $notification->created_at->setTimezone($timezone);
                $notification->updated_at = $notification->updated_at->setTimezone($timezone);
                $notification_type = ucwords(str_replace('_', ' ', $notification->data['data']['notification_type'])) . '!';
                $notification_group = $notification->data['data']['notification_group'] ?? 'general';

            @endphp

            @php
                // Get notification data
                $innerData = $notification->data['data'] ?? [];
                $notificationType = $innerData['notification_type'] ?? '';
                $thumbnailImage = null;
                
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
                
                // If no specific thumbnail, use loader GIF from settings
                if (!$thumbnailImage) {
                    $thumbnailImage = GetSettingValue('loader_gif') ? setBaseUrlWithFileName(GetSettingValue('loader_gif'), 'image', 'logos') : asset('img/logo/loader.gif');
                }
            @endphp

            @if(in_array($notification->data['data']['notification_type'], ['new_subscription', 'cancle_subscription']))
                <div class="dropdown-item-1 float-none p-3 list-unstyled iq-sub-card  {{ $notification->read_at ? '':'notify-list-bg'}} ">
                    <a href="{{ route('backend.users.details', ['id' => $notification->data['data']['user_id'] , 'notification_id' => $notification->id]) }}" class="">
                        <div class="d-flex justify-content-between">
                            <h6>{{ $notification_type }}</h6>
                            <h6>#{{ $notification->data['data']['id']}}</h6>
                        </div>
                        <div class="list-item d-flex">
                            <div class="me-3 mt-1">
                                <img src="{{ $thumbnailImage }}" alt="Notification" class="rounded notification-user">
                            </div>
                            <div class="list-style-detail">
                                    <p class="heading-color text-start mb-1"> {!! $notification->data['data']['message'] !!}</span></p>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-body">{{ formatDate($notification->created_at) }}</small>
                                        <small class="text-body">{{ $notification->created_at->format('h:i A') }}</small>
                                    </div>
                            </div>
                        </div>
                    </a>
                </div>
            @elseif(in_array($notification->data['data']['notification_type'], ['purchase_video', 'rent_video','rent_expiry_reminder','purchase_expiry_reminder']))
                <div class="dropdown-item-1 float-none p-3 mb-3 list-unstyled iq-sub-card {{ $notification->read_at ? '':'notify-list-bg'}} ">
                    <div class="d-flex justify-content-between">
                        <h6>{{ $notification_type }}</h6>
                    </div>
                    <div class="list-item d-flex">
                        <div class="me-3 mt-1">
                            <img src="{{ $thumbnailImage }}" alt="Notification" class="rounded notification-user">
                        </div>
                        <div class="list-style-detail">
                                <p class="heading-color text-start mb-1">
                                    <span class="body-color"> {!! $notification->data['data']['message'] ?? '' !!}</span>
                                </p>
                            <div class="d-flex justify-content-between">
                                <small class="text-body">{{ formatDate($notification->created_at) }}</small>
                                <small class="text-body">{{ $notification->created_at->format('h:i A') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif(in_array($notification->data['data']['notification_type'], ['change_password', 'forget_email_password']))
                <div class="dropdown-item-1 float-none p-3 mb-3 list-unstyled iq-sub-card {{ $notification->read_at ? '':'notify-list-bg'}} ">
                    <div class="d-flex justify-content-between">
                        <h6>{{ $notification_type }}</h6>
                    </div>
                    <div class="list-item d-flex">
                        <div class="me-3 mt-1">
                            <button type="button" class="btn btn-primary-subtle btn-icon rounded-pill">
                                <i class="ph ph-shield"></i>
                            </button>
                        </div>
                        <div class="list-style-detail">
                                <p class="heading-color text-start mb-1">
                                    {!! $notification->data['data']['message'] ?? '' !!}
                                </p>
                            <div class="d-flex justify-content-between">
                                <small class="text-body">{{ formatDate($notification->created_at) }}</small>
                                <small class="text-body">{{ $notification->created_at->format('h:i A') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="dropdown-item-1 float-none p-3 mb-3 list-unstyled iq-sub-card {{ $notification->read_at ? '':'notify-list-bg'}} ">
                    <div class="d-flex justify-content-between">
                        <h6>{{ $notification_type }}</h6>
                        <small class="text-muted">{{ $created_at_diff }}</small>
                    </div>
                    <div class="list-item d-flex">
                        <div class="me-3 mt-1">
                            <img src="{{ $thumbnailImage }}" alt="Notification" class="rounded notification-user">
                        </div>
                        <div class="list-style-detail">
                            <p class="heading-color text-start mb-1">{!! $notification->data['data']['message'] ?? '' !!}</p>
                            <div class="d-flex justify-content-between">
                                <small class="text-body">{{ formatDate($notification->created_at) }}</small>
                                <small class="text-body">{{ $notification->created_at->format('h:i A') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        @endforeach
        @else
        <li class="list-unstyled dropdown-item-1 float-none p-3">
            <div class="list-item d-flex justify-content-center align-items-center">
                <div class="list-style-detail ml-2 mr-2">
                    <h6 class="font-weight-bold">{{ __('messages.no_notification') }}</h6>
                    <p class="mb-0"></p>
                </div>
            </div>
        </li>
        @endif
    </div>
</div>
<div class="card-footer py-2 border-top">
    <div class="d-flex align-items-center gap-3 justify-content-end">
        @if($all_unread_count > 0 )
        <a href="{{ route('backend.notifications.markAllAsRead') }}" data-type="markas_read" class="text-primary mb-0 notifyList pull-right"><span>{{__('messages.mark_all_as_read') }}</span></a>
        @endif
        @if(isset($notifications) && count($notifications) > 0)
        <a href="{{ route('backend.notifications.index') }}" class="btn btn-sm btn-primary">{{ __('messages.view_all') }}</a>
        @endif
    </div>
</div>

