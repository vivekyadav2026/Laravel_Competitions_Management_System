<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$b = App\Models\LudoBattle::latest()->first();
if ($b) {
    echo json_encode([
        'id' => $b->id,
        'uid' => $b->battle_uid,
        'status' => $b->status,
        'creator_id' => $b->creator_id,
        'joiner_id' => $b->joiner_id,
        'room_code' => $b->room_code,
        'creator_claim' => $b->creator_claimed_result,
        'joiner_claim' => $b->joiner_claimed_result,
        'review_status' => $b->review_status,
        'refund_status' => $b->refund_status,
    ], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "No battle found\n";
}
