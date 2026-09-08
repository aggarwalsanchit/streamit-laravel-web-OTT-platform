@if (count($data) > 0)
    <div class="favourite-person-block">
        <div class="d-flex align-items-center justify-content-between my-2 me-2">
            <h5 class="main-title text-capitalize mb-0">{{ $title }}</h5>
            <a href="{{ route('movie-castcrew-list', ['id' => 'all', 'type' => 'actor']) }}"
                class="view-all-button text-decoration-none flex-none"><span>{{ __('frontend.view_all') }}</span> <i
                    class="ph ph-caret-right"></i></a>
        </div>

        <div class="card-style-slider {{ count($data) <= 8 ? 'slide-data-less' : '' }}">
            <div class="slick-general {{ $slug == 'favorite_personality' ? 'slick-general-castcrew' : ($slug == 'user-favorite_personality' ? 'slick-general-favorite-personality' : '') }}"
                data-items="10.5" data-items-desktop="6.5" data-items-laptop="5.5" data-items-tab="4.5"
                data-items-mobile-sm="3.5" data-items-mobile="2.5" data-speed="1000" data-autoplay="false"
                data-center="false" data-infinite="false" data-navigation="true" data-pagination="false"
                data-spacing="12">
                @foreach ($data as $value)
                    <div class="slick-item">
                        @include('frontend::components.card.card_castcrew', ['data' => $value])
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
