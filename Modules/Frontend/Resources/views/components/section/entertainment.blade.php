@php
    $viewAllRoute = null;
    
    // Map slug to content list route
    $slugToRouteMap = [
        'latest_movie' => route('content.list', ['type' => 'latest-movies']),
        'popular_movie' => route('content.list', ['type' => 'popular-movies']),
        'popular_tvshow' => route('content.list', ['type' => 'popular-tv-shows']),
        'top_rated_movies' => route('content.list', ['type' => 'top-rated-movies']),
        'trending_movie' => route('content.list', ['type' => 'trending-movies']),
        'free_movie' => route('content.list', ['type' => 'free-movies']),
        'liked_movie' => route('content.list', ['type' => 'liked-movies']),
    ];
    
    // Check if slug exists in the map
    if (!empty($slug) && isset($slugToRouteMap[$slug])) {
        $viewAllRoute = $slugToRouteMap[$slug];
    } elseif (!empty($slug)) {
        // Custom dashboard section
        $viewAllRoute = route('custom-section', ['slug' => $slug]);
    } else {
        // Fallback to default routes
        switch ($type) {
            case 'tvshow':
                $viewAllRoute = route('tv-shows');
                break;
            default:
                $viewAllRoute = route('movies');
        }
    }

    $slickClasses = [
        'latest_movie' => 'slick-general-latest-movie',
        'popular_movie' => 'slick-general-popular-movie',
        'popular_tvshow' => 'slick-general-popular-tvshow',
        'free_movie' => 'slick-general-free-movie',
        'based_on_last_watch' => 'slick-general-last-watch',
        'most-like' => 'slick-general-most-like',
        'most-view' => 'slick-general-most-view',
        'tranding-in-country' => 'slick-general-tranding-country',
    ];

    $class = 'slick-general ' . ($slickClasses[$slug] ?? '');

@endphp

<div class="streamit-block">
    <div class="d-flex align-items-center justify-content-between my-2 me-2">
        <h5 class="main-title text-capitalize mb-0">{{ $title }}</h5>

        @php
            $showViewAll = false;
            $thresholdSlugs = ['latest_movie', 'popular_movie', 'popular_tvshow'];

            if (!empty($slug) && in_array($slug, $thresholdSlugs, true)) {
                // Standard sections: show View All only if data count >= 9
                $showViewAll = count($data) >= 9;
            } elseif (!empty($slug) && !isset($slugToRouteMap[$slug])) {
                // Custom sections: show View All only if data count > 9
                $showViewAll = count($data) > 9;
            } else {
                // Other sections: show View All if data count > 0
                $showViewAll = count($data) > 0;
            }
        @endphp

        @if ($showViewAll)
            <a href="{{ $viewAllRoute }}" class="view-all-button text-decoration-none flex-none">
                <span>{{ __('frontend.view_all') }}</span>
                <i class="ph ph-caret-right"></i>
            </a>
        @endif
    </div>

    <div class="card-style-slider {{ count($data) <= 6 ? 'slide-data-less' : '' }}">
        <div class="{{ $class }}" data-items="6.5" data-items-desktop="5.5" data-items-laptop="4.5"
            data-items-tab="3.5" data-items-mobile-sm="3.5" data-items-mobile="2.5" data-speed="1000"
            data-autoplay="false" data-center="false" data-infinite="false" data-navigation="true"
            data-pagination="false" data-spacing="12">





            @if ($type == 'movie')
                @include('frontend::components.card.card_movie', ['values' => $data])
            @else
                @include('frontend::components.card.card_tvshow', ['values' => $data])
            @endif



        </div>
    </div>
</div>
