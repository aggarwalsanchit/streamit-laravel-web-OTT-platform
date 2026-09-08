@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')

    <x-back-button-component route="backend.videos.index" />

    <script>
        function toggleClipsSection() {
            if (typeof jQuery !== 'undefined' && jQuery('#enable_clips').length) {
                if (jQuery('#enable_clips').is(':checked')) {
                    jQuery('#clips-inputs-container-parent').removeClass('d-none');
                } else {
                    jQuery('#clips-inputs-container-parent').addClass('d-none');
                }
            }
        }
        // Make it globally accessible
        window.toggleClipsSection = toggleClipsSection;
    </script>

    {{ html()->form('PUT', route('backend.videos.update', $data->id))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->attribute('novalidate', 'novalidate')->class('requires-validation')->open() }}

    @csrf

    <ul class="nav nav-pills mb-3 movie-tab" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-movie-tab" data-bs-toggle="pill" data-bs-target="#pills-movie"
                type="button" role="tab" aria-controls="pills-movie"
                aria-selected="true">{{ __('messages.lbl_video_details') }}</button>
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
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-clip-tab" data-bs-toggle="pill" data-bs-target="#pills-clip" type="button"
                role="tab" aria-controls="pills-clip"
                aria-selected="false">{{ __('messages.lbl_clip_details') }}</button>
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
                <h5>{{ __('messages.lbl_video_details') }}</h5>
            </div>
            <p class="text-danger" id="error_message"></p>
            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                    <div class="col-lg-4">
                            <div class="position-relative">
                                {{ html()->label(__('movie.lbl_thumbnail'), 'thumbnail')->class('form-label') }}

                                <div class="input-group btn-file-upload">
                                    {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerThumbnail')->attribute('data-hidden-input', 'file_urlthumbnail')->style('height: 13.8rem') }}

                                    {{ html()->text('image_input_thumbnail')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Image Input thumbnail')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerThumbnail')->attribute('data-hidden-input', 'file_urlthumbnail')->attribute('aria-describedby', 'basic-addon1') }}
                                </div>

                                <div class="mb-3 uploaded-image" id="selectedImageContainerThumbnail">
                                    @if ($data->thumbnail_url)
                                        <img src="{{ $data->thumbnail_url }}" class="img-fluid mb-2"
                                            style="max-width: 100px; max-height: 100px;">
                                        <span class="remove-media-icon"
                                            style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                                            onclick="removeImage('file_urlthumbnail', 'remove_image_flag_thumbnail')">×</span>
                                    @else
                                        <p>No image selected.</p>
                                    @endif
                                </div>
                                {{ html()->hidden('thumbnail_url')->id('file_urlthumbnail')->value($data->thumbnail_url) }}
                                {{ html()->hidden('remove_image_thumbnail')->id('remove_image_flag_thumbnail')->value(0) }}
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="position-relative">
                                {{ html()->label(__('movie.lbl_poster'), 'poster')->class('form-label') }}

                                <div class="input-group btn-file-upload">
                                    {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer2')->attribute('data-hidden-input', 'file_url2')->style('height: 13.8rem') }}

                                    {{ html()->text('image_input2')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Image Input 2')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer2')->attribute('data-hidden-input', 'file_url2')->attribute('aria-describedby', 'basic-addon1') }}
                                </div>

                                <div class="mb-3 uploaded-image" id="selectedImageContainer2">
                                    @if ($data->poster_url)
                                        <img src="{{ $data->poster_url }}" class="img-fluid mb-2"
                                            style="max-width: 100px; max-height: 100px;">
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
                        <div class="col-lg-4">
                            <div class="position-relative">
                                {{ html()->label(__('movie.lbl_poster_tv'), 'poster_tv')->class('form-label') }}

                                <div class="input-group btn-file-upload">
                                    {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerTv')->attribute('data-hidden-input', 'file_urltv')->style('height: 13.8rem') }}

                                    {{ html()->text('image_input2')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Image Input 2')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerTv')->attribute('data-hidden-input', 'file_urltv')->attribute('aria-describedby', 'basic-addon1') }}
                                </div>

                                <div class="mb-3 uploaded-image" id="selectedImageContainerTv">
                                    @if ($data->poster_tv_url)
                                        <img src="{{ $data->poster_tv_url }}" class="img-fluid mb-2"
                                            style="max-width: 100px; max-height: 100px;">
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
                        <div class="col-lg-12">
                            <div class="row gy-3">
                                <div class="col-md-6">
                                    {{ html()->label(__('video.lbl_title') . ' <span class="text-danger">*</span>', 'name')->class('form-label') }}
                                    {{ html()->text('name')->attribute('value', $data->name)->placeholder(__('placeholder.lbl_movie_name'))->class('form-control')->attribute('required', 'required') }}
                                    @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <div class="invalid-feedback" id="name-error">{{ __('messages.title_field_required') }}</div>
                                </div>
                                <div class="col-md-6">
                                    {{ html()->label(__('movie.lbl_movie_access'), 'access')->class('form-label') }}
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                                            <div>
                                                <input class="form-check-input" type="radio" name="access"
                                                    id="paid" value="paid"
                                                    onchange="showPlanSelection(this.value === 'paid')"
                                                    {{ $data->access == 'paid' ? 'checked' : '' }} checked>
                                                <span class="form-check-label"
                                                    for="paid">{{ __('movie.lbl_paid') }}</span>
                                            </div>
                                        </label>
                                        <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                                            <div>
                                                <input class="form-check-input" type="radio" name="access"
                                                    id="free" value="free"
                                                    onchange="showPlanSelection(this.value === 'paid')"
                                                    {{ $data->access == 'free' ? 'checked' : '' }}>
                                                <label class="form-check-label"
                                                    for="free">{{ __('movie.lbl_free') }}</label>
                                            </div>
                                        </label>

                                        <label class="form-check form-check-inline form-control cursor-pointer w-auto m-0">
                                            <div>
                                                <input class="form-check-input" type="radio" name="access"
                                                    id="pay-per-view" value="pay-per-view"
                                                    onchange="showPlanSelection(this.value === 'paid')"
                                                    {{ $data->access == 'pay-per-view' ? 'checked' : '' }}>
                                                <span class="form-check-label"
                                                    for="free">{{ __('messages.lbl_pay_per_view') }}</span>
                                            </div>
                                        </label>
                                    </div>
                                    @error('movie_access')
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
                                        <div class="invalid-feedback" id="purchase_type-error">Purchase Type field is
                                            required</div>
                                    </div>

                                    {{-- Access Duration (Only for Rental) --}}
                                    <div class="col-md-4 {{ $data->purchase_type == 'rental' ? '' : 'd-none' }}"
                                        id="accessDurationWrapper">
                                        {{ html()->label(__('messages.lbl_access_duration') . __('messages.lbl_in_days') . '<span class="text-danger">*</span>', 'access_duration')->class('form-label') }}
                                        {{ html()->number('access_duration', old('access_duration', $data->access_duration))->class('form-control')->attribute('pattern', '[0-9]*')->attribute('oninput', 'this.value = this.value.replace(/[^0-9]/g, "")')->attribute('placeholder', __('messages.access_duration')) }}
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
                                        <div class="invalid-feedback" id="discount-error">{{ __('messages.available_for_field_required') }}
                                        </div>

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
                                        <div class="invalid-feedback" id="available_for-error">Available For field is
                                            required</div>
                                    </div>

                                </div>
                                <div class="col-md-6 {{ old('access', 'paid') == 'free' ? 'd-none' : '' }}"
                                    id="planSelection">
                                    {{ html()->label(__('movie.lbl_select_plan') . ' <span class="text-danger">*</span>', 'type')->class('form-label') }}
                                    {{ html()->select('plan_id', $plan->pluck('name', 'id')->prepend(__('placeholder.lbl_select_plan'), ''), $data->plan_id)->class('form-control select2')->id('plan_id') }}
                                    @error('plan_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <div class="invalid-feedback" id="name-error">{{ __('messages.plan_field_required') }}</div>
                                </div>
                                <div class="col-md-6">
                                    {{ html()->label(__('plan.lbl_status'), 'status')->class('form-label') }}
                                    <div class="d-flex justify-content-between align-items-center form-control">
                                        {{ html()->label(__('messages.active'), 'status')->class('form-label mb-0 text-body') }}
                                        <div class="form-check form-switch">
                                            {{ html()->hidden('status', 0) }}
                                            {{ html()->checkbox('status', $data->status)->class('form-check-input')->id('status')->value(1) }}
                                        </div>
                                    </div>
                                    @error('status')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        {{-- <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                {{ html()->label(__('movie.lbl_short_desc'), 'short_desc')->class('form-label') }}
                                <span class="text-primary cursor-pointer" id="GenrateshortDescription"><i
                                        class="ph ph-info" data-bs-toggle="tooltip"
                                        title="{{ __('messages.chatgpt_info') }}"></i>
                                    {{ __('messages.lbl_chatgpt') }}</span>
                            </div>

                            {{ html()->textarea('short_desc', $data->short_desc)->class('form-control')->id('short_desc')->placeholder(__('placeholder.episode_short_desc'))->rows('8') }}
                            @error('short_desc')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div> --}}
                        <div class="col-md-6">
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
        <div class="tab-pane fade" id="pills-basic" role="tabpanel" aria-labelledby="pills-basic-tab">
            <div class="d-flex align-items-center justify-content-between mt-5 mb-3">
                <h5>{{ __('movie.lbl_basic_info') }}</h5>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_duration') . ' <span class="text-danger">*</span>', 'duration')->class('form-label') }}
                            {{ html()->time('duration')->attribute('value', $data->duration)->placeholder(__('movie.lbl_duration'))->class('form-control min-datetimepicker-time')->attribute('required', 'required') }}
                            @error('duration')
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
                            {{ html()->label(__('movie.lbl_release_date') . ' <span class="text-danger">*</span>', 'release_date')->class('form-label') }}
                            {{ html()->date('release_date')->attribute('value', $data->release_date ? \Carbon\Carbon::parse($data->release_date)->format('Y-m-d') : '')->placeholder(__('movie.lbl_release_date'))->class('form-control datetimepicker')->required() }}
                            @error('release_date')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="release_date-error">{{ __('messages.release_date_field_required') }}</div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_age_restricted'), 'is_restricted')->class('form-label') }}
                            <div class="d-flex align-items-center justify-content-between form-control">
                                {{ html()->label(__('movie.lbl_child_content'), 'is_restricted')->class('form-label mb-0 text-body') }}
                                <div class="form-check form-switch">
                                    {{ html()->hidden('is_restricted', 0) }}
                                    {{ html()->checkbox('is_restricted', $data->is_restricted)->class('form-check-input')->id('is_restricted') }}
                                </div>
                            </div>
                            @error('is_restricted')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-md-6 col-lg-4">
                            {{ html()->label(__('movie.lbl_download_status'), 'download_status')->class('form-label') }}
                            <div class="d-flex justify-content-between align-items-center form-control">
                                {{ html()->label(__('movie.lbl_download_status'), 'download_status')->class('form-label mb-0 text-body') }}
                                <div class="form-check form-switch">
                                    {{ html()->hidden('download_status', 0)->id('download_status_hidden') }}
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
                        <div class="col-md-6">
                            {{ html()->label(__('movie.lbl_video_upload_type'), 'video_upload_type')->class('form-label') }}
                            {{ html()->select(
                                    'video_upload_type',
                                    $upload_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_video_type'), ''),
                                    old('video_upload_type', $data->video_upload_type ?? ''),
                                )->class('form-control select2')->id('video_upload_type')->required() }}
                            @error('video_upload_type')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="name-error">{{ __('messages.video_type_field_required') }}</div>
                        </div>

                        <div class="col-md-6 d-none" id="video_url_input_section">
                            {{ html()->label(__('movie.video_url_input'), 'video_url_input')->class('form-label') }}
                            {{ html()->text('video_url_input')->attribute('value', $data->video_url_input)->placeholder(__('placeholder.video_url_input'))->class('form-control')->id('video_url_input') }}
                            @error('video_url_input')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="url-error">{{ __('messages.video_url_field_required') }}</div>
                            <div class="invalid-feedback" id="url-pattern-error" style="display:none;">
                                Please enter a valid URL starting with http:// or https://.
                            </div>
                        </div>

                        <div class="col-md-6 d-none" id="video_file_input_section">
                            {{ html()->label(__('movie.video_file_input'), 'video_file')->class('form-label') }}

                            <div class="mb-3" id="selectedImageContainer4">
                                @if (Str::endsWith($data->video_url_input, ['.jpeg', '.jpg', '.png', '.gif']))
                                    <img class="img-fluid media-thumb-10" src="{{ $data->video_url_input }}">
                                @else
                                    <video width="400" controls="controls" preload="metadata">
                                        <source src="{{ $data->video_url_input }}" type="video/mp4">
                                    </video>
                                @endif
                            </div>

                            <div class="input-group btn-video-link-upload mb-3">
                                {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer4')->attribute('data-hidden-input', 'file_url4') }}

                                {{ html()->text('image_input4')->class('form-control')->placeholder(__('placeholder.lbl_select_file'))->attribute('aria-label', 'Image Input 3')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer4')->attribute('data-hidden-input', 'file_url4') }}
                            </div>

                            {{ html()->hidden('video_file_input')->id('file_url4')->value($data->video_url_input)->attribute('data-validation', 'iq_video_quality') }}


                            @error('video')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="file-error">{{ __('messages.video_file_field_required') }}</div>
                        </div>

                        <div class="col-md-6 d-none" id="video_embed_input_section">
                            {{ html()->label(__('movie.lbl_embed_code') . ' <span class="text-danger">*</span>', 'video_embedded')->class('form-label') }}
                            {{ html()->textarea('video_embedded')->placeholder('<iframe ...></iframe>')->class('form-control')->id('video_embedded')->value($data->video_upload_type === 'Embedded' ? $data->video_url_input : '') }}
                            @error('video_embedded')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div class="invalid-feedback" id="video-embed-error">{{ __('messages.embed_code_required') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5>{{ __('movie.lbl_quality_info') }}</h5>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center justify-content-between form-control">
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
                                @if (!empty($data['VideoStreamContentMappings']) && count($data['VideoStreamContentMappings']) > 0)
                                    @foreach ($data['VideoStreamContentMappings'] as $mapping)
                                        <div class="row gy-3 video-inputs-container mt-1">
                                            <div class="col-md-3">
                                                {{ html()->label(__('movie.lbl_video_upload_type'), 'video_quality_type')->class('form-label') }}
                                                {{ html()->select(
                                                        'video_quality_type[]',
                                                        $upload_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_video_type'), '')->merge(['Embedded' => 'Embedded']),
                                                        old('video_quality_type', $mapping->type ?? ''),
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
                                                        $mapping->quality ?? null,
                                                    )->class('form-control select2')->id('video_quality_' . ($mapping->id ?? 'new')) }}
                                                @error('video_quality')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 d-none video-url-input quality_video_input">
                                                {{ html()->label(__('movie.video_url_input'), 'quality_video_url_input')->class('form-label') }}
                                                {{ html()->text('quality_video_url_input[]', $mapping->url ?? null)->placeholder(__('placeholder.video_url_input'))->class('form-control') }}
                                                @error('quality_video_url_input')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 d-none video-file-input quality_video_file_input">
                                                {{ html()->label(__('movie.video_file_input'), 'quality_video')->class('form-label') }}
                                                <div class="mb-3" id="selectedImageContainer5_{{ $mapping->id ?? 'new' }}">
                                                    @if ($mapping->type == 'Local' && !empty($mapping->url))
                                                        @php
                                                            $videoUrl = setBaseUrlWithFileName($mapping->url, 'video', 'video');
                                                        @endphp
                                                        @if (Str::endsWith($videoUrl, ['.jpeg', '.jpg', '.png', '.gif']))
                                                            <img class="img-fluid mb-2" src="{{ $videoUrl }}" style="max-width: 100px; max-height: 100px;">
                                                        @else
                                                            <video width="400" controls="controls" preload="metadata">
                                                                <source src="{{ $videoUrl }}" type="video/mp4">
                                                            </video>
                                                        @endif
                                                    @endif
                                                </div>
                                                <div class="input-group btn-video-link-upload">
                                                    {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer5_' . ($mapping->id ?? 'new'))->attribute('data-hidden-input', 'file_url5_' . ($mapping->id ?? 'new')) }}
                                                    {{ html()->text('quality_video_file_input')->class('form-control')->placeholder(__('placeholder.lbl_select_file')) }}
                                                </div>
                                                {{ html()->hidden('video_quality_url[]')->id('file_url5_' . ($mapping->id ?? 'new'))->value($mapping->type == 'Local' ? setBaseUrlWithFileName($mapping->url, 'video', 'video') : '')->attribute('data-validation', 'iq_video_quality') }}
                                                @error('quality_video')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                                <div class="invalid-feedback" id="file-error">{{ __('messages.video_file_field_required') }}</div>
                                            </div>

                                            <div class="col-md-4 d-none video-embed-input quality_video_embed_input">
                                                {{ html()->label(__('movie.lbl_embed_code'), 'quality_video_embed')->class('form-label') }}
                                                {{ html()->textarea('quality_video_embed[]', ($mapping->type === 'Embedded' ? $mapping->url : null))->placeholder('<iframe ...></iframe>')->class('form-control')->rows(4) }}
                                                @error('quality_video_embed')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
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
                                            @error('quality_video_url_input')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-4 d-none video-file-input quality_video_file_input">
                                            {{ html()->label(__('movie.video_file_input'), 'quality_video')->class('form-label') }}
                                            <div class="mb-3" id="selectedImageContainer5">
                                                    @if (Str::endsWith($data->video_quality_url, ['.jpeg', '.jpg', '.png', '.gif']))
                                                        <img class="img-fluid mb-2" src="{{ $data->video_quality_url }}"  style="max-width: 100px; max-height: 100px;">
                                                    @else
                                                        <video width="400" controls="controls" preload="metadata">
                                                            <source src="{{ $data->video_quality_url }}" type="video/mp4">
                                                        </video>
                                                    @endif

                                            </div>

                                            <div class="input-group btn-video-link-upload">
                                                {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer5')->attribute('data-hidden-input', 'file_url5') }}

                                                {{ html()->text('image_input5')->class('form-control')->placeholder(__('placeholder.lbl_select_file'))->attribute('aria-label', 'Image Input 5')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer5')->attribute('data-hidden-input', 'file_url5') }}
                                            </div>

                                            {{ html()->hidden('video_quality_url')->id('file_url5')->value(setBaseUrlWithFileName($data->video_quality_url, 'video', 'video'))->attribute('data-validation', 'iq_video_quality') }}
                                            @error('quality_video')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                            <div class="invalid-feedback" id="file-error">{{ __('messages.video_file_field_required') }}</div>
                                        </div>


                                        <div class="col-md-4 d-none video-embed-input quality_video_embed_input">
                                            {{ html()->label(__('movie.lbl_embed_code'), 'quality_video_embed')->class('form-label') }}
                                            {{ html()->textarea('quality_video_embed[]')->placeholder('<iframe ...></iframe>')->class('form-control')->value($data->quality_video)->rows(4) }}
                                        </div>

                                        <div class="col-sm-1 d-flex justify-content-center align-items-center mt-5">
                                            <button type="button"
                                                class="btn btn-secondary-subtle btn-sm remove-video-input d-none"><i
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
            <div class="d-flex align-items-center justify-content-between mt-5 pt-1 mb-3">
                <h5>{{ __('movie.lbl_subtitle_info') }}</h5>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center justify-content-between form-control">
                                <label for="enable_subtitle"
                                    class="form-label mb-0 text-body">{{ __('movie.lbl_enable_subtitle') }}</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enable_subtitle" value="0">
                                    <input type="checkbox" name="enable_subtitle" id="enable_subtitle"
                                        class="form-check-input" value="1"
                                        {{ old('enable_subtitle', $data->enable_subtitle) ? 'checked' : '' }}
                                        onchange="toggleSubtitleSection()">
                                </div>
                            </div>
                            @error('enable_subtitle')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div id="subtitle_section"
                            class="col-md-12 {{ old('enable_subtitle', $data->enable_subtitle) ? '' : 'd-none' }}">
                            <input type="hidden" name="deleted_subtitles" id="deleted_subtitles" value="">
                            <div id="subtitle-inputs-container">
                                @if ($data->subtitles && count($data->subtitles) > 0)
                                    @foreach ($data->subtitles as $index => $subtitle)
                                        <div class="row gy-3 subtitle-row">
                                            <input type="hidden" name="subtitles[{{ $index }}][id]"
                                                value="{{ $subtitle->id }}">
                                            <div class="col-md-4">

                                                {{ html()->select('subtitles[' . $index . '][language]', $subtitle_language->pluck('name', 'value')->prepend(__('placeholder.lbl_select_language'), ''), old('subtitles.' . $index . '.language', $subtitle->language_code))->class('form-control select2 subtitle-language') }}
                                                @error('subtitles.' . $index . '.language')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                                <div class="invalid-feedback">
                                                    {{ __('validation.required', ['attribute' => 'language']) }}</div>
                                            </div>
                                            <div class="col-md-4">


                                                {{ html()->file('subtitles[' . $index . '][subtitle_file]')->class('form-control subtitle-file')->accept('.srt,.vtt') }}
                                                {{ html()->hidden('subtitles[' . $index . '][existing_file]')->value($subtitle->subtitle_file) }}
                                                {{ html()->hidden('subtitles[' . $index . '][id]')->value($subtitle->id) }}
                                                <div class="mb-2">
                                                    <small class="text-muted">Current file:
                                                        {{ basename($subtitle->subtitle_file) }}</small>
                                                </div>
                                                @error('subtitles.' . $index . '.subtitle_file')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                                <div class="invalid-feedback">
                                                    {{ __('validation.required', ['attribute' => 'subtitle file']) }}
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check mt-3 pt-4">
                                                    {{ html()->checkbox('subtitles[' . $index . '][is_default]', old('subtitles.' . $index . '.is_default', $subtitle->is_default))->class('form-check-input is-default-subtitle')->id('is_default_' . $index) }}
                                                    {{ html()->label(__('movie.lbl_default_subtitle'), 'is_default_' . $index)->class('form-check-label') }}
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm mt-5 remove-subtitle">
                                                    <i class="ph ph-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="subtitle-row row">
                                        <div class="col-md-4">
                                            {{ html()->label(__('movie.lbl_language'), 'language')->class('form-label') }}
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
                                            {{ html()->label(__('movie.lbl_subtitle_file'), 'subtitle_file')->class('form-label') }}
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
                                            <button type="button" class="btn btn-danger btn-sm mt-5 remove-subtitle"><i
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
            <div class="d-flex align-items-center justify-content-between mt-5 pt-1 mb-3">
                <h5 class="mb-0">&nbsp;{{ __('messages.lbl_seo_settings') }}</h4>
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
                                            class="form-control" placeholder="{{ __('placeholder.lbl_meta_keywords') }}"
                                            data-placeholder="{{ __('placeholder.lbl_meta_keywords') }}"
                                            value="{{ is_array(old('meta_keywords')) ? $seo->meta_keywords ?? '' : old('meta_keywords', $seo->meta_keywords ?? '') }}" />
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
        <div class="tab-pane fade" id="pills-clip" role="tabpanel" aria-labelledby="pills-clip-tab">
            <div class="d-flex align-items-center justify-content-between mt-5 pt-1 mb-3">
                <h5>{{ __('messages.lbl_clips') }}</h5>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center form-control">
                                <label for="enable_clips"
                                    class="form-label mb-0 text-body">{{ __('messages.lbl_enable_clips') }}</label>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="enable_clips" value="0">
                                    <input type="checkbox" name="enable_clips" id="enable_clips"
                                        class="form-check-input" value="1" onchange="toggleClipsSection()"
                                        {{ old('enable_clips', $data->enable_clips) ? 'checked' : '' }}>
                                </div>
                                @error('enable_clips')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div id="clips-inputs-container-parent"
                                class="{{ old('enable_clips', $data->enable_clips) ? '' : 'd-none' }}">
                                @if ($clips->count() > 0)
                                    @foreach ($clips as $idx => $clip)
                                        @php
                                            $rowId = 'existing_' . $idx;
                                            $isLocal = $clip->type === 'Local';
                                            $isEmbed = in_array($clip->type, ['Embedded', 'Embed']);
                                            $isUrl = !$isLocal && !$isEmbed;
                                        @endphp
                                        <div class="clip-block">
                                            <div class="row gy-3 clips-inputs-container">
                                                {{ html()->hidden('clip_id[]', $clip->id) }}
                                                <div class="col-md-3">
                                                    {{ html()->label(__('movie.lbl_video_upload_type'), 'clip_upload_type')->class('form-label') }}
                                                    {{ html()->select(
                                                            'clip_upload_type[]',
                                                            $upload_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_video_type'), ''),
                                                            $clip->type,
                                                        )->class('form-control select2 clip_upload_type')->id('clip_upload_type_' . $rowId) }}
                                                </div>

                                                <div
                                                    class="col-md-4 clip-url-input clip_video_input {{ $isUrl ? '' : 'd-none' }}">
                                                    {{ html()->label(__('movie.video_url_input'), 'clip_url_input')->class('form-label') }}
                                                    {{ html()->text('clip_url_input[]', $isUrl ? $clip->url : null)->placeholder(__('placeholder.video_url_input'))->class('form-control') }}
                                                    @error('clip_url_input.*')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                    <div class="invalid-feedback d-none" id="clip_url_input-error">Please enter a valid URL format.</div>
                                                </div>

                                                <div
                                                    class="col-md-4 clip-file-input clip_video_file_input {{ $isLocal ? '' : 'd-none' }}">
                                                    {{ html()->label(__('movie.video_file_input'), 'clip_video')->class('form-label') }}
                                                    <div class="input-group btn-video-link-upload">
                                                        {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipurl_' . $rowId)->attribute('data-hidden-input', 'file_url_clip_' . $rowId) }}
                                                        {{ html()->text('clip_file_input_display')->class('form-control')->placeholder('Select File')->attribute('aria-label', 'Clip File')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipurl_' . $rowId)->attribute('data-hidden-input', 'file_url_clip_' . $rowId) }}
                                                    </div>
                                                    <div class="mt-3"
                                                        id="selectedImageContainerClipurl_{{ $rowId }}">
                                                        @if ($isLocal && !empty($clip->url))
                                                            @php $clipUrl = setBaseUrlWithFileName($clip->url,'video', 'video'); @endphp
                                                            @if (Str::endsWith($clipUrl, ['.jpeg', '.jpg', '.png', '.gif']))
                                                                <img class="img-fluid mb-2" src="{{ $clipUrl }}"
                                                                    style="max-width: 100px; max-height: 100px;">
                                                            @else
                                                                <video width="400" controls="controls"
                                                                    preload="metadata">
                                                                    <source src="{{ $clipUrl }}" type="video/mp4">
                                                                </video>
                                                            @endif
                                                        @endif
                                                    </div>
                                                    {{ html()->hidden('clip_file_input[]')->id('file_url_clip_' . $rowId)->value($isLocal ? setBaseUrlWithFileName($clip->url, 'video', 'video') : '')->attribute('data-validation', 'iq_video_quality') }}
                                                </div>

                                                <div
                                                    class="col-md-4 clip-embed-input clip_video_embed_input {{ $isEmbed ? '' : 'd-none' }}">
                                                    {{ html()->label(__('movie.lbl_embed_code'), 'clip_embed')->class('form-label') }}
                                                    {{ html()->textarea('clip_embedded[]')->placeholder('<iframe ...></iframe>')->class('form-control')->value($isEmbed ? $clip->url : '') }}
                                                </div>

                                            </div>

                                            <div class="row gy-3 mt-2">
                                                <div class="col-md-3">
                                                    <div class="position-relative">
                                                        {{ html()->label(__('movie.lbl_poster'), 'clip_poster_url')->class('form-label') }}
                                                        <div class="input-group btn-file-upload">
                                                            {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipPoster_' . $rowId)->attribute('data-hidden-input', 'file_url_clip_poster_' . $rowId)->style('height: 13.8rem') }}
                                                            {{ html()->text('clip_poster_input')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Clip Poster Image')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipPoster_' . $rowId)->attribute('data-hidden-input', 'file_url_clip_poster_' . $rowId) }}
                                                        </div>
                                                        <div class="uploaded-image"
                                                            id="selectedImageContainerClipPoster_{{ $rowId }}">
                                                            <img src="{{ setBaseUrlWithFileName($clip->poster_url, 'image', 'video') }}"
                                                                class="img-fluid mb-2"
                                                                style="max-width: 100px; max-height: 100px;">
                                                        </div>
                                                        {{ html()->hidden('clip_poster_url[]')->id('file_url_clip_poster_' . $rowId)->value($clip->poster_url ? setBaseUrlWithFileName($clip->poster_url, 'image', 'video') : '') }}
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="position-relative">
                                                        {{ html()->label(__('movie.lbl_poster_tv'), 'clip_tv_poster_url')->class('form-label') }}
                                                        <div class="input-group btn-file-upload">
                                                            {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipTvPoster_' . $rowId)->attribute('data-hidden-input', 'file_url_clip_tv_poster_' . $rowId)->style('height: 13.8rem') }}
                                                            {{ html()->text('clip_tv_poster_input')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Clip TV Poster Image')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipTvPoster_' . $rowId)->attribute('data-hidden-input', 'file_url_clip_tv_poster_' . $rowId) }}
                                                        </div>
                                                        <div class="uploaded-image"
                                                            id="selectedImageContainerClipTvPoster_{{ $rowId }}">
                                                            <img src="{{ setBaseUrlWithFileName($clip->tv_poster_url, 'image', 'video') }}"
                                                                class="img-fluid mb-2"
                                                                style="max-width: 100px; max-height: 100px;">
                                                        </div>
                                                        {{ html()->hidden('clip_tv_poster_url[]')->id('file_url_clip_tv_poster_' . $rowId)->value($clip->tv_poster_url ? setBaseUrlWithFileName($clip->tv_poster_url, 'image', 'video') : '') }}
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    {{ html()->label(__('movie.lbl_name'), 'clip_title')->class('form-label') }}
                                                    {{ html()->text('clip_title[]', $clip->title)->placeholder(__('messages.lbl_clip_title'))->class('form-control') }}
                                                </div>

                                                <div
                                                    class="col-sm-1 d-flex justify-content-center align-items-center mt-5">
                                                    <button type="button"
                                                        class="btn btn-secondary-subtle btn-sm remove-clip-input"><i
                                                            class="ph ph-trash align-middle"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="clip-block">
                                        <div class="row gy-3 clips-inputs-container mt-1">
                                            <div class="col-md-3">
                                                {{ html()->label(__('movie.lbl_video_upload_type'), 'clip_upload_type')->class('form-label') }}
                                                {{ html()->select(
                                                        'clip_upload_type[]',
                                                        $upload_url_type->pluck('name', 'value')->prepend(__('placeholder.lbl_select_video_type'), ''),
                                                        '',
                                                    )->class('form-control select2 clip_upload_type') }}
                                            </div>

                                            <div class="col-md-4 d-none clip-url-input clip_video_input">
                                                {{ html()->label(__('movie.video_url_input'), 'clip_url_input')->class('form-label') }}
                                                {{ html()->text('clip_url_input[]')->placeholder(__('placeholder.video_url_input'))->class('form-control') }}
                                                @error('clip_url_input.*')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                                <div class="invalid-feedback d-none" id="clip_url_input-error">Please enter a valid URL format.</div>
                                            </div>

                                            <div class="col-md-4 d-none clip-file-input clip_video_file_input">
                                                {{ html()->label(__('movie.video_file_input'), 'clip_video')->class('form-label') }}
                                                <div class="input-group btn-video-link-upload">
                                                    {{ html()->button(__('placeholder.lbl_select_file') . '<i class="ph ph-upload"></i>')->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipurl')->attribute('data-hidden-input', 'file_url_clip') }}
                                                    {{ html()->text('clip_file_input_display')->class('form-control')->placeholder('Select File')->attribute('aria-label', 'Clip File')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipurl')->attribute('data-hidden-input', 'file_url_clip') }}
                                                </div>
                                                <div class="mt-3" id="selectedImageContainerClipurl"></div>
                                                {{ html()->hidden('clip_file_input[]')->id('file_url_clip')->value('')->attribute('data-validation', 'iq_video_quality') }}
                                            </div>

                                            <div class="col-md-4 d-none clip-embed-input clip_video_embed_input">
                                                {{ html()->label(__('movie.lbl_embed_code'), 'clip_embed')->class('form-label') }}
                                                {{ html()->textarea('clip_embedded[]')->placeholder('<iframe ...></iframe>')->class('form-control') }}
                                            </div>

                                        </div>

                                        <div class="row gy-3 mt-2">
                                            <div class="col-md-3">
                                                <div class="position-relative">
                                                    {{ html()->label(__('movie.lbl_poster'), 'clip_poster_url')->class('form-label') }}
                                                    <div class="input-group btn-file-upload">
                                                        {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipPoster')->attribute('data-hidden-input', 'file_url_clip_poster')->style('height: 13.8rem') }}
                                                        {{ html()->text('clip_poster_input')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Clip Poster Image')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipPoster')->attribute('data-hidden-input', 'file_url_clip_poster') }}
                                                    </div>
                                                    <div class="uploaded-image" id="selectedImageContainerClipPoster">

                                                    </div>
                                                    {{ html()->hidden('clip_poster_url[]')->id('file_url_clip_poster')->value('') }}
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="position-relative">
                                                    {{ html()->label(__('movie.lbl_poster_tv'), 'clip_tv_poster_url')->class('form-label') }}
                                                    <div class="input-group btn-file-upload">
                                                        {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipTvPoster')->attribute('data-hidden-input', 'file_url_clip_tv_poster')->style('height: 13.8rem') }}
                                                        {{ html()->text('clip_tv_poster_input')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Clip TV Poster Image')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerClipTvPoster')->attribute('data-hidden-input', 'file_url_clip_tv_poster') }}
                                                    </div>
                                                    <div class="uploaded-image" id="selectedImageContainerClipTvPoster">

                                                    </div>
                                                    {{ html()->hidden('clip_tv_poster_url[]')->id('file_url_clip_tv_poster')->value('') }}
                                                </div>
                                            </div>

                                            <div class="col-md-3">
                                                {{ html()->label(__('movie.lbl_name'), 'clip_title')->class('form-label') }}
                                                {{ html()->text('clip_title[]')->placeholder(__('messages.lbl_clip_title'))->class('form-control') }}
                                            </div>

                                            <div class="col-sm-1 d-flex justify-content-center align-items-center mt-5">
                                                <button type="button"
                                                    class="btn btn-secondary-subtle btn-sm remove-clip-input d-none"><i
                                                        class="ph ph-trash align-middle"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="text-end mt-3">
                                    <a id="add_more_clip" class="btn btn-sm btn-primary"><i
                                            class="ph ph-plus-circle"></i> {{ __('episode.lbl_add_more') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade {{ old('download_status', $data->download_status ?? 0) ? '' : 'd-none' }}"
            id="pills-download" role="tabpanel" aria-labelledby="pills-download-tab">
            <div class="d-flex align-items-center justify-content-between mt-5 pt-1 mb-3">
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
                                    @php $downloadLocal = old('video_file_input_download', ($data->download_type === 'Local' ? setBaseUrlWithFileName($data->download_url,'video', 'video') : null)); @endphp
                                    @if ($downloadLocal)
                                        <video width="400" controls="controls" preload="metadata">
                                            <source src="{{ $downloadLocal }}" type="video/mp4">
                                        </video>
                                    @endif
                                </div>

                                {{ html()->hidden('video_file_input_download')->id('file_url_video_download')->value(old('video_file_input_download', $data->download_type === 'Local' ? setBaseUrlWithFileName($data->download_url, 'video', 'video') : ''))->attribute('data-validation', 'iq_video_quality') }}

                                @error('video_file_input_download')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                                <div class="invalid-feedback" id="file-error">{{ __('messages.video_file_field_required') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-5 pt-1 mb-3">
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
                                @php $existingDownloads = old('quality_video_download_type') ? null : ($data->videoDownloadMappings ?? collect()); @endphp
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
                                                        @php $dlUrl = setBaseUrlWithFileName($dl->url,'video', 'video'); @endphp
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
                                                {{ html()->hidden('download_quality_video[]')->id('file_url_download_videoquality' . $idx)->value(old('download_quality_video.' . $idx, $isLocal ? setBaseUrlWithFileName($dl->url, 'video', 'video') : ''))->attribute('data-validation', 'iq_video_quality') }}
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

    </div>
    </form>

    @include('components.media-modal')
@endsection
@push('before-scripts')
    <script>
        // Set validation modal translations - Must be before media.js loads
        window.validationTranslations = {
            validation_errors: '{{ __('messages.validation_errors') }}',
            please_correct_errors: '{{ __('messages.please_correct_errors') }}',
            close: '{{ __('messages.close') }}'
        };
    </script>
@endpush
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
                    errorElement.textContent =
                        "{{ __('messages.discount_must_be_between_zero_and_ninety_nine') }}";
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
                        errorElement.textContent =
                            "{{ __('messages.available_for_must_be_greater_than_access_duration') }}";
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
                        validateAvailableForGreaterThanAccessDuration(availableForInput,
                            accessDurationInput, 'available_for-error');
                    }
                });
            }
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
                            const errs = window.__videoEditCurrentErrors || {};
                            if (errs.description) delete errs.description;
                            Object.keys(errs).forEach(function(k) { if (k === 'description' || (k && k.startsWith('description.'))) delete errs[k]; });
                            window.__videoEditCurrentErrors = errs;
                            if (window.showErrorCountOnTabs && window.__videoEditTabFields) { window.showErrorCountOnTabs(errs, window.__videoEditTabFields); }
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
                var trailervideo = document.querySelector('input[name="trailer_video"]');
                var trailervideourl = document.querySelector('input[name="trailer_url"]');
                if (selectedValue === 'Local') {
                    FileInput.classList.remove('d-none');
                    URLInput.classList.add('d-none');
                    if (trailervideo) {
                        trailervideo.value = trailervideo.value;
                    }
                    if (trailervideourl) {
                        trailervideourl.value = '';
                    }
                } else if (selectedValue === 'URL' || selectedValue === 'YouTube' || selectedValue === 'HLS' ||
                    selectedValue === 'x265' ||
                    selectedValue === 'Vimeo') {
                    URLInput.classList.remove('d-none');
                    FileInput.classList.add('d-none');
                    if (trailervideourl) {
                        trailervideourl.value = trailervideourl.value;
                    }
                    if (trailervideo) {
                        trailervideo.value = '';
                    }
                } else {
                    FileInput.classList.add('d-none');
                    URLInput.classList.add('d-none');
                }
            }


        });

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
                downlaodstatusDataFeild.classList.remove('d-none');
            }
        }



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
            showPlanSelection();
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
                var VideoEmbedInput = document.getElementById('video_embed_input_section');
                var videofile = document.querySelector('input[name="video_file_input"]');
                var videourl = document.querySelector('input[name="video_url_input"]');
                var videoembed = document.getElementById('video_embedded');

                if (selectedtypeValue === 'Local') {
                    VideoFileInput.classList.remove('d-none');
                    VideoURLInput.classList.add('d-none');
                    VideoEmbedInput.classList.add('d-none');
                    videofile.setAttribute('required', 'required');
                    if (videourl) videourl.removeAttribute('required');
                    if (videoembed) videoembed.removeAttribute('required');
                } else if (
                    selectedtypeValue === 'URL' ||
                    selectedtypeValue === 'YouTube' ||
                    selectedtypeValue === 'HLS' ||
                    selectedtypeValue === 'Vimeo' ||
                    selectedtypeValue === 'x265'
                ) {
                    VideoURLInput.classList.remove('d-none');
                    VideoFileInput.classList.add('d-none');
                    VideoEmbedInput.classList.add('d-none');
                    if (videourl) videourl.setAttribute('required', 'required');
                    if (videofile) videofile.removeAttribute('required');
                    if (videoembed) videoembed.removeAttribute('required');
                    validateVideoUrlInput();
                } else if (selectedtypeValue === 'Embedded') {
                    VideoEmbedInput.classList.remove('d-none');
                    VideoFileInput.classList.add('d-none');
                    VideoURLInput.classList.add('d-none');
                    if (videoembed) videoembed.setAttribute('required', 'required');
                    if (videofile) videofile.removeAttribute('required');
                    if (videourl) videourl.removeAttribute('required');
                } else {
                    VideoFileInput.classList.add('d-none');
                    VideoURLInput.classList.add('d-none');
                    VideoEmbedInput.classList.add('d-none');
                    if (videofile) videofile.removeAttribute('required');
                    if (videourl) videourl.removeAttribute('required');
                    if (videoembed) videoembed.removeAttribute('required');
                }
            }

            function validateVideoUrlInput() {
                var videourl = document.querySelector('input[name="video_url_input"]');
                var urlError = document.getElementById('url-error');
                var urlPatternError = document.getElementById('url-pattern-error');

                if (videourl.value === '') {
                    urlError.style.display = 'block';
                    urlPatternError.style.display = 'none';
                    return false;
                } else {
                    urlError.style.display = 'none';
                    selectedValue = document.getElementById('video_upload_type').value;
                    if (selectedValue === 'YouTube') {
                        urlPattern = /^(https?:\/\/)?(www\.youtube\.com|youtu\.?be)\/.+$/;
                        urlPatternError.innerText = '';
                        urlPatternError.innerText = 'Please enter a valid Youtube URL'
                    } else if (selectedValue === 'Vimeo') {
                        urlPattern =
                            /^(https?:\/\/)?(www\.)?(vimeo\.com\/(channels\/[a-zA-Z0-9]+\/|groups\/[^/]+\/videos\/)?\d+)(\/.*)?$/;
                        urlPatternError.innerText = '';
                        urlPatternError.innerText = 'Please enter a valid Vimeo URL'
                    } else {
                        // General URL pattern for other types
                        urlPattern = /^https?:\/\/.+$/;
                        urlPatternError.innerText = 'Please enter a valid URL starting with http:// or https://.'
                    } // Simple URL pattern validation
                    if (!urlPattern.test(videourl.value)) {
                        urlPatternError.style.display = 'block';
                        return false;
                    } else {
                        urlPatternError.style.display = 'none';
                        return true;
                    }
                }
            }
            var initialSelectedValue = document.getElementById('video_upload_type').value;
            handleVideoUrlTypeChange(initialSelectedValue);
            $('#video_upload_type').change(function() {
                var selectedtypeValue = $(this).val();
                handleVideoUrlTypeChange(selectedtypeValue);
            });

            // Real-time validation while typing
            var videourl = document.querySelector('input[name="video_url_input"]');
            if (videourl) {
                videourl.addEventListener('input', function() {
                    validateVideoUrlInput();
                });
            }
        });


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

        $(document).on('change', '.video_quality_type', function() {
            var $container = $(this).closest('.video-inputs-container');
            handleQualityTypeChange($container);
        });

        $(document).ready(function() {
            setTimeout(function() {
                $('.video-inputs-container').each(function() {
                    handleQualityTypeChange($(this));
                });
            }, 100);
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

    <script>
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

        function removeImage(hiddenInputId, removedFlagId) {
            var containerId = hiddenInputId === 'file_urlthumbnail'
                ? 'selectedImageContainerThumbnail'
                : 'selectedImageContainer2';
            var container = document.getElementById(containerId);
            var hiddenInput = document.getElementById(hiddenInputId);
            var removedFlag = document.getElementById(removedFlagId);

            if (container) container.innerHTML = '';
            if (hiddenInput) hiddenInput.value = '';
            if (removedFlag) removedFlag.value = 1;
        }

        function removeTvImage(hiddenInputId, removedFlagId) {
            var container = document.getElementById('selectedImageContainerTv');
            var hiddenInput = document.getElementById(hiddenInputId);
            var removedFlag = document.getElementById(removedFlagId);

            if (container) container.innerHTML = '';
            if (hiddenInput) hiddenInput.value = '';
            if (removedFlag) removedFlag.value = 1;
        }
        // Add Subtitle Functionality
        function toggleSubtitleSection() {
            if ($('#enable_subtitle').is(':checked')) {
                $('#subtitle_section').removeClass('d-none');
                $('.subtitle-language').attr('required', true);

                // Only set 'required' if no existing file
                const fileInput = $('.subtitle-file');
                const fileAlreadyExists = $('#subtitle_file_exists').val() === '1';

                if (!fileAlreadyExists && fileInput.val() === '') {
                    // fileInput.attr('required', true);
                } else {
                    fileInput.removeAttr('required');
                }

            } else {
                $('#subtitle_section').addClass('d-none');
                $('.subtitle-language').removeAttr('required');
                $('.subtitle-file').removeAttr('required');
            }
        }

        function updateSubtitleRemoveButtons() {
            const rows = $('.subtitle-row');
            const hideButtons = rows.length <= 1;
            rows.find('.remove-subtitle').each(function() {
                if (hideButtons) {
                    $(this).addClass('d-none').attr('tabindex', '-1');
                } else {
                    $(this).removeClass('d-none').removeAttr('tabindex');
                }
            });
        }

        // Toggle clips section
        function toggleClipsSection() {
            if ($('#enable_clips').is(':checked')) {
                $('#clips-inputs-container-parent').removeClass('d-none');
            } else {
                $('#clips-inputs-container-parent').addClass('d-none');
            }
        }

        // Initial state
        toggleSubtitleSection();
        toggleClipsSection();
        updateSubtitleRemoveButtons();

        // On change
        $('#enable_subtitle').on('change', toggleSubtitleSection);
        $('#enable_clips').on('change', toggleClipsSection);

        // Add new subtitle row
        let subtitleIndex = {{ $data->subtitles ? count($data->subtitles) : 1 }};

        $('#add-subtitle').on('click', function() {
            let newRow = $(`
                <div class="row gy-3 subtitle-row my-3">
                    <div class="col-md-4">
                        <select name="subtitles[${subtitleIndex}][language]" class="form-control select2 subtitle-language" required>
                            <option value="">{{ __('placeholder.lbl_select_language') }}</option>
                            @foreach ($subtitle_language as $language)
                                <option value="{{ $language->value }}">{{ $language->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">{{ __('validation.required', ['attribute' => 'language']) }}</div>
                    </div>
                    <div class="col-md-4">
                        <input type="file" name="subtitles[${subtitleIndex}][subtitle_file]" class="form-control subtitle-file" required>
                        <div class="invalid-feedback">{{ __('validation.required', ['attribute' => 'subtitle file']) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-3">
                            <input type="checkbox" name="subtitles[${subtitleIndex}][is_default]" class="form-check-input is-default-subtitle" id="is_default_${subtitleIndex}">
                            <label class="form-check-label" for="is_default_${subtitleIndex}">{{ __('movie.lbl_default_subtitle') }}</label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm mt-2 remove-subtitle">
                            <i class="ph ph-trash"></i>
                        </button>
                    </div>
                </div>
            `);
            $('#subtitle-inputs-container').append(newRow);
            subtitleIndex++;
            // Re-initialize select2 for the new select
            newRow.find('.subtitle-language').select2({
                width: '100%',
                placeholder: "{{ __('placeholder.lbl_select_language') }}",
                allowClear: false
            });
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

        // --- Embed Code Validation (Entertainment style) ---
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
            ['video_embedded', 'trailer_embedded'].forEach((id, i) => {
                const input = document.getElementById(id);
                const errorId = i === 0 ? 'video-embed-error' : 'trailer-embed-error';
                if (input) {
                    input.addEventListener('input', () => validateEmbedInput(id, errorId));
                }
            });

            // Form validation
            const form = document.getElementById('form-submit');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const videoType = document.getElementById('video_upload_type')?.value;
                    const trailerType = document.getElementById('trailer_url_type')?.value;

                    let isValid = true;

                    if (videoType === 'Embedded') {
                        isValid = validateEmbedInput('video_embedded', 'video-embed-error');
                    }

                    if (!isValid) {
                        e.preventDefault();
                        return false;
                        $('#submit-button').prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm"></span> {{ trans('messages.save') }}'
                        );
                    }

                    // Only here, after validation passes, set loading/disabled

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

        const videoFieldLabels = {
            'name': '{{ __('video.lbl_title') }}',
            'description': '{{ __('movie.lbl_description') }}',
            'short_desc': '{{ __('movie.lbl_short_desc') }}',
            'duration': '{{ __('movie.lbl_duration') }}',
            'start_time': '{{ __('messages.lbl_skip_intro_start_time') }}',
            'end_time': '{{ __('messages.lbl_skip_intro_end_time') }}',
            'release_date': '{{ __('movie.lbl_release_date') }}',
            'video_upload_type': '{{ __('movie.lbl_video_upload_type') }}',
            'video_url_input': '{{ __('movie.video_url_input') }}',
            'video_file_input': '{{ __('movie.video_file_input') }}',
            'embed_code': '{{ __('movie.lbl_embed_code') }}',
            'enable_quality': '{{ __('movie.lbl_enable_quality') }}',
            'video_quality_type': '{{ __('movie.lbl_video_upload_type') }}',
            'video_quality': '{{ __('movie.lbl_video_quality') }}',
            'enable_subtitle': '{{ __('movie.lbl_enable_subtitle') }}',
            'subtitles': '{{ __('movie.lbl_subtitle_info') }}',
            'meta_title': '{{ __('messages.lbl_meta_title') }}',
            'meta_keywords': '{{ __('messages.lbl_meta_keywords') }}',
            'meta_description': '{{ __('messages.lbl_meta_description') }}',
            'canonical_url': '{{ __('messages.lbl_canonical_url') }}',
            'google_site_verification': '{{ __('messages.lbl_google_site_verification') }}',
            'short_description': '{{ __('messages.lbl_short_description') }}',
            'seo_image': '{{ __('messages.lbl_seo_image') }}',
            'clip_title': '{{ __('messages.lbl_clip_title') }}',
            'clip_upload_type': '{{ __('movie.lbl_video_upload_type') }}',
            'clip_url_input': '{{ __('movie.video_url_input') }}',
            'clip_file_input': '{{ __('movie.video_file_input') }}',
            'clip_embedded': '{{ __('movie.lbl_embed_code') }}',
            'video_upload_type_download': '{{ __('movie.lbl_quality_video_download_type') }}',
            'video_url_input_download': '{{ __('movie.download_url') }}',
            'video_file_input_download': '{{ __('messages.lbl_download_file') }}',
            'enable_download_quality': '{{ __('movie.lbl_enable_quality') }}',
            'quality_video_download_type': '{{ __('movie.lbl_quality_video_download_type') }}',
            'quality_video_download_type.*': '{{ __('movie.lbl_quality_video_download_type') }}',
            'video_download_quality': '{{ __('movie.lbl_video_download_quality') }}',
            'video_download_quality.*': '{{ __('movie.lbl_video_download_quality') }}',
            'download_quality_video_url': '{{ __('movie.download_url') }}',
            'download_quality_video_url.*': '{{ __('movie.download_url') }}',
            'download_quality_video': '{{ __('messages.lbl_download_file') }}',
            'download_quality_video.*': '{{ __('messages.lbl_download_file') }}',
            'price': '{{ __('messages.lbl_price') }}',
            'purchase_type': '{{ __('messages.purchase_type') }}',
            'access_duration': '{{ __('messages.lbl_access_duration') }}',
            'available_for': '{{ __('messages.lbl_available_for') }}',
            'discount': '{{ __('messages.lbl_discount') }}',
            'plan_id': '{{ __('movie.lbl_select_plan') }}',
            'access': '{{ __('movie.lbl_movie_access') }}',
            'status': '{{ __('plan.lbl_status') }}',
            'is_restricted': '{{ __('movie.lbl_age_restricted') }}',
            'download_status': '{{ __('movie.lbl_download_status') }}',
            'enable_seo': '{{ __('movie.lbl_enable_seo-setting') }}',
            'clips': '{{ __('messages.lbl_clips') }}',
            'download_info': '{{ __('movie.lbl_download_info') }}',
            'subtitles.*.language': '{{ __('messages.lbl_subtitle_language') }}',
            'subtitles.*.subtitle_file': '{{ __('movie.lbl_subtitle_file') }}',
        };

        const videoTabFields = {
            'pills-movie': ['name', 'description', 'short_desc', 'price', 'purchase_type', 'access_duration',
                'available_for', 'discount', 'plan_id', 'access', 'status'
            ],
            'pills-basic': ['duration', 'start_time', 'end_time', 'release_date', 'is_restricted', 'download_status'],
            'pills-quality': ['video_upload_type', 'video_url_input', 'video_file_input', 'embed_code',
                'enable_quality', 'video_quality_type', 'video_quality'
            ],
            'pills-Subtitle': ['enable_subtitle', 'subtitles', 'subtitles.*'],
            'pills-clip': ['enable_clips', 'clip_title.*', 'clip_upload_type.*', 'clip_poster_url.*',
                'clip_tv_poster_url.*', 'clip_url_input.*', 'clip_file_input.*', 'clip_embedded.*'
            ],
            'pills-seo': ['meta_title', 'meta_keywords', 'meta_description', 'canonical_url',
                'google_site_verification', 'short_description', 'seo_image', 'enable_seo'
            ],
            'pills-download': ['video_upload_type_download', 'video_url_input_download', 'video_file_input_download',
                'enable_download_quality', 'quality_video_download_type.*', 'video_download_quality.*',
                'download_quality_video_url.*', 'download_quality_video.*'
            ]
        };

        let currentErrors = {};
        window.__videoEditCurrentErrors = currentErrors;
        window.__videoEditTabFields = videoTabFields;

        document.addEventListener('DOMContentLoaded', function() {
            @if ($errors->any())
                const serverErrors = @json($errors->toArray());
                currentErrors = serverErrors || {};
                window.__videoEditCurrentErrors = currentErrors;
                if (window.showValidationModal) window.showValidationModal(serverErrors, videoFieldLabels);
                if (window.showErrorCountOnTabs) window.showErrorCountOnTabs(serverErrors, videoTabFields);
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
                        window.successSnackbar(response.message || 'Video saved successfully');

                        setTimeout(function() {
                            window.location.href = response.redirect;
                        }, 1500);
                    } else {
                        $('#error_message').text(response.message ||
                            'An error occurred while saving the video');
                        $('#submit-button').prop('disabled', false).html(
                            '{{ trans('messages.save') }}');
                    }
                },
                error: function(xhr) {
                    $('#submit-button').prop('disabled', false).html('{{ trans('messages.save') }}');

                    if (xhr.responseJSON?.errors) {
                        currentErrors = xhr.responseJSON.errors || {};
                        window.__videoEditCurrentErrors = currentErrors;
                        if (window.showValidationModal) {
                            window.showValidationModal(xhr.responseJSON.errors, videoFieldLabels);
                        }
                        if (window.showErrorCountOnTabs) {
                            window.showErrorCountOnTabs(xhr.responseJSON.errors, videoTabFields);
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
                            'An error occurred while saving the video');
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
            var errId = base === 'video_upload_type' ? 'video-upload-type-error' : (base === 'video_url_input' ? 'url-error' : (base === 'video_file_input' ? 'file-error' : (base === 'embed_code' ? 'embed-error' : (base === 'video_embedded' ? 'video-embed-error' : (base === 'meta_title' ? 'meta_title_error' : (base === 'description' ? 'desc-error' : base + '-error'))))));
            var errEl = document.getElementById(errId); if (errEl) errEl.style.display = 'none';
            if (base === 'video_url_input') { var upe = document.getElementById('url-pattern-error'); if (upe) upe.style.display = 'none'; }
            var $sibs = $(el).siblings('.invalid-feedback'); if ($sibs.length) $sibs.hide(); else $(el).closest('.col-md-3, .col-md-4, .col-md-6, .col-lg-3, .col-lg-4, .col-lg-6, .col-lg-12, .mb-3').find('.invalid-feedback').first().hide();

            var errs = window.__videoEditCurrentErrors || currentErrors || {};
            var idx = getArrayFieldIndex(el, name); var changed = false;
            if (errs[fieldKey]) { delete errs[fieldKey]; changed = true; }
            if (errs[name]) { delete errs[name]; changed = true; }
            if (idx !== null) { var indexedKey = fieldKey + '.' + idx; if (errs[indexedKey]) { delete errs[indexedKey]; changed = true; } Object.keys(errs).forEach(function(k) { if (k.indexOf(indexedKey + '.') === 0) { delete errs[k]; changed = true; } }); }
            var dottedKey = String(name).replace(/\[(\d+)\]/g, '.$1').replace(/\[(\w+)\]/g, '.$1');
            if (dottedKey && dottedKey !== name && errs[dottedKey]) { delete errs[dottedKey]; changed = true; }
            if (changed) { currentErrors = errs; window.__videoEditCurrentErrors = errs; if (window.showErrorCountOnTabs) window.showErrorCountOnTabs(errs, window.__videoEditTabFields || videoTabFields); }
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
        });
    </script>
@endpush

@once
    <style>
        .media-thumb-10 {
            width: 10rem;
            height: 10rem;
        }
    </style>
@endonce
