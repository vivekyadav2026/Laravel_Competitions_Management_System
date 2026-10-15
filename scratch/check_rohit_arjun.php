<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$battles = \App\Models\LudoBattle::with(['creator', 'joiner'])
    ->whereHas('creator', function($q) {
        $q->where('name', 'like', '%Rohit%')->orWhere('name', 'like', '%Arjun%');
    })
    ->get();

foreach ($battles as $b) {
    echo "ID: {$b->id} | Status: {$b->status} | Creator: " . ($b->creator->name ?? 'None') . " | Joiner: " . ($b->joiner->name ?? 'None') . " | Fee: {$b->entry_fee}\n";
}
