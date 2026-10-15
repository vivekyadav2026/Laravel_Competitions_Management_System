<?php

namespace Database\Seeders;

use App\Models\LudoBattle;
use App\Models\LudoKycSubmission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RunningBattleDemoSeeder extends Seeder
{
    /**
     * Run the database seeds to populate development-only Running Battles for visual verification.
     * Strictly guarded against running in production.
     * Fully idempotent (safe to rerun without duplicating records).
     */
    public function run(): void
    {
        // 1. Guard against execution in production environment
        if (app()->environment('production')) {
            $this->command?->warn('RunningBattleDemoSeeder is strictly disabled in production.');
            return;
        }

        // 2. Exactly 12 authentic Indian players for development & demo lobbies
        $demoUsersData = [
            ['name' => 'Arjun Sharma', 'username' => 'arjun_sharma', 'email' => 'demo_arjun@fsaludoclub.test', 'phone' => '9876000101'],
            ['name' => 'Rohit Verma', 'username' => 'rohit_verma', 'email' => 'demo_rohit@fsaludoclub.test', 'phone' => '9876000102'],
            ['name' => 'Priya Patel', 'username' => 'priya_patel', 'email' => 'demo_priya@fsaludoclub.test', 'phone' => '9876000103'],
            ['name' => 'Vikram Singh', 'username' => 'vikram_singh', 'email' => 'demo_vikram@fsaludoclub.test', 'phone' => '9876000104'],
            ['name' => 'Neha Gupta', 'username' => 'neha_gupta', 'email' => 'demo_neha@fsaludoclub.test', 'phone' => '9876000105'],
            ['name' => 'Suresh Raina', 'username' => 'suresh_raina', 'email' => 'demo_suresh@fsaludoclub.test', 'phone' => '9876000106'],
            ['name' => 'Rahul Sharma', 'username' => 'rahul_sharma', 'email' => 'demo_rahul@fsaludoclub.test', 'phone' => '9876000107'],
            ['name' => 'Deepak Yadav', 'username' => 'deepak_yadav', 'email' => 'demo_deepak@fsaludoclub.test', 'phone' => '9876000110'],
            ['name' => 'Amit Kumar', 'username' => 'amit_kumar', 'email' => 'demo_amit@fsaludoclub.test', 'phone' => '9876000111'],
            ['name' => 'Pooja Sharma', 'username' => 'pooja_sharma', 'email' => 'demo_pooja@fsaludoclub.test', 'phone' => '9876000112'],
            ['name' => 'Manish Tiwari', 'username' => 'manish_tiwari', 'email' => 'demo_manish@fsaludoclub.test', 'phone' => '9876000113'],
            ['name' => 'Anjali Verma', 'username' => 'anjali_verma', 'email' => 'demo_anjali@fsaludoclub.test', 'phone' => '9876000114'],
        ];

        $users = [];
        foreach ($demoUsersData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'phone' => $data['phone'],
                    'password' => bcrypt('DemoPass123!'),
                    'coins' => 50000,
                    'status' => 'active',
                    'role' => 'normal',
                    'referral_code' => 'DEMO' . strtoupper(Str::random(4)),
                ]
            );

            if (empty($user->username)) {
                $user->update(['username' => $data['username']]);
            }

            if (empty($user->player_uid)) {
                $user->update(['player_uid' => 'DEMO' . str_pad($user->id, 5, '0', STR_PAD_LEFT)]);
            }

            $users[$data['email']] = $user;
        }

        // 3. Define Development Running Battles with High-Stakes and authentic Indian contenders (12 running battles)
        $runningBattlesData = [
            [
                'battle_uid' => 'DEMO-RUN-20000',
                'creator_email' => 'demo_vikram@fsaludoclub.test',
                'joiner_email' => 'demo_deepak@fsaludoclub.test',
                'entry_fee' => 20000,
                'room_code' => '91823746',
                'started_minutes_ago' => 2,
            ],
            [
                'battle_uid' => 'DEMO-RUN-15000',
                'creator_email' => 'demo_suresh@fsaludoclub.test',
                'joiner_email' => 'demo_rohit@fsaludoclub.test',
                'entry_fee' => 15000,
                'room_code' => '48201948',
                'started_minutes_ago' => 3,
            ],
            [
                'battle_uid' => 'DEMO-RUN-10000',
                'creator_email' => 'demo_deepak@fsaludoclub.test',
                'joiner_email' => 'demo_suresh@fsaludoclub.test',
                'entry_fee' => 10000,
                'room_code' => '82910472',
                'started_minutes_ago' => 4,
            ],
            [
                'battle_uid' => 'DEMO-RUN-7500',
                'creator_email' => 'demo_arjun@fsaludoclub.test',
                'joiner_email' => 'demo_vikram@fsaludoclub.test',
                'entry_fee' => 7500,
                'room_code' => '59281039',
                'started_minutes_ago' => 5,
            ],
            [
                'battle_uid' => 'DEMO-RUN-5000',
                'creator_email' => 'demo_amit@fsaludoclub.test',
                'joiner_email' => 'demo_rahul@fsaludoclub.test',
                'entry_fee' => 5000,
                'room_code' => '93810482',
                'started_minutes_ago' => 6,
            ],
            [
                'battle_uid' => 'DEMO-RUN-3000',
                'creator_email' => 'demo_neha@fsaludoclub.test',
                'joiner_email' => 'demo_priya@fsaludoclub.test',
                'entry_fee' => 3000,
                'room_code' => '38192048',
                'started_minutes_ago' => 7,
            ],
            [
                'battle_uid' => 'DEMO-RUN-2500',
                'creator_email' => 'demo_rahul@fsaludoclub.test',
                'joiner_email' => 'demo_rohit@fsaludoclub.test',
                'entry_fee' => 2500,
                'room_code' => '04829104',
                'started_minutes_ago' => 8,
            ],
            [
                'battle_uid' => 'DEMO-RUN-1500',
                'creator_email' => 'demo_pooja@fsaludoclub.test',
                'joiner_email' => 'demo_amit@fsaludoclub.test',
                'entry_fee' => 1500,
                'room_code' => '72910482',
                'started_minutes_ago' => 9,
            ],
            [
                'battle_uid' => 'DEMO-RUN-1000',
                'creator_email' => 'demo_manish@fsaludoclub.test',
                'joiner_email' => 'demo_pooja@fsaludoclub.test',
                'entry_fee' => 1000,
                'room_code' => '71029485',
                'started_minutes_ago' => 10,
            ],
            [
                'battle_uid' => 'DEMO-RUN-500',
                'creator_email' => 'demo_anjali@fsaludoclub.test',
                'joiner_email' => 'demo_manish@fsaludoclub.test',
                'entry_fee' => 500,
                'room_code' => '61928401',
                'started_minutes_ago' => 11,
            ],
            [
                'battle_uid' => 'DEMO-RUN-250',
                'creator_email' => 'demo_rohit@fsaludoclub.test',
                'joiner_email' => 'demo_neha@fsaludoclub.test',
                'entry_fee' => 250,
                'room_code' => '83910284',
                'started_minutes_ago' => 12,
            ],
            [
                'battle_uid' => 'DEMO-RUN-100',
                'creator_email' => 'demo_arjun@fsaludoclub.test',
                'joiner_email' => 'demo_priya@fsaludoclub.test',
                'entry_fee' => 100,
                'room_code' => '49201948',
                'started_minutes_ago' => 13,
            ],
        ];

        foreach ($runningBattlesData as $b) {
            $creator = $users[$b['creator_email']];
            $joiner = $users[$b['joiner_email']];
            $prize = LudoBattle::calculatePrize($b['entry_fee']);
            $startedAt = now()->subMinutes($b['started_minutes_ago']);

            LudoBattle::updateOrCreate(
                ['battle_uid' => $b['battle_uid']],
                [
                    'creator_id' => $creator->id,
                    'joiner_id' => $joiner->id,
                    'entry_fee' => $b['entry_fee'],
                    'prize' => $prize,
                    'status' => 'running',
                    'review_status' => 'auto_approved',
                    'refund_status' => 'none',
                    'claimed_result' => 'pending',
                    'final_result' => 'pending',
                    'creator_claimed_result' => 'pending',
                    'joiner_claimed_result' => 'pending',
                    'room_code' => $b['room_code'],
                    'started_at' => $startedAt,
                    'submission_deadline_at' => null, // Demo battles never expire
                    'room_code_expires_at' => null,
                ]
            );
        }

        // 4. Seed Demo KYC Submissions for compliance verification queue
        $arjun = $users['demo_arjun@fsaludoclub.test'] ?? null;
        if ($arjun) {
            $arjun->update(['kyc_status' => 'pending']);
            LudoKycSubmission::updateOrCreate(
                ['user_id' => $arjun->id],
                [
                    'document_type' => 'aadhaar',
                    'document_number' => '4821 9023 1184',
                    'front_image_path' => 'private/kyc_documents/sample_pending_front.png',
                    'back_image_path' => 'private/kyc_documents/sample_pending_back.png',
                    'status' => 'pending',
                ]
            );
        }

        $rohit = $users['demo_rohit@fsaludoclub.test'] ?? null;
        if ($rohit) {
            $rohit->update(['kyc_status' => 'pending']);
            LudoKycSubmission::updateOrCreate(
                ['user_id' => $rohit->id],
                [
                    'document_type' => 'pan',
                    'document_number' => 'ABCPV1234D',
                    'front_image_path' => 'private/kyc_documents/sample_pending_front.png',
                    'back_image_path' => null,
                    'status' => 'pending',
                ]
            );
        }

        $priya = $users['demo_priya@fsaludoclub.test'] ?? null;
        if ($priya) {
            $priya->update(['kyc_status' => 'rejected']);
            LudoKycSubmission::updateOrCreate(
                ['user_id' => $priya->id],
                [
                    'document_type' => 'aadhaar',
                    'document_number' => '9876 5432 1098',
                    'front_image_path' => 'private/kyc_documents/sample_rejected_front.png',
                    'back_image_path' => null,
                    'status' => 'rejected',
                    'admin_reason' => 'Aadhaar image was blurry and unreadable. Please upload a clear photo.',
                    'reviewed_by_admin_id' => 1,
                    'reviewed_at' => now()->subDay(),
                ]
            );
        }

        $this->command?->info('RunningBattleDemoSeeder executed successfully. 12 demo running battles seeded.');
    }
}
