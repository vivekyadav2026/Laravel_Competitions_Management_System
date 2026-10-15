<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$b1 = \App\Models\LudoBattle::find(1440);
$b2 = \App\Models\LudoBattle::find(1275);
echo "1440: Created: {$b1->created_at} | Updated: {$b1->updated_at}\n";
echo "1275: Created: {$b2->created_at} | Updated: {$b2->updated_at}\n";
