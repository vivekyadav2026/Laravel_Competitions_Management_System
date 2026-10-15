<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$battleId = (int) ($argv[1] ?? 0);
$battle = App\Models\LudoBattle::find($battleId);
if (!$battle) {
    echo json_encode(['error' => 'Battle not found']);
    exit(1);
}

$payoutCount = App\Models\LudoLedgerTransaction::where('type', 'prize_payout')
    ->where('description', 'like', "%{$battle->battle_uid}%")
    ->where('status', 'successful')
    ->count();

$refundCount = App\Models\LudoLedgerTransaction::where('type', 'refund')
    ->where('description', 'like', "%{$battle->battle_uid}%")
    ->where('status', 'successful')
    ->count();

echo json_encode([
    'status' => $battle->status,
    'winner_id' => $battle->winner_id,
    'payout_count' => $payoutCount,
    'refund_count' => $refundCount,
    'player_a_coins' => (int) $battle->creator->fresh()->coins,
    'player_b_coins' => (int) $battle->joiner->fresh()->coins,
]);
