@props([
    'id' => 'ludoConfirmModal',
    'title' => 'Confirm Battle Entry',
    'message' => 'Are you sure you want to join this battle? The entry fee will be deducted from your coins balance.',
    'confirmText' => 'Confirm & Play',
    'cancelText' => 'Cancel'
])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered px-3">
        <div class="modal-content ludo-modal-dialog">
            <div class="modal-header border-secondary border-opacity-25 pb-2">
                <h5 class="modal-title fw-bold fs-6" id="{{ $id }}Label">
                    <span class="text-warning">⚔️</span> {{ $title }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-secondary small py-3">
                <p class="mb-0 text-light">{{ $message }}</p>
                {{ $slot }}
            </div>
            <div class="modal-footer border-secondary border-opacity-25 pt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">{{ $cancelText }}</button>
                <button type="button" class="btn btn-sm btn-info rounded-pill px-4 fw-bold text-dark">{{ $confirmText }}</button>
            </div>
        </div>
    </div>
</div>
