<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoReferral extends Model
{
    use HasFactory;

    protected $table = 'ludo_referrals';

    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'referral_code',
        'status',
        'reward_points',
        'rewarded_at',
    ];

    protected $casts = [
        'reward_points' => 'integer',
        'rewarded_at' => 'datetime',
    ];

    /**
     * Referrer user who invited
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Referred user who registered
     */
    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
