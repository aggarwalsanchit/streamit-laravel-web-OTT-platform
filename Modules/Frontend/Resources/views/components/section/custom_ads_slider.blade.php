    <div id="custom-ads-slider-section-{{ $placement ?? 'default' }}" class="section-wraper section-hidden">
        <div class="custom-ad-container">
            <div class="custom-ad-box">
                <div class="custom-ad-slider">
                    @if (isset($ads_data) && count($ads_data) > 0)
                        @foreach ($ads_data as $ad)
                            <div class="custom-ad-wrapper">
                                <div class="custom-ad-content">
                                    @if ($ad['type'] === 'image')
                                        @if ($ad['redirect_url'])
                                            <a href="{{ $ad['redirect_url'] }}" class="ad-link" target="_blank"
                                                rel="noopener noreferrer">
                                                <img src="{{ $ad['media'] }}" alt="{{ $ad['name'] }}"
                                                    class="ad-image">
                                                <div class="ad-overlay"></div>
                                            </a>
                                        @else
                                            <img src="{{ $ad['media'] }}" alt="{{ $ad['name'] }}" class="ad-image">
                                            <div class="ad-overlay"></div>
                                        @endif
                                    @elseif ($ad['type'] === 'video')
                                        @php
                                            $isYouTube =
                                                str_contains($ad['media'], 'youtube.com') ||
                                                str_contains($ad['media'], 'youtu.be');
                                        @endphp
                                        @if ($isYouTube)
                                            @php
                                                $videoId = '';
                                                if (str_contains($ad['media'], 'youtu.be/')) {
                                                    $videoId = explode('/', explode('youtu.be/', $ad['media'])[1])[0];
                                                } elseif (str_contains($ad['media'], 'youtube.com')) {
                                                    $url = parse_url($ad['media']);
                                                    parse_str($url['query'] ?? '', $query);
                                                    $videoId = $query['v'] ?? '';
                                                }
                                            @endphp
                                            <div class="custom-ad-content video-content">
                                                <div class="video-container">
                                                    <iframe class="ad-video"
                                                        src="https://www.youtube.com/embed/{{ $videoId }}?rel=0&autoplay=1&mute=1&controls=0&showinfo=0&modestbranding=1&loop=1&playlist={{ $videoId }}"
                                                        frameborder="0"></iframe>
                                                </div>
                                                <div class="ad-overlay"></div>
                                                @if ($ad['redirect_url'])
                                                    <div class="ad-video-overlay"
                                                        onclick="window.open('{{ $ad['redirect_url'] }}', '_blank')">
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="custom-ad-content video-content">
                                                {{-- <div class="video-container"> --}}
                                                    <video class="ad-video" autoplay muted loop playsinline>
                                                        <source
                                                            src="{{ $ad['media'] }}"
                                                            type="video/mp4">                                                    </video>
                                                {{-- </div> --}}
                                                <div class="ad-overlay"></div>
                                                @if ($ad['redirect_url'])
                                                    <div class="ad-video-overlay"
                                                        onclick="window.open('{{ $ad['redirect_url'] }}', '_blank')">
                                                    </div>
                                                @endif
                                            </div   >
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <!-- Fallback content when no ads data is passed -->
                        <div class="custom-ad-wrapper">
                            <div class="custom-ad-content">
                                <img src="" alt="" class="ad-image">
                                <div class="ad-overlay"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>



