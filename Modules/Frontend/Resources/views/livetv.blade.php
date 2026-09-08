@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.live_tv') }}
@endsection


@section('content')
    {{-- <div id="livetvthumbnail-section">
        @include('frontend::components.section.livetvthumbnail', [
            'livetvthumbnail' => $responseData['slider'],
        ])
    </div> --}}
    @if (isset($featured_livetvs) && !is_null($featured_livetvs) && !empty($featured_livetvs))
    <div id="livetvthumbnail-section">
        @include('frontend::components.section.livetvBannerthumbnail', [
                'livetvthumbnail' => $featured_livetvs,
            ])
        </div>
    @endif



    <div class="container-fluid padding-right-0">
        <div class="overflow-hidden">
            <div id="more-infinity-section">
                @include('frontend::components.section.livetv_category', [
                    'moreinfinity' => $responseData['category_data'],
                ])
            </div>
        </div>
    </div>
@endsection
