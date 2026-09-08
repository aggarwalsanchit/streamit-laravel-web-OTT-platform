@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.coming_soon') }}
@endsection
@section('content')
    <div class="list-page section-spacing-bottom px-0">
        <div class="page-title text-center m-auto" id="page_title">
            <h3 class="m-0 text-center text-primary">{{ __('frontend.coming_soon') }}</h3>
            <p>{{ __('messages.coming_soon_description') }}</p>
        </div>
        <div id="comingsoon-card-list">
            <div class="container">
                <ul class="nav nav-pills comingsoon-tabs" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pills-alldata-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-alldata" type="button" role="tab" aria-controls="pills-alldata"
                            aria-selected="true">{{ __('messages.all') }}</button>
                    </li>
                    @if (isenablemodule('movie') == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-movie-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-movie" type="button" role="tab" aria-controls="pills-movie"
                                aria-selected="false">{{ __('messages.movie') }}</button>
                        </li>
                    @endif
                    @if (isenablemodule('tvshow') == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-tvshow-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-tvshow" type="button" role="tab" aria-controls="pills-tvshow"
                                aria-selected="false">{{ __('messages.tvshow') }}</button>
                        </li>
                    @endif
                    @if (isenablemodule('video') == 1)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-video-tab" data-bs-toggle="pill"
                                data-bs-target="#pills-video" type="button" role="tab" aria-controls="pills-video"
                                aria-selected="false">{{ __('messages.video') }}</button>
                        </li>
                    @endif
                </ul>
                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active mt-5" id="pills-alldata" role="tabpanel"
                        aria-labelledby="pills-alldata-tab">
                        <div class="row gy-5 row-cols-1 row-cols-sm-2 row-cols-md-3" id="coming-soon"></div>
                        <div class="shimmer-container mt-5" id="shimmer-alldata" style="display: block;">
                            <div class="row gy-5 row-cols-1 row-cols-sm-2 row-cols-md-3">
                                @for ($i = 0; $i < 6; $i++)
                                    @include('components.card_shimmer_commingSoon')
                                @endfor
                            </div>
                        </div>
                    </div>
                    @if (isenablemodule('movie') == 1)
                        <div class="tab-pane fade mt-5" id="pills-movie" role="tabpanel" aria-labelledby="pills-movie-tab">
                            <div class="row gy-5 row-cols-1 row-cols-sm-2 row-cols-md-3" id="coming-soon-movie"></div>
                            <div class="shimmer-container mt-5" id="shimmer-movie" style="display: none;">
                                <div class="row gy-5 row-cols-1 row-cols-sm-2 row-cols-md-3">
                                    @for ($i = 0; $i < 6; $i++)
                                        @include('components.card_shimmer_commingSoon')
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @endif
                    @if (isenablemodule('tvshow') == 1)
                        <div class="tab-pane fade mt-5" id="pills-tvshow" role="tabpanel"
                            aria-labelledby="pills-tvshow-tab">
                            <div class="row gy-5 row-cols-1 row-cols-sm-2 row-cols-md-3" id="coming-soon-tvshow"></div>
                            <div class="shimmer-container mt-5" id="shimmer-tvshow" style="display: none;">
                                <div class="row gy-5 row-cols-1 row-cols-sm-2 row-cols-md-3">
                                    @for ($i = 0; $i < 6; $i++)
                                        @include('components.card_shimmer_commingSoon')
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @endif
                    @if (isenablemodule('video') == 1)
                        <div class="tab-pane fade mt-5" id="pills-video" role="tabpanel" aria-labelledby="pills-video-tab">
                            <div class="row gy-5 row-cols-1 row-cols-sm-2 row-cols-md-3" id="coming-soon-video"></div>
                            <div class="shimmer-container mt-5" id="shimmer-video" style="display: none;">
                                <div class="row gy-5 row-cols-1 row-cols-sm-2 row-cols-md-3">
                                    @for ($i = 0; $i < 6; $i++)
                                        @include('components.card_shimmer_commingSoon')
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
    <script>
        (() => {
            const noDataImageSrc = '{{ asset('img/NoData.png') }}';
            const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
            const apiUrl = `${baseUrl}/api/coming-soon`;
            const perPage = 6;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const noComingSoonContentText = "{{ __('messages.no_coming_soon_content') }}";
            const errorLoadingContentText = "{{ __('messages.error_loading_content') }}";

            const tabState = {
                alldata: {
                    container: document.getElementById('coming-soon'),
                    shimmer: document.getElementById('shimmer-alldata'),
                    apiType: 'all',
                    currentPage: 1,
                    isLoading: false,
                    hasMore: true,
                    initialized: false,
                },
                @if (isenablemodule('movie') == 1)
                    movie: {
                        container: document.getElementById('coming-soon-movie'),
                        shimmer: document.getElementById('shimmer-movie'),
                        apiType: 'movie',
                        currentPage: 1,
                        isLoading: false,
                        hasMore: true,
                        initialized: false,
                    },
                @endif
                @if (isenablemodule('tvshow') == 1)
                    tvshow: {
                        container: document.getElementById('coming-soon-tvshow'),
                        shimmer: document.getElementById('shimmer-tvshow'),
                        apiType: 'tvshow',
                        currentPage: 1,
                        isLoading: false,
                        hasMore: true,
                        initialized: false,
                    },
                @endif
                @if (isenablemodule('video') == 1)
                    video: {
                        container: document.getElementById('coming-soon-video'),
                        shimmer: document.getElementById('shimmer-video'),
                        apiType: 'video',
                        currentPage: 1,
                        isLoading: false,
                        hasMore: true,
                        initialized: false,
                    },
                @endif
            };

            let currentTabKey = 'alldata';

            const toggleShimmer = (state, visible) => {
                if (!state || !state.shimmer) {
                    return;
                }

                state.shimmer.style.display = visible ? 'block' : 'none';
            };

            const showMessage = (state, message, includeImage = true) => {
                if (!state || !state.container) return;
                state.container.innerHTML = `
                    <div class="col-12 text-center mx-auto">
                        ${includeImage ? `<img src="${noDataImageSrc}" alt="No Data" class="img-fluid" style="max-width: 300px;">` : ''}
                        <p class="mt-3 ${includeImage ? '' : 'text-danger'}">${message}</p>
                    </div>
                `;
            };

            const showNoDataMessage = (state) => {
                showMessage(state, noComingSoonContentText);
                state.hasMore = false;
            };

            const showErrorMessage = (state) => {
                showMessage(state, errorLoadingContentText, false);
                state.hasMore = false;
            };


            function bindWatchlistButtons(scope = document) {
                scope.querySelectorAll('.js-watchlist-btn').forEach(btn => {
                    if (btn.dataset.bound === '1') {
                        return;
                    }
                    btn.dataset.bound = '1';
                    btn.addEventListener('click', async (event) => {
                        event.preventDefault();
                        if (btn.disabled) return;
                        btn.disabled = true;

                        const entertainmentId = btn.dataset.entertainmentId;
                        const entertainmentType = btn.dataset.entertainmentType || '';
                        const isInWatchlist = Number(btn.dataset.inWatchlist) === 1;
                        const url = isInWatchlist ?
                            `${baseUrl}/api/delete-watchlist?is_ajax=1` :
                            `${baseUrl}/api/save-watchlist`;
                        const body = isInWatchlist ? {
                            id: [entertainmentId],
                            type: entertainmentType
                        } : {
                            entertainment_id: entertainmentId,
                            type: entertainmentType
                        };

                        try {
                            const response = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                body: JSON.stringify(body),
                            });

                            if (response.status === 401) {
                                window.location.href = `${baseUrl}/login`;
                                return;
                            }

                            const data = await response.json();
                            if (window.successSnackbar && data?.message) {
                                window.successSnackbar(data.message);
                            }

                            const nowIn = !isInWatchlist;
                            btn.dataset.inWatchlist = nowIn ? 1 : 0;
                            btn.classList.toggle('btn-primary', nowIn);
                            btn.classList.toggle('btn-dark', !nowIn);
                            const icon = btn.querySelector('i');
                            if (icon) {
                                icon.classList.toggle('ph-check', nowIn);
                                icon.classList.toggle('ph-plus', !nowIn);
                            }
                            const tooltipText = nowIn ?
                                '{{ __('messages.remove_watchlist') }}' :
                                '{{ __('messages.add_watchlist') }}';
                            if (btn.dataset.bsTitle) {
                                const tooltip = bootstrap?.Tooltip?.getInstance(btn);
                                tooltip?.dispose();
                                btn.setAttribute('data-bs-title', tooltipText);
                                bootstrap?.Tooltip?.getOrCreateInstance(btn);
                            }
                        } catch (error) {
                            console.error('Watchlist error:', error);
                        } finally {
                            btn.disabled = false;
                        }
                    });
                });
            }

            function bindRemindButtons(scope = document) {
                scope.querySelectorAll('.js-remind-btn').forEach(btn => {
                    if (btn.dataset.bound === '1') {
                        return;
                    }
                    btn.dataset.bound = '1';
                    btn.addEventListener('click', async () => {
                        if (btn.disabled) return;
                        btn.disabled = true;

                        const entertainmentId = btn.dataset.entertainmentId;
                        const isInRemind = btn.dataset.inRemindlist === '1' || btn.dataset
                            .inRemindlist === 'true';
                        const url = isInRemind ?
                            `${baseUrl}/api/delete-reminder?is_ajax=1` :
                            `${baseUrl}/api/save-reminder`;
                        const body = isInRemind ? {
                            is_remind: 0,
                            id: [entertainmentId]
                        } : {
                            is_remind: 1,
                            entertainment_id: entertainmentId
                        };

                        try {
                            const response = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                },
                                body: JSON.stringify(body),
                            });

                            if (response.status === 401) {
                                window.location.href = `${baseUrl}/login`;
                                return;
                            }

                            const data = await response.json();
                            if (window.successSnackbar && data?.message) {
                                window.successSnackbar(data.message);
                            }

                            const nowIn = !isInRemind;
                            btn.dataset.inRemindlist = nowIn ? 1 : 0;
                            btn.classList.toggle('btn-primary', nowIn);
                            btn.classList.toggle('btn-dark', !nowIn);
                            const icon = btn.querySelector('i');
                            if (icon) {
                                icon.classList.toggle('ph-fill', nowIn);
                                icon.classList.toggle('ph', !nowIn);
                            }
                            const tooltipText = nowIn ?
                                '{{ __('messages.remove_reminder') }}' :
                                '{{ __('messages.add_reminder') }}';
                            if (btn.dataset.bsTitle) {
                                const tooltip = bootstrap?.Tooltip?.getInstance(btn);
                                tooltip?.dispose();
                                btn.setAttribute('data-bs-title', tooltipText);
                                bootstrap?.Tooltip?.getOrCreateInstance(btn);
                            }
                        } catch (error) {
                            console.error('Reminder error:', error);
                        } finally {
                            btn.disabled = false;
                        }
                    });
                });
            }

            function enhanceInteractiveElements(container) {
                bindWatchlistButtons(container);
                bindRemindButtons(container);
            }

            const loadTabData = async (tabKey) => {
                const state = tabState[tabKey];
                if (!state || !state.container || state.isLoading || !state.hasMore) {
                    return;
                }

                const isInitialPage = state.currentPage === 1;
                toggleShimmer(state, true);

                state.isLoading = true;

                try {
                    const params = new URLSearchParams({
                        page: state.currentPage,
                        per_page: perPage,
                        is_ajax: 1,
                        type: state.apiType,
                    });

                    const response = await fetch(`${apiUrl}?${params}`);
                    const data = await response.json();
                    const isSuccessful = response.ok && data?.status !== false;

                    if (!isSuccessful) {
                        throw new Error(data?.message || 'Unable to load coming soon content');
                    }

                    const htmlContent = data.html ?? '';
                    const hasHtml = htmlContent.trim().length > 0;

                    if (isInitialPage) {
                        state.container.innerHTML = '';
                    }

                    if (hasHtml) {
                        if (isInitialPage) {
                            state.container.innerHTML = htmlContent;
                        } else {
                            state.container.insertAdjacentHTML('beforeend', htmlContent);
                        }
                        enhanceInteractiveElements(state.container);
                    }

                    state.hasMore = typeof data.hasMore === 'boolean' ? data.hasMore : hasHtml;

                    if (isInitialPage && !hasHtml) {
                        showNoDataMessage(state);
                    }

                    if (state.hasMore) {
                        state.currentPage += 1;
                    }
                } catch (error) {
                    console.error('Error loading coming soon content:', error);
                    if (isInitialPage) {
                        showErrorMessage(state);
                    }
                    state.hasMore = false;
                } finally {
                    state.isLoading = false;
                    toggleShimmer(state, false);
                }
            };

            const loadTabContent = (tabKey) => {
                const state = tabState[tabKey];
                if (!state || !state.container || state.initialized) {
                    return;
                }

                state.initialized = true;
                state.container.innerHTML = '';
                loadTabData(tabKey);
            };

            const handleScroll = () => {
                if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
                    loadTabData(currentTabKey);
                }
            };

            document.addEventListener('DOMContentLoaded', () => {
                Object.keys(tabState).forEach(key => loadTabContent(key));

                document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
                    tab.addEventListener('click', function() {
                        const targetTab = this.getAttribute('data-bs-target').replace('#pills-',
                            '');
                        if (!tabState[targetTab]) {
                            return;
                        }
                        currentTabKey = targetTab;
                        loadTabContent(targetTab);
                    });
                });

                window.addEventListener('scroll', handleScroll);
            });
        })();
    </script>
@endsection
