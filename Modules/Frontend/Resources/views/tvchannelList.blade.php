@extends('frontend::layouts.master')

@section('title')
    {{ $categoryName ?? __('frontend.tv_channels') }}
@endsection

@section('content')
    <div class="list-page section-spacing-bottom px-0">
        <div class="page-title">
            <h4 class="m-0 text-center">{{ $categoryName ?? __('frontend.tv_channels') }}</h4>
        </div>

        <div class="movie-lists section-spacing-bottom">
            <div class="container-fluid">
                <div class="row mt-3 gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4" id="tvchannel-list">
                </div>
                <div class="card-style-slider shimmer-container">
                    <div
                        class="row mt-3 gy-4 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4">
                        @for ($i = 0; $i < 12; $i++)
                            <div class="col">
                                @include('components.card_shimmer_channel')
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
        const EntertainmentList = document.getElementById('tvchannel-list');
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        const per_page = 12;
        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const categoryId = "{{ $tvcategory_id ?? '' }}";
        let apiUrl = `${baseUrl}/api/channel-list?is_ajax=1&per_page=${per_page}`;
        if (categoryId) {
            apiUrl += `&category_id=${categoryId}`;
        }

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
    </script>
@endsection
