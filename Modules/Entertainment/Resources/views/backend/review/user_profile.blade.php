<div class="d-flex gap-3 align-items-center">
    <img src="{{ setBaseUrlWithFileName(optional($review->user)->file_url, 'image', 'users') ?? default_user_avatar() }}"
        alt="avatar" class="avatar avatar-40 rounded-pill">
    <div class="text-start">
        @if($review->user && $review->user->id)
            <h6 class="m-0">
                <a href="{{ route('backend.users.details', $review->user->id) }}" class="text-decoration-none text-white">
                    {{ optional($review->user)->full_name ?? default_user_name() }}
                </a>
            </h6>
        @else
            <h6 class="m-0">{{ default_user_name() }}</h6>
        @endif
        <small>{{ optional($review->user)->email ?? '--' }}</small>
    </div>
</div>
