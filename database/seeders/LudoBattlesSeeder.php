<?php

namespace Database\Seeders;

use App\Models\LudoBattle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LudoBattlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed tournament player accounts if none exist
        $players = [
            ['name' => 'UEsBa', 'email' => 'uesba@royalludo.test'],
            ['name' => 'Hello...', 'email' => 'hello@royalludo.test'],
            ['name' => 'mlEhr', 'email' => 'mlehr@royalludo.test'],
            ['name' => 'Ztrmk...', 'email' => 'ztrmk@royalludo.test'],
            ['name' => 'Kmyil...', 'email' => 'kmyil@royalludo.test'],
            ['name' => 'Lalla...', 'email' => 'lalla@royalludo.test'],
            ['name' => 'MlqwZ', 'email' => 'mlqwz@royalludo.test'],
            ['name' => 'UrXeD', 'email' => 'urxed@royalludo.test'],
            ['name' => 'ExzaW', 'email' => 'exzaw@royalludo.test'],
            ['name' => 'SEzgE', 'email' => 'sezge@royalludo.test'],
            ['name' => 'eliRB', 'email' => 'elirb@royalludo.test'],
            ['name' => 'ICOrG', 'email' => 'icorg@royalludo.test'],
            ['name' => 'No Th...', 'email' => 'noth@royalludo.test'],
            ['name' => 'TABHH', 'email' => 'tabhh@royalludo.test'],
        ];

        $userIds = [];
        foreach ($players as $p) {
            $user = User::firstOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'],
                    'password' => bcrypt('secret123'),
                    'coins' => 5000,
                    'referral_code' => strtoupper(Str::random(6)),
                ]
            );
            $userIds[$p['name']] = $user->id;
        }

        // Open Battles from reference screenshots
        $openData = [
            ['creator' => 'UEsBa', 'fee' => 250],
            ['creator' => 'Hello...', 'fee' => 1600],
            ['creator' => 'mlEhr', 'fee' => 2400],
            ['creator' => 'Ztrmk...', 'fee' => 19700],
            ['creator' => 'Kmyil...', 'fee' => 36250],
        ];

        foreach ($openData as $b) {
            if (isset($userIds[$b['creator']])) {
                LudoBattle::firstOrCreate(
                    [
                        'creator_id' => $userIds[$b['creator']],
                        'entry_fee' => $b['fee'],
                        'status' => 'open',
                    ],
                    [
                        'battle_uid' => 'BAT-' . strtoupper(Str::random(8)),
                        'prize' => LudoBattle::calculatePrize($b['fee']),
                        'status' => 'open',
                    ]
                );
            }
        }

        // Running Battles from reference screenshots
        $runningData = [
            ['p1' => 'UEsBa', 'p2' => 'Lalla...', 'fee' => 200, 'room' => '10492841'],
            ['p1' => 'MlqwZ', 'p2' => 'UrXeD', 'fee' => 250, 'room' => '73910482'],
            ['p1' => 'ExzaW', 'p2' => 'SEzgE', 'fee' => 100, 'room' => '58190248'],
            ['p1' => 'eliRB', 'p2' => 'ICOrG', 'fee' => 1500, 'room' => '49201948'],
            ['p1' => 'No Th...', 'p2' => 'TABHH', 'fee' => 350, 'room' => '83920194'],
        ];

        foreach ($runningData as $rb) {
            if (isset($userIds[$rb['p1']], $userIds[$rb['p2']])) {
                LudoBattle::firstOrCreate(
                    [
                        'creator_id' => $userIds[$rb['p1']],
                        'joiner_id' => $userIds[$rb['p2']],
                        'status' => 'running',
                    ],
                    [
                        'battle_uid' => 'BAT-' . strtoupper(Str::random(8)),
                        'entry_fee' => $rb['fee'],
                        'prize' => LudoBattle::calculatePrize($rb['fee']),
                        'status' => 'running',
                        'room_code' => $rb['room'],
                        'started_at' => now()->subMinutes(rand(1, 10)),
                    ]
                );
            }
        }
    }
}
