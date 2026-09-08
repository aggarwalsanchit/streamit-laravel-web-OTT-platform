@extends('frontend::layouts.master')

@section('title')
    {{ __('messages.pay_per_view') }}
@endsection
@section('content')
    <!-- Tab-wise layout -->
    <div class="list-page section-spacing-bottom px-0">
        <div class="container">
            <div class="d-flex justify-content-center mb-3">
                <h4 class="m-0 text-center">{{ __('messages.pay_per_view') }}</h4>
            </div>

            <ul class="nav nav-pills comingsoon-tabs" id="ppv-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="ppv-all-tab" data-bs-toggle="pill" data-bs-target="#ppv-all"
                        type="button" role="tab" aria-controls="ppv-all" aria-selected="true">{{ __('messages.all') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ppv-movie-tab" data-bs-toggle="pill" data-bs-target="#ppv-movie"
                        type="button" role="tab" aria-controls="ppv-movie" aria-selected="false">{{ __('messages.movie') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ppv-video-tab" data-bs-toggle="pill" data-bs-target="#ppv-video"
                        type="button" role="tab" aria-controls="ppv-video" aria-selected="false">{{ __('messages.video') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ppv-episode-tab" data-bs-toggle="pill" data-bs-target="#ppv-episode"
                        type="button" role="tab" aria-controls="ppv-episode" aria-selected="false">{{ __('messages.episode') }}</button>
                </li>
            </ul>

            <div class="tab-content" id="ppv-tabs-content">
                <div class="tab-pane fade show active" id="ppv-all" role="tabpanel" aria-labelledby="ppv-all-tab">
                    <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6"
                        id="ppv-all-container"></div>
                    <div class="card-style-slider shimmer-ppv-all" style="display:none;">
                        <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 mt-3">
                            @for ($i = 0; $i < 12; $i++)
                                <div class="shimmer-container col mb-3">@include('components.card_shimmer_movieList')</div>
                            @endfor
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="ppv-movie" role="tabpanel" aria-labelledby="ppv-movie-tab">
                    <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6"
                        id="ppv-movie-container"></div>
                    <div class="card-style-slider shimmer-ppv-movie" style="display:none;">
                        <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 mt-3">
                            @for ($i = 0; $i < 12; $i++)
                                <div class="shimmer-container col mb-3">@include('components.card_shimmer_movieList')</div>
                            @endfor
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="ppv-video" role="tabpanel" aria-labelledby="ppv-video-tab">
                    <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6"
                        id="ppv-video-container"></div>
                    <div class="card-style-slider shimmer-ppv-video" style="display:none;">
                        <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 mt-3">
                            @for ($i = 0; $i < 12; $i++)
                                <div class="shimmer-container col mb-3">@include('components.card_shimmer_movieList')</div>
                            @endfor
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="ppv-episode" role="tabpanel" aria-labelledby="ppv-episode-tab">
                    <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6"
                        id="ppv-episode-container"></div>
                    <div class="card-style-slider shimmer-ppv-episode" style="display:none;">
                        <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 mt-3">
                            @for ($i = 0; $i < 12; $i++)
                                <div class="shimmer-container col mb-3">@include('components.card_shimmer_movieList')</div>
                            @endfor
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const baseUrl = document.querySelector('meta[name="baseUrl"]').content;

            // Containers where content will be inserted
            const containers = {
                all: document.getElementById('ppv-all-container'),
                movie: document.getElementById('ppv-movie-container'),
                video: document.getElementById('ppv-video-container'),
                episode: document.getElementById('ppv-episode-container')
            };

            // Loading shimmer elements
            const shimmers = {
                all: document.querySelector('.shimmer-ppv-all'),
                movie: document.querySelector('.shimmer-ppv-movie'),
                video: document.querySelector('.shimmer-ppv-video'),
                episode: document.querySelector('.shimmer-ppv-episode')
            };

            // Track pagination and scroll state for each tab
            const tabsState = {
                all: {
                    page: 1,
                    loading: false,
                    hasMore: true
                },
                movie: {
                    page: 1,
                    loading: false,
                    hasMore: true
                },
                video: {
                    page: 1,
                    loading: false,
                    hasMore: true
                },
                episode: {
                    page: 1,
                    loading: false,
                    hasMore: true
                }
            };

            // Keep track of which tabs are already initialized
            const initializedTabs = new Set();

            // Function to build API URL
            function getApiUrl(tab, page) {
                if (tab === 'all') {
                    return `${baseUrl}/api/pay-per-view-list?is_ajax=1&per_page=12&page=${page}`;
                }
                const apiMap = {
                    movie: 'movies',
                    video: 'videos',
                    episode: 'episodes'
                };
                return `${baseUrl}/api/ppv/${apiMap[tab]}?per_page=12&page=${page}`;
            }

            // Load data for a specific tab
            async function loadData(tab) {
                const state = tabsState[tab];
                if (state.loading || !state.hasMore) return; // Avoid duplicate calls

                state.loading = true;
                shimmers[tab].style.display = 'block';

                try {
                    const url = getApiUrl(tab, state.page);
                    const response = await fetch(url);
                    const data = await response.json();

                    shimmers[tab].style.display = 'none';

                    // Clear container only on first load
                    if (state.page === 1) containers[tab].innerHTML = '';

                    // Insert new HTML if available
                    if (data.html) {
                        containers[tab].insertAdjacentHTML('beforeend', data.html);
                        if (window.initTrailerHover) window.initTrailerHover();
                    }

                    // Update pagination state
                    state.page++;
                    if (data.hasMore === false) state.hasMore = false;
                } catch (error) {
                    console.error('Failed to load PPV data:', error);
                    shimmers[tab].style.display = 'none';
                } finally {
                    state.loading = false;
                }
            }

            // Setup infinite scroll for a tab
            function setupInfiniteScroll(tab) {
                window.addEventListener('scroll', () => {
                    const state = tabsState[tab];
                    if (!state.hasMore || state.loading) return;

                    const nearBottom = window.innerHeight + window.scrollY >= document.body.offsetHeight -
                        400;
                    if (nearBottom) loadData(tab);
                });
            }

            // Initialize a tab (load data + bind scroll)
            function initTab(tab) {
                if (initializedTabs.has(tab)) return; // only once
                loadData(tab);
                setupInfiniteScroll(tab);
                initializedTabs.add(tab);
            }

            // Bind tab switch events
            document.getElementById('ppv-all-tab')?.addEventListener('shown.bs.tab', () => initTab('all'));
            document.getElementById('ppv-movie-tab')?.addEventListener('shown.bs.tab', () => initTab('movie'));
            document.getElementById('ppv-video-tab')?.addEventListener('shown.bs.tab', () => initTab('video'));
            document.getElementById('ppv-episode-tab')?.addEventListener('shown.bs.tab', () => initTab('episode'));

            // Load the "All" tab initially
            initTab('all');
        });
    </script>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Banner Slider
            $('.slick-banner').slick({
                dots: true,
                infinite: true,
                speed: 500,
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 5000
            });
        });
    </script>
@endpush
