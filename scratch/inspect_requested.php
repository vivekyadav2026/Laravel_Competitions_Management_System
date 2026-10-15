<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$battles = \App\Models\LudoBattle::with(['creator', 'joiner'])
    ->where('status', 'requested')
    ->get();

echo "Total requested battles: " . $battles->count() . "\n";
foreach ($battles as $b) {
    echo "ID: {$b->id} | UID: {$b->battle_uid} | Creator: " . ($b->creator->name ?? $b->creator_id) . " | Joiner: " . ($b->joiner->name ?? $b->joiner_id) . " | Updated: {$b->updated_at}\n";
}
