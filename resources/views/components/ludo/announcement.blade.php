@props([
    'text' => '⭐ 🎉 📢 Welcome to Fsaludoclub! Play fair and compete for virtual tournament points glory. For any assistance, 24x7 Support Desk is active.'
])

<div class="ludo-announcement" role="alert">
    <span class="fs-6">⭐</span>
    <div>
        {{ $slot->isEmpty() ? $text : $slot }}
    </div>
</div>
