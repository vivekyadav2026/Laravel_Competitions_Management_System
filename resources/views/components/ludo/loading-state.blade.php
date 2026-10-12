@props([
    'count' => 3
])

<div class="d-flex flex-column gap-2 my-3">
    @for($i = 0; $i < $count; $i++)
        <div class="ludo-battle-card border-secondary border-opacity-25 py-3">
            <div class="d-flex align-items-center gap-3 w-70">
                <div class="ludo-skeleton" style="width: 36px; height: 36px; border-radius: 50%;"></div>
                <div class="flex-grow-1">
                    <div class="ludo-skeleton mb-2" style="width: 50%; height: 14px;"></div>
                    <div class="ludo-skeleton" style="width: 80%; height: 10px;"></div>
                </div>
            </div>
            <div class="ludo-skeleton" style="width: 46px; height: 46px; border-radius: 50%;"></div>
        </div>
    @endfor
</div>
