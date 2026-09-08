@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <x-back-button-component route="backend.banners.index" />

    {{ html()->form('POST', route('backend.banners.store'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->class('requires-validation')->attribute('novalidate', 'novalidate')->open() }}
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="row gy-3">
                <div class="col-md-6 col-lg-4">
                    <div class="position-relative">
                        {{ html()->label(__('banner.title'), 'poster')->class('form-label') }}
                        <div class="input-group btn-file-upload">
                            {{ html()->button('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image'))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerPoster')->attribute('data-hidden-input', 'poster_url')->style('height:13.6rem') }}

                            {{ html()->text('poster_input')->class('form-control')->placeholder('placeholder.lbl_image')->attribute('aria-label', 'Poster Image') }}

                            {{ html()->hidden('poster_url')->id('poster_url')->value(old('poster_url', isset($data) ? $data->poster_url : '')) }}
                            {{ html()->hidden('remove_image')->id('remove_image_flag2')->value(0) }}

                        </div>
                        <div class="uploaded-image" id="selectedImageContainerPoster">
                            @if (old('poster_url', isset($data) ? $data->poster_url : ''))
                                <img src="{{ old('poster_url', isset($data) ? $data->poster_url : '') }}"
                                    class="img-fluid avatar-150">
                            @endif
                        </div>
                        <small class="text-danger">{{ __('messages.note_recommended_banner_image_size_1') }}</small>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4" id="web-banner-div">
                    <div class="position-relative">
                        {{ html()->label(__('banner.lbl_web_banner'), 'file_url')->class('form-label') }}
                        <div class="input-group btn-file-upload">
                            {{ html()->button('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image'))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerThumbnail')->attribute('data-hidden-input', 'file_url_image')->style('height:13.6rem') }}

                            {{ html()->text('thumbnail_input')->class('form-control')->placeholder(__('placeholder.lbl_image'))->attribute('aria-label', 'Thumbnail Image') }}

                            {{ html()->hidden('file_url')->id('file_url_image')->value(old('file_url', isset($data) ? $data->file_url : '')) }}
                            {{ html()->hidden('remove_image')->id('remove_image_flag1')->value(0) }}
                        </div>
                        <div class="uploaded-image" id="selectedImageContainerThumbnail">
                            @if (old('file_url', isset($data) ? $data->file_url : ''))
                                <img src="{{ old('file_url', isset($data) ? $data->file_url : '') }}"
                                    class="img-fluid avatar-150">
                            @endif
                        </div>
                        <small class="text-danger">{{ __('messages.note_recommended_banner_image_size_2') }}</small>
                    </div>
                </div>


                <div class="col-md-6 col-lg-4" id="tv-banner-div">
                    <div class="position-relative">
                        {{ html()->label(__('banner.lbl_tv_banner'), 'poster_tv')->class('form-label') }}
                        <div class="input-group btn-file-upload">
                            {{ html()->button('<i class="ph ph-image"></i>' . __('messages.lbl_choose_image'))->class('input-group-text form-control')->type('button')->attribute('data-bs-toggle', 'modal')->attribute('data-bs-target', '#exampleModal')->attribute('data-image-container', 'selectedImageContainerPosterTv')->attribute('data-hidden-input', 'poster_tv_url')->style('height:13.6rem') }}

                            {{ html()->text('poster_input')->class('form-control')->placeholder('placeholder.lbl_image')->attribute('aria-label', 'Poster Image') }}

                            {{ html()->hidden('poster_tv_url')->id('poster_tv_url')->value(old('poster_tv_url', isset($data) ? $data->poster_tv_url : '')) }}
                            {{ html()->hidden('remove_image')->id('remove_image_flag3')->value(0) }}

                        </div>
                        <div class="uploaded-image" id="selectedImageContainerPosterTv">
                            @if (old('poster_tv_url', isset($data) ? $data->poster_tv_url : ''))
                                <img src="{{ old('poster_tv_url', isset($data) ? $data->poster_tv_url : '') }}"
                                    class="img-fluid avatar-150">
                            @endif
                        </div>
                        <small class="text-danger">{{ __('messages.note_recommended_banner_image_size_2') }}</small>
                    </div>
                </div>
                <div class="row gy-3">
                    <div class="col-md-4 col-lg-4 mt-3">
                        {{ html()->label(__('banner.lbl_banner_for') . '<span class="text-danger">*</span>', 'banner_for')->class('form-label') }}
                        {{ html()->select(
                                'banner_for',
                                [
                                    '' => __('messages.lbl_select_banner_for'),
                                    'home' => __('messages.lbl_home'),
                                    'movie' => __('messages.movie'),
                                    'tv_show' => __('messages.tvshow'),
                                    'video' => __('messages.video'),
                                    'livetv' => __('messages.livetv'),
                                    'promotional' => __('messages.lbl_promotions'),
                                ],
                                old('banner_for'),
                            )->class('form-control select2')->id('banner_for')->attribute('required', 'required') }}
                        @error('banner_for')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="banner-for-error">{{ __('messages.banner_for_field_required') }}</div>
                    </div>
                    <div class="col-md-6 col-lg-4  mt-3">


                        {{ html()->label(__('banner.lbl_status'), 'status')->class('form-label') }}
                        <div class="d-flex justify-content-between form-control">
                            {{ html()->label(__('messages.active'), 'status')->class('form-label mb-0 text-body') }}
                            <div class="form-check form-switch">
                                {{ html()->hidden('status', 0) }}
                                {{ html()->checkbox('status', old('status', 1))->class('form-check-input')->id('status')->value(1) }}
                                @error('status')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                    </div>
                </div>

                <div class="row gy-3" id="banner-type-div">
                    <div class="col-md-4 col-lg-4 mt-3">
                        {{ html()->label(__('banner.lbl_type') . '<span class="text-danger" id="type-required">*</span>', 'type')->class('form-label') }}
                        {{ html()->select('type', ['' => __('placeholder.lbl_select_type')] + $types, old('type'))->class('form-control select2')->id('type')->attribute('required', 'required') }}
                        @error('type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">{{ __('messages.type_field_required') }}</div>
                    </div>
                    {{ html()->hidden('type_id')->id('type_id') }}
                    {{ html()->hidden('type_name')->id('type_name') }}
                    <div class="col-md-4 col-lg-4 mt-3">
                        {{ html()->label(__('banner.lbl_name') . '<span class="text-danger" id="name-required">*</span>', 'name')->class('form-label') }}
                        {{ html()->select('name_id', ['' => __('messages.select_name')] + [], old('name_id'))->class('form-control select2')->id('name_id')->attribute('required', 'required')->attribute('data-placeholder', __('messages.select_name')) }}
                        @error('name_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="name-error">{{ __('messages.name_field_required') }}</div>
                    </div>
                </div>

                <div class="row gy-3" id="banner-title-div" style="display: none;">
                    <div class="col-md-4 col-lg-4 mt-3">
                        {{ html()->label(__('banner.lbl_title') . '<span class="text-danger" id="title-required">*</span>', 'type')->class('form-label') }}
                        {{ html()->text('title')->class('form-control')->id('title')->placeholder(__('placeholder.lbl_banner_title'))->attribute('aria-label', 'Title')->attribute('required', 'required')->attribute('maxlength', '120') }}
                        <small class="text-muted d-block mt-1">
                            <span id="title-char-count">0</span> / 120 {{ __('messages.characters') }}
                        </small>
                        @error('title')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                        <div class="invalid-feedback" id="title-error">Title field is required</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-grid d-sm-flex justify-content-sm-end gap-3">
        {{ html()->submit(__('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}
    </div>
    {{ html()->form()->close() }}

    @include('components.media-modal', compact('page_type'))
@endsection

@push('after-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function readURL(input, imgElement) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        imgElement.attr('src', e.target.result).show();
                        $('#removeImageBtn').removeClass('d-none');
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            $('#file_url').change(function() {
                readURL(this, $('#selectedImage'));
            });

            $('#removeImageBtn').click(function() {
                $('#selectedImage').attr('src', '').hide();
                $('#file_url').val('');
                $(this).addClass('d-none');
            });
        });

        function getNames(type, selectedNameId = "") {
            var get_names_list = "{{ route('backend.banners.index_list', ['type' => ':type']) }}".replace(':type', type);

            // Clear the name field first to prevent old records from showing
            if ($('#name_id').hasClass('select2-hidden-accessible')) {
                $('#name_id').select2('destroy');
            }
            $('#name_id').empty();

            $.ajax({
                url: get_names_list,
                success: function(result) {
                    var formattedResult = [{
                        id: '',
                        text: "{{ __('messages.select_name') }}"
                    }]; // Default option

                    var names = result.map(function(item) {
                        return {
                            id: item.id,
                            text: item.name,
                            thumbnail_url: item.thumbnail_url,
                            poster_url: item.poster_url,
                            poster_tv_url: item.poster_tv_url
                        };
                    });

                    formattedResult = formattedResult.concat(names); // Append fetched names

                    $('#name_id').select2({
                        width: '100%',
                        data: formattedResult,
                        placeholder: $('#name_id').data('placeholder') ||
                            "{{ __('messages.select_name') }}",
                        allowClear: true
                    });

                    if (selectedNameId != "") {
                        $('#name_id').val(selectedNameId).trigger('change');
                    } else {
                        $('#name_id').val(null).trigger('change');
                    }
                }
            });
        }


        // Validation functions for banner form
        function validateField(field) {
            const fieldId = field.id;
            const fieldError = document.getElementById(`${fieldId}-error`);
            let isValid = true;

            if (!field.value.trim()) {
                if (fieldError) {
                    fieldError.style.display = 'block';
                }
                isValid = false;
            } else {
                if (fieldError) {
                    fieldError.style.display = 'none';
                }
            }

            return isValid;
        }

        function validateBannerForm() {
            const bannerForField = document.getElementById('banner_for');
            let isValid = true;

            if (bannerForField && bannerForField.value === 'promotional') {
                // For promotional banners: validate title only
                const titleField = document.getElementById('title');

                if (titleField && titleField.required) {
                    if (!validateField(titleField)) {
                        isValid = false;
                    }
                }
            } else if (bannerForField && bannerForField.value && bannerForField.value !== 'promotional') {
                // For non-promotional banners: validate type and name only
                const typeField = document.getElementById('type');
                const nameField = document.getElementById('name_id');

                if (typeField && typeField.required) {
                    if (!validateField(typeField)) {
                        isValid = false;
                    }
                }

                if (nameField && nameField.required) {
                    if (!validateField(nameField)) {
                        isValid = false;
                    }
                }
            } else {
                // For all other cases, validate all required fields
                const requiredFields = document.querySelectorAll('#form-submit [required]');
                requiredFields.forEach(field => {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });
            }

            console.log('Banner form validation result:', isValid);
            return isValid;
        }

        $(document).ready(function() {
            // Initialize Select2 for all dropdowns
            $('.select2').select2({
                width: '100%',
                placeholder: function() {
                    return $(this).data('placeholder') || 'Select an option';
                },
                allowClear: true
            });

            // Check initial state for banner_for field with a small delay to ensure DOM is ready
            setTimeout(function() {
                $("#banner_for").trigger('change');
            }, 100);

            // Override form submission for banner form
            $('#form-submit').on('submit', function(e) {
                e.preventDefault();

                if (!validateBannerForm()) {
                    return false;
                }

                // Handle null values based on banner type
                var bannerForValue = $('#banner_for').val();

                if (bannerForValue === 'promotional') {
                    // For promotional: set type and name to null
                    $('#type').val('');
                    $('#name_id').val('');
                    $('#type_id').val('');
                    $('#type_name').val('');
                } else {
                    // For non-promotional: set title to null
                    $('#title').val('');
                }

                // If validation passes, submit the form
                this.submit();
            });

            $('#type').on('change', function() {
                var type = $(this).val();
                var typeName = $('#type option:selected').text();

                if (type === 'promotional') {
                    // Handle promotional type
                    $('#type_id').val(type);
                    $('#type_name').val(typeName);
                    $('#name_id').empty().trigger('change.select2');
                } else if (type) {
                    // Handle regular types
                    $('#type_id').val(type);
                    $('#type_name').val(typeName);
                    $('#name_id').empty().trigger('change.select2');
                    getNames(type);
                } else {
                    $('#name_id').empty().trigger('change.select2');
                }
            });

            $('#name_id').on('change', function() {
                var selectedNameId = $(this).val();
                var selectedNameText = $('#name_id option:selected').text();

                if (selectedNameId) {
                    $('#type_id').val(selectedNameId);
                    $('#type_name').val(selectedNameText);
                } else {
                    $('#type_id').val('');
                    $('#type_name').val('');
                }
            });

            $('#name_id').on('change', function() {
                
                var selectedOption = $('#name_id').select2('data')[0];
                if (selectedOption) {
                    var thumbnailUrl = selectedOption.thumbnail_url;
                    var posterUrl = selectedOption.poster_url;
                    var posterTvUrl = selectedOption.poster_tv_url;

                    console.log('thumbnailUrl', thumbnailUrl, 'posterUrl', posterUrl, 'posterTvUrl',
                        posterTvUrl);


                    if (thumbnailUrl) {
                        $('#file_url_image').val(thumbnailUrl);
                        $('#selectedImageContainerThumbnail').html(`
          <div style="position: relative; display: inline-block;">
            <img src="${thumbnailUrl}" class="img-fluid avatar-150">
            <span class="remove-media-icon"
                  style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                  onclick="removeImage('selectedImageContainerThumbnail', 'file_url_image', 'remove_image_flag1')">×</span>
          </div>
        `);
                    } else {
                        $('#selectedImageContainerThumbnail').html('');
                        $('#file_url_image').val('');
                    }

                    if (posterUrl) {
                        $('#poster_url').val(posterUrl);
                        $('#selectedImageContainerPoster').html(`
          <div style="position: relative; display: inline-block;">
            <img src="${posterUrl}" class="img-fluid avatar-150">
            <span class="remove-media-icon"
                  style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                  onclick="removeImage('selectedImageContainerPoster', 'poster_url', 'remove_image_flag2')">×</span>
          </div>
            `);
                    } else {
                        $('#selectedImageContainerPoster').html('');
                        $('#poster_url').val('');
                    }
                    if (posterTvUrl) {
                        $('#poster_tv_url').val(posterTvUrl);
                        $('#selectedImageContainerPosterTv').html(`
          <div style="position: relative; display: inline-block;">
            <img src="${posterTvUrl}" class="img-fluid avatar-150">
            <span class="remove-media-icon"
                  style="cursor: pointer; font-size: 24px; position: absolute; top: 0; right: 0; color: red;"
                  onclick="removeTvImage('selectedImageContainerPosterTv', 'poster_tv_url', 'remove_image_flag3')">×</span>
          </div>
        `);
                    } else {
                        $('#selectedImageContainerPosterTv').html('');
                        $('#poster_tv_url').val('');
                    }
                }
            });
        });

        function removeImage(containerId, hiddenInputId, removedFlagId) {
            var container = document.getElementById(containerId);
            var hiddenInput = document.getElementById(hiddenInputId);
            var removedFlag = document.getElementById(removedFlagId);

            container.innerHTML = '';
            hiddenInput.value = '';
            removedFlag.value = 1;
        }

        window.removeImage = removeImage;

        function removeTvImage(containerId, hiddenInputId, removedFlagId) {
            var container = document.getElementById(containerId);
            var hiddenInput = document.getElementById(hiddenInputId);
            var removedFlag = document.getElementById(removedFlagId);

            container.innerHTML = '';
            hiddenInput.value = '';
            removedFlag.value = 1;
        }

        window.removeTvImage = removeTvImage;

        $('#removeImageBtn1').click(function() {
            removeImage('selectedImageContainerThumbnail', 'file_url_image', 'remove_image_flag1');
        });

        $('#removeImageBtn2').click(function() {
            removeImage('selectedImageContainerPoster', 'poster_url', 'remove_image_flag2');
        });

        // $('#type').change(function() {
        //     let selectedType = $(this).val();

        //     let options = {
        //         home: 'Home',
        //     };

        //     if (selectedType === 'movie') {
        //         options.movie = 'Movie';
        //     } else if (selectedType === 'tvshow') {
        //         options.tv_show = 'TV Show';
        //     } else if (selectedType === 'video') {
        //         options.video = 'Video';
        //     }

        //     let $bannerFor = $('#banner_for');
        //     $bannerFor.empty();

        //     $bannerFor.append(new Option("{{ __('placeholder.lbl_select_banner_for') }}", ""));

        //     $.each(options, function(value, text) {
        //         $bannerFor.append(new Option(text, value));
        //     });

        //     $bannerFor.trigger('change');
        // });
        // Character counter for title field
        function updateTitleCharCount() {
            var titleInput = $('#title');
            var charCount = $('#title-char-count');
            if (titleInput.length && charCount.length) {
                var currentLength = titleInput.val().length;
                charCount.text(currentLength);
                if (currentLength > 120) {
                    charCount.addClass('text-danger');
                } else {
                    charCount.removeClass('text-danger');
                }
            }
        }

        // Initialize character counter on page load and when title field is shown
        $(document).ready(function() {
            updateTitleCharCount();
            $('#title').on('input keyup paste', function() {
                updateTitleCharCount();
            });
        });

        $("#banner_for").on('change', function() {
            var selectedBannerFor = $(this).val();
            var typeSelect = $('#type');
            var nameSelect = $('#name_id');
            var titleInput = $('#title');
            var typeRequired = $('#type-required');
            var nameRequired = $('#name-required');
            var titleRequired = $('#title-required');

            if (selectedBannerFor === 'promotional') {
                // For promotional: hide type/name fields, show title field
                $('#banner-type-div').hide();
                $('#banner-title-div').show();
                // Hide web banner and TV banner fields
                $('#web-banner-div').hide();
                $('#tv-banner-div').hide();

                // Make type and name not required, title required
                typeSelect.removeAttr('required');
                nameSelect.removeAttr('required');
                titleInput.attr('required', 'required');
                typeRequired.hide();
                nameRequired.hide();
                titleRequired.show();
                
                // Update character counter when title field is shown
                setTimeout(function() {
                    updateTitleCharCount();
                }, 100);

                // Clear type and name selections
                typeSelect.val('').trigger('change.select2');
                nameSelect.empty().trigger('change.select2');
                $('#type_id').val('');
                $('#type_name').val('');
            } else {
                // For non-promotional: show type/name fields, hide title field
                $('#banner-type-div').show();
                $('#banner-title-div').hide();
                // Show web banner and TV banner fields
                $('#web-banner-div').show();
                $('#tv-banner-div').show();

                // Make type and name required, title not required
                typeSelect.attr('required', 'required');
                nameSelect.attr('required', 'required');
                titleInput.removeAttr('required');
                typeRequired.show();
                nameRequired.show();
                titleRequired.hide();

                // Clear name field first to prevent old records from showing
                nameSelect.empty().trigger('change.select2');
                $('#type_id').val('');
                $('#type_name').val('');

                // Set type to selected banner_for value and load names
                if (selectedBannerFor) {
                    if (selectedBannerFor === 'tv_show') {
                        selectedBannerFor = 'tvshow';
                    }
                    $('#type').val(selectedBannerFor).trigger('change.select2');
                    getNames(selectedBannerFor);
                }
            }
        });
    </script>
@endpush
