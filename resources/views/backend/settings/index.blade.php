@extends('backend.layouts.app')

@section('title') {{ __($module_action) }} {{ __($module_title) }} @endsection



@section('content')
<meta name="setting_local" content="none">
<input type="hidden" name="admin-profile" value="{{asset('img/avatar/avatar.webp')}}">
<input type="hidden" name="logo" value="{{asset('img/logo/logo.png')}}">
<input type="hidden" name="mini-logo" value="{{asset('img/logo/mini_logo.png')}}">
<input type="hidden" name="dark-logo" value="{{asset('img/logo/dark_logo.png')}}">
<input type="hidden" name="dark-mini-logo" value="{{asset('img/logo/dark_mini_logo.png')}}">
<input type="hidden" name="favicon" value="{{asset('img/logo/favicon.png')}}">

<div id="setting-app"></div>

@endsection

@push('after-styles')
  <style>
    .modal-backdrop {
      --bs-backdrop-zindex: 1030;
    }
  </style>
@endpush
@push('after-scripts')
<script src="{{ asset('js/setting-vue.min.js')}}"></script>
@endpush
