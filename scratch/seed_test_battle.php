<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$uA = App\Models\User::find(3520);
if (!$uA) {
    $uA = App\Models\User::firstOrCreate(
        ['email' => 'player_a@fsaludoclub.test'],
        ['name' => 'Arjun Sharma', 'phone' => '9876543210', 'password' => bcrypt('secret123'), 'role' => 'player', 'coins' => 5000, 'is_phone_verified' => true]
    );
}

$uB = App\Models\User::firstOrCreate(
    ['email' => 'player_b_test@fsaludoclub.test'],
    ['name' => 'Kavita Singh', 'phone' => '9888877777', 'password' => bcrypt('secret123'), 'role' => 'player', 'coins' => 2000, 'is_phone_verified' => true]
);
$uB->coins = 2000;
$uB->save();

// Clean any old requested battles for User A so we have a fresh test
App\Models\LudoBattle::where('creator_id', $uA->id)->where('status', 'requested')->update(['status' => 'cancelled']);

// Create an open battle for Player A
$battle = App\Models\LudoBattle::create([
    'battle_uid' => 'BAT-SND-' . strtoupper(substr(uniqid(), -6)),
    'creator_id' => $uA->id,
    'entry_fee' => 100,
    'prize' => 190,
    'status' => 'open',
    'game_type' => 'classic',
]);

echo "Player A ID: {$uA->id}\n";
echo "Player B ID: {$uB->id}\n";
echo "Battle ID: {$battle->id} UID: {$battle->battle_uid}\n";
