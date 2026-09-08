
{{-- Footer Section Start --}}
@php
    $footerStyle = getCustomizationSetting('footer_style');
@endphp
<footer class="footer pr-hide {{ $footerStyle }}">
  <div class="footer-body">
      <div class="text-center">
          <a href="{{ route('backend.home') }}">{{setting('app_name')}}</a>
           <span>(v{{ config('app.version') }})</span>
      </div>
  </div>
</footer>
