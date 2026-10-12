<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoAdminAuditLog extends Model
{
    use HasFactory;

    protected $table = 'ludo_admin_audit_logs';

    protected $fillable = [
        'admin_id',
        'action_type',
        'target_type',
        'target_id',
        'description',
        'ip_address',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public static function log(string $actionType, string $description, ?string $targetType = null, ?int $targetId = null): self
    {
        return self::create([
            'admin_id' => auth()->id() ?? 1,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }
}
