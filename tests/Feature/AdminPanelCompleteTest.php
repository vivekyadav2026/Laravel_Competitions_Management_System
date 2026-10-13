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
use App\Models\LudoTournament;
use App\Models\LudoTournamentParticipant;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPanelCompleteTest extends TestCase
{
    protected function tearDown(): void
    {
        // Restore default platform settings so other tests have consistent baseline state
        LudoSetting::set('welcome_bonus_points', '100');
        LudoSetting::set('referral_reward_points', '50');
        LudoSetting::set('referral_enabled', 'on');
        LudoSetting::set('maintenance_mode', 'off');

        parent::tearDown();
    }

    /**
     * 1. Admin RBAC: normal players blocked, support_staff allowed on operational desks but blocked from senior governance.
     */
    public function test_admin_rbac_and_role_hierarchy_enforcement()
    {
        $player = User::factory()->create(['role' => 'normal', 'status' => 'active']);
        $support = User::factory()->create(['role' => 'support_staff', 'status' => 'active']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $superadmin = User::factory()->create(['role' => 'superadmin', 'status' => 'active']);

        // Normal player gets 403 on admin dashboard
        $this->actingAs($player)->get('/admin/ludo/dashboard')->assertStatus(403);

        // Support staff can view operational desks (dashboard, tickets, disputes, kyc)
        $this->actingAs($support)->get('/admin/ludo/dashboard')->assertStatus(200);
        $this->actingAs($support)->get('/admin/ludo/tickets')->assertStatus(200);
        $this->actingAs($support)->get('/admin/ludo/disputes')->assertStatus(200);
        $this->actingAs($support)->get('/admin/ludo/kyc')->assertStatus(200);

        // Support staff is blocked (403) from senior admin actions (settings update, ledger adjust, CSV export)
        $this->actingAs($support)->post('/admin/ludo/settings', [
            'platform_name' => 'Fsaludoclub',
        ])->assertStatus(403);

        $this->actingAs($support)->post('/admin/ludo/ledger/adjust', [
            'user_id' => $player->id,
            'amount' => 100,
            'reason' => 'Unauthorized attempt by support staff',
        ])->assertStatus(403);

        $this->actingAs($support)->get('/admin/ludo/reports/export/users')->assertStatus(403);

        // Regular admin cannot promote anyone to superadmin or modify a superadmin's role (403)
        $this->actingAs($admin)->post("/admin/ludo/users/{$player->id}/role", [
            'role' => 'superadmin',
        ])->assertStatus(403);
        $this->assertEquals('normal', $player->fresh()->role);

        $this->actingAs($admin)->post("/admin/ludo/users/{$superadmin->id}/role", [
            'role' => 'normal',
        ])->assertStatus(403);
        $this->assertEquals('superadmin', $superadmin->fresh()->role);

        // Superadmin can promote a player to moderator
        $this->actingAs($superadmin)->post("/admin/ludo/users/{$player->id}/role", [
            'role' => 'moderator',
        ])->assertSessionHas('success');
        $this->assertEquals('moderator', $player->fresh()->role);
    }

    /**
     * 2. Dashboard displays real database metrics and supports date-range filters.
     */
    public function test_admin_dashboard_metrics_and_date_filtering()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $resAll = $this->actingAs($admin)->get('/admin/ludo/dashboard');
        $resAll->assertStatus(200);
        $resAll->assertSee('Operational Overview');
        $resAll->assertSee('Registered Players');
        $resAll->assertSee('KYC Verification');

        $res7d = $this->actingAs($admin)->get('/admin/ludo/dashboard?period=7d');
        $res7d->assertStatus(200);
        $res7d->assertSee('Last 7 Days');

        $resCustom = $this->actingAs($admin)->get('/admin/ludo/dashboard?from=' . now()->subDays(5)->toDateString() . '&to=' . now()->toDateString());
        $resCustom->assertStatus(200);
    }

    /**
     * 3. User Management: search, filter, profile correction, suspension, and self-suspension guard.
     */
    public function test_user_management_profile_correction_and_suspension_guards()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $target = User::factory()->create([
            'name' => 'Original Player Name',
            'phone' => '91' . rand(10000000, 99999999),
            'role' => 'normal',
            'status' => 'active',
        ]);

        // Search user in index
        $this->actingAs($admin)
            ->get('/admin/ludo/users?search=' . urlencode($target->phone))
            ->assertStatus(200)
            ->assertSee('Original Player Name');

        // Permitted profile correction
        $newPhone = '92' . rand(10000000, 99999999);
        $this->actingAs($admin)->post("/admin/ludo/users/{$target->id}/profile", [
            'name' => 'Corrected Champion Name',
            'phone' => $newPhone,
            'email' => $target->email,
            'reason' => 'User requested typo fix via verified ticket',
        ])->assertSessionHas('success');

        $this->assertEquals('Corrected Champion Name', $target->fresh()->name);
        $this->assertEquals($newPhone, $target->fresh()->phone);

        $this->assertDatabaseHas('ludo_admin_audit_logs', [
            'admin_id' => $admin->id,
            'action_type' => 'user_profile_corrected',
            'target_type' => 'User',
            'target_id' => $target->id,
        ]);

        // Suspend target user
        $this->actingAs($admin)->post("/admin/ludo/users/{$target->id}/status", [
            'action' => 'suspend',
            'reason' => 'Collusion investigation',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertEquals('suspended', $target->fresh()->status);

        // Reactivate target user
        $this->actingAs($admin)->post("/admin/ludo/users/{$target->id}/status", [
            'action' => 'reactivate',
            'reason' => 'Investigation cleared',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertEquals('active', $target->fresh()->status);

        // Self-suspension is blocked
        $this->actingAs($admin)->post("/admin/ludo/users/{$admin->id}/status", [
            'action' => 'suspend',
            'reason' => 'Accidental self click',
        ])->assertRedirect()->assertSessionHas('error');
        $this->assertEquals('active', $admin->fresh()->status);
    }

    /**
     * 4. Tournament CRUD: edit, close registration, cancel with participant refunds, and complete with winner payout.
     */
    public function test_tournament_edit_cancel_refunds_and_completion_payout()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $player1 = User::factory()->create(['coins' => 500, 'status' => 'active']);
        $player2 = User::factory()->create(['coins' => 500, 'status' => 'active']);

        $tournament = LudoTournament::create([
            'tournament_uid' => 'TRN-' . strtoupper(Str::random(6)),
            'title' => 'Initial Cup Title',
            'description' => 'Initial description',
            'entry_fee' => 100,
            'prize_pool' => 180,
            'max_players' => 4,
            'status' => 'published',
            'starts_at' => now()->addHours(3),
            'registration_closes_at' => now()->addHours(2),
            'created_by_admin_id' => $admin->id,
        ]);

        LudoTournamentParticipant::create([
            'tournament_id' => $tournament->id,
            'user_id' => $player1->id,
            'seed_number' => 1,
            'status' => 'registered',
            'joined_at' => now(),
        ]);

        LudoTournamentParticipant::create([
            'tournament_id' => $tournament->id,
            'user_id' => $player2->id,
            'seed_number' => 2,
            'status' => 'registered',
            'joined_at' => now(),
        ]);

        // 1) Edit tournament details
        $this->actingAs($admin)->post("/admin/ludo/tournaments/{$tournament->id}/update", [
            'title' => 'Updated Grand Masters Cup',
            'description' => 'Updated official description',
            'entry_fee' => 100,
            'prize_pool' => 190,
            'max_players' => 8,
            'starts_at' => now()->addHours(4)->format('Y-m-d H:i:s'),
        ])->assertSessionHas('success');

        $this->assertEquals('Updated Grand Masters Cup', $tournament->fresh()->title);
        $this->assertEquals(190, (int) $tournament->fresh()->prize_pool);

        // 2) Close registrations
        $this->actingAs($admin)->post("/admin/ludo/tournaments/{$tournament->id}/close-registration")
            ->assertSessionHas('success');
        $this->assertTrue($tournament->fresh()->registration_closes_at->lessThanOrEqualTo(now()));

        // 3) Cancel tournament -> refunds entry_fee (100 pts) to both participants
        $this->actingAs($admin)->post("/admin/ludo/tournaments/{$tournament->id}/status", [
            'status' => 'cancelled',
            'reason' => 'Server maintenance window conflict',
        ])->assertSessionHas('success');

        $this->assertEquals('cancelled', $tournament->fresh()->status);
        $this->assertEquals(600, (float) $player1->fresh()->coins);
        $this->assertEquals(600, (float) $player2->fresh()->coins);

        // 4) Create second tournament and complete it with winner prize payout
        $tournament2 = LudoTournament::create([
            'tournament_uid' => 'TRN-' . strtoupper(Str::random(6)),
            'title' => 'Championship Finale',
            'entry_fee' => 50,
            'prize_pool' => 200,
            'max_players' => 4,
            'status' => 'running',
            'starts_at' => now()->addHour(),
            'created_by_admin_id' => $admin->id,
        ]);

        LudoTournamentParticipant::create([
            'tournament_id' => $tournament2->id,
            'user_id' => $player1->id,
            'seed_number' => 1,
            'status' => 'registered',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)->post("/admin/ludo/tournaments/{$tournament2->id}/status", [
            'status' => 'completed',
            'winner_user_id' => $player1->id,
            'reason' => 'Verified final bracket match',
        ])->assertSessionHas('success');

        $this->assertEquals('completed', $tournament2->fresh()->status);
        $this->assertEquals(800, (float) $player1->fresh()->coins); // 600 + 200 prize_pool
        $this->assertEquals('winner', LudoTournamentParticipant::where('tournament_id', $tournament2->id)->where('user_id', $player1->id)->value('status'));
    }

    /**
     * 5. Battle & Match Management: direct result settlement and duplicate settlement prevention.
     */
    public function test_battle_direct_result_settlement_and_duplicate_prevention()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $creator = User::factory()->create(['coins' => 300]);
        $joiner = User::factory()->create(['coins' => 300]);

        $battle = LudoBattle::create([
            'battle_uid' => 'BTL-' . strtoupper(Str::random(8)),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'room_code' => '05544332',
            'status' => 'running',
            'started_at' => now()->subMinutes(15),
        ]);

        // View match dossier
        $this->actingAs($admin)->get("/admin/ludo/matches/{$battle->id}")
            ->assertStatus(200)
            ->assertSee($battle->battle_uid);

        // Settle match in favor of joiner
        $this->actingAs($admin)->post("/admin/ludo/matches/{$battle->id}/resolve", [
            'decision' => 'award_joiner',
            'reason' => 'Joiner provided verified victory screenshot.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertEquals('completed', $battle->fresh()->status);
        $this->assertEquals($joiner->id, $battle->fresh()->winner_id);
        $this->assertEquals(490, (float) $joiner->fresh()->coins);

        // Attempting to settle or cancel an already-completed match is rejected (idempotency guard)
        $this->actingAs($admin)->post("/admin/ludo/matches/{$battle->id}/resolve", [
            'decision' => 'award_creator',
            'reason' => 'Duplicate attempt to settle',
        ])->assertRedirect()->assertSessionHas('error');

        $this->actingAs($admin)->post("/admin/ludo/matches/{$battle->id}/cancel", [
            'reason' => 'Late cancel attempt after settlement',
        ])->assertRedirect()->assertSessionHas('error');

        // Coins remain unchanged
        $this->assertEquals(490, (float) $joiner->fresh()->coins);
        $this->assertEquals(300, (float) $creator->fresh()->coins);
    }

    /**
     * 6. Virtual-Points Management: manual credit/debit adjustment and insufficient balance guard.
     */
    public function test_virtual_points_ledger_adjustment_and_overdraft_guard()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $player = User::factory()->create(['coins' => 150, 'status' => 'active']);

        // Credit +250 points
        $this->actingAs($admin)->post('/admin/ludo/ledger/adjust', [
            'user_id' => $player->id,
            'amount' => 250,
            'reason' => 'Event promotional virtual points reward',
        ])->assertSessionHas('success');

        $this->assertEquals(400, (float) $player->fresh()->coins);

        // Attempt to debit -500 points (exceeds 400 balance) -> blocked
        $this->actingAs($admin)->post('/admin/ludo/ledger/adjust', [
            'user_id' => $player->id,
            'amount' => -500,
            'reason' => 'Excessive debit overdraft test',
        ])->assertSessionHas('error');

        $this->assertEquals(400, (float) $player->fresh()->coins);
    }

    /**
     * 7. Dispute Workbench & Duplicate Resolution Guard.
     */
    public function test_dispute_workbench_loads_and_prevents_double_resolution()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $creator = User::factory()->create(['coins' => 200]);
        $joiner = User::factory()->create(['coins' => 200]);

        $battle = LudoBattle::create([
            'battle_uid' => 'BTL-' . strtoupper(Str::random(8)),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'review_status' => 'disputed',
        ]);

        $dispute = LudoDispute::create([
            'dispute_uid' => 'DSP-' . strtoupper(Str::random(8)),
            'battle_id' => $battle->id,
            'opened_by_user_id' => $creator->id,
            'against_user_id' => $joiner->id,
            'reason_category' => 'wrong_result',
            'description' => 'Opponent claimed false win.',
            'status' => 'open',
        ]);

        // Load Dispute Workbench
        $this->actingAs($admin)->get("/admin/ludo/disputes/{$dispute->id}")
            ->assertStatus(200)
            ->assertSee($battle->battle_uid)
            ->assertSee('Dispute Resolution Workbench');

        // Resolve dispute (award creator)
        $this->actingAs($admin)->post("/admin/disputes/{$dispute->id}/resolve", [
            'decision' => 'award_creator',
            'admin_reason' => 'Creator victory screenshot verified by referee.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertEquals('resolved', $dispute->fresh()->status);
        $this->assertEquals(390, (float) $creator->fresh()->coins);

        // Second attempt to resolve same dispute is rejected
        $this->actingAs($admin)->post("/admin/disputes/{$dispute->id}/resolve", [
            'decision' => 'refund_both',
            'admin_reason' => 'Accidental double click on resolve button',
        ])->assertRedirect()->assertSessionHas('error');

        // Balance not double-credited
        $this->assertEquals(390, (float) $creator->fresh()->coins);
    }

    /**
     * 8. Referral Management: settings update synchronized with player signup + status revocation.
     */
    public function test_referral_management_and_signup_synchronization()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        // Update referral reward points to 75 via Admin Referral Settings
        $this->actingAs($admin)->post('/admin/ludo/referrals/settings', [
            'referral_enabled' => 'on',
            'referral_reward_points' => 75,
            'referral_commission' => '3%',
        ])->assertSessionHas('success');

        $referrerCode = 'REF' . strtoupper(Str::random(4));
        $referrer = User::factory()->create([
            'coins' => 100,
            'referral_code' => $referrerCode,
        ]);

        $unique = 'r' . time() . '_' . Str::random(4);
        $this->post('/register', [
            'name' => 'Dynamic Referral Player',
            'email' => "ref_{$unique}@fsaludoclub.test",
            'phone' => '96' . rand(10000000, 99999999),
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'referral_code' => $referrerCode,
        ])->assertRedirect('/showlogin');

        // Referrer received 75 points (configured in admin)
        $this->assertEquals(175, (float) $referrer->fresh()->coins);

        $referral = LudoReferral::where('referrer_id', $referrer->id)->latest('id')->first();
        $this->assertNotNull($referral);
        $this->assertEquals(75, (int) $referral->reward_points);

        // View referrals page in admin
        $this->actingAs($admin)->get('/admin/ludo/referrals')
            ->assertStatus(200)
            ->assertSee($referrerCode);

        // Revoke referral with audit reason
        $this->actingAs($admin)->post("/admin/ludo/referrals/{$referral->id}/status", [
            'status' => 'revoked',
            'reason' => 'Duplicate multi-account signup detected',
        ])->assertSessionHas('success');

        $this->assertEquals('revoked', $referral->fresh()->status);
    }

    /**
     * 9 & 10. Support Ticket Workbench and KYC Review Notification.
     */
    public function test_support_ticket_workbench_and_kyc_notification()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $player = User::factory()->create(['role' => 'normal', 'kyc_status' => 'pending']);

        $ticket = LudoSupportTicket::create([
            'ticket_uid' => 'TCK-' . strtoupper(Str::random(8)),
            'user_id' => $player->id,
            'subject' => 'Battle room code freeze',
            'category' => 'battle_issue',
            'priority' => 'high',
            'description' => 'Room code did not load.',
            'status' => 'open',
        ]);

        // View Ticket Workbench
        $this->actingAs($admin)->get("/admin/ludo/tickets/{$ticket->id}")
            ->assertStatus(200)
            ->assertSee($ticket->ticket_uid)
            ->assertSee('Battle room code freeze');

        // Update ticket status & priority directly
        $this->actingAs($admin)->post("/admin/ludo/tickets/{$ticket->id}/status", [
            'status' => 'in_progress',
            'priority' => 'urgent',
        ])->assertSessionHas('success');

        $this->assertEquals('in_progress', $ticket->fresh()->status);
        $this->assertEquals('urgent', $ticket->fresh()->priority);

        // KYC rejection sends in-app notification to player
        $kyc = LudoKycSubmission::create([
            'user_id' => $player->id,
            'document_type' => 'aadhaar',
            'document_number' => '998877665544',
            'front_image_path' => 'private/kyc/sample.jpg',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->post("/admin/kyc/{$kyc->id}/review", [
            'status' => 'rejected',
            'admin_reason' => 'Document image is blurry, please re-upload clear scan.',
        ])->assertSessionHas('success');

        $this->assertEquals('rejected', $player->fresh()->kyc_status);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $player->id,
            'notifiable_type' => User::class,
        ]);
    }

    /**
     * 11 & 12. Reports, CSV Exports, and Audit Logs Explorer.
     */
    public function test_reports_csv_exports_and_audit_logs_explorer()
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        // Reports page
        $this->actingAs($admin)->get('/admin/ludo/reports')
            ->assertStatus(200)
            ->assertSee('Users CSV')
            ->assertSee('Matches CSV')
            ->assertSee('Ledger CSV');

        // CSV Export for users, matches, tournaments, ledger, disputes, referrals, tickets, audit_logs
        foreach (['users', 'matches', 'tournaments', 'ledger', 'disputes', 'referrals', 'tickets', 'audit_logs'] as $exportType) {
            $csvRes = $this->actingAs($admin)->get("/admin/ludo/reports/export/{$exportType}");
            $csvRes->assertStatus(200);
            $this->assertStringContainsString('text/csv', $csvRes->headers->get('Content-Type'));
        }

        // Audit logs explorer
        $this->actingAs($admin)->get('/admin/ludo/audit-logs')
            ->assertStatus(200)
            ->assertSee('Administrative Audit Log Explorer');
    }
}
