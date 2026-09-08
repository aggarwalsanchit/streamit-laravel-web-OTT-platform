<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark"
    dir="{{ session()->has('dir') ? session()->get('dir') : 'ltr' }}"
    data-bs-theme-color={{ getCustomizationSetting('theme_color') }}>

<head>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="baseUrl" content="{{ url('/') }}" />
    @php
        $faviconUrl = GetSettingValue('favicon')
            ? setBaseUrlWithFileName(GetSettingValue('favicon'), 'image', 'logos')
            : asset('img/logo/favicon.png');
    @endphp
    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', GetSettingValue('app_name'))</title>

    @include('frontend::layouts.head')

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;1,100;1,300&amp;display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @php
        $frontendStylePath = public_path('modules/frontend/style.css');
        $frontendStyleVersion = is_file($frontendStylePath) ? filemtime($frontendStylePath) : time();
        $searchCssPath = public_path('css/search.css');
        $searchCssVersion = is_file($searchCssPath) ? filemtime($searchCssPath) : time();
        $mobileHubCssPath = public_path('css/mobile-hub-tabs.css');
        $mobileHubCssVersion = is_file($mobileHubCssPath) ? filemtime($mobileHubCssPath) : time();
        $searchJsPath = public_path('js/search.js');
        $searchJsVersion = is_file($searchJsPath) ? filemtime($searchJsPath) : time();
        $showSdMobileHub = function_exists('short_drama_show_mobile_hub') && short_drama_show_mobile_hub();
    @endphp
    <link rel="stylesheet" href="{{ asset('modules/frontend/style.css') }}?v={{ $frontendStyleVersion }}">
    <link rel="stylesheet" href="{{ asset('css/search.css') }}?v={{ $searchCssVersion }}">
    @if (! empty($showSdMobileHub))
        <link rel="stylesheet" href="{{ asset('css/mobile-hub-tabs.css') }}?v={{ $mobileHubCssVersion }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/customizer.css') }}">

    <link rel="stylesheet" href="{{ asset('iconly/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/regular/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/fill/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/bold/style.css') }}">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    @include('frontend::components.partials.head.plugins')
    @stack('after-styles')

    {{-- Custom CSS from admin settings (wrapped in <style> like admin) --}}
    <style>
        {!! setting('custom_css_block') !!}
    </style>

    @php
        $currentLang = App::currentLocale();
        $langFolderPath = base_path("lang/$currentLang");
        $filePaths = \File::files($langFolderPath);
    @endphp

    @foreach ($filePaths as $filePath)
        @php
            $fileName = pathinfo($filePath, PATHINFO_FILENAME);
            $arr = require $filePath;
        @endphp
        <script>
            window.localMessagesUpdate = {
                ...window.localMessagesUpdate,
                "{{ $fileName }}": @json($arr)
            }
        </script>
    @endforeach

    <!-- Slider Fallback CSS - Display content horizontally when slick fails -->
    <style>
        /* Fallback styles for when slick carousel fails to initialize */
        .slick-general:not(.slick-initialized) {
            display: flex !important;
            flex-wrap: nowrap !important;
            gap: 12px !important;
            padding: 0 !important;
            margin: 0 !important;

        }

        .slick-general:not(.slick-initialized)>* {
            flex: 0 0 260px !important;
            /* desktop card width */
            width: 260px !important;
            min-width: 260px !important;
            max-width: 260px !important;
            display: block !important;
        }

        /* Hide slick arrows and dots when not initialized */
        .slick-general:not(.slick-initialized) .slick-arrow,
        .slick-general:not(.slick-initialized) .slick-dots {
            display: none !important;
        }

        /* Responsive fallback - match slick breakpoints */

        @media (max-width: 768px) {
            .slick-general:not(.slick-initialized)>* {
                flex-basis: 180px !important;
                width: 180px !important;
                min-width: 180px !important;
                max-width: 180px !important;
            }
        }

        @media (max-width: 576px) {
            .slick-general:not(.slick-initialized)>* {
                flex-basis: 160px !important;
                width: 160px !important;
                min-width: 160px !important;
                max-width: 160px !important;
            }
        }

        /* Ensure proper spacing for card-style-slider */
        .card-style-slider .slick-general:not(.slick-initialized) {
            margin-bottom: 3.75rem !important;
            overflow: visible !important;
            /* add bottom space between sections */
        }

        .card-style-slider .slick-general:not(.slick-initialized)>* {
            margin: 0 6px !important;
        }
    </style>

    <style>
        /* ========== Full Page Loader Styles ========== */
        #page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: var(--bs-body-bg);
            /* matches your dark theme */
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }

        #page-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .loader-wrapper {
            text-align: center;
        }



        .loader-text {
            color: #fff;
            font-family: "Roboto", sans-serif;
            font-size: 18px;
            letter-spacing: 1px;
        }
    </style>

</head>

<style>
    :root {
        <?php
        $rootColors = setting('root_colors'); // Assuming the setting() function retrieves the JSON string
        
        // Check if the JSON string is not empty and can be decoded
        if (!empty($rootColors) && is_string($rootColors)) {
            $colors = json_decode($rootColors, true);
        
            // Check if decoding was successful and the colors array is not empty
            if (json_last_error() === JSON_ERROR_NONE && is_array($colors) && count($colors) > 0) {
                foreach ($colors as $key => $value) {
                    echo $key . ': ' . $value . '; ';
                }
            } else {
                echo 'Invalid JSON or empty colors array.';
            }
        }
        ?>
    }
</style>

@php
    $showSdMobileHub = $showSdMobileHub ?? (function_exists('short_drama_show_mobile_hub') && short_drama_show_mobile_hub());
    $sdMobileHubHome = ! empty($showSdMobileHub) && request()->routeIs('user.login');
