<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class LudoLedgerTransaction extends Model
{
    use HasFactory;

    protected $table = 'ludo_ledger_transactions';

    protected $fillable = [
        'tx_uid',
        'user_id',
        'type',
        'points_delta',
        'balance_before',
        'balance_after',
        'status',
        'description',
        'reason',
        'created_by_admin_id',
    ];

    protected $casts = [
        'points_delta' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
    ];

    /**
     * User associated with the ledger entry
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Admin who authorized adjustment (if any)
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    /**
     * Atomically adjust a user's virtual points balance with ledger recording
     * Prevents negative balance, guarantees atomic DB transactions, provides audit trail
     */
    public static function recordAdjustment(
        User $user,
        int $delta,
        string $type,
        string $description,
        string $status = 'successful',
        ?string $reason = null,
        ?int $adminId = null
    ): self {
        if ($type === 'admin_adjustment' && empty($reason)) {
            throw new InvalidArgumentException('A mandatory reason is required for administrative balance adjustments.');
        }

        return DB::transaction(function () use ($user, $delta, $type, $description, $status, $reason, $adminId) {
            // Lock row for update to prevent race conditions & duplicate crediting
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            $balanceBefore = $lockedUser->coins;
            $newBalance = $balanceBefore + $delta;

            if ($status === 'successful') {
                if ($newBalance < 0) {
                    throw new InvalidArgumentException("Insufficient balance! Current: {$balanceBefore}, required deduction: " . abs($delta));
                }
                $lockedUser->coins = $newBalance;
                $lockedUser->save();
            } else {
                // If transaction failed or expired, balance is unaffected
                $newBalance = $balanceBefore;
            }

            return self::create([
                'tx_uid' => 'TX-' . strtoupper(Str::random(10)),
                'user_id' => $user->id,
                'type' => $type,
                'points_delta' => $delta,
                'balance_before' => $balanceBefore,
                'balance_after' => $newBalance,
                'status' => $status,
                'description' => $description,
                'reason' => $reason,
                'created_by_admin_id' => $adminId,
            ]);
        });
    }
}
