@foreach ($values as $data)
    <div class="slick-item">
        <div class="iq-card card-hover entainment-slick-card hover-card-container" data-movie-id="{{ $data['id'] }}"
            data-movie-data="{{ json_encode($data) }}" onmouseenter="openHoverModal(this)"
            data-is-search="{{ isset($is_search) && $is_search == 1 ? 1 : null }}" onmouseleave="closeHoverModal(this)">
            <div class="block-images position-relative w-100">

                @if (isset($is_search) && $is_search == 1)
                    <a href="{{ route('video-details', ['id' => $data['slug'], 'is_search' => request()->has('search') ? 1 : null]) }}"
                        class="position-absolute top-0 bottom-0 start-0 end-0 w-100 h-100">
                    </a>
                @else
                    <a href="{{ route('video-details', ['id' => $data['slug']]) }}"
                        class="position-absolute top-0 bottom-0 start-0 end-0 w-100 h-100">
                    </a>
                @endif

                <div class="image-box w-100">
                    <img src="{{ $data['poster_image'] }}" alt="movie-card"
                        class="img-fluid object-cover w-100 d-block border-0">
                    @if (!empty($data['is_pay_per_view']))
                        @if (!empty($data['is_purchased']))
                            <span class="product-rent">
                                <i class="ph ph-film-reel"></i> {{ __('messages.rented') }}
                            </span>
                        @else
                            <span class="product-rent">
                                <i class="ph ph-film-reel"></i> {{ __('messages.rent') }}
                            </span>
                        @endif
                    @elseif (!empty($data['show_premium_badge']))
                        <button type="button" class="product-premium border-0" data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-title="{{ __('messages.lbl_premium') }}">
                            <i class="ph ph-crown-simple"></i>
                        </button>
                    @endif

                </div>

            </div>
        </div>
    </div>
@endforeach
