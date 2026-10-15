<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transactions = \App\Models\LudoLedgerTransaction::whereIn('description', [
    'Refund for cancelled Battle BAT-SND-2B7FBB',
    'Refund for cancelled Battle BAT-BLU-250'
])->orWhere('description', 'like', '%1275%')
  ->orWhere('description', 'like', '%1440%')
  ->orWhere('description', 'like', '%BAT-SND-2B7FBB%')
  ->orWhere('description', 'like', '%BAT-BLU-250%')
  ->get();

echo "Ledger matches: " . $transactions->count() . "\n";
foreach ($transactions as $t) {
    echo "ID: {$t->id} | User: {$t->user_id} | Amount: {$t->amount} | Type: {$t->type} | Desc: {$t->description}\n";
}
