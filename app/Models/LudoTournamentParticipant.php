<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoTournamentParticipant extends Model
{
    use HasFactory;

    protected $table = 'ludo_tournament_participants';

    protected $fillable = [
        'tournament_id',
        'user_id',
        'seed',
        'current_round',
        'status',
    ];

    public function tournament()
    {
        return $this->belongsTo(LudoTournament::class, 'tournament_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
