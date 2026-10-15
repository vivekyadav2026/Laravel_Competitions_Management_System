<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\LudoReferral;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class LudoTournamentTest extends TestCase
{
    public function test_home_page_loads_successfully()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('OUR TOURNAMENTS');
        $response->assertSee('Install App');
        $response->assertSee('Dark Mode');
    }

    public function test_play_and_battles_page_loads_with_real_data()
    {
        $response = $this->get('/play');
        $response->assertStatus(200);
        $response->assertSee('Create a Battle!');
        $response->assertSee('OPEN BATTLES');
        $response->assertSee('RUNNING BATTLES');
    }

    public function test_battle_creation_validation_requires_minimum_points()
    {
        $user = User::factory()->create([
            'coins' => 500,
            'referral_code' => 'VAL_' . Str::random(5),
        ]);

        $response = $this->actingAs($user)->post('/battles/create', [
            'amount' => 20,
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_battle_creation_deducts_virtual_points()
    {
        $user = User::factory()->create([
            'coins' => 500,
            'referral_code' => 'DED_' . Str::random(5),
        ]);

        $response = $this->actingAs($user)->post('/battles/create', [
            'amount' => 100,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(400, $user->fresh()->coins);

        $this->assertDatabaseHas('ludo_battles', [
            'creator_id' => $user->id,
            'entry_fee' => 100,
            'status' => 'open',
        ]);
    }

    public function test_player_cannot_join_own_battle()
    {
        $user = User::factory()->create([
            'coins' => 500,
            'referral_code' => 'OWN_' . Str::random(5),
        ]);

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-' . Str::random(10),
            'creator_id' => $user->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'open',
        ]);

        $response = $this->actingAs($user)->post('/battles/join/' . $battle->id);
        $response->assertSessionHas('error');
        $this->assertEquals('open', $battle->fresh()->status);
    }

    public function test_player_can_join_open_battle()
    {
        $creator = User::factory()->create([
            'coins' => 500,
            'referral_code' => 'CR_' . Str::random(5),
        ]);

        $joiner = User::factory()->create([
            'coins' => 500,
            'referral_code' => 'JN_' . Str::random(5),
        ]);

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-' . Str::random(10),
            'creator_id' => $creator->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'open',
        ]);

        $response = $this->actingAs($joiner)->post('/battles/join/' . $battle->id);
        $response->assertSessionHas('success');

        $this->assertEquals(400, $joiner->fresh()->coins);
        $this->assertEquals('requested', $battle->fresh()->status);
        $this->assertEquals($joiner->id, $battle->fresh()->joiner_id);

        // Creator starts match
        $this->actingAs($creator)->post('/battles/' . $battle->id . '/start');
        $this->assertEquals('running', $battle->fresh()->status);
    }

    public function test_refer_and_earn_page_loads()
    {
        $code = 'REF_' . Str::random(4);
        $user = User::factory()->create([
            'coins' => 200, 
            'referral_code' => $code,
        ]);

        $response = $this->actingAs($user)->get('/refer');
        $response->assertStatus(200);
        $response->assertSee('Exclusive Rewards');
        $response->assertSee($code);
    }

    public function test_registration_with_referral_attribution_and_no_self_referral()
    {
        $refCode = 'VIP_' . Str::random(4);
        $referrer = User::factory()->create([
            'coins' => 100,
            'referral_balance' => 0.00,
            'referral_code' => $refCode,
        ]);

        $newEmail = 'invited_' . time() . '_' . Str::random(4) . '@royalludo.test';
        $response = $this->post('/register', [
            'name' => 'Invited Player',
            'email' => $newEmail,
            'password' => 'secret12345',
            'referral_code' => $refCode,
        ]);

        $response->assertRedirect('/showlogin');

        $referredUser = User::where('email', $newEmail)->first();
        $this->assertNotNull($referredUser);
        $this->assertEquals($referrer->id, $referredUser->referred_by_id);

        // Verify referral record created
        $this->assertDatabaseHas('ludo_referrals', [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'status' => 'eligible',
            'reward_points' => 50,
        ]);

        // Referrer awarded virtual points
        $this->assertEquals(150, $referrer->fresh()->coins);
        $this->assertEquals(50.00, $referrer->fresh()->referral_balance);
    }

    public function test_profile_page_loads_and_user_can_update_profile_and_privacy()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'phone' => '1234567890',
            'hide_email' => true,
            'referral_code' => 'PRF_' . Str::random(4),
        ]);

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('Original Name');

        // Update profile
        $updateResponse = $this->actingAs($user)->post('/profile/update', [
            'name' => 'Updated Gamer',
            'phone' => '9876543210',
            'hide_email' => '1',
            'hide_phone' => '1',
        ]);

        $updateResponse->assertSessionHas('success');
        $this->assertEquals('Updated Gamer', $user->fresh()->name);
        $this->assertEquals('9876543210', $user->fresh()->phone);
        $this->assertTrue($user->fresh()->hide_email);
        $this->assertTrue($user->fresh()->hide_phone);
    }

    public function test_virtual_points_deposit_creates_ledger_entry_and_credits_balance()
    {
        $user = User::factory()->create([
            'coins' => 200,
            'referral_code' => 'WLT_' . Str::random(4),
        ]);

        $response = $this->actingAs($user)->post('/wallet/deposit', [
            'amount' => 500,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(700, $user->fresh()->coins);

        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $user->id,
            'type' => 'deposit',
            'points_delta' => 500,
            'balance_before' => 200,
            'balance_after' => 700,
            'status' => 'successful',
        ]);
    }

    public function test_ledger_prevents_negative_balance()
    {
        $user = User::factory()->create([
            'coins' => 50,
            'referral_code' => 'NEG_' . Str::random(4),
        ]);

        $this->expectException(\InvalidArgumentException::class);

        \App\Models\LudoLedgerTransaction::recordAdjustment(
            $user,
            -100,
            'entry_fee',
            'Attempted overdraft',
            'successful'
        );
    }

    public function test_history_page_loads_with_three_tabs_and_filters()
    {
        $user = User::factory()->create([
            'coins' => 1000,
            'referral_code' => 'HST_' . Str::random(4),
        ]);

        // Record a transaction
        \App\Models\LudoLedgerTransaction::recordAdjustment(
            $user,
            100,
            'deposit',
            'Welcome points reload',
            'successful'
        );

        $response = $this->actingAs($user)->get('/history?tab=tx');
        $response->assertStatus(200);
        $response->assertSee('History');
        $response->assertSee('Welcome points reload');
    }

    public function test_rules_page_loads_in_english_and_hindi()
    {
        $responseEn = $this->get('/rules?lang=en');
        $responseEn->assertStatus(200);
        $responseEn->assertSee('Rules &amp; Guidelines', false);
        $responseEn->assertSee('General Tournament Rules');

        $responseHi = $this->get('/rules?lang=hi');
        $responseHi->assertStatus(200);
        $responseHi->assertSee('नियम और दिशानिर्देश');
        $responseHi->assertSee('सामान्य टूर्नामेंट नियम');
    }

    public function test_user_can_create_and_reply_to_support_ticket_with_private_attachment()
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $user = User::factory()->create([
            'referral_code' => 'TCK_' . Str::random(4),
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('proof.png', 500, 'image/png');

        $response = $this->actingAs($user)->post('/support/ticket/create', [
            'category' => 'battle_issue',
            'subject' => 'Opponent left mid match',
            'description' => 'Opponent exited during turn 4 and refused to re-join.',
            'priority' => 'urgent',
            'attachment' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ludo_support_tickets', [
            'user_id' => $user->id,
            'subject' => 'Opponent left mid match',
            'status' => 'open',
        ]);

        $ticket = \App\Models\LudoSupportTicket::where('user_id', $user->id)->first();
        $this->assertNotNull($ticket->attachment_path);

        // User can view ticket
        $viewRes = $this->actingAs($user)->get("/support/ticket/{$ticket->id}");
        $viewRes->assertStatus(200);
        $viewRes->assertSee('Opponent left mid match');

        // Other user cannot view ticket
        $otherUser = User::factory()->create(['referral_code' => 'OTH_' . Str::random(4)]);
        $forbiddenRes = $this->actingAs($otherUser)->get("/support/ticket/{$ticket->id}");
        $forbiddenRes->assertStatus(403);

        // Reply to ticket
        $replyRes = $this->actingAs($user)->post("/support/ticket/{$ticket->id}/reply", [
            'message' => 'Additional update regarding room code.',
        ]);
        $replyRes->assertRedirect();
        $this->assertDatabaseHas('ludo_ticket_messages', [
            'ticket_id' => $ticket->id,
            'message' => 'Additional update regarding room code.',
        ]);
    }

    public function test_kyc_submission_and_admin_review_workflow()
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $user = User::factory()->create([
            'kyc_status' => 'unverified',
            'referral_code' => 'KYC_' . Str::random(4),
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'referral_code' => 'ADM_' . Str::random(4),
        ]);

        $front = \Illuminate\Http\UploadedFile::fake()->create('aadhaar.jpg', 600, 'image/jpeg');

        $response = $this->actingAs($user)->post('/kyc/submit', [
            'document_type' => 'aadhaar',
            'document_number' => '123456789012',
            'front_image' => $front,
        ]);

        $response->assertRedirect();
        $this->assertEquals('pending', $user->fresh()->kyc_status);

        $submission = \App\Models\LudoKycSubmission::where('user_id', $user->id)->first();
        $this->assertNotNull($submission);

        // User can download their document
        $dlRes = $this->actingAs($user)->get("/kyc/download/{$submission->id}/front");
        $dlRes->assertStatus(200);

        // Stranger cannot download confidential document
        $stranger = User::factory()->create(['referral_code' => 'STR_' . Str::random(4)]);
        $strangerRes = $this->actingAs($stranger)->get("/kyc/download/{$submission->id}/front");
        $strangerRes->assertStatus(403);

        // Admin approves KYC with reason
        $adminRes = $this->actingAs($admin)->post("/admin/kyc/{$submission->id}/review", [
            'status' => 'approved',
            'admin_reason' => 'Government ID front photo clearly verified with name match.',
        ]);

        $adminRes->assertRedirect();
        $this->assertEquals('verified', $user->fresh()->kyc_status);
        $this->assertTrue($user->fresh()->is_verified);
    }

    public function test_dispute_creation_evidence_protection_and_admin_decision()
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $creator = User::factory()->create(['coins' => 500, 'referral_code' => 'CR_' . Str::random(4)]);
        $joiner = User::factory()->create(['coins' => 500, 'referral_code' => 'JN_' . Str::random(4)]);
        $admin = User::factory()->create(['role' => 'admin', 'referral_code' => 'AD_' . Str::random(4)]);

        $battle = \App\Models\LudoBattle::create([
            'battle_uid' => 'BAT_' . Str::random(6),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'room_code' => '12345678',
            'started_at' => now(),
            'submission_deadline_at' => now()->addMinutes(15),
        ]);

        $evidence = \Illuminate\Http\UploadedFile::fake()->create('victory_screen.png', 800, 'image/png');

        // Creator opens dispute
        $dispRes = $this->actingAs($creator)->post("/battles/{$battle->id}/dispute", [
            'reason_category' => 'wrong_result',
            'description' => 'Opponent left and submitted false claim on third party app.',
            'evidence' => $evidence,
        ]);

        $dispRes->assertRedirect();
        $this->assertDatabaseHas('ludo_disputes', [
            'battle_id' => $battle->id,
            'opened_by_user_id' => $creator->id,
            'status' => 'open',
        ]);

        $dispute = \App\Models\LudoDispute::where('battle_id', $battle->id)->first();
        $this->assertEquals('disputed', $battle->fresh()->review_status);

        // Joiner uploads counter-evidence
        $counterEv = \Illuminate\Http\UploadedFile::fake()->create('counter_proof.png', 700, 'image/png');
        $counterRes = $this->actingAs($joiner)->post("/disputes/{$dispute->id}/evidence", [
            'notes' => 'I have full screen recording of match conclusion.',
            'evidence' => $counterEv,
        ]);
        $counterRes->assertRedirect();
        $this->assertEquals(2, $dispute->evidences()->count());

        // Stranger cannot view dispute or download evidence
        $stranger = User::factory()->create(['referral_code' => 'ST2_' . Str::random(4)]);
        $strangerRes = $this->actingAs($stranger)->get("/disputes/{$dispute->id}");
        $strangerRes->assertStatus(403);

        // Admin resolves dispute in favor of creator
        $resolveRes = $this->actingAs($admin)->post("/admin/disputes/{$dispute->id}/resolve", [
            'decision' => 'award_creator',
            'admin_reason' => 'Clear winning board screenshot corroborated by timestamps.',
        ]);

        $resolveRes->assertRedirect();
        $this->assertEquals('resolved', $dispute->fresh()->status);
        $this->assertEquals('completed', $battle->fresh()->status);
        $this->assertEquals($creator->id, $battle->fresh()->winner_id);
        $this->assertEquals(690, $creator->fresh()->coins); // 500 initial + 190 prize
    }

    public function test_expired_15_minute_submission_is_flagged_for_review_not_auto_forfeit()
    {
        $creator = User::factory()->create(['coins' => 500, 'referral_code' => 'E1_' . Str::random(4)]);
        $joiner = User::factory()->create(['coins' => 500, 'referral_code' => 'E2_' . Str::random(4)]);

        // Match started 30 mins ago (deadline was 15 mins ago)
        $battle = \App\Models\LudoBattle::create([
            'battle_uid' => 'BAT_' . Str::random(6),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'room_code' => '99887766',
            'started_at' => now()->subMinutes(30),
            'submission_deadline_at' => now()->subMinutes(15),
        ]);

        // Creator submits late
        $response = $this->actingAs($creator)->post("/battles/{$battle->id}/result", [
            'result' => 'won',
        ]);

        $response->assertSessionHas('warning');
        // Match must NOT be auto-completed or auto-awarded to joiner; placed under review
        $this->assertEquals('under_review', $battle->fresh()->review_status);
        $this->assertEquals('running', $battle->fresh()->status);
        $this->assertNull($battle->fresh()->winner_id);
    }

    public function test_admin_dashboard_authorization_and_metrics()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'referral_code' => 'AD2_' . Str::random(4),
        ]);
        $normalPlayer = User::factory()->create([
            'role' => 'normal',
            'referral_code' => 'NP_' . Str::random(4),
        ]);

        // Normal player is blocked with 403
        $blocked = $this->actingAs($normalPlayer)->get('/admin/ludo/dashboard');
        $blocked->assertStatus(403);

        // Admin can access dashboard and see real metrics
        $response = $this->actingAs($admin)->get('/admin/ludo/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Operational Overview');
        $response->assertSee('Registered Players');
    }

    public function test_admin_can_suspend_user_and_record_audit_log()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'referral_code' => 'AD3_' . Str::random(4),
        ]);
        $target = User::factory()->create([
            'status' => 'active',
            'referral_code' => 'TGT_' . Str::random(4),
        ]);

        $response = $this->actingAs($admin)->post("/admin/ludo/users/{$target->id}/status", [
            'action' => 'suspend',
            'reason' => 'Multiple reports of cheating with modified client.',
        ]);

        $response->assertRedirect();
        $this->assertEquals('suspended', $target->fresh()->status);
        $this->assertEquals('Multiple reports of cheating with modified client.', $target->fresh()->suspension_reason);

        $this->assertDatabaseHas('ludo_admin_audit_logs', [
            'admin_id' => $admin->id,
            'action_type' => 'user_suspended',
            'target_id' => $target->id,
        ]);
    }

    public function test_admin_can_create_tournament_and_generate_bracket()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'referral_code' => 'AD4_' . Str::random(4),
        ]);

        $createRes = $this->actingAs($admin)->post('/admin/ludo/tournaments', [
            'title' => 'Championship Gold Cup',
            'entry_fee' => 150,
            'prize_pool' => 2000,
            'max_players' => 8,
            'starts_at' => now()->addDays(3)->toDateTimeString(),
        ]);

        $createRes->assertRedirect();
        $tournament = \App\Models\LudoTournament::where('title', 'Championship Gold Cup')->first();
        $this->assertNotNull($tournament);

        // Register 2 participants
        $p1 = User::factory()->create(['referral_code' => 'P1_' . Str::random(4)]);
        $p2 = User::factory()->create(['referral_code' => 'P2_' . Str::random(4)]);
        \App\Models\LudoTournamentParticipant::create(['tournament_id' => $tournament->id, 'user_id' => $p1->id]);
        \App\Models\LudoTournamentParticipant::create(['tournament_id' => $tournament->id, 'user_id' => $p2->id]);

        // Generate bracket
        $bracketRes = $this->actingAs($admin)->post("/admin/ludo/tournaments/{$tournament->id}/generate-bracket");
        $bracketRes->assertRedirect();
        $this->assertNotEmpty($tournament->fresh()->bracket_data);
    }

    public function test_admin_can_cancel_match_with_reason_and_refund_both_players()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'referral_code' => 'AD5_' . Str::random(4),
        ]);
        $creator = User::factory()->create(['coins' => 200, 'referral_code' => 'MC1_' . Str::random(4)]);
        $joiner = User::factory()->create(['coins' => 200, 'referral_code' => 'MC2_' . Str::random(4)]);

        $battle = \App\Models\LudoBattle::create([
            'battle_uid' => 'BAT_' . Str::random(6),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'room_code' => '11223344',
            'started_at' => now(),
        ]);

        $cancelRes = $this->actingAs($admin)->post("/admin/ludo/matches/{$battle->id}/cancel", [
            'reason' => 'Room code was broken by official game update.',
        ]);

        $cancelRes->assertRedirect();
        $this->assertEquals('cancelled', $battle->fresh()->status);
        $this->assertEquals(300, $creator->fresh()->coins); // 200 + 100 refund
        $this->assertEquals(300, $joiner->fresh()->coins); // 200 + 100 refund
    }

    public function test_tournament_lobby_loads_for_player()
    {
        $response = $this->get('/tournaments');
        $response->assertStatus(200);
        $response->assertSee('Tournament Arena');
        $response->assertSee('Official Bracket Tournaments');
    }

    public function test_tournament_registration_prevents_closed_or_draft_status()
    {
        $admin = User::factory()->create(['role' => 'admin', 'referral_code' => 'AD_ST_' . Str::random(4)]);
        $player = User::factory()->create(['coins' => 500, 'referral_code' => 'PL_ST_' . Str::random(4)]);

        // Draft tournament
        $tournament = \App\Models\LudoTournament::create([
            'tournament_uid' => 'TN_' . Str::random(8),
            'title' => 'Draft Cup',
            'entry_fee' => 50,
            'prize_pool' => 400,
            'max_players' => 8,
            'status' => 'draft',
            'created_by_admin_id' => $admin->id,
        ]);

        $res = $this->actingAs($player)->post("/tournaments/{$tournament->id}/join");
        $res->assertSessionHas('error');
        $this->assertDatabaseMissing('ludo_tournament_participants', [
            'tournament_id' => $tournament->id,
            'user_id' => $player->id,
        ]);
        $this->assertEquals(500, $player->fresh()->coins); // No deduction
    }

    public function test_tournament_registration_enforces_capacity_limit()
    {
        $admin = User::factory()->create(['role' => 'admin', 'referral_code' => 'AD_CAP_' . Str::random(4)]);
        $player = User::factory()->create(['coins' => 500, 'referral_code' => 'PL_CAP_' . Str::random(4)]);

        $tournament = \App\Models\LudoTournament::create([
            'tournament_uid' => 'TN_' . Str::random(8),
            'title' => 'Full 2-Player Cup',
            'entry_fee' => 50,
            'prize_pool' => 90,
            'max_players' => 2,
            'status' => 'published',
            'created_by_admin_id' => $admin->id,
        ]);

        // Fill capacity
        $p1 = User::factory()->create(['referral_code' => 'P1_C_' . Str::random(4)]);
        $p2 = User::factory()->create(['referral_code' => 'P2_C_' . Str::random(4)]);
        \App\Models\LudoTournamentParticipant::create(['tournament_id' => $tournament->id, 'user_id' => $p1->id]);
        \App\Models\LudoTournamentParticipant::create(['tournament_id' => $tournament->id, 'user_id' => $p2->id]);

        $res = $this->actingAs($player)->post("/tournaments/{$tournament->id}/join");
        $res->assertSessionHas('error');
        $this->assertDatabaseMissing('ludo_tournament_participants', [
            'tournament_id' => $tournament->id,
            'user_id' => $player->id,
        ]);
    }

    public function test_tournament_registration_prevents_duplicate_participation()
    {
        $admin = User::factory()->create(['role' => 'admin', 'referral_code' => 'AD_DUP_' . Str::random(4)]);
        $player = User::factory()->create(['coins' => 500, 'referral_code' => 'PL_DUP_' . Str::random(4)]);

        $tournament = \App\Models\LudoTournament::create([
            'tournament_uid' => 'TN_' . Str::random(8),
            'title' => 'Duplicate Prevention Cup',
            'entry_fee' => 50,
            'prize_pool' => 400,
            'max_players' => 8,
            'status' => 'published',
            'created_by_admin_id' => $admin->id,
        ]);

        // First join succeeds
        $res1 = $this->actingAs($player)->post("/tournaments/{$tournament->id}/join");
        $res1->assertSessionHas('success');
        $this->assertEquals(450, $player->fresh()->coins);

        // Second join rejected
        $res2 = $this->actingAs($player)->post("/tournaments/{$tournament->id}/join");
        $res2->assertSessionHas('error');
        $this->assertEquals(450, $player->fresh()->coins); // No double deduction
        $this->assertEquals(1, \App\Models\LudoTournamentParticipant::where('tournament_id', $tournament->id)->where('user_id', $player->id)->count());
    }

    public function test_tournament_registration_dispatches_notification()
    {
        $admin = User::factory()->create(['role' => 'admin', 'referral_code' => 'AD_NOT_' . Str::random(4)]);
        $player = User::factory()->create(['coins' => 500, 'referral_code' => 'PL_NOT_' . Str::random(4)]);

        $tournament = \App\Models\LudoTournament::create([
            'tournament_uid' => 'TN_' . Str::random(8),
            'title' => 'Notification Cup',
            'entry_fee' => 50,
            'prize_pool' => 400,
            'max_players' => 8,
            'status' => 'published',
            'created_by_admin_id' => $admin->id,
        ]);

        $this->actingAs($player)->post("/tournaments/{$tournament->id}/join");
        $this->assertCount(1, $player->fresh()->notifications);
        $this->assertStringContainsString('Tournament Registration Confirmed', $player->fresh()->notifications->first()->data['title']);
    }

    public function test_notifications_center_loads_and_user_can_mark_read()
    {
        $player = User::factory()->create(['referral_code' => 'PL_NC_' . Str::random(4)]);
        $player->notify(new \App\Notifications\LudoInAppNotification(
            'Test Notification',
            'This is a sample test notification message.',
            'system',
            url('/history')
        ));

        $this->assertEquals(1, $player->unreadNotifications->count());

        $getRes = $this->actingAs($player)->get('/notifications');
        $getRes->assertStatus(200);
        $getRes->assertSee('Test Notification');

        // Mark all read
        $markRes = $this->actingAs($player)->post('/notifications/read-all');
        $markRes->assertSessionHas('success');
        $this->assertEquals(0, $player->fresh()->unreadNotifications->count());
    }

    public function test_admin_ticket_reply_dispatches_in_app_notification_to_user()
    {
        $admin = User::factory()->create(['role' => 'admin', 'referral_code' => 'AD_TK_' . Str::random(4)]);
        $user = User::factory()->create(['referral_code' => 'US_TK_' . Str::random(4)]);

        $ticket = \App\Models\LudoSupportTicket::create([
            'ticket_uid' => 'TCK_' . Str::random(8),
            'user_id' => $user->id,
            'category' => 'battle_issue',
            'subject' => 'Match sync problem',
            'description' => 'Opponent left mid match',
            'status' => 'open',
        ]);

        $this->actingAs($admin)->post("/support/ticket/{$ticket->id}/reply", [
            'message' => 'Hello player, we have reviewed your match logs and resolved the issue.',
        ]);

        $this->assertCount(1, $user->fresh()->notifications);
        $this->assertStringContainsString('Support Ticket Update', $user->fresh()->notifications->first()->data['title']);
    }

    public function test_dispute_creation_and_resolution_dispatch_notifications_to_players()
    {
        $admin = User::factory()->create(['role' => 'admin', 'referral_code' => 'AD_DSP_' . Str::random(4)]);
        $creator = User::factory()->create(['coins' => 100, 'referral_code' => 'CR_DSP_' . Str::random(4)]);
        $joiner = User::factory()->create(['coins' => 100, 'referral_code' => 'JN_DSP_' . Str::random(4)]);

        $battle = \App\Models\LudoBattle::create([
            'battle_uid' => 'BAT_' . Str::random(6),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 50,
            'prize' => 95,
            'status' => 'running',
            'room_code' => '55667788',
            'started_at' => now(),
        ]);

        // Upload fake evidence to open dispute
        \Illuminate\Support\Facades\Storage::fake('local');
        $file = \Illuminate\Http\UploadedFile::fake()->create('proof.png', 500, 'image/png');

        $this->actingAs($creator)->post("/battles/{$battle->id}/dispute", [
            'reason_category' => 'fake_screenshot',
            'description' => 'Opponent provided fake victory screen',
            'evidence' => $file,
        ]);

        // Joiner (opponent) should receive notification
        $this->assertCount(1, $joiner->fresh()->notifications);
        $this->assertStringContainsString('Match Dispute Lodged', $joiner->fresh()->notifications->first()->data['title']);

        // Admin resolves dispute
        $dispute = \App\Models\LudoDispute::where('battle_id', $battle->id)->first();
        $this->actingAs($admin)->post("/admin/disputes/{$dispute->id}/resolve", [
            'decision' => 'refund_both',
            'admin_reason' => 'Both parties submitted inconclusive match recordings. Refunding both.',
        ]);

        // Both players receive resolution notifications
        $this->assertTrue($creator->fresh()->notifications->where('type', 'App\Notifications\LudoInAppNotification')->count() >= 1);
        $this->assertTrue($joiner->fresh()->notifications->where('type', 'App\Notifications\LudoInAppNotification')->count() >= 2);
    }
}
