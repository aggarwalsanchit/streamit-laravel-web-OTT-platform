@extends('frontend::layouts.master')

@section('title')
    {{ $title }}
@endsection
@section('content')
    <!-- Content listing page -->
    <div class="list-page section-spacing-bottom px-0">
        <div class="container">
            <div class="d-flex justify-content-center mb-3">
                <h4 class="m-0 text-center">{{ $title }}</h4>
            </div>

            <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6"
                id="content-list-container"></div>
            <div class="card-style-slider shimmer-content-list" style="display:none;">
                <div class="row gy-4 row-cols-2 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 mt-2">
                    @for ($i = 0; $i < 12; $i++)
                        <div class="shimmer-container col mb-3">@include('components.card_shimmer_movieList')</div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const baseUrl = document.querySelector('meta[name="baseUrl"]').content;
            const container = document.getElementById('content-list-container');
            const shimmer = document.querySelector('.shimmer-content-list');
            const contentType = '{{ $type ?? '' }}';
            const apiEndpoint = '{{ $apiEndpoint ?? '' }}';

            let currentPage = 1;
            let loading = false;
            let hasMore = true;

            // Function to build API URL
            function getApiUrl(page) {
                const endpoint = apiEndpoint
                    ? `${baseUrl}${apiEndpoint}`
                    : `${baseUrl}/api/content-list/${contentType}`;
                const separator = endpoint.includes('?') ? '&' : '?';
                return `${endpoint}${separator}is_ajax=1&per_page=12&page=${page}`;
            }

            const noDataImageSrc = '{{ asset('img/NoData.png') }}';

            const showNoDataImage = () => {
                if (shimmer) {
                    shimmer.style.display = 'block';
                    shimmer.innerHTML = `<div class="col-12 text-center py-5">
                        <img src="${noDataImageSrc}" alt="No Data Found" class="img-fluid d-block mx-auto" style="max-width: 450px;">
                    </div>`;
                }
            };

            // Load data
            async function loadData() {
                if (loading || !hasMore) return;

                loading = true;
                if (shimmer) shimmer.style.display = 'block';

                try {
                    const url = getApiUrl(currentPage);
                    const response = await fetch(url);
                    const data = await response.json();

                    if (shimmer) shimmer.style.display = 'none';

                    // Clear container only on first load
                    if (currentPage === 1 && container) container.innerHTML = '';

                    // Insert new HTML if available
                    if (data.html && container) {
                        container.insertAdjacentHTML('beforeend', data.html);
                        if (window.initTrailerHover) window.initTrailerHover();
                    } else if (currentPage === 1) {
                        showNoDataImage();
                        if (container) container.innerHTML = '';
                    }

                    // Update pagination state
                    currentPage++;
                    if (data.hasMore === false) hasMore = false;
                } catch (error) {
                    console.error('Failed to load content data:', error);
                    if (shimmer) shimmer.style.display = 'none';
                } finally {
                    loading = false;
                }
            }

            // Setup infinite scroll
            function setupInfiniteScroll() {
                window.addEventListener('scroll', () => {
                    if (!hasMore || loading) return;

                    const nearBottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - 400;
                    if (nearBottom) loadData();
                });
            }

            // Initialize
            loadData();
            setupInfiniteScroll();
        });
    </script>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize any required scripts here
        });
    </script>
@endpush
