<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoTicketMessage extends Model
{
    use HasFactory;

    protected $table = 'ludo_ticket_messages';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'is_admin',
        'message',
        'attachment_path',
    ];

    public function ticket()
    {
        return $this->belongsTo(LudoSupportTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
