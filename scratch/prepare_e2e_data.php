<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$admin = User::where('email', 'admin@fsaludoclub.com')->first();
if (!$admin) {
    $admin = User::create([
        'name' => 'Fsaludoclub Superadmin',
        'username' => 'fsaludoclub_superadmin',
        'email' => 'admin@fsaludoclub.com',
        'password' => Hash::make('Admin@12345'),
        'role' => 'superadmin',
        'coins' => 10000,
    ]);
}

$playerA = User::updateOrCreate(
    ['email' => 'player_a_rt@fsaludoclub.test'],
    [
        'name' => 'Arjun Sharma',
        'username' => 'arjun_sharma_4689',
        'password' => Hash::make('secret1234'),
        'phone' => '9811111111',
        'coins' => 1000,
        'role' => 'user',
        'kyc_status' => 'verified',
        'is_phone_verified' => true,
    ]
);

$playerB = User::updateOrCreate(
    ['email' => 'player_b_rt@fsaludoclub.test'],
    [
        'name' => 'Rohit Verma',
        'username' => 'rohit_verma_4690',
        'password' => Hash::make('secret1234'),
        'phone' => '9822222222',
        'coins' => 1000,
        'role' => 'user',
        'kyc_status' => 'verified',
        'is_phone_verified' => true,
    ]
);

$brokePlayer = User::updateOrCreate(
    ['email' => 'player_broke@fsaludoclub.test'],
    [
        'name' => 'Broke Gamer',
        'username' => 'broke_gamer_test',
        'password' => Hash::make('secret1234'),
        'phone' => '9844444444',
        'coins' => 25,
        'role' => 'user',
        'kyc_status' => 'verified',
        'is_phone_verified' => true,
    ]
);

// Clean up any stale active test battles for these isolated test users
\App\Models\LudoBattle::where(function ($q) use ($playerA, $playerB, $brokePlayer) {
    $q->whereIn('creator_id', [$playerA->id, $playerB->id, $brokePlayer->id])
      ->orWhereIn('joiner_id', [$playerA->id, $playerB->id, $brokePlayer->id]);
})->whereIn('status', ['open', 'requested', 'running'])->update(['status' => 'cancelled', 'cancellation_reason' => 'Test cleanup']);

echo "DATA_READY: Admin=#{$admin->id} (@{$admin->username}), PlayerA=#{$playerA->id} (@{$playerA->username}), PlayerB=#{$playerB->id} (@{$playerB->username}), Broke=#{$brokePlayer->id} (@{$brokePlayer->username}, coins={$brokePlayer->coins})\n";

