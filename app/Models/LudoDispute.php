<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoDispute extends Model
{
    use HasFactory;

    protected $table = 'ludo_disputes';

    protected $fillable = [
        'dispute_uid',
        'battle_id',
        'opened_by_user_id',
        'against_user_id',
        'reason_category',
        'description',
        'status',
        'evidence_path',
        'admin_decision_reason',
        'resolved_by_admin_id',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function battle()
    {
        return $this->belongsTo(LudoBattle::class, 'battle_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function againstUser()
    {
        return $this->belongsTo(User::class, 'against_user_id');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by_admin_id');
    }

    public function evidences()
    {
        return $this->hasMany(LudoDisputeEvidence::class, 'dispute_id')->oldest('id');
    }
}
