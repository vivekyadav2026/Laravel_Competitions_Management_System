<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoDisputeEvidence extends Model
{
    use HasFactory;

    protected $table = 'ludo_dispute_evidences';

    protected $fillable = [
        'dispute_id',
        'user_id',
        'notes',
        'file_path',
        'file_mime',
    ];

    public function dispute()
    {
        return $this->belongsTo(LudoDispute::class, 'dispute_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
