@props([
    'value' => [],
    'modalId' => 'cardDetailModal',
    'isComingSoon' => false,
])

<!-- Card Detail Hover Overlay -->
<div class="hover-modal-overlay" id="{{ $modalId }}">
    <div class="hover-modal-content">
        <div class="hover-modal-body">
            <div class="iq-card card-hover entainment-slick-card">
                <div class="block-images position-relative w-100" data-trailer-url="{{ $value['trailer_url'] ?? '' }}"
                    data-trailer-type="{{ $value['trailer_url_type'] ?? 'youtube' }}">

                    <div class="image-box w-100 position-relative">
                        <img src="{{ $value['poster_image'] ?? '/img/placeholder.jpg' }}" alt="movie-card"
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
                                data-bs-placement="top" data-bs-title="{{ __('messages.lbl_premium') }}"><i
                                    class="ph ph-crown-simple"></i></button>
                        @endif

                        @if ($value['imdb_rating'])
                            <span class="ratting-value">
                                <i class="ph ph-star"></i>
                                {{ $value['imdb_rating'] }}
                            </span>
                        @endif
                    </div>

                    <div class="card-description with-transition">
                        <div class="position-relative w-100">
                            <ul class="genres-list ps-0 mb-2 d-flex align-items-center gap-5">
                                @foreach (collect($value['genres'])->slice(0, 2) as $gener)
                                    <li class="small">{{ $gener['name'] ?? ($gener->resource->genre->name ?? '--') }}
                                    </li>
                                @endforeach
                            </ul>

                            <h5 class="iq-title text-capitalize line-count-1"> {{ $value['name'] ?? '--' }} </h5>

                            <div class="d-flex align-items-center gap-3">
                                @if ($value['duration'])
                                    <div class="movie-time d-flex align-items-center gap-1 font-size-14">
                                        <i class="ph ph-clock"></i>
                                        {{ formatDuration($value['duration']) ?? '--' }}

                                    </div>
                                @endif
                                @if ($value['language'])
                                    <div class="movie-language d-flex align-items-center gap-1 font-size-14">
                                        <i class="ph ph-translate"></i>
                                        <small>{{ ucfirst($value['language']) }}</small>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex align-items-center gap-3 mt-3">
                                <x-watchlist-button :entertainment-id="$value['id']" :in-watchlist="$value['is_watch_list']" :entertainmentType="'movie'"
                                    customClass="watch-list-btn" />

                                <div class="flex-grow-1">
                                    <a href="{{ $isComingSoon && $value['type'] == 'movie'
                                        ? route('comming-soon-details', ['id' => $value['id']])
                                        : route('movie-details', ['id' => $value['slug']]) }}"
                                        class="btn btn-primary w-100">
                                        {{ __('frontend.watch_now') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
