<div class="streamit-block">
    <div class="d-flex align-items-center justify-content-between my-2 me-2">
        <h5 class="main-title text-capitalize mb-0">{{ $title }}</h5>

        @php
            $showViewAll = false;
            $viewAllRoute = route('videos');

            // Map slug to content list route
            if (!empty($slug) && $slug === 'popular_video') {
                $viewAllRoute = route('content.list', ['type' => 'most-watched-videos']);
                // For most-watched videos, show View All only if we have 9+ items
                $showViewAll = count($data) >= 9;
            } elseif (!empty($slug)) {
                // Custom video sections
                $viewAllRoute = route('custom-section', ['slug' => $slug]);
                $showViewAll = count($data) > 9;
            } else {
                // Other video sections: show View All when at least one item exists
                $showViewAll = count($data) > 0;
            }
        @endphp

        @if ($showViewAll)
            <a href="{{ $viewAllRoute }}" class="view-all-button text-decoration-none flex-none">
                <span>{{ __('frontend.view_all') }}</span>
                <i class="ph ph-caret-right"></i>
            </a>
        @endif
    </div>
      <div class="card-style-slider {{ count($data) <= 6 ? 'slide-data-less' : '' }}">
          <div class="slick-general slick-general-video-section" data-items="6.5" data-items-desktop="5.5"
              data-items-laptop="4.5" data-items-tab="3.5" data-items-mobile-sm="3.5" data-items-mobile="2.5"
              data-speed="1000" data-autoplay="false" data-center="false" data-infinite="false" data-navigation="true"
              data-pagination="false" data-spacing="12">


              @include('frontend::components.card.card_video', ['values' => $data])


          </div>
      </div>
  </div>
