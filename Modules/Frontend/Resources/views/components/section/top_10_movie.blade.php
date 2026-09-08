<div class="top-ten-block">
    <div class="d-flex align-items-center justify-content-between my-3">
        <h5 class="main-title text-capitalize mb-0">{{ $sectionName }}</h5>
    </div>
    <div class="card-style-slider {{ count($top10) <= 6 ? 'slide-data-less' : '' }}">
        <div class="slick-general slick-general-top-10  iq-top-ten-block-slider" data-items="6.5" data-items-desktop="5.5"
            data-items-laptop="4.5" data-items-tab="3.5" data-items-mobile-sm="3.5" data-items-mobile="2.5"
            data-speed="1000" data-autoplay="false" data-center="false" data-infinite="false" data-navigation="true"
            data-pagination="false" data-spacing="12">
            @foreach ($top10 as $index => $data)
                <div class="slick-item">
                    <div class="iq-top-ten-block iq-card">
                        <div class="block-image block-images position-relative">
                            <div class="img-box image-box">
                                @php
                                    $isTvShow = ($data['type'] ?? '') === 'tvshow';
                                    $detailRoute = $isTvShow
                                        ? route('tvshow-details', ['id' => $data['slug']])
                                        : route('movie-details', ['id' => $data['slug']]);
                                @endphp
                                <a class="overly-images" href="{{ $detailRoute }}">
                                    <img src="{{ $data['poster_image'] }}" alt="{{ $data['name'] ?? 'poster' }}"
                                        class="img-fluid object-cover top-ten-img" loading="lazy" decoding="async"
                                        referrerpolicy="no-referrer">
                                    @if (!empty($data['is_pay_per_view']))
                                        <span class="product-rent">
                                            <i class="ph ph-film-reel"></i>
                                            {{ $data['is_purchased'] ? __('messages.rented') : __('messages.rent') }}
                                        </span>
                                    @endif

                                    @if (!empty($data['show_premium_badge']))
                                        <button type="button" class="product-premium border-0" data-bs-toggle="tooltip"
                                            data-bs-placement="top" data-bs-title="{{ __('messages.lbl_premium') }}">
                                            <i class="ph ph-crown-simple"></i>
                                        </button>
                                    @endif
                                    @php
                                        $imdbRating = $data['imdb_rating'] ?? data_get($data, 'details.imdb_rating');
                                    @endphp
                                    @if (!empty($imdbRating))
                                        <span class="ratting-value">
                                            <i class="ph ph-star"></i>
                                            {{ $imdbRating }}
                                        </span>
                                    @endif
                                </a>
                            </div>
                            <span class="top-ten-numbers texture-text"
                                style="background-image: url('{{ asset('img/web-img/texture.jpg') }}');">
                                {{ $index + 1 }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
