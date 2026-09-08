<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="row">
                    {{ html()->hidden('id',null) }}
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form-label">{{ (__('notification.lbl_type')) }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" value="{{ $data->constant->name ?? '' }}" readonly>
                            {{ html()->hidden('type', $data->type ?? '')->attribute('id', 'type_hidden') }}
                            <div class="invalid-feedback" id="name-error">Type field is required</div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form-label">{{ (__('notification.parameters')) }} </label>
                                <div class="main_form">
                                    @if(isset($buttonTypes))
                                        @include('notificationtemplate::backend.notificationtemplates.perameters-buttons',['buttonTypes' => $buttonTypes])
                                    @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form-label">{{ (__('notification.lbl_user_type')) }} <span class="text-danger">*</span></label>
                            <select name="defaultNotificationTemplateMap[user_type]" class="form-control select2" id="user_type" onchange="onChangeUserType()" required>
                                @if(isset($roles))
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ (isset($data->defaultNotificationTemplateMap) && $data->defaultNotificationTemplateMap->user_type == $role->name) ? 'selected' : '' }}>
                                            {{ ucfirst($role->title) }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="invalid-feedback" id="user-type-error">User type field is required</div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="form-label">{{ (__('notification.lbl_to')) }} </label><br>
                            <select name="to[]" class="form-control select2" data-ajax--url="{{ route('backend.notificationtemplates.ajax-list',['type' => 'constants_key','data_type' => 'notification_to']) }}" data-ajax--cache="true" multiple>
                                @if(isset($data))
                                @if($data->to != null)
                                @foreach(json_decode($data->to) as $to)
                                <option value="{{$to}}" selected="">{{ isset($notificationToConstants) && isset($notificationToConstants[$to]) ? $notificationToConstants[$to] : ucfirst(str_replace('_', ' ', $to)) }}</option>
                                @endforeach
                                @endif
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                        <label class="form-label" for="category-status">{{ __('messages.lbl_status') }} </label>
                        <div class="d-flex justify-content-between align-items-center form-control">
                        <label class="form-label mb-0 text-body" for="category-status">{{ __('messages.active') }} </label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="status" value="0">
                            <input class="form-check-input" value="1" name="status" id="category-status" type="checkbox" {{ isset($data) && old('status', $data->status) == 1 ? 'checked' : '' }} />
                        </div>
                        </div>
                    </div>
                    </div>

                </div>
            </div>
            <div class="col-md-8 ">
                <div class="row pl-3">
                    <div class="col-md-12">
                        <div class="text-left">
                            <label class="form-label">{{ (__('notification.notification_template')) }} </label>
                            {{ html()->hidden("defaultNotificationTemplateMap[language]",'en') }}
                        </div>
                        <div class="form-group">
                            <label class="float-left form-label">{{ (__('messages.subject')) }} </label>
                            {{ html()->text("defaultNotificationTemplateMap[notification_subject]",null)
                            ->class('form-control notification_subject')
                            ->attribute('id', "en-notification_subject")
                            ->value($data->defaultNotificationTemplateMap->notification_subject ?? '') }}
                        </div>
                        <div class="form-group">
                            <label class="float-left form-label">{{ (__('messages.template')) }} </label>
                            {{ html()->textarea("defaultNotificationTemplateMap[notification_template_detail]",null)
                            ->class('form-control textarea')
                            ->attribute('id', "notification_textarea")
                            ->value($data->defaultNotificationTemplateMap->notification_template_detail ?? '') }}
                        </div>

                        <div class="text-left mt-4">
                            <label class="form-label">{{ (__('notification.email_template')) }} </label>
                        </div>
                        <div class="form-group">
                            <label class="float-left form-label">{{ (__('messages.subject')) }} </label>
                            {{ html()->text("defaultNotificationTemplateMap[subject]",null)
                            ->class('form-control')
                            ->value($data->defaultNotificationTemplateMap->subject ?? '')
                            ->required() }}
                            {{ html()->hidden("defaultNotificationTemplateMap[status]",1)
                                ->class('form-control') }}
                                <div class="invalid-feedback" id="name-error">Subject field is required</div>
                        </div>
                        <div class="form-group">
                            <label class="float-left form-label">{{ (__('messages.template')) }} </label>
                            {{ html()->textarea("defaultNotificationTemplateMap[template_detail]",null)
                            ->class('form-control textarea')
                            ->attribute('id', "mytextarea")
                            ->value($data->defaultNotificationTemplateMap->template_detail ?? '') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
 <div class="d-grid d-sm-flex justify-content-sm-end gap-3 mb-5">
        <button type="submit" class="btn btn-primary"> {{ (__('messages.save'))}}<i class="md md-lock-open"></i></button>
</div>
<script>
  function onChangeType(url, render) {
      var dropdown = document.getElementById("type");
      var selectedValue = dropdown.value;

      // Update the hidden field with the selected type
      document.getElementById('type_hidden').value = selectedValue;

      var url = "{{ route('backend.notificationtemplates.notification-buttons',['type' => 'buttonTypes']) }}";
      $.get(url, function(data) {
          var html = data;
          if (render !== undefined && render !== '' && render !== null) {
              $('.' + render).html(html);
          } else {
              $(".main_form").html(html);
              $("#formModal").modal("show");
          }
      });
  }

  function onChangeUserType() {
      var type = document.getElementById("type_hidden").value;
      var userType = document.getElementById("user_type").value;

      if (type && userType) {
          var url = "{{ route('backend.notificationtemplates.fetchnotification_data') }}";
          $.get(url, {
              type: type,
              user_type: userType
          }, function(response) {
              if (response.success && response.data) {
                  // Populate template fields with existing data
                  if (response.data.subject) {
                      $('input[name="defaultNotificationTemplateMap[subject]"]').val(response.data.subject);
                  }
                  if (response.data.template_detail) {
                      $('textarea[name="defaultNotificationTemplateMap[template_detail]"]').val(response.data.template_detail);
                  }
                  if (response.data.notification_message) {
                      $('input[name="defaultNotificationTemplateMap[notification_message]"]').val(response.data.notification_message);
                  }
                  if (response.data.notification_link) {
                      $('input[name="defaultNotificationTemplateMap[notification_link]"]').val(response.data.notification_link);
                  }
                  if (response.data.notification_subject) {
                      $('input[name="defaultNotificationTemplateMap[notification_subject]"]').val(response.data.notification_subject);
                  }
                  if (response.data.notification_template_detail) {
                      $('textarea[name="defaultNotificationTemplateMap[notification_template_detail]"]').val(response.data.notification_template_detail);
                  }

                  // Update TinyMCE editors if they exist
                  if (typeof tinymce !== 'undefined') {
                      if (tinymce.get('mytextarea')) {
                          tinymce.get('mytextarea').setContent(response.data.template_detail || '');
                      }
                      if (tinymce.get('notification_textarea')) {
                          tinymce.get('notification_textarea').setContent(response.data.notification_template_detail || '');
                      }
                  }
              }
          });
      }
  }
</script>
