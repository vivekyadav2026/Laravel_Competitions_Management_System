<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoUpiDeposit extends Model
{
    use HasFactory;

    protected $table = 'ludo_upi_deposits';

    protected $fillable = [
        'deposit_uid',
        'user_id',
        'amount',
        'utr_number',
        'upi_id',
        'screenshot_path',
        'status',
        'admin_notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getRejectionReasonAttribute()
    {
        return $this->admin_notes;
    }
}
