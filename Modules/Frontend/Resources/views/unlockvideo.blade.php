@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.rent_videos') }}
@endsection

@section('content')
    <div class="list-page section-spacing">
        <div class="container-fluid">
            <div class="row gy-4">
                <div class="col-lg-3 col-md-4">
                    @include('frontend::components.account-settings-sidebar')
                </div>
                <div class="col-lg-9 col-md-8 d-flex flex-column">
                    <div class="d-flex justify-content-start mb-4">
                        <h4 class="m-0">{{ __('frontend.rent_videos') }}</h4>
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
                                aria-selected="false">{{ __('messages.movie') }}</button>
                        </li>
                        @endif
                        @if(isenablemodule('video') == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="unlock-video-tab" data-bs-toggle="pill"
                                data-bs-target="#unlock-video" type="button" role="tab" aria-controls="unlock-video"
                                aria-selected="false">{{ __('messages.video') }}</button>
                        </li>
                        @endif
                        @if(isenablemodule('tvshow') == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="unlock-episode-tab" data-bs-toggle="pill"
                                data-bs-target="#unlock-episode" type="button" role="tab"
                                aria-controls="unlock-episode" aria-selected="false">{{ __('frontend.episodes') }}</button>
                        </li>
                        @endif 
                    </ul>

                    <div class="tab-content flex-grow-1 d-flex flex-column" id="unlock-tabs-content">
                        <div class="tab-pane fade show active flex-grow-1" id="unlock-all" role="tabpanel"
                            aria-labelledby="unlock-all-tab">
                            <div class="d-flex flex-column h-100">
                                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                    id="unlock-all-container"></div>
                                <div id="no-content-all"
                                    class="text-center d-none flex-grow-1 d-flex flex-column align-items-center justify-content-center gap-3 pt-4">
                                    <i class="ph ph-shopping-cart h2"></i>
                                    <h4 class="mt-3">{{ __('messages.lbl_no_content_purchase_at') }}</h4>
                                    <div class="mt-3">
                                        <a href="{{ route('pay-per-view') }}">
                                            <button class="btn btn-primary">{{ __('messages.Explor_Content') }}</button>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade flex-grow-1" id="unlock-movie" role="tabpanel" aria-labelledby="unlock-movie-tab">
                            <div class="d-flex flex-column h-100">
                                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                    id="unlock-movie-container">
                                </div>
                                <div id="no-content-movie"
                                    class="text-center d-none flex-grow-1 d-flex flex-column align-items-center justify-content-center gap-3 pt-4">
                                    <i class="ph ph-shopping-cart h2"></i>
                                    <h4 class="mt-3">{{ __('messages.lbl_no_content_purchase_at') }}</h4>
                                    <div class="mt-3">
                                        <a href="{{ route('pay-per-view') }}">
                                            <button class="btn btn-primary">{{ __('messages.Explor_Content') }}</button>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade flex-grow-1" id="unlock-video" role="tabpanel" aria-labelledby="unlock-video-tab">
                            <div class="d-flex flex-column h-100">
                                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                    id="unlock-video-container"></div>
                                <div id="no-content-video"
                                    class="text-center d-none flex-grow-1 d-flex flex-column align-items-center justify-content-center gap-3 pt-4">
                                    <i class="ph ph-shopping-cart h2"></i>
                                    <h4 class="mt-3">{{ __('messages.lbl_no_content_purchase_at') }}</h4>
                                    <div class="mt-3">
                                        <a href="{{ route('pay-per-view') }}">
                                            <button class="btn btn-primary">{{ __('messages.Explor_Content') }}</button>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade flex-grow-1" id="unlock-episode" role="tabpanel" aria-labelledby="unlock-episode-tab">
                            <div class="d-flex flex-column h-100">
                                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5"
                                    id="unlock-episode-container"></div>
                                <div id="no-content-episode"
                                    class="text-center d-none flex-grow-1 d-flex flex-column align-items-center justify-content-center gap-3 pt-4">
                                    <i class="ph ph-shopping-cart h2"></i>
                                    <h4 class="mt-3">{{ __('messages.lbl_no_content_purchase_at') }}</h4>
                                    <div class="mt-3">
                                        <a href="{{ route('pay-per-view') }}">
                                            <button class="btn btn-primary">{{ __('messages.Explor_Content') }}</button>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shimmer loader -->
                    <div class="card-style-slider shimmer-container" style="display: none;">
                        <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 mt-3">
                            @for ($i = 0; $i < 10; $i++)
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

    <script>
        (function() {
            function destroyUnlockVideos() {
                if (window.__unlockVideosOnScroll) {
                    window.removeEventListener('scroll', window.__unlockVideosOnScroll);
                    window.__unlockVideosOnScroll = null;
                }
            }

            function initUnlockVideos() {
                destroyUnlockVideos();

                const c = {
                    all: document.getElementById('unlock-all-container'),
                    movie: document.getElementById('unlock-movie-container'),
                    video: document.getElementById('unlock-video-container'),
                    episode: document.getElementById('unlock-episode-container')
                };
                const base = document.querySelector('meta[name="baseUrl"]')?.getAttribute('content') || '';
                const loaded = new Set();
                const shimmer = document.querySelector('.shimmer-container');

                // Infinite scroll state per tab (like watchlist)
                const paging = {
                    all: { page: 1, perPage: 12, hasMore: true, loading: false, loadedOnce: false },
                    movie: { page: 1, perPage: 12, hasMore: true, loading: false, loadedOnce: false },
                    video: { page: 1, perPage: 12, hasMore: true, loading: false, loadedOnce: false },
                    episode: { page: 1, perPage: 12, hasMore: true, loading: false, loadedOnce: false },
                };

                function appendCards(container, html, tab) {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const items = doc.querySelectorAll('.entainment-slick-card, .iq-card');
                    if (items.length === 0) {
                        container.innerHTML += html;
                        if (container.children.length === 0) {
                            document.getElementById(`no-content-${tab}`)?.classList.remove('d-none');
                        }
                        return;
                    }
                    items.forEach(node => {
                        const col = document.createElement('div');
                        col.className = 'col';
                        col.innerHTML = node.outerHTML;
                        container.appendChild(col);
                    });
                }

                function fetchTab(tab, loadMore = false) {
                    const st = paging[tab];
                    if (!st) return;
                    if (!loadMore && loaded.has(tab)) return;
                    if (st.loading) return;

                    st.loading = true;
                    const map = {
                        all: null,
                        movie: `${base}/api/movies-pay-per-view`,
                        video: `${base}/api/videos-pay-per-view`,
                        episode: `${base}/api/episodes-pay-per-view`
                    };
                    document.getElementById(`no-content-${tab}`)?.classList.add('d-none');
                    if (shimmer && !loadMore) shimmer.style.display = 'block';

                    if (tab === 'all') {
                        const nextPage = loadMore ? (st.page + 1) : st.page;
                        Promise.all([
                            fetch(`${base}/api/movies-pay-per-view?page=${nextPage}&per_page=${st.perPage}`).then(r => r.json()).catch(() => ({
                                html: ''
                            })),
                            fetch(`${base}/api/videos-pay-per-view?page=${nextPage}&per_page=${st.perPage}`).then(r => r.json()).catch(() => ({
                                html: ''
                            })),
                            fetch(`${base}/api/episodes-pay-per-view?page=${nextPage}&per_page=${st.perPage}`).then(r => r.json()).catch(() => ({
                                html: ''
                            })),
                        ]).then(([m, v, e]) => {
                            if (!loadMore) c[tab].innerHTML = '';
                            let hasContent = false;
                            if (m?.html) {
                                appendCards(c[tab], m.html, tab);
                                hasContent = true;
                            }
                            if (v?.html) {
                                appendCards(c[tab], v.html, tab);
                                hasContent = true;
                            }
                            if (e?.html) {
                                appendCards(c[tab], e.html, tab);
                                hasContent = true;
                            }
                            if (!hasContent && !loadMore) document.getElementById(`no-content-${tab}`)?.classList.remove('d-none');
                            const anyHasMore = (m && m.hasMore) || (v && v.hasMore) || (e && e.hasMore);
                            st.hasMore = !!anyHasMore && hasContent;
                            if (st.hasMore) st.page = nextPage;
                            window.initTrailerHover && window.initTrailerHover();
                            if (shimmer) shimmer.style.display = 'none';
                        }).catch(() => {
                            document.getElementById(`no-content-${tab}`)?.classList.remove('d-none');
                            if (shimmer) shimmer.style.display = 'none';
                        }).finally(() => {
                            st.loading = false;
                            st.loadedOnce = true;
                        });
                    } else {
                        const url = map[tab];
                        if (!url) {
                            if (shimmer) shimmer.style.display = 'none';
                            st.loading = false;
                            st.loadedOnce = true;
                            return;
                        }
                        const nextPage = loadMore ? (st.page + 1) : st.page;
                        const pagedUrl = `${url}?page=${nextPage}&per_page=${st.perPage}`;
                        fetch(pagedUrl)
                            .then(r => r.json())
                            .then(data => {
                                if (!loadMore) c[tab].innerHTML = '';
                                if (data?.html) {
                                    appendCards(c[tab], data.html, tab);
                                    if (c[tab].children.length === 0 && !loadMore) {
                                        document.getElementById(`no-content-${tab}`)?.classList.remove('d-none');
                                    }
                                } else {
                                    if (!loadMore) document.getElementById(`no-content-${tab}`)?.classList.remove('d-none');
                                }
                                const hasMore = !!(data && (data.hasMore || (data.html && data.html.trim().length > 0)));
                                st.hasMore = hasMore;
                                if (st.hasMore) st.page = nextPage;
                                window.initTrailerHover && window.initTrailerHover();
                                if (shimmer) shimmer.style.display = 'none';
                            })
                            .catch(() => {
                                document.getElementById(`no-content-${tab}`)?.classList.remove('d-none');
                                if (shimmer) shimmer.style.display = 'none';
                            })
                            .finally(() => {
                                st.loading = false;
                                st.loadedOnce = true;
                            });
                    }
                    loaded.add(tab);
                }

                document.getElementById('unlock-all-tab')?.addEventListener('shown.bs.tab', () => fetchTab('all'));
                document.getElementById('unlock-movie-tab')?.addEventListener('shown.bs.tab', () => fetchTab('movie'));
                document.getElementById('unlock-video-tab')?.addEventListener('shown.bs.tab', () => fetchTab('video'));
                document.getElementById('unlock-episode-tab')?.addEventListener('shown.bs.tab', () => fetchTab('episode'));

                // initial
                fetchTab('all');

                function getActiveTab() {
                    if (document.getElementById('unlock-all')?.classList.contains('active')) return 'all';
                    if (document.getElementById('unlock-movie')?.classList.contains('active')) return 'movie';
                    if (document.getElementById('unlock-video')?.classList.contains('active')) return 'video';
                    if (document.getElementById('unlock-episode')?.classList.contains('active')) return 'episode';
                    return 'all';
                }

                function onScrollLoadMore() {
                    const tab = getActiveTab();
                    const st = paging[tab];
                    if (!st || !st.hasMore || st.loading) return;
                    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 400) {
                        fetchTab(tab, true);
                    }
                }

                window.__unlockVideosOnScroll = onScrollLoadMore;
                window.addEventListener('scroll', window.__unlockVideosOnScroll, { passive: true });

                window.__accountSettingsCleanup = destroyUnlockVideos;
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initUnlockVideos, { once: true });
            } else {
                initUnlockVideos();
            }
        })();
    </script>
@endsection
