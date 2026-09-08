@extends('setting::backend.setting.index')

@section('title')
    {{ __('setting_sidebar.lbl_misc_setting') }}
@endsection

@section('settings-content')
    <form method="POST" action="{{ route('backend.setting.store') }}" id="form-submit">
        @csrf
        <input type="hidden" name="setting_tab" value="misc">

        <div class="card">
            <div class="card-header p-0 mb-4">
                <h3 class="mb-0"><i class="fa-solid fa-screwdriver-wrench"></i> {{ __('setting_sidebar.lbl_misc_setting') }} </h3>
            </div>
            <div class="card-body p-0">
                <div class="row gy-3">
                    <div class="col-md-4">
                        {{ html()->label(__('messages.lbl_google_analytics'))->class('form-label') }}
                        {{ html()->text('google_analytics')->class('form-control')->placeholder(__('messages.lbl_google_analytics'))->value(old('google_analytics', $settings['google_analytics'] ?? '')) }}
                        @error('google_analytics')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        {{ html()->label(__('setting_language_page.lbl_language'))->class('form-label') }}
                        {{ html()->select('default_language')->options(array_column($languages, 'name', 'id'))->class('form-control select2')->value(old('default_language', $settings['default_language'] ?? '')) }}
                        @error('default_language')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        {{ html()->label(__('setting_language_page.lbl_timezone'))->class('form-label') }}
                        {{ html()->select('default_time_zone')->options(array_column($timezones, 'text', 'id'))->class('form-control select2')->value(old('default_time_zone', $settings['default_time_zone'] ?? '')) }}
                        @error('default_time_zone')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        {{ html()->label(__('setting_language_page.lbl_data_table_limit'))->class('form-label') }}
                        {{ html()->select('data_table_limit')->options([
                                10 => 10,
                                20 => 20,
                                25 => 25,
                                50 => 50,
                                100 => 100,
                            ])->class('form-control select2')->value(old('data_table_limit', $settings['data_table_limit'] ?? 10)) }}
                    </div>
                    <div class="col-md-4">
                        {{ html()->label(__('messages.lbl_date_format'))->class('form-label') }}
                        {{ html()->select('default_date_format')->options(array_column($dateFormat, 'text', 'id'))->class('form-control select2')->value(old('default_date_format', $settings['default_date_format'] ?? '')) }}
                        @error('default_date_format')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        {{ html()->label(__('messages.lbl_time_format'))->class('form-label') }}
                        {{ html()->select('default_time_format')->options(array_column($timeFormatList, 'text', 'id'))->class('form-control select2')->value(old('default_time_format', $settings['default_time_format'] ?? '')) }}
                        @error('default_time_format')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        {{ html()->label(__('messages.lbl_forward_seconds'))->class('form-label') }}
                        {{ html()->text('forward_seconds')->class('form-control')->placeholder(__('messages.lbl_forward_seconds'))->value(old('forward_seconds', isset($settings['forward_seconds']) ? $settings['forward_seconds'] : '')) }}
                        @error('forward_seconds')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        {{ html()->label(__('messages.lbl_backward_seconds'))->class('form-label') }}
                        {{ html()->text('backward_seconds')->class('form-control')->placeholder(__('messages.lbl_backward_seconds'))->value(old('backward_seconds', isset($settings['backward_seconds']) ? $settings['backward_seconds'] : '')) }}
                        @error('backward_seconds')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

            </div>
            <div class="text-end mt-3">
                {{ html()->button(__('messages.save'))->type('submit')->id('submit-button')->class('btn btn-primary') }}
            </div>
        </div>
    </form>
@endsection
