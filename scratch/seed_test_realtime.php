<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::where('role', 'admin')->orWhere('role', 'superadmin')->first();
if (!$admin) {
    $admin = App\Models\User::firstOrCreate(
        ['email' => 'admin@fsaludoclub.com'],
        ['name' => 'Admin User', 'phone' => '9999999999', 'password' => bcrypt('password'), 'role' => 'admin']
    );
}

$uA = App\Models\User::firstOrCreate(
    ['email' => 'player_a_rt@fsaludoclub.test'],
    ['name' => 'Arjun Sharma', 'phone' => '9876543201', 'password' => bcrypt('secret123'), 'role' => 'player', 'coins' => 1500, 'is_phone_verified' => true]
);
$uA->update(['coins' => 1500, 'role' => 'player', 'is_phone_verified' => true, 'kyc_status' => 'verified']);

$uB = App\Models\User::firstOrCreate(
    ['email' => 'player_b_rt@fsaludoclub.test'],
    ['name' => 'Rohit Verma', 'phone' => '9876543202', 'password' => bcrypt('secret123'), 'role' => 'player', 'coins' => 1000, 'is_phone_verified' => true]
);
$uB->update(['coins' => 1000, 'role' => 'player', 'is_phone_verified' => true, 'kyc_status' => 'verified']);

$uC = App\Models\User::firstOrCreate(
    ['email' => 'player_c_unrelated@fsaludoclub.test'],
    ['name' => 'Karan Patel', 'phone' => '9876543203', 'password' => bcrypt('secret123'), 'role' => 'player', 'coins' => 500, 'is_phone_verified' => true]
);
$uC->update(['coins' => 500, 'role' => 'player', 'is_phone_verified' => true, 'kyc_status' => 'verified']);

// Clean up any stale active test battles for these test users
App\Models\LudoBattle::whereIn('creator_id', [$uA->id, $uB->id])
    ->whereIn('status', ['open', 'requested', 'running'])
    ->update(['status' => 'cancelled', 'cancellation_reason' => 'Reset for new e2e test']);

echo json_encode([
    'admin_id' => $admin->id,
    'admin_email' => $admin->email,
    'player_a_id' => $uA->id,
    'player_a_coins' => (int) $uA->fresh()->coins,
    'player_b_id' => $uB->id,
    'player_b_coins' => (int) $uB->fresh()->coins,
    'player_c_id' => $uC->id,
    'player_c_coins' => (int) $uC->fresh()->coins,
]);
