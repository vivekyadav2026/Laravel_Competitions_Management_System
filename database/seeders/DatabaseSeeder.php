<?php

namespace Database\Seeders;

use App\Models\LudoBattle;
use App\Models\LudoSetting;
use App\Models\LudoTournament;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database for development and initial staging.
     */
    public function run(): void
    {
        // 1. Initial Superadmin Account
        $superadmin = User::firstOrCreate(
            ['email' => 'admin@royalludo.com'],
            [
                'name' => 'Royal Administrator',
                'password' => Hash::make('Admin@12345'),
                'role' => 'superadmin',
                'status' => 'active',
                'coins' => 100000,
                'referral_code' => 'ADMIN_ROYAL',
                'kyc_status' => 'verified',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        // 2. Demo Players
        $player1 = User::firstOrCreate(
            ['email' => 'player1@royalludo.com'],
            [
                'name' => 'Royal Champion',
                'password' => Hash::make('Player@12345'),
                'role' => 'user',
                'status' => 'active',
                'coins' => 2500,
                'referral_code' => 'ROYAL_CHAMP',
                'kyc_status' => 'verified',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        $player2 = User::firstOrCreate(
            ['email' => 'player2@royalludo.com'],
            [
                'name' => 'Dice Master',
                'password' => Hash::make('Player@12345'),
                'role' => 'user',
                'status' => 'active',
                'coins' => 1800,
                'referral_code' => 'DICE_MASTER',
                'kyc_status' => 'verified',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        // 3. Platform CMS Settings
        $defaultSettings = [
            'announcement_hindi' => '⭐ 🎉 📢 रॉयल लूडो परिवार में आप सभी प्लेयरों का स्वागत है! किसी भी प्लेयर के साथ ना कभी गलत होगा ना ही कभी गलत होने देगा 🙏🙏',
            'support_hours' => 'Mon-Sun 10:00 AM - 10:00 PM IST',
            'support_telegram' => 'https://t.me/royalludo_official',
            'support_whatsapp' => '+91 9876543210',
            'minimum_battle_amount' => '50',
            'commission_percentage' => '5',
            'virtual_points_mode' => 'true',
        ];

        foreach ($defaultSettings as $key => $val) {
            LudoSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $val, 'group' => 'general']
            );
        }

        // 4. Initial Tournament
        LudoTournament::firstOrCreate(
            ['tournament_uid' => 'TN_GRAND_CUP'],
            [
                'title' => 'Grand Diwali Championship',
                'description' => 'Official single-elimination tournament bracket with 16 contenders.',
                'entry_fee' => 100,
                'prize_pool' => 1500,
                'max_players' => 16,
                'status' => 'published',
                'starts_at' => now()->addDays(2),
                'created_by_admin_id' => $superadmin->id,
            ]
        );
    }
}
