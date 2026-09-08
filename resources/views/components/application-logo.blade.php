
            @php

            $logo=GetSettingValue('dark_logo') ? setBaseUrlWithFileName(GetSettingValue('dark_logo'),'image','logos') :  asset(setting('dark_logo'));
        @endphp

<img src="{{  $logo }}" class="img-fluid h-4 mb-4">

