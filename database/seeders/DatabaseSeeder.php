<?php

namespace Database\Seeders;

use App\Models\LudoBattle;
use App\Models\LudoKycSubmission;
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
        // 1. Initial Superadmin Accounts
        $superadmin = User::firstOrCreate(
            ['email' => 'admin@fsaludoclub.com'],
            [
                'name' => 'Fsaludoclub Superadmin',
                'password' => Hash::make('Admin@12345'),
                'role' => 'superadmin',
                'status' => 'active',
                'coins' => 100000,
                'referral_code' => 'ADMIN_FSALUDO',
                'kyc_status' => 'verified',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@royalludo.com'],
            [
                'name' => 'Fsaludoclub Admin',
                'password' => Hash::make('Admin@12345'),
                'role' => 'admin',
                'status' => 'active',
                'coins' => 50000,
                'referral_code' => 'ADMIN_ROYAL',
                'kyc_status' => 'verified',
                'is_verified' => true,
                'email_verified_at' => now(),
            ]
        );

        // Seed verified KYC submissions for initial admins
        LudoKycSubmission::firstOrCreate(
            ['user_id' => $superadmin->id],
            [
                'document_type' => 'pan',
                'document_number' => 'AAAPF1029K',
                'front_image_path' => 'private/kyc_documents/sample_verified_front.png',
                'back_image_path' => 'private/kyc_documents/sample_verified_back.png',
                'status' => 'approved',
                'admin_reason' => 'Platform superadmin verified with PAN card.',
                'reviewed_by_admin_id' => $superadmin->id,
                'reviewed_at' => now(),
            ]
        );

        LudoKycSubmission::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'document_type' => 'aadhaar',
                'document_number' => '6542 9871 3321',
                'front_image_path' => 'private/kyc_documents/sample_verified_front.png',
                'back_image_path' => 'private/kyc_documents/sample_verified_back.png',
                'status' => 'approved',
                'admin_reason' => 'Platform staff admin verified with Aadhaar card.',
                'reviewed_by_admin_id' => $superadmin->id,
                'reviewed_at' => now(),
            ]
        );

        // 2. Platform CMS & UPI Settings
        $rulesHindi = <<<TEXT
1. Autoexit & 100% Loss: यदि आप जान भुजकर Autoexit करते है तो भी आपको 100% Loss कर दिया जायेगा ! यदि दोनों प्लेयर में किसी की काटी खुली नहीं तो उसे हम कैंसिल कर सकते है !
2. Token Status & Loss %: यदि एक टोकन बाहर है और घर के पास है तो 30% Loss दिया जायेगा लेकिन यदि गेम खेला गया है और 2 काटी बहार आयी हो तो गेम को लेफ्ट करने वाले को 100% Loss कर दिया जायेगा !
3. Autoexit Cancellation vs Admin Decision: Autoexit में यदि 1 टोकन बाहर है तो गेम कैंसिल किया जा सकता है लेकिन यदि आपने गेम जान भुजकर में छोड़ा होगा तो आपको Loss ही दिया जायेगा इसमें अंतिम निर्णय Admin का होगा !
4. Mandatory Video Proof: यदि आपको लगता है की Opponent ने जानभूझकर गेम को Autoexit में छोड़ा है लेकिन Admin ने कैंसिल कर दिया है तो आपसे वीडियो प्रूफ माँगा जायेगा इसलिए हर गेम को रिकॉर्ड करना जरुरी है ! यदि आप वीडियो प्रूफ नहीं देते है तो गेम रिजल्ट एडमिन के अनुसार ही अपडेट किया जायेगा चाहे आप विन हो या गेम कैंसिल हो !
5. 15-Minute Result Submission Deadline: Game समाप्त होने के 15 मिनट के अंदर रिजल्ट डालना आवश्यक है अन्यथा Opponent के रिजल्ट के आधार पर गेम अपडेट कर दिया जायेगा चाहे आप जीते या हारे और इसमें पूरी ज़िम्मेदारी आपकी होगी इसमें बाद में कोई बदलाव नहीं किया जा सकता है !
6. Fake Screenshot Penalty & Live Chat: Win होने के बाद आप गलत स्क्रीनशॉट डालते है तो गेम को सीधा Cancel कर दिया जायेगा इसलिए यदि आप स्क्रीनशॉट लेना भूल गए है तो पहले Live Chat में एडमिन को संपर्क करे उसके बाद ही उनके बताये अनुसार रिजल्ट पोस्ट करे !
7. Zero Tokens Open (Both Players) Safe Cancel: दोनों प्लेयर की टोकन (काटी) घर से बाहर न आयी हो तो लेफ्ट होकर गेम कैंसिल किया जा सकता है ! [कैंसिल प्रूफ करने के लिए वीडियो आवश्यक होगा]
8. No Gameplay After Cancel: 'कैंसिल' रिजल्ट डालने के बाद गेम प्ले करके जीत जाते है तो उसमे हमारी कोई ज़िम्मेदारी नहीं होगी अतः गेम कैंसिल करने के बाद स्टार्ट न करे अन्यथा वो कैंसिल ही माना जायेगा।
9. Irreversible Submission: एक बार रिजल्ट डालने के बाद बदला नहीं जा सकता है इसलिए सोच समझकर रिजल्ट पोस्ट करे गलत रिजल्ट डालने पर पेनल्टी भी लगायी जाएगी चाहे आपने वो गलती से डाला हो या जान भुजकर।
10. Fresh ID Policy & Dice Proof: Fresh आईडी गेम के मामले में केवल पासा के उपयोग के प्रमाण पर रद्द कर दिया जाएगा गोटी के खुले होने के बावजूद नहीं ? गेम का रिकॉर्ड होना जरूरी है अगर कोई अपनी आईडी का नाम नो फ्रेश कर रखा तो नो फ्रेश आईडी ही टेक करे जिनका इंटरेस्ट हो वही टेक करे टेबल को ( इसका अंतिम रिजल्ट एडमिन देगा )।
TEXT;

        $rulesEnglish = <<<TEXT
