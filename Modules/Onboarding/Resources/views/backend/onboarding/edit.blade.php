@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection


@section('content')
    <x-back-button-component route="backend.onboardings.index" />
    {{ html()->form('PUT', route('backend.onboardings.update', $onboarding->id))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->class('requires-validation')->attribute('novalidate', 'novalidate')->open() }}
    <div class="card">
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-12 col-xl-3 position-relative">
                    {{ html()->label(__('messages.image') . ' <span class="text-danger">*</span>', 'Image')->class('form-label') }}
                    <div class="input-group btn-file-upload">
                        {{ html()->button(__('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image')))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer1')->attribute('data-hidden-input', 'file_url1') }}

                        {{ html()->text('image_input1')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Image Input 1')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainer1')->attribute('data-hidden-input', 'file_url1')->attribute('aria-describedby', 'basic-addon1') }}
                    </div>

                    <div class="mb-3 uploaded-image" id="selectedImageContainer1">
                        @if ($onboarding->file_url)
                            <img src="{{ $onboarding->file_url }}" class="img-fluid mb-2 box-preview-image">
                            <span class="remove-media-icon"
                                onclick="removeImage('selectedImageContainer1', 'file_url1', 'remove_image_flag')">×</span>
                        @endif
                    </div>
                    {{ html()->hidden('file_url')->id('file_url1')->attribute('required', 'required')->value($onboarding->file_url) }}
                    {{ html()->hidden('remove_image')->id('remove_image_flag')->value(0) }}
                    @error('file_url')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="file_url1-error">{{ __('messages.image_is_required') }}</div>
                </div>
                <div class="col-xl-9">
                    <div class="row gy-3">
                        <div class="col-md-6 col-lg-6">
                            <div class="mb-3">
                                {{ html()->label(__('messages.title') . '<span class="text-danger">*</span>', 'title')->class('form-label') }}
                                {{ html()->text('title', $onboarding->title)->class('form-control')->id('title')->placeholder(__('onboarding.lbl_onboarding_title'))->attribute('required', 'required')->attribute('maxlength', '50') }}
                                <div class="d-flex justify-content-between mt-1">
                                    <div>
                                        @error('title')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                        <div class="invalid-feedback" id="title-error">{{ __('messages.title_required') }}</div>
                                    </div>
                                    <small class="text-muted" id="title-char-count">0 / 50</small>
                                </div>
                            </div>
                            <div>
                                {{ html()->label(__('messages.lbl_status'), 'status')->class('form-label') }}
                                <div class="d-flex justify-content-between align-items-center form-control">
                                    {{ html()->label(__('messages.active'), 'status')->class('form-label mb-0') }}
                                    <div class="form-check form-switch">
                                        {{ html()->hidden('status', 0) }}
                                        {{ html()->checkbox('status', $onboarding->status)->class('form-check-input')->id('status')->value(1) }}
                                    </div>
                                </div>
                                @error('status')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-6">
                            {{ html()->label(__('plan.lbl_description') . ' <span class="text-danger">*</span>', 'description')->class('form-label') }}
                            {{ html()->textarea('description', $onboarding->description)->class('form-control')->id('description')->placeholder(__('onboarding.lbl_onboarding_description'))->rows('5')->attribute('required', 'required')->attribute('maxlength', '120') }}
                            <div class="d-flex justify-content-between mt-1">
                                <div>
                                    @error('description')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                    <div class="invalid-feedback" id="description-error">{{ __('messages.description_required') }}
                                    </div>
                                </div>
                                <small class="text-muted" id="description-char-count">0 / 120</small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="d-grid d-sm-flex justify-content-sm-end gap-3">
        {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}
    </div>

    {{ html()->form()->close() }}

    @include('components.media-modal')
    <script>
        function removeImage(hiddenInputId, removedFlagId) {
            var container = document.getElementById('selectedImageContainer1');
            var hiddenInput = document.getElementById(hiddenInputId);
            var removedFlag = document.getElementById(removedFlagId);

            container.innerHTML = '';
            hiddenInput.value = '';
            removedFlag.value = 1;
        }
    </script>
@endsection

@push('after-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const titleInput = document.getElementById('title');
    const titleCharCountElement = document.getElementById('title-char-count');
    const MAX = 50;

    if (!titleInput || !titleCharCountElement) return;

    function enforceLimit() {
        if (titleInput.value.length > MAX) {
            titleInput.value = titleInput.value.substring(0, MAX); // 🔥 HARD CUT
        }
        titleCharCountElement.textContent =
            titleInput.value.length + ' / ' + MAX;
    }

    titleInput.addEventListener('input', enforceLimit);
    titleInput.addEventListener('paste', () => setTimeout(enforceLimit, 0));

    // On page load (IMPORTANT for old data)
    enforceLimit();
});
</script>
@endpush
