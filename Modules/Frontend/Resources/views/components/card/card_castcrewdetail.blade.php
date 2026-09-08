
<div class="actor-detail-card">
    <div class="actor-image">
        <img src="{{ $data['profile_image'] }}" class="img-fluid actor-img rounde-3 object-cover rounded" alt="Actor Images">
    </div>
    <div class="py-3">
        <h2 class="mb-2 cast-title">{{ $data['name'] }}</h2>
        <p class="actor-description readmore-wrapper">
            <p class="font-size-14 js-episode-desc">
                <span class="js-desc-text">{!! Str::limit(strip_tags($data['bio']), 300) !!}</span>
                @if(strlen(strip_tags($data['bio'])) > 300)
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

                var fullText = `{!! addslashes($data['bio']) !!}`;
                var shortText = `{!! addslashes(Str::limit(strip_tags($data['bio']), 300)) !!}`;
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
        </p>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-md-5 gap-3 actor-desc">
            <div class="d-inline-flex align-items-center gap-3 border-bottom py-3 flex-grow-1">
                <i class="ph ph-identification-card"></i>
                <div class="">
                    <h6 class="desc-title">{{__('messages.cast_crew')}}</h6>
                    <h6 class="mb-0 fw-semibold font-size-16 heading-color">{{ $data['designation'] }}</h6>
                </div>
            </div>
            <div class="d-inline-flex align-items-center gap-3 border-bottom py-3 flex-grow-1">
                <i class="ph ph-cake"></i>
                <div class="">
                    <h6 class="desc-title">{{__('messages.birth_date')}}</h6>
                    <h6 class="mb-0 fw-semibold font-size-16 heading-color">{{  $data['dob'] ? formatDate($data['dob']) : '-' }}</h6>
                </div>
            </div>
            <div class="d-inline-flex align-items-center gap-3 border-bottom py-3 flex-grow-1">
                <i class="ph ph-map-pin-area"></i>
                <div class="">
                    <h6 class="desc-title">{{__('messages.birth_place')}}</h6>
                    <h6 class="mb-0 fw-semibold font-size-16 heading-color">{{  $data['place_of_birth'] ? $data['place_of_birth'] : '-'  }}</h6>
                </div>
            </div>
        </div>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 gy-3 mt-5">
                <div class="col">
                    <div class="counter-card">
                        <h3 class="counter-title">{{$movieCount ?? 0}}</h3>
                        <p class="m-0 text-uppercase">{{__('messages.movie')}}</p>
                    </div>
                </div>
                <div class="col">
                    <div class="counter-card">
                        <h3 class="counter-title">{{$tvshowCount ?? 0}}</h3>
                        <p class="m-0 text-uppercase">{{__('messages.tvshow')}}</p>
                    </div>
                </div>
                <div class="col">
                    <div class="counter-card">
                        <h3 class="counter-title">{{ $averageRating ? round($averageRating, 1) : 0 }}</h3>
                        <p class="m-0 text-uppercase">{{__('messages.average_rating')}}</p>
                    </div>
                </div>
            @if($topGenres)
                <div class="col">
                    <div class="counter-card">
                        <h3 class="counter-title text-wrap">{{ $topGenres ?? null }}</h3>
                        <p class="m-0 text-uppercase">{{__('messages.top_genres')}}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const readmoreWrapper = document.querySelector('.readmore-wrapper');
    if (readmoreWrapper) {
        const readmoreText = readmoreWrapper.querySelector('.readmore-text');
        const readmoreBtn = readmoreWrapper.querySelector('.readmore-btn');
        if (readmoreText && readmoreBtn) {
            const tempElement = readmoreText.cloneNode(true);
            tempElement.style.position = 'absolute';
            tempElement.style.visibility = 'hidden';
            tempElement.style.height = 'auto';
            tempElement.style.maxHeight = 'none';
            tempElement.style.webkitLineClamp = 'none';
            tempElement.style.display = 'block';
            tempElement.classList.remove('line-count-3');
            readmoreWrapper.appendChild(tempElement);
            const fullHeight = tempElement.offsetHeight;
            const limitedHeight = readmoreText.offsetHeight;
            readmoreWrapper.removeChild(tempElement);
            if (fullHeight > limitedHeight) {
                readmoreBtn.style.display = 'inline-block';
            }
        }
    }
});
</script>

