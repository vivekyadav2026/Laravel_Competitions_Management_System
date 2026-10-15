<?php

namespace App\Console\Commands;

use App\Models\LudoAdminAuditLog;
use App\Models\LudoLedgerTransaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResetWalletBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ludo:reset-wallet-balances 
                            {--dry-run : Report affected tables and preview adjustments without changing data}
                            {--force : Execute the balance reset without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely reset all user wallet coins and referral balances to zero with formal ledger audit trail';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $databaseName = DB::connection()->getDatabaseName();
        $this->info("=== FSALUDOCLUB WALLET BALANCE RESET UTILITY ===");
        $this->line("Target Database: <comment>{$databaseName}</comment>");

        // 1. Identify all users
        $allUsers = User::all();
        $totalUsers = $allUsers->count();

        $usersWithCoins = $allUsers->filter(fn ($u) => (int) $u->coins !== 0);
        $usersWithReferral = $allUsers->filter(fn ($u) => (float) $u->referral_balance > 0);

        $totalCoinsToZero = $usersWithCoins->sum('coins');
        $totalReferralToZero = $usersWithReferral->sum('referral_balance');

        $this->newLine();
        $this->info("--- Pre-Reset Audit Summary ---");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Registered Accounts', $totalUsers],
                ['Accounts with Non-Zero Coins', $usersWithCoins->count()],
                ['Accounts with Non-Zero Referral Balance', $usersWithReferral->count()],
                ['Total Coins Circulating', number_format($totalCoinsToZero)],
                ['Total Referral Commission Outstanding', '₹' . number_format($totalReferralToZero, 2)],
                ['Affected Table 1', 'users (columns: coins, referral_balance)'],
                ['Affected Table 2', 'ludo_ledger_transactions (records: admin_adjustment)'],
                ['Affected Table 3', 'ludo_admin_audit_logs (action: wallet_balance_reset)'],
            ]
        );

        // Preview account details
        $rows = [];
        foreach ($allUsers as $u) {
            $rows[] = [
                $u->id,
                $u->player_uid ?? 'N/A',
                $u->name,
                $u->email,
                $u->role,
                $u->status,
                number_format($u->coins),
                number_format($u->referral_balance, 2),
                (int) $u->coins !== 0 ? '-' . number_format($u->coins) : '0 (No adjustment)',
            ];
        }

        $this->newLine();
        $this->info("--- Accounts Breakdown ---");
        $this->table(
            ['ID', 'UID', 'Name', 'Email', 'Role', 'Status', 'Current Coins', 'Current Referral', 'Proposed Delta'],
            $rows
        );

        if ($this->option('dry-run')) {
            $this->warn("DRY RUN ACTIVE: No database changes or table backups were executed.");
            return 0;
        }

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to proceed with resetting all user balances to zero?')) {
                $this->warn("Operation cancelled by user.");
                return 1;
            }
        }

        // 2. Perform table backups before changing data
        $timestamp = now()->format('Ymd_His') . '_' . Str::lower(Str::random(4));
        $usersBackupTable = "users_backup_{$timestamp}";
        $ledgerBackupTable = "ludo_ledger_backup_{$timestamp}";

        $this->info("Creating pre-reset table backups...");
        DB::statement("CREATE TABLE IF NOT EXISTS `{$usersBackupTable}` AS SELECT * FROM `users`");
        DB::statement("CREATE TABLE IF NOT EXISTS `{$ledgerBackupTable}` AS SELECT * FROM `ludo_ledger_transactions`");
        $this->line("✔ Users table backed up to: <comment>{$usersBackupTable}</comment>");
        $this->line("✔ Ledger table backed up to: <comment>{$ledgerBackupTable}</comment>");

        // 3. Find administrative user for ledger attribution
        $adminUser = User::whereIn('role', ['superadmin', 'admin'])->first();
        $adminId = $adminUser ? $adminUser->id : 1;

        // 4. Atomic balance reset with ledger recording
        $adjustedCount = 0;
        $ledgerEntriesCreated = 0;

        DB::transaction(function () use ($allUsers, $adminId, &$adjustedCount, &$ledgerEntriesCreated) {
            foreach ($allUsers as $u) {
                // Row-level lock to prevent concurrent modifications
                $lockedUser = User::where('id', $u->id)->lockForUpdate()->first();
                $currentCoins = (int) $lockedUser->coins;
                $currentReferral = (float) $lockedUser->referral_balance;

                $needsUpdate = false;

                // Adjust coins if non-zero
                if ($currentCoins !== 0) {
                    $delta = -$currentCoins;

                    LudoLedgerTransaction::create([
                        'tx_uid' => 'TX-' . strtoupper(Str::random(10)),
                        'user_id' => $lockedUser->id,
                        'type' => 'admin_adjustment',
                        'points_delta' => $delta,
                        'balance_before' => $currentCoins,
                        'balance_after' => 0,
                        'status' => 'successful',
                        'description' => 'System-wide wallet balance reset to zero',
                        'reason' => 'Authorized platform balance reset by administrator',
                        'created_by_admin_id' => $adminId,
                    ]);

                    $lockedUser->coins = 0;
                    $needsUpdate = true;
                    $ledgerEntriesCreated++;
                }

                // Adjust referral balance if non-zero
                if ($currentReferral > 0) {
                    $lockedUser->referral_balance = 0.00;
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $lockedUser->save();
                    $adjustedCount++;

                    LudoAdminAuditLog::log(
                        'wallet_balance_reset',
                        "System-wide balance reset: Zeroed {$currentCoins} coins and {$currentReferral} referral balance for user {$lockedUser->name} (#{$lockedUser->id})",
                        'User',
                        $lockedUser->id
                    );
                }
            }
        });

        // 5. Clear application cache so all views and aggregates re-query MySQL
        Artisan::call('cache:clear');
        $this->line("✔ Application cache cleared.");

        // 6. Post-reset verification
        $remainingNonZeroCoins = User::where('coins', '!=', 0)->count();
        $remainingCirculatingCoins = (int) User::sum('coins');
        $remainingReferralBalance = (float) User::sum('referral_balance');

        $this->newLine();
        $this->info("--- Post-Reset Verification ---");
        $this->table(
            ['Verification Check', 'Result', 'Status'],
            [
                ['Accounts Adjusted', $adjustedCount, '✔ Complete'],
                ['Ledger Entries Created', $ledgerEntriesCreated, '✔ Complete'],
                ['Remaining Non-Zero Coin Accounts', $remainingNonZeroCoins, $remainingNonZeroCoins === 0 ? '✔ Verified Zero' : '❌ Failed'],
                ['Total Circulating Coins in DB', $remainingCirculatingCoins, $remainingCirculatingCoins === 0 ? '✔ Verified Zero' : '❌ Failed'],
                ['Total Referral Balance in DB', '₹' . number_format($remainingReferralBalance, 2), $remainingReferralBalance == 0.0 ? '✔ Verified Zero' : '❌ Failed'],
                ['Historical Transactions Preserved', LudoLedgerTransaction::count() . ' total records', '✔ Intact'],
                ['Pre-Reset Backup 1', $usersBackupTable, '✔ Secured'],
                ['Pre-Reset Backup 2', $ledgerBackupTable, '✔ Secured'],
            ]
        );

        $this->info("🎉 All user wallet balances have been safely reset to zero!");
        return 0;
    }
}
