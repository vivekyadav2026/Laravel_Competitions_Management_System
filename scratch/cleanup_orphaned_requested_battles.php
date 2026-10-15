<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LudoBattle;
use App\Models\LudoLedgerTransaction;
use Illuminate\Support\Facades\DB;

$orphanedBattles = LudoBattle::with(['creator', 'joiner'])
    ->where('status', 'requested')
    ->where('updated_at', '<', now()->subMinutes(15))
    ->get();

echo "Found " . $orphanedBattles->count() . " orphaned requested battles to cancel.\n";

$cancelledCount = 0;
foreach ($orphanedBattles as $battle) {
    DB::transaction(function () use ($battle, &$cancelledCount) {
        $lockedBattle = LudoBattle::lockForUpdate()->find($battle->id);
        if ($lockedBattle->status !== 'requested') {
            return;
        }

        // Refund creator if needed
        if ($lockedBattle->creator) {
            // Check if creator had already been refunded
            $alreadyRefunded = LudoLedgerTransaction::where('user_id', $lockedBattle->creator_id)
                ->where('type', 'refund')
                ->where('description', 'like', "%{$lockedBattle->battle_uid}%")
                ->exists();

            if (!$alreadyRefunded) {
                LudoLedgerTransaction::recordAdjustment(
                    $lockedBattle->creator,
                    $lockedBattle->entry_fee,
                    'refund',
                    "Refund for expired requested Battle {$lockedBattle->battle_uid}",
                    'successful'
                );
            }
        }

        // Refund joiner if needed
        if ($lockedBattle->joiner) {
            $alreadyRefunded = LudoLedgerTransaction::where('user_id', $lockedBattle->joiner_id)
                ->where('type', 'refund')
                ->where('description', 'like', "%{$lockedBattle->battle_uid}%")
                ->exists();

            if (!$alreadyRefunded) {
                LudoLedgerTransaction::recordAdjustment(
                    $lockedBattle->joiner,
                    $lockedBattle->entry_fee,
                    'refund',
                    "Refund for expired requested Battle {$lockedBattle->battle_uid}",
                    'successful'
                );
            }
        }

        $lockedBattle->update([
            'status' => 'cancelled',
            'cancellation_reason' => 'Expired requested match cleaned up by system',
            'completed_at' => now(),
        ]);

        $cancelledCount++;
    });
}

echo "Successfully cancelled and refunded {$cancelledCount} orphaned requested battles.\n";

$remainingRequested = LudoBattle::where('status', 'requested')->count();
echo "Remaining requested battles: {$remainingRequested}\n";