1. Auto-Exit & 100% Loss: If you intentionally Auto-exit from the match, you will be penalized with a 100% Loss. If neither player's token has opened yet (all tokens inside base), the game can be safely cancelled.
2. Token Status & Loss Percentage: If only 1 token has exited and remains near the home yard, a 30% loss penalty applies. However, if active gameplay has occurred and 2 or more tokens are out on the board, leaving the match results in an immediate 100% Loss!
3. Auto-Exit Cancellation vs Admin Discretion: In Auto-exit scenarios with only 1 token out, the match may be cancelled; however, if the match was deliberately abandoned, a Loss will be awarded. The Admin holds the ultimate decision.
4. Mandatory Video Recording Proof: If you believe your opponent deliberately triggered an Auto-exit but Admin has cancelled the match, video evidence is mandatory. Always screen record your games! Without video proof, the match result will be decided solely at the Admin's discretion.
5. 15-Minute Submission Deadline: You must submit your match result within 15 minutes of game completion. Otherwise, the match will be updated based solely on your opponent's submitted result, at your own liability. No modifications can be made thereafter!
6. Wrong Screenshot Penalty & Live Support: Uploading an incorrect or fabricated winning screenshot will result in immediate match cancellation and penalties. If you forgot to capture a screenshot, contact Admin via Live Chat before posting any result.
7. Safe Cancellation (Zero Tokens Open): If neither player's tokens (gotis) have exited the home yard, you may leave the match and claim cancellation. [Video proof is mandatory to substantiate cancellation].
8. No Gameplay After Cancellation: If you submit a 'Cancel' result and subsequently resume playing and win, the platform bears zero liability. Never resume play after requesting cancellation; the match remains Cancelled.
9. Irreversible Result Submissions: Once submitted, match results cannot be amended under any circumstances. Review carefully before posting; submitting fraudulent results incurs severe financial and account penalties.
10. Fresh ID Policy & Dice Roll Proof: In Fresh ID matches, cancellation will only be granted upon video proof of dice usage, not solely whether tokens are open. Screen recording is mandatory. If a table is designated 'No Fresh ID', only non-fresh accounts may join. The Admin provides the final verdict.
TEXT;

        $defaultSettings = [
            'announcement_hindi' => '⭐ 🎉 📢 Fsaludoclub परिवार में आप सभी प्लेयरों का स्वागत है! किसी भी प्लेयर के साथ ना कभी गलत होगा ना ही कभी गलत होने देगा 🙏🙏',
            'support_hours' => 'Mon-Sun 10:00 AM - 10:00 PM IST',
            'support_telegram' => 'https://t.me/fsaludoclub_official',
            'support_whatsapp' => '7303859007',
            'support_instagram' => 'https://www.instagram.com/fsa.ludo.club',
            'minimum_battle_amount' => '50',
            'commission_percentage' => '5',
            'admin_commission_percent' => '5',
            'virtual_points_mode' => 'true',
            'upi_deposits_enabled' => 'on',
            'upi_id' => 'fsaludoclub@upi',
            'upi_merchant_name' => 'Fsaludoclub Gaming',
            'min_deposit_amount' => '50',
            'max_deposit_amount' => '50000',
            'rules_hi' => $rulesHindi,
            'rules_en' => $rulesEnglish,
            'battle_commission_mode' => 'slabs',
            'battle_commission_slabs' => json_encode([
                ['min' => 10, 'max' => 249, 'type' => 'percent_of_battle', 'basis' => 'battle', 'value' => 10, 'label' => '10% on battle amount'],
                ['min' => 250, 'max' => 500, 'type' => 'fixed', 'value' => 25, 'label' => 'Flat 25 coins'],
                ['min' => 501, 'max' => 50000, 'type' => 'percent_of_battle', 'basis' => 'battle', 'value' => 5, 'label' => '5% on battle amount'],
            ], JSON_PRETTY_PRINT),
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

        // 5. Seed Exactly 8 Authentic Indian Players & 7 Running Battles
        $this->call(RunningBattleDemoSeeder::class);
    }
}
