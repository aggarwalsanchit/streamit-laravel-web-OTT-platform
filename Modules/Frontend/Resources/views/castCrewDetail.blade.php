@extends('frontend::layouts.master')

@section('title')
    {{ $data['name'] }}
@endsection
@section('content')
    <div class="section-spacing cardcruedetail-section">
        <div class="container">
            <div id="castcrewdetail-section">
                @include('frontend::components.card.card_castcrewdetail', [
                    'data' => $data,
                    'movieCount' => $movieCount,
                    'tvshowCount' => $tvshowCount,
                    'averageRating' => $averageRating,
                    'topGenres' => $topGenres,
                ])
            </div>
        </div>
    </div>


    @if ($more_items != null)
        <div class="section-spacing px-0">
            <div class="container-fluid">
                <div class="d-flex align-items-center justify-content-between my-3">
                    <h5 class="main-title text-capitalize mb-0">{{ __('frontend.cast_movies_tvshows') }} {{ $data['name'] }}
                    </h5>
                </div>

                <!-- Content Type Tabs -->
                <ul class="nav nav-pills castcrew-tabs mb-4" id="content-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" type="button"
                            data-type="both">{{ __('messages.all') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="movie-tab" type="button"
                            data-type="movie">{{ __('messages.movie') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tvshow-tab" type="button"
                            data-type="tvshow">{{ __('messages.tvshow') }}</button>
                    </li>
                </ul>

                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6"
                    id="entertainment-list">

                </div>
                <div class="card-style-slider shimmer-container">
                    <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 mt-3">
                        @for ($i = 0; $i < 5; $i++)
                            <div class="shimmer-container col mb-3">
                                @include('components.card_shimmer_movieList')
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    @endif
    <script src="{{ asset('js/entertainment.min.js') }}" defer></script>

    <script>
        const noDataImageSrc = '{{ asset('img/NoData.png') }}';
        const envURL = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const shimmerContainer = document.querySelector('.shimmer-container');
        const EntertainmentList = document.getElementById('entertainment-list');
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        const per_page = 12;
        const csrf_token = '{{ csrf_token() }}'
        const castId = "{{ $data['id'] }}";
        let currentType = 'both';

        const buildApiUrl = (page = 1) => {
            let apiUrl =
                `${envURL}/api/genre-content-list?page=${page}&is_ajax=1&per_page=${per_page}&type=${currentType}`;

            // Add actor_id parameter
            if (castId) {
                apiUrl += `&actor_id=${castId}`;
            }

            return apiUrl;
        };

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
            shimmerContainer.style.display = ''; // Show shimmer container
            try {
                const response = await fetch(buildApiUrl(currentPage));
                const data = await response.json();

                if (data?.html) {
                    EntertainmentList.insertAdjacentHTML(currentPage === 1 ? 'afterbegin' : 'beforeend', data.html);
                    if (window.initTrailerHover) window.initTrailerHover();
                    hasMore = !!data.hasMore;
                    if (hasMore) currentPage++;
                    shimmerContainer.style.display = 'none'; // Hide shimmer container
                    initializeWatchlistButtons();
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

        // Function to switch tabs and reload data
        const switchTab = (type) => {
            currentType = type;
            currentPage = 1;
            hasMore = true;
            EntertainmentList.innerHTML = ''; // Clear existing content
            loadData(); // Load new data
        };

        document.addEventListener('DOMContentLoaded', () => {
            loadData(); // Load the first page of movies
            window.addEventListener('scroll', handleScroll); // Attach scroll listener
            initializeWatchlistButtons();

            // Tab click handlers
            document.getElementById('all-tab')?.addEventListener('click', () => {
                switchTab('both');
                document.querySelectorAll('#content-tabs .nav-link').forEach(btn => btn.classList.remove(
                    'active'));
                document.getElementById('all-tab').classList.add('active');
            });

            document.getElementById('movie-tab')?.addEventListener('click', () => {
                switchTab('movie');
                document.querySelectorAll('#content-tabs .nav-link').forEach(btn => btn.classList.remove(
                    'active'));
                document.getElementById('movie-tab').classList.add('active');
            });

            document.getElementById('tvshow-tab')?.addEventListener('click', () => {
                switchTab('tvshow');
                document.querySelectorAll('#content-tabs .nav-link').forEach(btn => btn.classList.remove(
                    'active'));
                document.getElementById('tvshow-tab').classList.add('active');
            });
        });

        function initializeWatchlistButtons() {
            const watchList = typeof isWatchList !== 'undefined' ? !!emptyWatchList : null;
            const watchListPresent = typeof emptyWatchList !== 'undefined' ? !!emptyWatchList : null;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            $('.watch-list-btn').off('click').on('click', function() {

                var $this = $(this);
                var isInWatchlist = $this.data('in-watchlist');
                var entertainmentId = $this.data('entertainment-id');
                const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
                var entertainmentType = $this.data('entertainment-type'); // Get the type
                let action = isInWatchlist == '1' ? 'delete' : 'save';
                var data = isInWatchlist ? {
                    id: [entertainmentId],
                    _token: csrf_token,
                    type: entertainmentType || ''
                } : {
                    entertainment_id: entertainmentId,
                    type: entertainmentType,
                    _token: csrfToken
                };

                // Perform the AJAX request
                $.ajax({
                    url: action === 'save' ? `${baseUrl}/api/save-watchlist` :
                        `${baseUrl}/api/delete-watchlist?is_ajax=1`,
                    method: 'POST',
                    data: data,
                    success: function(response) {
                        window.successSnackbar(response.message)
                        $this.find('i').toggleClass('ph-check ph-plus');
                        $this.toggleClass('btn-primary btn-dark');
                        $this.data('in-watchlist', !isInWatchlist);
                        var newInWatchlist = !isInWatchlist ? 'true' : 'false';
                        var newTooltip = newInWatchlist === 'true' ? 'Remove Watchlist' :
                            'Add Watchlist';

                        // Destroy the current tooltip
                        $this.tooltip('dispose');

                        // Update the tooltip attribute
                        $this.attr('data-bs-title', newTooltip);

                        // Reinitialize the tooltip
                        $this.tooltip();
                        if (action !== 'save' && watchList) {
                            $this.closest('.iq-card').remove();
                            if (EntertainmentList.children.length === 0) {
                                if (watchListPresent) {
                                    emptyWatchList.style.display = '';
                                    const noDataImage = document.createElement('img');
                                    noDataImage.src = noDataImageSrc;
                                    noDataImage.alt = 'No Data Found';
                                    noDataImage.style.display = 'block';
                                    noDataImage.style.margin = '0 auto';
                                    emptyWatchList.appendChild(noDataImage);
                                }
                            }
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 401) {
                            window.location.href = `${baseUrl}/login`;
                        } else {
                            console.error(xhr);
                        }
                    }
                });
            });
        }
    </script>
@endsection
