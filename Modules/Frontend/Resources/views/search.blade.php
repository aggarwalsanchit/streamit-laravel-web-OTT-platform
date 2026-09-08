@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.search') }}
@endsection

@section('content')
    <div class="list-page">
        <div class="container-fluid mt-4">
            <div class="row justify-content-center gy-5">
                <div class="col-md-8 col-lg-7">
                    <div class="search-title mb-5 text-center">
                        <h4>{{ __('frontend.discover_next_favorite') }}</h4>
                        <p>{{ __('frontend.search_through_content') }}</p>
                    </div>
                    <div class="form-group input-group search-not-found">
                        <input type="text" id="search-query" name="search" class="form-control border rounded" placeholder="{{ __('frontend.search_placeholder') }}"
                            id="">
                        <button type="submit" class="remove-search d-none" id="movie-remove">
                            <i class="ph ph-x"></i>
                        </button>
                        <button class="input-group-text btn btn-primary px-3" id="movie-search">
                            <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11.7669" cy="11.7666" r="8.98856" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                </circle>
                                <path d="M18.0186 18.4851L21.5426 22" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="col-md-8 col-lg-7">
                    <div id="active-filters" class="search-filter-data d-flex flex-wrap align-items-center gap-3">
                        <div class="label">{{ __('frontend.active_filters') }}</div>
                        <div class="filters-container d-flex flex-wrap align-items-center gap-2"></div>
                        <button type="button" class="btn btn-link search-clear-btn d-none">{{ __('frontend.clear_all') }}</button>
                    </div>
                </div>
                <div class="col-md-10 col-lg-9">
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 gy-4">
                        <div class="col">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="ph ph-funnel text-primary fs-5"></i>
                                <h5 class="m-0">{{ __('frontend.categories') }}</h5>
                            </div>
                            <ul id="categories-list" class="list-inline m-0 p-0 search-short-panel">
                                @if (isenablemodule('movie') == 1)
                                    <div class="search-short-panel-link" data-type="movie">{{ __('frontend.movies') }}</div>
                                @endif
                                @if (isenablemodule('tvshow') == 1)
                                    <div class="search-short-panel-link" data-type="tvshow">{{ __('frontend.tvshows') }}</div>
                                @endif

                                @if (isenablemodule('video') == 1)
                                    <div class="search-short-panel-link" data-type="video">{{ __('frontend.videos') }}</div>
                                @endif

                                @if (isenablemodule('tvshow') == 1)
                                    <div class="search-short-panel-link" data-type="season">{{ __('frontend.seasons') }}</div>
                                    <div class="search-short-panel-link" data-type="episode">{{ __('frontend.episodes') }}</div>
                                @endif

                                @if (isenablemodule('livetv') == 1)
                                    <div class="search-short-panel-link" data-type="livetv">{{ __('frontend.live_tv') }}</div>
                                @endif

                                <div class="search-short-panel-link" data-type="actor">{{ __('frontend.actors') }}</div>
                                <div class="search-short-panel-link" data-type="director">{{ __('frontend.directors') }}</div>

                            </ul>
                        </div>
                        <div class="col">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <i class="ph ph-faders text-primary fs-5"></i>
                                <h5 class="m-0">{{ __('frontend.genres') }}</h5>
                            </div>
                            <ul id="genres-list" class="list-inline m-0 p-0 search-short-panel">
                                @foreach ($gener as $gen)
                                    <div class="search-short-panel-link" data-genre-id="{{ $gen->id }}">
                                        {{ $gen->name }}</div>
                                @endforeach
                            </ul>
                        </div>
                        <div class="col">
                            @if($topSearches->count() > 0)
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <h5 class="m-0">{{ __('frontend.popular_search') }}</h5>
                                </div>
                            @endif
                            <div class="list-inline popular-search-panel">
                                @foreach ($topSearches as $topSearch)
                                    @if ($topSearch->type == 'movie' || $topSearch->type == 'tvshow')
                                        @if ($topSearch->entertainment && !empty($topSearch->entertainment->name) && !empty($topSearch->entertainment->slug))
                                            <div class="popular-search-panel-link d-flex align-items-center gap-2">
                                                <i class="ph ph-magnifying-glass"></i>
                                                @if ($topSearch->type == 'movie')
                                                    <a
                                                        href="{{ route('movie-details', ['id' => $topSearch->entertainment->slug]) }}">{{ $topSearch->entertainment->name }}</a>
                                                @elseif($topSearch->type == 'tvshow')
                                                    <a
                                                        href="{{ route('tvshow-details', ['id' => $topSearch->entertainment->slug]) }}">{{ $topSearch->entertainment->name }}</a>
                                                @endif
                                            </div>
                                        @endif
                                    @elseif($topSearch->type == 'video')
                                        @if ($topSearch->video && !empty($topSearch->video->name) && !empty($topSearch->video->slug))
                                            <div class="popular-search-panel-link d-flex align-items-center gap-2">
                                                <i class="ph ph-magnifying-glass"></i>
                                                <a
                                                    href="{{ route('video-details', ['id' => $topSearch->video->slug]) }}">{{ $topSearch->video->name }}</a>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="no-data-found" class="d-flex align-items-center justify-content-center gap-3 py-3">
        <div class="image">
            <img src="{{ asset('img/web-img/search-not-found.png') }}" class="img-fluid" alt="search-not-found">
        </div>
        <div class="content">
            <h5 class="mb-3">{{ __('frontend.search_not_found') }}</h5>
            <span>{{ __('frontend.try_something_new') }}</span>
        </div>
    </div>

    {{-- <div class="container-fluid">
        <div id="search_histroy" class="search-histroy mt-4"></div>
    </div> --}}

    <div class="movie-lists section-spacing-bottom" id="search_list">
        <div class="container-fluid" id="results">

        </div>
    </div>

    <div class="card-style-slider shimmer-container" id="search-shimmer" style="display: none;">
        <div class="container-fluid">
            <div class="row gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 mt-3">
                @for ($i = 0; $i < 12; $i++)
                    <div class="col mb-3">
                        @include('components.card_shimmer_movieList')
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <div class="search-not-found py-5 my-md-5" id="no_result"></div>

    </div>
