@php
    $dataArray = is_array($data) ? $data : $data->toArray(request());
    $count = count($dataArray);

    $viewAllRoute = null;
    if (empty($is_watch_list)) {
        switch ($type) {
            case 'tvshow':
                $viewAllRoute = route('tv-shows');
                break;
            case 'pay-per-view':
                $viewAllRoute = route('pay-per-view');
                break;
            default:
                $viewAllRoute = route('movies');
        }
    } else {
        $viewAllRoute = route('watchList');
    }

    $slickClasses = [
        'latest_movie'         => 'slick-general-latest-movie',
        'popular_movie'        => 'slick-general-popular-movie',
        'popular_tvshow'       => 'slick-general-popular-tvshow',
        'free_movie'           => 'slick-general-free-movie',
        'based_on_last_watch'  => 'slick-general-last-watch',
        'most-like'            => 'slick-general-most-like',
        'most-view'            => 'slick-general-most-view',
        'tranding-in-country'  => 'slick-general-tranding-country',
        'per_pay_view'         => 'slick-general-pav-per-view',
        'movies_pay_per_view'  => 'slick-general-movies-pay-per-view',
        'tvshows_pay_per_view' => 'slick-general-tvshows-pay-per-view',
    ];

    $class = 'slick-general ' . ($slickClasses[$slug] ?? '');
@endphp

<div class="streamit-block">
    <div class="d-flex align-items-center justify-content-between my-2 me-2">
        <h5 class="main-title text-capitalize mb-0">{{ $title }}</h5>

        @if($type !== 'movies-pay-per-view' && $type !== 'tvshows-pay-per-view' && $count > 6)
            <a href="{{ $viewAllRoute }}"
               class="view-all-button text-decoration-none flex-none">
               <span>{{ __('frontend.view_all') }}</span>
               <i class="ph ph-caret-right"></i>
            </a>
        @endif
    </div>

    <div class="card-style-slider {{ $count <= 6 ? 'slide-data-less' : '' }}">
        <div class="{{ $class }}"
             data-items="6.5" data-items-desktop="5.5"
             data-items-laptop="4.5" data-items-tab="3.5"
             data-items-mobile-sm="3.5" data-items-mobile="2.5"
             data-speed="1000" data-autoplay="false"
             data-center="false" data-infinite="false"
             data-navigation="true" data-pagination="false"
             data-spacing="12">
             

            @foreach($dataArray as $value)
                <div class="slick-item">
                    @switch($value['type'] ?? null)
                        @case('video')
                            @include('frontend::components.card.card_video', ['data' => $value])
                            @break

                        @case('episode')
                            @include('frontend::components.card.card_pay_per_view', ['data' => $value])
                            @break

                        @default
                            @include('frontend::components.card.card_entertainment', ['value' => $value])
                    @endswitch
                </div>
            @endforeach

        </div>
    </div>
</div>
