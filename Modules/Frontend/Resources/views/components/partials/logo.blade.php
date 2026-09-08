<div class="logo-default">
    <a class="navbar-brand text-primary" href="{{route('user.login')}}">

        @php

            $logo=GetSettingValue('dark_logo')  ? setBaseUrlWithFileName(GetSettingValue('dark_logo'),'image','logos') :  asset(setting('dark_logo'));
        @endphp


        <img class="img-fluid logo" src="{{ $logo }}" alt="streamit">
    </a>
</div>
