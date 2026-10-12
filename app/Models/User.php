<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'player_uid',
        'avatar',
        'phone',
        'hide_email',
        'hide_phone',
        'kyc_status',
        'is_verified',
        'referred_by_id',
        'coins',
        'referral_balance',
        'referral_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'hide_email' => 'boolean',
        'hide_phone' => 'boolean',
        'is_verified' => 'boolean',
        'coins' => 'integer',
        'referral_balance' => 'decimal:2',
    ];

    /**
     * Battles created by this user
     */
    public function createdBattles()
    {
        return $this->hasMany(LudoBattle::class, 'creator_id');
    }

    /**
     * Battles joined by this user
     */
    public function joinedBattles()
    {
        return $this->hasMany(LudoBattle::class, 'joiner_id');
    }

    /**
     * Referrals invited by this user
     */
    public function referrals()
    {
        return $this->hasMany(LudoReferral::class, 'referrer_id');
    }

    /**
     * The user who referred this user
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by_id');
    }

    /**
     * Check if user legitimately qualifies for the Pro Gamer badge
     * Criteria: At least 5 battles played AND win rate >= 60%
     */
    public function isProGamer(): bool
    {
        $stats = $this->getBattleStats();
        return $stats['total'] >= 5 && $stats['win_rate'] >= 60;
    }

    /**
     * Calculate legitimate battle statistics (no fake stats)
     */
    public function getBattleStats(): array
    {
        $createdCompleted = LudoBattle::where('creator_id', $this->id)
            ->where('status', 'completed')
            ->get();

        $joinedCompleted = LudoBattle::where('joiner_id', $this->id)
            ->where('status', 'completed')
            ->get();

        $total = $createdCompleted->count() + $joinedCompleted->count();

        $won = LudoBattle::where('winner_id', $this->id)
            ->where('status', 'completed')
            ->count();

        $lost = $total - $won;
        $winRate = $total > 0 ? round(($won / $total) * 100) : 0;

        $virtualPointsEarned = LudoBattle::where('winner_id', $this->id)
            ->where('status', 'completed')
            ->sum('prize');

        return [
            'total' => $total,
            'won' => $won,
            'lost' => max(0, $lost),
            'win_rate' => $winRate,
            'points_earned' => (int) $virtualPointsEarned,
        ];
    }
}
