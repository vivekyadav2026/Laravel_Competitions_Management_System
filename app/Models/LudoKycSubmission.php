<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoKycSubmission extends Model
{
    use HasFactory;

    protected $table = 'ludo_kyc_submissions';

    protected $fillable = [
        'user_id',
        'document_type',
        'document_number',
        'front_image_path',
        'back_image_path',
        'status',
        'admin_reason',
        'reviewed_by_admin_id',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }
}
