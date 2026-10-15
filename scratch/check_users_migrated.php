<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \App\Models\User::all(['id', 'name', 'username', 'email']);
echo "Total users: " . $users->count() . PHP_EOL;
foreach ($users as $u) {
    echo "ID: {$u->id} | Name: {$u->name} | Username: @{$u->username}" . PHP_EOL;
}
