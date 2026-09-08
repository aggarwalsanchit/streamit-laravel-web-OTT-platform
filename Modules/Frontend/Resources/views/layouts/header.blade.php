@php
    $currentRoute = Route::currentRouteName();
    $noAbsoluteRoutes = [
        'movie-details',
        'tvshow-details',
        'episode-details',
        'video-details',
        'pay-per-view.paymentform',
        'pay-per-view',
    ];
@endphp
<header
    class="{{ $currentRoute === 'user.login' && !in_array($currentRoute, $noAbsoluteRoutes) ? 'header-absolute' : '' }}">
    <nav class="nav navbar navbar-expand-xl navbar-light iq-navbar header-hover-menu py-xl-0">
        <div class="container-fluid navbar-inner">
            <div class="d-flex align-items-center justify-content-between w-100 landing-header">
                <div class="sd-header-start d-flex align-items-center flex-shrink-0">
                    <div class="d-flex gap-3 gap-xl-0 align-items-center">
                        <button type="button" data-bs-toggle="offcanvas" data-bs-target="#navbar_main"
                            aria-controls="navbar_main" class="d-xl-none btn btn-primary rounded-pill toggle-rounded-btn">
                            <i class="ph ph-arrow-right"></i>
                        </button>
                        @include('frontend::components.partials.logo')
                    </div>
                </div>

                @if (function_exists('short_drama_show_mobile_hub') && short_drama_show_mobile_hub())
                    <div class="sd-mobile-hub-header-wrap">
                        @include('frontend::components.partials.mobile-hub-tabs', ['placement' => 'header'])
                    </div>
                @endif

                @include('frontend::components.partials.horizontal-nav')
                <div class="right-panel">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-btn">
                            <span class="navbar-toggler-icon"></span>
                        </span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <div
                            class="d-flex flex-md-row flex-column align-items-md-center align-items-end justify-content-end gap-xl-3 gap-0">
                            <ul class="navbar-nav align-items-center list-inline justify-content-end mt-md-0 mt-3">
                                    <li class="nav-item">
                                        <div class="search-box-container">
                                            <div class="search-input-wrapper">
                                                <input type="text" id="search-query" class="search-input"
                                                    placeholder="{{ __('frontend.search_placeholder') }}" autocomplete="off">
                                                <button type="button" id="search-icon-btn" class="search-icon-btn">
                                                    <svg width="18" viewBox="0 0 24 24" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg" class="icon-18">
                                                        <circle cx="11.7666" cy="11.7666" r="8.98856"
                                                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                                            stroke-linejoin="round"></circle>
                                                        <path d="M18.0183 18.4851L21.5423 22" stroke="currentColor"
                                                            stroke-width="1.5" stroke-linecap="round"
                                                            stroke-linejoin="round"></path>
                                                    </svg>
                                                </button>
                                                
                                                <!-- Microphone Icon -->
                                                <button type="button" id="search-mic-btn" class="search-mic-btn">
                                                    <i class="ph ph-microphone"></i>
                                                </button>

                                                <!-- Close/Clear Icon (Visible when typing) -->
                                                <button type="button" id="search-close-btn" class="search-close-btn d-none">
                                                    <i class="ph ph-x"></i>
                                                </button>
                                            </div>
                                            <div id="search-suggestions" class="search-suggestions d-none"></div>
                                        </div>
                                    </li>
                                </ul>
                                <ul class="navbar-nav align-items-center mb-0 list-inline justify-content-end">
                                    @if (auth()->check() && !getCurrentProfileSession('is_child_profile'))
                                        <li class="nav-item dropdown iq-dropdown header-notification">
                                            <a class="nav-link btn-icon rounded-pill btn-action p-0"
                                                data-bs-toggle="dropdown" href="#">
                                                <div class="iq-sub-card mb-0">
                                                    <div class="notification_list">
                                                        <span class="btn-inner">
                                                            <i class="ph ph-bell fs-5"></i>
                                                        </span>
                                                        @if (auth()->check() && auth()->user()->unreadNotifications()->count() > 0)
                                                            <span
                                                                class="notification-alert">{{ auth()->user()->unreadNotifications()->count() }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </a>
                                            <ul class="p-0 sub-drop dropdown-menu dropdown-notification dropdown-menu-end">
                                                <div class="m-0 shadow-none card bg-transparent">
                                                    <div class="card-header border-bottom p-3">
                                                        <h5 class="mb-0">{{ __('messages.title_list') }}</h5>
                                                    </div>



                                                <div
                                                    class="card-body overflow-auto card-header-border p-0 card-body-list max-17 scroll-thin">
                                                    <div
                                                        class="dropdown-menu-1 overflow-y-auto list-style-1 mb-0 notification-height">
                                                        @if (auth()->check())
                                                            @php
                                                                $notifications = auth()
                                                                    ->user()
                                                                    ->notifications()
                                                                    ->orderBy('created_at', 'desc')
                                                                    ->take(5)
                                                                    ->get();
                                                                $timezone =
                                                                    App\Models\Setting::where(
                                                                        'name',
                                                                        'default_time_zone',
                                                                    )->value('val') ?? 'UTC';
                                                                $types = [
                                                                    'new_subscription' => [
                                                                        'icon' => null,
                                                                        'route' => route('payment-history'),
                                                                    ],
                                                                    'cancle_subscription' => [
                                                                        'icon' => null,
                                                                        'route' => route('payment-history'),
                                                                    ],
                                                                    'purchase_video' => [
                                                                        'icon' => 'ph-credit-card',
                                                                        'route' => route('transaction-history'),
                                                                    ],
                                                                    'rent_video' => [
                                                                        'icon' => 'ph-credit-card',
                                                                        'route' => route('transaction-history'),
                                                                    ],
                                                                    'rent_expiry_reminder' => [
                                                                        'icon' => 'ph-credit-card',
                                                                        'route' => route('transaction-history'),
                                                                    ],
                                                                    'purchase_expiry_reminder' => [
                                                                        'icon' => 'ph-credit-card',
                                                                        'route' => route('transaction-history'),
                                                                    ],
                                                                    'change_password' => [
                                                                        'icon' => 'ph-shield',
                                                                        'route' => route('update-profile'),
                                                                    ],
                                                                    'forget_email_password' => [
                                                                        'icon' => 'ph-shield',
                                                                        'route' => route('update-profile'),
                                                                    ],
                                                                ];
                                                            @endphp

                                                            @if ($notifications->count())
                                                                @foreach ($notifications as $notification)
                                                                    @php
                                                                        $notification->created_at = $notification->created_at->setTimezone(
                                                                            $timezone,
                                                                        );
                                                                        $notification_type =
                                                                            $notification->data['data']['type'] ?? '';
                                                                        $type_key =
                                                                            $notification->data['data'][
                                                                                'notification_type'
                                                                            ] ?? '';
                                                                        $type_data = $types[$type_key] ?? [
                                                                            'icon' => 'ph-bell',
                                                                            'route' => '#',
                                                                        ];
                                                                        $icon = $type_data['icon'];
                                                                        $route = $type_data['route'];
                                                                        $user_initial = strtoupper(
                                                                            substr(
                                                                                $notification->data['data'][
                                                                                    'user_name'
                                                                                ] ?? 'U',
                                                                                0,
                                                                                1,
                                                                            ),
                                                                        );
                                                                        $is_unread = $notification->read_at
                                                                            ? ''
                                                                            : 'notify-list-bg';
                                                                        $message =
                                                                            $notification->data['data']['message'] ??
                                                                            '';
                                                                        
                                                                        // Get thumbnail image based on notification type
                                                                        $innerData = $notification->data['data'] ?? [];
                                                                        $notificationType = $innerData['notification_type'] ?? '';
                                                                        $thumbnailImage = null;
                                                                        
                                                                        $thumbnailImage = $innerData['posterimage'] ?? null;
                                                                        
                                                                        if (!$thumbnailImage || !filter_var($thumbnailImage, FILTER_VALIDATE_URL)) { 
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
                                                                    @endphp

                                                                    <div
                                                                        class="dropdown-item-1 float-none p-3 list-unstyled iq-sub-card">
                                                                        <a href="{{ $route }}"
                                                                            class="notification-link text-decoration-none"
                                                                            data-notification-id="{{ $notification->id }}">
                                                                            <div
                                                                                class="d-flex justify-content-between align-items-center">
                                                                                <h6
                                                                                    class="{{ $is_unread ? 'fw-bold text-primary mb-0' : 'mb-0' }}">
                                                                                    {{ $notification_type }}</h6>
                                                                                @if ($is_unread)
                                                                                    <span class="position-relative">
                                                                                        <span
                                                                                            class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                                                                        </span>
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                            <div
                                                                                class="list-item d-flex gap-3 flex-md-nowrap flex-wrap mt-2">
                                                                                <div>
                                                                                    <img src="{{ $thumbnailImage }}" alt="Notification" class="rounded-circle notification-user">
                                                                                </div>
                                                                                <div
                                                                                    class="list-style-detail flex-grow-1">
                                                                                    <h6
                                                                                        class="heading-color text-start mb-1 {{ $is_unread ? 'fw-semibold' : '' }}">
                                                                                        {!! $message !!}
                                                                                    </h6>
                                                                                    <div
                                                                                        class="d-flex justify-content-between">
                                                                                        <small
                                                                                            class="text-body">{{ formatDate($notification->created_at) }}</small>
                                                                                        <small
                                                                                            class="text-body">{{ formatTime($notification->created_at) }}</small>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </a>
                                                                    </div>
                                                                @endforeach
                                                            @else
                                                                <li
                                                                    class="list-unstyled dropdown-item-1 float-none p-3">
                                                                    <div
                                                                        class="list-item d-flex justify-content-center align-items-center">
                                                                        <div class="list-style-detail ml-2 mr-2">
                                                                            <h6 class="font-weight-bold">
                                                                                {{ __('messages.no_notification') }}
                                                                            </h6>
                                                                            <p class="mb-0"></p>
                                                                        </div>
                                                                    </div>
                                                                </li>
                                                            @endif
                                                        @else
                                                            <li class="list-unstyled dropdown-item-1 float-none p-3">
                                                                <div
                                                                    class="list-item d-flex justify-content-center align-items-center">
                                                                    <div class="list-style-detail ml-2 mr-2">
                                                                        <h6 class="font-weight-bold">
                                                                            {{ __('frontend.login_to_view_notifications') }}
                                                                        </h6>
                                                                    </div>
                                                                </div>
                                                            </li>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if (auth()->check() && isset($notifications) && count($notifications) > 0)
                                                    <div class="card-footer py-2 border-top">
                                                        <div
                                                            class="d-flex align-items-center gap-3 justify-content-between">
                                                            <a href="{{ route('notifications.markAllAsRead') }}"
                                                                class="text-primary mb-0 notifyList pull-right">
                                                                <span>{{ __('messages.mark_all_as_read') }}</span>
                                                            </a>
                                                            <a href="{{ route('notifications.index') }}"
                                                                class="btn btn-sm btn-primary">{{ __('messages.view_all') }}</a>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </ul>
                                    </li>
                                @endif
                                <li class="nav-item dropdown dropdown-download-wrapper">
                                    <button class="btn btn-dark gap-3 p-2" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false" aria-label="Download Application">
                                        <i class="ph ph-download-simple fs-5"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end dropdown-download-scanner mt-0">
                                        <div class="row align-items-center gy-2">
                                            <div class="col-12">
                                                <h5 class="card-title text-white fw-semibold mb-0">
                                                    {{ __('frontend.scan_qr_download_app') }}
                                                    {{ setting('app_name') }}
                                                </h5>
                                            </div>
                                            <div class="col-6 text-end">
                                                <div class="d-flex align-items-center gap-2 mb-3">
                                                    <span class="platform-icon apple-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14"
                                                            height="14" viewBox="0 0 34 34" fill="none">
                                                            <path
                                                                d="M27.7273 18.0811C27.6826 13.7857 31.7959 11.696 31.984 11.5987C29.6545 8.66274 26.0438 8.26159 24.7749 8.2296C21.7424 7.95373 18.8008 9.79822 17.256 9.79822C15.6804 9.79822 13.3015 8.25626 10.7376 8.30157C7.43835 8.34555 4.35184 9.9968 2.65903 12.561C-0.834494 17.7893 1.77101 25.4724 5.11807 29.6985C6.79237 31.7696 8.74881 34.0792 11.3096 33.9979C13.8149 33.91 14.7507 32.6185 17.774 32.6185C20.7696 32.6185 21.6483 33.9979 24.26 33.9459C26.9488 33.91 28.6416 31.8669 30.2573 29.7785C32.1921 27.4076 32.9692 25.07 33 24.95C32.9383 24.9314 27.7798 23.2281 27.7273 18.0811ZM22.7938 5.44953C24.1413 3.99286 25.0632 2.01109 24.8073 0C22.8571 0.0746328 20.4181 1.16614 19.0136 2.59083C17.7709 3.84626 16.6609 5.90399 16.9477 7.83911C19.1384 7.98038 21.3878 6.88354 22.7938 5.44953Z"
                                                                fill="white" />
                                                        </svg>
                                                    </span>
                                                    <small>{{ __('messages.lbl_app_store') }}</small>
                                                </div>
                                                @php
                                                    $androidUrl = setting('android_url');
                                                    $iosUrl = setting('ios_url');
                                                    $siteUrl = url('/');
                                                    $qrBase =
                                                        'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=';
                                                @endphp
                                                <div class="bg-white p-1 rounded d-inline-block">
                                                    <div class="qr-code">
                                                        @if (!empty($iosUrl))
                                                            <img src="{{ $qrBase . urlencode($iosUrl) }}"
                                                                alt="qr code" class="img-fluid">
                                                        @else
                                                            <img src="{{ asset('img/web-img/app-scanner.jpg') }}"
                                                                alt="qr code" class="img-fluid">
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6 text-end">
                                                <div class="d-flex align-items-center gap-2 mb-3">
                                                    <span class="platform-icon android-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14"
                                                            height="14" viewBox="0 0 34 34" fill="none">
                                                            <path
                                                                d="M1.54935 0.466309C1.13734 0.862562 1 1.52298 1 2.31549V31.5062C1 32.2987 1.27468 32.9591 1.68669 33.3553L1.82403 33.4874L18.8539 17.109V16.8448L1.54935 0.466309Z"
                                                                fill="url(#paint0_linear_11733_30493)" />
                                                            <path
                                                                d="M24.3496 22.6559L18.7188 17.2404V16.8442L24.3496 11.4287L24.4869 11.5608L31.2165 15.2592C33.1392 16.3158 33.1392 18.0329 31.2165 19.0896L24.3496 22.6559Z"
                                                                fill="url(#paint1_linear_11733_30493)" />
                                                            <path
                                                                d="M24.4803 22.5236L18.7122 16.9761L1.54492 33.4866C2.23161 34.1471 3.19298 34.1471 4.42902 33.6187L24.4803 22.5236Z"
                                                                fill="url(#paint2_linear_11733_30493)" />
                                                            <path
                                                                d="M24.4803 11.4289L4.42902 0.465934C3.19298 -0.194489 2.23161 -0.0624046 1.54492 0.598018L18.7122 16.9765L24.4803 11.4289Z"
                                                                fill="url(#paint3_linear_11733_30493)" />
                                                            <path opacity="0.2"
                                                                d="M24.343 22.3921L4.42902 33.223C3.33031 33.8834 2.36895 33.7514 1.68226 33.223L1.54492 33.3551L1.68226 33.4872C2.36895 34.0155 3.33031 34.1476 4.42902 33.4872L24.343 22.3921Z"
                                                                fill="black" />
                                                            <path opacity="0.12"
                                                                d="M1.54935 33.2225C1.13734 32.8262 1 32.1658 1 31.3733V31.5054C1 32.2979 1.27468 32.9583 1.68669 33.3546V33.2225H1.54935ZM31.2143 18.6932L24.3474 22.3915L24.4848 22.5236L31.2143 18.8253C32.1757 18.2969 32.5877 17.6365 32.5877 16.9761C32.5877 17.6365 32.0384 18.1648 31.2143 18.6932Z"
                                                                fill="black" />
                                                            <path opacity="0.25"
                                                                d="M4.43345 0.598091L31.2143 15.2595C32.0384 15.7878 32.5877 16.3161 32.5877 16.9766C32.5877 16.3161 32.1757 15.6557 31.2143 15.1274L4.43345 0.466007C2.51072 -0.590669 1 0.201837 1 2.31519V2.44727C1 0.466007 2.51072 -0.458585 4.43345 0.598091Z"
                                                                fill="white" />
                                                            <defs>
                                                                <linearGradient id="paint0_linear_11733_30493"
                                                                    x1="17.2059" y1="2.06453" x2="-4.94507"
                                                                    y2="25.0951" gradientUnits="userSpaceOnUse">
                                                                    <stop stop-color="#00A0FF" />
                                                                    <stop offset="0.007" stop-color="#00A1FF" />
                                                                    <stop offset="0.26" stop-color="#00BEFF" />
                                                                    <stop offset="0.512" stop-color="#00D2FF" />
                                                                    <stop offset="0.76" stop-color="#00DFFF" />
                                                                    <stop offset="1" stop-color="#00E3FF" />
                                                                </linearGradient>
                                                                <linearGradient id="paint1_linear_11733_30493"
                                                                    x1="33.7353" y1="16.9776" x2="0.503633"
                                                                    y2="16.9776" gradientUnits="userSpaceOnUse">
                                                                    <stop stop-color="#FFE000" />
                                                                    <stop offset="0.409" stop-color="#FFBD00" />
                                                                    <stop offset="0.775" stop-color="#FFA500" />
                                                                    <stop offset="1" stop-color="#FF9C00" />
                                                                </linearGradient>
                                                                <linearGradient id="paint2_linear_11733_30493"
                                                                    x1="21.3587" y1="20.0087" x2="-8.67828"
                                                                    y2="51.2403" gradientUnits="userSpaceOnUse">
                                                                    <stop stop-color="#FF3A44" />
                                                                    <stop offset="1" stop-color="#C31162" />
                                                                </linearGradient>
                                                                <linearGradient id="paint3_linear_11733_30493"
                                                                    x1="-2.71667" y1="-9.20794" x2="10.6969"
                                                                    y2="4.7391" gradientUnits="userSpaceOnUse">
                                                                    <stop stop-color="#32A071" />
                                                                    <stop offset="0.069" stop-color="#2DA771" />
                                                                    <stop offset="0.476" stop-color="#15CF74" />
                                                                    <stop offset="0.801" stop-color="#06E775" />
                                                                    <stop offset="1" stop-color="#00F076" />
                                                                </linearGradient>
                                                            </defs>
                                                        </svg>
                                                    </span>
                                                    <small>{{ __('messages.lbl_play_store') }}</small>
                                                </div>
                                                <div class="bg-white p-1 rounded d-inline-block">
                                                    <div class="qr-code">
                                                        @if (!empty($androidUrl))
                                                            <img src="{{ $qrBase . urlencode($androidUrl) }}"
                                                                alt="qr code" class="img-fluid">
                                                        @else
                                                            <img src="{{ asset('img/web-img/app-scanner.jpg') }}"
                                                                alt="qr code" class="img-fluid">
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li class="nav-item dropdown dropdown-language-wrapper">
                                    <button class="btn btn-dark gap-3 px-3 dropdown-toggle" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false" aria-label="Toggle language">
                                        <img src="{{ asset('flags/' . App::getLocale() . '.png') }}" alt="flag"
                                            class="img-fluid me-2" style="width:20px;height:14px;object-fit:contain;"
                                            onerror="this.onerror=null; this.src='{{ asset('flags/globe.png') }}';">
                                        {{ strtoupper(App::getLocale()) }}
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-language mt-0">
                                        @foreach (config('app.available_locales') as $locale => $title)
                                            <a class="dropdown-item {{ App::getLocale() == $locale ? 'active' : '' }}"
                                                href="{{ route('frontend.language.switch', $locale) }}">
                                                <span class="d-flex align-items-center gap-3">
                                                    <img src="{{ asset('flags/' . $locale . '.png') }}"
                                                        alt="flag" class="img-fluid mr-2" style="width:20px;height:14px;object-fit:contain;">
                                                    <span>{{ $title }}</span>
                                                    @if (App::getLocale() == $locale)
                                                        <span class="text-success"><i
                                                                class="ph-fill ph-check-fat align-middle"></i></span>
                                                    @endif
                                                </span>
                                            </a>
                                        @endforeach
                                    </div>
                                </li>


                                @if (auth()->check() && auth()->user()->user_type == 'user' && !getCurrentProfileSession('is_child_profile'))
                                    <li class="nav-item">
                                        @if (auth()->user()->is_subscribe == 0)
                                            <button
                                                class="btn btn-warning-subtle font-size-14 text-uppercase subscribe-btn px-3"
                                                onclick="window.location.href='{{ route('subscriptionPlan') }}'">
                                                <span class="d-flex align-items-center gap-2">
                                                    <i class="ph-fill ph-crown-simple"></i>
                                                    {{ __('frontend.subscribe') }}
                                                </span>
                                            </button>
                                        @else
                                            <button
                                                class="btn btn-warning-subtle font-size-14 text-uppercase subscribe-btn px-3"
                                                onclick="window.location.href='{{ route('subscriptionPlan') }}'">
                                                <span class="d-flex align-items-center gap-2">
                                                    <i class="ph-fill ph-crown-simple"></i>
                                                    {{ __('frontend.upgrade') }}
                                                    <span>
                                            </button>
                                        @endif
                                    </li>
                                @elseif(!auth()->check())
                                    <button
                                        class="btn btn-warning-subtle font-size-14 text-uppercase subscribe-btn px-3"
                                        onclick="window.location.href='{{ route('subscriptionPlan') }}'">
                                        <span class="d-flex align-items-center gap-2">
                                            <i class="ph-fill ph-crown-simple"></i>
                                            {{ __('frontend.subscribe') }}
                                        </span>
                                    </button>
                                @endif
                                @php
                                    $currentProfile = getCurrentProfileSession();
                                    $isChildProfile = getCurrentProfileSession('is_child_profile');
                                @endphp
                                @if (auth()->check())
                                    @if ($currentProfile && $currentProfile->is_child_profile)
                                        <li class="nav-item">
                                            <button class="btn btn-primary font-size-14 text-uppercase px-3"
                                                onclick="window.location.href='{{ route('manage-profile') }}'">
                                                <span class="d-flex align-items-center gap-2">
                                                    <i class="ph ph-sign-out"></i>
                                                    {{ __('messages.exit_profile') }}
                                                </span>
                                            </button>
                                        </li>
                                    @endif


                                    <li class="nav-item flex-shrink-0 dropdown dropdown-user-wrapper">
                                        <a class="nav-link dropdown-user" href="#" id="navbarDropdown"
                                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <img src="{{ $currentProfile && $currentProfile->avatar ? setBaseUrlWithFileName($currentProfile->avatar) : setBaseUrlWithFileName(auth()->user()->file_url) }}"
                                                class="img-fluid user-image rounded-circle" alt="user image">
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end dropdown-user-menu border border-gray-900"
                                            aria-labelledby="navbarDropdown">
                                            <div
                                                class="bg-body p-3 d-flex justify-content-between align-items-center gap-3 rounded mb-4">
                                                @if ($isChildProfile == 0)
                                                    <a href="{{ route('update-profile') }}"
                                                        class="text-decoration-none">
                                                        <div class="d-inline-flex align-items-center gap-3">
                                                            <div class="image flex-shrink-0">
                                                                <img src="{{ setBaseUrlWithFileName(auth()->user()->file_url, 'image', 'users') }}"
                                                                    class="img-fluid dropdown-user-menu-image"
                                                                    alt="">
                                                            </div>
                                                            <div class="content">
                                                                <h6 class="mb-1">
                                                                    {{ auth()->user()->full_name ?? '--' }}
                                                                </h6>
                                                                <span
                                                                    class="font-size-14 dropdown-user-menu-contnet text-white">{{ auth()->user()->email }}</span>
                                                            </div>
                                                        </div>
                                                    </a>
                                                @else
                                                    <div class="text-decoration-none">
                                                        <div class="d-inline-flex align-items-center gap-3">
                                                            <div class="image flex-shrink-0">
                                                                <img src="{{ setBaseUrlWithFileName(auth()->user()->file_url, 'image', 'users') }}"
                                                                    class="img-fluid dropdown-user-menu-image"
                                                                    alt="">
                                                            </div>
                                                            <div class="content">
                                                                <h6 class="mb-1">
                                                                    {{ auth()->user()->full_name ?? '--' }}
                                                                </h6>
                                                                <span
                                                                    class="font-size-14 dropdown-user-menu-contnet text-white">{{ auth()->user()->email }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            @if ($isChildProfile == 0)
                                                <div class="profile-list-section mb-4">
                                                    <div class="profiles-container">
                                                        @php
                                                            $userProfiles = \App\Models\UserMultiProfile::where(
                                                                'user_id',
                                                                auth()->id(),
                                                            )->get();
                                                        @endphp
                                                        @forelse($userProfiles as $profile)
                                                            <div class="profile-item d-flex align-items-center gap-3 mb-2 p-2 rounded bg-dark {{ $currentProfile && $currentProfile->id == $profile->id ? 'active' : '' }}"
                                                                style="cursor: pointer;"
                                                                onclick="SelectProfile11({{ $profile->id }})">
                                                                <div class="profile-avatar">
                                                                    <img src="{{ $profile->avatar ? setBaseUrlWithFileName($profile->avatar) : asset('images/avatar.webp') }}"
                                                                        class="img-fluid rounded"
                                                                        style="width: 35px; height: 35px; object-fit: cover;"
                                                                        alt="{{ $profile->name }}">
                                                                </div>
                                                                <div class="profile-info">
                                                                    <div class="profile-name text-white font-size-14">
                                                                        {{ $profile->name }}</div>
                                                                    @if ($profile->is_child_profile)
                                                                        <div
                                                                            class="profile-type text-muted font-size-12">
                                                                            {{ __('frontend.kids_profile') }}</div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div class="text-center text-muted font-size-14">
                                                                {{ __('frontend.no_profiles_found') }}</div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            @endif
                                            <ul class="d-flex flex-column gap-3 list-inline m-0 p-0">
                                                {{-- <li>
                                                    <a href="{{ route('watchList') }}"
                                                        class="link-body-emphasis font-size-14">
                                                        <span
                                                            class="d-flex align-items-center justify-content-between gap-3">
                                                            <span
                                                                class="fw-medium">{{ __('frontend.my_watchlist') }}</span>
                                                            <i class="ph ph-caret-right"></i>
                                                        </span>
                                                    </a>
                                                </li> --}}

                                                {{-- @if (getCurrentProfileSession('is_child_profile') == 0)
                                                    <li
                                                        class="d-flex align-items-center justify-content-between py-2 {{ auth()->user()->pin ? 'border-bottom border-secondary' : '' }}">
                                                        <a href="javascript:void(0);"
                                                            class="link-body-emphasis font-size-14 d-flex align-items-center gap-2">
                                                            <span
                                                                class="fw-medium">{{ __('frontend.security_control') }}</span>
                                                        </a>

                                                        <!-- Toggle Switch -->
                                                        <label class="toggle-switch mb-0 ms-2">
                                                            <input type="checkbox" name="security_toggle"
                                                                id="security_toggle" value="1"
                                                                data-security-url="{{ route('security-control') }}"
                                                                data-disable-url="{{ route('disable-security') }}"
                                                                {{ auth()->user()->is_parental_lock_enable ? 'checked' : '' }}>
                                                            <span class="slider"></span>
                                                        </label>
                                                    </li>

                                                    <li id="security_control_section"
                                                        class="{{ auth()->user()->is_parental_lock_enable ? '' : 'd-none' }}">
                                                        <a href="{{ route('security-control') }}" id="pinActionBtn"
                                                            class="d-flex align-items-center justify-content-between py-2 text-decoration-none text-white">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <!-- Inserted SVG icon -->
                                                                <svg width="16" height="16"
                                                                    viewBox="0 0 16 16" fill="none"
                                                                    xmlns="http://www.w3.org/2000/svg">
                                                                    <g clip-path="url(#clip0_8998_18394)">
                                                                        <path
                                                                            d="M5 10H13C13.1326 10 13.2598 9.94732 13.3536 9.85355C13.4473 9.75979 13.5 9.63261 13.5 9.5V3C13.5 2.86739 13.4473 2.74021 13.3536 2.64645C13.2598 2.55268 13.1326 2.5 13 2.5H6C5.86739 2.5 5.74021 2.55268 5.64645 2.64645C5.55268 2.74021 5.5 2.86739 5.5 3V3.5"
                                                                            stroke="#999797" stroke-width="1.5"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round" />
                                                                        <path d="M6.5 8.5L5 10L6.5 11.5"
                                                                            stroke="#999797" stroke-width="1.5"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round" />
                                                                        <path
                                                                            d="M11 6H3C2.86739 6 2.74021 6.05268 2.64645 6.14645C2.55268 6.24021 2.5 6.36739 2.5 6.5V13C2.5 13.1326 2.55268 13.2598 2.64645 13.3536C2.74021 13.4473 2.86739 13.5 3 13.5H10C10.1326 13.5 10.2598 13.4473 10.3536 13.3536C10.4473 13.2598 10.5 13.1326 10.5 13V12.5"
                                                                            stroke="#999797" stroke-width="1.5"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round" />
                                                                        <path d="M9.5 7.5L11 6L9.5 4.5"
                                                                            stroke="#999797" stroke-width="1.5"
                                                                            stroke-linecap="round"
                                                                            stroke-linejoin="round" />
                                                                    </g>
                                                                    <defs>
                                                                        <clipPath id="clip0_8998_18394">
                                                                            <rect width="16" height="16"
                                                                                fill="white" />
                                                                        </clipPath>
                                                                    </defs>
                                                                </svg>


                                                                @if (empty(auth()->user()->pin))
                                                                    {{ __('frontend.set_pin') }}
                                                                @else
                                                                    {{ __('frontend.change_pins') }}
                                                                @endif
                                                            </div>
                                                        </a>


                                                        <div id="pinFormContainer" class="mt-2 d-none">
                                                            <!-- JS will load the PIN form here -->
                                                        </div>
                                                    </li>
                                                @endif --}}
                                    </li>

                                    {{-- <li>
                                        <a href="{{ route('unlock.videos') }}"
                                            class="link-body-emphasis font-size-14">
                                            <span class="d-flex align-items-center justify-content-between gap-3">
                                                <span class="fw-medium">{{ __('messages.lbl_unlock_videos') }}</span>
                                                <i class="ph ph-caret-right"></i>
                                            </span>
                                        </a>
                                    </li> --}}


                                    {{-- <li>
                                        <a href="{{ route('update-profile') }}"
                                            class="link-body-emphasis font-size-14">
                                            <span class="d-flex align-items-center justify-content-between gap-3">
                                                <span class="fw-medium">{{ __('frontend.profile') }}</span>
                                                <i class="ph ph-caret-right"></i>
                                            </span>
                                        </a>
                                    </li> --}}

                                    @if ($isChildProfile == 0)
                                        <li>
                                            <a href="{{ route('profile-management') }}"
                                                class="link-body-emphasis font-size-14">
                                                <span class="d-flex align-items-center justify-content-between gap-3">
                                                    <span
                                                        class="fw-medium">{{ __('messages.profile_management') }}</span>
                                                    <i class="ph ph-caret-right"></i>
                                                </span>
                                            </a>
                                        </li>
                                    @endif
                                    {{-- <li>
                                        <a href="{{ route('subscriptionPlan') }}"
                                            class="link-body-emphasis font-size-14">
                                            <span class="d-flex align-items-center justify-content-between gap-3">
                                                <span class="fw-medium">{{ __('frontend.subscription_plan') }}</span>
                                                <i class="ph ph-caret-right"></i>
                                            </span>
                                        </a>
                                    </li> --}}
                                    <li>
                                        <a href="{{ $isChildProfile == 0 ? route('accountSetting') : route('watchList') }}"
                                            class="link-body-emphasis font-size-14">
                                            <span class="d-flex align-items-center justify-content-between gap-3">
                                                <span
                                                    class="fw-medium">{{ $isChildProfile == 0 ? __('frontend.account_setting') : __('frontend.my_watchlist') }}</span>
                                                <i class="ph ph-caret-right"></i>
                                            </span>
                                        </a>
                                    </li>

                                    @if ($isChildProfile != 0)
                                        <li>
                                            <a href="{{ route('unlock.videos') }}"
                                                class="link-body-emphasis font-size-14">
                                                <span class="d-flex align-items-center justify-content-between gap-3">
                                                    <span class="fw-medium">{{ __('frontend.rent_videos') }}</span>
                                                    <i class="ph ph-caret-right"></i>
                                                </span>
                                            </a>
                                        </li>
                                    @endif


                                    <li>
                                        <a href="#" class="link-primary font-size-14" data-bs-toggle="modal"
                                            data-bs-target="#LogoutModal">
                                            <span class="d-flex align-items-center justify-content-between gap-3">
                                                <span class="fw-medium">{{ __('frontend.logout') }}</span>
                                            </span>
                                        </a>
                                    </li>
                            </ul>

                        </div>

                        </li>
                    @else
                        <li class="nav-item">
                            <a href="{{ route('login') }}" class="btn btn-primary font-size-14 login-btn">
                                {{ __('frontend.login') }}
                            </a>
                        </li>
                        @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </nav>
</header>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="LogoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-acoount-card">
        <div class="modal-content position-relative">
            <button type="button" class="btn btn-primary custom-close-btn rounded-2" data-bs-dismiss="modal">
                <i class="ph ph-x text-white fw-bold align-middle"></i>
            </button>
            <div class="modal-body modal-acoount-info text-center">
                <h6 class="mt-3 pt-2">{{ __('messages.logout_confirmation') }}</h6>
                <p class="text-muted mt-3">{{ __('messages.logout_confirmation_message') }}</p>
                <div class="d-flex justify-content-center gap-3 mt-4 pt-3">
                    <button type="button" class="btn btn-dark"
                        data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="logoutConfirmBtn"
                        onclick="confirmLogout()">{{ __('frontend.logout') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />

<script>
    // Logout confirmation function
    function confirmLogout() {
        const logoutButton = document.getElementById('logoutConfirmBtn');
        if (!logoutButton) {
            return;
        }


        logoutButton.disabled = true;
        logoutButton.textContent = '{{ __('messages.logging_out') }}';

        if (window.successSnackbar) {
            window.successSnackbar('{{ __('messages.user_logout') }}');
            setTimeout(function() {
                window.location.href = "{{ route('user-logout') }}";
            }, 1000);
        } else {
            window.location.href = "{{ route('user-logout') }}";
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        const toggle = document.getElementById('security_toggle');
        const securityControlSection = document.getElementById('security_control_section');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!csrfToken) {
            // console.error('CSRF token not found'); // Removed as per instruction
            return;
        }

        var securityToggle = document.getElementById('security_toggle');
        var toggleLabel = securityToggle?.closest('label');

        if (securityToggle) {
            securityToggle.addEventListener('click', function(event) {
                event.stopPropagation();
            });
            securityToggle.addEventListener('mousedown', function(event) {
                event.stopPropagation();
            });
        }
        if (toggleLabel) {
            toggleLabel.addEventListener('click', function(event) {
                event.stopPropagation();
            });
            toggleLabel.addEventListener('mousedown', function(event) {
                event.stopPropagation();
            });
        }

        if (toggle) {
            const dropdownItem = toggle.closest('.dropdown-item-flex');
            if (dropdownItem) {
                dropdownItem.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }

            toggle.addEventListener('change', function() {
                toggle.disabled = true;

                if (this.checked) {
                    const securityUrl = this.dataset.securityUrl;

                    fetch(securityUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                toggle.checked = true;
                                securityControlSection.classList.remove('d-none');

                            } else {
                                throw new Error('Failed to enable security');
                            }
                        })
                        .catch(error => {
                            console.error('Error details:', error);
                            toggle.checked = false;
                            securityControlSection.classList.add('d-none');
                            window.successSnackbar('{{ __('frontend.error_enabling_security') }}');
                        })
                        .finally(() => {
                            toggle.disabled = false;
                        });

                } else {
                    const disableUrl = this.dataset.disableUrl;

                    fetch(disableUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.status === 'success') {
                                toggle.checked = false;
                                securityControlSection.classList.add('d-none');
                            } else {
                                throw new Error('Failed to disable security');
                            }
                        })
                        .catch(error => {
                            console.error('Error details:', error);
                            toggle.checked = true;
                            securityControlSection.classList.remove('d-none');
                            window.successSnackbar(
                                '{{ __('frontend.error_disabling_security') }}');
                        })
                        .finally(() => {
                            toggle.disabled = false;
                        });
                }
            });
        }
    });
</script>

<script>
    window.searchConfig = {
        isLoggedIn: {{ Auth::check() ? 'true' : 'false' }},
        translations: {
            placeholder: "{{ __('frontend.search_placeholder') }}",
            movies_tv: "{{ __('frontend.movies_tv') }}",
            cast_crew: "{{ __('frontend.cast_crew') }}",
            genres: "{{ __('frontend.genres') }}",
            no_record: "{{ __('messages.no_results_found') ?? 'No results found' }}"
        }
    };
</script>
