<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$b = App\Models\LudoBattle::find(1265);
if ($b) {
    echo "Battle ID: {$b->id}, Status: {$b->status}, Creator: {$b->creator_id}, Joiner: {$b->joiner_id}\n";
} else {
    echo "Battle not found\n";
}
