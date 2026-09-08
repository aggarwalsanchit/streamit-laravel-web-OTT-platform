@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.video') }}
@endsection

@section('content')
    @php
        $sliderCount = is_countable($sliders) ? count($sliders) : 0;
    @endphp

    @if (!is_null($sliders) && !empty($sliders))
        <!-- Banner Section -->
        <div class="banner-section" class="section-spacing-bottom px-0">
            @if (App\Models\MobileSetting::getCacheValueBySlug('banner') == 1)
                <div class="slick-banner main-banner" data-speed="1000" data-autoplay="true" data-center="false"
                    data-infinite="false"
                    data-navigation="{{ $sliderCount > 1 ? 'true' : 'false' }}"
                    data-pagination="{{ $sliderCount > 1 ? 'true' : 'false' }}"
                    data-spacing="0">
                    @foreach ($sliders as $video)
                        @php
                            $sliderImage = $video['file_url'] ?? null;
                            $videoData = !empty($video['data']) && is_object($video['data']) ? $video['data']->toArray(request()) : $video['data'];
                            $details = data_get($videoData, 'details', $videoData);
                            $poster = $sliderImage ?: data_get($videoData, 'poster_image');
                        @endphp
                        @if (!empty($details))
                            <div class="slick-item banner-slide"
                                style="background-image: linear-gradient(to right, rgba(0,0,0,0.8) 40%, transparent), url({{ setBaseUrlWithFileName($poster, 'image', 'banner') }});">
                                <div class="movie-content h-100">
                                    <div class="container-fluid h-100">
                                        <div class="row align-items-center h-100">
                                            <div class="col-xxl-4 col-lg-6">
                                                <div class="movie-info">
                                                    @if (!empty(data_get($details, 'genres')))
                                                        <div class="movie-tag mb-3">
                                                            <ul
                                                                class="list-inline m-0 p-0 d-flex align-items-center flex-wrap movie-tag-list">
                                                                @foreach (data_get($details, 'genres', []) as $genre)
                                                                    <li>
                                                                        <a href="#"
                                                                            class="tag">{{ data_get($genre, 'name') }}</a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif

                                                    <h4 class="movie-title mb-2">{{ data_get($details, 'name') }}</h4>
                                                    <div class="mb-0 font-size-14 line-count-3">{!! data_get($details, 'description') !!}</div>

                                                    <ul
                                                        class="movie-meta list-inline mt-4 mx-0 p-0 d-flex align-items-center flex-wrap gap-3">
                                                        @php
                                                            $releaseDate = data_get($details, 'release_date');
                                                            $releaseYear = $releaseDate ? \Carbon\Carbon::parse($releaseDate)->format('Y') : null;
                                                        @endphp
                                                        @if (!empty($releaseYear))
                                                            <li>
                                                                <span class="d-flex align-items-center gap-2">
                                                                    <i class="ph ph-calendar"></i>
                                                                    <span class="fw-medium">{{ $releaseYear }}</span>
                                                                </span>
                                                            </li>
                                                        @endif


                                                        @if (!empty(data_get($details, 'duration')))
                                                            <li>
                                                                <span class="d-flex align-items-center gap-2">
                                                                    <i class="ph ph-clock"></i>
                                                                    <span class="fw-medium">{{ str_replace(':', 'h ', data_get($details, 'duration')) . 'm' }}</span>
                                                                </span>
                                                            </li>
                                                        @endif

                                                        @if (!empty(data_get($details, 'imdb_rating')))
                                                            <li>
                                                                <span class="d-flex align-items-center gap-2">
                                                                    <i class="ph ph-star"></i>
                                                                    <span class="fw-medium">{{ data_get($details, 'imdb_rating') }} ({{ __('messages.lbl_IMDb') }})</span>
                                                                </span>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                    <div class="mt-5 mb-md-0 mb-3">
                                                        <div
                                                            class="movie-actions d-flex align-items-center flex-wrap column-gap-3 row-gap-2">
                                                            <x-watchlist-button :entertainment-id="$details['id'] ?? null" :in-watchlist="$details['is_in_watchlist'] ?? null"
                                                            :entertainmentType="$details['type']" customClass="watch-list-btn" />
                                                            <a href="{{ route('video-details', data_get($details, 'slug')) }}"
                                                                class="btn btn-primary" tabindex="-1">
                                                                <span
                                                                    class="d-flex align-items-center justify-content-center gap-2">
                                                                    <span><i class="ph-fill ph-play"></i></span>
                                                                    <span>{{ __('frontend.watch_now') }}</span>
                                                                </span>
                                                            </a>
                                                            <div class="position-relative share-button dropend dropdown">
                                                                <button type="button"
                                                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" title="{{__('messages.lbl_share')}}"
                                                                    class="action-btn btn btn-dark share-list-btn" data-bs-share="tooltip"
                                                                    aria-expanded="false">
                                                                    <i class="ph ph-share-network"></i>
                                                                </button>
                                                                <div class="share-wrapper">
                                                                    <div class="share-box dropdown-menu">
                                                                        <svg width="15" height="40" viewBox="0 0 15 40"
                                                                            class="share-shape" fill="none"
                                                                            xmlns="http://www.w3.org/2000/svg">
                                                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                                                d="M14.8842 40C6.82983 37.2868 1 29.3582 1 20C1 10.6418 6.82983 2.71323 14.8842 0H0V40H14.8842Z"
                                                                                fill="currentColor"></path>
                                                                        </svg>
                                                                        <div
                                                                            class="d-flex align-items-center justify-content-center gap-3 px-3">
                                                                            <a href="https://www.facebook.com/sharer?u={{ urlencode(Request::url()) }}"
                                                                                target="_blank" rel="noopener noreferrer"
                                                                                class="share-ico">
                                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                                    height="25" width="25"
                                                                                    viewBox="-204.79995 -341.33325 1774.9329 2047.9995">
                                                                                    <path
                                                                                        d="M1365.333 682.667C1365.333 305.64 1059.693 0 682.667 0 305.64 0 0 305.64 0 682.667c0 340.738 249.641 623.16 576 674.373V880H402.667V682.667H576v-150.4c0-171.094 101.917-265.6 257.853-265.6 74.69 0 152.814 13.333 152.814 13.333v168h-86.083c-84.804 0-111.25 52.623-111.25 106.61v128.057h189.333L948.4 880H789.333v477.04c326.359-51.213 576-333.635 576-674.373"
                                                                                        fill="#1877f2" />
                                                                                    <path
                                                                                        d="M948.4 880l30.267-197.333H789.333V554.609C789.333 500.623 815.78 448 900.584 448h86.083V280s-78.124-13.333-152.814-13.333c-155.936 0-257.853 94.506-257.853 265.6v150.4H402.667V880H576v477.04a687.805 687.805 0 00106.667 8.293c36.288 0 71.91-2.84 106.666-8.293V880H948.4"
                                                                                        fill="#fff" />
                                                                                </svg>
                                                                            </a>
                                                                            <a href="https://twitter.com/intent/tweet?text={{ urlencode($details['name']) }}&url={{ urlencode(Request::url()) }}"
                                                                                target="_blank" rel="noopener noreferrer"
                                                                                class="share-ico">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" x="0px"
                                                                                    y="0px" width="20" height="20"
                                                                                    viewBox="0 0 50 50">
                                                                                    <path
                                                                                        d="M 6.9199219 6 L 21.136719 26.726562 L 6.2285156 44 L 9.40625 44 L 22.544922 28.777344 L 32.986328 44 L 43 44 L 28.123047 22.3125 L 42.203125 6 L 39.027344 6 L 26.716797 20.261719 L 16.933594 6 L 6.9199219 6 z"
                                                                                        fill="#fff"></path>
                                                                                </svg>
                                                                            </a>
                                                                            <a href="#" data-link="{{ route('video-details', ['id' => $details['slug']]) }}"
                                                                                class="share-ico iq-copy-link" onclick="copyLink(this)"><i
                                                                                    class="ph ph-link"></i></a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xxl-4 col-lg-6 d-lg-block d-none"></div>
                                            <div class="col-xxl-4 d-xxl-block d-none"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach

                </div>
            @endif
        </div>
    @endif

    <!-- Rest of your existing content -->
    <div class="list-page">

        <div class="movie-lists section-spacing-bottom px-0">

            <div class="container-fluid">

                <h4 class="mb-1">{{ __('frontend.video') }}</h4>
                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6"
                    id="entertainment-list">
                </div>
                <div class="card-style-slider shimmer-container">
                    <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 mt-3">
                        @for ($i = 0; $i < 12; $i++)
                            <div class="shimmer-container col mb-3">
                                @include('components.card_shimmer_movieList')
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/entertainment.min.js') }}" defer></script>

    <script>
        const noDataImageSrc = '{{ asset('img/NoData.png') }}';
        const shimmerContainer = document.querySelector('.shimmer-container');
        const EntertainmentList = document.getElementById('entertainment-list');
        const pageTitle = document.getElementById('page_title');
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        let movie_id = null;
        let actor_id = null;
        let type = null;
        let per_page = 12;
        const csrf_token = '{{ csrf_token() }}'
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const apiUrl = `${baseUrl}/api/v3/video-list?is_ajax=1&per_page=${per_page}`;

        const showNoDataImage = () => {
            shimmerContainer.innerHTML = '';
            const noDataImage = document.createElement('img');
            noDataImage.src = noDataImageSrc;
            noDataImage.alt = 'No Data Found';
            noDataImage.style.display = 'block';
            noDataImage.style.margin = '0 auto';
            shimmerContainer.appendChild(noDataImage);
        };

        const loadData = async () => {
            if (!hasMore || isLoading) return;
            isLoading = true;
            shimmerContainer.style.display = '';
            try {
                const response = await fetch(`${apiUrl}&page=${currentPage}`);
                const data = await response.json();
                if (data?.html) {
                    EntertainmentList.insertAdjacentHTML(currentPage === 1 ? 'afterbegin' : 'beforeend', data.html);
                    if (window.initTrailerHover) window.initTrailerHover();
                    hasMore = !!data.hasMore;
                    if (hasMore) currentPage++;
                    shimmerContainer.style.display = 'none';
                } else {
                    showNoDataImage();
                }
            } catch (error) {
                console.error('Fetch error:', error);
                showNoDataImage();
            } finally {
                isLoading = false;
            }
        };

        const handleScroll = () => {
            if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500 && hasMore) {
                loadData();
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            loadData();
            window.addEventListener('scroll', handleScroll);
        });
        function copyLink(element) {
            const link = element.getAttribute('data-link');

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(() => {
                    showCopyMessage();
                }).catch(() => {
                    fallbackCopy(link);
                });
            } else {
                fallbackCopy(link);
            }
        }

        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showCopyMessage();
        }

        function showCopyMessage() {
            if (typeof window.successSnackbar === 'function') {
                window.successSnackbar('{{ __('messages.link_copied') }}');
            } else {
                console.error('window.successSnackbar is not defined');
            }
        }
    </script>
@endsection
