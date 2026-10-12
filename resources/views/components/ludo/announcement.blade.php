@props([
    'text' => '⭐ 🎉 📢 Welcome to Royal Ludo! Instant 24x7 UPI withdrawals enabled. Keep playing and winning big! For any assistance, WhatsApp support is active 24x7.'
])

<div class="ludo-announcement" role="alert">
    <span class="fs-6">⭐</span>
    <div>
        {{ $slot->isEmpty() ? $text : $slot }}
    </div>
</div>
