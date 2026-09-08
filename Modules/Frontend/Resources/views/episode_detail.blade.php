@extends('frontend::layouts.master', ['entertainment' => $entertainment])

@section('title')
    {{ $data['data']['name'] ?? '' }}
@endsection
@section('content')
    <div id="thumbnail-section">


        @php
            $data = $data['data'];
        @endphp


        @if ($continue_watch === true)
            @include('frontend::components.section.thumbnail', [
                'data' => $data['video_url_input'],
                'type' => $data['video_upload_type'],
                'thumbnail_image' => $data['poster_image'],
                'subtitle_info' => $data['subtitle_info'],
                'dataAccess' => $data['access'],
                'plan_id' => $data['plan_id'] ?? null,
                'continue_watch' => true,
                'watched_time' => $data['watched_time'],
                'content_type' => 'tvshow',
                'content_id' => $data['id'],
                'video_type' => $data['video_upload_type'],
                'content_video_type' => 'video',
            ])
        @else
            @include('frontend::components.section.thumbnail', [
                'data' => $data['trailer_url'],
                'type' => $data['trailer_url_type'],
                'thumbnail_image' => $data['poster_image'],
                'subtitle_info' => $data['subtitle_info'],
                'dataAccess' => $data['access'],
                'plan_id' => $data['plan_id'] ?? null,
                'continue_watch' => false,
                'content_type' => 'tvshow',
                'content_id' => $data['id'],
                'video_type' => $data['video_upload_type'],
                'content_video_type' => 'trailer',
            ])
        @endif
    </div>

    <!-- @if($data['access'] == 'pay-per-view' && !empty($data['description']))
        <div class="container-fluid">
            <div class="bg-dark text-white p-3 my-3 rounded">
                {!! $data['description'] !!}
            </div>
        </div>
    @endif -->

    <div id="detail-section">
        @include('frontend::components.section.episode_data', [
            'data' => $data,
            'subtitle_info' => $data['subtitle_info'],
        ])
    </div>

    <div class="short-menu mb-5">
        <div class="container-fluid padding-right-0">
            <div class="py-4 movie-detail-menu">
                <div class="d-flex align-items-center gap-2">
                    <div class="left">
                        <i class="ph ph-caret-left align-middle"></i>
                    </div>
                    <div class="custom-nav-slider">
                        <ul class="list-inline m-0 p-0 d-flex align-items-center">
                            <li class="flex-shrink-0">
                                <a href="#seasons" class="link-body-emphasis">
                                    <span class="d-inline-flex align-items-center gap-2">
                                        <span><i class="ph ph-film-reel align-middle"></i></span>
                                        <span class="font-size-18">{{ __('frontend.episodes') }}</span>
                                    </span>
                                </a>
                            </li>

                            <li class="flex-shrink-0">
                                <a href="#more-like-this" class="link-body-emphasis">
                                    <span class="d-inline-flex align-items-center gap-2">
                                        <span><i class="ph ph-dots-three-circle align-middle"></i></span>
                                        <span class="font-size-18">{{ __('frontend.more_like_this') }}</span>
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="right">
                        <i class="ph ph-caret-right align-middle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div id="seasons">
        @include('frontend::components.section.episodes', [
            'data' => $data['tvShowLinks'],
            'selectedSeasonId' => $data['season_id'] ?? null,
        ])
    </div>

    <div class="container-fluid padding-right-0">
        <div class="overflow-hidden">
            @include('frontend::components.section.custom_ad_banner', [
                'placement' => 'tvshow_detail',
                'content_id' => $data['id'] ?? '',
                'content_type' => 'tvshow',
                'category_id' => $data['category_id'] ?? '',
            ])
            @if ($data['more_items'] != null && count($data['more_items']) > 0)
                <div id="more-like-this">
                    @include('frontend::components.section.entertainment', [
                        'data' => $data['more_items']->toArray(request()),
                        'title' => __('frontend.more_like_this'),
                        'type' => 'tvshow',
                        'slug' => '',
                    ])
                </div>
            @endif
        </div>
    </div>


    <div class="modal fade" id="DeviceSupport" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content position-relative">
                <div class="modal-body user-login-card m-0 p-4 position-relative">
                    <button type="button" class="btn btn-primary custom-close-btn rounded-2" data-bs-dismiss="modal">
                        <i class="ph ph-x text-white fw-bold align-middle"></i>
                    </button>

                    <div class="modal-body">
                        {{ __('frontend.device_not_support') }}
                    </div>

                    <div class="d-flex align-items-center justify-content-center">
                        <a href="{{ Auth::check() ? route('subscriptionPlan') : route('login') }}"
                            class="btn btn-primary mt-5">{{ __('frontend.upgrade') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="PurchaseEpisode" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content position-relative">
                <div class="modal-body user-login-card m-0 p-4 position-relative">
                    <button type="button" class="btn btn-primary custom-close-btn rounded-2" data-bs-dismiss="modal">
                        <i class="ph ph-x text-white fw-bold align-middle"></i>
                    </button>

                    <div class="modal-body">
                        {{ __('frontend.purchase_episode') }}
                    </div>

                    <div class="d-flex align-items-center justify-content-center">
                        <a href="{{ Auth::check() ? route('subscriptionPlan') : route('login') }}"
                            class="btn btn-primary mt-5">{{ __('frontend.upgrade') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function updateEpisodeNameDisplay(episodeName, episodeId) {
                const episodeNameDisplay = document.getElementById('episodeNameDisplay');
                const currentEpisodeName = document.getElementById('currentEpisodeName');
                const currentEpisodeIdEl = document.getElementById('currentEpisodeId');
                const seasonCards = document.querySelectorAll('.season-card');
                if (episodeNameDisplay && currentEpisodeName) {
                    if (episodeName) {
                        currentEpisodeName.textContent = episodeName;
                        if (currentEpisodeIdEl && episodeId != null) {
                            currentEpisodeIdEl.textContent = String(episodeId);
                        }
                        episodeNameDisplay.style.display = 'block';
                        setTimeout(() => {
                            episodeNameDisplay.classList.add('show');
                        }, 10);
                        // Highlight by episodeId if provided
                        if (episodeId != null) {
                            const targetId = String(episodeId);
                            seasonCards.forEach(function(card) {
                                if (String(card.getAttribute('episode-id')) === targetId) {
                                    card.classList.add('is-playing');
                                } else {
                                    card.classList.remove('is-playing');
                                }
                            });
                        }
                    } else {
                        episodeNameDisplay.classList.remove('show');
                        setTimeout(() => {
                            episodeNameDisplay.style.display = 'none';
                        }, 300);
                        seasonCards.forEach(function(card) {
                            card.classList.remove('is-playing');
                        });
                    }
                }
            }
            // Function to hide episode name display
            function hideEpisodeNameDisplay() {
                updateEpisodeNameDisplay(null, null);
            }
            // Make functions globally available
            window.updateEpisodeNameDisplay = updateEpisodeNameDisplay;
            window.hideEpisodeNameDisplay = hideEpisodeNameDisplay;
            document.addEventListener('episodeChanged', function(e) {
                const detail = e.detail || {};
                if (detail.episodeName || detail.episodeId) {
                    updateEpisodeNameDisplay(detail.episodeName || null, detail.episodeId || null);
                }
            });
            hideEpisodeNameDisplay();

            function highlightPlayingEpisode(episodeId) {
                const seasonCards = document.querySelectorAll('.season-card');
                if (episodeId != null) {
                    const targetId = String(episodeId);
                    seasonCards.forEach(function(card) {
                        if (String(card.getAttribute('episode-id')) === targetId) {
                            card.classList.add('is-playing');
                        } else {
                            card.classList.remove('is-playing');
                        }
                    });
                } else {
                    seasonCards.forEach(function(card) {
                        card.classList.remove('is-playing');
                    });
                }
            }
            function clearEpisodeHighlight() {
                highlightPlayingEpisode(null);
            }
            window.highlightPlayingEpisode = highlightPlayingEpisode;
            window.clearEpisodeHighlight = clearEpisodeHighlight;
            document.addEventListener('episodeChanged', function(e) {
                const detail = e.detail || {};
                if (detail.episodeId) {
                    highlightPlayingEpisode(detail.episodeId);
                } else {
                    clearEpisodeHighlight();
                }
            });
            document.addEventListener('videoPlaying', function(e) {
                const detail = e.detail || {};
                if (detail.episodeId) {
                    highlightPlayingEpisode(detail.episodeId);
                }
            });
            document.addEventListener('videoStopped', function(e) {
                clearEpisodeHighlight();
            });
            const episodeDetailUrlTemplate = `{{ route('episode-details', ['id' => '__SLUG__']) }}`;

            document.addEventListener('click', function(e) {
                const seasonWatchBtn = e.target.closest('.season-watch-btn');
                if (seasonWatchBtn) {
                    const episodeId = seasonWatchBtn.getAttribute('data-episode-id');
                    const episodeSlug = seasonWatchBtn.getAttribute('data-episode-slug');
                    if (episodeId) {
                        highlightPlayingEpisode(episodeId);
                    }
                    if (episodeSlug) {
                        const targetUrl = episodeDetailUrlTemplate.replace('__SLUG__', episodeSlug);
                        window.location.href = targetUrl;
                        return;
                    }
                }
                const watchNowButton = e.target.closest('#watchNowButton');
                if (watchNowButton) {
                    const episodeId = watchNowButton.getAttribute('data-episode-id');
                    const episodeSlug = watchNowButton.getAttribute('data-episode-slug');
                    if (episodeId) {
                        highlightPlayingEpisode(episodeId);
                    }
                    if (episodeSlug) {
                        const targetUrl = episodeDetailUrlTemplate.replace('__SLUG__', episodeSlug);
                        window.location.href = targetUrl;
                    }
                }
            });
            const currentEpisodeId = {{ $data['id'] ?? 'null' }};
            if (currentEpisodeId) {
                setTimeout(function() {
                    highlightPlayingEpisode(currentEpisodeId);
                }, 100);
            }

            document.getElementById('copyLink')?.addEventListener('click', function(e) {
                e.preventDefault();
                navigator.clipboard?.writeText(this.getAttribute('data-link')).then(() => {
                    document.getElementById('copyFeedback').style.display = 'inline';
                    setTimeout(() => document.getElementById('copyFeedback').style.display = 'none', 2000);
                });
            });
        });
    </script>
@endsection
