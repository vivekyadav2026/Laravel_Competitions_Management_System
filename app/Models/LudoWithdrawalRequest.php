<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoWithdrawalRequest extends Model
{
    use HasFactory;

    protected $table = 'ludo_withdrawal_requests';

    protected $fillable = [
        'withdrawal_uid',
        'user_id',
        'amount',
        'payout_method',
        'upi_id',
        'bank_account_number',
        'bank_ifsc',
        'bank_name',
        'account_holder_name',
        'status',
        'admin_utr_reference',
        'admin_notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'processed_at' => 'datetime',
    ];

    /**
     * The player who requested the withdrawal
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The admin staff who processed / settled this payout
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
