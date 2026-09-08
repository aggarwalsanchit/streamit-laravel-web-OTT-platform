@php
    $footerData = getFooterData();

@endphp

<footer class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="row gy-4 justify-content-center">
                <div class="col-md-3">
                    <div class="footer-logo mb-4">
                        <!--Logo -->
                        @include('frontend::components.partials.logo')
                    </div>
                    <p class="font-size-14 m-0">
                        {{ $footerData['short_description'] }}
                    </p>
                    <div class="mt-5">
                        <p class="mb-2 font-size-14 d-flex align-items-center gap-2"><i
                                class="ph ph-envelope-simple fs-6"></i> <a
                                href="mailto:{{ $footerData['inquriy_email'] }}" class="link-body-emphasis">
                                {{ $footerData['inquriy_email'] }}</a></p>
                        <p class="m-0 font-size-14 d-flex align-items-center gap-2"><i class="ph ph-phone fs-6"></i> <a
                                href="tel: {{ $footerData['helpline_number'] }}" class="link-body-emphasis fw-medium">
                                {{ $footerData['helpline_number'] }}</a></p>
                    </div>
                    <ul class="social-media list-inline mt-5 mb-0 p-0 d-flex align-items-center gap-3">
                        @if (!empty($footerData['facebook_url']))
                            <li>
                                <a href="{{ $footerData['facebook_url'] }}">
                                    <i class="ph ph-facebook-logo"></i>
                                </a>
                            </li>
                        @endif
                        @if (!empty($footerData['instagram_url']))
                            <li>
                                <a href="{{ $footerData['instagram_url'] }}">
                                    <i class="ph ph-instagram-logo"></i>
                                </a>
                            </li>
                        @endif
                        @if (!empty($footerData['youtube_url']))
                            <li>
                                <a href="{{ $footerData['youtube_url'] }}">
                                    <i class="ph ph-youtube-logo"></i>
                                </a>
                            </li>
                        @endif
                        @if (!empty($footerData['x_url']))
                            <li>
                                <a href="{{ $footerData['x_url'] }}">
                                    <i class="ph ph-x-logo"></i>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
                <div class="col-md-1">
                    <div class="footer-border"></div>
                </div>
                <div class="col-md-8">
                    <div class="row gy-4">
                        @if (isenablemodule('tvshow') == 1)
                            <div class="col-sm-4">
                                <h4 class="footer-title font-size-18 mb-5">{{ __('frontend.premium_show') }}</h4>
                                <ul class="list-unstyled footer-menu">
                                    @foreach (isset($footerData['premiumShows']) && !empty($footerData['premiumShows']) ? $footerData['premiumShows'] : [] as $show)
                                        <li class="mb-3">
                                            <a href="{{ route('tvshow-details', $show->slug) }}">{{ $show->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if (isenablemodule('movie') == 1)
                            <div class="col-sm-4">
                                <h4 class="footer-title font-size-18 mb-5">{{ __('frontend.top_movie_to_watch') }}</h4>
                                <ul class="list-unstyled footer-menu">
                                    @foreach ($footerData['topMovies'] as $movie)
                                        <li class="mb-3">
                                            @if ($movie->type == 'movie')
                                                <a
                                                    href="{{ route('movie-details', $movie->slug) }}">{{ $movie->name }}</a>
                                            @else
                                                <a
                                                    href="{{ route('tvshow-details', $movie->slug) }}">{{ $movie->name }}</a>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="col-sm-4">
                            <h4 class="footer-title font-size-18 mb-5">{{ __('frontend.download_app') }}</h4>
                            <p class="mb-5">{{ __('frontend.download_app_reason') }}</p>

                            <ul class="app-icon list-inline m-0 p-0 d-flex align-items-center gap-3">

                                @if ($footerData['play_store_url'])
                                    <li>
                                        <a href="{{ $footerData['play_store_url'] }}" class="btn btn-link p-0">
                                            <img src="{{ asset('img/web-img/play_store.png') }}" alt="play store"
                                                class="img-fluid">
                                        </a>
                                    </li>
                                @endif
                                @if ($footerData['app_store_url'])
                                    <li>
                                        <a href="{{ $footerData['app_store_url'] }}" class="btn btn-link p-0"
                                            target="_blank">
                                            <img src="{{ asset('img/web-img/app_store.png') }}" alt="app store"
                                                class="img-fluid">
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9 mt-5">
                    <ul
                        class="list-unstyled mb-0 footer-menu menu-horizontal d-flex flex-wrap align-items-center justify-content-center gap-3 border bg-body p-3 rounded">
                        @foreach ($footerData['pages'] as $page)
                            <li>
                                <a href="{{ route('page.show', ['slug' => $page->slug]) }}">{{ $page->name }}</a>
                            </li>
                        @endforeach
                        <li>
                            <a href="{{ route('faq') }}">{{ __('frontend.faq') }}</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container-fluid">
            <div class="text-center">
            @php
                $copyrightText = setting('copyright_text');
                // If copyright_text is not set, use default
                if (empty($copyrightText)) {
                    $copyrightText = '© ' . now()->year . ' ' . setting('app_name') . '. All Rights Reserved.';
                }
            @endphp
            {!! $copyrightText !!}
            </div>
        </div>
    </div>
</footer>
<!-- sticky footer -->
@include('frontend::components.partials.footer-sticky-menu')
