<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::firstOrCreate(
    ['email' => 'zero_player@fsaludoclub.test'],
    [
        'name' => 'Rohan Verma',
        'phone' => '9998880000',
        'password' => bcrypt('secret123'),
        'role' => 'player',
        'coins' => 0,
        'is_phone_verified' => true,
    ]
);
$user->coins = 0;
$user->save();

echo "Zero User ID: " . $user->id . "\n";
