@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="bd-example">
                <nav>
                    <div class="mb-3 nav nav-underline nav-tabs justify-content-between p-0 border-bottom rounded-0"
                        id="nav-tab" role="tablist">
                        <div class="d-flex align-items-center gap-3">
                            {{-- <button class="nav-link d-flex align-items-center rounded-0" id="nav-upload-files-tab" data-bs-toggle="tab" data-bs-target="#nav-upload" type="button" role="tab" aria-controls="nav-upload" aria-selected="true">{{__('messages.upload_media')}}</button> --}}
                            <button class="nav-link rounded-0 active" id="nav-media-library-tab" data-bs-toggle="tab"
                                data-bs-target="#nav-media" type="button" role="tab" aria-controls="nav-media"
                                aria-selected="false">{{ __('messages.view_library') }}</button>

                        </div>
                        {{-- <div class="media-search py-2 " id="media-search-containers">
                            <div class="d-flex">
                                <input type="text" id="media-search" class="form-control"
                                    placeholder="{{ __('messages.search_media') }}">
                                <button class="btn text-danger close-icon d-none px-2" type="button" id="clear-search">
                                    <i class="ph ph-x"></i> <!-- Change this icon to your desired close icon -->
                                </button>
                            </div>

                        </div> --}}
                    </div>
                </nav>
                <div class="tab-content iq-tab-fade-up" id="nav-tab-content">
                    {{-- <div class="tab-pane fade" id="nav-upload" role="tabpanel" aria-labelledby="nav-upload-files-tab">
                        {{ html()->form('POST', route('backend.media-library.store-data'))->id('form-submit')->attribute('enctype', 'multipart/form-data')
                            ->class('requires-validation')  // Add the requires-validation class
                            ->attribute('novalidate', 'novalidate')
                            ->open() }}
                        @csrf
                        <div class="col-12">
                            <div class="text-center mb-3">

                                <div class="input-group btn-file-upload">
                                    {{ html()->button(__('<i class="ph ph-image"></i>'. __('messages.lbl_choose_image')))
                                        ->class('input-group-text form-control')
                                        ->type('button')
                                        ->attribute('onclick', "document.getElementById('file_url_media').click()")
                                        ->style('height:16rem')
                                    }}
                                    {{ html()->file('file_url[]')
                                        ->id('file_url_media')
                                        ->class('form-control')
                                        ->attribute('accept', '.jpeg, .jpg, .png, .gif, .mov, .mp4, .avi')
                                        ->attribute('multiple', true)
                                        ->attribute('required', true)
                                        ->style('display: none;')
                                        ->required()
                                    }}
                                </div>
                                <div class="uploaded-image" id="selectedImageContainerThumbnail">
                                    @if (old('file_url', isset($data) ? $data->file_url : ''))
                                        <img src="{{ old('file_url', isset($data) ? $data->file_url : '') }}" class="img-fluid mb-2" style="max-width: 100px; max-height: 100px;">
                                    @endif
                                </div>
                                <div class="invalid-feedback" id="file_url_media-error">Please upload a file.</div>
                            </div>
                        </div>
                            <div id="uploadedImages" class="my-3 d-flex flex-wrap align-items-center gap-3"></div>
                            <div class="text-end">
                                {{ html()->submit(trans('messages.upload'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}

                            </div>

                        {{ html()->form()->close() }}
                    </div> --}}
                    <div class="tab-pane fade show active" id="nav-media" role="tabpanel"
                        aria-labelledby="nav-media-library-tab" style="position: relative;">
                        <div class="media-scroll pe-3">
                            <h6 id="no_data" class="text-center"></h6>
                            <div class="" id="media-container">
                                {{-- <div class="d-flex gap-5 flex-wrap justify-content-center" id="media-container"> --}}

                                @include('components.folder-browser')
                            </div>
                        </div>

                        <div id="loading-spinner" class="text-center mt-3"
                            style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">{{ __('season.lbl_loading') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if (session('success'))
            <div class="snackbar" id="snackbar">
                <div class="d-flex justify-content-around align-items-center">
                    <p class="mb-0">{{ session('success') }}</p>
                    <a href="#" class="dismiss-link text-decoration-none text-success"
                        onclick="dismissSnackbar(event)">{{ __('messages.dismiss') }}</a>
                </div>
            </div>
        @endif
    @endsection

    @push('after-scripts')
        <script src="{{ asset('js/form/index.js') }}" defer></script>
        <script>
            // Check if baseUrl already exists, if not create it
            if (typeof baseUrl === 'undefined') {
                var baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content');
            }
            let page = 1;
            let loading = false;
            let hasMore = @json($hasMore);


            function deleteImage(url, type, fileName, folderName) {
                const i18n = {
                    delete_confirm_title: @json(__('frontend.delete_confirm_title')),
                    delete_confirm_text: @json(__('frontend.delete_confirm_text')),
                    delete_confirm_ok: @json(__('frontend.delete_confirm_ok')),
                    deleted_title: @json(__('frontend.deleted_title')),
                    deleted_text: @json(__('frontend.deleted_text')),
                    delete_error_title: @json(__('frontend.delete_error_title')),
                    delete_error_text: @json(__('frontend.delete_error_text')),
                    from_folder_suffix: @json(__('frontend.from_folder_suffix')),
                    type_video: @json(__('frontend.video')),
                    type_image: @json(__('frontend.image')),
                    type_file: @json(__('frontend.file')),
                    cancel: @json(__('frontend.cancel')),
                    close: @json(__('frontend.close')),
                };

                const fileTypeText = type === 'video' ? i18n.type_video : (type === 'image' ? i18n.type_image : i18n.type_file);
                const displayName = fileName || `this ${fileTypeText}`;
                const folderDisplay = folderName && folderName !== 'default' && folderName !== 'Folders' ?
                    ' ' + i18n.from_folder_suffix.replace(':folder', folderName) :
                    '';

                Swal.fire({
                        title: i18n.delete_confirm_title.replace(':type', String(fileTypeText).toLowerCase()),
                        text: i18n.delete_confirm_text.replace(':name', displayName).replace(':folder', folderDisplay),
                        icon: undefined,
                        iconHtml: '<i class="ph ph-trash text-warning warning-icon" ></i>',
                        customClass: {
                            icon: 'swal2-icon--custom'
                        },
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: i18n.delete_confirm_ok,
                        cancelButtonText: i18n.cancel,
                        reverseButtons: true,
                    })
                    .then((result) => {
                        if (result.isConfirmed) {
                            fetch(`${baseUrl}/app/media-library/destroy`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({ url })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    console.log('Delete response data:', data);
                                    if (data.success) {
                                        const mediaNode = document.querySelector(`img[src="${url}"]`) || document
                                            .querySelector(`video source[src="${url}"]`);
                                        if (mediaNode) {
                                            const column = mediaNode.closest('.col-md-2, .col');
                                            const wrapper = column || mediaNode.closest(
                                                '.iq-media-images, .card, #media-images');
                                            if (wrapper) {
                                                wrapper.remove();
                                            }
                                        } else {
                                            window.location.reload();
                                        }
                                        Swal.fire({
                                            title: i18n.deleted_title,
                                            text: i18n.deleted_text.replace(':type', String(fileTypeText)
                                                .toLowerCase()).replace(':name', displayName).replace(
                                                ':folder', folderName || ''),
                                            icon: 'success',
                                            showConfirmButton: false,
                                            timer: 3000,
                                            timerProgressBar: true
                                        });
                                    } else {
                                        Swal.fire(
                                            i18n.delete_error_title,
                                            i18n.delete_error_text.replace(':type', String(fileTypeText)
                                                .toLowerCase()),
                                            'error'
                                        );
                                    }
                                })
                                .catch(error => {
                                    console.error('Delete error:', error);
                                    Swal.fire(
                                        i18n.delete_error_title,
                                        i18n.delete_error_text.replace(':type', String(fileTypeText)
                                            .toLowerCase()),
                                        'error'
                                    );
                                });
                        }
                    });
            }
        </script>
    @endpush
