@extends('setting::backend.setting.index')

@section('title')
    {{ __('setting_sidebar.lbl_module_setting') }}
@endsection

@section('settings-content')
    <div class="col-md-12 mb-3 d-flex justify-content-between">
        <h3 class="mb-0"><i class="icon ph ph-list-dashes"></i> {{ __('setting_sidebar.lbl_module-setting') }}</h3>

    </div>

    @php
        $isDemoAdmin = auth()->user()->user_type == 'demo_admin';
    @endphp

    <form method="POST" action="{{ route('backend.setting.store') }}" id="form-submit">
        @csrf
        <input type="hidden" name="setting_tab" value="module">

        {{-- Movie --}}
        <div class="form-group border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label m-0" for="movie">{{ __('movie.title') }}</label>
                @if($isDemoAdmin)
                    @php $movieStatus = old('movie', $settings['movie'] ?? 0); @endphp
                    <span class="badge {{ $movieStatus == 1 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                        {{ $movieStatus == 1 ? __('messages.active') : __('messages.inactive') }}
                    </span>
                    <input type="hidden" value="{{ $movieStatus }}" name="movie">
                @else
                    <input type="hidden" value="0" name="movie">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input toggle-input" data-toggle-target="#movie-fields" value="1"
                            name="movie" id="movie" type="checkbox"
                            {{ old('movie', $settings['movie'] ?? 0) == 1 ? 'checked' : '' }} />
                    </div>
                @endif
            </div>
        </div>
        
        {{-- TV Shows --}}
        <div class="form-group border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label m-0" for="tvshow">{{ __('movie.tvshows') }}</label>
                @if($isDemoAdmin)
                    @php $tvshowStatus = old('tvshow', $settings['tvshow'] ?? 0); @endphp
                    <span class="badge {{ $tvshowStatus == 1 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                        {{ $tvshowStatus == 1 ? __('messages.active') : __('messages.inactive') }}
                    </span>
                    <input type="hidden" value="{{ $tvshowStatus }}" name="tvshow">
                @else
                    <input type="hidden" value="0" name="tvshow">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input toggle-input" data-toggle-target="#tvshow-fields" value="1"
                            name="tvshow" id="tvshow" type="checkbox"
                            {{ old('tvshow', $settings['tvshow'] ?? 0) == 1 ? 'checked' : '' }} />
                    </div>
                @endif
            </div>
        </div>
        
        {{-- Live TV --}}
        <div class="form-group border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label m-0" for="livetv">{{ __('frontend.livetv') }}</label>
                @if($isDemoAdmin)
                    @php $livetvStatus = old('livetv', $settings['livetv'] ?? 0); @endphp
                    <span class="badge {{ $livetvStatus == 1 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                        {{ $livetvStatus == 1 ? __('messages.active') : __('messages.inactive') }}
                    </span>
                    <input type="hidden" value="{{ $livetvStatus }}" name="livetv">
                @else
                    <input type="hidden" value="0" name="livetv">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input toggle-input" data-toggle-target="#livetv-fields" value="1"
                            name="livetv" id="livetv" type="checkbox"
                            {{ old('livetv', $settings['livetv'] ?? 0) == 1 ? 'checked' : '' }} />
                    </div>
                @endif
            </div>
        </div>

        {{-- Video --}}
        <div class="form-group border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label m-0" for="video">{{ __('video.title') }}</label>
                @if($isDemoAdmin)
                    @php $videoStatus = old('video', $settings['video'] ?? 0); @endphp
                    <span class="badge {{ $videoStatus == 1 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                        {{ $videoStatus == 1 ? __('messages.active') : __('messages.inactive') }}
                    </span>
                    <input type="hidden" value="{{ $videoStatus }}" name="video">
                @else
                    <input type="hidden" value="0" name="video">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input toggle-input" data-toggle-target="#video-fields" value="1"
                            name="video" id="video" type="checkbox"
                            {{ old('video', $settings['video'] ?? 0) == 1 ? 'checked' : '' }} />
                    </div>
                @endif
            </div>
        </div>

        @if (auth()->user()->user_type == 'admin')
            <!-- Demo Login Section -->
            <div class="form-group border-bottom pb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label m-0" for="demo_login">{{ __('messages.demo_login') }}</label>
                    @if($isDemoAdmin)
                        @php $demoLoginStatus = old('demo_login', $settings['demo_login'] ?? 0); @endphp
                        <span class="badge {{ $demoLoginStatus == 1 ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                            {{ $demoLoginStatus == 1 ? __('messages.active') : __('messages.inactive') }}
                        </span>
                        <input type="hidden" value="{{ $demoLoginStatus }}" name="demo_login">
                    @else
                        <input type="hidden" value="0" name="demo_login">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input toggle-input" value="1" name="demo_login" id="demo_login"
                                type="checkbox" {{ old('demo_login', $settings['demo_login'] ?? 0) == 1 ? 'checked' : '' }} />
                        </div>
                    @endif
                </div>
            </div>
        @endif


        <div class="form-group border-bottom pb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label m-0" for="enable_tmdb_api">{{ __('messages.lbl_tmdb_Api') }}</label>

                <input type="hidden" value="0" name="enable_tmdb_api">
                <div class="form-check form-switch m-0">
                    {{ html()->checkbox('enable_tmdb_api', old('enable_tmdb_api', $settings['enable_tmdb_api'] ?? 0) == 1, 1)->class('form-check-input')->id('category-enable_tmdb_api')->attribute('onclick', 'toggleTmdbApi()') }}
                </div>
            </div>
        </div>

        <div id="tmdb_api_key-field" class=""
            style="display: {{ old('tmdb_api_key', $settings['enable_tmdb_api'] ?? 0) == 1 ? 'block' : 'none' }};">
            <div class="form-group border-bottom pb-3">
                <label class="form-label" for="category-tmdb_api_key">{{ __('messages.lbl_tmdb_key') }}</label>
                {{ html()->text('tmdb_api_key', old('tmdb_api_key', $settings['tmdb_api_key'] ?? ''))->class('form-control')->id('tmdb_api_key') }}
                @error('tmdb_api_key')
                    <p class="text-danger">{{ $message }}</p>
                @enderror
            </div>
        </div>


        <div class="text-end">
            <button type="submit" id="submit-button" class="btn btn-primary">{{ __('messages.save') }}</button>
        </div>
    </form>
@endsection

@push('after-scripts')
    <script>
        function toggleTmdbApi() {
            const TMDBapiEnabled = document.getElementById('category-enable_tmdb_api').checked;
            document.getElementById('tmdb_api_key-field').style.display = TMDBapiEnabled ? 'block' : 'none';

            const input = document.getElementById('tmdb_api_key');
            if (TMDBapiEnabled) {
                input.disabled = false;
            } else {
                input.disabled = true;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {

            toggleTmdbApi();

        });
    </script>
@endpush
