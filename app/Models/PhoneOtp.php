<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneOtp extends Model
{
    use HasFactory;

    protected $table = 'phone_otps';

    protected $fillable = [
        'phone',
        'otp',
        'ip_address',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * Check if OTP is still valid (not expired, not verified, not max attempts)
     */
    public function isValid(): bool
    {
        return is_null($this->verified_at) &&
               $this->expires_at->isFuture() &&
               $this->attempts < 5;
    }
}
