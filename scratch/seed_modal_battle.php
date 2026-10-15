<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\LudoBattle;

// Creator
$creator = User::firstOrCreate(
    ['email' => 'modal_creator@fsaludoclub.test'],
    ['name' => 'Rohit Verma', 'phone' => '9876123456', 'password' => bcrypt('secret123'), 'role' => 'player', 'coins' => 5000, 'is_phone_verified' => true]
);

// Delete old open battle
LudoBattle::where('battle_uid', 'BAT-BLU-250')->delete();
LudoBattle::where('creator_id', $creator->id)->where('status', 'open')->delete();

// Create 250 point battle (prize 475)
$battle = LudoBattle::create([
    'battle_uid' => 'BAT-BLU-250',
    'creator_id' => $creator->id,
    'entry_fee' => 250,
    'prize' => 475,
    'status' => 'open',
    'game_type' => 'classic'
]);

// Zero user
$zeroUser = User::firstOrCreate(
    ['email' => 'zero_modal@fsaludoclub.test'],
    ['name' => 'Deepak Zero', 'phone' => '9900011111', 'password' => bcrypt('secret123'), 'role' => 'player', 'coins' => 0, 'is_phone_verified' => true]
);
$zeroUser->coins = 0;
$zeroUser->save();

// Partial user (100 coins)
$partialUser = User::firstOrCreate(
    ['email' => 'partial_modal@fsaludoclub.test'],
    ['name' => 'Aman Partial', 'phone' => '9900022222', 'password' => bcrypt('secret123'), 'role' => 'player', 'coins' => 100, 'is_phone_verified' => true]
);
$partialUser->coins = 100;
$partialUser->save();

// Rich user (5000 coins)
$richUser = User::firstOrCreate(
    ['email' => 'rich_modal@fsaludoclub.test'],
    ['name' => 'Vikram Rich', 'phone' => '9900044444', 'password' => bcrypt('secret123'), 'role' => 'player', 'coins' => 5000, 'is_phone_verified' => true]
);
$richUser->coins = 5000;
$richUser->save();

echo "Battle ID: {$battle->id} UID: {$battle->battle_uid}\n";
echo "Zero User ID: {$zeroUser->id}\n";
echo "Rich User ID: {$richUser->id}\n";
