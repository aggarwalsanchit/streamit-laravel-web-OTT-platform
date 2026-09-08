@php
    $episodeHoverData = [
        'id' => $data['id'],
        'name' => $data['name'],
        'slug' => $data['slug'],
        'poster_image' => $data['poster_image'],
        'duration' => $data['duration'] ?? null,
        'description' => strip_tags($data['description'] ?? ''),
        'type' => 'episode',
        'access' => $data['access'] ?? null,
        'is_pay_per_view' => ($data['access'] ?? '') == 'pay-per-view',
        'is_purchased' => \Modules\Entertainment\Models\Entertainment::isPurchased($data['id'], 'episode'),
        'show_premium_badge' => $data['show_premium_badge'] ?? false,
        'imdb_rating' => $data['imdb_rating'] ?? null,
        'release_date' => $data['release_date'] ?? null,
        'episode_number' => $data['episode_number'] ?? null,
        'entertainment_id' => $data['entertainment_id'] ?? null,
    ];

    $qualityOptions = [];
    foreach ($data['video_links'] as $link) {
        $qualityOptions[$link->quality] = $link->url;
    }

    $type = $data['video_upload_type'];
    $video_url_input = $data['video_url_input'];
    if ($data['video_upload_type'] == 'Local' && !empty($data['bunny_video_url'] && config('filesystems.active') == 'bunny')) {
        $type = 'HLS';
        $video_url_input = Crypt::encryptString($data['bunny_video_url']);
    } else if ($data['video_upload_type'] == 'Local') {
        $video_url_input = $data['video_url_input'];
    } else {
        $video_url_input = Crypt::encryptString($data['video_url_input']);
    }

    $subtitleInfoJson = $data['subtitle_info']
        ? json_encode($data['subtitle_info']->toArray(request()))
        : json_encode([]);

    $isWatchButton = $data['access'] != 'pay-per-view' ||
        \Modules\Entertainment\Models\Entertainment::isPurchased($data['id'], 'episode');
@endphp
<div class="season-card hover-card-container"
     id="episode-card-{{ $data['id'] }}"
     episode-id="{{ $data['id'] }}"
     data-episode-slug="{{ $data['slug'] ?? '' }}"
     data-movie-id="{{ $data['id'] }}"
     data-movie-data="{{ json_encode($episodeHoverData) }}"
     onmouseenter="openHoverModal(this)"
     onmouseleave="closeHoverModal(this)">
    <div class="season-image flex-shrink-0">
        <a href="{{ route('episode-details', ['id' => $data['slug']]) }}"><img src="{{ $data['poster_image'] }}"
                alt="movie image" class="object-fit-cover rounded"></a>
            @if ($data['access'] == 'pay-per-view')
                <span class="product-rent">
                    <i class="ph ph-film-reel"></i>
                    {{ \Modules\Entertainment\Models\Entertainment::isPurchased($data['id'], 'episode') ? __('messages.rented') : __('messages.rent') }}
                </span>
            @elseif (isset($data['show_premium_badge']) && $data['show_premium_badge'])
                <button type="button" class="product-premium border-0" data-bs-toggle="tooltip" data-bs-placement="top"
                    data-bs-title="{{ __('messages.lbl_premium') }}"><i class="ph ph-crown-simple"></i></button>
            @endif
            <button class="season-watch-btn {{ $isWatchButton ? '' : 'd-none' }}" id="seasonWatchBtn_{{ $data['id'] }}"
                data-entertainment-id="{{ $data['entertainment_id'] }}" data-entertainment-type="tvshow"
                data-type="{{ $type }}"
                data-video-url="{{ $video_url_input }}" data-movie-access="{{ $data['access'] }}"
                data-purchase-type="{{ $data['purchase_type'] }}"
                data-plan-id="{{ $data['plan_id'] }}" data-user-id="{{ auth()->id() }}"
                data-profile-id="{{ getCurrentProfile(auth()->id(), request()) }}" data-episode-id="{{ $data['id'] }}"
                data-episode-slug="{{ $data['slug'] ?? '' }}"
                data-first-episode-id="{{ $index + 1 }}" data-quality-options="{{ json_encode($qualityOptions) }}"
                data-subtitle-info="{{ $subtitleInfoJson }}" data-contentid="{{ $data['id'] }}"
                data-contenttype="tvshow" content-video-type="video" data-episode-name="{{ $data['name'] }}"
                data-start-time="{{ $data['intro_starts_at'] }}" data-end-time="{{ $data['intro_ends_at'] }}"
                data-poster-url="{{ $data['poster_image'] }}">
                <span class="d-flex align-items-center justify-content-center gap-2">
                    <span><i class="ph-fill ph-play"></i></span>
                    {{ __('frontend.watch_now') }}
                </span>
            </button>
    </div>
    <div class="season-content flex-grow-1">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
            <span class="episode-date fe-semibold font-size-14 d-block">{{ $data['release_date'] ? formatDate($data['release_date']) : '-' }}</span>
        </div>

        <div class="d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><a href="{{ route('episode-details', ['id' => $data['slug']]) }}">{{ $data['name'] }}</a></h5>
        </div>

        <p class="mt-2 mb-3 font-size-14 js-episode-desc">
            <span class="js-desc-text">{!! Str::limit(strip_tags($data['description']), 150) !!}</span>
            @if(strlen(strip_tags($data['description'])) > 150)
                 <a href="javascript:void(0)" class="text-primary p-0 align-baseline js-episode-toggle">{{ __('messages.read_more') }}</a>
            @endif
        </p>

        <script>
        (function(){
            const container = document.currentScript.previousElementSibling;
            const toggle = container?.querySelector('.js-episode-toggle');
            const desc = container?.querySelector('.js-desc-text');
            if(!toggle || !desc) return;

            const fullText = `{!! addslashes($data['description']) !!}`;
            const shortText = `{!! addslashes(Str::limit(strip_tags($data['description']), 150)) !!}`;
            let expanded = false;

            toggle.addEventListener('click', function(e){
                e.preventDefault();
                expanded = !expanded;
                desc.innerHTML = expanded ? fullText : shortText;
                toggle.textContent = expanded ? "{{ __('messages.read_less') ?? 'Read Less' }}" : "{{ __('messages.read_more') ?? 'Read More' }}";
            });
        })();
        </script>
        <div class="episode-duration">
            {{ $data['duration'] ? formatDuration($data['duration']) : '--' }}
        </div>
    </div>
</div>
