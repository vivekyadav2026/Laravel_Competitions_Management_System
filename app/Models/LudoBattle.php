<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoBattle extends Model
{
    use HasFactory;

    protected $table = 'ludo_battles';

    protected $fillable = [
        'battle_uid',
        'creator_id',
        'joiner_id',
        'entry_fee',
        'prize',
        'status',
        'room_code',
        'winner_id',
        'claimed_result',
        'final_result',
        'review_status',
        'creator_claimed_result',
        'joiner_claimed_result',
        'submission_deadline_at',
        'room_code_expires_at',
        'cancellation_reason',
        'cancelled_by_id',
        'dispute_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'entry_fee' => 'integer',
        'prize' => 'integer',
        'submission_deadline_at' => 'datetime',
        'room_code_expires_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];


    /**
     * Match creator (player 1)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Match joiner / opponent (player 2)
     */
    public function joiner()
    {
        return $this->belongsTo(User::class, 'joiner_id');
    }

    /**
     * Winner
     */
    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    /**
     * Compute platform commission and prize based on dynamic database settings / slabs.
     * 
     * @param int $entryFee
     * @return array ['pool' => int, 'commission' => int, 'prize' => int, 'rate_applied' => string]
     */
    public static function calculateCommission(int $entryFee): array
    {
        $pool = $entryFee * 2;
        $mode = LudoSetting::get('battle_commission_mode', 'percentage');

        // Check if slab mode is enabled
        if ($mode === 'slabs') {
            $slabsJson = LudoSetting::get('battle_commission_slabs');
            $slabs = is_string($slabsJson) ? json_decode($slabsJson, true) : $slabsJson;

            if (is_array($slabs) && !empty($slabs)) {
                foreach ($slabs as $slab) {
                    $min = (int) ($slab['min'] ?? 0);
                    $max = (int) ($slab['max'] ?? PHP_INT_MAX);

                    if ($entryFee >= $min && $entryFee <= $max) {
                        $type = $slab['type'] ?? 'percent';
                        $value = (float) ($slab['value'] ?? 5);

                        if ($type === 'fixed') {
                            $commission = (int) round($value);
                            $rateApplied = "Fixed: {$commission} coins";
                        } elseif ($type === 'percent_of_battle' || $type === 'percent_of_entry' || ($slab['basis'] ?? '') === 'battle' || ($slab['basis'] ?? '') === 'entry') {
                            $commission = (int) round($entryFee * ($value / 100));
                            $rateApplied = "{$value}% of battle amount";
                        } else {
                            // Percentage of total pool
                            $commission = (int) round($pool * ($value / 100));
                            $rateApplied = "{$value}% of pool";
                        }

                        $commission = max(0, min($commission, $pool));
                        $prize = $pool - $commission;

                        return [
                            'pool' => $pool,
                            'commission' => $commission,
                            'prize' => $prize,
                            'rate_applied' => $rateApplied,
                        ];
                    }
                }
            }
        }

        // Standard / Percentage mode (default: 5% platform fee on pool => 1.9x entry fee)
        $pctSetting = LudoSetting::get('battle_commission_percentage', '5');
        // Clean any '%' sign from setting
        $pct = (float) str_replace('%', '', (string) $pctSetting);
        if ($pct <= 0) {
            $pct = 5.0;
        }

        $commission = (int) round($pool * ($pct / 100));
        $commission = max(0, min($commission, $pool));
        $prize = $pool - $commission;

        return [
            'pool' => $pool,
            'commission' => $commission,
            'prize' => $prize,
            'rate_applied' => "{$pct}% standard platform commission",
        ];
    }

    /**
     * Compute platform commission prize using authoritative dynamic configuration
     */
    public static function calculatePrize(int $entryFee): int
    {
        return self::calculateCommission($entryFee)['prize'];
    }

    /**
     * Dispute linked to this battle
     */
    public function dispute()
    {
        return $this->belongsTo(LudoDispute::class, 'dispute_id');
    }
}
