@extends('backend.layouts.app')

@section('title') {{ __($module_title) }} @endsection



@section('content')
<x-back-button-component route="backend.notification-templates.index" />

                {{ html()->form('PUT' ,route('backend.notification-templates.update', $data->id))
                ->attribute('enctype', 'multipart/form-data')
                ->attribute('data-toggle', 'validator')
                ->attribute('id', 'form-submit')  // Add the id attribute here
                ->class('requires-validation')  // Add the requires-validation class
                ->attribute('novalidate', 'novalidate')  // Disable default browser validation
                ->open()
            }}

                    @include('notificationtemplate::backend.notificationtemplates.form')
                    {{ html()->form()->close() }}
             
      @endsection

      @push('after-scripts')
          <script>
              tinymce.init({
                  selector: '#mytextarea,#mytextarea_mail',
                  plugins: 'link image code',
                  toolbar: 'undo redo | styleselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify | removeformat | code | image',
              });
              tinymce.init({
                selector: '#notification_textarea',
                plugins: 'link image code',
                toolbar: 'undo redo | styleselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify | removeformat | code | image',
            });
              $(document).on('click', '.variable_button', function() {
                  const textarea = $(document).find('.tab-pane.active');
                  const textareaID = textarea.find('textarea').attr('id');
                  tinyMCE.activeEditor.selection.setContent($(this).attr('data-value'));
              });

             $(document).on('submit', '#form-submit', function () {
                var $btn = $(this).find('button[type=submit], input[type=submit]').first();
                if (!$btn.length) return true;
                $btn.prop('disabled', true);
                var loadingText = "{{ __('messages.loading') }}";

                if ($btn.is('button')) {
                    $btn.data('orig', $btn.html()).html(loadingText);
                } else {
                    $btn.data('orig', $btn.val()).val(loadingText);
                }
                return true;
            });
          </script>
      @endpush
