@props(['entertainmentId', 'inWatchlist', 'entertainmentType' => null, 'customClass' => ''])
<button id="watchlist-btn-{{ $entertainmentId }}"
        class="action-btn btn {{ $inWatchlist ? 'btn-primary' : 'btn-dark' }} {{ $customClass }} watch-list-btn"
        data-entertainment-id="{{ $entertainmentId }}"
        data-in-watchlist="{{ $inWatchlist ? 'true' : 'false' }}"
        data-entertainment-type="{{ $entertainmentType ?? '' }}"
        data-bs-toggle="tooltip" data-bs-title="{{ $inWatchlist ? __('messages.remove_watchlist') : __('messages.add_watchlist') }}" data-bs-placement="top">
    <i class="ph {{ $inWatchlist ? 'ph-check' : 'ph-plus' }}"></i>
</button>
