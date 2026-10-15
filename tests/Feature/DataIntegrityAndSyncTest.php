<?php

namespace Tests\Feature;

use App\Models\LudoAdminAuditLog;
use App\Models\LudoBattle;
use App\Models\LudoDispute;
use App\Models\LudoKycSubmission;
use App\Models\LudoLedgerTransaction;
use App\Models\LudoReferral;
use App\Models\LudoSetting;
use App\Models\LudoSupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DataIntegrityAndSyncTest extends TestCase
{
    /**
     * Test that user registration records welcome bonus in ludo_ledger_transactions.
     */
    public function test_registration_records_welcome_bonus_in_ledger_transactions()
    {
        $unique = 't' . time() . '_' . Str::random(4);
        $email = "user_{$unique}@royalludo.test";
        $phone = '98' . rand(10000000, 99999999);

        $response = $this->post('/register', [
            'name' => 'Ledger Tester',
            'email' => $email,
            'phone' => $phone,
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('message');

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertEquals(100, $user->coins);

        // Verify ledger entry
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $user->id,
            'points_delta' => 100,
            'balance_after' => 100,
            'type' => 'deposit',
            'status' => 'successful',
        ]);
    }

    /**
     * Test that registration with referral credits referrer and creates ledger transaction.
     */
    public function test_registration_with_referral_credits_referrer_and_creates_ledger_transaction()
    {
        $referrerCode = 'REF' . strtoupper(Str::random(3));
        $referrer = User::factory()->create([
            'coins' => 200,
            'referral_code' => $referrerCode,
        ]);

        $unique = 't' . time() . '_' . Str::random(4);
        $email = "referred_{$unique}@royalludo.test";
        $phone = '97' . rand(10000000, 99999999);

        $response = $this->post('/register', [
            'name' => 'Referred Player',
            'email' => $email,
            'phone' => $phone,
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'referral_code' => $referrerCode,
        ]);

        $response->assertRedirect('/');

        // Referrer should be credited 50 points
        $this->assertEquals(250, $referrer->fresh()->coins);

        // Check referrer ledger entry
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $referrer->id,
            'points_delta' => 50,
            'balance_after' => 250,
            'type' => 'referral_bonus',
            'status' => 'successful',
        ]);

        // Check referral record
        $this->assertDatabaseHas('ludo_referrals', [
            'referrer_id' => $referrer->id,
            'reward_points' => 50,
        ]);
    }

    /**
     * Test that visiting referral page auto-generates a unique referral code if missing.
     */
    public function test_referral_page_auto_generates_code_if_user_lacks_one()
    {
        $user = User::factory()->create([
            'referral_code' => null,
            'coins' => 100,
        ]);

        $response = $this->actingAs($user)->get('/refer');
        $response->assertStatus(200);

        $freshUser = $user->fresh();
        $this->assertNotNull($freshUser->referral_code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $freshUser->referral_code);
        $response->assertSee($freshUser->referral_code);
    }

    /**
     * Test updating admin settings persists to database and reflects in player views.
     */
    public function test_admin_settings_update_persists_and_reflects_in_player_views()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $uniqueMsg = 'Special Festival Rules Announcement ' . Str::random(6);
        $customPhone = '+91 9988776655';

        // 1. Update Platform Settings
        $responseSettings = $this->actingAs($admin)->post('/admin/ludo/settings', [
            'platform_name' => 'Fsaludoclub',
            'submission_window' => 20,
            'referral_commission' => '5%',
            'maintenance_mode' => 'off',
            'support_email' => 'support@fsaludoclub.test',
            'support_whatsapp' => $customPhone,
            'support_telegram' => '@fsaludoclub_custom_support',
            'support_hours' => '10:00 AM - 11:00 PM IST',
            'minimum_battle_amount' => 75,
            'kyc_mandatory' => 'off',
        ]);
        $responseSettings->assertSessionHas('success');

        // 2. Update Content & Rules
        $responseContent = $this->actingAs($admin)->post('/admin/ludo/content', [
            'announcement_hi' => $uniqueMsg,
            'announcement_en' => 'English Welcome Message',
            'rules_en' => 'Official Player Rule: Complete match within 15 minutes.',
            'rules_hi' => 'नियम: मैच 15 मिनट के अंदर पूरा करें।',
        ]);
        $responseContent->assertSessionHas('success');

        // Verify database persistence
        $this->assertEquals($uniqueMsg, LudoSetting::get('announcement_hi'));
        $this->assertEquals($customPhone, LudoSetting::get('support_whatsapp'));
        $this->assertEquals(75, (int) LudoSetting::get('minimum_battle_amount'));

        // Check player /support page reflects dynamic contact
        $player = User::factory()->create();
        $supportResponse = $this->actingAs($player)->get('/support');
        $supportResponse->assertStatus(200);
        $supportResponse->assertSee('919988776655');
        $supportResponse->assertSee('10:00 AM - 11:00 PM IST');
        $supportResponse->assertSee('@fsaludoclub_custom_support');

        // Check player /play page reflects dynamic announcement and minimum points
        $playResponse = $this->actingAs($player)->get('/play');
        $playResponse->assertStatus(200);
        $playResponse->assertSee($uniqueMsg);
        $playResponse->assertSee('min 75');

        // Check player /rules page reflects custom rule
        $rulesResponse = $this->actingAs($player)->get('/rules');
        $rulesResponse->assertStatus(200);
        $rulesResponse->assertSee('Official Player Rule: Complete match within 15 minutes.');
    }

    /**
     * Test mandatory KYC setting restricts battle creation and joining.
     */
    public function test_mandatory_kyc_setting_enforces_restriction_on_battle_creation_and_joining()
    {
        // Turn on mandatory KYC
        LudoSetting::set('kyc_mandatory', 'on');

        $unverifiedUser = User::factory()->create([
            'kyc_status' => 'unverified',
            'coins' => 500,
        ]);

        // Attempt battle creation -> should be blocked and redirect to /kyc
        $createResponse = $this->actingAs($unverifiedUser)->post('/battles/create', [
            'amount' => 100,
        ]);
        $createResponse->assertRedirect('/kyc');
        $createResponse->assertSessionHas('error');

        // Attempt joining an existing battle -> should be blocked
        $creator = User::factory()->create(['coins' => 500, 'kyc_status' => 'verified']);
        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-KYC-' . Str::random(6),
            'creator_id' => $creator->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'open',
        ]);

        $joinResponse = $this->actingAs($unverifiedUser)->post('/battles/join/' . $battle->id);
        $joinResponse->assertRedirect('/kyc');
        $joinResponse->assertSessionHas('error');

        // Turn off mandatory KYC
        LudoSetting::set('kyc_mandatory', 'off');

        // Now battle creation should succeed
        $succeedResponse = $this->actingAs($unverifiedUser)->post('/battles/create', [
            'amount' => 100,
        ]);
        $succeedResponse->assertSessionHas('success');
    }

    /**
     * Test password update enforces minimum eight characters.
     */
    public function test_password_update_enforces_minimum_eight_characters()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        // Attempt updating to a 6-character password
        $failResponse = $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'oldpassword123',
            'new_password' => '123456',
            'new_password_confirmation' => '123456',
        ]);
        $failResponse->assertSessionHasErrors('new_password');

        // Valid update with 8+ characters
        $passResponse = $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newSecretPass99',
            'new_password_confirmation' => 'newSecretPass99',
        ]);
        $passResponse->assertSessionHas('success');
        $this->assertTrue(Hash::check('newSecretPass99', $user->fresh()->password));
    }

    /**
     * Test settings alias fallback guarantees compatibility.
     */
    public function test_settings_alias_fallback_guarantees_compatibility()
    {
        LudoSetting::set('announcement_hi', 'Namaste Ludo Champions!');
        $this->assertEquals('Namaste Ludo Champions!', LudoSetting::get('announcement_hindi'));

        LudoSetting::set('announcement_hindi', 'Updated Hindi Notice');
        $this->assertEquals('Updated Hindi Notice', LudoSetting::get('announcement_hi'));

        LudoSetting::set('referral_commission', '8.5%');
        $this->assertEquals('8.5%', LudoSetting::get('commission_percentage'));
    }

    /**
     * Test admin support tickets desk loads and filters real database tickets.
     */
    public function test_admin_tickets_desk_loads_and_filters()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $player = User::factory()->create(['role' => 'normal']);

        $ticket = LudoSupportTicket::create([
            'ticket_uid' => 'TCK-' . strtoupper(Str::random(8)),
            'user_id' => $player->id,
            'subject' => 'Points credit issue inquiry',
            'category' => 'deposit_wallet',
            'priority' => 'urgent',
            'description' => 'I added virtual points but need verification.',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->get('/admin/ludo/tickets');
        $response->assertStatus(200);
        $response->assertSee($ticket->ticket_uid);
        $response->assertSee('Points credit issue inquiry');
    }

    /**
     * Test admin KYC desk loads, allows review and writes audit log.
     */
    public function test_admin_kyc_desk_loads_and_allows_review_with_audit_log()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $player = User::factory()->create(['role' => 'normal', 'kyc_status' => 'pending']);

        $kyc = LudoKycSubmission::create([
            'user_id' => $player->id,
            'document_type' => 'aadhaar',
            'document_number' => '123456789012',
            'front_image_path' => 'private/kyc/front.jpg',
            'status' => 'pending',
        ]);

        // Access KYC desk
        $indexResponse = $this->actingAs($admin)->get('/admin/ludo/kyc');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('123456789012');

        // Review KYC (Approve)
        $reviewResponse = $this->actingAs($admin)->post("/admin/kyc/{$kyc->id}/review", [
            'status' => 'approved',
            'admin_reason' => 'Clear government ID provided and verified.',
        ]);
        $reviewResponse->assertSessionHas('success');

        $this->assertEquals('verified', $player->fresh()->kyc_status);
        $this->assertTrue((bool)$player->fresh()->is_verified);

        // Verify audit log
        $this->assertDatabaseHas('ludo_admin_audit_logs', [
            'action_type' => 'kyc_reviewed',
            'target_type' => 'LudoKycSubmission',
            'target_id' => $kyc->id,
        ]);
    }

    /**
     * Test admin user dossier renders KYC submissions, disputes, tickets, and ledger.
     */
    public function test_admin_user_dossier_renders_all_modules()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $player = User::factory()->create(['role' => 'normal', 'kyc_status' => 'verified']);

        $kyc = LudoKycSubmission::create([
            'user_id' => $player->id,
            'document_type' => 'pan',
            'document_number' => 'ABCDE1234F',
            'front_image_path' => 'private/kyc/pan_front.jpg',
            'status' => 'approved',
        ]);

        $ticket = LudoSupportTicket::create([
            'ticket_uid' => 'TCK-' . strtoupper(Str::random(8)),
            'user_id' => $player->id,
            'subject' => 'Tournament bracket inquiry',
            'category' => 'account_profile',
            'priority' => 'normal',
            'description' => 'When does the next tournament start?',
            'status' => 'open',
        ]);

        LudoLedgerTransaction::recordAdjustment(
            $player,
            500,
            'deposit',
            'Test deposit reload',
            'successful'
        );

        $response = $this->actingAs($admin)->get("/admin/ludo/users/{$player->id}");
        $response->assertStatus(200);
        $response->assertSee('ABCDE1234F');
        $response->assertSee($ticket->ticket_uid);
        $response->assertSee('Test deposit reload');
    }

    /**
     * 11. Global Fsaludoclub branding is present across all player and admin pages and legacy brand names are removed.
     */
    public function test_fsaludoclub_branding_applied_globally_and_legacy_brands_removed()
    {
        $brand = config('ludo.brand_name', 'Malik Ludo');

        // Guest authentication pages
        $loginRes = $this->get('/showlogin');
        $loginRes->assertStatus(200);
        $loginRes->assertSee($brand);
        $loginRes->assertDontSee('Royal Ludo');

        $registerRes = $this->get('/showregister');
        $registerRes->assertStatus(200);
        $registerRes->assertSee($brand);
        $registerRes->assertDontSee('Royal Ludo');

        // Authenticated player pages
        $player = User::factory()->create();
        $playerPages = ['/', '/play', '/tournaments', '/profile', '/wallet', '/refer', '/history', '/support', '/rules', '/kyc', '/notifications'];

        foreach ($playerPages as $page) {
            $res = $this->actingAs($player)->get($page);
            $res->assertStatus(200);
            $res->assertSee($brand);
            $res->assertDontSee('Royal Ludo');
        }

        // Admin console pages
        $admin = User::factory()->create(['role' => 'admin']);
        $adminPages = ['/admin/ludo/dashboard', '/admin/ludo/users', '/admin/ludo/tournaments', '/admin/ludo/matches', '/admin/ludo/disputes', '/admin/ludo/kyc', '/admin/ludo/tickets', '/admin/ludo/ledger', '/admin/ludo/content', '/admin/ludo/settings'];

        foreach ($adminPages as $adminPage) {
            $res = $this->actingAs($admin)->get($adminPage);
            $res->assertStatus(200);
            $res->assertSee($brand);
            $res->assertDontSee('Royal Ludo');
        }
    }
}
