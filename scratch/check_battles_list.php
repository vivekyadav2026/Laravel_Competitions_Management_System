<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$battles = \App\Models\LudoBattle::with(['creator', 'joiner'])->get();
foreach ($battles as $b) {
    echo "ID: {$b->id} | UID: {$b->battle_uid} | Status: {$b->status} | Creator: " . ($b->creator->name ?? 'None') . " | Joiner: " . ($b->joiner->name ?? 'None') . " | Fee: {$b->entry_fee}\n";
}