<style>
    /* Custom Ads Slider Styles */
    .section-hidden {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease-out, transform 0.5s ease-out;
    }

    .section-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .custom-ad-container {
        width: 100%;
        max-width: 1720px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .custom-ad-box {
        position: relative;
        background: radial-gradient(ellipse at center, #0f0f23 0%, #000000 70%);
        overflow: hidden;
        border-radius: 12px;
        animation: twinkle 2s ease-in-out infinite alternate;
        padding: 0 50px;
        margin: 30px 0;
    }

    /* Layered stars: repeat in both directions */
    .custom-ad-box::before,
        .custom-ad-box::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 200%;
            height: 200%;
            background-image:
                radial-gradient(2px 2px at 20px 30px, rgba(var(--bs-primary), 1), transparent),
                radial-gradient(2px 2px at 40px 70px, rgba(var(--bs-primary), 1), transparent),
                radial-gradient(2px 2px at 90px 40px, rgba(var(--bs-primary), 1), transparent),
                radial-gradient(2px 2px at 130px 80px, rgba(var(--bs-primary), 1), transparent),
                radial-gradient(2px 2px at 160px 30px, rgba(var(--bs-primary), 1), transparent),
                radial-gradient(2px 2px at 200px 90px, rgba(var(--bs-primary), 1), transparent),
                radial-gradient(2px 2px at 300px 50px, rgba(var(--bs-primary), 1), transparent),
                radial-gradient(2px 2px at 400px 10px, rgba(var(--bs-primary), 1), transparent),
                radial-gradient(2px 2px at 500px 60px, rgba(var(--bs-primary), 1), transparent),
                radial-gradient(2px 2px at 600px 80px, rgba(var(--bs-primary), 1), transparent),
                radial-gradient(2px 2px at 700px 30px, rgba(var(--bs-primary), 1), transparent);
            background-repeat: repeat;
            background-size: 200px 200px;
            animation: starfield 20s linear infinite, starTwinkle 3s ease-in-out infinite alternate;
            z-index: 1;
        }

    .custom-ad-box::after {
        background-image:
            radial-gradient(2px 2px at 20px 30px, rgba(var(--bs-primary-rgb), 1), transparent),
            radial-gradient(2px 2px at 40px 70px, rgba(var(--bs-primary-rgb), .5), transparent),
            radial-gradient(2px 2px at 90px 40px, rgba(var(--bs-primary-rgb), .8), transparent),
            radial-gradient(2px 2px at 130px 80px, rgba(var(--bs-primary-rgb), .3), transparent),
            radial-gradient(2px 2px at 160px 30px, rgba(var(--bs-primary-rgb), .5), transparent),
            radial-gradient(2px 2px at 200px 90px, rgba(var(--bs-primary-rgb), .6), transparent),
            radial-gradient(2px 2px at 300px 50px, rgba(var(--bs-primary-rgb), .7), transparent),
            radial-gradient(2px 2px at 400px 10px, rgba(var(--bs-primary-rgb), .3), transparent),
            radial-gradient(2px 2px at 500px 60px, rgba(var(--bs-primary-rgb), .9), transparent),
            radial-gradient(2px 2px at 600px 80px, rgba(var(--bs-primary-rgb), .5), transparent),
            radial-gradient(2px 2px at 700px 30px, rgba(var(--bs-primary-rgb), .4), transparent);
        background-repeat: repeat;
        background-size: 200px 250px;
        animation: starfield 30s linear infinite reverse, starTwinkle 4s ease-in-out infinite alternate-reverse;
        z-index: 1;
    }

    .custom-ad-wrapper {
        position: relative;
        width: 100%;
        border-radius: 6px;
        overflow: hidden;
        background: rgba(0, 0, 0, 0.1);
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        margin: 0 10px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .custom-ad-wrapper:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
    }

    .custom-ad-content {
        position: relative;
        width: 100%;
        height: 350px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .custom-ad-content .ad-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        transition: transform 0.3s ease;
        background: rgba(0, 0, 0, 0.8);
    }

    .ad-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(180deg,
                rgba(0, 0, 0, 0.3) 0%,
                rgba(0, 0, 0, 0.4) 50%,
                rgba(0, 0, 0, 0.6) 100%);
        z-index: 1;
        pointer-events: none;
    }

    .ad-link {
        display: block;
        width: 100%;
        height: 100%;
        text-decoration: none;
        position: relative;
        cursor: pointer;
    }

    .ad-link:hover .ad-image {
        transform: scale(1.05);
    }

    /* Video Content Styles */
    .video-content {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .video-container {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    .ad-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .ad-video-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
        z-index: 10;
        cursor: pointer;
        background: transparent;
    }

    /* Slick Slider Styles */
    .custom-ad-slider {
        position: relative;
        width: 70%;
        height: auto;
        margin: auto;
    }

    .custom-ad-slider .slick-dots {
        display: flex;
        justify-content: center;
        align-content: center;
        gap: 5px;
        bottom: 10px;
    }

    .custom-ad-slider .slick-dots li button {
        width: 20px;
        height: 5px;
        border-radius: 2px;
        background: #673b3a;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .custom-ad-slider .slick-dots li.slick-active button {
        width: 30px;
        height: 5px;
        border-radius: 2px;
        background: var(--bs-primary);
    }

    .custom-ad-slider .slick-arrow {
        display: none !important;
    }

    /* Animations */
    @keyframes twinkle {
        0% {
            filter: brightness(1);
        }

        100% {
            filter: brightness(1.2);
        }
    }

    @keyframes starTwinkle {
        0% {
            opacity: 0.5;
        }

        50% {
            opacity: 1;
        }

        100% {
            opacity: 0.7;
        }
    }

    @keyframes starfield {
        0% {
            transform: translateY(100%);
        }

        100% {
            transform: translateY(-100%);
        }
    }

    /* Responsive Styles */
    @media (max-width: 1800px) {
        .custom-ad-container {
            max-width: 100%;
            padding: 0 40px;
        }
    }

    @media (max-width: 1200px) {
        .custom-ad-container {
            padding: 0 30px;
        }

        .custom-ad-content {
            height: 300px;
        }
    }

    @media (max-width: 991px) {
        .custom-ad-container {
            padding: 0 20px;
        }

        .custom-ad-content {
            height: 250px;
        }

        .custom-ad-box {
            padding: 0 20px;
        }

        .custom-ad-slider {
            width: 100%;
        }

        .custom-ad-slider .slick-slide {
            padding: 0 10px;
            margin: 0 3px;
        }

        .custom-ad-wrapper {
            margin: 0 5px;
        }
    }

    @media (max-width: 767px) {
        .custom-ad-container {
            padding: 0 15px;
        }

        .custom-ad-content {
            height: 200px;
        }
    }

    @media (max-width: 576px) {
        .custom-ad-content {
            height: 180px;
        }

        .custom-ad-slider .slick-slide {
            padding: 0 5px;
            margin: 0 2px;
        }

        .custom-ad-wrapper {
            margin: 0 3px;
        }
    }

    /* Ensure ad content stays visible */
    .custom-ad-box>* {
        position: relative;
        z-index: 10;
    }

    /* Reduce motion accessibility */
    @media (prefers-reduced-motion: reduce) {

        .custom-ad-box::before,
        .custom-ad-box::after,
        .custom-ad-box {
            animation: none !important;
            background: #0f0f23 !important;
        }
    }
</style>
