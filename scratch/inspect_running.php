<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LudoBattle;

$filterRealUsers = function ($query) {
    if (!app()->environment('testing')) {
        $query->whereHas('creator', function ($q) {
            $q->where(function ($sub) {
                $sub->whereNotNull('phone')
                    ->orWhere('email', 'like', '%@fsaludoclub%');
            })
            ->where('email', 'not like', '%@royalludo.test%')
            ->where('email', 'not like', '%@ludoking.test%')
            ->where('email', 'not like', '%fakedata%');
        });
    }
};

$runningQuery = LudoBattle::with(['creator', 'joiner'])
    ->where('status', 'running')
    ->whereNotNull('joiner_id');

$filterRealUsers($runningQuery);

$runningBattles = $runningQuery->latest('started_at')
    ->latest('id')
    ->take(6)
    ->get();

echo "Running battles count from query: " . $runningBattles->count() . PHP_EOL;
foreach ($runningBattles as $b) {
    echo "ID: {$b->id} | UID: {$b->battle_uid} | Creator: {$b->creator->name} | Joiner: {$b->joiner->name} | Fee: {$b->entry_fee} | Prize: {$b->prize} | Room: " . ($b->room_code ?? 'PENDING') . " | Started: " . $b->started_at . PHP_EOL;
}
