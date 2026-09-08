@php
    $entertainmentId = $data['id'] ?? null;
    $entertainmentType = $data['type'] ?? null;
    $inRemindList = auth()->check() ? $data['is_remind'] ?? 0 : 0;
    $inWatchlist = $data['is_watch_list'] ?? 0;
    $isLiked = auth()->check() ? $data['is_likes'] ?? 0 : 0;
@endphp

{{-- Custom CSS for like button --}}
<style>
    .like-btn {
        height: 40px;
        width: 40px;
        line-height: 40px;
        border-radius: 50%;
        padding: 0;
        font-size: 1rem;
    }
</style>

<div class="col">
    <div class="comingsoon-card">
        <div class="comingsoon-image">
            <img src="{{ $data['poster_image'] }}" alt="comingsoon-image" class="img-fluid w-100 h-100 object-cover">
            <button class="remind-btn btn {{ $inRemindList ? 'btn-primary' : 'btn-dark' }} p-2 js-remind-btn"
                data-entertainment-id="{{ $entertainmentId }}" data-in-remindlist="{{ $inRemindList }}"
                data-bs-toggle="tooltip"
                data-bs-title="{{ $inRemindList ? __('messages.remove_reminder') : __('messages.add_reminder') }}"
                data-bs-placement="top">
                <span class="d-flex align-items-center justify-content-center gap-2">
                    <i
                        class="ph {{ $inRemindList ? 'ph-fill ph-bell-simple-ringing' : 'ph ph-bell-simple-ringing' }}"></i>
                </span>
            </button>
            <div class="coming-soon-badge">{{ __('frontend.coming_soon') }}</div>
            @if (!empty($data['remaining_release_days']))
                @if ($data['remaining_release_days'] > 0)
                    <div class="comingsoon-timer">
                        {{ abs($data['remaining_release_days']) }} {{ __('messages.days_to_go') }}
                    </div>
                @endif
            @endif
        </div>

        <div class="comingsoon-detail">
            <div class="comingsoon-info">
                <ul class="list-inline mb-0 mx-0 p-0 d-flex align-items-center flex-wrap gap-3">
                    <li>
                        <span href="#"
                            class="release-date text-white">{{ formatDate($data['release_date']) }}</span>
                    </li>
                    @if ($data['is_restricted'] == 1)
                        <li>
                            <span class="d-inline-block">
                                <span class="py-1 px-2 font-size-10 text-white bg-dark rounded fw-bold align-middle">
                                    {{ __('frontend.age_restriction') }}
                                </span>
                            </span>
                        </li>
                    @endif
                </ul>
                <h5 class="mt-3 mb-0 line-count-1">{{ $data['name'] }}</h5>


                @if (!empty($data['genres']))
                    <ul class="list-inline mt-3 mb-0 p-0 d-flex align-items-center flex-wrap gap-2 movie-tag-list">
                        @foreach ($data['genres'] as $gener)
                            @php
                                $genreName = is_array($gener) ? $gener['name'] ?? null : $gener->name ?? null;
                            @endphp
                            @if (!empty($genreName))
                                <li>
                                    <a class="tag">{{ $genreName }}</a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif



                <p class="mt-3 mb-0 line-count-2 font-size-14">
                    {!! $data['description'] !!}
                </p>
                <ul class="list-inline mt-2 mb-0 mx-0 p-0 d-flex align-items-center flex-wrap gap-3">
                    @if (!empty($data['season_name']))
                        <li>
                            <span class="fw-medium">{{ $data['season_name'] }}</span>
                        </li>
                    @endif

                    @if ($entertainmentType !== 'video')
                        <li>
                            <span class="d-flex align-items-center gap-2">
                                <span><i class="ph ph-translate lh-base"></i></span>
                                <span class="fw-medium">{{ ucfirst($data['language']) }}</span>
                            </span>
                        </li>
                    @endif
                </ul>
                <div class="d-flex align-items-center gap-3 mt-3 flex-wrap">
                    <button class="action-btn like-btn btn {{ $isLiked ? 'btn-primary' : 'btn-dark' }} js-like-btn"
                        data-entertainment-id="{{ $entertainmentId }}"
                        data-entertainment-type="{{ $entertainmentType }}" data-is-liked="{{ $isLiked }}"
                        data-bs-toggle="tooltip"
                        data-bs-title="{{ $isLiked ? __('messages.lbl_unlike') : __('messages.lbl_like') }}"
                        data-bs-placement="top">
                        <i class="ph {{ $isLiked ? 'ph-fill ph-heart' : 'ph ph-heart' }}"></i>
                    </button>
                    <button
                        class="action-btn watch-list-btn btn {{ $inWatchlist ? 'btn-primary' : 'btn-dark' }} js-watchlist-btn"
                        data-entertainment-id="{{ $entertainmentId }}" data-in-watchlist="{{ $inWatchlist }}"
                        data-entertainment-type="{{ $entertainmentType }}" data-bs-toggle="tooltip"
                        data-bs-title="{{ $inWatchlist ? __('messages.remove_watchlist') : __('messages.add_watchlist') }}"
                        data-bs-placement="top">
                        <i class="ph {{ $inWatchlist ? 'ph-check' : 'ph-plus' }}"></i>
                    </button>
                    <a href="{{ route('comming-soon-details', $data['slug']) }}"
                        class="btn btn-primary">{{ __('messages.watch_trailer') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
