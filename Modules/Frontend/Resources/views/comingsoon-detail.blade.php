@extends('frontend::layouts.master', [
    'entertainment' => (object) array_filter([
        'meta_title' => $data['meta_title'] ?? null,
        'short_description' => $data['short_description'] ?? null,
        'meta_keywords' => $data['meta_keywords'] ?? null,
        'meta_description' => $data['meta_description'] ?? null,
        'seo_image' => $data['seo_image'] ?? null,
        'google_site_verification' => $data['google_site_verification'] ?? null,
        'canonical_url' => $data['canonical_url'] ?? null,
    ], function($v){ return !empty($v); })
])

@section('title')
    {{ $data['name'] }}
@endsection

@section('content')

    <div id="thumbnail-section">
        @include('frontend::components.section.thumbnail', [
            'data' => !empty($data['trailer_url']) ? $data['trailer_url'] : null,
            'type' => !empty($data['trailer_url']) ? ($data['trailer_url_type'] ?? null) : null,
            'thumbnail_image' => $data['thumbnail_image'],
            'subtitle_info' => '',
            'content_type' => 'comingsoon',
            'content_id' => $data['id'],
            'video_type' => $data['trailer_url_type'] ?? null,
            'content_video_type' => 'trailer',
            'is_trailer' => true,
        ])
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="movie-detail-content section-spacing">
                    <div class="d-flex align-items-center mb-3">
                        @if ($data['is_restricted'] == 1)
                            <span
                                class="movie-badge rounded fw-bold font-size-12 px-2 py-1 me-3">{{ __('frontend.age_restriction') }}</span>
                        @endif
                    </div>
                    <!-- Coming Soon Badge -->
                    <div class="coming-soon-notice my-2 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary-subtle text-white p-3 rounded">
                                <span class="fw-bold">
                                    <i class="ph ph-clock me-2"></i>
                                    {{ __('frontend.coming_soon') }} - {{ $data['formatted_release_date'] }}
                                </span>
                            </div>

                            @if (!empty($data['remaining_release_days']))
                                @if($data['remaining_release_days'] > 0)
                                    <div class="bg-dark text-white p-3 rounded">
                                        <span>
                                            {{ abs($data['remaining_release_days']) }} {{ __('messages.days_to_go') }}
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>
                        <div>
                            <x-remind-button :entertainment-id="$data['id']" :inremindlist="$data['is_remind'] ?? 0" />
                        </div>
                    </div>
                    <h4>{{ $data['name'] }}</h4>
                    @if (!empty($data['content_rating']))
                        <p class="font-size-14 mb-2">
                            <span class="fw-medium">{{ $data['content_rating'] }}</span>
                        </p>
                    @endif

                    <p class="font-size-14 js-episode-desc">
                        <span class="js-desc-text">{!! Str::limit(strip_tags($data['description']), 300) !!}</span>
                        @if(strlen(strip_tags($data['description'])) > 300)
                            <a href="javascript:void(0)" class="btn btn-link p-0 align-baseline js-episode-toggle">{{ __('messages.read_more') }}</a>
                        @endif
                    </p>

                    <script>
                    (function(){
                        var container = document.currentScript.previousElementSibling;
                        if(!container) return;
                        var toggle = container.querySelector('.js-episode-toggle');
                        var desc = container.querySelector('.js-desc-text');
                        if(!toggle || !desc) return;

                        var fullText = `{!! addslashes($data['description']) !!}`;
                        var shortText = `{!! addslashes(Str::limit(strip_tags($data['description']), 300)) !!}`;
                        var expanded = false;

                        toggle.addEventListener('click', function(e){
                            e.preventDefault();
                            if(!expanded){
                                desc.innerHTML = fullText;
                                toggle.textContent = ("{{ __('messages.read_less') ?? 'Read Less' }}").trim();
                            } else {
                                desc.innerHTML = shortText;
                                toggle.textContent = ("{{ __('messages.read_more') ?? 'Read More' }}").trim();
                            }
                            expanded = !expanded;
                        });
                    })();
                    </script>
                    <div class="d-none"><button id="watchNowButton"></button></div>
                    <ul class="list-inline my-4 mx-0 p-0 d-flex align-items-center flex-wrap gap-3 movie-metalist">
                        <li>
                            <span class="d-flex align-items-center gap-2">
                                <span><i class="ph ph-calendar"></i></span>
                                <span
                                    class="fw-medium">{{ $data['release_date'] }}</span>
                            </span>
                        </li>

                        @if (!empty($data['language']))
                        <li>
                            <span class="d-flex align-items-center gap-2">
                                <span><i class="ph ph-translate lh-base"></i></span>
                                <span class="fw-medium">{{ ucfirst($data['language']) }}</span>
                            </span>
                        </li>
                        @endif

                        <li>
                            <span class="d-flex align-items-center gap-2">
                                <span><i class="ph ph-clock lh-base"></i></span>
                                {{ $data['duration'] ? formatDuration($data['duration']) : '--' }}
                            </span>
                        </li>
                        <li>
                            @if ($data['imdb_rating'])
                                <span class="d-flex align-items-center gap-2">
                                    <span><i class="ph ph-star lh-base"></i></span>
                                    <span class="fw-medium">{{ $data['imdb_rating'] }} ({{ __('messages.lbl_IMDb') }})</span>
                                </span>
                            @endif
                        </li>
                    </ul>

                    @if (isset($data['genres']) && $data['genres'] && count($data['genres']) > 0)
                        <div class="mb-3">
                            <ul class="genres-list ps-0 m-0 d-flex flex-wrap align-items-center gap-2">
                                @foreach ($data['genres'] as $genreResource)
                                    <li class="position-relative fw-semibold d-flex align-items-center">
                                        {{ is_array($genreResource) ? ($genreResource['name'] ?? '--') : ($genreResource->name ?? '--') }}
                                        @if (!$loop->last)
                                            <span class="mx-1">•</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <ul class="actions-list list-inline mb-0 p-0 d-flex align-items-center flex-wrap gap-3">
                        <li>
                            <x-watchlist-button :entertainment-id="$data['id']" :in-watchlist="$data['is_watch_list'] ?? 0"
                                entertainmentType="{{ $data['type'] }}" customClass="watch-list-btn" />
                        </li>

                        <li class="position-relative share-button dropend dropdown">
                            <button type="button" class="action-btn btn btn-dark" data-bs-toggle="dropdown"
                                data-bs-share="tooltip" data-bs-auto-close="outside" title="{{ __('messages.lbl_share') }}"
                                aria-expanded="false">
                                <i class="ph ph-share-network"></i>
                            </button>
                            <div class="share-wrapper">
                                <div class="share-box dropdown-menu">
                                    <svg width="15" height="40" viewBox="0 0 15 40" class="share-shape" fill="none"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M14.8842 40C6.82983 37.2868 1 29.3582 1 20C1 10.6418 6.82983 2.71323 14.8842 0H0V40H14.8842Z"
                                            fill="currentColor"></path>
                                    </svg>
                                    <div class="d-flex align-items-center justify-content-center gap-3 px-3">
                                        <a href="https://www.facebook.com/sharer?u={{ urlencode(Request::url()) }}"
                                            target="_blank" rel="noopener noreferrer" class="share-ico"><i
                                                class="ph ph-facebook-logo"></i></a>
                                        <a href="https://twitter.com/intent/tweet?text={{ urlencode($data['name']) }}&url={{ urlencode(Request::url()) }}"
                                            target="_blank" rel="noopener noreferrer" class="share-ico"><i
                                                class="ph ph-x-logo"></i></a>
                                        <a href="javascript:void(0)" data-link="{{ Request::url() }}" class="share-ico iq-copy-link"
                                            id="copyLink" onclick="copyLink(this)"><i class="ph ph-link"></i></a>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li>
                            <x-like-button :entertainmentId="$data['id']" :isLiked="$data['is_likes'] ?? 0"
                                :type="$data['type']" />
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    @if($data['is_clips_enabled'])
            @include('frontend::components.section.clips_trailers', ['clips' => $data['clips'] ?? []])
    @endif

    @if(isset($data['casts']) && count($data['casts']) > 0)
    <div class="container-fluid padding-right-0">
        <div class="overflow-hidden">
            <div id="comingsoon-cast" class="half-spacing">
                @include('frontend::components.section.castcrew', [
                    'data' => is_array($data['casts']) ? $data['casts'] : $data['casts']->toArray(request()),
                    'title' => __('frontend.casts'),
                    'entertainment_id' => $data['id'],
                    'type' => 'actor',
                    'slug' => '',
                ])
            </div>
        </div>
    </div>
    @endif

    @if(isset($data['directors']) && count($data['directors']) > 0)
    <div class="container-fluid padding-right-0">
        <div class="overflow-hidden">
            <div id="comingsoon-directors" class="half-spacing">
                @include('frontend::components.section.castcrew', [
                    'data' => is_array($data['directors']) ? $data['directors'] : $data['directors']->toArray(request()),
                    'title' => __('frontend.directors'),
                    'entertainment_id' => $data['id'],
                    'type' => 'director',
                    'slug' => '',
                ])
            </div>
        </div>
    </div>
    @endif

    <script>
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


@endsection
