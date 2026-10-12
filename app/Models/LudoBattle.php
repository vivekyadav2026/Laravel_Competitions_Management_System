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
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'entry_fee' => 'integer',
        'prize' => 'integer',
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
     * Compute platform commission prize (typically 95% of total pool: 2 * entry_fee * 0.95)
     */
    public static function calculatePrize(int $entryFee): int
    {
        // 5% platform virtual fee deduction: total pool = $entryFee * 2; prize = pool * 0.95 = 1.9 * entryFee
        return (int) round($entryFee * 1.9);
    }
}
