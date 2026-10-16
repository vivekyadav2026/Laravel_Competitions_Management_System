<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settings = App\Models\LudoSetting::whereIn('key', ['upi_id', 'payee_name', 'upi_qr_image'])->pluck('value', 'key')->toArray();
echo json_encode($settings, JSON_PRETTY_PRINT) . PHP_EOL;

$all = App\Models\LudoSetting::all()->pluck('value', 'key')->toArray();
echo json_encode($all, JSON_PRETTY_PRINT) . PHP_EOL;
