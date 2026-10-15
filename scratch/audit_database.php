<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\LudoBattle;
use App\Models\LudoTournament;
use App\Models\LudoUpiDeposit;
use App\Models\LudoLedgerTransaction;
use App\Models\LudoSupportTicket;
use App\Models\LudoKycSubmission;
use App\Models\LudoDispute;
use App\Models\LudoReferral;
use App\Models\LudoAdminAuditLog;

echo "=== DATABASE COUNTS ===\n";
echo "Users: " . User::count() . "\n";
echo "LudoBattles: " . LudoBattle::count() . "\n";
echo "LudoTournaments: " . LudoTournament::count() . "\n";
echo "LudoUpiDeposits: " . LudoUpiDeposit::count() . "\n";
echo "LudoLedgerTransactions: " . LudoLedgerTransaction::count() . "\n";
echo "LudoSupportTickets: " . LudoSupportTicket::count() . "\n";
echo "LudoKycSubmissions: " . LudoKycSubmission::count() . "\n";
echo "LudoDisputes: " . LudoDispute::count() . "\n";
echo "LudoReferrals: " . LudoReferral::count() . "\n";
echo "LudoAdminAuditLogs: " . LudoAdminAuditLog::count() . "\n";

echo "\n=== BATTLE STATUS BREAKDOWN ===\n";
$battleStatuses = LudoBattle::groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status');
foreach ($battleStatuses as $st => $cnt) {
    echo " - {$st}: {$cnt}\n";
}

echo "\n=== UPI DEPOSITS BREAKDOWN ===\n";
$depStatuses = LudoUpiDeposit::groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status');
foreach ($depStatuses as $st => $cnt) {
    echo " - {$st}: {$cnt}\n";
}

echo "\n=== USER ROLES BREAKDOWN ===\n";
$userRoles = User::groupBy('role')->selectRaw('role, count(*) as count')->pluck('count', 'role');
foreach ($userRoles as $r => $cnt) {
    echo " - {$r}: {$cnt}\n";
}

echo "\n=== USERS WITH DEMO/TEST IDENTIFIERS ===\n";
$demoUsers = User::where(function($q) {
    $q->where('email', 'like', '%@royalludo%')
      ->orWhere('email', 'like', '%@ludoking%')
      ->orWhere('email', 'like', '%demo%')
      ->orWhere('email', 'like', '%test%')
      ->orWhere('email', 'like', '%example%');
})->count();
echo "Users with test/demo email: {$demoUsers} of " . User::count() . "\n";
