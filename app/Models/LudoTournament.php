<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LudoTournament extends Model
{
    use HasFactory;

    protected $table = 'ludo_tournaments';

    protected $fillable = [
        'tournament_uid',
        'title',
        'description',
        'entry_fee',
        'prize_pool',
        'max_players',
        'status',
        'registration_closes_at',
        'starts_at',
        'completed_at',
        'rules_text',
        'bracket_data',
        'created_by_admin_id',
        'winner_user_id',
    ];

    protected $casts = [
        'entry_fee' => 'integer',
        'prize_pool' => 'integer',
        'max_players' => 'integer',
        'registration_closes_at' => 'datetime',
        'starts_at' => 'datetime',
        'completed_at' => 'datetime',
        'bracket_data' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }

    public function participants()
    {
        return $this->hasMany(LudoTournamentParticipant::class, 'tournament_id');
    }

    /**
     * Helper to generate a single-elimination bracket structure
     */
    public function generateBracket(): array
    {
        $players = $this->participants()->with('user')->get();
        $playerList = $players->map(function ($p, $idx) {
            return [
                'participant_id' => $p->id,
                'user_id' => $p->user_id,
                'name' => $p->user->name ?? "Player " . ($idx + 1),
                'seed' => $idx + 1,
            ];
        })->toArray();

        $count = count($playerList);
        $rounds = [];
        $round1Matches = [];

        for ($i = 0; $i < $count; $i += 2) {
            $p1 = $playerList[$i] ?? null;
            $p2 = $playerList[$i + 1] ?? null;
            $round1Matches[] = [
                'match_index' => ($i / 2) + 1,
                'player1' => $p1,
                'player2' => $p2,
                'winner_id' => null,
                'status' => ($p1 && $p2) ? 'ready' : 'bye',
            ];
        }

        $rounds['round_1'] = $round1Matches;
        $this->update(['bracket_data' => $rounds]);
        return $rounds;
    }
}
