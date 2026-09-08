<div class="iq-card card-hover entainment-slick-card hover-card-container" data-movie-id="{{ $value['id'] }}"
    data-movie-data="{{ json_encode($value) }}" onmouseenter="openHoverModal(this)" onmouseleave="closeHoverModal(this)">
    <div class="block-images position-relative w-100">

        @if (isset($is_search) && $is_search == 1)
            <a href="{{ isset($value['season_id'])
                ? route('episode-details', ['id' => $value['slug'], 'is_search' => request()->has('search') ? 1 : null])
                : route('tvshow-details', ['id' => $value['slug'], 'is_search' => request()->has('search') ? 1 : null]) }}"
                class="position-absolute top-0 bottom-0 start-0 end-0 w-100 h-100">
            </a>
        @else
            <a href="{{ isset($value['season_id'])
                ? route('episode-details', ['id' => $value['slug']])
                : route('tvshow-details', ['id' => $value['slug']]) }}"
                class="position-absolute top-0 bottom-0 start-0 end-0 w-100 h-100">
            </a>
        @endif
        <div class="image-box w-100 position-relative">
            <img src="{{ $value['poster_image'] }}" alt="movie-card"
                class="img-fluid object-cover w-100 d-block border-0">
            <div class="trailer-preview position-absolute top-0 start-0 w-100 h-100"></div>
            @if ($value['access'] == 'pay-per-view')
                @if (\Modules\Entertainment\Models\Entertainment::isPurchased($value['id'], $value['type']))
                    <!-- Display "RENTED" badge if the movie is purchased -->
                    <span
                        class="product-rent">
                        <i class="ph ph-film-reel"></i> {{ __('messages.rented') }}
                    </span>
                @else
                    <!-- Display "RENT" badge if the movie is available for rent -->
                    <span
                        class="product-rent">
                        <i class="ph ph-film-reel"></i> {{ __('messages.rent') }}
                    </span>
                @endif
            @endif
        </div>
        {{-- <div class="card-description with-transition">
            <div class="position-relative w-100">

                <h5 class="iq-title text-capitalize line-count-1"> {{ $value['name'] ?? '--' }} </h5>
                <p class="line-count-2 font-size-14 mb-1"> {{ $value['short_desc'] ?? '' }} </p>
                <div class="movie-time d-flex align-items-center gap-1 font-size-14">
                    <i class="ph ph-clock"></i>
                    {{ $value['duration'] ? formatDuration($value['duration']) : '--' }}
                </div>
                <div class="d-flex align-items-center gap-3">

                </div>
                <div class="d-flex align-items-center gap-3 mt-3  font-size-14">


                    <div class="flex-grow-1">
                        <a href="{{ isset($value['season_id']) ? route('episode-details', ['id' => $value['slug']]) : route('tvshow-details', ['id' => $value['slug']]) }}"
                            class="btn btn-primary w-100">
                            {{ __('frontend.watch_now') }}
                        </a>
                    </div>
                </div>
            </div>
        </div> --}}
    </div>
</div>
