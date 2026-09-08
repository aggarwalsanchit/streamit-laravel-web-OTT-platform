<?php
$notifications_count = optional(auth()->user())->unreadNotifications->count();
$headerNavbarShow = getCustomizationSetting('header_navbar_show');
// Check if navbar should be hidden
$navbarHide = false;
if (!empty($headerNavbarShow)) {
    if (is_array($headerNavbarShow)) {
        $navbarHide = in_array('iq-navbar-none', $headerNavbarShow);
    } elseif (is_string($headerNavbarShow) && $headerNavbarShow === 'iq-navbar-none') {
        $navbarHide = true;
    }
}
$navbarClass = $navbarHide ? 'd-none' : (!empty(getCustomizationSetting('navbar_show')) ? getCustomizationSetting('navbar_show') : '');
?>
<nav
    class="nav navbar navbar-expand-xl navbar-light iq-navbar header-hover-menu left-border {{ $navbarClass }} {{ getCustomizationSetting('header_navbar') }}">
    <div class="container-fluid navbar-inner">
        <a href="{{ route('backend.home') }}" class="navbar-brand">
            <div class="logo-main">
                <div class="logo-mini d-none">
                    <img src="{{ asset(setting('mini_logo')) }}" height="30" alt="{{ app_name() }}">
                </div>
                <div class="logo-normal">
                    <img src="{{ asset(setting('logo')) }}" height="30" alt="{{ app_name() }}">
                    {{-- <h4 class="logo-title d-none d-sm-block">{{app_name()}}</h4> --}}
                </div>
                <div class="logo-dark">
                    <img src="{{ asset(setting('dark_logo')) }}" height="30" alt="{{ app_name() }}">
                </div>
            </div>
        </a>
        <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
            <i class="icon d-flex">
                <i class="ph ph-caret-left"></i>
            </i>
        </div>

        <!-- horizontal header code -->

        <div class="d-flex align-items-center">
            <button id="navbar-toggle" class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon">
                    <span class="navbar-toggler-bar bar1 mt-1"></span>
                    <span class="navbar-toggler-bar bar2"></span>
                    <span class="navbar-toggler-bar bar3"></span>
                </span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto align-items-center navbar-list gap-3">
                <li class="nav-item dropdown iq-dropdown">
                    <a class="nav-link" data-bs-toggle="dropdown" href="#">
                        <div class="d-flex align-items-center gap-3 btn btn-warning px-2 py-2">
                            <i class="ph ph-plus-circle fs-5"></i>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-widget dropdown-menu-end">
                        <div class="widget-data grid-container">
                            <!-- Plan -->
                            <a href="{{ route('backend.plans.create') }}" class="widget-card">
                                <span class="icon-wrapper">
                                    <i class="ph ph-list-dashes"></i>
                                </span>
                                <span class="label">{{ __('dashboard.lbl_plan') }}</span>
                            </a>

                            <!-- Subscription -->
                            <a href="{{ route('backend.subscriptions.create') }}" class="widget-card">
                                <span class="icon-wrapper">
                                    <i class="ph ph-crown"></i>
                                </span>
                                <span class="label">{{ __('dashboard.lbl_subscription') }}</span>
                            </a>

                            <!-- Dashboard Setting -->
                            <a href="{{ route('backend.settings.general') }}" class="widget-card">
                                <span class="icon-wrapper">
                                    <i class="ph ph-gear"></i>
                                </span>
                                <span class="label">{{ __('dashboard.lbl_setting') }}</span>
                            </a>

                            <!-- Coupon -->
                            <a href="{{ route('backend.coupon.create') }}" class="widget-card">
                                <span class="icon-wrapper">
                                    <i class="ph ph-ticket"></i>
                                </span>
                                <span class="label">{{ __('dashboard.lbl_coupon') }}</span>
                            </a>

                            <!-- Vast Ads -->
                            <a href="{{ route('backend.vastads.create') }}" class="widget-card">
                                <span class="icon-wrapper">
                                    <i class="ph ph-megaphone"></i>
                                </span>
                                <span class="label">{{ __('dashboard.lbl_vast_ads') }}</span>
                            </a>

                            <!-- Users -->
                            <a href="{{ route('backend.users.create') }}" class="widget-card">
                                <span class="icon-wrapper">
                                    <i class="ph ph-users"></i>
                                </span>
                                <span class="label">{{ __('dashboard.lbl_users') }}</span>
                            </a>
                        </div>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <div class="d-flex align-items-center mr-2 iq-font-style" role="group"
                        aria-label="First group">
                        <input type="radio" class="btn-check" name="theme_font_size" value="theme-fs-sm"
                            id="font-size-sm" checked>
                        <label for="font-size-sm" class="btn btn-border border-0 btn-icon btn-sm"
                            data-bs-toggle="tooltip" title="{{ __('messages.font_size_14px') }}" data-bs-placement="bottom">
                            <span class="mb-0 h6" style="color: inherit !important;">A</span>
                        </label>
                        <input type="radio" class="btn-check" name="theme_font_size" value="theme-fs-md"
                            id="font-size-md">
                        <label for="font-size-md" class="btn btn-border border-0 btn-icon" data-bs-toggle="tooltip"
                            title="{{ __('messages.font_size_16px') }}" data-bs-placement="bottom">
                            <span class="mb-0 h4" style="color: inherit !important;">A</span>
                        </label>
                        <input type="radio" class="btn-check" name="theme_font_size" value="theme-fs-lg"
                            id="font-size-lg">
                        <!-- <label for="font-size-lg" class="btn btn-border border-0 btn-icon" data-bs-toggle="tooltip"
                            title="{{ __('messages.font_size_18px') }}" data-bs-placement="bottom">
                            <span class="mb-0 h2" style="color: inherit !important;">A</span>
                        </label> -->
                    </div>
                </li>
                <li class="nav-item dropdown iq-dropdown">
                    <a class="nav-link btn btn-primary btn-icon btn-sm rounded-pill btn-action p-0"
                        data-bs-toggle="dropdown" href="#">
                        <div class="iq-sub-card">
                            <div class="d-flex align-items-center notification_list">
                                <span class="btn-inner">
                                    <i class="ph ph-bell"></i>
                                </span>
                                @if ($notifications_count > 0)
                                    <span class="notification-alert">{{ $notifications_count }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                    <ul class="p-0 sub-drop dropdown-menu dropdown-notification dropdown-menu-end">
                        <div class="m-0 shadow-none card bg-transparent notification_data"></div>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link d-flex align-items-center p-0" data-bs-toggle="dropdown" href="#"
                        role="button" aria-haspopup="true" aria-expanded="false"
                        style="color: inherit !important;">
                        <img src="{{ asset('flags/' . App::getLocale() . '.png') }}" alt="flag"
                            class="img-fluid me-2" style="width: 20px; height: auto; min-width: 15px;"
                            onerror="this.onerror=null; this.src='{{ asset('flags/globe.png') }}';">
                        {{ strtoupper(App::getLocale()) }}
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header bg-primary-subtle py-2 rounded">
                            <div class="fw-semibold">{{ __('messages.change_language') }}</div>
                        </div>
                        @foreach (config('app.available_locales') as $locale => $title)
                            <a class="dropdown-item {{ App::getLocale() == $locale ? 'active' : '' }}" href="{{ route('language.switch', $locale) }}">
                                <span class="d-flex align-items-center gap-3">
                                    <img src="{{ asset('flags/' . $locale . '.png') }}" alt="flag"
                                        class="img-fluid mr-2"style="width: 20px;height: auto;min-width: 15px;">
                                    <span>{{ $title }}</span>
                                    @if(App::getLocale() == $locale)
                                        <span class="text-success"><i
                                                class="ph-fill ph-check-fat align-middle"></i></span>
                                    @endif
                                </span>
                            </a>
                        @endforeach
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link p-0" data-bs-toggle="dropdown" href="#" role="button"
                        aria-haspopup="true" aria-expanded="false">
                        <div class="avatar avatar-md">
                            <img class="avatar avatar-40 img-fluid rounded-pill"
                                src="{{ setBaseUrlWithFileName(auth()->user()->file_url, 'image', 'users') }}"
                                alt="{{ auth()->user()->name ?? default_user_name() }}" loading="lazy">
                        </div>
                    </a>
                    <ul class="dropdown-menu sub-drop dropdown-menu-end">
                        <div class="dropdown-header bg-primary-subtle py-3 rounded">
                            <div class="d-flex gap-2">
                                <img class="avatar avatar-40 img-fluid rounded-pill"
                                    src="{{ setBaseUrlWithFileName(auth()->user()->file_url, 'image', 'users') }}" />
                                <div class="d-flex flex-column align-items-start">
                                    <h5 class="m-0 text-primary">{{ Auth::user()->full_name ?? default_user_name() }}
                                    </h5>
                                    <span class="text-muted">{{ Auth::user()->email ?? 'abc@email.com' }}</span>
                                </div>
                            </div>
                        </div>
                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center"
                                href="{{ route('backend.my-profile') }}">{{ __('messages.myprofile') }}<i
                                    class="ph ph-user"></i></a>
                        </li>
                        @role('admin|demo_admin')
                            <li>
                                <a class="dropdown-item d-flex justify-content-between align-items-center"
                                    href="{{ route('backend.settings.general') }}">
                                    @lang('settings.title') <i class="ph ph-gear"></i>
                                </a>
                            </li>
                        @endrole

                        <li>
                            <a class="dropdown-item d-flex justify-content-between align-items-center"
                                href="{{ route('admin-logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                @lang('messages.logout')<i class="ph ph-sign-out"></i>
                            </a>
                        </li>
                        <form id="logout-form" action="{{ route('admin-logout') }}" method="POST"
                            style="display: none;"> @csrf </form>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

@push('after-scripts')
    <script type="text/javascript">
        $(document).ready(function() {

            $('.change-mode').on('click', function() {
                const element = document.querySelector('html');
                let value = element.getAttribute('data-bs-theme');
                sessionStorage.setItem('theme_mode', value !== 'dark' ? 'dark' : 'light');
                if (value !== 'dark') {
                    $('html').attr('data-bs-theme', 'dark');
                } else {
                    $('html').attr('data-bs-theme', 'light');
                }
            });

            $('input[name="theme_font_size"]').on('change', function() {
                const font = $('[name="theme_font_size"]').map(function() {
                    return $(this).attr('value')
                }).get();
                $('html').removeClass(font).addClass($(this).val());
            });
            $('.notification_list').on('click', function() {
                notificationList();
            });
        });



        function notification_count() {

            var url = "{{ route('notification.counts') }}";
            $.ajax({
                type: 'get',
                url: url,
                success: function(res) {

                    console.log(res);


                }
            });


        }

        function notificationList(type = '') {
            var url = "{{ route('notification.list') }}";
            $.ajax({
                type: 'get',
                url: url,
                data: {
                    'type': type
                },
                success: function(res) {

                    $('.notification_data').html(res.data);
                    getNotificationCounts();
                    if (res.type == "markas_read") {
                        notificationList();
                    }
                    $('.notify_count').removeClass('notification_tag').text('');
                }
            });
        }
    </script>
@endpush
