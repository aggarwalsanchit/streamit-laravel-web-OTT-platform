<!-- Horizontal Menu Start -->
@php
  $showShortsNav = function_exists('short_drama_mobile_hub_enabled')
      ? short_drama_mobile_hub_enabled()
      : (\App\Services\ShortDrama\ShortDramaAddonStatus::isPresent()
          && (bool) \Modules\Setting\Models\Setting::get('short_drama_is_active', '1'));
  $shortsLabel = function_exists('short_drama_feature_name')
      ? short_drama_feature_name()
      : (\Modules\Setting\Models\Setting::get('short_drama_feature_name', 'Shorts') ?: 'Shorts');
  $shortsIconUrl = function_exists('short_drama_feature_icon_url')
      ? short_drama_feature_icon_url()
      : null;
@endphp
<nav id="navbar_main" class="offcanvas mobile-offcanvas nav navbar navbar-expand-xl hover-nav horizontal-nav py-xl-0">
  <div class="container-fluid p-lg-0">
    <div class="offcanvas-header">
      <div class="navbar-brand p-0">
        <!--Logo -->
        @include('frontend::components.partials.logo')

      </div>
      <button type="button" class="btn-close p-0 m-0" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="d-flex align-items-xl-center align-items-start flex-xl-row flex-column">
      <ul class="navbar-nav iq-nav-menu  list-unstyled" id="header-menu">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('user.login') ? 'active text-primary' : '' }}"
            href="{{route('user.login')}}">
            <span class="item-name">{{__('frontend.home')}}</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs(['movies', 'movie-details', 'tv-shows', 'tvshow-details', 'episode-details', 'videos', 'video-details', 'video-detail']) ? 'active text-primary' : '' }}"
            href="#">
            <span class="item-name">{{__('frontend.all_content')}}</span>
          </a>
          <ul class="sub-menu list-unstyled">
            @if(isenablemodule('movie'))
              <li class="nav-item">
                <a class="nav-link {{ request()->routeIs(['movies', 'movie-details']) ? 'active text-primary' : '' }}"
                  href="{{ route('movies') }}">
                  <span class="item-name">{{__('frontend.movies')}}</span>
                </a>
              </li>
            @endif
            @if(isenablemodule('tvshow'))
              <li class="nav-item">
                <a class="nav-link {{ request()->routeIs(['tv-shows', 'tvshow-details', 'episode-details']) ? 'active text-primary' : '' }}"
                  href="{{ route('tv-shows') }}">
                  <span class="item-name">{{__('frontend.tvshows')}}</span>
                </a>
              </li>
            @endif
            @if(isenablemodule('video'))
              <li class="nav-item">
                <a class="nav-link {{ request()->routeIs(['videos', 'video-details', 'video-detail']) ? 'active text-primary' : '' }}"
                  href="{{ route('videos') }}">
                  <span class="item-name">{{__('frontend.video')}}</span>
                </a>
              </li>
            @endif
          </ul>
        </li>
        <!-- @if(isenablemodule('movie'))
        <li class="nav-item">
          <a class="nav-link"  href="{{ route('movies') }}">
            <span class="item-name">{{__('frontend.movies')}}</span>
          </a>
        </li>
        @endif
        @if(isenablemodule('tvshow'))
        <li class="nav-item">
          <a class="nav-link"  href="{{ route('tv-shows') }}">
            <span class="item-name">{{__('frontend.tvshows')}}</span>
          </a>
        </li>
        @endif
        @if(isenablemodule('video'))
        <li class="nav-item">
          <a class="nav-link"  href="{{ route('videos') }}">
            <span class="item-name">{{__('frontend.video')}}</span>
          </a>
        </li>
        @endif -->
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('comingsoon') ? 'active text-primary' : '' }}"
            href="{{ route('comingsoon') }}">
            <span class="item-name">{{__('frontend.coming_soon')}}</span>
          </a>
        </li>
        @if(isenablemodule('livetv'))
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('livetv') ? 'active text-primary' : '' }}" href="{{route('livetv')}}">
              <span class="item-name">{{__('frontend.livetv')}}</span>
            </a>
          </li>
        @endif
       
  
      </ul>
       @if($showShortsNav)
            <a class="btn button-shorts {{ request()->routeIs('short-drama.*') ? 'active' : '' }}" href="{{ route('short-drama.index') }}">
                @if($shortsIconUrl)
                  <img src="{{ $shortsIconUrl }}" alt="" class="shorts-icon-img" aria-hidden="true">
                @else
                  <i class="ph-fill ph-lightning" aria-hidden="true"></i>
                @endif
                <span class="item-name">{{ $shortsLabel }}</span>
            </a>
        
        @endif

    </div>
  </div>
  <!-- container-fluid.// -->
</nav>

<!-- Horizontal Menu End -->