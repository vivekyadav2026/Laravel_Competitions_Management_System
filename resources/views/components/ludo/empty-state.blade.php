@props([
    'title' => 'No Battles Available',
    'message' => 'There are currently no active matches in this section. Create one now to start playing!',
    'actionText' => 'Create a Battle',
    'actionLink' => '#create-battle'
])

<div class="ludo-empty-state">
    <div class="mb-3">
        <img src="{{ asset('assets/ludo/trophy.svg') }}" alt="Empty" class="ludo-empty-icon">
    </div>
    <h5 class="fw-bold mb-2 text-white">{{ $title }}</h5>
    <p class="small text-secondary mb-4 mx-auto" style="max-width: 280px;">{{ $message }}</p>
    @if($actionLink)
        <a href="{{ $actionLink }}" class="btn btn-sm btn-outline-info rounded-pill px-4 fw-bold">
            {{ $actionText }}
        </a>
    @endif
</div>
