@extends('backend.layouts.app')

@section('title')
    {{ __('profile.title') }}
@endsection

@section('content')
    <div class="row g-4">
        @include('setting::backend.profile.sidebar-panel')
        @include('setting::backend.profile.main-content')
    </div>
    @if(session('success'))
<div class="snackbar" id="snackbar">

    <div class="d-flex justify-content-around align-items-center">
        <p class="mb-0">{{ session('success') }}</p>
        <a href="#" class="dismiss-link text-decoration-none text-success" onclick="dismissSnackbar(event)">{{ __('messages.dismiss') }}</a>
    </div>
</div>
@endif

@push('after-scripts')
<script src="{{ asset('js/form-modal/index.js') }}" defer></script>
<script src="{{ asset('js/form/index.js') }}" defer></script>

@endpush
@endsection




@push ('after-styles')


@endpush
