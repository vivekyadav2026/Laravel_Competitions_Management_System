<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$uA = App\Models\User::where('email', 'player_a@fsaludoclub.test')->first();
$uB = App\Models\User::where('email', 'player_b@fsaludoclub.test')->first();
echo "User A: " . ($uA ? "ID: {$uA->id} | @{$uA->username} | {$uA->email}" : "None") . "\n";
echo "User B: " . ($uB ? "ID: {$uB->id} | @{$uB->username} | {$uB->email}" : "None") . "\n";
