<div class="iq-card card-hover entainment-slick-card">

    <div class="block-images position-relative w-100" data-trailer-url="{{ $value['trailer_url'] ?? '' }}"
        data-trailer-type="{{ $value['trailer_url_type'] ?? '' }}">
        @php
            $isComingSoon = isset($value['release_date']) && \Carbon\Carbon::parse($value['release_date'])->isFuture();
        @endphp
        @if (isset($is_search) && $is_search == 1)
            <a href="{{ $value['type'] == 'tvshow'
                ? route('tvshow-details', ['id' => $value['slug'], 'is_search' => request()->has('search') ? 1 : null])
                : route('movie-details', ['id' => $value['slug'], 'is_search' => request()->has('search') ? 1 : null]) }}"
                class="position-absolute top-0 bottom-0 start-0 end-0 w-100 h-100">
            </a>
        @else
            <a href="{{ $value['type'] == 'tvshow' ? route('tvshow-details', ['id' => $value['slug']]) : route('movie-details', ['id' => $value['slug']]) }}"
                class="position-absolute top-0 bottom-0 start-0 end-0 w-100 h-100">
            </a>
        @endif
        <div class="image-box w-100 position-relative">
            <img src="{{ $value['poster_image'] }}" alt="movie-card"
                class="img-fluid object-cover w-100 d-block border-0">
            <div class="trailer-preview position-absolute top-0 start-0 w-100 h-100"></div>
            @if ($value['movie_access'] == 'pay-per-view')
                @if (\Modules\Entertainment\Models\Entertainment::isPurchased($value['id'], $value['type']))
                    <span class="product-rent">
                        <i class="ph ph-film-reel"></i> {{ __('messages.rented') }}
                    </span>
                @else
                    <span class="product-rent">
                        <i class="ph ph-film-reel"></i> {{ __('messages.rent') }}
                    </span>
                @endif
            @endif
            @if ($value['movie_access'] == 'paid')
                @php
                    $current_user_plan = auth()->user() ? auth()->user()->subscriptionPackage : null;
                    $current_plan_level = $current_user_plan->level ?? 0;
                @endphp

                @if ($value['plan_level'] > $current_plan_level || auth()->user() == null)
                    <button type="button" class="product-premium border-0" data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-title="{{ __('messages.lbl_premium') }}"><i class="ph ph-crown-simple"></i></button>
                @endif
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
                        <li class="small">{{ $gener['name'] ?? ($gener->resource->genre->name ?? '--') }}</li>
                    @endforeach
                </ul>

                <h5 class="iq-title text-capitalize line-count-1"> {{ $value['name'] ?? '--' }} </h5>
                <div class="d-flex align-items-center gap-3">
                    <div class="movie-time d-flex align-items-center gap-1 font-size-14">
                        <i class="ph ph-clock"></i>
                        {{ $value['duration'] ? formatDuration($value['duration']) : '--' }}
                    </div>
                    <div class="movie-language d-flex align-items-center gap-1 font-size-14">
                        <i class="ph ph-translate"></i>
                        <small>{{ ucfirst($value['language']) }}</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 mt-3">
                    <x-watchlist-button :entertainment-id="$value['id']" :in-watchlist="$value['is_watch_list']" :entertainmentType="$value['type']"
                        customClass="watch-list-btn" />

                    <div class="flex-grow-1">
                        <a href="{{ $isComingSoon && in_array($value['type'], ['movie', 'tvshow'])
                            ? route('comming-soon-details', ['id' => $value['id']])
                            : ($value['type'] == 'tvshow'
                                ? route('tvshow-details', ['id' => $value['slug']])
                                : route('movie-details', ['id' => $value['slug']])) }}"
                            class="btn btn-primary w-100">
                            {{ __('frontend.watch_now') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
