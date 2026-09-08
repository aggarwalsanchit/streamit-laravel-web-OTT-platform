@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <x-back-button-component route="backend.pages.index" />
    {{ html()->form('POST', route('backend.pages.store'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('id', 'form-submit')->class('requires-validation')->attribute('novalidate', 'novalidate')->open() }}
    @csrf
    <div class="card">
        <div class="card-body">
            {{-- NAME row --}}
            <div class="row gy-3">
                <div class="col-md-6">
                    {{ html()->label(__('page.lbl_name') . ' <span class="text-danger">*</span>', 'name')->class('form-label') }}
                    {{ html()->text('name')->attribute('value', old('name'))->placeholder(__('page.lbl_name'))->class('form-control')->attribute('required', 'required') }}
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="name-error">{{ __('messages.name_field_required') }}</div>
                </div>
                <div class="col-md-6">
                    {{ html()->label(__('page.lbl_status'), 'status')->class('form-label') }}
                    <div class="d-flex align-items-center justify-content-between form-control">
                        {{ html()->label(__('messages.active'), 'status')->class('form-label mb-0 text-body') }}
                        <div class="form-check form-switch">
                            {{ html()->hidden('status', 0) }}
                            {{ html()->checkbox('status', old('status', 1))->class('form-check-input')->id('status')->value(1) }}
                        </div>
                        @error('status')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-12">
                    {{ html()->label(__('page.lbl_description') . ' <span class="text-danger">*</span>', 'name')->class('form-label') }}
                    {{ html()->textarea('description')->attribute('value', old('description'))->placeholder(__('page.lbl_description'))->class('form-control')->id('description')->attribute('required', 'required') }}
                    @error('description')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                    <div class="invalid-feedback" id="description-error">{{ __('messages.description_field_required') }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mb-5">

        {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-right')->id('submit-button') }}
    </div>
    {{ html()->form()->close() }}
@endsection

@push('after-scripts')
    <script>
        tinymce.init({
            selector: 'textarea#description',
            plugins: 'link image code',
            toolbar: 'undo redo | styleselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify | removeformat | code | image',
            // Ensure images and links use absolute URLs so they render correctly later
            convert_urls: true,
            relative_urls: false,
            remove_script_host: false,
            document_base_url: "{{ url('/') }}/",
            // Allow cross-domain cookies if your file manager requires it
            images_upload_credentials: true,
            setup: function(editor) {
                const textarea = document.getElementById('description');
                const errorEl = document.getElementById('description-error');

                function syncAndClear() {
                    // Write editor HTML back into the textarea so HTML5 validation can pass
                    editor.save();

                    // Clear UI validation once there's meaningful content
                    const plainText = editor.getContent({ format: 'text' }).trim();
                    if (plainText.length > 0) {
                        textarea?.classList.remove('is-invalid');
                        if (errorEl) errorEl.style.display = 'none';
                    }
                }

                editor.on('keyup change input', syncAndClear);
                editor.on('init', syncAndClear);
            },
        });

        $(document).on('click', '.variable_button', function() {
            const textarea = $(document).find('.tab-pane.active');
            const textareaID = textarea.find('textarea').attr('id');
            tinyMCE.activeEditor.selection.setContent($(this).attr('data-value'));
        });
    </script>
@endpush
