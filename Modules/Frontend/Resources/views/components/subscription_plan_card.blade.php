<div class="subscription-plan-wrapper {{ $plan->id == $currentPlanId ? 'active' : '' }} ">
    <div class="subscription-plan-header">
        <p class="subscription-name text-capitalize">{{ $plan->name }}</p>
        <p class="mt-2 mb-3 font-size-14 text-break js-episode-desc">
            <span class="js-desc-text">{!! Str::limit(strip_tags($plan->description), 150) !!}</span>
            @if(strlen(strip_tags($plan->description)) > 150)
                 <a href="javascript:void(0)" class="text-primary p-0 align-baseline js-episode-toggle">{{ __('messages.read_more') }}</a>
            @endif
        </p>

        <script>
        (function(){
            var container = document.currentScript.previousElementSibling;
            if(!container) return;
            var toggle = container.querySelector('.js-episode-toggle');
            var desc = container.querySelector('.js-desc-text');
            if(!toggle || !desc) return;

            var fullText = `{!! addslashes($plan->description) !!}`;
            var shortText = `{!! addslashes(Str::limit(strip_tags($plan->description), 150)) !!}`;
            var expanded = false;

            toggle.addEventListener('click', function(e){
                e.preventDefault();
                if(!expanded){
                    desc.innerHTML = fullText;
                    toggle.textContent = ("{{ __('messages.read_less') ?? 'Read Less' }}").trim();
                } else {
                    desc.innerHTML = shortText;
                    toggle.textContent = ("{{ __('messages.read_more') ?? 'Read More' }}").trim();
                }
                expanded = !expanded;
            });
        })();
        </script>
        @if($plan->discount == 1)
            <div class="discount-offer">{{$plan->discount_percentage}} {{ __('messages.lbl_off') }}</div>
        @endif
        <p class="subscription-price">
            @if($plan->discount == 1)
            <!-- <s class="text-body">{{ Currency::format($plan->price) }}/</s> -->
            {{ Currency::format($plan->total_price) }}
            @else
            {{ Currency::format($plan->price) }}
            @endif
            @php
                $durationValue = intval($plan->duration_value ?? 0);
                $durationUnitRaw = strtolower(trim((string)($plan->duration ?? '')));
                $baseUnit = in_array($durationUnitRaw, ['month', 'months']) ? __('messages.lbl_month') : (in_array($durationUnitRaw, ['year', 'years']) ? __('messages.lbl_year') : ucfirst($durationUnitRaw));
                $unitLabel = $durationValue === 1 ? $baseUnit : $baseUnit . __('messages.lbl_s');
            @endphp
            <span class="subscription-price-desc">/ {{ $durationValue }} {{ $unitLabel }}</span>
        </p>
    </div>
    <div class="readmore-wrapper">
        <ul class="list-inline subscription-details">
            @foreach ($plan->planLimitation as $limitation)
                @if(!optional($limitation->limitation_data)->status)
                    @continue
                @endif
                @php
                    // Set the default icon class for disabled state
                    $iconClass = 'ph-x text-danger';

                    // Determine icon class based on specific conditions
                    if ($limitation->limitation_value || $limitation->limitation_slug === 'ads') {
                        $iconClass = 'ph-check text-success'; // Show check for enabled limitations and ads
                    } elseif ($limitation->limitation_slug === 'device-limit' && $limitation->limit == 0) {
                        $iconClass = 'ph-check text-success'; // Show check for 1 mobile device
                    } elseif ($limitation->limitation_slug === 'profile-limit' && $limitation->limit == 0) {
                        $iconClass = 'ph-check text-success'; // Show check for profile limit
                    }
                @endphp

                <li class="list-desc d-flex align-items-start gap-2 mb-2">
                    <i class="ph {{ $iconClass }} align-middle"></i>
                    <span class="font-size-16 text-white">
                        @switch($limitation->limitation_slug)
                            @case('video-cast')
                                {{ $limitation->limitation_value ? __('messages.video_cast_enabled') : __('messages.video_cast_not_available') }}
                                @break

                            @case('ads')
                                {{ $limitation->limitation_value ? __('messages.ads_will_be_shown') : __('messages.ads_will_not_be_shown') }}
                                @break

                            @case('device-limit')
                                {{ $limitation->limit == 0
                                    ? __('messages.only_one_mobile_device')
                                    : __('messages.up_to_devices', ['count' => $limitation->limit]) . ' ' . __('messages.simultaneously')
                                }}
                                @break

                            @case('download-status')
                                {{ __('messages.download_resolutions') }}:
                                @php
                                    $availableQualities = [];
                                    $notAvailableQualities = [];
                                @endphp

                                @foreach (json_decode($limitation->limit, true) as $quality => $available)
                                    @if($available == 1)
                                        @php
                                            $availableQualities[] = strtoupper($quality);
                                        @endphp
                                    @else
                                        @php
                                            $notAvailableQualities[] = strtoupper($quality);
                                        @endphp
                                    @endif
                                @endforeach

                                <ul class="sub-limits ps-0 mt-1">
                                    @if (!empty($availableQualities))
                                        <li class="d-flex align-items-center gap-2 mb-2">
                                            <i class="ph ph-check text-success"></i>
                                            {{ implode('/', $availableQualities) }}
                                        </li>
                                    @endif

                                    @if (!empty($notAvailableQualities))
                                        <li class="d-flex align-items-center gap-2 mb-2">
                                            <i class="ph ph-x text-danger"></i>
                                            {{ implode('/', $notAvailableQualities) }}
                                        </li>
                                    @endif
                                </ul>
                            @break

                            @case('supported-device-type')
                                @php
                                    $supportedDevices = json_decode($limitation->limit, true);
                                    $supportedDevicesList = [];
                                @endphp

                                @foreach ($supportedDevices as $device => $supported)
                                    @if ($supported == 1)
                                        @php
                                            $deviceKey = 'device_' . strtolower($device);
                                            $translatedDevice = __('messages.' . $deviceKey, [], null);
                                            // Fallback to original device name if translation doesn't exist
                                            $supportedDevicesList[] = ($translatedDevice !== 'messages.' . $deviceKey) ? $translatedDevice : ucfirst($device);
                                        @endphp
                                    @endif
                                @endforeach

                                @if (!empty($supportedDevicesList))
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        {{ __('messages.supported_on') }}: {{ implode(', ', $supportedDevicesList) }}.
                                    </div>
                                @else
                                    <div class="d-flex align-items-center gap-2">
                                        {{ __('messages.only_mobile_supported') }}
                                    </div>
                                @endif
                            @break



                            @case('profile-limit')
                                {{ __('messages.create_up_to_profiles', ['count' => ($limitation->limit == 0 ? 1 : $limitation->limit)]) }}
                                @break

                            @default
                                {{ ucwords(str_replace('-', ' ', $limitation->limitation_slug)) }}: {{ $limitation->limitation_value ? __('messages.enabled') : __('messages.disabled') }}
                        @endswitch
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    <button type="button"
            class="rounded btn btn-{{ $plan->id == $currentPlanId ? 'primary' : 'dark' }} subscription-btn"
            data-plan-id="{{ $plan->id }}"
            data-plan-name="{{ $plan->name }}">
        {{ $plan->id == $currentPlanId ? __('messages.renew_plan') : __('messages.choose_plan') }}
    </button>
</div>


