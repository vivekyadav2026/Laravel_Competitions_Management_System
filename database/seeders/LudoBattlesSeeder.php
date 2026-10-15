<?php

namespace Database\Seeders;

use App\Models\LudoBattle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LudoBattlesSeeder extends Seeder
{
    /**
     * Run the database seeds with authentic Indian player names.
     */
    public function run(): void
    {
        // Indian player accounts
        $players = [
            ['name' => 'Rahul Sharma', 'email' => 'rahul.sharma@ludoking.test', 'phone' => '9820112233'],
            ['name' => 'Aman Verma', 'email' => 'aman.verma@ludoking.test', 'phone' => '9820112234'],
            ['name' => 'Vikram Singh', 'email' => 'vikram.singh@ludoking.test', 'phone' => '9820112235'],
            ['name' => 'Rohit Kumar', 'email' => 'rohit.kumar@ludoking.test', 'phone' => '9820112236'],
            ['name' => 'Deepak Patel', 'email' => 'deepak.patel@ludoking.test', 'phone' => '9820112237'],
            ['name' => 'Sachin Yadav', 'email' => 'sachin.yadav@ludoking.test', 'phone' => '9820112238'],
            ['name' => 'Aditya Raj', 'email' => 'aditya.raj@ludoking.test', 'phone' => '9820112239'],
            ['name' => 'Suresh Gupta', 'email' => 'suresh.gupta@ludoking.test', 'phone' => '9820112240'],
            ['name' => 'Ankit Mishra', 'email' => 'ankit.mishra@ludoking.test', 'phone' => '9820112241'],
            ['name' => 'Pooja Singh', 'email' => 'pooja.singh@ludoking.test', 'phone' => '9820112242'],
            ['name' => 'Sunil Kumar', 'email' => 'sunil.kumar@ludoking.test', 'phone' => '9820112243'],
            ['name' => 'Vikas Meena', 'email' => 'vikas.meena@ludoking.test', 'phone' => '9820112244'],
            ['name' => 'Manoj Joshi', 'email' => 'manoj.joshi@ludoking.test', 'phone' => '9820112245'],
            ['name' => 'Karan Singh', 'email' => 'karan.singh@ludoking.test', 'phone' => '9820112246'],
        ];

        $userIds = [];
        foreach ($players as $p) {
            $user = User::firstOrCreate(
                ['email' => $p['email']],
                [
                    'name' => $p['name'],
                    'phone' => $p['phone'],
                    'password' => bcrypt('secret123'),
                    'coins' => 10000,
                    'referral_code' => strtoupper(Str::random(6)),
                    'status' => 'active',
                    'role' => 'normal',
                ]
            );

            if (empty($user->player_uid)) {
                $user->update(['player_uid' => 'LUDO' . str_pad($user->id, 5, '0', STR_PAD_LEFT)]);
            }

            $userIds[$p['name']] = $user->id;
        }

        // Open Battles (₹50, ₹100, ₹250, ₹1550, ₹3350)
        $openData = [
            ['creator' => 'Rahul Sharma', 'fee' => 50],
            ['creator' => 'Aman Verma', 'fee' => 100],
            ['creator' => 'Vikram Singh', 'fee' => 250],
            ['creator' => 'Deepak Patel', 'fee' => 1550],
            ['creator' => 'Sachin Yadav', 'fee' => 3350],
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
            ['p1' => 'Rohit Kumar', 'p2' => 'Suresh Gupta', 'fee' => 100, 'room' => '10492841'],
            ['p1' => 'Aditya Raj', 'p2' => 'Ankit Mishra', 'fee' => 200, 'room' => '73910482'],
            ['p1' => 'Sunil Kumar', 'p2' => 'Vikas Meena', 'fee' => 250, 'room' => '58190248'],
            ['p1' => 'Manoj Joshi', 'p2' => 'Karan Singh', 'fee' => 1500, 'room' => '49201948'],
            ['p1' => 'Pooja Singh', 'p2' => 'Deepak Patel', 'fee' => 500, 'room' => '83920194'],
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
