<div class="detail-page-banner">
    <div class="video-player-wrapper">
        <!-- Video.js core -->
        <link rel="stylesheet" href="{{ asset('css/video-js.css') }}" />
        <script src="{{ asset('js/videojs/video.min.js') }}"></script>

        <!-- YouTube Support -->
        <script src="{{ asset('js/videojs/videojs-youtube.min.js') }}"></script>

        <!-- IMA SDK -->
        <script src="{{ asset('js/videojs/ima3.js') }}"></script>

        <!-- Video.js Ads & IMA plugins -->
        <script src="{{ asset('js/videojs/videojs-contrib-ads.min.js') }}"></script>
        <script src="{{ asset('js/videojs/videojs.ima.min.js') }}"></script>
        <link href="{{ asset('css/videojs.ima.css') }}" rel="stylesheet">

        <div class="video-player">
            <video id="videoPlayer" class="video-js vjs-default-skin vjs-ima" controls width="560" height="315" muted
                poster="{{ $thumbnail_image }}" data-setup='{"muted": true}' data-type="{{ $type }}"
                content-video-type="{{ $content_video_type }}"
                data-continue-watch="{{ isset($continue_watch) && $continue_watch ? 'true' : 'false' }}"
                data-movie-access="{{ $dataAccess ?? '' }}" data-plan-id="{{ $plan_id ?? '' }}"
                data-watch-time="{{ $watched_time ?? 0 }}"
                @if ($type != 'Local') data-encrypted="{{ $data }}" @endif
                @if (isset($content_type) && isset($content_id)) data-contentType="{{ $content_type }}"
                    data-contentId="{{ $content_id }}" @endif
                data-forward-seconds="{{ setting('forward_seconds', 30) }}"
                data-backward-seconds="{{ setting('backward_seconds', 30) }}" playsinline webkit-playsinline
                x-webkit-airplay="allow" preload="metadata">
                @if ($type == 'Local')
                    <source src="{{ $data }}" type="video/mp4" id="videoSource">
                @endif
            </video>

            <!-- Vimeo iframe for Vimeo videos -->
            <div id="vimeoContainer">
                <iframe id="vimeoIframe" frameborder="0" allow="autoplay; fullscreen; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>

            <!-- Custom Ad Modal -->
            <div id="customAdModal">
                <div id="customAdContent">
                    <!-- Ad content will be injected here -->
                    <button id="customAdCloseBtn">&times;</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Include Video.js script if not already -->
<script src="{{ mix('js/videoplayer.min.js') }}"></script>
<script>
    var isAuthenticated = {{ auth()->check() ? 'true' : 'false' }};
    var loginUrl = "{{ route('login') }}";
    var skipTrailerText = "{{ __('messages.skip_trailer') }}";
    var skipIntroText = "{{ __('messages.skip_intro') }}";
    var previousEpisodeText = "{{ __('messages.previous_episode') }}";
    var nextEpisodeText = "{{ __('messages.next_episode') }}";
    var backwardButtonText = "{{ __('messages.backward_button') }}";
    var forwardButtonText = "{{ __('messages.forward_button') }}";
    var defaultText = "{{ __('messages.default') }}";
    var errorLoadingAdText = "{{ __('messages.error_loading_ad') }}";
    var nextText = "{{ __('messages.next') }}";
</script>
<style>
    .video-player-wrapper {
        position: relative;
    }

    #vimeoContainer {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        display: none;
    }

    #vimeoIframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
    }

    #customAdModal {
        display: none;
        position: absolute;
        z-index: 9999;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        align-items: center;
        justify-content: center;
    }

    #customAdContent {
        position: relative;
        background: rgba(0, 0, 0, 0.0);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    #customAdCloseBtn {
        position: absolute;
        top: -20px;
        right: -20px;
        background: #f00;
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        font-size: 20px;
        cursor: pointer;
        z-index: 2;
    }
