<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoSupportTicket extends Model
{
    use HasFactory;

    protected $table = 'ludo_support_tickets';

    protected $fillable = [
        'ticket_uid',
        'user_id',
        'category',
        'subject',
        'description',
        'status',
        'priority',
        'attachment_path',
        'attachment_mime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages()
    {
        return $this->hasMany(LudoTicketMessage::class, 'ticket_id')->oldest('id');
    }
}