@endsection
@push('after-scripts')
    <script>
        window.onload = function() {
            const isLoggedIn = {{ Auth::check() ? 'true' : 'false' }};
            const currentUrl = window.location.href;
            const searchNavLink = document.getElementById('search-drop');
            const searchDropdown = document.querySelector('.dropdown-menu');

            // Check if we are on the search page
            if (currentUrl.includes('/search')) {
                searchNavLink.classList.add('show');
                searchDropdown.classList.add('show');
            }

            const urlParams = new URLSearchParams(window.location.search);
            const query = urlParams.get('search') || urlParams.get('query');

            const navigationEntry = performance.getEntriesByType('navigation')[0];
            const isPageRefresh = navigationEntry && navigationEntry.type === 'reload';

            // Multi-select state for filters
            // Keep filters out of URL; use sessionStorage instead
            let selectedCategories = [];
            let selectedGenreIds = [];
            try {
                selectedCategories = JSON.parse(sessionStorage.getItem('searchSelectedTypes') || '[]');
                selectedGenreIds = JSON.parse(sessionStorage.getItem('searchSelectedGenreIds') || '[]');
            } catch (e) {
                selectedCategories = [];
                selectedGenreIds = [];
            }
            // Default select Movie and TV Show if nothing stored
            if (!Array.isArray(selectedCategories) || selectedCategories.length === 0) {
                selectedCategories = ['movie', 'tvshow'];
            }
            if (!Array.isArray(selectedGenreIds)) {
                selectedGenreIds = [];
            }

            function validateGenreIds() {
                const genresListEl = document.getElementById('genres-list');
                if (!genresListEl || selectedGenreIds.length === 0) {
                    return;
                }

                const availableGenreIds = [];
                genresListEl.querySelectorAll('.search-short-panel-link[data-genre-id]').forEach(el => {
                    const genreId = parseInt(el.getAttribute('data-genre-id'));
                    if (!isNaN(genreId)) {
                        availableGenreIds.push(genreId);
                    }
                });

                const validGenreIds = selectedGenreIds.filter(id => availableGenreIds.includes(id));

                if (validGenreIds.length !== selectedGenreIds.length) {
                    selectedGenreIds = validGenreIds;
                    persistFilters();
                }
            }

            function persistFilters() {
                sessionStorage.setItem('searchSelectedTypes', JSON.stringify(selectedCategories));
                sessionStorage.setItem('searchSelectedGenreIds', JSON.stringify(selectedGenreIds));
            }

            const searchInput = document.querySelector('input[name="search"]');

            if (isPageRefresh) {
                // Clear search input and URL parameter on refresh
                searchInput.value = '';
                if (query) {
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, '', newUrl);
                }
            } else {
                // Preserve search term when navigating from another page
                searchInput.value = query || '';
            }

            let debounceTimer;

            // Auto-search only if query exists and it's not a refresh
            if (query && !isPageRefresh) {
                search(query);
            }

            document.getElementById('movie-search').addEventListener('click', function() {
                const query = searchInput.value || '';
                persistFilters();
                const params = new URLSearchParams();
                if (query) params.set('search', query);
                const newUrl = `${window.location.pathname}?${params.toString()}`;
                window.history.pushState({}, '', newUrl);
                search(query);
            });


            searchInput.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    document.getElementById('movie-search').click();
                }
            });

            const removeSearchButton = document.querySelector('#movie-remove');

            searchInput.addEventListener('input', function() {
                toggleRemoveButton();
            });


            function toggleRemoveButton() {
                if (searchInput.value.trim() !== '') {
                    removeSearchButton.style.display = 'block';
                } else {
                    removeSearchButton.style.display = 'none';
                }
            }


            toggleRemoveButton();
            removeSearchButton.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));

                const newUrl = `${window.location.origin}${window.location.pathname}`;
                window.history.pushState({}, '', newUrl); // Update the URL without reloading the page
                const query = document.getElementById('search-query').value;
                search(query)

            });

            function search(query) {
                clearTimeout(debounceTimer); // Clear the previous timer

                debounceTimer = setTimeout(() => {
                    const hasFilters = (selectedCategories && selectedCategories.length) || (selectedGenreIds &&
                        selectedGenreIds.length);
                    const shimmerContainer = document.getElementById('search-shimmer');
                    if (query.length === 0 && !hasFilters) {
                        // Clear search results and history
                        $('#search_histroy').empty();
                        $('#results').empty();
                        $('#no_result').empty();
                        $('#no-data-found').removeClass('d-none'); // show default state when nothing to search
                        $('#more-like-this').removeClass('d-none'); // Show popular movies section
                        $('.remove-search').addClass('d-none');
                        if (shimmerContainer) {
                            shimmerContainer.style.display = 'none';
                        }
                    } else {
                        $('.remove-search').removeClass('d-none');
                        performSearch(query);
                        if (isLoggedIn) {
                            getSearchKey(query);
                        }
                        // Show loading state while searching
                    }
                }, 300);
            }

            window.performSearch = function(query) {
                const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
                const searchApiUrl = `${baseUrl}/api/v3/get-search-data`;
                const params = new URLSearchParams();
                params.set('search', query);
                params.set('is_ajax', '1');
                if (selectedCategories.length) params.set('type', selectedCategories.join(','));
                if (selectedGenreIds.length) params.set('genre_id', selectedGenreIds.join(','));
                const searchUrl = `${searchApiUrl}?${params.toString()}`;

                // Show shimmer and hide results/no-data
                const shimmerContainer = document.getElementById('search-shimmer');
                if (shimmerContainer) {
                    shimmerContainer.style.display = 'block';
                }
                $('#no-data-found').addClass('d-none');
                $('#results').empty();

                $.ajax({
                    url: searchUrl,
                    method: 'GET',
                    success: function(response) {
                        // Hide shimmer
                        if (shimmerContainer) {
                            shimmerContainer.style.display = 'none';
                        }

                        if (response.status) {
                            // Persist search history when results load
                            if (isLoggedIn && query && query.trim().length) {
                                saveSearchHistory(query.trim());
                                getSearchKey(query.trim());
                            }
                            if (response.html === '') {
                                $('#results').empty().append('');
                                $('.movie-lists').removeClass('d-none');
                                $('#more-like-this').addClass('d-none');
                                $('#no_result').empty();
                                $('#no-data-found').removeClass('d-none');
                            } else {
                                $('#no_result').empty().append('');
                                $('#no-data-found').addClass('d-none');
                                $('#results').empty().append('')
                                $('#more-like-this').addClass('d-none');
                                $('#results').empty().append(response.html);
                                if (window.initTrailerHover) {
                                    window.initTrailerHover();
                                }
                                const resultCount = $(response.html).find('.slick-item').length;

                                if (resultCount > 1) {
                                    // Initialize or reinitialize slick if there are multiple results
                                    slickInstance.slick({
                                        infinite: true,
                                        slidesToShow: 3,
                                        slidesToScroll: 3
                                    });
                                    updateFirstLastClasses(slickInstance);
                                }
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr);
                        // Hide shimmer on error
                        if (shimmerContainer) {
                            shimmerContainer.style.display = 'none';
                        }
                    }
                });
            }

            // Filters UI wiring
            const categoriesList = document.getElementById('categories-list');
            const genresList = document.getElementById('genres-list');
            const activeFiltersRoot = document.getElementById('active-filters');
            const activeFiltersContainer = activeFiltersRoot.querySelector('.filters-container');
            const clearAllBtn = activeFiltersRoot.querySelector('.search-clear-btn');

            function applySelectedClasses() {
                if (categoriesList) {
                    categoriesList.querySelectorAll('.search-short-panel-link').forEach(el => {
                        const type = el.getAttribute('data-type');
                        if (type && selectedCategories.includes(type)) {
                            el.classList.add('active');
                        } else {
                            el.classList.remove('active');
                        }
                    });
                }
                if (genresList) {
                    genresList.querySelectorAll('.search-short-panel-link').forEach(el => {
                        const gid = el.getAttribute('data-genre-id');
                        const n = gid ? parseInt(gid) : null;
                        if (n && selectedGenreIds.includes(n)) {
                            el.classList.add('active');
                        } else {
                            el.classList.remove('active');
                        }
                    });
                }
            }

            function renderActiveFilters() {
                const chips = [];
                if (selectedCategories.length) {
                    const labelMap = {
                        movie: '{{ __('frontend.movies') }}',
                        tvshow: '{{ __('frontend.tvshows') }}',
                        video: '{{ __('frontend.videos') }}',
                        season: '{{ __('frontend.seasons') }}',
                        episode: '{{ __('frontend.episodes') }}',
                        livetv: '{{ __('frontend.live_tv') }}',
                        actor: '{{ __('frontend.actors') }}',
                        director: '{{ __('frontend.directors') }}'
                    };
                    selectedCategories.forEach(type => {
                        const label = labelMap[type] || type;
                        const capitalizedLabel = label.charAt(0).toUpperCase() + label.slice(1);
                        chips.push(
                            `<div class="search-filter-item d-flex align-items-center gap-2" data-remove="type" data-type="${type}">${label} <i class=\"ph ph-x\"></i></div>`
                        );
                    });
                }
                if (selectedGenreIds.length) {
                    selectedGenreIds.forEach(gid => {
                        const genreEl = genresList ? genresList.querySelector(`[data-genre-id="${gid}"]`) :
                            null;
                        const label = genreEl ? genreEl.textContent.trim() : `{{ __('frontend.genre') }} ${gid}`;
                        const capitalizedLabel = label.charAt(0).toUpperCase() + label.slice(1);
                        chips.push(
                            `<div class="search-filter-item d-flex align-items-center gap-2" data-remove="genre" data-genre-id="${gid}">${label} <i class=\"ph ph-x\"></i></div>`
                        );
                    });
                }
                activeFiltersContainer.innerHTML = chips.join('');
                if (chips.length) {
                    clearAllBtn.classList.remove('d-none');
                    activeFiltersRoot.querySelector('.label').classList.remove('d-none');
                } else {
                    clearAllBtn.classList.add('d-none');
                    activeFiltersRoot.querySelector('.label').classList.add('d-none');
                }
            }

            if (categoriesList) {
                categoriesList.addEventListener('click', function(e) {
                    const item = e.target.closest('.search-short-panel-link');
                    if (!item) return;
                    const type = item.getAttribute('data-type');
                    if (!type) return;
                    if (selectedCategories.includes(type)) {
                        selectedCategories = selectedCategories.filter(t => t !== type);
                    } else {
                        selectedCategories = [...selectedCategories, type];
                    }
                    applySelectedClasses();
                    renderActiveFilters();
                    const q = searchInput.value || '';
                    persistFilters();
                    const params = new URLSearchParams();
                    if (q) params.set('search', q);
                    const newUrl = `${window.location.pathname}?${params.toString()}`;
                    window.history.pushState({}, '', newUrl);
                    search(q);
                });
            }

            if (genresList) {
                genresList.addEventListener('click', function(e) {
                    const item = e.target.closest('.search-short-panel-link');
                    if (!item) return;
                    const idStr = item.getAttribute('data-genre-id');
                    const gid = idStr ? parseInt(idStr) : null;
                    if (!gid) return;
                    if (selectedGenreIds.includes(gid)) {
                        selectedGenreIds = selectedGenreIds.filter(id => id !== gid);
                    } else {
                        selectedGenreIds = [...selectedGenreIds, gid];
                    }
                    applySelectedClasses();
                    renderActiveFilters();
                    const q = searchInput.value || '';
                    persistFilters();
                    const params = new URLSearchParams();
                    if (q) params.set('search', q);
                    const newUrl = `${window.location.pathname}?${params.toString()}`;
                    window.history.pushState({}, '', newUrl);
                    if (typeof isLoggedIn !== 'undefined' && isLoggedIn) {
                        saveSearchHistory(String(gid), 'genre', gid);
                    }
                    search(q);
                });
            }

            activeFiltersContainer.addEventListener('click', function(e) {
                const chip = e.target.closest('.search-filter-item');
                if (!chip) return;
                const removeType = chip.getAttribute('data-remove');
                if (removeType === 'type') {
                    const type = chip.getAttribute('data-type');
                    selectedCategories = selectedCategories.filter(t => t !== type);
                } else if (removeType === 'genre') {
                    const gid = parseInt(chip.getAttribute('data-genre-id'));
                    if (!isNaN(gid)) {
                        selectedGenreIds = selectedGenreIds.filter(id => id !== gid);
                    }
                }
                applySelectedClasses();
                renderActiveFilters();
                const q = searchInput.value || '';
                persistFilters();
                const params = new URLSearchParams();
                if (q) params.set('search', q);
                const newUrl = `${window.location.pathname}?${params.toString()}`;
                window.history.pushState({}, '', newUrl);

                // Check if there are no active filters and no search query
                const shimmerContainer = document.getElementById('search-shimmer');
                if (!q.trim() && selectedCategories.length == 0 && selectedGenreIds.length == 0) {
                    $('#results').empty();
                    $('#no_result').empty();
                    $('#no-data-found').removeClass('d-none');
                    $('#more-like-this').addClass('d-none');
                    if (shimmerContainer) {
                        shimmerContainer.style.display = 'none';
                    }
                } else {
                    search(q);
                }
            });

            clearAllBtn.addEventListener('click', function() {
                selectedCategories = [];
                selectedGenreIds = [];
                applySelectedClasses();
                renderActiveFilters();
                const q = searchInput.value || '';
                persistFilters();
                const params = new URLSearchParams();
                if (q) params.set('search', q);
                const newUrl = `${window.location.pathname}?${params.toString()}`;
                window.history.pushState({}, '', newUrl);

                // Clear search results when no query and no filters
                const shimmerContainer = document.getElementById('search-shimmer');
                if (!q.trim()) {
                    $('#results').empty();
                    $('#no_result').empty();
                    $('#no-data-found').removeClass('d-none');
                    $('#more-like-this').addClass('d-none');
                    if (shimmerContainer) {
                        shimmerContainer.style.display = 'none';
                    }
                } else {
                    // Call performSearch function when there's a query
                    performSearch(q);
                }
            });

            validateGenreIds();
            // Initial paint
            applySelectedClasses();
            renderActiveFilters();
            (function initialTrigger() {
                const q = searchInput.value || '';
                const hasFilters = (selectedCategories && selectedCategories.length) || (selectedGenreIds &&
                    selectedGenreIds.length);
                if (q.length || hasFilters) {
                    search(q);
                }
            })();

            function getSearchKey(query) {
                const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
                const searchlistApiUrl = `${baseUrl}/api/v3/search-list`;
                const searchlistUrl = `${searchlistApiUrl}?search=${encodeURIComponent(query)}&is_ajax=1&per_page=20`;

                $.ajax({
                    url: searchlistUrl,
                    method: 'GET',
                    success: function(response) {
                        if (response.status) {
                            $("#search_histroy").empty();
                            response.data.forEach(item => {
                                const searchHtml = `
                            <div id="search-history-${item.id}" class="history-item">
                                <span onclick="performSearch('${item.search_query}')">
                                    ${item.search_query}
                                </span>
                                <button onclick="removeSearchHistory(${item.id})">Remove</button>
                            </div>
                        `;
                                $("#search_histroy").append(searchHtml);
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr);
                    }
                });
            }

            function saveSearchHistory(query, type = null, searchId = null) {
                const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const url = `${baseUrl}/api/save-search`;
                // We only save the raw query here; backend can fill profile_id from session
                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: {
                        search_query: query,
                        type: type,
                        search_id: searchId
                    },
                });
            }

            window.removeSearchHistory = function(id) {
                const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
                const searchApiUrl = `${baseUrl}/api/delete-search?id=${id}`;

                $.ajax({
                    url: searchApiUrl,
                    method: 'GET',
                    success: function(response) {
                        if (response.status) {
                            $("#search-history-" + id).addClass('d-none');
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr);
                    }
                });
            }
        };



        function updateFirstLastClasses(slider) {
            let active = slider.find(".slick-active");
            slider.find(".slick-item").removeClass("first last");
            if (active.length > 0) {
                active.first().addClass("first");
                active.last().addClass("last");
            }
        }

        // document.addEventListener('DOMContentLoaded', () => {
        //     loadData(`.movie-shimmer`, 'tranding_movie', `.slick-movie`);


        //     const slickInstance = $('.slick-general');
        //     updateFirstLastClasses(slickInstance);
        //     slickInstance.on('afterChange', function(event, slick, currentSlide) {
        //         updateFirstLastClasses(slickInstance);
        //     });


        // });

        // function loadData(containerSelector, apiSection, slickInstance) {

        //     const container = document.querySelector(containerSelector);
        //     const baseUrl = "{{ env('APP_URL') }}";
        //     const apiUrl = `${baseUrl}/api/get-tranding-data`;
        //     const csrf_token = '{{ csrf_token() }}'
        //     if (!apiSection) {
        //         return;
        //     }

        //     fetch(`${apiUrl}?is_ajax=1&section=${apiSection}`)
        //         .then(response => response.json())
        //         .then(data => {
        //             if (data?.html) {
        //                 const parser = new DOMParser();
        //                 const doc = parser.parseFromString(data.html, 'text/html');
        //                 const slickItems = doc.querySelectorAll('.entainment-slick-card');
        //                 if (slickItems) {
        //                     slickItems.forEach(item => {
        //                         if (item.outerHTML.trim() !== '<div class="slick-item"></div>') {
        //                             // Create a new slick item wrapper
        //                             const newItem = document.createElement('div');
        //                             newItem.classList.add('slick-item');
        //                             newItem.innerHTML = item.outerHTML; // Add the outer HTML of the item
        //                             updateFirstLastClasses($(slickInstance));
        //                             // Add the new item to the Slick instance
        //                             $(slickInstance).slick('slickAdd', newItem.outerHTML);
        //                             $(slickInstance).slick('setPosition');
        //                             if (window.initTrailerHover) {
        //                                 window.initTrailerHover();
        //                             }
        //                         }
        //                     });
        //                 }



        //                 if (container) {
        //                     container.style.display = 'none';
        //                 }

        //             } else {
        //                 container.innerHTML = '';
        //                 console.error('Invalid data from the API');
        //             }
        //         })
        //         .catch(error => console.error('Fetch error:', error))
        //         .finally(() => {});
        // }
    </script>
@endpush
