@php
    $dataArray = is_array($data) ? $data : $data->toArray(request());
    $count = count($dataArray);
@endphp

<div class="streamit-block">
    <div class="d-flex align-items-center justify-content-between my-2 me-2">
        <h5 class="main-title text-capitalize mb-0">{{ $title }}</h5>

        @if ($count > 0)
            <a href="{{ route('pay-per-view') }}" class="view-all-button text-decoration-none flex-none">
                <span>{{ __('frontend.view_all') }}</span>
                <i class="ph ph-caret-right"></i>
            </a>
        @endif
    </div>

    <div class="card-style-slider {{ $count <= 6 ? 'slide-data-less' : '' }}">
        <div class="slick-general {{ $type == 'pay-per-view' ? 'slick-general-pay-per-view' : '' }}" data-items="6.5"
            data-items-desktop="5.5" data-items-laptop="4.5" data-items-tab="3.5" data-items-mobile-sm="3.5"
            data-items-mobile="2.5" data-speed="1000" data-autoplay="false" data-center="false" data-infinite="false"
            data-navigation="true" data-pagination="false" data-spacing="12">

            @foreach ($dataArray as $index => $value)
                @if (is_array($value))
                    <div class="slick-item">
                        @switch($value['type'] ?? null)
                            @case('video')
                                @include('frontend::components.card.card_video', ['values' => [$value]])
                            @break

                            @case('episode')
                                @include('frontend::components.card.card_pay_per_view', ['value' => $value])
                            @break

                            @default
                                @include('frontend::components.card.card_movie', ['values' => [$value]])
                        @endswitch
                    </div>
                @else
                @endif
            @endforeach

        </div>
    </div>
</div>
