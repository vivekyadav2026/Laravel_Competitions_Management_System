<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \App\Models\User::orderBy('id')->get();
$slugCounts = [];

foreach ($users as $u) {
    $clean = strtolower(preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-'], '_', strtolower(trim($u->name)))));
    if (empty($clean)) {
        $clean = 'player';
    }
    $slugCounts[$clean] = ($slugCounts[$clean] ?? 0) + 1;
}

$assigned = [];
foreach ($users as $u) {
    $clean = strtolower(preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-'], '_', strtolower(trim($u->name)))));
    if (empty($clean)) {
        $clean = 'player';
    }
    
    // If multiple users share the base name, append their user id deterministically
    if ($slugCounts[$clean] > 1) {
        $username = $clean . '_' . $u->id;
    } else {
        $username = $clean;
    }

    // Guard against collision
    if (in_array($username, $assigned, true)) {
        $username = $clean . '_' . $u->id;
    }

    $assigned[] = $username;
    echo "ID: {$u->id} | Name: '{$u->name}' => @{$username}" . PHP_EOL;
}

echo "Total assigned: " . count($assigned) . ", Unique: " . count(array_unique($assigned)) . PHP_EOL;