</style>
<style>
    /* Hide ALL IMA Skip Elements */
    .ima-skip-container,
    .ima-skip-button,
    div[class*="ima-ad-skip"],
    div[class*="ima_skip"],
    div[class*="ima-skip"],
    button[class*="ima-skip"],
    button[class*="ima_skip"],
    div[class*="skip-button"],
    .ima-skip-button-container,
    [class*="skip-container"],
    [data-skip-button],
    [data-skip-container] {
        opacity: 0 !important;
        visibility: hidden !important;
        display: none !important;
        pointer-events: none !important;
        width: 0 !important;
        height: 0 !important;
        position: absolute !important;
        top: -9999px !important;
        left: -9999px !important;
    }

    /* Keep IMA Progress and Countdown Visible */
    .ima-controls-div,
    .ima-progress-div,
    .ima-countdown-div,
    .ima-seek-bar-div,
    .ima-progress-bar-div {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: auto !important;
    }

    .vjs-texttrack-settings {
        display: none !important;
    }

    /* Subtitle Responsive Font Size */
    .video-js .vjs-text-track-cue,
    .video-js .vjs-text-track-cue div {
        font-size: 1.4em !important;
        line-height: normal !important;
    }

    /* iOS FIX: Prevent caption size explosion */
    @supports (-webkit-touch-callout: none) {

        /* iOS-specific styles */
        .video-js .vjs-text-track-display {
            font-size: 1.4em !important;
            line-height: normal !important;
            transform: none !important;
            -webkit-transform: none !important;
        }

        .video-js .vjs-text-track-cue,
        .video-js .vjs-text-track-cue div {
            font-size: 1.4em !important;
            line-height: normal !important;
            transform: none !important;
            -webkit-transform: none !important;
            max-font-size: 1.4em !important;
        }

        /* iOS FIX: Ensure play button is always clickable */
        .video-js .vjs-big-play-button {
            pointer-events: auto !important;
            opacity: 1 !important;
        }

        .video-js .vjs-big-play-button.vjs-hidden {
            display: block !important;
            opacity: 1 !important;
        }
    }

    @media (max-width: 768px) {

        .video-js .vjs-text-track-cue,
        .video-js .vjs-text-track-cue div {
            font-size: 16px !important;
        }
    }

    @media (max-width: 480px) {

        .video-js .vjs-text-track-cue,
        .video-js .vjs-text-track-cue div {
            font-size: 13px !important;
        }

        video::-webkit-media-text-track-display {
            font-size: 13px !important;
        }
    }

    .video-js.vjs-ima {
        overflow: visible;
    }

    .video-js.vjs-ima .vjs-ima-ad-container {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        pointer-events: none;
    }

    .video-js.vjs-ima .vjs-ima-ad-container>div {
        pointer-events: auto;
    }

    .vjs-ad-cue {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 4px;
        z-index: 10;
        pointer-events: none;
        transition: all 0.3s ease;
    }

    .vjs-ad-cue:hover {
        width: 6px;
        opacity: 0.8;
    }

    .vjs-ad-cue[title*="Mid-roll"] {
        background-color: orange;
    }

    .vjs-ad-cue[title*="Post-roll"] {
        background-color: orange;
    }

    .vjs-ad-cue[title*="Overlay"] {
        background-color: orange;
    }

    /* Enhanced Skip Button Styling */
    .vjs-skip-ad-button {
        position: absolute;
        bottom: 80px;
        right: 20px;
        background: rgba(15, 15, 15, 0.85);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #fff;
        padding: 12px 24px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 4px;
        cursor: pointer;
        z-index: 10001 !important;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .vjs-skip-ad-button:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: #fff;
        transform: scale(1.05);
    }

    .vjs-skip-ad-button::after {
        content: "";
        display: inline-block;
        width: 0;
        height: 0;
        border-top: 6px solid transparent;
        border-bottom: 6px solid transparent;
        border-left: 10px solid #fff;
    }

    @media (max-width: 768px) {
        .vjs-skip-ad-button {
            bottom: 70px;
            padding: 8px 16px;
            font-size: 14px;
        }
    }

    @media (max-width: 575px) {
        .vjs-skip-ad-button {
            bottom: 110px;
            padding: 6px 12px;
            font-size: 12px;
        }
    }

    .overlay-ad {
        position: absolute;
        bottom: 80px;
        left: 20px;
        z-index: 1000;
        background: rgba(15, 15, 15, 0.9);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        padding: 8px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
    }

    @media (max-width: 575px) {
        .overlay-ad {
            bottom: 110px;
            left: 10px;
            right: 10px;
            text-align: center;
        }
    }

    .video-player {
        position: relative;
        z-index: 0;
    }

    #customAdModal {
        display: none;
        position: absolute;
        z-index: 10000;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    #customAdModal.show {
        opacity: 1;
    }

    #customAdContent {
        max-width: 900px;
        width: 90%;
        max-height: 85vh;
        position: relative;
        background: #000;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    #customAdContent img,
    #customAdContent video,
    #customAdContent iframe {
        width: 100%;
        height: auto;
        max-height: 85vh;
        object-fit: contain;
        display: block;
    }

    #customAdCloseBtn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 0, 0, 0.85);
        color: #fff;
        border: 2px solid #fff;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        font-size: 22px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10001;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        padding: 0;
        padding-bottom: 4px; // Align the × character
    }

    #customAdCloseBtn:hover {
        background: #f00;
        transform: scale(1.1);
        box-shadow: 0 0 15px rgba(255, 0, 0, 0.5);
    }

    @media (max-width: 768px) {
        #customAdContent {
            width: 95%;
            border-radius: 12px;
        }

        #customAdCloseBtn {
            width: 32px;
            height: 32px;
            top: 10px;
            right: 10px;
            font-size: 18px;
        }
    }

    @media (max-width: 480px) {
        #customAdContent {
            width: 98%;
            max-height: 70vh; // Avoid obscuring too much on very small screens
        }
    }
</style>
