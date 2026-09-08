@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@php
    $subtitleIndexInit = $data->subtitles && count($data->subtitles) > 0 ? count($data->subtitles) : 1;
@endphp

@section('content')

    <x-back-button-component route="backend.episodes.index" />

    {{ html()->form('PUT', route('backend.episodes.update', $data->id))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->attribute('novalidate', 'novalidate')->class('requires-validation')->open() }}

    @csrf

    <ul class="nav nav-pills mb-3 movie-tab mt-5" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-movie-tab" data-bs-toggle="pill" data-bs-target="#pills-movie"
                type="button" role="tab" aria-controls="pills-movie"
                aria-selected="true">{{ __('messages.lbl_episode_details') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-basic-tab" data-bs-toggle="pill" data-bs-target="#pills-basic" type="button"
                role="tab" aria-controls="pills-basic" aria-selected="false">{{ __('movie.lbl_basic_info') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-quality-tab" data-bs-toggle="pill" data-bs-target="#pills-quality"
                type="button" role="tab" aria-controls="pills-quality"
                aria-selected="false">{{ __('movie.lbl_quality_info') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-Subtitle-tab" data-bs-toggle="pill" data-bs-target="#pills-Subtitle"
                type="button" role="tab" aria-controls="pills-Subtitle"
                aria-selected="false">{{ __('movie.lbl_subtitle_info') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-seo-tab" data-bs-toggle="pill" data-bs-target="#pills-seo" type="button"
                role="tab" aria-controls="pills-seo"
                aria-selected="false">{{ __('messages.lbl_seo_settings') }}</button>
        </li>
        <li class="nav-item {{ old('download_status', $data->download_status ?? 0) ? '' : 'd-none' }}"
            id="download-tab-wrapper" role="presentation">
            <button class="nav-link" id="pills-download-tab" data-bs-toggle="pill" data-bs-target="#pills-download"
                type="button" role="tab" aria-controls="pills-download"
                aria-selected="false">{{ __('movie.lbl_download_info') }}</button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        <div class="tab-pane fade show active" id="pills-movie" role="tabpanel" aria-labelledby="pills-movie-tab">
            <input type="hidden" name="id" value="{{ $data->id }}">
            <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                <h6>{{ __('messages.lbl_episode_details') }}</h6>
            </div>
            <p class="text-danger" id="error_message"></p>
            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-6 col-lg-3">
                            <input type="hidden" name="tmdb_id" id="tmdb_id" value="{{ $tmdb_id }}">
                            <div class="position-relative">
                                {{ html()->label(__('movie.lbl_poster'), 'poster')->class('form-label') }}

                                <div class="input-group btn-file-upload">
                                    {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer2')->attribute('data-hidden-input', 'file_url2') }}

                                    {{ html()->text('image_input2')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Image Input 2')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer2')->attribute('data-hidden-input', 'file_url2')->attribute('aria-describedby', 'basic-addon1') }}
                                </div>

                                <div class="uploaded-image" id="selectedImageContainer2">
                                    @if ($data->poster_url)
                                        <img src="{{ old('poster_url', isset($data) ? $data->poster_url : '') }}"
                                            alt="feature-image" class="img-fluid mb-2 avatar-80 " />

                                        <span class="remove-media-icon"
                                            style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                                            onclick="removeImage('file_url2', 'remove_image_flag')">×</span>
                                    @else
                                        <p>No image selected.</p>
                                    @endif
                                </div>

                                {{ html()->hidden('poster_url')->id('file_url2')->value($data->poster_url) }}
                                {{ html()->hidden('remove_image')->id('remove_image_flag')->value(0) }}
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <input type="hidden" name="tmdb_id" id="tmdb_id" value="{{ $tmdb_id }}">
                            <div class="position-relative">
                                {{ html()->label(__('movie.lbl_poster_tv'), 'poster_tv')->class('form-label') }}

                                <div class="input-group btn-file-upload">
                                    {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerTv')->attribute('data-hidden-input', 'file_urltv') }}

                                    {{ html()->text('image_input_tv')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Image Input Tv')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerTv')->attribute('data-hidden-input', 'file_urltv')->attribute('aria-describedby', 'basic-addon1') }}
                                </div>

                                <div class="uploaded-image" id="selectedImageContainerTv">
                                    @if ($data->poster_tv_url)
                                        <img src="{{ old('poster_tv_url', isset($data) ? $data->poster_tv_url : '') }}"
                                            alt="feature-image" class="img-fluid mb-2 avatar-80 " />

                                        <span class="remove-media-icon"
                                            style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                                            onclick="removeTvImage('file_urltv', 'remove_image_flag_tv')">×</span>
                                    @else
                                        <p>No image selected.</p>
                                    @endif
                                </div>

                                {{ html()->hidden('poster_tv_url')->id('file_urltv')->value($data->poster_tv_url) }}
                                {{ html()->hidden('remove_image')->id('remove_image_flag_tv')->value(0) }}
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6">
                            <div class="row gy-3">
                                <div class="col-md-6">
                                    {{ html()->label(__('season.lbl_tv_shows') . ' <span class="text-danger">*</span>', 'type')->class('form-label') }}
                                    {{ html()->select(
                                            'entertainment_id',
                                            $tvshows->pluck('name', 'id')->prepend(__('placeholder.lbl_select_tvshow'), ''),
                                            $data->entertainment_id,
                                        )->class('form-control select2')->id('entertainment_id')->attribute('required', 'required') }}
                                    @error('entertainment_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <div class="invalid-feedback" id="name-error">{{ __('messages.tv_show_field_required') }}</div>
                                </div>
                                <div class="col-md-6">
                                    {{ html()->label(__('placeholder.lbl_select_season') . ' <span class="text-danger">*</span>', 'season_id')->class('form-label') }}
                                    {{ html()->select(
                                            'season_id',
                                            $seasons->pluck('name', 'id')->prepend(__('placeholder.lbl_select_season'), ''),
                                            $data->season_id,
                                        )->class('form-control select2')->id('season_id')->attribute('required', 'required') }}
                                    @error('season_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <div class="invalid-feedback" id="name-error">{{ __('messages.season_field_required') }}</div>
                                </div>
                                <div class="col-md-6">
                                    {{ html()->label(__('episode.lbl_episode_number'), 'episode_number')->class('form-label') }}
                                    {{ html()->number('episode_number', old('episode_number', $data->episode_number))->class('form-control')->id('episode_number')->placeholder(__('placeholder.lbl_episode_number'))->attribute('min', 1) }}
                                    @error('episode_number')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    {{ html()->label(__('movie.lbl_name') . ' <span class="text-danger">*</span>', 'name')->class('form-label') }}
                                    {{ html()->text('name')->attribute('value', $data->name)->placeholder(__('placeholder.lbl_episode_name'))->class('form-control')->attribute('required', 'required') }}
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <div class="invalid-feedback" id="name-error">{{ __('messages.name_field_required') }}</div>
                                </div>
                                <div class="col-md-6">
                                    {{ html()->label(__('plan.lbl_status'), 'status')->class('form-label') }}
                                    <div class="d-flex justify-content-between align-items-center form-control">
                                        {{ html()->label(__('messages.active'), 'status')->class('form-label mb-0 text-body') }}
                                        <div class="form-check form-switch">
                                            {{ html()->hidden('status', 0) }}
                                            {{ html()->checkbox('status', $data->status)->class('form-check-input')->id('status') }}
                                        </div>
                                    </div>
                                    @error('status')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                {{ html()->label(__('movie.lbl_description') . '<span class="text-danger"> *</span>', 'description')->class('form-label mb-0') }}
                                <span class="text-primary cursor-pointer" id="GenrateDescription"><i class="ph ph-info"
                                        data-bs-toggle="tooltip" title="{{ __('messages.chatgpt_info') }}"></i>
                                    {{ __('messages.lbl_chatgpt') }}</span>
                            </div>
                            {{ html()->textarea('description', $data->description)->class('form-control')->id('description')->placeholder(__('placeholder.lbl_movie_description'))->attribute('required', 'required') }}
                            @error('description')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="desc-error">{{ __('messages.description_field_required') }}</div>
                        </div>
                        <div class="col-lg-12">
                            {{ html()->label(__('movie.lbl_movie_access'), 'access')->class('form-label') }}
                            <div class="d-flex flex-wrap align-items-center gap-3">

                                <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                                    <input class="form-check-input" type="radio" name="access" id="paid"
                                        value="paid" onchange="showPlanSelection()"
                                        {{ $data->access == 'paid' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ __('movie.lbl_paid') }}</span>
                                </label>

                                <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                                    <input class="form-check-input" type="radio" name="access" id="free"
                                        value="free" onchange="showPlanSelection()"
                                        {{ $data->access == 'free' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ __('movie.lbl_free') }}</span>
                                </label>

                                <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                                    <input class="form-check-input" type="radio" name="access" id="pay-per-view"
                                        value="pay-per-view" onchange="showPlanSelection()"
                                        {{ $data->access == 'pay-per-view' ? 'checked' : '' }}>
                                    <span class="form-check-label">{{ __('messages.lbl_pay_per_view') }}</span>
                                </label>

                            </div>

                            @error('access')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-12 row g-3 mt-2 {{ $data->access == 'pay-per-view' ? '' : 'd-none' }}"
                            id="payPerViewFields">
                            {{-- Price --}}
                            <div class="col-md-4">
                                {{ html()->label(__('messages.lbl_price') . '<span class="text-danger">*</span>', 'price')->class('form-label')->for('price') }}
                                {{ html()->number('price', old('price', $data->price))->class('form-control')->attribute('placeholder', __('messages.enter_price'))->attribute('step', '0.01')->required() }}
                                @error('price')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="price-error">{{ __('messages.price_field_required') }}</div>
                            </div>

                            {{-- Purchase Type --}}
                            <div class="col-md-4">
                                {{ html()->label(__('messages.purchase_type') . '<span class="text-danger">*</span>', 'purchase_type')->class('form-label') }}
                                {{ html()->select(
                                        'purchase_type',
                                        [
                                            '' => __('messages.lbl_select_purchase_type'),
                                            'rental' => __('messages.lbl_rental'),
                                            'onetime' => __('messages.lbl_one_time_purchase'),
                                        ],
                                        old('purchase_type', $data->purchase_type ?? 'rental'),
                                    )->id('purchase_type')->class('form-control select2')->required()->attributes(['onchange' => 'toggleAccessDuration(this.value)']) }}
                                @error('purchase_type')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="purchase_type-error">{{ __('messages.purchase_type_field_required') }}</div>
                            </div>

                            {{-- Access Duration (Only for Rental) --}}
                            <div class="col-md-4 {{ $data->purchase_type == 'rental' ? '' : 'd-none' }}"
                                id="accessDurationWrapper">
                                {{ html()->label(__('messages.lbl_access_duration') . __('messages.lbl_in_days') . '<span class="text-danger">*</span>', 'access_duration')->class('form-label') }}
                                {{ html()->number('access_duration', old('access_duration', $data->access_duration))->class('form-control')->attribute('pattern', '[0-9]*')->attribute('oninput', 'this.value = this.value.replace(/[^0-9]/g, "")')->attribute('placeholder', __('messages.access_duration'))->required() }}
                                @error('access_duration')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="access_duration-error">{{ __('messages.access_duration_field_required') }}</div>
                            </div>

                            {{-- Discount --}}
                            <div class="col-md-4">
                                {{ html()->label(__('messages.lbl_discount') . ' (%)', 'discount')->class('form-label') }}
                                {{ html()->number('discount', old('discount', $data->discount))->class('form-control')->attribute('placeholder', __('messages.enter_discount'))->attribute('min', 1)->attribute('max', 99)->attribute('step', '0.01') }}
                                @error('discount')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="discount-error">{{ __('messages.available_for_field_required') }}</div>
                            </div>
                            <div class="col-md-4">
                                {{ html()->label(__('messages.lbl_total_price'), 'total_amount')->class('form-label') }}
                                {{ html()->text('total_amount', null)->class('form-control')->attribute('disabled', true)->id('total_amount') }}
                            </div>
                            {{-- Available For --}}
                            <div class="col-md-4">
                                {{ html()->label(__('messages.lbl_available_for') . __('messages.lbl_in_days') . '<span class="text-danger">*</span>', 'available_for')->class('form-label') }}
                                {{ html()->number('available_for', old('available_for', $data->available_for))->class('form-control')->attribute('pattern', '[0-9]*')->attribute('oninput', 'this.value = this.value.replace(/[^0-9]/g, "")')->attribute('placeholder', __('messages.available_for'))->required() }}
                                @error('available_for')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="available_for-error">{{ __('messages.available_for_field_required') }}</div>
                            </div>

                        </div>
                        <div class="col-md-4  {{ old('access', 'paid') == 'free' ? 'd-none' : '' }}" id="planSelection">
                            {{ html()->label(__('movie.lbl_select_plan') . ' <span class="text-danger">*</span>', 'type')->class('form-label') }}
                            {{ html()->select('plan_id', $plan->pluck('name', 'id')->prepend(__('placeholder.lbl_select_plan'), ''), $data->plan_id)->class('form-control select2')->id('plan_id')->attribute('data-plan-levels', json_encode($plan->pluck('level', 'id'))) }}
                            @error('plan_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="plan_id-error">{{ __('messages.plan_field_required') }}</div>
                        </div>


                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_trailer_url_type') . ' <span class="text-danger">*</span>', 'type')->class('form-label') }}
                            {{ html()->select(
                                    'trailer_url_type',
                                    $upload_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_type'), ''),
                                    old('trailer_url_type', $data->trailer_url_type ?? ''),
                                )->class('form-control select2')->id('trailer_url_type') }}
                            @error('trailer_url_type')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="name-error">{{ __('messages.trailer_type_field_required') }}</div>

                        </div>
                        <div class="col-md-6 col-lg-4 d-none" id="url_input">
                            {{ html()->label(__('movie.lbl_trailer_url') . ' <span class="text-danger">*</span>', 'trailer_url')->class('form-label') }}

                            <!-- Regular input for URLs -->
                            <div id="regular_input">
                                {{ html()->text('trailer_url')->placeholder(__('placeholder.lbl_trailer_url'))->class('form-control') }}
                            </div>

                            <!-- Add textarea for embedded code -->
                            <div id="embedded_input" class="d-none">
                                {{ html()->textarea('trailer_url_embedded')->placeholder(__('messages.lbl_embed_code'))->class('form-control')->id('trailer_url_embedded')->rows(4) }}
                            </div>

                            @error('trailer_url')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="trailer-url-error">{{ __('messages.video_url_field_required') }}</div>
                            <div class="invalid-feedback" id="trailer-pattern-error" style="display:none;">
                                Please enter a valid URL starting with http:// or https://.
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4 d-none" id="url_file_input">
                            {{ html()->label(__('movie.lbl_trailer_video') . ' <span class="text-danger">*</span>', 'trailer_video')->class('form-label') }}
                            <div class="mb-3" id="selectedImageContainer3">
                                @if (Str::endsWith($data->trailer_url, ['.jpeg', '.jpg', '.png', '.gif']))
                                    <img class="img-fluid mb-2" src="{{ $data->trailer_url }}"
                                        style="max-width: 100px; max-height: 100px;">
                                @else
                                    <video width="400" controls="controls" preload="metadata">
                                        <source src="{{ $data->trailer_url }}" type="video/mp4">
                                    </video>
                                @endif
                            </div>

                            <div class="input-group btn-video-link-upload">
                                {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer3')->attribute('data-hidden-input', 'file_url3') }}

                                {{ html()->text('image_input3')->class('form-control')->placeholder(__('placeholder.lbl_select_file'))->attribute('aria-label', 'Image Input 3')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer3')->attribute('data-hidden-input', 'file_url3') }}
                            </div>

                            {{ html()->hidden('trailer_video')->id('file_url3')->value($data->trailer_url)->attribute('data-validation', 'iq_video_quality') }}


                            @error('trailer_video')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="trailer-file-error">{{ __('messages.video_file_field_required') }}</div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="pills-basic" role="tabpanel" aria-labelledby="pills-basic-tab">
            <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                <h5>{{ __('movie.lbl_basic_info') }}</h5>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_duration') . ' <span class="text-danger">*</span>', 'duration')->class('form-label') }}
                            {{ html()->time('duration')->attribute('value', $data->duration)->placeholder(__('movie.lbl_duration'))->class('form-control  min-datetimepicker-time')->attribute('required', 'required') }}
                            @error('time')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="duration-error">{{ __('messages.duration_field_required') }}</div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('messages.lbl_skip_intro_start_time'), 'start_time')->class('form-label') }}
                            {{ html()->text('start_time')->attribute('value', $data->start_time ? \Carbon\Carbon::parse($data->start_time)->format('H:i:s') : '')->placeholder(__('messages.lbl_skip_intro_start_time'))->class('form-control')->id('start_time') }}
                            @error('start_time')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('messages.lbl_skip_intro_end_time'), 'end_time')->class('form-label') }}
                            {{ html()->text('end_time')->attribute('value', $data->end_time ? \Carbon\Carbon::parse($data->end_time)->format('H:i:s') : '')->placeholder(__('messages.lbl_skip_intro_end_time'))->class('form-control')->id('end_time') }}
                            @error('end_time')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_imdb_rating'), 'IMDb_rating')->class('form-label') }}
                            {{ html()->text('IMDb_rating', old('IMDb_rating', $data->IMDb_rating))->placeholder(__('movie.lbl_imdb_rating'))->class('form-control') }}


                            @error('IMDb_rating')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="imdb-error">{{ __('messages.imdb_rating_field_required') }}</div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_release_date') . ' <span class="text-danger">*</span>', 'release_date')->class('form-label') }}
                            {{ html()->date('release_date')->attribute('value', $data->release_date ? \Carbon\Carbon::parse($data->release_date)->format('Y-m-d') : '')->placeholder(__('movie.lbl_release_date'))->class('form-control datetimepicker')->attribute('required', 'required') }}
                            @error('release_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="release_date-error">{{ __('messages.release_date_field_required') }}</div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_content_rating') . '<span class="text-danger">*</span>', 'content_rating')->class('form-label') }}
                            {{ html()->text('content_rating')->attribute('value', $data->content_rating)->placeholder(__('placeholder.lbl_content_rating'))->class('form-control')->attribute('required', 'required') }}

                            @error('content_rating')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="name-error">{{ __('messages.content_rating_field_required') }}</div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_download_status'), 'download_status')->class('form-label') }}
                            <div class="d-flex justify-content-between align-items-center form-control">
                                {{ html()->label(__('messages.on'), 'download_status')->class('form-label mb-0 text-body') }}
                                <div class="form-check form-switch">
                                    {{ html()->hidden('download_status', 0) }}
                                    {{ html()->checkbox('download_status', !empty($data) && $data->download_status == 1)->class('form-check-input')->id('download_status')->value(1) }}
                                </div>
                            </div>
                            @error('download_status')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="pills-quality" role="tabpanel" aria-labelledby="pills-quality-tab">
            <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                <h5>{{ __('movie.lbl_video_info') }}</h5>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-6 col-lg-6">
                            {{ html()->label(__('movie.lbl_video_upload_type') . '<span class="text-danger">*</span>', 'video_upload_type')->class('form-label') }}
                            {{ html()->select(
                                    'video_upload_type',
                                    $upload_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_video_type'), ''),
                                    old('video_upload_type', $data->video_upload_type ?? ''),
                                )->class('form-control select2')->id('video_upload_type') }}
                            @error('video_upload_type')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="name-error">{{ __('messages.video_type_field_required') }}</div>
                        </div>
                        <div class="col-md-6 col-lg-6 d-none" id="video_url_input_section">
                            {{ html()->label(__('movie.video_url_input') . '<span class="text-danger">*</span>', 'video_url_input')->class('form-label') }}

                            <!-- Regular input for URLs -->
                            <div id="regular_video_input">
                                {{ html()->text('video_url_input')->attribute('value', $data->video_url_input)->placeholder(__('placeholder.video_url_input'))->class('form-control') }}
                            </div>

                            <!-- Add textarea for embedded code -->
                            <div id="embedded_video_input" class="d-none">
                                {{ html()->textarea('video_url_embedded')->placeholder(__('messages.lbl_embed_code'))->class('form-control')->id('video_url_embedded')->rows(4) }}
                            </div>

                            @error('video_url_input')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="url-error">{{ __('messages.video_url_field_required') }}</div>
                            <div class="invalid-feedback" id="url-pattern-error" style="display:none;">
                                Please enter a valid URL starting with http:// or https://.
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-6 d-none" id="video_file_input_section">
                            {{ html()->label(__('movie.video_file_input') . '<span class="text-danger">*</span>', 'video_file')->class('form-label') }}
                            <div class="mb-3" id="selectedImageContainer4">
                                @if (Str::endsWith($data->video_url_input, ['.jpeg', '.jpg', '.png', '.gif']))
                                    <img class="img-fluid media-thumb-10" src="{{ $data->video_url_input }}">
                                @else
                                    <video width="400" controls="controls" preload="metadata">
                                        <source src="{{ $data->video_url_input }}" type="video/mp4">
                                    </video>
                                @endif
                            </div>

                            <div class="input-group btn-video-link-upload">
                                {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer4')->attribute('data-hidden-input', 'file_url4') }}

                                {{ html()->text('image_input4')->class('form-control')->placeholder(__('placeholder.lbl_select_file'))->attribute('aria-label', 'Image Input 3')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer4')->attribute('data-hidden-input', 'file_url4') }}
                            </div>

                            {{ html()->hidden('video_file_input')->id('file_url4')->value($data->video_url_input)->attribute('data-validation', 'iq_video_quality') }}

                            @error('video_file_input')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="file-error">{{ __('messages.video_file_field_required') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                <h5>{{ __('movie.lbl_quality_info') }}</h5>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-lg-12">
                            <div class="d-flex justify-content-between align-items-center form-control">
                                <label for="enable_quality"
                                    class="form-label mb-0 text-body">{{ __('movie.lbl_enable_quality') }}</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enable_quality" value="0">
                                    <input type="checkbox" name="enable_quality" id="enable_quality"
                                        class="form-check-input" value="1" onchange="toggleQualitySection()"
                                        {{ !empty($data) && $data->enable_quality == 1 ? 'checked' : '' }}>
                                </div>
                            </div>
                            @error('enable_quality')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div id="enable_quality_section" class="col-md-12 enable_quality_section d-none">
                            <div id="video-inputs-container-parent">
                                @if (!empty($data['EpisodeStreamContentMapping']) && count($data['EpisodeStreamContentMapping']) > 0)
                                    @foreach ($data['EpisodeStreamContentMapping'] as $idx => $mapping)
                                        <div class="row gy-3 video-inputs-container mt-1">
                                            <div class="col-md-3">
                                                {{ html()->label(__('movie.lbl_video_upload_type'), 'video_quality_type')->class('form-label') }}
                                                {{ html()->select(
                                                        'video_quality_type[]',
                                                        $upload_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_video_type'), ''),
                                                        $mapping->type,
                                                    )->class('form-control select2 video_quality_type')->id('video_quality_type_' . $mapping->id) }}
                                                @error('video_quality_type')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 video-input">
                                                {{ html()->label(__('movie.lbl_video_quality'), 'video_quality')->class('form-label') }}
                                                {{ html()->select(
                                                        'video_quality[]',
                                                        $video_quality->pluck('name', 'value')->prepend(__('placeholder.lbl_select_quality'), ''),
                                                        $mapping->quality, // Populate the select with the existing quality
                                                    )->class('form-control select2')->id('video_quality_' . $mapping->id) }}
                                                @error('video_quality')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 video-url-input quality_video_input"
                                                id="quality_video_input">
                                                {{ html()->label(__('movie.video_url_input'), 'quality_video_url_input')->class('form-label') }}
                                                {{ html()->text('quality_video_url_input[]', $mapping->url)->placeholder(__('placeholder.video_url_input'))->class('form-control') }}

                                                @error('quality_video_url_input.' . $idx)
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                                @error('quality_video_url_input.*')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>


                                            <div class="col-md-4 d-none video-file-input quality_video_file_input">
                                                {{ html()->label(__('movie.video_file_input'), 'quality_video')->class('form-label') }}

                                                <div class="input-group btn-video-link-upload">
                                                    {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer6')->attribute('data-hidden-input', 'file_url5') }}

                                                    {{ html()->text('image_input6')->class('form-control')->placeholder('Select Image')->attribute('aria-label', 'Image Input 5')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer6')->attribute('data-hidden-input', 'file_url5') }}
                                                </div>
                                                <div class="mt-3" id="selectedImageContainer6">
                                                    @if (Str::endsWith(setBaseUrlWithFileName($mapping->url, 'video', 'episode'), ['.jpeg', '.jpg', '.png', '.gif']))
                                                        <img class="img-fluid"
                                                            src="{{ setBaseUrlWithFileName($mapping->url, 'video', 'episode') }}"
                                                            style="max-width: 100px; max-height: 100px;">
                                                    @else
                                                        <video width="400" controls="controls" preload="metadata">
                                                            <source
                                                                src="{{ setBaseUrlWithFileName($mapping->url, 'video', 'episode') }}"
                                                                type="video/mp4">
                                                        </video>
                                                    @endif
                                                </div>

                                                {{ html()->hidden('quality_video[]')->id('file_url5')->value(setBaseUrlWithFileName($mapping->url, 'video', 'episode'))->attribute('data-validation', 'iq_video_quality') }}

                                                @error('quality_video')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 d-none video-embed-input quality_video_embed_input">
                                                {{ html()->label(__('movie.lbl_embed_code'), 'quality_video_embed')->class('form-label') }}
                                                {{ html()->textarea('quality_video_embed_input[]')->placeholder('<iframe ...></iframe>')->class('form-control')->value($mapping->type === 'Embedded' ? $mapping->url : '')->rows(4) }}
                                            </div>

                                            <div class="col-sm-1 d-flex justify-content-center align-items-center mt-5">
                                                <button type="button"
                                                    class="btn btn-secondary-subtle btn-sm fs-4 remove-video-input"><i
                                                        class="ph ph-trash align-middle"></i></button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="row gy-3 video-inputs-container mt-1">
                                        <div class="col-md-3">
                                            {{ html()->label(__('movie.lbl_video_upload_type'), 'video_quality_type')->class('form-label') }}
                                            {{ html()->select(
                                                    'video_quality_type[]',
                                                    $upload_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_video_type'), ''),
                                                    old('video_quality_type', 'Local'),
                                                )->class('form-control select2 video_quality_type') }}
                                            @error('video_quality_type')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-4 video-input">
                                            {{ html()->label(__('movie.lbl_video_quality'), 'video_quality')->class('form-label') }}
                                            {{ html()->select(
                                                    'video_quality[]',
                                                    $video_quality->pluck('name', 'value')->prepend(__('placeholder.lbl_select_quality'), ''),
                                                    null, // No existing quality
                                                )->class('form-control select2')->id('video_quality_new') }}
                                            @error('video_quality')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-4 video-url-input quality_video_input"
                                            id="quality_video_input">
                                            {{ html()->label(__('movie.video_url_input'), 'quality_video_url_input')->class('form-label') }}
                                            {{ html()->text('quality_video_url_input[]', null)->placeholder(__('placeholder.video_url_input'))->class('form-control') }}
                                            @error('quality_video_url_input.0')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                            @error('quality_video_url_input.*')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-4 d-none video-file-input quality_video_file_input">
                                            {{ html()->label(__('movie.video_file_input'), 'quality_video')->class('form-label') }}
                                            <div id="selectedImageContainer5">
                                                @if ($data->quality_video)
                                                    <img src="{{ setBaseUrlWithFileName($data->quality_video, 'video', 'episode') }}"
                                                        class="img-fluid mb-2"
                                                        style="max-width: 100px; max-height: 100px;">
                                                @endif
                                            </div>

                                            <div class="input-group btn-video-link-upload">
                                                {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer5')->attribute('data-hidden-input', 'file_url5') }}

                                                {{ html()->text('image_input5')->class('form-control')->placeholder(__('placeholder.lbl_select_file'))->attribute('aria-label', 'Image Input 5')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer5')->attribute('data-hidden-input', 'file_url5') }}
                                            </div>

                                            {{ html()->hidden('quality_video[]')->id('file_url5')->value(setBaseUrlWithFileName($data->quality_video, 'video', 'episode'))->attribute('data-validation', 'iq_video_quality') }}
                                            @error('quality_video')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-4 d-none video-embed-input quality_video_embed_input">
                                            {{ html()->label(__('movie.lbl_embed_code'), 'quality_video_embed')->class('form-label') }}
                                            {{ html()->textarea('quality_video_embed_input[]')->placeholder('<iframe ...></iframe>')->class('form-control')->value($data->quality_video, 'video', 'episode')->rows(4) }}
                                        </div>

                                        <div class="col-sm-1 d-flex justify-content-center align-items-center mt-5">
                                            <button type="button"
                                                class="btn btn-secondary-subtle btn-sm fs-4 remove-video-input d-none"><i
                                                    class="ph ph-trash align-middle"></i></button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="text-end">
                                <a id="add_more_video" class="btn btn-sm btn-primary"><i class="ph ph-plus-circle"></i>
                                    {{ __('episode.lbl_add_more') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="pills-Subtitle" role="tabpanel" aria-labelledby="pills-Subtitle-tab">
            <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                <h5>{{ __('movie.lbl_subtitle_info') }}</h5>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center form-control">
                                <label for="enable_subtitle"
                                    class="form-label mb-0 text-body">{{ __('movie.lbl_enable_subtitle') }}</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enable_subtitle" value="0">
                                    <input type="checkbox" name="enable_subtitle" id="enable_subtitle"
                                        class="form-check-input" value="1"
                                        {{ old('enable_subtitle', $data->enable_subtitle) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                        <div id="subtitle_section"
                            class="col-md-12 {{ old('enable_subtitle', $data->enable_subtitle) ? '' : 'd-none' }}">
                            <input type="hidden" name="deleted_subtitles" id="deleted_subtitles" value="">
                            <div id="subtitle-container">
                                @if ($data->subtitles && count($data->subtitles) > 0)
                                    @foreach ($data->subtitles as $index => $subtitle)
                                        <div class="subtitle-row row">
                                            <input type="hidden" name="subtitles[{{ $index }}][id]"
                                                value="{{ $subtitle->id }}">
                                            <div class="col-md-4">
                                                <select name="subtitles[{{ $index }}][language]"
                                                    class="form-control subtitle-language select2" required>
                                                    <option value="">{{ __('placeholder.lbl_select_language') }}
                                                    </option>
                                                    @foreach ($subtitle_language as $language)
                                                        <option value="{{ $language->value }}"
                                                            {{ $subtitle->language_code == $language->value ? 'selected' : '' }}>
                                                            {{ $language->name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback">
                                                    {{ __('validation.required', ['attribute' => 'language']) }}</div>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="file"
                                                    name="subtitles[{{ $index }}][subtitle_file]"
                                                    class="form-control">
                                                @if ($subtitle->subtitle_file)
                                                    <small class="text-muted">Current file:
                                                        {{ basename($subtitle->subtitle_file) }}</small>
                                                @endif
                                                <div class="invalid-feedback">
                                                    {{ __('validation.required', ['attribute' => 'subtitle file']) }}
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check mt-3 ">
                                                    <input type="checkbox"
                                                        name="subtitles[{{ $index }}][is_default]"
                                                        class="form-check-input is-default-subtitle" value="1"
                                                        {{ $subtitle->is_default ? 'checked' : '' }}>
                                                    <label
                                                        class="form-check-label">{{ __('movie.lbl_default_subtitle') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button"
                                                    class="btn btn-danger btn-sm mt-5 remove-subtitle"><i
                                                        class="ph ph-trash"></i></button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="subtitle-row row">
                                        <div class="col-md-4">
                                            <label for="language"
                                                class="form-label">{{ __('messages.lbl_languages') }}</label>
                                            <select name="subtitles[0][language]"
                                                class="form-control subtitle-language select2">
                                                <option value="">{{ __('placeholder.lbl_select_language') }}
                                                </option>
                                                @foreach ($subtitle_language as $language)
                                                    <option value="{{ $language->value }}">{{ $language->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">
                                                {{ __('validation.required', ['attribute' => 'language']) }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="subtitle_file"
                                                class="form-label">{{ __('movie.lbl_subtitle_file') }}</label>
                                            <input type="file" name="subtitles[0][subtitle_file]"
                                                class="form-control">
                                            <div class="invalid-feedback">
                                                {{ __('validation.required', ['attribute' => 'subtitle file']) }}</div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-check mt-3 pt-4">
                                                <input type="checkbox" name="subtitles[0][is_default]"
                                                    class="form-check-input is-default-subtitle" value="1">
                                                <label
                                                    class="form-check-label">{{ __('movie.lbl_default_subtitle') }}</label>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button"
                                                class="btn btn-danger btn-sm mt-5 remove-subtitle d-none"><i
                                                    class="ph ph-trash"></i></button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="text-end mt-3">
                                <a type="button" id="add-subtitle" class="btn btn-sm btn-primary">
                                    <i class="ph ph-plus-circle"></i> {{ __('episode.lbl_add_more') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="pills-seo" role="tabpanel" aria-labelledby="pills-seo-tab">
            <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                <h5 class="mb-0">&nbsp;{{ __('messages.lbl_seo_settings') }}</h5>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center form-control">
                                <label for="enableSeoIntegration"
                                    class="form-label mb-0 text-body">{{ __('movie.lbl_enable_seo-setting') }}</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enable_seo" value="0">
                                    <input type="checkbox" name="enable_seo" id="enableSeoIntegration"
                                        class="form-check-input" value="1"
                                        {{ !empty($seo->meta_title) || !empty($seo->meta_keywords) || !empty($seo->meta_description) || !empty($seo->seo_image) || !empty($seo->google_site_verification) || !empty($seo->canonical_url) || !empty($seo->short_description) ? 'checked' : '' }}>

                                </div>
                            </div>
                        </div>


                        <!-- SEO Fields Section -->
                        <div id="seoFields"
                            style="display: {{ !empty($seo->meta_title) || !empty($seo->meta_keywords) || !empty($seo->meta_description) || !empty($seo->seo_image) || !empty($seo->google_site_verification) || !empty($seo->canonical_url) || !empty($seo->short_description) ? 'block' : 'none' }};">
                            <div class="row mb-3">
                                <!-- SEO Image -->
                                <div class="col-md-4 position-relative">
                                    {{ html()->hidden('seo_image')->id('seo_image')->value(old('seo_image', $data->seo_image ?? '')) }}

                                    {!! html()->label(__('messages.lbl_seo_image') . ' <span class="required">*</span>', 'seo_image')->class('form-label')->attribute('for', 'seo_image') !!}

                                    <div class="input-group btn-file-upload">
                                        {{ html()->button('<i class="ph ph-image"></i> ' . __('messages.lbl_choose_image'))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerSeo')->attribute('data-hidden-input', 'seo_image')->id('seo-image-url-button')->style('height:13.6rem') }}

                                        {{ html()->text('seo_image_input')->class('form-control ' . ($errors->has('seo_image') ? 'is-invalid' : ''))->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'SEO Image')->attribute('readonly', true)->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerSeo')->attribute('data-hidden-input', 'seo_image') }}
                                    </div>

                                    {{-- ✅ Move this outside input-group --}}
                                    <div class="invalid-feedback mt-1" id="seo_image_error">
                                        {{ __('messages.seo_image_required') }}
                                    </div>

                                    {{-- Image Preview --}}
                                    <div class="uploaded-image mt-2" id="selectedImageContainerSeo">
                                        <img id="selectedSeoImage" src="{{ old('seo_image', $data->seo_image ?? '') }}"
                                            alt="seo-image-preview" class="img-fluid"
                                            style="{{ old('seo_image', $data->seo_image ?? '') ? '' : 'display:none;' }}" />
                                    </div>

                                    {{-- Laravel Error --}}
                                    @error('seo_image')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Meta Title + Google Verification -->
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        <div class="d-flex justify-content-between">
                                            {!! html()->label(__('messages.lbl_meta_title') . ' <span class="required">*</span>', 'meta_title')->class('form-label')->attribute('for', 'meta_title') !!}

                                            <div id="meta-title-char-count" class="text-muted">0/100
                                                {{ __('messages.words') }}</div>
                                        </div>

                                        <input type="text" name="meta_title" id="meta_title"
                                            class="form-control @error('meta_title') is-invalid @enderror"
                                            value="{{ old('meta_title', $seo->meta_title ?? '') }}" maxlength="100"
                                            placeholder="{{ __('placeholder.lbl_meta_title') }}"
                                            oninput="updateCharCount()">

                                        <div class="invalid-feedback" id="meta_title_error" style="display: none;">{{ __('messages.meta_title_required') }}</div>
                                        @error('meta_title')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group mb-3">
                                        {!! html()->label(
                                                __('messages.lbl_google_site_verification') . ' <span class="required">*</span>',
                                                'google_site_verification',
                                            )->class('form-label')->attribute('for', 'google_site_verification') !!}
                                        <input type="text" name="google_site_verification"
                                            id="google_site_verification"
                                            class="form-control @error('google_site_verification') is-invalid @enderror"
                                            value="{{ old('google_site_verification', $seo->google_site_verification ?? '') }}"
                                            placeholder="{{ __('placeholder.lbl_google_site_verification') }}">
                                        <div class="invalid-feedback" id="embed-error">{{ __('messages.google_site_verification_required') }}</div>
                                    </div>
                                </div>

                                <!-- Meta Keywords + Canonical URL -->
                                <div class="col-md-4">
                                    <div class="form-group mb-3">
                                        {!! html()->label(__('messages.lbl_meta_keywords') . ' <span class="required">*</span>', 'meta_keywords_input')->class('form-label')->attribute('for', 'meta_keywords_input') !!}
                                        <input type="text" name="meta_keywords" id="meta_keywords_input"
                                            class="form-control"
                                            value="{{ is_array(old('meta_keywords')) ? $seo->meta_keywords ?? '' : old('meta_keywords', $seo->meta_keywords ?? '') }}"
                                            placeholder="{{ __('placeholder.lbl_meta_keywords') }}"
                                            data-placeholder="{{ __('placeholder.lbl_meta_keywords') }}">
                                        <div id="meta_keywords_hidden_inputs"></div>
                                        <div class="invalid-feedback" id="meta_keywords_error">
                                            {{ __('messages.meta_keywords_required') }}
                                        </div>
                                        @error('meta_keywords')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group mb-3">
                                        {!! html()->label(__('messages.lbl_canonical_url') . ' <span class="required">*</span>', 'canonical_url')->class('form-label')->attribute('for', 'canonical_url') !!}
                                        <input type="text" name="canonical_url" id="canonical_url"
                                            class="form-control @error('canonical_url') is-invalid @enderror"
                                            value="{{ old('canonical_url', $seo->canonical_url ?? '') }}"
                                            placeholder="{{ __('placeholder.lbl_canonical_url') }}">

                                        <div class="invalid-feedback" id="embed-error">{{ __('messages.canonical_url_required') }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Short Description -->
                            <div class="row">
                                <div class="col-md-12 form-group mb-3">
                                    <div class="d-flex justify-content-between">
                                        {!! html()->label(__('messages.lbl_short_description') . ' <span class="required">*</span>', 'short_description')->class('form-label')->attribute('for', 'short_description') !!}

                                        <div id="meta-description-char-count" class="text-muted">0/200
                                            {{ __('messages.words') }}</div>
                                    </div>

                                    <textarea name="short_description" id="short_description"
                                        class="form-control @error('short_description') is-invalid @enderror" maxlength="200"
                                        placeholder="{{ __('placeholder.lbl_short_description') }}">{{ old('short_description', $seo->short_description ?? '') }}</textarea>

                                    @error('short_description')
                                        <span class="text-danger" id="short_description-error">{{ $message }}</span>
                                    @enderror
                                    <div class="invalid-feedback" id="embed-error">{{ __('messages.site_meta_description_required') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="pills-download" role="tabpanel" aria-labelledby="pills-download-tab">
            <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                <h5>{{ __('movie.lbl_download_info') }}</h5>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-6">
                            {{ html()->label(__('movie.lbl_quality_video_download_type'), 'video_upload_type_download')->class('form-label') }}
                            {{ html()->select(
                                    'video_upload_type_download',
                                    $download_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_video_type'), ''),
                                    old('video_upload_type_download', $data->download_type ?? ''),
                                )->class('form-control select2')->id('video_upload_type_download') }}
                            @error('video_upload_type')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="name-error">{{ __('messages.video_type_field_required') }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3 d-none" id="video_url_input_section_download">
                                {{ html()->label(__('movie.download_url'), 'video_url_input_download')->class('form-label') }}
                                {{ html()->text('video_url_input_download')->attribute('value', old('video_url_input_download', $data->download_type === 'URL' ? $data->download_url : ''))->placeholder(__('placeholder.video_url_input'))->class('form-control')->id('video_url_input_download') }}
                                @error('video_url_input_download')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="url-error">{{ __('messages.video_url_field_required') }}</div>
                                <div class="invalid-feedback" id="url-pattern-error" style="display:none;">
                                    Please enter a valid URL starting with http:// or https://.
                                </div>
                            </div>

                            <div class="mb-3 d-none" id="video_file_input_section_download">
                                {{ html()->label(__('messages.lbl_download_file'), 'video_file_input_download')->class('form-label') }}

                                <div class="input-group btn-video-link-upload mb-3">
                                    {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerDownloadVideourl')->attribute('data-hidden-input', 'file_url_video_download') }}

                                    {{ html()->text('video_file_input_download')->class('form-control')->placeholder('Select video')->attribute('aria-label', 'Download Video')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerDownloadVideourl')->attribute('data-hidden-input', 'file_url_video_download') }}
                                </div>

                                <div class="mt-3" id="selectedImageContainerDownloadVideourl">
                                    @php $downloadLocal = old('video_file_input_download', ($data->download_type === 'Local' ? setBaseUrlWithFileName($data->download_url,'video', 'episode') : null)); @endphp
                                    @if ($downloadLocal)
                                        <video width="400" controls="controls" preload="metadata">
                                            <source src="{{ $downloadLocal }}" type="video/mp4">
                                        </video>
                                    @endif
                                </div>

                                {{ html()->hidden('video_file_input_download')->id('file_url_video_download')->value(old('video_file_input_download', $data->download_type === 'Local' ? setBaseUrlWithFileName($data->download_url, 'video', 'episode') : ''))->attribute('data-validation', 'iq_video_quality') }}

                                @error('video_file_input_download')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="file-error">{{ __('messages.video_file_field_required') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                <h5>{{ __('movie.lbl_download_quality_info') }}</h5>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center justify-content-between form-control">
                                <label for="enable_download_quality"
                                    class="form-label mb-0 text-body">{{ __('movie.lbl_enable_quality') }}</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enable_download_quality" value="0">
                                    <input type="checkbox" name="enable_download_quality" id="enable_download_quality"
                                        class="form-check-input" value="1"
                                        {{ old('enable_download_quality', $data->enable_download_quality ?? false) ? 'checked' : '' }}>
                                </div>
                            </div>
                            @error('enable_download_quality')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div id="enable_download_quality_section"
                            class="col-md-12 enable_quality_section {{ old('enable_download_quality', $data->enable_download_quality ?? false) ? '' : 'd-none' }}">
                            <div id="download-video-inputs-container-parent">
                                @php $existingDownloads = old('quality_video_download_type') ? null : ($data->episodeDownloadMappings ?? collect()); @endphp
                                @if ($existingDownloads && $existingDownloads->count())
                                    @foreach ($existingDownloads as $idx => $dl)
                                        <div class="row gy-3 download-video-inputs-container mt-1">
                                            <div class="col-md-3">
                                                {{ html()->label(__('movie.lbl_quality_video_download_type'), 'quality_video_download_type')->class('form-label') }}
                                                {{ html()->select(
                                                        'quality_video_download_type[]',
                                                        $download_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_video_type'), ''),
                                                        old('quality_video_download_type.' . $idx, $dl->type),
                                                    )->class('form-control select2 download_quality_video_type') }}
                                            </div>
                                            <div class="col-md-4">
                                                {{ html()->label(__('movie.lbl_video_download_quality'), 'video_download_quality')->class('form-label') }}
                                                {{ html()->select('video_download_quality[]', $video_quality->pluck('name', 'value')->prepend(__('placeholder.lbl_select_quality'), ''), old('video_download_quality.' . $idx, $dl->quality))->class('form-control select2') }}
                                            </div>
                                            @php $isLocal = ($dl->type === 'Local'); @endphp
                                            <div
                                                class="col-md-4 {{ $isLocal ? 'd-none' : '' }} download-video-url-input download_quality_video_input">
                                                {{ html()->label(__('movie.download_url'), 'download_quality_video_url')->class('form-label') }}
                                                {{ html()->text('download_quality_video_url[]', old('download_quality_video_url.' . $idx, $isLocal ? '' : $dl->url))->placeholder(__('placeholder.video_url_input'))->class('form-control') }}
                                                @error('download_quality_video_url.' . $idx)
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                                @error('download_quality_video_url.*')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div
                                                class="col-md-4 {{ $isLocal ? '' : 'd-none' }} download-video-file-input download_quality_video_file_input">
                                                {{ html()->label(__('messages.lbl_download_file'), 'download_quality_video')->class('form-label') }}
                                                <div class="input-group btn-video-link-upload">
                                                    {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerDownloadVideoqualityurl' . $idx)->attribute('data-hidden-input', 'file_url_download_videoquality' . $idx) }}
                                                    {{ html()->text('download_videoquality_input')->class('form-control')->placeholder('Select File')->attribute('aria-label', 'Download Video Quality File')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerDownloadVideoqualityurl' . $idx)->attribute('data-hidden-input', 'file_url_download_videoquality' . $idx) }}
                                                </div>
                                                <div class="mt-3"
                                                    id="selectedImageContainerDownloadVideoqualityurl{{ $idx }}">
                                                    @if ($isLocal && !empty($dl->url))
                                                        @php $dlUrl = setBaseUrlWithFileName($dl->url,'video', 'episode'); @endphp
                                                        @if (Str::endsWith($dlUrl, ['.jpeg', '.jpg', '.png', '.gif']))
                                                            <img class="img-fluid" src="{{ $dlUrl }}"
                                                                style="max-width: 100px; max-height: 100px;">
                                                        @else
                                                            <video width="400" controls="controls" preload="metadata">
                                                                <source src="{{ $dlUrl }}" type="video/mp4">
                                                            </video>
                                                        @endif
                                                    @endif
                                                </div>
                                                {{ html()->hidden('download_quality_video[]')->id('file_url_download_videoquality' . $idx)->value(old('download_quality_video.' . $idx, $isLocal ? setBaseUrlWithFileName($dl->url, 'video', 'episode') : ''))->attribute('data-validation', 'iq_video_quality') }}
                                            </div>
                                            <div class="col-sm-1 d-flex justify-content-center align-items-center mt-5">
                                                <button type="button"
                                                    class="btn btn-secondary-subtle btn-sm remove-download-video-input"><i
                                                        class="ph ph-trash align-middle"></i></button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="row gy-3 download-video-inputs-container mt-1">
                                        <div class="col-md-3">
                                            {{ html()->label(__('movie.lbl_quality_video_download_type'), 'quality_video_download_type')->class('form-label') }}
                                            {{ html()->select(
                                                    'quality_video_download_type[]',
                                                    $download_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_video_type'), ''),
                                                    old('quality_video_download_type.0', ''),
                                                )->class('form-control select2 download_quality_video_type') }}
                                        </div>
                                        <div class="col-md-4">
                                            {{ html()->label(__('movie.lbl_video_download_quality'), 'video_download_quality')->class('form-label') }}
                                            {{ html()->select('video_download_quality[]', $video_quality->pluck('name', 'value')->prepend(__('placeholder.lbl_select_quality'), ''), old('video_download_quality.0', ''))->class('form-control select2') }}
                                        </div>
                                        <div class="col-md-4 d-none download-video-url-input download_quality_video_input">
                                            {{ html()->label(__('movie.download_url'), 'download_quality_video_url')->class('form-label') }}
                                            {{ html()->text('download_quality_video_url[]', old('download_quality_video_url.0', ''))->placeholder(__('placeholder.video_url_input'))->class('form-control') }}
                                            @error('download_quality_video_url.0')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                            @error('download_quality_video_url.*')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div
                                            class="col-md-4 d-none download-video-file-input download_quality_video_file_input">
                                            {{ html()->label(__('messages.lbl_download_file'), 'download_quality_video')->class('form-label') }}
                                            <div class="input-group btn-video-link-upload">
                                                {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerDownloadVideoqualityurl0')->attribute('data-hidden-input', 'file_url_download_videoquality0') }}
                                                {{ html()->text('download_videoquality_input')->class('form-control')->placeholder('Select File')->attribute('aria-label', 'Download Video Quality File')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerDownloadVideoqualityurl0')->attribute('data-hidden-input', 'file_url_download_videoquality0') }}
                                            </div>
                                            <div class="mt-3" id="selectedImageContainerDownloadVideoqualityurl0"></div>
                                            {{ html()->hidden('download_quality_video[]')->id('file_url_download_videoquality0')->value(old('download_quality_video.0', ''))->attribute('data-validation', 'iq_video_quality') }}
                                        </div>
                                        <div class="col-sm-1 d-flex justify-content-center align-items-center mt-5">
                                            <button type="button"
                                                class="btn btn-secondary-subtle btn-sm remove-download-video-input d-none"><i
                                                    class="ph ph-trash align-middle"></i></button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="text-end">
                                <a id="add_more_download_video" class="btn btn-sm btn-primary"><i
                                        class="ph ph-plus-circle"></i> {{ __('episode.lbl_add_more') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mb-5">

        <button type="submit" class="btn btn-primary" id="submit-button">{{ __('messages.save') }}</button>
    </div>
    </form>
    @include('components.media-modal')
@endsection
@push('after-scripts')
    <script>
        // JavaScript to update character count dynamically
        function updateCharCount() {
            const metaTitleInput = document.getElementById('meta_title');
            const charCountElement = document.getElementById('meta-title-char-count');
            const charCount = metaTitleInput.value.length;
            charCountElement.textContent = `${charCount}/100 {{ __('messages.words') }}`;
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('seoForm');
            const submitButton = document.getElementById('submit-button');
            const seoCheckbox = document.getElementById('enableSeoIntegration');

            const metaTitle = document.getElementById('meta_title');
            const hiddenInputsContainer = document.getElementById('meta_keywords_hidden_inputs');
            const errorMsg = document.getElementById('meta_keywords_error');
            const tagifyInput = document.getElementById('meta_keywords_input');
            const tagifyWrapper = tagifyInput.closest('.tagify');
            const keywordInputs = hiddenInputsContainer.querySelectorAll('input[name="meta_keywords[]"]');
            const googleVerification = document.getElementById('google_site_verification');
            const canonicalUrl = document.getElementById('canonical_url');
            const shortDescription = document.getElementById('short_description');
            const seoImage = document.getElementById('seo_image_input');
            const seoImagePreview = document.getElementById('selectedSeoImage');
            const seoImageError = document.getElementById('seo_image_error');

            const metaKeywordsError = document.getElementById('meta_keywords_error');

            document.getElementById('enableSeoIntegration')?.addEventListener('change', function() {
                document.getElementById('seoFields').style.display = this.checked ? 'block' : 'none';
                if (this.checked) {
                    metaTitle.setAttribute('required', 'required');
                    tagifyInput.setAttribute('required', 'required');
                    googleVerification.setAttribute('required', 'required');
                    canonicalUrl.setAttribute('required', 'required');
                    shortDescription.setAttribute('required', 'required');
                    seoImage.setAttribute('required', 'required');

                } else {
                    metaTitle.removeAttribute('required');
                    tagifyInput.removeAttribute('required');
                    googleVerification.removeAttribute('required');
                    canonicalUrl.removeAttribute('required');
                    shortDescription.removeAttribute('required');
                    seoImage.removeAttribute('required');


                    // Clear the SEO fields when unchecked
                    metaTitle.value = '';
                    tagifyInput.value = '';
                    googleVerification.value = '';
                    canonicalUrl.value = '';
                    shortDescription.value = '';
                    seoImage.value = '';
                }
            });

        });
    </script>

    <script src="{{ asset('js/tagify.min.js') }}"></script>



    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Meta Title Character Count
            const metaTitleInput = document.getElementById('meta_title');
            const metaTitleCharCountDisplay = document.getElementById('meta-title-char-count');

            // Meta Description Character Count
            const metaDescriptionInput = document.getElementById('short_description');
            const metaDescriptionCharCountDisplay = document.getElementById('meta-description-char-count');

            // Function to update character count
            function updateCharCount(inputField, charCountDisplay, limit) {
                const currentLength = inputField.value.length;
                charCountDisplay.textContent = `${currentLength}/${limit}`;

                // Change color based on length
                charCountDisplay.style.color = currentLength > limit ? 'red' : 'green';

                // Update character count as the user types
                inputField.addEventListener('input', function() {
                    const currentLength = inputField.value.length;
                    charCountDisplay.textContent = `${currentLength}/${limit}`;
                    charCountDisplay.style.color = currentLength > limit ? 'red' : 'green';
                });
            }

            // Update character count for Meta Title
            if (metaTitleInput && metaTitleCharCountDisplay) {
                updateCharCount(metaTitleInput, metaTitleCharCountDisplay, 100);
            }

            // Update character count for Meta Description
            if (metaDescriptionInput && metaDescriptionCharCountDisplay) {
                updateCharCount(metaDescriptionInput, metaDescriptionCharCountDisplay, 200);
            }

            // Meta Keywords with Tagify
            const input = document.querySelector('#meta_keywords_input');
            const hiddenContainer = document.getElementById('meta_keywords_hidden_inputs');

            if (input) {
                const placeholderText = input.getAttribute('data-placeholder') || input.getAttribute('placeholder') || '{{ __('placeholder.lbl_meta_keywords') }}';
                input.setAttribute('data-placeholder', placeholderText);
                const tagify = new Tagify(input, {
                    placeholder: placeholderText,
                    originalInputValueFormat: (valuesArr) => JSON.stringify(valuesArr.map(item => item
                        .value)) // Format as JSON string
                });

                // Sync hidden inputs and update meta tag dynamically
                function syncHiddenInputs() {
                    if (hiddenContainer) {
                        hiddenContainer.innerHTML = ''; // Clear existing hidden inputs

                        // Loop through each tag and create a hidden input field
                        tagify.value.forEach(item => {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name =
                                'meta_keywords[]'; // Name the inputs for proper array submission
                            hiddenInput.value = item.value; // Value of the hidden input is the tag value
                            hiddenContainer.appendChild(hiddenInput);
                        });

                        // Update meta tag content dynamically
                        const metaTag = document.getElementById('dynamicMetaKeywords');
                        if (metaTag) {
                            const keywords = tagify.value.map(item => item.value).join(
                                ', '); // Join the tag values into a string
                            metaTag.setAttribute('content', keywords); // Set the content attribute of the meta tag
                        }
                    }
                }

                // Call syncHiddenInputs when tags are added, removed, or changed
                tagify.on('add', syncHiddenInputs);
                tagify.on('remove', syncHiddenInputs);
                tagify.on('change', syncHiddenInputs);

                // Optional: Restore old input if validation failed
                @if (old('meta_keywords'))
                    // Ensure the old value is in array format before passing it to Tagify
                    const oldTags = Array.isArray(@json(old('meta_keywords'))) ? @json(old('meta_keywords')) :
                        JSON.parse(@json(old('meta_keywords')));
                    tagify.addTags(oldTags); // Restores tags if there's any old input
                @endif
            }
        });
    </script>

    <script>
        tinymce.init({
            selector: '#description',
            plugins: 'link image code',
            toolbar: 'undo redo | styleselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify | removeformat | code | image',
            setup: function(editor) {
                const syncDescriptionErrors = function() {
                    const textContent = (editor.getContent({ format: 'text' }) || '').trim();
                    const $textarea = $('#description'); const $error = $('#desc-error');
                    if (!textContent) { /* only show after form submit */ } else {
                        $textarea.removeClass('is-invalid'); $error.hide();
                        try {
                            const errs = window.__episodeEditCurrentErrors || {};
                            if (errs.description) delete errs.description;
                            Object.keys(errs).forEach(function(k) { if (k === 'description' || (k && k.startsWith('description.'))) delete errs[k]; });
                            window.__episodeEditCurrentErrors = errs;
                            if (window.showErrorCountOnTabs && window.__episodeEditTabFields) { window.showErrorCountOnTabs(errs, window.__episodeEditTabFields); }
                        } catch (e) {}
                    }
                };
                editor.on('change keyup SetContent Paste Undo Redo', syncDescriptionErrors);
                editor.on('init', syncDescriptionErrors);
            }
        });

        $(document).on('click', '.variable_button', function() {
            const textarea = $(document).find('.tab-pane.active');
            const textareaID = textarea.find('textarea').attr('id');
            tinyMCE.activeEditor.selection.setContent($(this).attr('data-value'));
        });

        document.addEventListener('DOMContentLoaded', function() {

            function handleTrailerUrlTypeChange(selectedValue) {
                var FileInput = document.getElementById('url_file_input');
                var URLInput = document.getElementById('url_input');
                var EmbedInput = document.getElementById('embedded_input');
                var trailerfile = document.querySelector('input[name="trailer_video"]');
                var URLInputField = document.querySelector('input[name="trailer_url"]');
                var IframeField = document.querySelector('textarea[name="trailer_url_embedded"]');

                if (FileInput) FileInput.classList.add('d-none');
                if (URLInput) URLInput.classList.add('d-none');
                if (EmbedInput) EmbedInput.classList.add('d-none');
                if (URLInputField) URLInputField.removeAttribute('required');
                if (trailerfile) trailerfile.removeAttribute('required');
                if (IframeField) IframeField.removeAttribute('required');

                if (selectedValue === 'Local') {
                    if (FileInput) FileInput.classList.remove('d-none');
                    if (trailerfile) trailerfile.setAttribute('required', 'required');
                } else if (selectedValue === 'Embedded') {
                    if (EmbedInput) EmbedInput.classList.remove('d-none');
                    if (IframeField) IframeField.setAttribute('required', 'required');
                } else if (selectedValue === 'URL' || selectedValue === 'YouTube' || selectedValue === 'HLS' ||
                    selectedValue === 'x265' || selectedValue === 'Vimeo') {
                    if (URLInput) URLInput.classList.remove('d-none');
                    if (URLInputField) URLInputField.setAttribute('required', 'required');
                }
            }

            // function validateTrailerUrlInput() {
            //         var URLInput = document.querySelector('input[name="trailer_url"]');
            //         var urlPatternError = document.getElementById('trailer-pattern-error');
            //         selectedValue = document.getElementById('trailer_url_type').value;
            //         if (selectedValue === 'YouTube') {
            //             urlPattern = /^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/;
            //             urlPatternError.innerText = '';
            //             urlPatternError.innerText='Please enter a valid Youtube URL'
            //         } else if (selectedValue === 'Vimeo') {
            //             urlPattern = /^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^/]+\/videos\/)?\d+)(\/.*)?$/;
            //             urlPatternError.innerText = '';
            //             urlPatternError.innerText='Please enter a valid Vimeo URL'
            //         } else {
            //             // General URL pattern for other types
            //             urlPattern = /^https?:\/\/.+$/;
            //              urlPatternError.innerText='Please enter a valid URL'
            //         }
            //             if (!urlPattern.test(URLInput.value)) {
            //                 urlPatternError.style.display = 'block';
            //                 return false;
            //             } else {
            //                 urlPatternError.style.display = 'none';
            //                 return true;
            //             }
            //         }

            var initialSelectedValue = document.getElementById('trailer_url_type').value;
            handleTrailerUrlTypeChange(initialSelectedValue);
            $('#trailer_url_type').change(function() {
                var selectedValue = $(this).val();
                handleTrailerUrlTypeChange(selectedValue);
            });

            // var URLInput = document.querySelector('input[name="trailer_url"]');
            //     if (URLInput) {
            //         URLInput.addEventListener('input', function() {

            //             validateTrailerUrlInput();
            //         });
            //     }
        });

        function showPlanSelection() {
            const planSelection = document.getElementById('planSelection');
            const payPerViewFields = document.getElementById('payPerViewFields');
            const planIdSelect = document.getElementById('plan_id');
            const priceInput = document.querySelector('input[name="price"]');
            const selectedAccess = document.querySelector('input[name="access"]:checked');

            if (!selectedAccess) return;

            const value = selectedAccess.value;

            // Handle visibility and required attributes
            if (value === 'paid') {
                planSelection.classList.remove('d-none');
                payPerViewFields.classList.add('d-none');
                planIdSelect.setAttribute('required', 'required');
                priceInput.removeAttribute('required');
            } else if (value === 'pay-per-view') {
                planSelection.classList.add('d-none');
                payPerViewFields.classList.remove('d-none');
                planIdSelect.removeAttribute('required');
                priceInput.setAttribute('required', 'required');
            } else {
                planSelection.classList.add('d-none');
                payPerViewFields.classList.add('d-none');
                planIdSelect.removeAttribute('required');
                priceInput.removeAttribute('required');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initial setup
            showPlanSelection();

            // Event listeners for movie access radio buttons
            const accessRadios = document.querySelectorAll('input[name="access"]');
            accessRadios.forEach(function(radio) {
                radio.addEventListener('change', showPlanSelection);
            });
        });

        function toggleAccessDuration(value) {
            const accessDuration = document.getElementById('accessDurationWrapper');
            accessDuration.classList.toggle('d-none', value !== 'rental');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const purchaseType = document.getElementById('purchase_type');
            if (purchaseType) {
                toggleAccessDuration(purchaseType.value);
                purchaseType.addEventListener('change', function() {
                    toggleAccessDuration(this.value);
                });
            }
        });



        $(document).ready(function() {

            $('#GenrateDescription').on('click', function(e) {

                e.preventDefault();

                var description = $('#description').val();
                var name = $('#name').val();
                var tvshow = $('#entertainment_id').val();

                var generate_discription = "{{ route('backend.seasons.generate-description') }}";
                generate_discription = generate_discription.replace('amp;', '');

                if (!description && !name) {
                    return;
                }

                tinymce.get('description').setContent('Loading...');

                $.ajax({

                    url: generate_discription,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        description: description,
                        name: name,
                        tvshow: tvshow,
                    },
                    success: function(response) {

                        tinymce.get('description').setContent('');

                        if (response.success) {

                            var data = response.data;

                            tinymce.get('description').setContent(data);

                        } else {
                            $('#error_message').text(response.message ||
                                'Failed to get Description.');
                        }
                    },
                    error: function(xhr) {
                        $('#error_message').text('Failed to get Description.');
                        tinymce.get('description').setContent('');

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            $('#error_message').text(xhr.responseJSON.message);
                        } else {
                            $('#error_message').text(
                                'An error occurred while fetching the movie details.');
                        }
                    }
                });
            });
        });

        function removeImage(hiddenInputId, removedFlagId) {
            var container = document.getElementById('selectedImageContainer2');
            var hiddenInput = document.getElementById(hiddenInputId);
            var removedFlag = document.getElementById(removedFlagId);

            container.innerHTML = '';
            hiddenInput.value = '';
            removedFlag.value = 1;
        }

        function removeTvImage(hiddenInputId, removedFlagId) {
            var container = document.getElementById('selectedImageContainerTv');
            var hiddenInput = document.getElementById(hiddenInputId);
            var removedFlag = document.getElementById(removedFlagId);

            container.innerHTML = '';
            hiddenInput.value = '';
            removedFlag.value = 1;
        }

        function calculateTotal() {
            const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
            const discount = parseFloat(document.querySelector('input[name="discount"]').value) || 0;
            let total = price;

            if (discount > 0 && discount < 100) {
                total = price - ((price * discount) / 100);
            }

            document.getElementById('total_amount').value = total.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const priceInput = document.querySelector('input[name="price"]');
            const discountInput = document.querySelector('input[name="discount"]');

            priceInput.addEventListener('input', calculateTotal);
            discountInput.addEventListener('input', calculateTotal);

            // Trigger initial calculation if old values exist
            calculateTotal();
        });

        $(document).on('click', '.variable_button', function() {
            const textarea = $(document).find('.tab-pane.active');
            const textareaID = textarea.find('textarea').attr('id');
            tinyMCE.activeEditor.selection.setContent($(this).attr('data-value'));
        });

        function validateEmbedInput(inputId, errorId) {
            const embedInput = document.getElementById(inputId);
            const embedError = document.getElementById(errorId);
            const value = embedInput?.value.trim() || '';

            // Error messages from Laravel translations
            const msgRequired = "{{ __('messages.embed_code_required') }}";
            const msgInvalid = "{{ __('messages.embed_code_invalid') }}";
            const msgOnlyYoutubeVimeo = "{{ __('messages.embed_code_only_youtube_vimeo') }}";

            // Clear previous error
            if (embedError) embedError.style.display = 'none';
            if (embedInput) embedInput.classList.remove('is-invalid');

            if (!embedInput || value === '') {
                return showError(msgRequired);
            }

            // Extract iframe src
            const iframeMatch = value.match(/<iframe\b[^>]*\bsrc\s*=\s*["'“”‘’](.*?)["'“”‘’][^>]*>[\s\S]*?<\/iframe>/i);
            if (!iframeMatch) {
                return showError(msgInvalid);
            }

            const src = iframeMatch[1];



            return true;

            function showError(message) {
                if (embedError) embedError.innerText = message;
                if (embedError) embedError.style.display = 'block';
                if (embedInput) embedInput.classList.add('is-invalid');
                return false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Live validation
            const embedInput = document.getElementById('trailer_url_embedded');
            if (embedInput) {
                embedInput.addEventListener('input', () => validateEmbedInput('trailer_url_embedded',
                    'trailer-pattern-error'));
            }

            // Form validation on button click
            const submitButton = document.getElementById('submit-button');
            if (submitButton) {
                submitButton.addEventListener('click', function(e) {
                    const trailerType = document.getElementById('trailer_url_type')?.value;

                    if (trailerType === 'Embedded') {
                        if (!validateEmbedInput('trailer_url_embedded', 'trailer-pattern-error')) {
                            e.preventDefault(); // Prevent form submission
                        }
                    }
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr('.min-datetimepicker-time', {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i", // Format for time (24-hour format)
                time_24hr: true // Enable 24-hour format

            });

            flatpickr('.datetimepicker', {
                dateFormat: "Y-m-d",
            });

            // Initialize skip intro time pickers
            flatpickr("#start_time", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i:S",
                time_24hr: true,
                enableSeconds: true
            });

            flatpickr("#end_time", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i:S",
                time_24hr: true,
                enableSeconds: true
            });
        });

        tinymce.init({
            selector: '#description',
            plugins: 'link image code',
            toolbar: 'undo redo | styleselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify | removeformat | code | image',
            setup: function(editor) {
                const syncDescriptionErrors = function() {
                    const textContent = (editor.getContent({ format: 'text' }) || '').trim();
                    const $textarea = $('#description'); const $error = $('#desc-error');
                    if (!textContent) { /* only show after form submit */ } else {
                        $textarea.removeClass('is-invalid'); $error.hide();
                        try {
                            const errs = window.__episodeEditCurrentErrors || {};
                            if (errs.description) delete errs.description;
                            Object.keys(errs).forEach(function(k) { if (k === 'description' || (k && k.startsWith('description.'))) delete errs[k]; });
                            window.__episodeEditCurrentErrors = errs;
                            if (window.showErrorCountOnTabs && window.__episodeEditTabFields) { window.showErrorCountOnTabs(errs, window.__episodeEditTabFields); }
                        } catch (e) {}
                    }
                };
                editor.on('change keyup SetContent Paste Undo Redo', syncDescriptionErrors);
                editor.on('init', syncDescriptionErrors);
            }
        });

        $(document).on('click', '.variable_button', function() {
            const textarea = $(document).find('.tab-pane.active');
            const textareaID = textarea.find('textarea').attr('id');
            tinyMCE.activeEditor.selection.setContent($(this).attr('data-value'));
        });



        document.addEventListener('DOMContentLoaded', function() {

            function handleTrailerUrlTypeChange(selectedValue) {
                var FileInput = document.getElementById('url_file_input');
                var URLInput = document.getElementById('url_input');
                var trailerfile = document.querySelector('input[name="trailer_video"]');
                var regularInput = document.getElementById('regular_input');
                var embeddedInput = document.getElementById('embedded_input');
                var URLInputField = document.querySelector('input[name="trailer_url"]');
                var embeddedField = document.querySelector('textarea[name="trailer_url_embedded"]');

                if (FileInput) FileInput.classList.add('d-none');
                if (URLInput) URLInput.classList.add('d-none');
                if (regularInput) regularInput.classList.add('d-none');
                if (embeddedInput) embeddedInput.classList.add('d-none');

                var existingTrailerUrl = @json($data->trailer_url);

                if (selectedValue === 'Local') {
                    if (FileInput) FileInput.classList.remove('d-none');
                    if (trailerfile) trailerfile.setAttribute('required', 'required');
                    if (URLInputField) URLInputField.removeAttribute('required');
                    if (embeddedField) embeddedField.removeAttribute('required');
                } else if (selectedValue === 'Embedded') {
                    if (URLInput) URLInput.classList.remove('d-none');
                    if (embeddedInput) embeddedInput.classList.remove('d-none');
                    if (embeddedField) { embeddedField.setAttribute('required', 'required'); embeddedField.value = existingTrailerUrl; }
                    if (URLInputField) URLInputField.removeAttribute('required');
                    if (trailerfile) trailerfile.removeAttribute('required');
                } else if (selectedValue === 'URL' || selectedValue === 'YouTube' ||
                    selectedValue === 'HLS' || selectedValue === 'Vimeo' || selectedValue === 'x265') {
                    if (URLInput) URLInput.classList.remove('d-none');
                    if (regularInput) regularInput.classList.remove('d-none');
                    if (URLInputField) { URLInputField.setAttribute('required', 'required'); URLInputField.value = existingTrailerUrl; }
                    if (embeddedField) embeddedField.removeAttribute('required');
                    if (trailerfile) trailerfile.removeAttribute('required');
                }

                if (selectedValue === 'Embedded' && typeof validateTrailerEmbedCode === 'function') {
                    validateTrailerEmbedCode();
                }
            }

            // function validateTrailerUrlInput(type) {
            //     var URLInput = document.querySelector('input[name="trailer_url"]');
            //     var urlPatternError = document.getElementById('trailer-pattern-error');
            //     var urlPattern;

            //     if (!URLInput.value) {
            //         urlPatternError.style.display = 'none';
            //         return true;
            //     }

            //     switch(type) {
            //         case 'YouTube':
            //             urlPattern = /^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/;
            //             urlPatternError.innerText = 'Please enter a valid Youtube URL';
            //             break;
            //         case 'Vimeo':
            //             urlPattern = /^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^/]+\/videos\/)?\d+)(\/.*)?$/;
            //             urlPatternError.innerText = 'Please enter a valid Vimeo URL';
            //             break;
            //         case 'Embedded':
            //             urlPattern = /<iframe[\s\S]*?<\/iframe>/;
            //             urlPatternError.innerText = 'Please enter a valid iframe embed code';
            //             break;
            //         default:
            //             urlPattern = /^https?:\/\/.+$/;
            //             urlPatternError.innerText = 'Please enter a valid URL starting with http:// or https://';
            //     }

            //     if (!urlPattern.test(URLInput.value)) {
            //         urlPatternError.style.display = 'block';
            //         return false;
            //     }
            //     urlPatternError.style.display = 'none';
            //     return true;
            // }

            // Add validation function for embed code
            function validateTrailerEmbedCode() {
                var embedField = document.querySelector('textarea[name="trailer_url_embedded"]');
                var errorDiv = document.getElementById('trailer-pattern-error');

                if (!embedField.value) {
                    errorDiv.style.display = 'none';
                    return true;
                }

                // Basic iframe validation
                const iframePattern = /<iframe[\s\S]*?<\/iframe>/i;

                if (!iframePattern.test(embedField.value)) {
                    errorDiv.innerText = 'Please enter a valid iframe embed code';
                    errorDiv.style.display = 'block';
                    return false;
                }

                errorDiv.style.display = 'none';
                return true;
            }

            var initialSelectedValue = document.getElementById('trailer_url_type').value;
            handleTrailerUrlTypeChange(initialSelectedValue);
            $('#trailer_url_type').change(function() {
                var selectedValue = $(this).val();
                handleTrailerUrlTypeChange(selectedValue);
            });
        });

        // Function to validate numeric fields
        function validateNumericField(input, errorId) {
            const value = parseFloat(input.value);
            const errorElement = document.getElementById(errorId);

            if (isNaN(value) || value <= 0) {
                input.classList.add('is-invalid');
                errorElement.style.display = 'block';
                errorElement.textContent = "{{ __('messages.value_must_be_greater_than_zero') }}";
                return false;
            } else {
                input.classList.remove('is-invalid');
                errorElement.style.display = 'none';
                return true;
            }
        }

        // Function to validate discount field
        function validateDiscount(input) {
            const value = parseFloat(input.value);
            const errorElement = document.getElementById('discount-error');

            if (value < 1 || value > 99) {
                input.classList.add('is-invalid');
                errorElement.style.display = 'block';
                errorElement.textContent = "{{ __('messages.discount_must_be_between_zero_and_ninety_nine') }}";
                return false;
            } else {
                input.classList.remove('is-invalid');
                errorElement.style.display = 'none';
                return true;
            }
        }

        function validateAvailableForGreaterThanAccessDuration(availableInput, accessInput, errorId) {
            const availableValue = parseFloat(availableInput.value);
            const accessValue = parseFloat(accessInput.value);
            const errorElement = document.getElementById(errorId);
            const purchaseType = document.querySelector('select[name="purchase_type"]').value;

            // Run base numeric validation first
            const isValid = validateNumericField(availableInput, errorId);

            if (!isValid || isNaN(accessValue)) return;

            // Only validate if purchase type is rental
            if (purchaseType === 'rental') {
                if (availableValue <= accessValue) {
                    availableInput.classList.add('is-invalid');
                    errorElement.style.display = 'block';
                    errorElement.textContent = "{{ __('messages.available_for_must_be_greater_than_access_duration') }}";
                } else {
                    availableInput.classList.remove('is-invalid');
                    errorElement.style.display = 'none';
                }
            } else {
                // If not rental, just remove any existing error
                availableInput.classList.remove('is-invalid');
                errorElement.style.display = 'none';
            }
        }

        // Add blur event listeners to numeric fields
        const priceInput = document.querySelector('input[name="price"]');
        const accessDurationInput = document.querySelector('input[name="access_duration"]');
        const discountInput = document.querySelector('input[name="discount"]');
        const availableForInput = document.querySelector('input[name="available_for"]');

        if (priceInput) {
            priceInput.addEventListener('blur', function() {
                validateNumericField(this, 'price-error');
            });
        }

        if (accessDurationInput) {
            accessDurationInput.addEventListener('blur', function() {
                validateNumericField(this, 'access_duration-error');
            });
        }

        if (discountInput) {
            discountInput.addEventListener('blur', function() {
                validateDiscount(this);
            });
        }

        if (availableForInput) {
            availableForInput.addEventListener('blur', function() {
                validateNumericField(this, 'available_for-error');
            });
        }

        if (availableForInput && accessDurationInput) {
            availableForInput.addEventListener('blur', function() {
                validateAvailableForGreaterThanAccessDuration(availableForInput, accessDurationInput,
                    'available_for-error');
            });

            accessDurationInput.addEventListener('blur', function() {
                if (availableForInput.value.trim() !== '') {
                    validateAvailableForGreaterThanAccessDuration(availableForInput, accessDurationInput,
                        'available_for-error');
                }
            });
        }

        function showPlanSelection() {
            const planSelection = document.getElementById('planSelection');
            const payPerViewFields = document.getElementById('payPerViewFields');
            const planIdSelect = document.getElementById('plan_id');
            const priceInput = document.querySelector('input[name="price"]');
            const selectedAccess = document.querySelector('input[name="access"]:checked');
            const releaseDateField = document.querySelector('input[name="release_date"]').closest('.col-md-6');
            const releaseDateInput = document.querySelector('input[name="release_date"]');
            const downlaodstatusDataFeild = document.querySelector('input[name="download_status"]').closest('.col-md-6');
            const purchaseTypeSelect = document.querySelector('select[name="purchase_type"]');
            const accessDurationInput = document.querySelector('input[name="access_duration"]');
            const availableForInput = document.querySelector('input[name="available_for"]');


            if (!selectedAccess) return;

            const value = selectedAccess.value;

            // Handle visibility and required attributes
            if (value === 'paid') {
                planSelection.classList.remove('d-none');
                payPerViewFields.classList.add('d-none');
                planIdSelect.setAttribute('required', 'required');
                priceInput.removeAttribute('required');
                purchaseTypeSelect.required = false;
                accessDurationInput.required = false;
                availableForInput.required = false;
                releaseDateField.classList.remove('d-none');
                releaseDateInput.setAttribute('required', 'required');
                downlaodstatusDataFeild.classList.remove('d-none');
            } else if (value === 'pay-per-view') {
                planSelection.classList.add('d-none');
                payPerViewFields.classList.remove('d-none');
                planIdSelect.removeAttribute('required');
                priceInput.setAttribute('required', 'required');
                purchaseTypeSelect.required = true;
                accessDurationInput.required = purchaseTypeSelect.value === 'rental';
                availableForInput.required = true;
                releaseDateField.classList.add('d-none');
                releaseDateInput.removeAttribute('required');
                downlaodstatusDataFeild.classList.add('d-none');
            } else {
                planSelection.classList.add('d-none');
                payPerViewFields.classList.add('d-none');
                planIdSelect.removeAttribute('required');
                priceInput.removeAttribute('required');
                purchaseTypeSelect.required = false;
                accessDurationInput.required = false;
                availableForInput.required = false;
                releaseDateField.classList.remove('d-none');
                releaseDateInput.setAttribute('required', 'required');
                downlaodstatusDataFeild.classList.remove('d-none');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Initial setup
            showPlanSelection();

            // Event listeners for movie access radio buttons
            const accessRadios = document.querySelectorAll('input[name="access"]');
            accessRadios.forEach(function(radio) {
                radio.addEventListener('change', showPlanSelection);
            });
        });

        function toggleAccessDuration(value) {
            const accessDuration = document.getElementById('accessDurationWrapper');
            const accessDurationInput = document.querySelector('input[name="access_duration"]');
            const selectedAccess = document.querySelector('input[name="access"]:checked');

            if (value === 'rental') {
                accessDuration.classList.remove('d-none');
                // Only set required if pay-per-view is selected
                if (selectedAccess && selectedAccess.value === 'pay-per-view') {
                    accessDurationInput.required = true;
                }
            } else {
                accessDuration.classList.add('d-none');
                accessDurationInput.required = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const purchaseType = document.getElementById('purchase_type');
            if (purchaseType) {
                toggleAccessDuration(purchaseType.value);
                purchaseType.addEventListener('change', function() {
                    toggleAccessDuration(this.value);
                });
            }
        });


        function toggleQualitySection() {

            var enableQualityCheckbox = document.getElementById('enable_quality');
            var enableQualitySection = document.getElementById('enable_quality_section');

            if (enableQualityCheckbox.checked) {

                enableQualitySection.classList.remove('d-none');

            } else {

                enableQualitySection.classList.add('d-none');
            }
        }
        document.addEventListener('DOMContentLoaded', function() {
            toggleQualitySection();
        });

        document.addEventListener('DOMContentLoaded', function() {

            function handleVideoUrlTypeChange(selectedtypeValue) {
                var VideoFileInput = document.getElementById('video_file_input_section');
                var VideoURLInput = document.getElementById('video_url_input_section');
                var videofile = document.querySelector('input[name="video_file_input"]');
                var regularInput = document.getElementById('regular_video_input');
                var embeddedInput = document.getElementById('embedded_video_input');
                var URLInputField = document.querySelector('input[name="video_url_input"]');
                var embeddedField = document.querySelector('textarea[name="video_url_embedded"]');

                if (VideoFileInput) VideoFileInput.classList.add('d-none');
                if (VideoURLInput) VideoURLInput.classList.add('d-none');
                if (regularInput) regularInput.classList.add('d-none');
                if (embeddedInput) embeddedInput.classList.add('d-none');

                var existingVideoUrl = @json($data->video_url_input);

                if (selectedtypeValue === 'Local') {
                    if (VideoFileInput) VideoFileInput.classList.remove('d-none');
                    if (videofile) videofile.setAttribute('required', 'required');
                    if (URLInputField) URLInputField.removeAttribute('required');
                    if (embeddedField) embeddedField.removeAttribute('required');
                } else if (selectedtypeValue === 'Embedded') {
                    if (VideoURLInput) VideoURLInput.classList.remove('d-none');
                    if (embeddedInput) embeddedInput.classList.remove('d-none');
                    if (embeddedField) { embeddedField.setAttribute('required', 'required'); embeddedField.value = existingVideoUrl; }
                    if (URLInputField) URLInputField.removeAttribute('required');
                    if (videofile) videofile.removeAttribute('required');
                } else if (selectedtypeValue === 'URL' || selectedtypeValue === 'YouTube' ||
                    selectedtypeValue === 'HLS' || selectedtypeValue === 'Vimeo' || selectedtypeValue === 'x265') {
                    if (VideoURLInput) VideoURLInput.classList.remove('d-none');
                    if (regularInput) { regularInput.classList.remove('d-none'); }
                    if (URLInputField) { URLInputField.setAttribute('required', 'required'); URLInputField.value = existingVideoUrl; }
                    if (embeddedField) embeddedField.removeAttribute('required');
                    if (videofile) videofile.removeAttribute('required');
                }
            }

            // function validateVideoUrlInput(type) {
            //     var videourl = document.querySelector('input[name="video_url_input"]');
            //     var urlError = document.getElementById('url-error');
            //     var urlPatternError = document.getElementById('url-pattern-error');
            //     var urlPattern;

            //     if (!videourl.value) {
            //         urlError.style.display = 'block';
            //         urlPatternError.style.display = 'none';
            //         return false;
            //     }

            //     urlError.style.display = 'none';

            //     switch(type) {
            //         case 'YouTube':
            //             urlPattern = /^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/;
            //             urlPatternError.innerText = 'Please enter a valid Youtube URL';
            //             break;
            //         case 'Vimeo':
            //             urlPattern = /^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^/]+\/videos\/)?\d+)(\/.*)?$/;
            //             urlPatternError.innerText = 'Please enter a valid Vimeo URL';
            //             break;
            //         case 'Embedded':
            //             urlPattern = /<iframe[\s\S]*?<\/iframe>/;
            //             urlPatternError.innerText = 'Please enter a valid iframe embed code';
            //             break;
            //         default:
            //             urlPattern = /^https?:\/\/.+$/;
            //             urlPatternError.innerText = 'Please enter a valid URL starting with http:// or https://';
            //     }

            //     if (!urlPattern.test(videourl.value)) {
            //         urlPatternError.style.display = 'block';
            //         return false;
            //     }
            //     urlPatternError.style.display = 'none';
            //     return true;
            // }

            var initialSelectedValue = document.getElementById('video_upload_type').value;
            handleVideoUrlTypeChange(initialSelectedValue);
            $('#video_upload_type').change(function() {
                var selectedtypeValue = $(this).val();
                handleVideoUrlTypeChange(selectedtypeValue);
            });

            // Real-time validation while typing
            // var videourl = document.querySelector('input[name="video_url_input"]');
            // if (videourl) {
            //     videourl.addEventListener('input', function() {
            //         validateVideoUrlInput();
            //     });
            // }

        });

        function getSeasons(entertainmentId, selectedSeasonId = "") {
            var get_seasons_list = "{{ route('backend.seasons.index_list', ['entertainment_id' => '']) }}" +
                entertainmentId;
            get_seasons_list = get_seasons_list.replace('amp;', '');

            $.ajax({
                url: get_seasons_list,
                success: function(result) {

                    var formattedResult = result.map(function(season) {
                        return {
                            id: season.id,
                            text: season.name
                        };
                    });

                    $('#season_id').select2({
                        width: '100%',
                        placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.season')]) }}",
                        data: formattedResult
                    });

                    if (selectedSeasonId != "") {
                        $('#season_id').val(selectedSeasonId).trigger('change');
                    }
                    var seasonId = $('#season_id').val();
                    if (seasonId) {
                        getAccessType(entertainmentId, seasonId);
                        getNextEpisodeNumber(seasonId, entertainmentId);
                    }
                }
            });
        }

        $(document).ready(function() {
            $('#entertainment_id').change(function() {
                var entertainmentId = $(this).val();
                // Clear episode number when TV show changes
                $('#episode_number').val('');
                if (entertainmentId) {
                    $('#season_id').empty().select2({
                        width: '100%',
                        placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.season')]) }}"
                    });

                    getSeasons(entertainmentId);
                    // Call get-access-type when entertainment_id changes
                    var seasonId = $('#season_id').val();
                    if (seasonId) {
                        getAccessType(entertainmentId, seasonId);
                        getNextEpisodeNumber(seasonId, entertainmentId);
                    }
                } else {
                    $('#season_id').empty().select2({
                        width: '100%',
                        placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.season')]) }}"
                    });
                    $('#episode_number').val('');
                }
            });
        });

        $('#season_id').change(function() {
            var seasonId = $(this).val();
            var entertainmentId = $('#entertainment_id').val();

            $('#episode_number').val('');

            if (seasonId && entertainmentId) {
                // Call get-access-type when season_id changes
                getAccessType(entertainmentId, seasonId);
                getNextEpisodeNumber(seasonId, entertainmentId);
            } else if (seasonId) {
                getNextEpisodeNumber(seasonId, entertainmentId);
            }
        });

        $('#entertainment_id').change(function() {
            var entertainmentId = $(this).val();
            var seasonId = $('#season_id').val();

            if (seasonId && entertainmentId) {
                // Call get-access-type when season_id changes
                getAccessType(entertainmentId, seasonId);
                getNextEpisodeNumber(seasonId, entertainmentId);
            }
        });

        $(document).ready(function() {
            // Initially hide the pay-per-view option
            $('#pay-per-view').closest('label').hide();
            // Get the initial values of entertainment_id and season_id
            var entertainmentId = $('#entertainment_id').val();
            var seasonId = $('#season_id').val();

            // Call getAccessType if both IDs are available
            if (entertainmentId && seasonId) {
                getAccessType(entertainmentId, seasonId);
            }
        });

        function getAccessType(tvshowId, seasonId) {
            $.ajax({
                url: "{{ route('backend.episodes.get-access-type') }}",
                type: 'GET',
                data: {
                    tvshow_id: tvshowId,
                    season_id: seasonId
                },
                success: function(response) {
                    const isTvshowPaid = response.tvshow_access === 'paid';
                    const isSeasonPaid = response.season_access === 'paid';

                    if (isTvshowPaid || isSeasonPaid) {
                        $('#pay-per-view').closest('label').hide();
                    } else {
                        $('#pay-per-view').closest('label').show();
                    }
                }
            });
        }

        function getNextEpisodeNumber(seasonId, tvshowId) {
            if (!seasonId) {
                $('#episode_number').val('');
                return;
            }

            console.log("Requesting next episode number for:", { seasonId: seasonId, tvshowId: tvshowId });

            $.ajax({
                url: "{{ route('backend.episodes.get-next-episode-number') }}",
                type: 'GET',
                data: {
                    season_id: seasonId,
                    tvshow_id: tvshowId
                },
                success: function(response) {
                    console.log(response);
                    if (response.success && response.next_episode_number) {
                        $('#episode_number').val(response.next_episode_number);
                    }
                },
                error: function() {

                }
            });
        }

        function handleQualityTypeChange($container) {
            var type = $container.find('.video_quality_type').val();
            $container.find('.quality_video_input').addClass('d-none');
            $container.find('.quality_video_file_input').addClass('d-none');
            $container.find('.quality_video_embed_input').addClass('d-none');

            if (type === 'URL' || type === 'YouTube' || type === 'HLS' || type === 'Vimeo' || type === 'x265') {
                $container.find('.quality_video_input').removeClass('d-none');
            } else if (type === 'Local') {
                $container.find('.quality_video_file_input').removeClass('d-none');
            } else if (type === 'Embedded' || type === 'Embed') {
                $container.find('.quality_video_embed_input').removeClass('d-none');
            }
        }

        // Add these event handlers
        $(document).ready(function() {
            // Initial setup for existing containers
            $('.video-inputs-container').each(function() {
                handleQualityTypeChange($(this));
            });

            // Handle type changes
            $(document).on('change', '.video_quality_type', function() {
                var $container = $(this).closest('.video-inputs-container');
                handleQualityTypeChange($container);
            });

        });

        $(document).ready(function() {

            $('#GenrateDescription').on('click', function(e) {

                e.preventDefault();

                var description = $('#description').val();
                var name = $('#name').val();
                var tvshow = $('#entertainment_id').val();
                var season = $('#season_id').val();
                var type = null;


                var generate_discription = "{{ route('backend.episodes.generate-description') }}";
                generate_discription = generate_discription.replace('amp;', '');

                if (!description && !name) {
                    return;
                }

                tinymce.get('description').setContent('Loading...');

                $.ajax({

                    url: generate_discription,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        description: description,
                        name: name,
                        tvshow: tvshow,
                        season: season

                    },
                    success: function(response) {

                        tinymce.get('description').setContent('');

                        if (response.success) {

                            var data = response.data;

                            tinymce.get('description').setContent(data);

                        } else {
                            $('#error_message').text(response.message ||
                                'Failed to get Description.');
                        }
                    },
                    error: function(xhr) {
                        $('#error_message').text('Failed to get Description.');
                        tinymce.get('description').setContent('');

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            $('#error_message').text(xhr.responseJSON.message);
                        } else {
                            $('#error_message').text(
                                'An error occurred while fetching the movie details.');
                        }
                    }
                });
            });
        });

        function removeImage(hiddenInputId, removedFlagId) {
            var container = document.getElementById('selectedImageContainer2');
            var hiddenInput = document.getElementById(hiddenInputId);
            var removedFlag = document.getElementById(removedFlagId);

            container.innerHTML = '';
            hiddenInput.value = '';
            removedFlag.value = 1;
        }

        function removeTvImage(hiddenInputId, removedFlagId) {
            var container = document.getElementById('selectedImageContainerTv');
            var hiddenInput = document.getElementById(hiddenInputId);
            var removedFlag = document.getElementById(removedFlagId);

            container.innerHTML = '';
            hiddenInput.value = '';
            removedFlag.value = 1;
        }

        function calculateTotal() {
            const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
            const discount = parseFloat(document.querySelector('input[name="discount"]').value) || 0;
            let total = price;

            if (discount > 0 && discount < 100) {
                total = price - ((price * discount) / 100);
            }

            document.getElementById('total_amount').value = total.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const priceInput = document.querySelector('input[name="price"]');
            const discountInput = document.querySelector('input[name="discount"]');

            priceInput.addEventListener('input', calculateTotal);
            discountInput.addEventListener('input', calculateTotal);

            // Trigger initial calculation if old values exist
            calculateTotal();
        });

        $(document).on('click', '.variable_button', function() {
            const textarea = $(document).find('.tab-pane.active');
            const textareaID = textarea.find('textarea').attr('id');
            tinyMCE.activeEditor.selection.setContent($(this).attr('data-value'));
        });
        // Subtitle functionality
        $(document).ready(function() {
            // Toggle subtitle section
            function toggleSubtitleSection() {
                if ($('#enable_subtitle').is(':checked')) {
                    $('#subtitle_section').removeClass('d-none');
                    $('.subtitle-language').attr('required', true);
                    $('.subtitle-file').attr('required', true);
                } else {
                    $('#subtitle_section').addClass('d-none');
                    $('.subtitle-language').removeAttr('required');
                    $('.subtitle-file').removeAttr('required');
                }
            }

            function updateSubtitleRemoveButtons() {
                const rows = $('#subtitle-container .subtitle-row');
                const hideButtons = rows.length <= 1;
                rows.find('.remove-subtitle').each(function () {
                    if (hideButtons) {
                        $(this).addClass('d-none').attr('tabindex', '-1');
                    } else {
                        $(this).removeClass('d-none').removeAttr('tabindex');
                    }
                });
            }

            // Initial state
            toggleSubtitleSection();
            updateSubtitleRemoveButtons();

            // On change
            $('#enable_subtitle').on('change', toggleSubtitleSection);

            // Add new subtitle row
            let subtitleIndex = $('.subtitle-row').length;

            $('#add-subtitle').on('click', function() {
                var newRow = $(`
            <div class="subtitle-row row my-3">
                <div class="col-md-4">
                    <select name="subtitles[${subtitleIndex}][language]" class="form-control subtitle-language select2" required>
                        <option value="">{{ __('placeholder.lbl_select_language') }}</option>
                        @foreach ($subtitle_language as $language)
                            <option value="{{ $language->value }}">{{ $language->name }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback">{{ __('validation.required', ['attribute' => 'language']) }}</div>
                </div>
                <div class="col-md-4">
                    <input type="file" name="subtitles[${subtitleIndex}][subtitle_file]" class="form-control" required>
                    <div class="invalid-feedback">{{ __('validation.required', ['attribute' => 'subtitle file']) }}</div>
                </div>
                <div class="col-md-3">
                    <div class="form-check mt-3">
                        <input type="checkbox" name="subtitles[${subtitleIndex}][is_default]" class="form-check-input is-default-subtitle" value="1">
                        <label class="form-check-label">{{ __('movie.lbl_default_subtitle') }}</label>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm mt-4 remove-subtitle"><i class="ph ph-trash"></i></button>
                </div>
            </div>
        `);

                newRow.find('.subtitle-language').select2({
                    width: '100%',
                    placeholder: "{{ __('placeholder.lbl_select_language') }}",
                    allowClear: false
                });

                $('#subtitle-container').append(newRow);
                subtitleIndex++;
                updateSubtitleRemoveButtons();
            });

            // Remove subtitle row and mark for deletion if it has an id
            $(document).on('click', '.remove-subtitle', function() {
                var row = $(this).closest('.subtitle-row');
                var idInput = row.find('input[name*="[id]"]');

                if (idInput.length && idInput.val()) {
                    // If the subtitle has an ID, add it to the deleted_subtitles list
                    var deleted = $('#deleted_subtitles').val();
                    var ids = deleted ? deleted.split(',') : [];
                    ids.push(idInput.val());
                    $('#deleted_subtitles').val(ids.join(','));
                }

                row.remove();
                updateSubtitleRemoveButtons();
            });

            // Handle default subtitle selection
            $(document).on('change', '.is-default-subtitle', function() {
                if ($(this).is(':checked')) {
                    $('.is-default-subtitle').not(this).prop('checked', false);
                }
            });
        });

        function validateEmbedInput(inputId, errorId) {
            const embedInput = document.getElementById(inputId);
            const embedError = document.getElementById(errorId);
            const value = embedInput?.value.trim() || '';

            // Error messages from Laravel translations
            const msgRequired = "{{ __('messages.embed_code_required') }}";
            const msgInvalid = "{{ __('messages.embed_code_invalid') }}";
            const msgOnlyYoutubeVimeo = "{{ __('messages.embed_code_only_youtube_vimeo') }}";

            // Clear previous error
            if (embedError) embedError.style.display = 'none';
            if (embedInput) embedInput.classList.remove('is-invalid');

            if (!embedInput || value === '') {
                return showError(msgRequired);
            }

            // Extract iframe src
            const iframeMatch = value.match(/<iframe\b[^>]*\bsrc\s*=\s*["'“" '](.*?)["'“" '][^>]*>[\s\S]*?<\/iframe>/i);
            if (!iframeMatch) {
                return showError(msgInvalid);
            }

            const src = iframeMatch[1];


            return true;

            function showError(message) {
                if (embedError) embedError.innerText = message;
                if (embedError) embedError.style.display = 'block';
                if (embedInput) embedInput.classList.add('is-invalid');
                return false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Live validation
            [{
                    id: 'video_url_embedded',
                    error: 'url-pattern-error'
                },
                {
                    id: 'trailer_url_embedded',
                    error: 'trailer-pattern-error'
                }
            ].forEach(({
                id,
                error
            }) => {
                const input = document.getElementById(id);
                if (input) {
                    input.addEventListener('input', () => validateEmbedInput(id, error));
                }
            });

            // Form validation on button click
            const submitButton = document.getElementById('submit-button');
            if (submitButton) {
                submitButton.addEventListener('click', function(e) {
                    const videoType = document.getElementById('video_upload_type')?.value;
                    const trailerType = document.getElementById('trailer_url_type')?.value;

                    let trailerIsValid = true;
                    if (trailerType === 'Embedded') {
                        trailerIsValid = validateEmbedInput('trailer_url_embedded',
                            'trailer-pattern-error');
                    }
                    let videoIsValid = true;
                    if (videoType === 'Embedded') {
                        videoIsValid = validateEmbedInput('video_url_embedded', 'url-pattern-error');
                    }

                    if (!trailerIsValid || !videoIsValid) {
                        e.preventDefault(); // Prevent form submission
                    }
                });
            }
        });


        ////////////// DOWNLOAD TAB: mirror stream logic //////////////////
        document.addEventListener('DOMContentLoaded', function() {
            const downloadTab = document.getElementById('pills-download');
            if (!downloadTab) return;

            function toggleDownloadQualitySection() {
                const enable = document.getElementById('enable_download_quality');
                const section = document.getElementById('enable_download_quality_section');
                if (!enable || !section) return;
                if (enable.checked) {
                    section.classList.remove('d-none');
                } else {
                    section.classList.add('d-none');
                }
            }

            function handleDownloadVideoUrlTypeChange(selectedType) {
                const urlSection = downloadTab.querySelector('#video_url_input_section_download');
                const fileSection = downloadTab.querySelector('#video_file_input_section_download');

                const urlInput = downloadTab.querySelector('#video_url_input_download');
                const fileInput = downloadTab.querySelector('input[name="video_file_input_download"]');

                // hide all
                urlSection?.classList.add('d-none');
                fileSection?.classList.add('d-none');

                urlInput?.removeAttribute('required');
                fileInput?.removeAttribute('required');

                switch (selectedType) {
                    case 'Local':
                        fileSection?.classList.remove('d-none');
                        // fileInput?.setAttribute('required', 'required');
                        break;
                    case 'URL':
                        urlSection?.classList.remove('d-none');
                        // urlInput?.setAttribute('required', 'required');
                        break;
                }
            }

            function handleDownloadQualityTypeChange($container) {
                var type = $container.find('.download_quality_video_type').val();
                $container.find('.download_quality_video_input').addClass('d-none');
                $container.find('.download_quality_video_file_input').addClass('d-none');
                if (type === 'URL') {
                    $container.find('.download_quality_video_input').removeClass('d-none');
                } else if (type === 'Local') {
                    $container.find('.download_quality_video_file_input').removeClass('d-none');
                }
            }
            // init toggle section
            toggleDownloadQualitySection();
            const enableDownloadQuality = document.getElementById('enable_download_quality');
            if (enableDownloadQuality) {
                enableDownloadQuality.addEventListener('change', toggleDownloadQualitySection);
            }

            // init main download type
            const downloadUploadType = downloadTab.querySelector('#video_upload_type_download');
            if (downloadUploadType) {
                handleDownloadVideoUrlTypeChange(downloadUploadType.value);
                downloadUploadType.addEventListener('change', function() {
                    handleDownloadVideoUrlTypeChange(this.value);
                });
                $('#pills-download #video_upload_type_download').on('select2:select', function(e) {
                    handleDownloadVideoUrlTypeChange(e.target.value);
                });
            }

            // init existing quality row (first one)
            $('.download-video-inputs-container').each(function() {
                handleDownloadQualityTypeChange($(this));
            });

            // change event for dynamic rows
            $(document).on('change', '.download_quality_video_type', function() {
                var $container = $(this).closest('.download-video-inputs-container');
                handleDownloadQualityTypeChange($container);
            });
        });


        $(function() {
            function getDownloadCheckbox() {
                return $('input[type="checkbox"][name="download_status"]');
            }

            function applyVisibilityFromCheckbox() {
                const isChecked = getDownloadCheckbox().is(':checked');
                $('#download-tab-wrapper, #pills-download').toggleClass('d-none', !isChecked);
            }
            // initial and on window load
            applyVisibilityFromCheckbox();
            $(window).on('load', applyVisibilityFromCheckbox);
            // change handler
            $(document).on('change', 'input[type="checkbox"][name="download_status"]', function() {
                console.log(this.checked);
                applyVisibilityFromCheckbox();
            });
        });

        const episodeFieldLabels = {
            'name': '{{ __('movie.lbl_name') }}',
            'description': '{{ __('movie.lbl_description') }}',
            'entertainment_id': '{{ __('season.lbl_tv_shows') }}',
            'season_id': '{{ __('episode.lbl_season') }}',
            'duration': '{{ __('movie.lbl_duration') }}',
            'start_time': '{{ __('messages.lbl_skip_intro_start_time') }}',
            'end_time': '{{ __('messages.lbl_skip_intro_end_time') }}',
            'IMDb_rating': '{{ __('movie.lbl_imdb_rating') }}',
            'content_rating': '{{ __('movie.lbl_content_rating') }}',
            'release_date': '{{ __('movie.lbl_release_date') }}',
            'trailer_url_type': '{{ __('movie.lbl_trailer_url_type') }}',
            'trailer_url': '{{ __('movie.lbl_trailer_url') }}',
            'trailer_video': '{{ __('movie.lbl_trailer_video') }}',
            'trailer_embedded': '{{ __('movie.lbl_embed_code') }}',
            'video_upload_type': '{{ __('movie.lbl_video_upload_type') }}',
            'video_url_input': '{{ __('movie.video_url_input') }}',
            'video_file_input': '{{ __('movie.video_file_input') }}',
            'embedded': '{{ __('movie.lbl_embed_code') }}',
            'enable_quality': '{{ __('movie.lbl_enable_quality') }}',
            'video_quality_type': '{{ __('movie.lbl_video_upload_type') }}',
            'video_quality': '{{ __('movie.lbl_video_quality') }}',
            'enable_subtitle': '{{ __('movie.lbl_enable_subtitle') }}',
            'subtitles': '{{ __('movie.lbl_subtitle_file') }}',
            'meta_title': '{{ __('messages.lbl_meta_title') }}',
            'meta_keywords': '{{ __('messages.lbl_meta_keywords') }}',
            'meta_description': '{{ __('messages.lbl_meta_description') }}',
            'canonical_url': '{{ __('messages.lbl_canonical_url') }}',
            'google_site_verification': '{{ __('messages.lbl_google_site_verification') }}',
            'short_description': '{{ __('messages.lbl_short_description') }}',
            'seo_image': '{{ __('messages.lbl_seo_image') }}',
            'price': '{{ __('messages.lbl_price') }}',
            'purchase_type': '{{ __('messages.purchase_type') }}',
            'access_duration': '{{ __('messages.lbl_access_duration') }}',
            'available_for': '{{ __('messages.lbl_available_for') }}',
            'discount': '{{ __('messages.lbl_discount') }}',
            'plan_id': '{{ __('movie.lbl_select_plan') }}',
            'video_upload_type_download': '{{ __('movie.lbl_quality_video_download_type') }}',
            'video_url_input_download': '{{ __('movie.download_url') }}',
            'video_file_input_download': '{{ __('messages.lbl_download_file') }}',
            'enable_download_quality': '{{ __('movie.lbl_enable_quality') }}',
            'quality_video_download_type': '{{ __('movie.lbl_quality_video_download_type') }}',
            'video_download_quality': '{{ __('movie.lbl_video_download_quality') }}',
            'download_quality_video_url': '{{ __('movie.download_url') }}',
            'download_quality_video': '{{ __('messages.lbl_download_file') }}',
            'subtitles.*.language': '{{ __('messages.lbl_subtitle_language') }}',
            'subtitles.*.subtitle_file': '{{ __('movie.lbl_subtitle_file') }}',
            'video_download_quality.*': '{{ __('movie.lbl_video_download_quality') }}',
            'download_quality_video_url.*': '{{ __('movie.download_url') }}',
            'download_quality_video.*': '{{ __('messages.lbl_download_file') }}',
            'enable_quality': '{{ __('movie.lbl_enable_quality') }}',
            'enable_download_quality': '{{ __('movie.lbl_enable_quality') }}',
            'video_upload_type_download': '{{ __('movie.lbl_quality_video_download_type') }}',
            'video_url_input_download': '{{ __('movie.download_url') }}',
            'video_file_input_download': '{{ __('messages.lbl_download_file') }}',
            'clips': '{{ __('messages.lbl_clips') }}',
            'download_info': '{{ __('movie.lbl_download_info') }}'

        };

        const episodeTabFields = {
            'pills-movie': ['name', 'description', 'entertainment_id', 'season_id', 'start_time',
                'end_time', 'trailer_url_type', 'trailer_url', 'trailer_video', 'trailer_embedded', 'price',
                'purchase_type', 'access_duration', 'available_for', 'discount', 'plan_id'
            ],
            'pills-basic': ['duration', 'IMDb_rating', 'content_rating', 'release_date'],
            'pills-quality': ['video_upload_type', 'video_url_input', 'video_file_input', 'video_url_embedded', 'enable_quality',
                'video_quality_type.*', 'video_quality.*', 'quality_video_url_input.*', 'quality_video.*', 'quality_video_embed_input.*'
            ],
            'pills-Subtitle': ['enable_subtitle', 'subtitles', 'subtitles.*'],
            'pills-seo': ['meta_title', 'meta_keywords', 'meta_description', 'canonical_url',
                'google_site_verification', 'short_description', 'seo_image'
            ],
            'pills-download': ['video_upload_type_download', 'video_url_input_download', 'video_file_input_download',
                'enable_download_quality', 'quality_video_download_type.*', 'video_download_quality.*',
                'download_quality_video_url.*', 'download_quality_video.*'
            ]
        };

        let currentErrors = {};
        window.__episodeEditCurrentErrors = currentErrors;
        window.__episodeEditTabFields = episodeTabFields;

        document.addEventListener('DOMContentLoaded', function() {
            // Clear any JavaScript-generated error messages when page loads with validation errors
            @if ($errors->any())
                // Remove JavaScript-generated "Only video files are allowed" errors
                const videoContainers = document.querySelectorAll('#selectedImageContainerVideourl, #selectedImageContainer4');
                videoContainers.forEach(function(container) {
                    if (container) {
                        const jsErrors = container.querySelectorAll('.text-danger');
                        jsErrors.forEach(function(err) {
                            if (err.textContent.includes('Only video files are allowed') ||
                                err.textContent.includes('Only image files are allowed')) {
                                err.remove();
                            }
                        });
                    }
                });

                const serverErrors = @json($errors->toArray());
                currentErrors = serverErrors || {};
                window.__episodeEditCurrentErrors = currentErrors;
                if (window.showValidationModal) window.showValidationModal(serverErrors, episodeFieldLabels);
                if (window.showErrorCountOnTabs) window.showErrorCountOnTabs(serverErrors, episodeTabFields);
            @endif
        });

        $('#form-submit').on('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            if (typeof tinymce !== 'undefined' && tinymce.get('description')) {
                formData.append('description', tinymce.get('description').getContent());
            }

            formData.append('_token', '{{ csrf_token() }}');

            $('#submit-button').prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm"></span> {{ trans('season.lbl_loading') }}');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.success) {
                        window.successSnackbar(response.message || 'Episode saved successfully');

                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                    } else {
                        $('#error_message').text(response.message ||
                            'An error occurred while saving the episode');
                        $('#submit-button').prop('disabled', false).html(
                            '{{ trans('messages.save') }}');
                    }
                },
                error: function(xhr) {
                    $('#submit-button').prop('disabled', false).html('{{ trans('messages.save') }}');

                    if (xhr.responseJSON?.errors) {
                        currentErrors = xhr.responseJSON.errors || {};
                        window.__episodeEditCurrentErrors = currentErrors;
                        if (window.showValidationModal) {
                            window.showValidationModal(xhr.responseJSON.errors, episodeFieldLabels);
                        }
                        if (window.showErrorCountOnTabs) {
                            window.showErrorCountOnTabs(xhr.responseJSON.errors, episodeTabFields);
                        }

                        Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                            $(`[name="${key}"]`).addClass('is-invalid');

                            let errorDivId = `${key}-error`;
                            if (key === 'meta_title') {
                                errorDivId = 'meta_title_error';
                            }
                            const errorDiv = $(`#${errorDivId}`);
                            if (errorDiv.length) {
                                errorDiv.text(xhr.responseJSON.errors[key][0]).show();
                            }
                        });
                    } else {
                        $('#error_message').text(xhr.responseJSON?.message ||
                            'An error occurred while saving the episode');
                    }
                }
            });
        });

        function fieldHasValue(el) {
            if (!el) return false;
            if (el.type === 'checkbox') return el.checked;
            if (el.tagName === 'SELECT' && el.multiple) { return (el.selectedOptions && el.selectedOptions.length) > 0; }
            return String(el.value ?? '').trim() !== '';
        }

        function getArrayFieldIndex(el, name) {
            try {
                if (!el || !name || !/\[\]$/.test(String(name))) return null;
                var nodes = []; document.querySelectorAll('#form-submit [name]').forEach(function(n) { if (n.getAttribute('name') === name) nodes.push(n); });
                var idx = nodes.indexOf(el); return idx >= 0 ? idx : null;
            } catch (e) { return null; }
        }

        function clearFieldValidation(el) {
            if (!el) return;
            var name = el.getAttribute('name'); if (!name) return;
            var base = String(name).split('[')[0]; var fieldKey = String(name).replace(/\[\]$/, '');
            $(el).removeClass('is-invalid');
            var $select2 = $(el).next('.select2-container'); if ($select2.length) $select2.find('.select2-selection').removeClass('is-invalid');
            var errId = base === 'trailer_url' ? 'trailer-url-error' : (base === 'trailer_embedded' ? 'trailer-embed-error' : (base === 'trailer_url_embedded' ? 'trailer-pattern-error' : (base === 'trailer_video' ? 'trailer-file-error' : (base === 'video_upload_type' ? 'video-upload-type-error' : (base === 'video_url_input' ? 'url-error' : (base === 'video_file_input' ? 'file-error' : (base === 'embedded' ? 'embed-error' : (base === 'video_url_embedded' ? 'url-pattern-error' : (base === 'meta_title' ? 'meta_title_error' : base + '-error')))))))));
            var errEl = document.getElementById(errId); if (errEl) errEl.style.display = 'none';
            if (base === 'trailer_url') { var pe = document.getElementById('trailer-pattern-error'); if (pe) pe.style.display = 'none'; }
            if (base === 'video_url_input') { var upe = document.getElementById('url-pattern-error'); if (upe) upe.style.display = 'none'; }
            var $sibs = $(el).siblings('.invalid-feedback'); if ($sibs.length) $sibs.hide(); else $(el).closest('.col-md-3, .col-md-4, .col-md-6, .col-lg-3, .col-lg-4, .col-lg-6, .col-lg-12, .mb-3').find('.invalid-feedback').first().hide();

            var errs = window.__episodeEditCurrentErrors || currentErrors || {};
            var idx = getArrayFieldIndex(el, name); var changed = false;
            if (errs[fieldKey]) { delete errs[fieldKey]; changed = true; }
            if (errs[name]) { delete errs[name]; changed = true; }
            if (idx !== null) { var indexedKey = fieldKey + '.' + idx; if (errs[indexedKey]) { delete errs[indexedKey]; changed = true; } Object.keys(errs).forEach(function(k) { if (k.indexOf(indexedKey + '.') === 0) { delete errs[k]; changed = true; } }); }
            var dottedKey = String(name).replace(/\[(\d+)\]/g, '.$1').replace(/\[(\w+)\]/g, '.$1');
            if (dottedKey && dottedKey !== name && errs[dottedKey]) { delete errs[dottedKey]; changed = true; }
            if (changed) { currentErrors = errs; window.__episodeEditCurrentErrors = errs; if (window.showErrorCountOnTabs) window.showErrorCountOnTabs(errs, window.__episodeEditTabFields || episodeTabFields); }
        }

        $(document).on('change input', '#form-submit :input', function() { if (fieldHasValue(this)) clearFieldValidation(this); });
        $(document).on('select2:select select2:change', '#form-submit select.select2', function() { if (fieldHasValue(this)) clearFieldValidation(this); });

        // Live validation: when selecting a plan for a paid episode, ensure plan level >= TV show plan level
        $(document).on('change', '#plan_id', function () {
            var access = $('input[name="access"]:checked').val();
            if (access !== 'paid') {
                clearFieldValidation(this);
                return;
            }
            var selectedPlanId = $(this).val();
            if (!selectedPlanId) return;
            var tvshowId = $('#entertainment_id').val();
            if (!tvshowId) {
                clearFieldValidation(this);
                return;
            }
            var levelsJson = $(this).data('plan-levels') || {};
            var episodeLevel = parseInt(levelsJson[selectedPlanId] || 0, 10);
            $.getJSON("{{ url('api/tvshows') }}/" + tvshowId + "/plan-level", function (response) {
                if (response && typeof response.level !== 'undefined' && response.level !== null) {
                    var tvLevel = parseInt(response.level, 10);
                    if (!isNaN(tvLevel) && !isNaN(episodeLevel) && episodeLevel < tvLevel) {
                        var $error = $('#plan_id-error');
                        if ($error.length === 0) {
                            $error = $('<div class="invalid-feedback" id="plan_id-error"></div>').insertAfter($('#plan_id'));
                        }
                        $('#plan_id').addClass('is-invalid');
                        $error.text("{{ __('messages.episode_plan_must_be_same_or_higher_than_tvshow_plan') }}").show();
                    } else {
                        clearFieldValidation(document.getElementById('plan_id'));
                    }
                } else {
                    clearFieldValidation(document.getElementById('plan_id'));
                }
            }).fail(function () {
                clearFieldValidation(document.getElementById('plan_id'));
            });
        });

        $(document).on('change', '#form-submit input[type="file"]', function() { if (this.files && this.files.length > 0) clearFieldValidation(this); });
        $(document).on('change', '#form-submit input[type="hidden"]', function() { if (this.value && String(this.value).trim() !== '') clearFieldValidation(this); });
        document.getElementById('mediaSubmitButton') && document.getElementById('mediaSubmitButton').addEventListener('click', function() {
            setTimeout(function() { try { var hid = (window.mediaSelection && window.mediaSelection.getCurrentHiddenInput) ? window.mediaSelection.getCurrentHiddenInput() : ''; if (!hid) return; var el = document.getElementById(hid); if (el && fieldHasValue(el)) clearFieldValidation(el); } catch (e) {} }, 0);
        });

        // Initialize Select2 with localization
        $(document).ready(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    language: {
                        noResults: function() {
                            return "{{ __('messages.no_results_found') }}";
                        }
                    }
                });
            }
        });

        // Local helper to manage delete buttons for quality info rows
        function updateLocalDeleteButtons() {
            var rows = $('#video-inputs-container-parent .video-inputs-container');
            rows.each(function(index) {
                var btn = $(this).find('.remove-video-input');
                if (index === 0) {
                    btn.addClass('d-none');
                } else {
                    btn.removeClass('d-none');
                }
            });

            var downloadRows = $('#download-video-inputs-container-parent .download-video-inputs-container');
            downloadRows.each(function(index) {
                var btn = $(this).find('.remove-download-video-input');
                if (index === 0) {
                    btn.addClass('d-none');
                } else {
                    btn.removeClass('d-none');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Hook into Add More clicks
            $(document).on('click', '#add_more_video, #add_more_download_video', function() {
                setTimeout(updateLocalDeleteButtons, 500); // Give media.js time to append
            });
            // Hook into Remove clicks
            $(document).on('click', '.remove-video-input, .remove-download-video-input', function() {
                setTimeout(updateLocalDeleteButtons, 50);
            });
            
            // Initial run
            updateLocalDeleteButtons();
        });
    </script>


    <style>
        #video-inputs-container-parent .video-inputs-container:first-child .remove-video-input,
        #download-video-inputs-container-parent .download-video-inputs-container:first-child .remove-download-video-input {
            display: none !important;
        }

        .position-relative {
            position: relative;
        }

        .position-absolute {
            position: absolute;
        }

        .close-icon {
            top: -13px;
            left: 54px;
            background: rgba(255, 0, 0, 0.6);
            border: none;
            border-radius: 50%;
            color: white;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            line-height: 25px;
        }

        .required {
            color: red;
        }
    </style>
@endpush

@once
    <style>
        .media-thumb-10 {
            width: 10rem;
            height: 10rem;
        }
    </style>
@endonce
