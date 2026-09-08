@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.my_watchlist') }}
@endsection

@section('content')
    <div class="section-spacing">
        <!-- <div class="page-title" id="page_title1">
                <h4 class="m-0 text-center">{{ __('frontend.my_watchlist') }}</h4>
            </div> -->

        <div class="container-fluid">
            <div class="row gy-4">
                <div class="col-lg-3 col-md-4">
                    @include('frontend::components.account-settings-sidebar')
                </div>
                <div class="col-lg-9 col-md-8 d-flex flex-column">
                    <div class="d-flex justify-content-start mb-4">
                        <h4 class="m-0">{{ __('frontend.my_watchlist') }}</h4>
                    </div>
                    <ul class="nav nav-pills justify-content-start comingsoon-tabs" id="unlock-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="unlock-all-tab" data-bs-toggle="pill"
                                data-bs-target="#unlock-all" type="button" role="tab" aria-controls="unlock-all"
                                aria-selected="true">{{ __('messages.all') }}</button>
                        </li>
                        @if(isenablemodule('movie') == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="unlock-movie-tab" data-bs-toggle="pill"
                                data-bs-target="#unlock-movie" type="button" role="tab" aria-controls="unlock-movie"
                                aria-selected="false" tabindex="-1">{{ __('messages.movie') }}</button>
                        </li>
                        @endif
                        @if(isenablemodule('tvshow') == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="unlock-video-tab" data-bs-toggle="pill"
                                data-bs-target="#unlock-video" type="button" role="tab" aria-controls="unlock-video"
                                aria-selected="false" tabindex="-1">{{ __('messages.tvshow') }}</button>
                        </li>
                        @endif
                        @if(isenablemodule('video') == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="unlock-episode-tab" data-bs-toggle="pill"
                                data-bs-target="#unlock-episode" type="button" role="tab"
                                aria-controls="unlock-episode" aria-selected="false" tabindex="-1">{{__('messages.video')}}</button>
                        </li>
                        @endif
                    </ul>
                    <div class="tab-content flex-grow-1 d-flex flex-column" id="unlock-tabs-content">
                        <div class="tab-pane fade show active flex-grow-1" id="unlock-all" role="tabpanel"
                            aria-labelledby="unlock-all-tab">
                            <div class="d-flex flex-column h-100">
                                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                    id="watch-list-all">

                                </div>
                                <div class="d-none flex-grow-1 d-flex align-items-center justify-content-center pt-4"
                                    id="empty-watch-list-all">
                                    <div class="row flex-column justify-content-center align-items-center">
                                        <div class="col-sm-12 text-center">
                                            <div class="my-5 py-2 add-watch-list-info text-center">
                                                <h4>{{ __('frontend.your_watchlist_empty') }}</h4>
                                                <p class="mb-0 watchlist-description">{{ __('frontend.add_watchlist_content') }}
                                                </p>
                                            </div>
                                            <div>
                                                <a href="{{ route('user.login') }}"> <button class="btn btn-primary">
                                                        {{ __('messages.Explor_Content') }} </button></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if(isenablemodule('movie') == 1)
                        <div class="tab-pane fade flex-grow-1" id="unlock-movie" role="tabpanel" aria-labelledby="unlock-movie-tab">
                            <div class="d-flex flex-column h-100">
                                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                    id="watch-list-movie">

                                </div>
                                <div class="d-none flex-grow-1 d-flex align-items-center justify-content-center pt-4"
                                    id="empty-watch-list-movie">
                                    <div class="row flex-column justify-content-center align-items-center">
                                        <div class="col-sm-12 text-center">
                                            <div class="my-5 py-2 add-watch-list-info text-center">
                                                <h4>{{ __('frontend.your_watchlist_empty') }}</h4>
                                                <p class="mb-0 watchlist-description">{{ __('frontend.add_watchlist_content') }}
                                                </p>
                                            </div>
                                            <div>
                                                <a href="{{ route('user.login') }}"> <button class="btn btn-primary">
                                                        {{ __('messages.Explor_Content') }} </button></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if(isenablemodule('tvshow') == 1)
                        <div class="tab-pane fade flex-grow-1" id="unlock-video" role="tabpanel" aria-labelledby="unlock-video-tab">
                            <div class="d-flex flex-column h-100">
                                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                    id="watch-list-tvshow">

                                </div>
                                <div class="d-none flex-grow-1 d-flex align-items-center justify-content-center pt-4"
                                    id="empty-watch-list-tvshow">
                                    <div class="row flex-column justify-content-center align-items-center">
                                        <div class="col-sm-12 text-center">
                                            <div class="my-5 py-2 add-watch-list-info text-center">
                                                <h4>{{ __('frontend.your_watchlist_empty') }}</h4>
                                                <p class="mb-0 watchlist-description">{{ __('frontend.add_watchlist_content') }}
                                                </p>
                                            </div>
                                            <div>
                                                <a href="{{ route('user.login') }}"> <button class="btn btn-primary">
                                                        {{ __('messages.Explor_Content') }} </button></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if(isenablemodule('video') == 1)
                        <div class="tab-pane fade flex-grow-1" id="unlock-episode" role="tabpanel" aria-labelledby="unlock-episode-tab">
                            <div class="d-flex flex-column h-100">
                                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                    id="watch-list-video">

                                </div>
                                <div class="d-none flex-grow-1 d-flex align-items-center justify-content-center pt-4"
                                    id="empty-watch-list-video">
                                    <div class="row flex-column justify-content-center align-items-center">
                                        <div class="col-sm-12 text-center">
                                            <div class="my-5 py-2 add-watch-list-info text-center">
                                                <h4>{{ __('frontend.your_watchlist_empty') }}</h4>
                                                <p class="mb-0 watchlist-description">{{ __('frontend.add_watchlist_content') }}
                                                </p>
                                            </div>
                                            <div>
                                                <a href="{{ route('user.login') }}"> <button class="btn btn-primary">
                                                        {{ __('messages.Explor_Content') }} </button></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="card-style-slider shimmer-container" style="display: none;">
                        <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 mt-3">
                            @for ($i = 0; $i < 12; $i++)
                                <div class="col mb-3">
                                    @include('components.card_shimmer_movieList')
                                </div>
                            @endfor
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/entertainment.min.js') }}" defer></script>

    <script>
        (function() {
            function destroyWatchlist() {
                if (window.__watchlistOnScroll) {
                    window.removeEventListener('scroll', window.__watchlistOnScroll);
                    window.__watchlistOnScroll = null;
                }
            }

            function initWatchlist() {
                // cleanup any prior instance (important for AJAX nav)
                destroyWatchlist();

                const baseUrl = document.querySelector('meta[name="baseUrl"]')?.getAttribute('content') || '';
                const apiUrl = `${baseUrl}/api/watch-list`;
                const csrf_token = '{{ csrf_token() }}';
                const shimmerContainer = document.querySelector('.shimmer-container');

                const paging = {
                    all: { page: 1, perPage: 10, hasMore: true, loading: false, loadedOnce: false },
                    @if(isenablemodule('movie') == 1)
                    movie: { page: 1, perPage: 10, hasMore: true, loading: false, loadedOnce: false },
                    @endif
                    @if(isenablemodule('tvshow') == 1)
                    tvshow: { page: 1, perPage: 10, hasMore: true, loading: false, loadedOnce: false },
                    @endif
                    @if(isenablemodule('video') == 1)
                    video: { page: 1, perPage: 10, hasMore: true, loading: false, loadedOnce: false },
                    @endif
                };

                function ensureLoaded(type) {
                    if (!paging[type].loadedOnce) {
                        paging[type].page = 1;
                        paging[type].hasMore = true;
                        loadData(type, false);
                    }
                }

                function getActiveType() {
                    if (document.getElementById('unlock-all')?.classList.contains('active')) return 'all';
                    @if(isenablemodule('movie') == 1)
                    if (document.getElementById('unlock-movie')?.classList.contains('active')) return 'movie';
                    @endif
                    @if(isenablemodule('tvshow') == 1)
                    if (document.getElementById('unlock-video')?.classList.contains('active')) return 'tvshow';
                    @endif
                    @if(isenablemodule('video') == 1)
                    if (document.getElementById('unlock-episode')?.classList.contains('active')) return 'video';
                    @endif
                    return 'all';
                }

                function onScrollLoadMore() {
                    const type = getActiveType();
                    const st = paging[type];
                    if (!st || !st.hasMore || st.loading) return;
                    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 400) {
                        loadData(type, true);
                    }
                }

                function loadData(type, loadMore) {
                    const st = paging[type];
                    if (!st || st.loading) return;
                    st.loading = true;

                    const container = document.getElementById(`watch-list-${type}`);
                    const emptyContainer = document.getElementById(`empty-watch-list-${type}`);

                    const page = loadMore ? (st.page + 1) : st.page;
                    const perPage = st.perPage;

                    if (!loadMore) {
                        if (shimmerContainer) shimmerContainer.style.display = 'block';
                        if (emptyContainer) emptyContainer.classList.add('d-none');
                    }

                    fetch(`${apiUrl}?type=${type}&is_ajax=1&per_page=${perPage}&page=${page}`, {
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': csrf_token,
                                'Content-Type': 'application/json',
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            const html = data && data.html ? data.html : '';
                            if (!loadMore) {
                                container.innerHTML = html;
                                st.loadedOnce = true;
                            } else if (html) {
                                container.insertAdjacentHTML('beforeend', html);
                            }

                            if (emptyContainer) {
                                emptyContainer.classList.toggle('d-none', !!container.innerHTML.trim());
                            }

                            st.hasMore = !!(data && data.hasMore);
                            if (st.hasMore && html) st.page = page;
                            if (shimmerContainer) shimmerContainer.style.display = 'none';
                        })
                        .catch(error => {
                            console.error('Error loading watchlist:', error);
                            if (!loadMore) {
                                container.innerHTML = '';
                                if (emptyContainer) emptyContainer.classList.remove('d-none');
                            }
                            if (shimmerContainer) shimmerContainer.style.display = 'none';
                        })
                        .finally(() => {
                            st.loading = false;
                        });
                }

                // Bind tab clicks (idempotent - safe to add multiple times, but we keep it minimal)
                document.getElementById('unlock-all-tab')?.addEventListener('click', () => ensureLoaded('all'));
                @if(isenablemodule('movie') == 1)
                document.getElementById('unlock-movie-tab')?.addEventListener('click', () => ensureLoaded('movie'));
                @endif
                @if(isenablemodule('tvshow') == 1)
                document.getElementById('unlock-video-tab')?.addEventListener('click', () => ensureLoaded('tvshow'));
                @endif
                @if(isenablemodule('video') == 1)
                document.getElementById('unlock-episode-tab')?.addEventListener('click', () => ensureLoaded('video'));
                @endif

                ensureLoaded('all');

                window.__watchlistOnScroll = onScrollLoadMore;
                window.addEventListener('scroll', window.__watchlistOnScroll, { passive: true });

                // allow master PJAX to cleanup this page before swapping
                window.__accountSettingsCleanup = destroyWatchlist;
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initWatchlist, { once: true });
            } else {
                initWatchlist();
            }
        })();
    </script>
@endsection
