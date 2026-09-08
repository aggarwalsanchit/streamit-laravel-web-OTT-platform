<div id="season-card-wrapper" class="section-spacing-bottom px-0">
    <div class="container-fluid">
        <div class="seasons-tabs-wrapper position-relative">
            <div class="season-tabs-inner">
                <div class="left">
                    <i class="ph ph-caret-left"></i>
                </div>
                <div class="season-tab-container custom-nav-slider">
                    <ul class="nav nav-tabs season-tab" id="season-tab" role="tablist">
                        @php
                            $seasonsWithEpisodes = collect($data)->filter(function($item) {
                                return isset($item['total_episodes']) && $item['total_episodes'] > 0;
                            })->values();
                            $activeSeasonId = isset($selectedSeasonId) && $selectedSeasonId
                                ? $selectedSeasonId
                                : ($seasonsWithEpisodes->first()['season_id'] ?? null);
                        @endphp
                        @foreach ($seasonsWithEpisodes as $index => $item)
                            @php
                                $originalIndex = collect($data)->search(function($season) use ($item) {
                                    return $season['season_id'] == $item['season_id'];
                                });
                            @endphp
                            <li class="nav-item" role="presentation">
                                <button
                                    class="nav-link {{ isset($activeSeasonId) && $item['season_id'] == $activeSeasonId ? 'active' : '' }}"
                                    id="season-{{ (int) $originalIndex + 1 }}" data-bs-toggle="tab"
                                    data-bs-target="#season-{{ (int) $originalIndex + 1 }}-pane" type="button" role="tab"
                                    aria-controls="season-{{ (int) $originalIndex + 1 }}-pane"
                                    aria-selected="{{ isset($activeSeasonId) && $item['season_id'] == $activeSeasonId ? 'true' : 'false' }}">
                                    {{ $item['name'] }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="right">
                    <i class="ph ph-caret-right"></i>
                </div>
            </div>
            <div class="tab-content" id="season-tab-content">
                @php
                    $seasonsWithEpisodes = collect($data)->filter(function($item) {
                        return isset($item['total_episodes']) && $item['total_episodes'] > 0;
                    })->values();
                    $activeSeasonId = isset($activeSeasonId)
                        ? $activeSeasonId
                        : ($seasonsWithEpisodes->first()['season_id'] ?? null);
                @endphp
                @foreach ($data as $index => $value)
                    @if(isset($value['total_episodes']) && $value['total_episodes'] > 0)
                    <div class="tab-pane fade {{ isset($activeSeasonId) && $value['season_id'] == $activeSeasonId ? 'show active' : '' }}"
                        id="season-{{ (int) $index + 1 }}-pane" role="tabpanel"
                        aria-labelledby="season-{{ (int) $index + 1 }}" tabindex="0">
                        <div id="episode-list-{{ $value['season_id'] }}" class="row gy-5 row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 episode-list col">
                            @foreach ($value['episodes']->toArray(request()) as $epIndex => $episode)
                                <div class="col">
                                    @include('frontend::components.card.card_episode', [
                                        'data' => $episode,
                                        'index' => $epIndex,
                                        'subtitle_info' => '',
                                    ])
                                </div>
                            @endforeach
                        </div>
                        @if(isset($value['total_episodes']) && $value['total_episodes'] > 0 && $value['total_episodes'] > 8)
                            <div class="viewmore-button-wrapper">
                                <button id="view-more-btn-{{ $value['season_id'] }}"
                                        data-page="12"
                                        data-season-id="{{ $value['season_id'] }}"
                                        class="btn btn-dark view-more-btn">{{__('frontend.view_more')}}</button>
                                <button id="view-less-btn-{{ $value['season_id'] }}"
                                        data-page="8"
                                        data-season-id="{{ $value['season_id'] }}"
                                        class="btn btn-secondary view-less-btn"
                                        style="display: none;">{{__('frontend.view_less')}}</button>
                            </div>
                        @endif
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const baseUrlMeta = document.querySelector('meta[name="baseUrl"]');
    if (!baseUrlMeta) return;
    const baseUrl = baseUrlMeta.getAttribute('content');
    const apiUrl = `${baseUrl}/api/episode-list`;

    // Smooth scroll helper function
    function smoothScrollTo(element, offset = 100) {
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
        const offsetPosition = elementPosition - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }


    function handleViewMoreClick(e) {
        const btn = e.currentTarget;
        const seasonId = btn.getAttribute('data-season-id');
        const perPage = parseInt(btn.getAttribute('data-page'), 10) || 8;
        const url = `${apiUrl}?per_page=${perPage}&season_id=${seasonId}&is_ajax=1`;

        fetch(url, { method: 'GET' })
            .then(res => res.json())
            .then(response => {
                if (response && response.status) {
                    const list = document.getElementById('episode-list-' + seasonId);
                    if (!list) return;
                    list.innerHTML = response.html;

                    const lessBtn = document.getElementById('view-less-btn-' + seasonId);
                    const listItems = list ? list.children.length : 0;

                    if (response.hasMore) {
                        const next = perPage + 4; // keep seasons' increment behavior
                        btn.setAttribute('data-page', String(next));
                        if (lessBtn) lessBtn.style.display = '';
                    } else {
                        btn.style.display = 'none';
                        if (lessBtn && listItems > 8) lessBtn.style.display = '';
                    }
                } else {
                    console.log('No more episodes to load.');
                }
            })
            .catch(err => console.log(err));
    }

    function handleViewLessClick(e) {
        const btn = e.currentTarget;
        const seasonId = btn.getAttribute('data-season-id');

        // Scroll to the top of the season tabs before collapsing
        const seasonTabContent = document.getElementById('season-tab-content');
        if (seasonTabContent) {
            smoothScrollTo(seasonTabContent, 100);
        }

        const url = `${apiUrl}?per_page=8&season_id=${seasonId}&is_ajax=1`;

        fetch(url, { method: 'GET' })
            .then(res => res.json())
            .then(response => {
                if (response && response.status) {
                    const list = document.getElementById('episode-list-' + seasonId);
                    if (!list) return;
                    list.innerHTML = response.html;
                    btn.style.display = 'none';
                    const moreBtn = document.getElementById('view-more-btn-' + seasonId);
                    if (moreBtn) {
                        moreBtn.style.display = '';
                        moreBtn.setAttribute('data-page', '8');
                    }
                } else {
                    console.log('Failed to load initial episodes.');
                }
            })
            .catch(err => console.log(err));
    }

    document.querySelectorAll('.view-more-btn').forEach(el => {
        el.addEventListener('click', handleViewMoreClick);
    });
    document.querySelectorAll('.view-less-btn').forEach(el => {
        el.addEventListener('click', handleViewLessClick);
    });
})();
</script>