@endphp
<body class="d-flex flex-column min-vh-100 {{ Route::currentRouteName() == 'search' ? 'search-page' : '' }}{{ ! empty($showSdMobileHub) ? ' sd-has-mobile-hub' : '' }}{{ ! empty($sdMobileHubHome) ? ' sd-mobile-hub-home' : '' }}">

    <div id="page-loader">
        <div class="loader-wrapper">
            @php
                $loader_gif = GetSettingValue('loader_gif')
                    ? setBaseUrlWithFileName(GetSettingValue('loader_gif'), 'image', 'logos')
                    : asset('img/logo/loader.gif');
            @endphp
            <img src="{{ $loader_gif }}" alt="Loading..." class="loader-gif" width="100" height="100">
            {{-- <div class="loader-text">Loading...</div> --}}
        </div>
    </div>
    @include('frontend::layouts.header')

    <main class="{{ ! empty($showSdMobileHub) ? 'main--sd-hub' : 'flex-fill' }}">
        @yield('content')
    </main>
    {{-- @include('frontend::components.card.card_detail') --}}

    @include('frontend::layouts.footer')

    @include('frontend::components.partials.scripts.plugins')


    @include('frontend::components.partials.back-to-top')
    <script src="{{ mix('modules/frontend/script.js') }}" defer></script>
    <script src="{{ mix('js/backend-custom.js') }}" defer></script>




    <script>
        document.addEventListener("readystatechange", () => {
            if (document.readyState === "complete") {
                hideLoader();
            }
        });

        window.addEventListener("load", () => {
            hideLoader();
        });
        // Hide loader smoothly
        function hideLoader() {
            const loader = document.getElementById("page-loader");
            if (!loader) return;
            loader.classList.add("hidden");
            setTimeout(() => loader.remove(), 600);
        }

        setTimeout(() => {
            const loader = document.getElementById("page-loader");
            if (loader) hideLoader();
        }, 8000);
    </script>




    @if (session('success'))
        @php
            // Get current user's device count and new plan's device limit
            $user = auth()->user();
            $deviceCount = \App\Models\Device::where('user_id', $user->id)->count();
            $planDeviceLimit = 1; // Default limit

            if ($user->subscriptionPackage && !empty($user->subscriptionPackage->plan_type)) {
                $planLimitations = json_decode($user->subscriptionPackage->plan_type, true);
                if (is_array($planLimitations)) {
                    foreach ($planLimitations as $limitation) {
                        if (isset($limitation['slug']) && $limitation['slug'] === 'device-limit') {
                            $limitData = $limitation['limit'] ?? null;
                            if (is_array($limitData) && isset($limitData['value'])) {
                                $planDeviceLimit = (int) $limitData['value'];
                            } elseif (is_string($limitData) || is_numeric($limitData)) {
                                $planDeviceLimit = (int) $limitData;
                            }
                            break;
                        }
                    }
                }
            }

            $showLogoutAll = $deviceCount > $planDeviceLimit;
            $logoutMessage =
                __('messages.logout_all_text') .
                ' (' .
                $deviceCount .
                ' ' .
                __('messages.devices_logged_in') .
                ', ' .
                __('messages.limit_is') .
                ' ' .
                $planDeviceLimit .
                ')';
        @endphp
        <script>
            const messages = {
                logout_all_title: "{{ __('messages.logout_all_title') }}",
                logout_all_text: "{{ __('messages.logout_all_text') }}",
                logout_all_button: "{{ __('messages.logout_all_button') }}",
                continue_button: "{{ __('messages.continue_button') }}",
                lbl_plan: "{{ __('messages.lbl_plan') }}",
                lbl_amount: "{{ __('messages.lbl_amount') }}",
                lbl_valid_until: "{{ __('messages.lbl_valid_until') }}",
            };
            const showLogoutAll = {{ $showLogoutAll ? 'true' : 'false' }};
            const logoutMessage = "{{ $logoutMessage }}";
            const deviceCount = {{ $deviceCount }};
            const planDeviceLimit = {{ $planDeviceLimit }};

            document.addEventListener('DOMContentLoaded', function() {
                document.body.setAttribute('data-swal2-theme', 'dark');
                Swal.fire({
                    icon: 'success',
                    title: "{{ __('messages.payment_successful_title') }}",
                    html: `
            <div class="text-center">
                <p>{{ __('messages.subscription_activated_message') }}</p>
                <div class="mt-3">
                    <p><strong>${messages.lbl_plan}:</strong> {{ session('success.plan_name') }}</p>
                    <p><strong>${messages.lbl_amount}:</strong> {{ session('success.amount') }}</p>
                    <p><strong>${messages.lbl_valid_until}:</strong> {{ session('success.valid_until') }}</p>
                </div>
            </div>
        `,
                    showConfirmButton: true,
                    confirmButtonText: messages.continue_button,
                    confirmButtonColor: 'var(--bs-primary)',
                    iconColor: 'var(--bs-primary)',
                    customClass: {
                        icon: 'swal2-icon-red'
                    }
                }).then(function() {
                    // If device limit is exceeded, call logout API and redirect to login with message
                    if (showLogoutAll) {
                        const baseUrl = document.querySelector('meta[name="baseUrl"]')?.getAttribute(
                            'content') || '';
                        const logoutMessage = messages.logout_all_text +
                            ` (${deviceCount} {{ __('messages.devices_logged_in') ?? 'devices logged in' }}, {{ __('messages.limit_is') ?? 'limit is' }} ${planDeviceLimit})`;

                        fetch(baseUrl + '/api/logout-all-data', {
                                method: 'GET',
                                credentials: 'same-origin'
                            })
                            .then(function() {
                                // Redirect to login page with message in query parameter
                                const loginUrl = baseUrl + '/login';
                                localStorage.setItem('logout_message', logoutMessage);
                                window.location.href = loginUrl;
                            })
                            .catch(function(error) {
                                console.error('Error:', error);
                                // Still redirect to login even if API call fails
                                const loginUrl = baseUrl + '/login';
                                localStorage.setItem('logout_message', logoutMessage);
                                window.location.href = loginUrl;
                            });
                    } else {
                        window.location.reload();
                    }
                });
            });
        </script>

        <style>
            .swal2-icon-red {
                border-color: var(--bs-primary) !important;
                color: var(--bs-primary) !important;
            }
        </style>
    @endif



    @if (session('purchase_success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const purchaseMessages = {
                    purchase_successful_title: "{{ __('messages.purchase_successful_title') }}",
                    purchase_successful_message: "{{ __('messages.purchase_successful_message') }}",
                    enjoy_until: "{{ __('messages.enjoy_until') }}",
                    begin_watching: "{{ __('messages.begin_watching') }}",
                };
                document.body.setAttribute('data-swal2-theme', 'dark');
                Swal.fire({
                    icon: 'success',
                    html: `
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 60px;"></div>
                    <h2 class="text-heading" style="margin: 15px 0 10px; font-size: 21px;">${purchaseMessages.purchase_successful_title}</h2>
                    <p class="text-body" style="font-size: 16px;">${purchaseMessages.purchase_successful_message} {{ session('movie_name') }}.</p>
                    <p class="text-body" style="font-size: 14px;">${purchaseMessages.enjoy_until} {{ session('view_expiry') }}.</p>
                </div>
            `,
                    showConfirmButton: true,
                    confirmButtonText: purchaseMessages.begin_watching,
                    confirmButtonColor: 'var(--bs-primary)',
                    iconColor: 'var(--bs-primary)', // Added to make the success icon match the primary color
                    customClass: {
                        icon: 'swal2-icon-red' // Added custom class for icon color
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "{{ route('unlock.videos') }}";
                    }
                });
            });
        </script>
    @endif



    <!--- chrome cast  --->
    <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1">
    </script>
    <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js"></script>
    <script src="{{ asset('js/script.js') }}" defer></script>
    <script src="{{ asset('js/search.js') }}?v={{ $searchJsVersion }}" defer></script>
    @if (! empty($showSdMobileHub))
        <script>
            (function () {
                function syncSdSiteHeaderHeight() {
                    var header = document.querySelector('header');
                    if (!header) {
                        return;
                    }
                    document.documentElement.style.setProperty('--sd-site-header-height', header.offsetHeight + 'px');
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', syncSdSiteHeaderHeight);
                } else {
                    syncSdSiteHeaderHeight();
                }
                window.addEventListener('resize', syncSdSiteHeaderHeight);
            })();
        </script>
    @endif
    @stack('after-scripts')

    {{-- Custom JS from admin settings (wrapped in <script> like auth/guest layouts) --}}
    <script>
        {!! setting('custom_js_block') !!}
    </script>

    <script>
        (function() {
            let hlsLibraryPromise = null;

            const loadHlsLibrary = () => {
                if (hlsLibraryPromise) return hlsLibraryPromise;
                hlsLibraryPromise = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/hls.js@latest';
                    script.onload = () => resolve(window.Hls);
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
                return hlsLibraryPromise;
            };

            const convertToYouTubeEmbed = (url) => {
                try {
                    const parsedUrl = new URL(url);
                    const videoId = parsedUrl.hostname.includes('youtu.be') ?
                        parsedUrl.pathname.slice(1) :
                        parsedUrl.searchParams.get('v');
                    return videoId ? `https://www.youtube.com/embed/${videoId}` : null;
                } catch {
                    return null;
                }
            };

            const convertToVimeoEmbed = (url) => {
                try {
                    const parsedUrl = new URL(url);
                    if (!parsedUrl.hostname.includes('vimeo.com')) return null;
                    const videoId = parsedUrl.pathname.split('/').filter(Boolean).pop();
                    return videoId ? `https://player.vimeo.com/video/${videoId}` : null;
                } catch {
                    return null;
                }
            };

            const addAutoplayParameters = (src) => {
                if (!src) return src;
                const separator = src.includes('?') ? '&' : '?';
                const baseParams = 'autoplay=1&muted=1&controls=0&playsinline=1&loop=1';
                const lowerSrc = src.toLowerCase();

                if (lowerSrc.includes('youtube.com')) {
                    return `${src}${separator}${baseParams}&enablejsapi=1&mute=1`;
                }
                if (lowerSrc.includes('vimeo.com')) {
                    return `${src}${separator}${baseParams}&api=1`;
                }
                return `${src}${separator}${baseParams}`;
            };

            const commonPlayerStyles = {
                position: 'absolute',
                top: '0',
                left: '0',
                width: '100%',
                height: '55%',
                objectFit: 'cover',
                pointerEvents: 'none'
            };

            const createIframePlayer = (src) => {
                const iframe = document.createElement('iframe');
                iframe.src = addAutoplayParameters(src);
                iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
                iframe.loading = 'lazy';
                Object.assign(iframe.style, commonPlayerStyles, {
                    border: '0'
                });
                return iframe;
            };

            const createVideoPlayer = (src) => {
                const video = document.createElement('video');
                video.src = src;
                video.autoplay = false;
                video.muted = true;
                video.loop = true;
                video.playsInline = true;
                video.preload = 'metadata';
                Object.assign(video.style, commonPlayerStyles);
                return video;
            };

            const getPlayerForUrl = async (url, type) => {
                const trimmedUrl = (url || '').trim();

                // Handle raw iframe HTML
                if (trimmedUrl.toLowerCase().startsWith('<iframe')) {
                    const srcMatch = trimmedUrl.match(/src=["']([^"']+)/i);
                    if (srcMatch && srcMatch[1]) {
                        return createIframePlayer(srcMatch[1]);
                    }
                }

                const lowerType = (type || '').toLowerCase();

                // YouTube
                if (lowerType === 'youtube') {
                    const embedUrl = convertToYouTubeEmbed(url);
                    return embedUrl ? createIframePlayer(embedUrl) : null;
                }

                // Vimeo
                if (lowerType === 'vimeo') {
                    const embedUrl = convertToVimeoEmbed(url);
                    return embedUrl ? createIframePlayer(embedUrl) : null;
                }

                // Generic embed
                if (lowerType === 'embed' || lowerType === 'embedded') {
                    return createIframePlayer(url);
                }

                // HLS streaming
                if (lowerType === 'hls') {
                    const testVideo = document.createElement('video');
                    const supportsNativeHls = testVideo.canPlayType('application/vnd.apple.mpegURL');

                    if (supportsNativeHls) {
                        return createVideoPlayer(url);
                    }

                    try {
                        const Hls = await loadHlsLibrary();
                        if (Hls?.isSupported()) {
                            const video = createVideoPlayer('');
                            const hlsInstance = new Hls();
                            hlsInstance.loadSource(url);
                            hlsInstance.attachMedia(video);
                            video.hlsInstance = hlsInstance;
                            return video;
                        }
                    } catch (error) {
                        console.warn('HLS.js failed to load:', error);
                    }
                }

                // Default to direct video
                return createVideoPlayer(url);
            };

            const isYouTubeIframe = (iframe) => iframe.src?.toLowerCase().includes('youtube.com/embed/');
            const isVimeoIframe = (iframe) => iframe.src?.toLowerCase().includes('player.vimeo.com');

            const sendMessageToIframe = (iframe, message) => {
                try {
                    iframe.contentWindow?.postMessage(JSON.stringify(message), '*');
                } catch (error) {
                    console.warn('Failed to send message to iframe:', error);
                }
            };

            const controlYouTubePlayer = (iframe, command) => {
                sendMessageToIframe(iframe, {
                    event: 'command',
                    func: command,
                    args: []
                });
            };

            const controlVimeoPlayer = (iframe, method, value) => {
                const message = {
                    method
                };
                if (value !== undefined) message.value = value;
                sendMessageToIframe(iframe, message);
            };

            const createMuteButton = (onToggle) => {
                const button = document.createElement('button');
                button.textContent = 'Unmute';
                Object.assign(button.style, {
                    position: 'absolute',
                    top: '10px',
                    right: '10px',
                    zIndex: '5',
                    padding: '5px 10px',
                    background: 'rgba(0,0,0,0.5)',
                    color: '#fff',
                    border: 'none',
                    cursor: 'pointer',
                    borderRadius: '3px',
                    pointerEvents: 'auto'
                });
                button.addEventListener('click', (event) => {
                    event.stopPropagation();
                    onToggle(button);
                });
                return button;
            };

            const initializeTrailerHover = () => {
                const cards = document.querySelectorAll('[data-trailer-url][data-trailer-scope="hover-modal"]');


                cards.forEach(card => {
                    const trailerUrl = card.getAttribute('data-trailer-url');
                    const trailerType = card.getAttribute('data-trailer-type');

                    if (!trailerUrl) return;

                    // Get or create preview container
                    let previewContainer = card.querySelector('.trailer-preview');
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.className =
                            'trailer-preview position-absolute top-0 start-0 w-100 h-100';
                        Object.assign(previewContainer.style, {
                            display: 'none',
                            zIndex: '3',
                            pointerEvents: 'none'
                        });
                        card.appendChild(previewContainer);
                    }

                    const thumbnailImage = card.querySelector('img') || card.closest('.image-box')
                        ?.querySelector('img');
                    if (!thumbnailImage) return;

                    let activePlayer = null;

                    const showPreview = async () => {
                        if (activePlayer) return;

                        activePlayer = await getPlayerForUrl(trailerUrl, trailerType);
                        if (!activePlayer) return;

                        previewContainer.innerHTML = '';
                        previewContainer.appendChild(activePlayer);
                        previewContainer.style.display = 'block';
                        thumbnailImage.style.visibility = 'hidden';

                        // Handle video element
                        if (activePlayer.tagName === 'VIDEO') {
                            activePlayer.play().catch(() => {});

                            const muteButton = createMuteButton((button) => {
                                activePlayer.muted = !activePlayer.muted;
                                button.textContent = activePlayer.muted ? 'Unmute' : 'Mute';
                            });
                            previewContainer.appendChild(muteButton);
                        }
                        // Handle iframe element
                        else if (activePlayer.tagName === 'IFRAME') {
                            if (isYouTubeIframe(activePlayer)) {
                                controlYouTubePlayer(activePlayer, 'playVideo');
                            } else if (isVimeoIframe(activePlayer)) {
                                controlVimeoPlayer(activePlayer, 'play');
                            }

                            // Add mute button for supported platforms
                            if (isYouTubeIframe(activePlayer) || isVimeoIframe(activePlayer)) {
                                let isMuted = true;
                                const muteButton = createMuteButton((button) => {
                                    isMuted = !isMuted;
                                    if (isYouTubeIframe(activePlayer)) {
                                        controlYouTubePlayer(activePlayer, isMuted ?
                                            'mute' : 'unMute');
                                    } else if (isVimeoIframe(activePlayer)) {
                                        controlVimeoPlayer(activePlayer, 'setVolume',
                                            isMuted ? 0 : 1);
                                    }
                                    button.textContent = isMuted ? 'Unmute' : 'Mute';
                                });
                                previewContainer.appendChild(muteButton);
                            }
                        }
                    };

                    const hidePreview = () => {
                        if (!activePlayer) return;

                        // Cleanup video
                        if (activePlayer.tagName === 'VIDEO') {
                            activePlayer.pause();
                            activePlayer.src = '';
                            activePlayer.load();
                            if (activePlayer.hlsInstance) {
                                activePlayer.hlsInstance.destroy();
                                activePlayer.hlsInstance = null;
                            }
                        }
                        // Cleanup iframe
                        else if (activePlayer.tagName === 'IFRAME') {
                            if (isYouTubeIframe(activePlayer)) {
                                controlYouTubePlayer(activePlayer, 'pauseVideo');
                            } else if (isVimeoIframe(activePlayer)) {
                                controlVimeoPlayer(activePlayer, 'pause');
                            }
                        }

                        previewContainer.innerHTML = '';
                        previewContainer.style.display = 'none';
                        thumbnailImage.style.visibility = 'visible';
                        activePlayer = null;
                    };

                    // Attach event listeners
                    card.addEventListener('mouseenter', showPreview);
                    card.addEventListener('focusin', showPreview);
                    card.addEventListener('mouseleave', hidePreview);
                    card.addEventListener('focusout', hidePreview);
                });
            };

            // Export to global scope
            window.initTrailerHover = initializeTrailerHover;

            // Auto-initialize on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeTrailerHover);
            } else {
                initializeTrailerHover();
            }
        })();
    </script>



    <script>
        const currencyFormat = (amount) => {
            const DEFAULT_CURRENCY = JSON.parse(@json(json_encode(Currency::getDefaultCurrency(true))))
            const noOfDecimal = DEFAULT_CURRENCY.no_of_decimal
            const decimalSeparator = DEFAULT_CURRENCY.decimal_separator
            const thousandSeparator = DEFAULT_CURRENCY.thousand_separator
            const currencyPosition = DEFAULT_CURRENCY.currency_position
            const currencySymbol = DEFAULT_CURRENCY.currency_symbol
            return formatCurrency(amount, noOfDecimal, decimalSeparator, thousandSeparator, currencyPosition,
                currencySymbol)
        }

        window.currencyFormat = currencyFormat
        window.defaultCurrencySymbol = @json(Currency::defaultSymbol())
    </script>
    <script>
        window.translations = {
            otp_send_success: @json(__('frontend.otp_send_success')),
            otp_send_error: @json(__('frontend.otp_send_error')),
            send_otp: @json(__('Send OTP')),
            sending: @json(__('frontend.sending')),
            send_otp: @json(__('frontend.send_otp')),
        }
    </script>

    <!-- Hover Modal Functionality -->
    <script>
        let hoverModal = null;
        let hoverTimeout = null;
        let lastMouseX = 0;
        let lastMouseY = 0;
        document.addEventListener('mousemove', (e) => {
            lastMouseX = e.clientX;
            lastMouseY = e.clientY;
        }, {
            passive: true
        });

        // Global hover modal functions
        window.openHoverModal = function(element) {
            clearTimeout(hoverTimeout);

            const movieId = element.getAttribute('data-movie-id');
            const movieData = JSON.parse(element.getAttribute('data-movie-data') || '{}');
            const isSearch = Number(element.getAttribute('data-is-search') || 0);

            if (!movieId || !movieData) return;

            hoverTimeout = setTimeout(() => {
                createHoverModal(movieId, movieData, element, isSearch);
            }, 300); // 300ms delay before showing
        };

        window.closeHoverModal = function(element) {
            clearTimeout(hoverTimeout);

            hoverTimeout = setTimeout(() => {
                if (hoverModal) {
                    hoverModal.remove();
                    hoverModal = null;
                }
            }, 100); // 100ms delay before hiding
        };


        function getContentUrl(movieData, isSearch = 0) {
            const isComingSoon = movieData.release_date && new Date(movieData.release_date) > new Date();
            let baseUrl;
            if (isComingSoon && (movieData.type === 'movie' || movieData.type === 'tvshow')) {
                baseUrl = `{{ route('comming-soon-details', ['id' => '__ID__']) }}`;
            } else if (movieData.type === 'movie') {
                baseUrl = `{{ route('movie-details', ['id' => '__ID__']) }}`;
            } else if (movieData.type === 'tvshow') {
                baseUrl = `{{ route('tvshow-details', ['id' => '__ID__']) }}`;
            } else if (movieData.type === 'video') {
                baseUrl = `{{ route('video-details', ['id' => '__ID__']) }}`;
            } else if (movieData.type === 'episode' || movieData.type === 'season') {
                baseUrl = `{{ route('episode-details', ['id' => '__ID__']) }}`;
            } else {
                baseUrl = `{{ route('movie-details', ['id' => '__ID__']) }}`;
            }
            baseUrl = baseUrl.replace('__ID__', movieData.episode_slug || movieData.slug || movieData.id);
            return Number(isSearch) === 1 ? baseUrl + (baseUrl.includes('?') ? '&' : '?') + 'is_search=1' : baseUrl;
        }

        function createHoverModal(movieId, movieData, element, isSearch = 0) {
            if (hoverModal) {
                hoverModal.remove();
            }

            const rect = element.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const isRTL = (() => {
                const dirAttr = (document.documentElement.getAttribute('dir') || document.body.getAttribute(
                    'dir') || '').toLowerCase();
                return dirAttr === 'rtl';
            })();

            const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

            const modalHTML = `
                <div class="movie-hover-modal" style="
                    position: fixed;
                    z-index: 9999;
                    background: rgba(0, 0, 0, 0.95);
                    border-radius: 12px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
                    color: white;
                    backdrop-filter: blur(10px);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                ">
                <div class="iq-card">
                    <div class="block-images position-relative w-100" data-trailer-scope="hover-modal" data-trailer-url="${(movieData.trailer_url || '').replace(/"/g, '&quot;')}" data-trailer-type="${movieData.trailer_url_type || ''}">
                        <div class="image-box w-100 position-relative">
                            <a href="${getContentUrl(movieData, isSearch)}" class="d-block w-100 h-100 position-absolute top-0 start-0" style="z-index: 1;"></a>
                            <img src="${movieData.poster_image || ''}" alt="movie-card"
                                class="img-fluid object-cover w-100 d-block border-0" loading="lazy" >
                                ${ movieData.is_pay_per_view
                                    ? (movieData.is_purchased
                                        ? `<span class="product-rent"><i class="ph ph-film-reel"></i> Rented</span>`
                                        : `<span class="product-rent"><i class="ph ph-film-reel"></i> Rent</span>`
                                    )
                                    : (movieData.show_premium_badge
                                        ? `<button type="button" class="product-premium border-0" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Premium"><i class="ph ph-crown-simple"></i></button>`
                                        : ``
                                    )
                                }
                            ${movieData.imdb_rating ?
                                `<span class="ratting-value">
                                                     <i class="ph ph-star"></i>
                                                        ${movieData.imdb_rating}
                                               </span>` : ''
                            }
                        </div>
                        <div class="card-description with-transition">
                            <div class="position-relative w-100">
                                <ul class="genres-list ps-0 mb-2 d-flex align-items-center gap-5">
                                    ${movieData.genres ? movieData.genres.slice(0, 2).map(g =>
                                        `<li class="small">${g.name || (g.resource?.genre?.name || '--')}</li>`
                                    ).join('') : ''}
                                </ul>

                                <h5 class="iq-title text-capitalize line-count-1">${movieData.name || '--'}</h5>
                                <p> ${movieData.tv_show_data || ''} </p>
                                <div class="d-flex align-items-center gap-3">
                                    ${movieData.duration ? `
                                                    <div class="movie-time d-flex align-items-center gap-1 font-size-14">
                                                        <i class="ph ph-clock"></i>
                                                            ${formatDuration(movieData.duration)}
                                                        </div>
                                                    ` : ''}
                                    ${movieData.language ? `
                                                <div class="movie-language d-flex align-items-center gap-1 font-size-14">
                                                    <i class="ph ph-translate"></i>
                                                        <small>${movieData.language ? movieData.language.charAt(0).toUpperCase() + movieData.language.slice(1) : '--'}</small>
                                                </div>
                                                ` : ''}
                                </div>

                                <div class="d-flex align-items-center gap-3 mt-3">
                                    ${(movieData.type !== 'episode' && movieData.type !== 'season') ? `
                                                <button id="watchlist-btn-${movieId}"
                                                    class="action-btn btn ${movieData.is_watch_list ? 'btn-primary' : 'btn-dark'} watch-list-btn"
                                                    data-entertainment-id="${movieId}"
                                                    data-in-watchlist="${movieData.is_watch_list ? 'true' : 'false'}"
                                                    data-entertainment-type=" ${movieData.type}"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-title="${movieData.is_watch_list ? 'Remove from Watchlist' : 'Add to Watchlist'}"
                                                    data-bs-placement="top"
                                                    onclick="toggleWatchlist(${movieId})">
                                                    <i class="ph ${movieData.is_watch_list ? 'ph-check' : 'ph-plus'}"></i>
                                                </button>
                                            ` : ''}

                                    <div class="flex-grow-1">
                                        <a href="${getContentUrl(movieData, isSearch)}" class="btn btn-primary w-100">
                                            {{ __('frontend.watch_now') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            `;


            hoverModal = document.createElement('div');
            hoverModal.innerHTML = modalHTML;
            hoverModal = hoverModal.firstElementChild;
            hoverModal.style.position = 'fixed';
            hoverModal.style.visibility = 'hidden';
            document.body.appendChild(hoverModal);

            const modalWidth = hoverModal.offsetWidth || rect.width * 1.2; // measure rendered width
            const modalHeight = hoverModal.offsetHeight || rect.height * 1.2; // measure rendered height

            // Center over the card but move slightly upward
            let tentativeLeft = rect.left - (modalWidth - rect.width) / 2;
            let top = rect.top - (modalHeight - rect.height) / 2 - 20;

            // Adjust if modal goes off screen vertically
            if (top < 10) {
                top = 10;
            } else if (top + modalHeight > viewportHeight - 10) {
                top = viewportHeight - modalHeight - 10;
            }

            tentativeLeft = clamp(tentativeLeft, 10, viewportWidth - modalWidth - 10);

            if (isRTL) {
                const rtlRight = clamp(viewportWidth - (tentativeLeft + modalWidth), 10, viewportWidth - modalWidth - 10);
                hoverModal.style.right = rtlRight + 'px';
                hoverModal.style.left = 'auto';
            } else {
                hoverModal.style.left = tentativeLeft + 'px';
                hoverModal.style.right = 'auto';
            }
            hoverModal.style.top = top + 'px';
            hoverModal.style.visibility = '';

            const onScroll = () => {
                // Follow the trigger card while it is visible; hide when the card scrolls out of view
                const newRect = element.getBoundingClientRect();
                const isVisible = (
                    newRect.bottom > 0 &&
                    newRect.top < window.innerHeight &&
                    newRect.right > 0 &&
                    newRect.left < window.innerWidth
                );

                if (!isVisible) {
                    if (hoverTimeout) {
                        clearTimeout(hoverTimeout);
                        hoverTimeout = null;
                    }
                    if (hoverModal) {
                        hoverModal.remove();
                        hoverModal = null;
                    }
                    return;
                }

                let newTop = newRect.top - 50;
                if (newTop < 10) newTop = 10;
                if (newTop + modalHeight > window.innerHeight - 10) {
                    newTop = window.innerHeight - modalHeight - 10;
                }
                let newLeft = clamp(newRect.left + (newRect.width / 2) - (modalWidth / 2), 10, window.innerWidth -
                    modalWidth - 10);
                if (hoverModal) {
                    hoverModal.style.top = newTop + 'px';
                    if (isRTL) {
                        const rtlRight = clamp(window.innerWidth - (newLeft + modalWidth), 10, window.innerWidth -
                            modalWidth - 10);
                        hoverModal.style.right = rtlRight + 'px';
                        hoverModal.style.left = 'auto';
                    } else {
                        hoverModal.style.left = newLeft + 'px';
                        hoverModal.style.right = 'auto';
                    }
                }
            };

            window.addEventListener('scroll', onScroll, {
                passive: true
            });


            try {
                if (window.initTrailerHover) {
                    window.initTrailerHover();
                }
            } catch (e) {
                console.warn('hover trailer init failed', e);
            }
            hoverModal.addEventListener('mouseenter', () => {
                clearTimeout(hoverTimeout);
            });

            hoverModal.addEventListener('mouseleave', () => {
                hoverTimeout = setTimeout(() => {
                    if (hoverModal) {
                        window.removeEventListener('scroll', onScroll);
                        hoverModal.remove();
                        hoverModal = null;
                    }
                }, 200);
            });
        }

        // Helper function to format duration
        function formatDuration(duration) {
            if (!duration) return '--';

            // Handle "HH:MM" format (e.g., "05:20")
            if (typeof duration === 'string' && duration.includes(':')) {
                const [hours, minutes] = duration.split(':').map(Number);
                const hoursFormatted = String(hours).padStart(2, '0');
                const minutesFormatted = String(minutes).padStart(2, '0');
                return `${hoursFormatted}h ${minutesFormatted}m`;
            }

            // Handle seconds (numeric)
            if (typeof duration === 'number' || !isNaN(duration)) {
                const seconds = parseInt(duration);
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);

                if (hours > 0) {
                    const hoursFormatted = String(hours).padStart(2, '0');
                    const minutesFormatted = String(minutes).padStart(2, '0');
                    return `${hoursFormatted}h ${minutesFormatted}m`;
                }
                const minutesFormatted = String(minutes).padStart(2, '0');
                return `${minutesFormatted}m`;
            }

            return duration;
        }

        // Single delegated handler for all .watch-list-btn (movies/tv-shows pages) – prevents duplicate API calls
        (function() {
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.watch-list-btn');
                if (!btn || btn.disabled || btn.hasAttribute('onclick')) return;
                var entertainmentId = btn.getAttribute('data-entertainment-id');
                if (!entertainmentId) return;
                e.preventDefault();
                btn.disabled = true;
                var inWatchlistStr = (btn.getAttribute('data-in-watchlist') || '').toString();
                var isInWatchlist = inWatchlistStr === 'true' || inWatchlistStr === '1';
                var entertainmentType = (btn.getAttribute('data-entertainment-type') || '').trim();
                var baseUrl = (document.querySelector('meta[name="baseUrl"]') || {}).getAttribute ? document
                    .querySelector('meta[name="baseUrl"]').getAttribute('content') : '';
                var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute ?
                    document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
                var action = isInWatchlist ? 'delete' : 'save';
                var body = isInWatchlist ? {
                    id: parseInt(entertainmentId, 10),
                    type: entertainmentType,
                    _token: csrfToken
                } : {
                    entertainment_id: parseInt(entertainmentId, 10),
                    type: entertainmentType,
                    _token: csrfToken
                };
                var url = action === 'save' ? baseUrl + '/api/save-watchlist' : baseUrl +
                    '/api/delete-watchlist?is_ajax=1';
                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(body),
                        credentials: 'same-origin'
                    })
                    .then(function(res) {
                        // If logged out / session expired, redirect directly (no alert popup)
                        if (res.status === 401 || res.status === 419) {
                            window.location.href = baseUrl + '/login';
                            return null;
                        }
                        if (!res.ok) {
                            return res.text().then(function(t) {
                                throw new Error(t || 'Request failed');
                            });
                        }
                        return res.json().catch(function() {
                            return {};
                        });
                    })
                    .then(function(data) {
                        if (data === null) return;
                        var nowIn = !isInWatchlist;
                        btn.setAttribute('data-in-watchlist', nowIn ? 'true' : 'false');
                        var icon = btn.querySelector('i');
                        if (icon) {
                            icon.classList.remove(nowIn ? 'ph-plus' : 'ph-check');
                            icon.classList.add(nowIn ? 'ph-check' : 'ph-plus');
                        }
                        btn.classList.remove(nowIn ? 'btn-dark' : 'btn-primary');
                        btn.classList.add(nowIn ? 'btn-primary' : 'btn-dark');
                        var newTitle = nowIn ? 'Remove from Watchlist' : 'Add to Watchlist';
                        btn.setAttribute('data-bs-title', newTitle);
                        try {
                            if (window.successSnackbar && data.message) window.successSnackbar(data
                                .message);
                        } catch (_) {}
                        if (action === 'delete') {
                            try {
                                document.dispatchEvent(new CustomEvent('watchlist-deleted', {
                                    detail: entertainmentId
                                }));
                            } catch (_) {}
                        }
                    })
                    .catch(function(err) {
                        // For non-auth errors, keep it silent (or log) — user asked no popup
                        try {
                            console.error('watchlist request failed', err);
                        } catch (_) {}
                    })
                    .finally(function() {
                        btn.disabled = false;
                    });
            }, true);
        })();

        // Single delegated handler for all .like-btn – prevents duplicate API calls
        (function() {
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.like-btn');
                if (!btn || btn.disabled || btn.hasAttribute('onclick')) return;
                var entertainmentId = btn.getAttribute('data-entertainment-id');
                if (!entertainmentId) return;
                e.preventDefault();
                btn.disabled = true;
                var isLikedStr = (btn.getAttribute('data-is-liked') || '').toString();
                var isLiked = isLikedStr === 'true' || isLikedStr === '1';
                var entertainmentType = (btn.getAttribute('data-type') || '').trim();
                var baseUrl = (document.querySelector('meta[name="baseUrl"]') || {}).getAttribute ? document
                    .querySelector('meta[name="baseUrl"]').getAttribute('content') : '';
                var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute ?
                    document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
                var newIsLiked = isLiked ? 0 : 1;
                var url = baseUrl + '/api/save-likes';
                var body = {
                    entertainment_id: parseInt(entertainmentId, 10),
                    is_like: newIsLiked,
                    type: entertainmentType,
                    _token: csrfToken
                };
                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(body),
                        credentials: 'same-origin'
                    })
                    .then(function(res) {
                        if (res.status === 401 || res.status === 419) {
                            window.location.href = baseUrl + '/login';
                            return null;
                        }
                        if (!res.ok) {
                            return res.text().then(function(t) {
                                throw new Error(t || 'Request failed');
                            });
                        }
                        return res.json().catch(function() {
                            return {};
                        });
                    })
                    .then(function(data) {
                        if (data === null) return;
                        var nowLiked = newIsLiked === 1;
                        btn.setAttribute('data-is-liked', nowLiked ? 'true' : 'false');
                        var icon = btn.querySelector('i');
                        if (icon) {
                            icon.classList.remove(nowLiked ? 'ph' : 'ph-fill', 'ph-heart');
                            icon.classList.add(nowLiked ? 'ph-fill' : 'ph', 'ph-heart');
                        }
                        btn.classList.remove(nowLiked ? 'btn-dark' : 'btn-primary');
                        btn.classList.add(nowLiked ? 'btn-primary' : 'btn-dark');
                        var newTitle = nowLiked ? '{{ __('messages.lbl_unlike') }}' :
                            '{{ __('messages.lbl_like') }}';
                        btn.setAttribute('title', newTitle);
                        btn.setAttribute('data-bs-original-title', newTitle);
                        try {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                                var tt = bootstrap.Tooltip.getInstance(btn);
                                if (tt) {
                                    tt.dispose();
                                }
                                new bootstrap.Tooltip(btn);
                            }
                        } catch (_) {}
                        try {
                            if (window.successSnackbar && data.message) window.successSnackbar(data
                                .message);
                        } catch (_) {}
                    })
                    .catch(function(err) {
                        try {
                            console.error('like request failed', err);
                        } catch (_) {}
                    })
                    .finally(function() {
                        btn.disabled = false;
                    });
            }, true);
        })();

        // Watchlist toggle function (used by hover card on home)
        function toggleWatchlist(movieId) {
            const baseUrlMeta = document.querySelector('meta[name="baseUrl"]');
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (!baseUrlMeta) {
                console.warn('baseUrl meta not found');
                return;
            }
            const baseUrl = baseUrlMeta.getAttribute('content');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            const btn = document.getElementById('watchlist-btn-' + movieId);
            if (!btn) return;
            if (btn.disabled) return;
            btn.disabled = true;

            const isInWatchlist = String(btn.getAttribute('data-in-watchlist')) === 'true';
            const entertainmentType = (btn.getAttribute('data-entertainment-type') || '').trim();

            const saveUrl = baseUrl + '/api/save-watchlist';
            const deleteUrl = baseUrl + '/api/delete-watchlist?is_ajax=1';

            const requestInit = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify(
                    isInWatchlist ? {
                        id: [movieId],
                        type: entertainmentType || ''
                    } : {
                        entertainment_id: movieId,
                        type: entertainmentType
                    }
                )
            };

            fetch(isInWatchlist ? deleteUrl : saveUrl, requestInit)
                .then(async (res) => {
                    if (res.status === 401) {
                        window.location.href = baseUrl + '/login';
                        return Promise.reject(new Error('Unauthorized'));
                    }
                    if (!res.ok) {
                        const text = await res.text().catch(() => '');
                        throw new Error(text || 'Request failed');
                    }
                    return res.json().catch(() => ({}));
                })
                .then((data) => {
                    // Toggle UI state on the button
                    const nowIn = !isInWatchlist;
                    btn.setAttribute('data-in-watchlist', nowIn ? 'true' : 'false');
                    try {
                        const icon = btn.querySelector('i');
                        if (icon) {
                            icon.classList.remove(nowIn ? 'ph-plus' : 'ph-check');
                            icon.classList.add(nowIn ? 'ph-check' : 'ph-plus');
                        }
                        btn.classList.remove(nowIn ? 'btn-dark' : 'btn-primary');
                        btn.classList.add(nowIn ? 'btn-primary' : 'btn-dark');
                        btn.setAttribute('data-bs-title', nowIn ? 'Remove from Watchlist' : 'Add to Watchlist');
                    } catch {}

                    // Persist state on the originating card so future hovers reflect it
                    const card = document.querySelector('[data-movie-id="' + movieId + '"]');
                    if (card) {
                        try {
                            const raw = card.getAttribute('data-movie-data') || '{}';
                            const obj = JSON.parse(raw);
                            obj.is_watch_list = nowIn ? 1 : 0;
                            card.setAttribute('data-movie-data', JSON.stringify(obj));
                        } catch (e) {
                            console.warn('failed to persist movie data', e);
                        }
                    }

                    // Optional: global snackbar if available
                    try {
                        if (window.successSnackbar && data && data.message) {
                            window.successSnackbar(data.message);
                        }
                    } catch {}
                })
                .catch((err) => {
                    console.error('watchlist toggle failed', err);
                })
                .finally(() => {
                    btn.disabled = false;
                });
        }
    </script>

</body>

</html>
