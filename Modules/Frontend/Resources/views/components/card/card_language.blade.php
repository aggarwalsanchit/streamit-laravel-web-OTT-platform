<div>
    <a href="{{ route('movies.language', strtolower($popular_language->name)) }}" class="language-card">
        <!-- <span class="language-inner">{{ substr($popular_language->name, 0, 1) }}</span>
        <span class="text-capitalize language-title line-count-1">{{ $popular_language->name }}</span> -->
        <img src="{{ setBaseUrlWithFileName($popular_language->language_image,'image','constant') }}" alt="Language Image" class="img-fluid rounded">
    </a>
</div>
