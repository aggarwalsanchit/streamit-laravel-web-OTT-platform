@php
    use Illuminate\Http\Resources\Json\JsonResource;

    $clipsCollection = collect($clips)->map(function ($clip) {
        if ($clip instanceof JsonResource) {
            return $clip->toArray(request());
        }
        if (is_array($clip)) {
            return $clip;
        }
        if (is_object($clip) && method_exists($clip, 'toArray')) {
            return $clip->toArray();
        }
        return (array) $clip;
    });
@endphp

@if ($clipsCollection->count())
    <div class="section-spacing-bottom">
        <div class="container-fluid padding-right-0">
            <div class="overflow-hidden">
                <div class="d-flex align-items-center justify-content-between my-2 me-2">
                    <h5 class="main-title text-capitalize mb-0">{{ $title ?? __('messages.lbl_clips') }}</h5>
                </div>
                <div class="card-style-slider">
                    <div class="slick-general" data-items="5.5" data-items-desktop="5" data-items-laptop="4.5"
                            data-items-tab="3.5" data-items-mobile-sm="2.5" data-items-mobile="2.5" data-speed="1000"
                            data-autoplay="false" data-center="false" data-infinite="false" data-navigation="true"
                            data-pagination="false" data-spacing="12">
                        @foreach ($clipsCollection as $clip)
                            @php
                                $clip = is_array($clip) ? $clip : (method_exists($clip, 'toArray') ? $clip->toArray() : []);
                                $poster = $clip['poster_url'] ?? ($clip['tv_poster_url'] ?? '');
                                $url = $clip['url'] ?? '#';
                                $type = strtolower($clip['type'] ?? '');
                                $titleText = $clip['title'] ?? '';
                            @endphp
                            <div class="slick-item">
                                <div class="trailer-card">
                                    <div class="trailer-image">
                                        <a href="{{ $url }}" rel="noopener" class="clip-link" data-url="{{ $url }}"
                                            data-type="{{ $type }}">
                                            <img src="{{ $poster }}" alt="{{ $titleText ?: 'clip' }}"
                                                class="object-fit-cover rounded">
                                        </a>
                                    </div>
                                    <div class="trailer-details">
                                        <h5 class="mb-2">
                                            <a href="{{ $url }}" rel="noopener" class="clip-link" data-url="{{ $url }}"
                                                data-type="{{ $type }}">{{ $titleText }}</a>
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

