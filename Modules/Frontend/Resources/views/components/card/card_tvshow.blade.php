@foreach ($values as $value)
    <div class="slick-item">
        <div class="iq-card card-hover entainment-slick-card hover-card-container" data-movie-id="{{ $value['id'] }}"
            data-movie-data="{{ json_encode($value) }}" data-trailer-url="{{ $value['trailer_url'] ?? '' }}"
            data-trailer-type="{{ $value['trailer_url_type'] ?? '' }}" onmouseenter="openHoverModal(this)"
            onmouseleave="closeHoverModal(this)" data-is-search="{{ isset($is_search) && $is_search == 1 ? 1 : null }}">

            <div class="block-images position-relative w-100" data-trailer-url="{{ $value['trailer_url'] ?? '' }}"
                data-trailer-type="{{ $value['trailer_url_type'] ?? '' }}">
                @php
                    $isComingSoon =
                        isset($value['release_date']) && \Carbon\Carbon::parse($value['release_date'])->isFuture();
                @endphp
                @if (isset($is_search) && $is_search == 1)
                    <a href="{{ route('tvshow-details', ['id' => $value['slug'], 'is_search' => request()->has('search') ? 1 : null]) }}"
                        class="position-absolute top-0 bottom-0 start-0 end-0 w-100 h-100">
                    </a>
                @else
                    <a href="{{ route('tvshow-details', ['id' => $value['slug']]) }}"
                        class="position-absolute top-0 bottom-0 start-0 end-0 w-100 h-100">
                    </a>
                @endif
                <div class="image-box w-100 position-relative">
                    <img src="{{ $value['poster_image'] }}" alt="movie-card"
                        class="img-fluid object-cover w-100 d-block border-0" loading="lazy">
                    <div class="trailer-preview position-absolute top-0 start-0 w-100 h-100"></div>
                    @if ($value['is_pay_per_view'])
                        @if ($value['is_purchased'])
                            <span class="product-rent">
                                <i class="ph ph-film-reel"></i> {{ __('messages.rented') }}
                            </span>
                        @else
                            <span class="product-rent">
                                <i class="ph ph-film-reel"></i> {{ __('messages.rent') }}
                            </span>
                        @endif
                    @endif
                    @if ($value['show_premium_badge'])
                        <button type="button" class="product-premium border-0" data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-title="{{ __('messages.lbl_premium') }}"><i class="ph ph-crown-simple"></i></button>
                    @endif
                    @if ($value['imdb_rating'])
                        <span class="ratting-value">
                            <i class="ph ph-star"></i>
                            {{ $value['imdb_rating'] }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach
