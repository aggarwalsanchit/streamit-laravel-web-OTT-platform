<div class="detail-page-info section-spacing">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="movie-detail-content">
                    <div class="d-flex align-items-center mb-3">
                        @if ($data['is_restricted'] == 1)
                            <span
                                class="movie-badge rounded fw-bold font-size-12 px-2 py-1 me-3">{{ __('frontend.age_restriction') }}</span>
                        @endif
                        <ul class="p-0 mb-0 list-inline d-flex flex-wrap align-items-center movie-tags">
                            @foreach ($data['genres'] as $gener)
                                <li class="position-relative fw-semibold">{{ $gener['name'] }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @if ($data['access'] == 'pay-per-view')
                        <div class="bg-dark text-white p-3 my-2 rounded d-flex flex-wrap justify-content-between align-items-center gap-3"
                            style="border-width: 2px;">
                            <div>
                                @php
                                    $rentalStartDuration = trans_choice('messages.lbl_day_text', $data['available_for'], ['count' => $data['available_for']]);
                                    $rentalWatchDuration = trans_choice('messages.lbl_day_text', $data['access_duration'], ['count' => $data['access_duration']]);
                                @endphp
                                @if ($data['purchase_type'] === 'rental')
                                    <span>
                                        {!! __('messages.rental_info', [
                                            'start_duration' => $rentalStartDuration,
                                            'watch_duration' => $rentalWatchDuration,
                                        ]) !!}
                                        <button class="btn btn-link p-0" data-bs-toggle="modal"
                                            data-bs-target="#rentalPurchaseModal">
                                            <i class="ph ph-info">i</i>
                                        </button>
                                    </span>
                                @else
                                    <span>
                                        {!! __('messages.purchase_info', [
                                            'start_duration' => $rentalStartDuration,
                                        ]) !!}
                                        <button class="btn btn-link p-0" data-bs-toggle="modal"
                                            data-bs-target="#onetimePurchaseModal">
                                            <i class="ph ph-info">i</i>
                                        </button>
                                    </span>
                                @endif
                            </div>
                            @if (!\Modules\Entertainment\Models\Entertainment::isPurchased($data['id'], 'episode'))
                            <div>
                                <div>
                                    @if ($data['purchase_type'] === 'rental')
                                        <a href="{{ route('pay-per-view.paymentform', ['id' => $data['id'], 'type' => 'episode']) }}"
                                            class="btn btn-success d-flex align-items-center">
                                            <i class="bi bi-lock-fill me-1"></i>
                                            @if ($data['discount'] > 0)
                                                <span class="me-2">
                                                    {!! __('messages.rent_button', ['price' => '<span dir="ltr">' . Currency::format($data['price'] - $data['price'] * ($data['discount'] / 100), 2) . '</span>']) !!}
                                                </span>
                                                <span class="text-decoration-line-through text-white-50">
                                                    <span dir="ltr">{{ Currency::format($data['price'], 2) }}</span>
                                                </span>
                                            @else
                                                {!! __('messages.rent_button', ['price' => '<span dir="ltr">' . Currency::format($data['price'], 2) . '</span>']) !!}
                                            @endif
                                        </a>
                                    @else
                                        <a href="{{ route('pay-per-view.paymentform', ['id' => $data['id'], 'type' => 'episode']) }}"
                                            class="btn btn-success d-flex align-items-center">
                                            <i class="bi bi-unlock-fill me-1"></i>
                                            @if ($data['discount'] > 0)
                                                <span class="me-2">
                                                    {!! __('messages.one_time_button', ['price' => '<span dir="ltr">' . Currency::format($data['price'] - $data['price'] * ($data['discount'] / 100), 2) . '</span>']) !!}
                                                </span>
                                                <span class="text-decoration-line-through text-white-50">
                                                    <span dir="ltr">{{ Currency::format($data['price'], 2) }}</span>
                                                </span>
                                            @else
                                                {!! __('messages.one_time_button', ['price' => '<span dir="ltr">' . Currency::format($data['price'], 2) . '</span>']) !!}
                                            @endif
                                        </a>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    @endif
                    @if ($data['tvshow_name'])
                        <h3>{{ $data['tvshow_name'] }}</h3>
                    @endif
                    <h4>{{ $data['name'] }}</h4>
                    @if ($data['content_rating'])
                        <p class="font-size-14">
                            <span class="fw-medium">{{ $data['content_rating'] }}</span>
                        </p>
                    @endif
                    <p class="font-size-14 js-episode-desc">
                        <span class="js-desc-text">{!! Str::limit(strip_tags($data['description']), 300) !!}</span>
                        @if(strlen(strip_tags($data['description'])) > 300)
                              <a href="javascript:void(0)" class="text-primary p-0 align-baseline js-episode-toggle">{{ __('messages.read_more') }}</a>
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
                    <ul class="list-inline my-4 mx-0 p-0 d-flex align-items-center flex-wrap gap-3 movie-metalist">

                        <li>
                            <span class="d-flex align-items-center gap-2">
                                <span><i class="ph ph-translate lh-base"></i></span>
                                <span class="fw-medium">{{ ucfirst($data['language']) }}</span>
                            </span>
                        </li>
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
                        <li>
                            @if ($data['content_rating'])
                                <span class="d-flex align-items-start gap-2">
                                    <span><i class="ph ph-star lh-base"></i></span>
                                    <span class="fw-medium">{{ $data['content_rating'] }}</span>
                                </span>
                            @endif
                        </li>
                    </ul>

                    @php

                        $qualityOptions = [];

                        if ($data['enable_quality'] == 1) {
                            $videoLinks = $data['video_links'];

                            foreach ($videoLinks as $link) {
                                $qualityOptions[$link->quality] = [
                                    'value' =>
                                        $link->type === 'Local'
                                            ? setBaseUrlWithFileName($link->url, 'video', 'episode')
                                            : Crypt::encryptString($link->url),
                                    'type' => $link->type, // Add the type here
                                ];
                            }
                        }

                        $type = $data['video_upload_type'];
                        $video_url = $data['video_url_input'];
                        if ($data['video_upload_type'] == 'Local' && !empty($data['bunny_video_url'] && config('filesystems.active') == 'bunny')) {
                            $type = 'HLS';
                            $video_url = Crypt::encryptString($data['bunny_video_url']);
                        } else {
                            $video_url = $data['video_url_input'];
                        }

                        $qualityOptionsJson = json_encode($qualityOptions);

                        $subtitleInfoJson = $data['subtitle_info']
                            ? json_encode($data['subtitle_info']->toArray(request()))
                            : json_encode([]);

                    @endphp
                    @if ($data['access'] == 'pay-per-view' && \Modules\Entertainment\Models\Entertainment::isPurchased($data['id'], 'episode') == false)
                        <div class="d-none"><button id="watchNowButton"></button></div>
                    @endif
                    <ul class="actions-list list-inline mb-0 p-0 d-flex align-items-center flex-wrap gap-3">
                        <li>
                            @if (
                                $data['access'] != 'pay-per-view' ||
                                    \Modules\Entertainment\Models\Entertainment::isPurchased($data['id'], 'episode'))
                                <div
                                    class="d-flex align-items-sm-center justify-content-start flex-wrap flex-column flex-sm-row gap-4 mt-5">
                                    <div class="play-button-wrapper">
                                        <button class="btn btn-primary" id="watchNowButton"
                                            data-entertainment-id="{{ $data['entertainment_id'] }}"
                                            data-entertainment-type="episode"
                                            data-type="{{ $type }}"
                                            data-video-url="{{ $video_url }}"
                                            data-movie-access="{{ $data['access'] }}"
                                            data-plan-id="{{ $data['plan_id'] }}" data-user-id="{{ auth()->id() }}"
                                            data-purchase-type="{{ $data['purchase_type'] }}"
                                            data-profile-id="{{ getCurrentProfile(auth()->id(), request()) }}"
                                            data-episode-id="{{ $data['id'] }}" data-first-episode-id="1"
                                            data-quality-options={{ $qualityOptionsJson }}
                                            data-subtitle-info="{{ $subtitleInfoJson }}",
                                            data-contentid="{{ $data['id'] }}",
                                            data-start-time="{{ $data['intro_starts_at'] }}",
                                            data-end-time="{{ $data['intro_ends_at'] }}", data-contenttype="tvshow",
                                            content-video-type="video">
                                            <span class="d-flex align-items-center justify-content-center gap-2">
                                                <span><i class="ph-fill ph-play"></i></span>
                                                <span>{{ __('frontend.watch_now') }}</span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                        </li>

                        <!--- Cast button -->
                        @php
                            $video_url = $data['video_url_input'];
                            $video_upload_type = $data['video_upload_type'];
                            $plan_type = getActionPlan('video-cast');
                        @endphp
                        @if (!empty($plan_type) && ($video_upload_type == 'Local' || $video_upload_type == 'URL'))
                            @php
                                $video_url11 =
                                    $video_upload_type == 'URL' ? Crypt::decryptString($video_url) : $video_url;
                            @endphp
                            <li>
                                <button class="action-btn btn btn-dark gap-4 mt-5" data-name="{{ $video_url11 }}"
                                    id="castme">
                                    <i class="ph ph-screencast"></i>
                                </button>
                            </li>
                        @endif
                        <!--- End cast button -->

                        @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- One-time Purchase Modal -->
@if ($data['access'] == 'pay-per-view')
<div class="modal fade" id="onetimePurchaseModal" tabindex="-1" aria-labelledby="onetimePurchaseModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width:500px;">
        <div class="modal-content section-bg text-white rounded shadow border-0 p-4">

            <!-- Header Info -->
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    @if (isset($data['is_restricted']) && $data['is_restricted'] == 1)
                        <span
                            class="badge bg-light text-dark fw-bold px-2 py-1 me-2">{{ __('messages.lbl_age_restriction') }}</span>
                    @endif
                    @if (isset($data['genres']) && count($data['genres']) > 0)
                        <span class="text-white-50 small">
                            @foreach ($data['genres'] as $key => $genre)
                                {{ is_array($genre) ? $genre['name'] : $genre->name }}@if (!$loop->last)
                                    &bull;
                                @endif
                            @endforeach
                        </span>
                    @endif
                </div>
                <button class="custom-close-btn btn btn-primary" data-bs-dismiss="modal">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <!-- Movie Title -->
            <h4 class="fw-bold mb-2">{{ $data['name'] }}</h4>

            <!-- Movie Metadata -->
            <ul class="list-inline mb-4 d-flex flex-wrap gap-4">
                {{-- <li class="d-flex align-items-center gap-1"><span>{{ \Carbon\Carbon::parse($data['release_date'])->format('Y') }}</span></li> --}}
                <li class="d-flex align-items-center gap-1"><i
                        class="ph ph-translate me-1"></i><span>{{ ucfirst($data['language']) }}</span></li>
                <li class="d-flex align-items-center gap-1"><i class="ph ph-clock me-1"></i><span>
                        {{ $data['duration'] ? formatDuration($data['duration']) : '--' }}</span></li>
                @if ($data['imdb_rating'])
                    <li class="d-flex align-items-center gap-1"><i
                            class="ph-fill ph-star text-warning"></i><span>{{ $data['imdb_rating'] }} ({{ __('messages.lbl_IMDb') }})</span></li>
                @endif
            </ul>

            <!-- Validity & Watch Time -->
            <div class="rounded p-3 p-lg-5 mb-4 bg-dark">
                    @php
                        $availableDays = is_numeric($data['available_for']) ? (int) $data['available_for'] : 0;
                        $accessDays = is_numeric($data['access_duration']) ? (int) $data['access_duration'] : 0;
                    @endphp
                    @if ($data['available_for'] > 0)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <p class="text-muted m-0 small">{{ __('messages.lbl_validity') }}</p>
                            <h6 class="fw-semibold m-0">
                                {{ trans_choice('messages.lbl_day_text', $availableDays, ['count' => $availableDays]) }}
                            </h6>
                        </div>
                    @endif
                    @if ($data['access_duration'] > 0)
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-4 pb-4 border-bottom">
                            <p class="text-muted m-0 small">{{ __('messages.lbl_watch_duration') }}</p>
                            <h6 class="fw-semibold m-0">
                                {{ trans_choice('messages.lbl_day_text', $accessDays, ['count' => $accessDays]) }}
                            </h6>
                        </div>
                    @endif
                </div>
                {{-- <hr class="font-size-14 text-body"> --}}
                <ul class="font-size-14 text-body">
                    <li>{!! __('messages.info_start_days', ['duration' => trans_choice('messages.lbl_day_text', $data['available_for'], ['count' => $data['available_for']])]) !!}</li>
                    <li>{{ __('messages.info_multiple_times') }}</li>
                    <li>{!! __('messages.info_non_refundable') !!}</li>
                    <li>{{ __('messages.info_not_premium') }}</li>
                    <li>{{ __('messages.info_supported_devices') }}</li>
                </ul>
                @if (!\Modules\Entertainment\Models\Entertainment::isPurchased($data['id'], 'episode'))
                <!-- Agreement Checkbox -->
                <div class="form-check mb-4 d-flex align-items-center gap-3 p-0">
                    <input class="form-check-input m-0" type="checkbox" checked id="agreeCheckbox">
                    <label class="form-check-label small text-white-50" for="rentalAgreeCheckbox">
                        {{ __('messages.lbl_agree_term') }}
                        <a href="{{ route('page.show', ['slug' => 'terms-conditions']) }}"
                            class="text-decoration-underline text-white">{{ __('messages.terms_use') }}</a>.
                    </label>
                </div>

                <!-- Rent Button -->
                <div class="text-center">
                    <a href="{{ route('pay-per-view.paymentform', ['id' => $data['id'], 'type' => 'episode']) }}"
                        id="onetimeSubmitButton"
                        class="btn btn-success fw-semibold d-inline-flex justify-content-center align-items-center gap-2">
                        <i class="ph ph-lock-key"></i>

                        @if ($data['discount'] > 0)
                            <span>{!! __('messages.btn_onetime_payment', [
                                'price' => '<span dir="ltr">' . Currency::format($data['price'] - $data['price'] * ($data['discount'] / 100), 2) . '</span>',
                            ]) !!}</span>
                            <span class="text-decoration-line-through small text-white-50 ms-2">
                                <span dir="ltr">{{ Currency::format($data['price'], 2) }}</span>
                            </span>
                        @else
                            <span>{!! __('messages.btn_onetime_payment', [
                                'price' => '<span dir="ltr">' . Currency::format($data['price'], 2) . '</span>',
                            ]) !!}</span>
                        @endif
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif


<!-- Rental Purchase Modal -->
@if ($data['access'] == 'pay-per-view')
<div class="modal fade" id="rentalPurchaseModal" tabindex="-1" aria-labelledby="rentalPurchaseModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width:500px;">
        <div class="modal-content section-bg text-white rounded shadow-lg border-0 p-4">

            <!-- Header Info -->
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    @if (isset($data['is_restricted']) && $data['is_restricted'] == 1)
                        <span
                            class="badge bg-light text-dark fw-bold px-2 py-1 me-2">{{ __('messages.lbl_age_restriction') }}</span>
                    @endif
                    @if (isset($data['genres']) && count($data['genres']) > 0)
                        <span class="text-white-50 small">
                            @foreach ($data['genres'] as $key => $genre)
                                {{ is_array($genre) ? $genre['name'] : $genre->name }}@if (!$loop->last)
                                    &bull;
                                @endif
                            @endforeach
                        </span>
                    @endif
                </div>
                <button class="custom-close-btn btn btn-primary" data-bs-dismiss="modal">
                    <i class="ph ph-x"></i>
                </button>
            </div>

            <!-- Movie Title -->
            <h4 class="fw-bold mb-2">{{ $data['name'] }}</h4>

            <!-- Movie Metadata -->
            <ul class="list-inline mb-4 d-flex flex-wrap gap-4">
                {{-- <li class="d-flex align-items-center gap-1"><span>{{ \Carbon\Carbon::parse($data['release_date'])->format('Y') }}</span></li> --}}
                <li class="d-flex align-items-center gap-1"><i
                        class="ph ph-translate me-1"></i><span>{{ ucfirst($data['language']) }}</span></li>
                <li class="d-flex align-items-center gap-1"><i class="ph ph-clock me-1"></i><span>
                        {{ $data['duration'] ? formatDuration($data['duration']) : '--' }}</span></li>
                @if ($data['imdb_rating'])
                    <li class="d-flex align-items-center gap-1"><i
                            class="ph-fill ph-star text-warning"></i><span>{{ $data['imdb_rating'] }} ({{ __('messages.lbl_IMDb') }})</span></li>
                @endif
            </ul>

            <!-- Validity & Duration -->
            <div class="rounded p-3 p-lg-5 mb-4 bg-dark">
                <div class="">
                    @php
                        $rentalAvailableDays = is_numeric($data['available_for']) ? (int) $data['available_for'] : 0;
                        $rentalAccessDays = is_numeric($data['access_duration']) ? (int) $data['access_duration'] : 0;
                    @endphp
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <p class="text-muted m-0 small">{{ __('messages.lbl_validity') }}</p>
                        <h6 class="fw-semibold m-0">
                            {{ trans_choice('messages.lbl_day_text', $rentalAvailableDays, ['count' => $rentalAvailableDays]) }}
                        </h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-4 pb-4 border-bottom">
                        <p class="text-muted m-0 small">{{ __('messages.lbl_watch_duration') }}</p>
                        <h6 class="fw-semibold m-0">
                            {{ trans_choice('messages.lbl_day_text', $rentalAccessDays, ['count' => $rentalAccessDays]) }}
                        </h6>
                    </div>
                </div>
                <ul class="font-size-14 text-body ">
                    <li>{!! __('messages.rental_info_start', ['duration' => trans_choice('messages.lbl_day_text', $data['available_for'], ['count' => $data['available_for']])]) !!}</li>
                    <li>{!! __('messages.rental_info_duration', ['duration' => trans_choice('messages.lbl_day_text', $data['access_duration'], ['count' => $data['access_duration']])]) !!}</li>
                    <li>{!! __('messages.info_non_refundable') !!}</li>
                    <li>{{ __('messages.info_not_premium') }}</li>
                    <li>{{ __('messages.info_supported_devices') }}</li>
                </ul>

                @if (!\Modules\Entertainment\Models\Entertainment::isPurchased($data['id'], 'episode'))
                <!-- Terms Checkbox -->
                <div class="form-check mb-4 d-flex align-items-center gap-3 p-0">
                    <input class="form-check-input m-0" type="checkbox" checked id="rentalAgreeCheckbox">
                    <label class="form-check-label small text-white-50" for="rentalAgreeCheckbox">
                        {{ __('messages.lbl_agree_term') }}
                        <a href="{{ route('page.show', ['slug' => 'terms-conditions']) }}"
                            class="text-decoration-underline text-white">{{ __('messages.terms_use') }}</a>.
                    </label>
                </div>

                <!-- Rent Button -->
                <div class="">
                    <a href="{{ route('pay-per-view.paymentform', ['id' => $data['id'], 'type' => 'episode']) }}"
                        id="rentalSubmitButton"
                        class="btn btn-success fw-semibold d-inline-flex justify-content-center align-items-center gap-2 w-100">
                        <i class="ph ph-film-reel"></i>

                        @if ($data['discount'] > 0)
                            <span>{!! __('messages.btn_rent_payment', [
                                'price' => '<span dir="ltr">' . Currency::format($data['price'] - $data['price'] * ($data['discount'] / 100), 2) . '</span>',
                            ]) !!}</span>
                            <span class="text-decoration-line-through small text-white-50 ms-2">
                                <span dir="ltr">{{ Currency::format($data['price'], 2) }}</span>
                            </span>
                        @else
                            <span>{!! __('messages.btn_rent_payment', [
                                'price' => '<span dir="ltr">' . Currency::format($data['price'], 2) . '</span>',
                            ]) !!}</span>
                        @endif
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const oneTimeCheckbox = document.getElementById('agreeCheckbox');
        const oneTimeButton = document.getElementById('onetimeSubmitButton');
        if (oneTimeCheckbox && oneTimeButton) {
            oneTimeCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    oneTimeButton.classList.remove('disabled-link');
                    oneTimeButton.style.pointerEvents = 'auto';
                    oneTimeButton.style.opacity = '1';
                } else {
                    oneTimeButton.classList.add('disabled-link');
                    oneTimeButton.style.pointerEvents = 'none';
                    oneTimeButton.style.opacity = '0.5';
                }
            });
        }

        const rentalCheckbox = document.getElementById('rentalAgreeCheckbox');
        const rentalButton = document.getElementById('rentalSubmitButton');
        if (rentalCheckbox && rentalButton) {
            rentalCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    rentalButton.classList.remove('disabled-link');
                    rentalButton.style.pointerEvents = 'auto';
                    rentalButton.style.opacity = '1';
                } else {
                    rentalButton.classList.add('disabled-link');
                    rentalButton.style.pointerEvents = 'none';
                    rentalButton.style.opacity = '0.5';
                }
            });
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const watchButton = document.getElementById('watchNowButton');

        if (!watchButton) return;

        watchButton.addEventListener('click', function() {
            const movieAccess = watchButton.dataset.movieAccess;
            const purchaseType = watchButton.dataset.purchaseType;

            const userId = watchButton.dataset.userId;
            const entertainmentId = watchButton.dataset.episodeId;

            if (movieAccess === 'pay-per-view' && purchaseType === 'onetime') {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('entertainment_id', entertainmentId);
                formData.append('entertainment_type', 'episode');
                formData.append('_token', '{{ csrf_token() }}'); // Blade variable

                fetch('{{ route('pay-per-view.start-date') }}', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        // console.log('Start date set:', data);
                    })
                    .catch(error => {
                        console.error('Failed to set start date:', error);
                        // alert('Something went wrong. Please try again.');
                    });
            }
        });
    });
</script>
