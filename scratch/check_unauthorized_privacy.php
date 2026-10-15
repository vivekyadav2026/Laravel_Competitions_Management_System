<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$battleId = (int) ($argv[1] ?? 0);
$userId = (int) ($argv[2] ?? 0);

Auth::loginUsingId($userId);
$req = Illuminate\Http\Request::create('/battles/sync-state', 'GET', ['battle_id' => $battleId]);
$req->headers->set('Accept', 'application/json');
$res = app()->handle($req);
$data = json_decode($res->getContent(), true);

echo json_encode([
    'authorized' => $data['battle']['authorized'] ?? null,
    'room_code' => $data['battle']['room_code'] ?? null,
    'creator_claim' => $data['battle']['creator_claimed_result'] ?? null,
    'joiner_claim' => $data['battle']['joiner_claimed_result'] ?? null,
]);
