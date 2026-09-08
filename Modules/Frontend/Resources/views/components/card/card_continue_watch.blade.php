<div class="continue-watch-card">

    @php
        $duration =
            $value['watched_duration'] ??
            ($value['entertainment_type'] == 'video' && isset($value->video['duration'])
                ? $value->video['watched_duration']
                : (isset($value->entertainment['watched_duration'])
                    ? $value->entertainment['watched_duration']
                    : '00:00'));
        $episode_name = null;
        $episode_poster_url = null;

        $episode_slug = null;
        $tvshow_slug = $value['slug'] ?? ($value['entertainment']['slug'] ?? null);

        if ($value['entertainment_type'] == 'tvshow' && isset($value['episode_id']) && $value['episode_id']) {
            $episode = \Modules\Episode\Models\Episode::where('id', $value['episode_id'])->first();
            if ($episode) {
                $episode_name = $episode->name;
                $episode_poster_url = $episode->poster_url;
                $episode_slug = $episode->slug;
            }
        }

        $name = $episode_name
            ? $episode_name
            : $value['name'] ??
                ($value['entertainment_type'] == 'video' && isset($value->video['name'])
                    ? $value->video['name']
                    : (isset($value->entertainment['name'])
                        ? $value->entertainment['name']
                        : 'Unknown'));

        $poster_image = $episode_poster_url
            ? setBaseUrlWithFileName($episode_poster_url, 'image', 'episode')
            : $value['poster_image'] ??
                ($value['entertainment_type'] == 'video' && isset($value->video['poster_url'])
                    ? setBaseUrlWithFileName($value->video['poster_url'], 'image', $value['entertainment_type'])
                    : (isset($value->entertainment['poster_url'])
                        ? setBaseUrlWithFileName(
                            $value->entertainment['poster_url'],
                            'image',
                            $value['entertainment_type'],
                        )
                        : 'default-poster.jpg'));

        if (!function_exists('convertSeconds')) {
            function convertSeconds($time)
            {
                $parts = array_reverse(array_map('intval', explode(':', $time)));
                $seconds = 0;
                $multipliers = [1, 60, 3600];
                foreach ($parts as $index => $part) {
                    if (isset($multipliers[$index])) {
                        $seconds += $part * $multipliers[$index];
                    }
                }
                return $seconds;
            }
        }
        $total_watched_time = $value['total_duration'] ?? $value['total_watched_time'];
        $totalDurationInSeconds = convertSeconds($total_watched_time);
        $watchedTime = $value['watched_duration'] ?? '00:00:00';
        $watchedTimeInSeconds = convertSeconds($watchedTime);

        $progressPercentage = $totalDurationInSeconds > 0 ? ($watchedTimeInSeconds / $totalDurationInSeconds) * 100 : 0;

    @endphp
    <div class="continue-watch-card-image position-relative">

        @if ($value['entertainment_type'] == 'episode' || $value['entertainment_type'] == 'tvshow')
            @php
                $episodeLink = $episode_slug ? route('episode-details', ['id' => $episode_slug, 'continue_watch' => true]) : null;
                $tvshowLink = $tvshow_slug ? route('tvshow-details', ['id' => $tvshow_slug]) : 'javascript:void(0)';
                $detailLink = $episodeLink ?? $tvshowLink;
                $posterType = $episode_slug ? 'episode' : 'tvshow';
            @endphp

            <a href="{{ $detailLink }}" class="d-block image-link">
                <img src="{{ setBaseUrlWithFileName($poster_image, 'image', $posterType) }}" alt="movie-card"
                    class="img-fluid object-cover w-100 continue-watch-image">
            </a>
        @endif

        @if ($value['entertainment_type'] == 'movie')
            <a href="{{ route('movie-details', ['id' => $value['slug'], 'continue_watch' => true]) }}"
                class="d-block image-link">
                <img src="{{ setBaseUrlWithFileName($poster_image, 'image', 'movie') }}" alt="movie-card"
                    class="img-fluid object-cover w-100 continue-watch-image">
            </a>
        @endif

        @if ($value['entertainment_type'] == 'video')
            <a href="{{ route('video-details', ['id' => $value['slug'], 'continue_watch' => true]) }}" class="d-block image-link">
                <img src="{{ setBaseUrlWithFileName($poster_image, 'image', 'video') }}" alt="movie-card"
                    class="img-fluid object-cover w-100 continue-watch-image">
            </a>
        @endif

        <button class="continue_remove_btn remove_btn btn btn-primary" data-id="{{ $value['id'] ?? $value->id }}" data-name="{{ $name }}">
            <i class="ph ph-x"></i>
        </button>
        <div class="progress" role="progressbar" aria-label="Progress bar" aria-valuenow="{{ $progressPercentage }}"
            aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: {{ $progressPercentage }}%"></div>
        </div>
    </div>
    <div class="continue-watch-card-content">

        @if ($value['entertainment_type'] == 'episode' || $value['entertainment_type'] == 'tvshow')
            <a href="{{ $detailLink ?? 'javascript:void(0)' }}" class="title-wrapper">
                <h5 class="mb-1 font-size-18 title line-count-1">{{ $name }}</h5>
            </a>
        @endif

        @if ($value['entertainment_type'] == 'movie')
            <a href="{{ route('movie-details', ['id' => $value['slug'], 'continue_watch' => true]) }}"
                class="title-wrapper">
                <h5 class="mb-1 font-size-18 title line-count-1">{{ $name }}</h5>
            </a>
        @endif

        @if ($value['entertainment_type'] == 'video')
            <a href="{{ route('video-details', ['id' => $value['slug'], 'continue_watch' => true]) }}" class="title-wrapper">
                <h5 class="mb-1 font-size-18 title line-count-1">{{ $name }}</h5>
            </a>
        @endif



        <span class="font-size-14 fw-semibold">{{ $duration }}</span>
    </div>
</div>
