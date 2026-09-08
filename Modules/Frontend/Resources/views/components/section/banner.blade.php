@php
    $sliderCount = is_countable($data) ? count($data) : 0;
@endphp

<div class="slick-banner main-banner" data-speed="1000" data-autoplay="true" data-center="false" data-infinite="false"
    data-navigation="{{ $sliderCount > 1 ? 'true' : 'false' }}"
    data-pagination="{{ $sliderCount > 1 ? 'true' : 'false' }}"
    data-spacing="0">

    @foreach ($data as $slider)

        @if (!empty($slider['data']))

            @php
                $sliderData = !empty($slider['data']) && is_object($slider['data']) ? $slider['data']->toArray(request()) : $slider['data'];
                $details = data_get($sliderData, 'details', $sliderData);
                
                $data = is_array($details) ? $details : (is_object($details) ? $details->toArray(request()) : []);
            @endphp

            @if (isenablemodule($slider['type']) == 1)
                <div class="slick-item"
                    style="background-image: url({{ setBaseUrlWithFileName($slider['file_url'], 'image', 'banner') }});">
                    <div class="movie-content h-100">
                        <div class="container-fluid h-100">
                            <div class="row align-items-center h-100">
                                <div class="col-xxl-4 col-lg-6">
                                    <div class="movie-info">
                                        <div class="movie-tag mb-3">
                                            <ul
                                                class="list-inline m-0 p-0 d-flex align-items-center flex-wrap movie-tag-list">
                                                @if (!empty($data['genres']))
                                                    @foreach ($data['genres'] as $genres)
                                                        <li>
                                                            <a href="#" class="tag">{{ $genres['name'] }}</a>
                                                        </li>
                                                    @endforeach
                                                @endif
                                            </ul>
                                        </div>
                                        <h4 class="mb-2">{{ $data['name'] }}</h4>
                                        <div class="mb-0 font-size-14 line-count-3">{!! $data['description'] !!}</div>
                                        <ul
                                            class="list-inline mt-4 mb-0 mx-0 p-0 d-flex align-items-center flex-wrap gap-3">

                                            <li>
                                                @if (!empty($data['release_date']))
                                                    <span class="d-flex align-items-center gap-2">
                                                        <span><i class="ph ph-calendar-blank"></i></span>
                                                        <span class="fw-medium">{{ date('Y', strtotime($data['release_date'])) }}</span>
                                                    </span>
                                                @endif
                                            </li>
                                            <li>
                                                @if (!empty($data['language']))
                                                    <span class="d-flex align-items-center gap-2">
                                                        <span><i class="ph ph-translate lh-base"></i></span>
                                                        <span class="fw-medium">{{ ucfirst($data['language']) }}</span>
                                                    </span>
                                                @endif
                                            </li>
                                            <li>
                                                @if (!empty($data['duration']))
                                                    <span class="d-flex align-items-center gap-2">
                                                        <span><i class="ph ph-clock lh-base"></i></span>
                                                        <span
                                                            class="fw-medium">{{ str_replace(':', 'h ', $data['duration']) . 'm' }}</span>
                                                    </span>
                                                @endif
                                            </li>
                                            @if (!empty($data['imdb_rating']))
                                                <li>
                                                    <span class="d-flex align-items-center gap-2">
                                                        <span><i class="ph ph-star lh-base"></i></span>
                                                        <span class="fw-medium">{{ $data['imdb_rating'] }} ({{ __('messages.lbl_IMDb') }})</span>
                                                    </span>
                                                </li>
                                            @endif
                                        </ul>
                                        <div class="mt-5">
                                            <div class="d-flex align-items-center gap-3">
                                                @if ($slider['type'] != 'livetv')
                                                <x-watchlist-button :entertainment-id="$data['id'] ?? null" :in-watchlist="$data['is_watch_list'] ?? null"
                                                :entertainmentType="$slider['type']" customClass="watch-list-btn" />
                                                @endif
                                                <div class="">
                                                    <a href="{{ $slider['type'] == 'livetv'
                                                        ? route('livetv-details', ['id' => $data['slug']])
                                                        : ($slider['type'] == 'video'
                                                            ? route('video-details', ['id' => $data['slug']])
                                                            : ($data['type'] == 'tvshow'
                                                                ? route('tvshow-details', ['id' => $data['slug']])
                                                                : route('movie-details', ['id' => $data['slug']]))) }}"
                                                        class="btn btn-primary">
                                                        <span
                                                            class="d-flex align-items-center justify-content-center gap-2">
                                                            <span><i class="ph-fill ph-play"></i></span>
                                                            <span>{{ __('frontend.play_now') }}</span>
                                                        </span>
                                                    </a>
                                                </div>
                                                <div class="position-relative share-button dropend dropdown">
                                                    <button type="button"
                                                        data-bs-toggle="dropdown" data-bs-auto-close="outside" title="{{__('messages.lbl_share')}}"
                                                        class="action-btn btn btn-dark share-list-btn" data-bs-share="tooltip"
                                                        aria-expanded="false">
                                                        <i class="ph ph-share-network"></i>
                                                    </button>
                                                    <div class="share-wrapper">
                                                        <div class="share-box dropdown-menu">
                                                            <svg width="15" height="40" viewBox="0 0 15 40"
                                                                class="share-shape" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                                    d="M14.8842 40C6.82983 37.2868 1 29.3582 1 20C1 10.6418 6.82983 2.71323 14.8842 0H0V40H14.8842Z"
                                                                    fill="currentColor"></path>
                                                            </svg>
                                                            <div
                                                                class="d-flex align-items-center justify-content-center gap-3 px-3">
                                                                <a href="https://www.facebook.com/sharer?u={{ urlencode(Request::url()) }}"
                                                                    target="_blank" rel="noopener noreferrer"
                                                                    class="share-ico">
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        height="25" width="25"
                                                                        viewBox="-204.79995 -341.33325 1774.9329 2047.9995">
                                                                        <path
                                                                            d="M1365.333 682.667C1365.333 305.64 1059.693 0 682.667 0 305.64 0 0 305.64 0 682.667c0 340.738 249.641 623.16 576 674.373V880H402.667V682.667H576v-150.4c0-171.094 101.917-265.6 257.853-265.6 74.69 0 152.814 13.333 152.814 13.333v168h-86.083c-84.804 0-111.25 52.623-111.25 106.61v128.057h189.333L948.4 880H789.333v477.04c326.359-51.213 576-333.635 576-674.373"
                                                                            fill="#1877f2" />
                                                                        <path
                                                                            d="M948.4 880l30.267-197.333H789.333V554.609C789.333 500.623 815.78 448 900.584 448h86.083V280s-78.124-13.333-152.814-13.333c-155.936 0-257.853 94.506-257.853 265.6v150.4H402.667V880H576v477.04a687.805 687.805 0 00106.667 8.293c36.288 0 71.91-2.84 106.666-8.293V880H948.4"
                                                                            fill="#fff" />
                                                                    </svg>
                                                                </a>
                                                                <a href="https://twitter.com/intent/tweet?text={{ urlencode($data['name']) }}&url={{ urlencode(Request::url()) }}"
                                                                    target="_blank" rel="noopener noreferrer"
                                                                    class="share-ico">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" x="0px"
                                                                        y="0px" width="20" height="20"
                                                                        viewBox="0 0 50 50">
                                                                        <path
                                                                            d="M 6.9199219 6 L 21.136719 26.726562 L 6.2285156 44 L 9.40625 44 L 22.544922 28.777344 L 32.986328 44 L 43 44 L 28.123047 22.3125 L 42.203125 6 L 39.027344 6 L 26.716797 20.261719 L 16.933594 6 L 6.9199219 6 z"
                                                                            fill="#fff"></path>
                                                                    </svg>
                                                                </a>
                                                                <a href="#" data-link="{{ $slider['type'] == 'livetv'
                                                                    ? route('livetv-details', ['id' => $data['slug']])
                                                                    : ($slider['type'] == 'video'
                                                                        ? route('video-details', ['id' => $data['slug']])
                                                                        : ($data['type'] == 'tvshow'
                                                                            ? route('tvshow-details', ['id' => $data['slug']])
                                                                            : route('movie-details', ['id' => $data['slug']]))) }}"
                                                                    class="share-ico iq-copy-link" onclick="copyLink(this)"><i
                                                                        class="ph ph-link"></i></a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xxl-4 col-lg-6 d-lg-block d-none"></div>
                                <div class="col-xxl-4 d-xxl-block d-none"></div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        @endif
    @endforeach
</div>


@push('after-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const playButtons = document.querySelectorAll('.play-now-btn');
            playButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const encryptedUrl = this.getAttribute('data-encrypted-url');

                    if (encryptedUrl) {
                        fetch('{{ route('decrypt.url') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    encrypted_url: encryptedUrl
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.url) {
                                    window.open(data.url, '_blank');
                                } else {
                                    alert('Error: ' + data.error);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                            });
                    }
                });
            });
        });
        function copyLink(element) {
            const link = element.getAttribute('data-link');

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(link).then(() => {
                    showCopyMessage();
                }).catch(() => {
                    fallbackCopy(link);
                });
            } else {
                fallbackCopy(link);
            }
        }

        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showCopyMessage();
        }

        function showCopyMessage() {
            if (typeof window.successSnackbar === 'function') {
                window.successSnackbar('{{ __('messages.link_copied') }}');
            } else {
                console.error('window.successSnackbar is not defined');
            }
        }
    </script>
@endpush
