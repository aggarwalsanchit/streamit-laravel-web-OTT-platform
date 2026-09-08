@extends('backend.layouts.app')


@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <x-back-button-component route="backend.seasons.index" />

    {{ html()->form('PUT', route('backend.seasons.update', $data->id))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->attribute('novalidate', 'novalidate')->class('requires-validation')->open() }}

    @csrf

    <ul class="nav nav-pills mb-3  movie-tab mt-5" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-movie-tab" data-bs-toggle="pill" data-bs-target="#pills-movie"
                type="button" role="tab" aria-controls="pills-movie"
                aria-selected="true">{{ __('messages.lbl_season_details') }}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-seo-tab" data-bs-toggle="pill" data-bs-target="#pills-seo" type="button"
                role="tab" aria-controls="pills-seo"
                aria-selected="false">{{ __('messages.lbl_seo_settings') }}</button>
        </li>
    </ul>


    <div class="tab-content" id="pills-tabContent">
        <div class="tab-pane fade show active" id="pills-movie" role="tabpanel" aria-labelledby="pills-movie-tab">
            <input type="hidden" name="id" value="{{ $data->id }}">
            <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                <h5>{{ __('messages.lbl_season_details') }} </h5>
            </div>
            <p class="text-danger" id="error_message"></p>
            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <input type="hidden" name="tmdb_id" id="tmdb_id" value="{{ $tmdb_id }}">
                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_poster'), 'poster')->class('form-label') }}
                            <div class="position-relative">
                                <div class="input-group btn-file-upload">
                                    {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer2')->attribute('data-hidden-input', 'file_url2')->style('height:13.5rem') }}

                                    {{ html()->text('image_input2')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Image Input 2')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer2')->attribute('data-hidden-input', 'file_url2')->attribute('aria-describedby', 'basic-addon1') }}
                                </div>

                                <div class="mb-3 uploaded-image" id="selectedImageContainer2">
                                    @if ($data->poster_url)
                                        <img id="selectedPosterImage"
                                            src="{{ old('poster_url', isset($data) ? $data->poster_url : '') }}"
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
                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_poster_tv'), 'poster_tv')->class('form-label') }}
                            <div class="position-relative">
                                <div class="input-group btn-file-upload">
                                    {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerTv')->attribute('data-hidden-input', 'file_urltv')->style('height:13.5rem') }}

                                    {{ html()->text('image_input2')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Image Input 2')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerTv')->attribute('data-hidden-input', 'file_urltv')->attribute('aria-describedby', 'basic-addon1') }}
                                </div>

                                <div class="mb-3 uploaded-image" id="selectedImageContainerTv">
                                    @if ($data->poster_tv_url)
                                        <img id="selectedPosterTvImage"
                                            src="{{ old('poster_tv_url', isset($data) ? $data->poster_tv_url : '') }}"
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
                        <div class="col-md-6 col-lg-4">
                            <div class="mb-3">
                                {{ html()->label(__('movie.lbl_name') . ' <span class="text-danger">*</span>', 'name')->class('form-label') }}
                                {{ html()->text('name')->attribute('value', $data->name)->placeholder(__('placeholder.lbl_season_name'))->class('form-control')->attribute('required', 'required') }}
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="name-error">{{ __('messages.name_field_required') }}</div>
                            </div>
                            <div class="mb-3">
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
                            <div class="mb-3">
                                {{ html()->label(__('movie.lbl_trailer_url_type') . ' <span class="text-danger">*</span>', 'type')->class('form-label') }}
                                {{ html()->select(
                                        'trailer_url_type',
                                        $upload_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_type'), ''),
                                        old('trailer_url_type', $data->trailer_url_type ?? ''), // Set '' as the default value
                                    )->class('form-control select2')->id('trailer_url_type') }}
                                @error('trailer_url_type')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="name-error">{{ __('messages.trailer_type_field_required') }}</div>

                            </div>
                            <div class="d-none" id="url_input">
                                {{ html()->label(__('movie.lbl_trailer_url') . ' <span class="text-danger">*</span>', 'trailer_url')->class('form-label') }}
                                {{ html()->text('trailer_url')->attribute('value', $data->trailer_url)->placeholder(__('placeholder.lbl_trailer_url'))->class('form-control') }}
                                @error('trailer_url')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="trailer-url-error">{{ __('messages.video_url_field_required') }}</div>
                                <div class="invalid-feedback" id="trailer-pattern-error" style="display:none;">
                                    Please enter a valid URL starting with http:// or https://.
                                </div>
                            </div>
                            <div class="d-none" id="embed_input">
                                {{ html()->label(__('movie.lbl_embed_code') . ' <span class="text-danger">*</span>', 'trailer_embedded')->class('form-label') }}
                                {{ html()->textarea('trailer_embedded')->placeholder('<iframe ...></iframe>')->class('form-control')->id('trailer_embedded')->rows(4)->value(old('trailer_embedded', $data->trailer_url_type === 'Embedded' ? $data->trailer_url : '')) }}
                                @error('trailer_embedded')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="trailer-embed-error"></div>
                            </div>
                            <div class="d-none" id="url_file_input">
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

                                <div class="input-group btn-video-link-upload mb-3">
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
                        {{-- Access fields commented out - access is now inherited from parent TV show --}}
                        {{-- <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_movie_access'), 'movie_access')->class('form-label') }}
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                                    <div>
                                        <input class="form-check-input" type="radio" name="access" id="paid"
                                            value="paid" onchange="showPlanSelection(this.value === 'paid')"
                                            {{ $data->access == 'paid' ? 'checked' : '' }} checked>
                                        <span class="form-check-label">{{ __('movie.lbl_paid') }}</span>
                                    </div>
                                </label>
                                <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                                    <div>
                                        <input class="form-check-input" type="radio" name="access" id="free"
                                            value="free" onchange="showPlanSelection(this.value === 'paid')"
                                            {{ $data->access == 'free' ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ __('movie.lbl_free') }}</span>
                                    </div>
                            </div>
                            </lable>

                            @error('access')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-12 row g-3 mt-2 {{ $data->access == 'pay-per-view' ? '' : 'd-none' }}"
                            id="payPerViewFields">

                            <div class="col-md-4">
                                {{ html()->label(__('messages.lbl_price') . '<span class="text-danger">*</span>', 'price')->class('form-label')->for('price') }}
                                {{ html()->number('price', old('price', $data->price))->class('form-control')->attribute('placeholder', __('messages.enter_price'))->required() }}
                                @error('price')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="invalid-feedback" id="price-error">{{ __('messages.price_field_required') }}</div>

                            <div class="col-md-4">
                                {{ html()->label(__('messages.purchase_type'), 'purchase_type')->class('form-label') }}
                                {{ html()->select(
                                        'purchase_type',
                                        [
                                            '' => __('messages.lbl_select_purchase_type'),
                                            'rental' => __('messages.lbl_rental'),
                                            'onetime' => __('messages.lbl_one_time_purchase'),
                                        ],
                                        old('purchase_type', $data->purchase_type ?? 'rental'),
                                    )->id('purchase_type')->class('form-control select2')->attributes(['onchange' => 'toggleAccessDuration(this.value)']) }}
                            </div>

                            <div class="col-md-4 {{ $data->purchase_type == 'rental' ? '' : 'd-none' }}"
                                id="accessDurationWrapper">
                                {{ html()->label(__('messages.lbl_access_duration') . __('messages.lbl_in_days'), 'access_duration')->class('form-label') }}
                                {{ html()->number('access_duration', old('access_duration', $data->access_duration))->class('form-control')->attribute('placeholder', __('messages.access_duration')) }}
                                @error('access_duration')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                {{ html()->label(__('messages.lbl_discount') . ' (%)', 'discount')->class('form-label') }}
                                {{ html()->number('discount', old('discount', $data->discount))->class('form-control')->attribute('placeholder', __('messages.enter_discount'))->attribute('min', 1)->attribute('max', 99) }}
                                @error('discount')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                {{ html()->label(__('messages.lbl_total_price'), 'total_amount')->class('form-label') }}
                                {{ html()->text('total_amount', null)->class('form-control')->attribute('disabled', true)->id('total_amount') }}
                            </div>
                            <div class="col-md-4">
                                {{ html()->label(__('messages.lbl_available_for') . __('messages.lbl_in_days'), 'available_for')->class('form-label') }}
                                {{ html()->number('available_for', old('available_for', $data->available_for))->class('form-control')->attribute('placeholder', __('messages.available_for')) }}
                                @error('available_for')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                        </div>
                        <div class="col-md-6 col-lg-4 {{ old('access', 'paid') == 'free' ? 'd-none' : '' }}"
                            id="planSelection">
                            {{ html()->label(__('movie.lbl_select_plan') . '<span class="text-danger"> *</span>', 'type')->class('form-label') }}
                            {{ html()->select('plan_id', $plan->pluck('name', 'id')->prepend(__('placeholder.lbl_select_plan'), ''), $data->plan_id)->class('form-control select2')->id('plan_id')->attribute('required', 'required') }}
                            @error('plan_id')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="name-error">{{ __('messages.plan_field_required') }}</div>
                        </div> --}}


                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('season.lbl_season_number') . ' <span class="text-danger">*</span>', 'season_number')->class('form-label') }}
                            {{ html()->number('season_number', old('season_number', $data->season_number))->class('form-control')->placeholder(__('season.lbl_season_number_placeholder'))->attribute('required', 'required')->attribute('min', '1') }}
                            @error('season_number')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="season-number-error">{{ __('messages.season_number_field_required') }}</div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('plan.lbl_status'), 'status')->class('form-label') }}
                            <div class="d-flex justify-content-between align-items-center form-control">
                                {{ html()->label(__('messages.active'), 'status')->class('form-label text-body mb-0') }}
                                <div class="form-check form-switch">
                                    {{ html()->hidden('status', 0) }}
                                    {{ html()->checkbox('status', $data->status)->class('form-check-input')->id('status')->value(1) }}
                                </div>
                            </div>
                            @error('status')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                {{ html()->label(__('movie.lbl_short_desc'), 'short_desc')->class('form-label') }}
                                <span class="text-primary cursor-pointer" id="GenrateshortDescription"><i
                                        class="ph ph-info" data-bs-toggle="tooltip"
                                        title="{{ __('messages.chatgpt_info') }}"></i>
                                    {{ __('messages.lbl_chatgpt') }}</span>
                            </div>
                            {{ html()->textarea('short_desc', $data->short_desc)->class('form-control')->id('short_desc')->placeholder(__('placeholder.lbl_season_short_desc'))->rows('8') }}
                            @error('short_desc')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-lg-6">
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
                                    <div class="invalid-feedback mt-1" id="seo_image_error" style="display: none;">
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
                                            placeholder="{{ __('placeholder.lbl_meta_title') }}">

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


                                    <div class="invalid-feedback" id="embed-error">{{ __('messages.site_meta_description_required') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mb-5">
        {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}
    </div>
    {{ html()->form()->close() }}

    @include('components.media-modal', compact('page_type'))
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
            const seoImage = document.getElementById('seo_image');
            const seoImagePreview = document.getElementById('selectedSeoImage');
            const seoImageError = document.querySelector('#seo_image_input + .invalid-feedback');

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
                    const $textarea = $('#description');
                    const $error = $('#desc-error');
                    if (!textContent) { /* only show after form submit */ } else {
                        $textarea.removeClass('is-invalid');
                        $error.hide();
                        try {
                            const errs = window.__seasonEditCurrentErrors || {};
                            if (errs.description) delete errs.description;
                            Object.keys(errs).forEach(function(k) { if (k === 'description' || (k && k.startsWith('description.'))) delete errs[k]; });
                            window.__seasonEditCurrentErrors = errs;
                            if (window.showErrorCountOnTabs && window.__seasonEditTabFields) { window.showErrorCountOnTabs(errs, window.__seasonEditTabFields); }
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

        function validateTrailerUrlInput() {
            var URLInput = document.querySelector('input[name="trailer_url"]');
            var urlError = document.getElementById('trailer-url-error');
            var urlPatternError = document.getElementById('trailer-pattern-error');
            var selectedValue = (document.getElementById('trailer_url_type') && document.getElementById('trailer_url_type').value) || '';
            var urlTypes = ['URL', 'YouTube', 'HLS', 'x265', 'Vimeo'];
            var urlPattern;
            if (!URLInput || urlTypes.indexOf(selectedValue) === -1) {
                if (urlPatternError) urlPatternError.style.display = 'none';
                if (urlError) urlError.style.display = 'none';
                return true;
            }
            if (selectedValue === 'YouTube') {
                urlPattern = /^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/;
                if (urlPatternError) urlPatternError.innerText = 'Please enter a valid Youtube URL';
            } else if (selectedValue === 'Vimeo') {
                urlPattern = /^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^/]+\/videos\/)?\d+)(\/.*)?$/;
                if (urlPatternError) urlPatternError.innerText = 'Please enter a valid Vimeo URL';
            } else {
                urlPattern = /^https?:\/\/.+$/;
                if (urlPatternError) urlPatternError.innerText = 'Please enter a valid URL';
            }
            var val = (URLInput.value || '').trim();
            if (val === '') {
                if (urlPatternError) urlPatternError.style.display = 'none';
                if (urlError) urlError.style.display = 'none';
                URLInput.classList.remove('is-invalid');
                return false;
            }
            if (!urlPattern.test(URLInput.value)) {
                if (urlPatternError) urlPatternError.style.display = 'block';
                if (urlError) urlError.style.display = 'none';
                URLInput.classList.add('is-invalid');
                return false;
            }
            if (urlPatternError) urlPatternError.style.display = 'none';
            if (urlError) urlError.style.display = 'none';
            URLInput.classList.remove('is-invalid');
            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {

            function handleTrailerUrlTypeChange(selectedValue) {
                var FileInput = document.getElementById('url_file_input');
                var URLInput = document.getElementById('url_input');
                var EmbedInput = document.getElementById('embed_input');
                var trailerfile = document.querySelector('input[name="trailer_video"]');
                var trailerfileError = document.getElementById('trailer-file-error');
                var urlError = document.getElementById('trailer-url-error');
                var URLInputField = document.querySelector('input[name="trailer_url"]');
                var IframeField = document.querySelector('textarea[name="trailer_embedded"]');

                // Hide all inputs first
                FileInput.classList.add('d-none');
                URLInput.classList.add('d-none');
                EmbedInput.classList.add('d-none');

                // Remove all required attributes
                if (URLInputField) URLInputField.removeAttribute('required');
                if (trailerfile) trailerfile.removeAttribute('required');
                if (IframeField) IframeField.removeAttribute('required');

                if (selectedValue === 'Local') {
                    FileInput.classList.remove('d-none');
                    if (trailerfile) trailerfile.setAttribute('required', 'required');
                } else if (selectedValue === 'Embedded') {
                    EmbedInput.classList.remove('d-none');
                    if (IframeField) IframeField.setAttribute('required', 'required');
                } else if (selectedValue === 'URL' || selectedValue === 'YouTube' || selectedValue === 'HLS' ||
                    selectedValue === 'x265' || selectedValue === 'Vimeo') {
                    URLInput.classList.remove('d-none');
                    if (URLInputField) URLInputField.setAttribute('required', 'required');
                    validateTrailerUrlInput();
                }
            }

            var initialSelectedValue = document.getElementById('trailer_url_type').value;
            handleTrailerUrlTypeChange(initialSelectedValue);
            $('#trailer_url_type').change(function() {
                var selectedValue = $(this).val();
                handleTrailerUrlTypeChange(selectedValue);
            });

            var URLInput = document.querySelector('input[name="trailer_url"]');
            if (URLInput) {
                URLInput.addEventListener('input', function() {

                    validateTrailerUrlInput();
                });
            }
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

            $('#GenrateshortDescription').on('click', function(e) {

                e.preventDefault();

                var description = $('#short_desc').val();
                var name = $('#name').val();
                var tvshow = $('#entertainment_id').val();

                var generate_discription = "{{ route('backend.seasons.generate-description') }}";
                generate_discription = generate_discription.replace('amp;', '');

                if (!description && !name) {
                    return;
                }

                $('#short_desc').text('Loading...')


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

                        $('#short_desc').text('')

                        if (response.success) {

                            var data = response.data;
                            $('#short_desc').html(data)

                        } else {
                            $('#error_message').text(response.message ||
                                'Failed to get Description.');
                        }
                    },
                    error: function(xhr) {
                        $('#error_message').text('Failed to get Description.');
                        $('#short_desc').text('');
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

        const seasonFieldLabels = {
            'name': '{{ __('movie.lbl_name') }}',
            'season_number': '{{ __('season.lbl_season_number') }}',
            'description': '{{ __('movie.lbl_description') }}',
            'short_desc': '{{ __('movie.lbl_short_desc') }}',
            'entertainment_id': '{{ __('season.lbl_tv_shows') }}',
            'trailer_url_type': '{{ __('movie.lbl_trailer_url_type') }}',
            'trailer_url': '{{ __('movie.lbl_trailer_url') }}',
            'trailer_embedded': '{{ __('movie.lbl_embed_code') }}',
            'trailer_video': '{{ __('movie.lbl_trailer_video') }}',
            'access': '{{ __('movie.lbl_movie_access') }}',
            'plan_id': '{{ __('movie.lbl_select_plan') }}',
            'price': '{{ __('messages.lbl_price') }}',
            'purchase_type': '{{ __('messages.purchase_type') }}',
            'access_duration': '{{ __('messages.lbl_access_duration') }}',
            'discount': '{{ __('messages.lbl_discount') }}',
            'available_for': '{{ __('messages.lbl_available_for') }}',
            'status': '{{ __('plan.lbl_status') }}',
            'meta_title': '{{ __('messages.lbl_meta_title') }}',
            'meta_keywords': '{{ __('messages.lbl_meta_keywords') }}',
            'canonical_url': '{{ __('messages.lbl_canonical_url') }}',
            'google_site_verification': '{{ __('messages.lbl_google_site_verification') }}',
            'short_description': '{{ __('messages.lbl_short_description') }}',
            'seo_image': '{{ __('messages.lbl_seo_image') }}'
        };

        const seasonTabFields = {
            'pills-movie': ['name', 'season_number', 'description', 'short_desc', 'entertainment_id',
                'trailer_url_type', 'trailer_url', 'trailer_embedded', 'trailer_video',
                'access', 'plan_id', 'price', 'purchase_type', 'access_duration',
                'discount', 'available_for', 'status'
            ],
            'pills-seo': ['meta_title', 'meta_keywords', 'canonical_url',
                'google_site_verification', 'short_description', 'seo_image'
            ]
        };

        let currentErrors = {};
        window.__seasonEditCurrentErrors = currentErrors;
        window.__seasonEditTabFields = seasonTabFields;

        function showValidationModal(errors, fieldLabels = {}) {
            if (window.showValidationModal) {
                return window.showValidationModal(errors, fieldLabels);
            }
        }

        function showErrorCountOnTabs(errors, tabFields = {}) {
            if (window.showErrorCountOnTabs) {
                return window.showErrorCountOnTabs(errors, tabFields);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            @if ($errors->any())
                const serverErrors = @json($errors->toArray());
                currentErrors = serverErrors || {};
                window.__seasonEditCurrentErrors = currentErrors;
                if (window.showValidationModal) window.showValidationModal(serverErrors, seasonFieldLabels);
                if (window.showErrorCountOnTabs) window.showErrorCountOnTabs(serverErrors, seasonTabFields);
            @endif

            // Live validation for trailer embed
            const embedInput = document.getElementById('trailer_embedded');
            if (embedInput) {
                embedInput.addEventListener('input', () => validateEmbedInput('trailer_embedded',
                    'trailer-embed-error'));
            }

            document.getElementById('form-submit').addEventListener('submit', function(e) {
                e.preventDefault();
                let errors = {};
                var trailerType = (document.getElementById('trailer_url_type') && document.getElementById('trailer_url_type').value) || '';
                var urlTypes = ['URL', 'YouTube', 'HLS', 'x265', 'Vimeo'];

                if (trailerType === 'Embedded') {
                    if (!validateEmbedInput('trailer_embedded', 'trailer-embed-error')) {
                        errors['trailer_embedded'] = ['{{ __('messages.embed_code_required') }}'];
                    }
                }

                if (urlTypes.indexOf(trailerType) !== -1) {
                    var input = document.querySelector('input[name="trailer_url"]');
                    if (input) {
                        var val = (input.value || '').trim();
                        if (val === '') {
                            errors['trailer_url'] = ['Video URL field is required'];
                            var ue = document.getElementById('trailer-url-error'); if (ue) ue.style.display = 'block';
                            var pe = document.getElementById('trailer-pattern-error'); if (pe) pe.style.display = 'none';
                            input.classList.add('is-invalid');
                        } else {
                            if (!validateTrailerUrlInput()) { errors['trailer_url'] = ['Please enter a valid URL']; }
                        }
                    }
                }

                Object.keys(seasonFieldLabels).forEach(field => {
                    if (field === 'trailer_url' && urlTypes.indexOf(trailerType) !== -1) return;
                    const el = document.getElementsByName(field)[0];
                    var val = (el && el.value) ? String(el.value).trim() : '';
                    if (field === 'description' && typeof tinymce !== 'undefined' && tinymce.get('description')) { try { val = (tinymce.get('description').getContent({ format: 'text' }) || '').trim(); } catch (e) {} }
                    if (el && el.hasAttribute('required') && !val) {
                        errors[field] = [
                            `${seasonFieldLabels[field]} is required.`
                        ];
                    }
                });

                if (Object.keys(errors).length > 0) {
                    currentErrors = errors;
                    window.__seasonEditCurrentErrors = currentErrors;
                    showValidationModal(errors, seasonFieldLabels);
                    showErrorCountOnTabs(errors, seasonTabFields);
                    return false;
                }

                this.submit();
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
            var fieldKey = String(name).replace(/\[\]$/, '');
            $(el).removeClass('is-invalid');
            var $select2 = $(el).next('.select2-container'); if ($select2.length) $select2.find('.select2-selection').removeClass('is-invalid');
            var errId = fieldKey === 'trailer_url' ? 'trailer-url-error' : (fieldKey === 'trailer_embedded' ? 'trailer-embed-error' : (fieldKey === 'season_number' ? 'season-number-error' : (fieldKey === 'meta_title' ? 'meta_title_error' : fieldKey + '-error')));
            var errEl = document.getElementById(errId); if (errEl) errEl.style.display = 'none';
            if (fieldKey === 'trailer_url') { var pe = document.getElementById('trailer-pattern-error'); if (pe) pe.style.display = 'none'; }
            var $sibs = $(el).siblings('.invalid-feedback'); if ($sibs.length) $sibs.hide(); else $(el).closest('.col-md-3, .col-md-4, .col-md-6, .col-lg-3, .col-lg-4, .col-lg-6, .col-lg-12, .mb-3').find('.invalid-feedback').first().hide();

            var errs = window.__seasonEditCurrentErrors || currentErrors || {};
            var idx = getArrayFieldIndex(el, name); var changed = false;
            if (errs[fieldKey]) { delete errs[fieldKey]; changed = true; }
            if (errs[name]) { delete errs[name]; changed = true; }
            if (idx !== null) { var indexedKey = fieldKey + '.' + idx; if (errs[indexedKey]) { delete errs[indexedKey]; changed = true; } Object.keys(errs).forEach(function(k) { if (k.indexOf(indexedKey + '.') === 0) { delete errs[k]; changed = true; } }); }
            var dottedKey = String(name).replace(/\[(\d+)\]/g, '.$1').replace(/\[(\w+)\]/g, '.$1');
            if (dottedKey && dottedKey !== name && errs[dottedKey]) { delete errs[dottedKey]; changed = true; }
            if (changed) { currentErrors = errs; window.__seasonEditCurrentErrors = errs; if (window.showErrorCountOnTabs) window.showErrorCountOnTabs(errs, seasonTabFields); }
        }

        $(document).on('change input', '#form-submit :input', function() { if (fieldHasValue(this)) clearFieldValidation(this); });
        $(document).on('select2:select select2:change', '#form-submit select.select2', function() { if (fieldHasValue(this)) clearFieldValidation(this); });
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

            // Auto-fill season number when TV show is changed and field is empty
            $('#entertainment_id').on('change', function() {
                const tvShowId = $(this).val();
                const seasonNumberInput = $('input[name="season_number"]');
                
                if (tvShowId && seasonNumberInput.val() === '') {
                    $.ajax({
                        url: '{{ route("backend.seasons.index_list") }}',
                        type: 'GET',
                        data: { entertainment_id: tvShowId },
                        success: function(response) {
                            const nextSeasonNumber = response.data ? response.data.length + 1 : 1;
                            seasonNumberInput.val(nextSeasonNumber);
                        }
                    });
                }
            });
        });
    </script>

    <style>
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
