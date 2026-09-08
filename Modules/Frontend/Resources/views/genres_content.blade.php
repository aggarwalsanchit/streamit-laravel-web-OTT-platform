@extends('frontend::layouts.master')

@section('title')
    {{ $genre->name ?? '' }}
@endsection

@section('content')
    <div class="list-page section-spacing-bottom px-0">
        <div class="movie-lists">
            <div class="container-fluid">


                <h4 class="mb-5 text-center">
                    {{ $genre->name }}
                </h4>

                <!-- Content Type Tabs -->
                <ul class="nav nav-pills comingsoon-tabs mb-4" id="content-tabs" role="tablist">
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
        const envURL = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const shimmerContainer = document.querySelector('.shimmer-container');
        const EntertainmentList = document.getElementById('entertainment-list');
        const pageTitle = document.getElementById('page_title');
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        const per_page = 12;
        const csrf_token = '{{ csrf_token() }}'
        const language = "{{ $language ?? '' }}";
        const genreId = "{{ $genre_id ?? '' }}";
        const accessType = "{{ $access_type ?? '' }}";
        let currentType = 'both';

        const buildApiUrl = (page = 1) => {
            let apiUrl =
                `${envURL}/api/genre-content-list?page=${page}&is_ajax=1&per_page=${per_page}&type=${currentType}`;

            // Add query parameters only if they exist
            if (language) {
                apiUrl += `&language=${language}`;
            }
            if (genreId) {
                apiUrl += `&genre_id=${genreId}`;
            }
            if (accessType) {
                apiUrl += `&access_type=${accessType}`;
            }

            return apiUrl;
        };

        const showNoDataImage = () => {
            shimmerContainer.innerHTML = '';
            const noDataImage = document.createElement('img');
            noDataImage.src = noDataImageSrc;
            noDataImage.alt = 'No Data Found';
            noDataImage.classList.add('img-fluid', 'd-block', 'mx-auto', 'mt-5');
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
                                    noDataImage.classList.add('img-fluid', 'd-block', 'mx-auto', 'mt-5');
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
        // Initialize Banner Swiper
        new Swiper('.banner-swiper', {
            loop: true,
            autoplay: {
                delay: 5000,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            }
        });
    </script>
@endsection
