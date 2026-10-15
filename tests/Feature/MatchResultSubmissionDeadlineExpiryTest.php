<?php

namespace Tests\Feature;

use App\Models\LudoAdminAuditLog;
use App\Models\LudoBattle;
use App\Models\LudoLedgerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MatchResultSubmissionDeadlineExpiryTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $playerA;
    protected User $playerB;
    protected User $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@fsaludoclub.com'],
            [
                'name' => 'Fsaludoclub Superadmin',
                'username' => 'fsaludoclub_superadmin',
                'password' => bcrypt('Admin@12345'),
                'role' => 'superadmin',
                'coins' => 10000,
            ]
        );

        $suffix = uniqid();
        $this->playerA = User::create([
            'name' => 'Karan Malhotra',
            'username' => 'karan_' . $suffix,
            'email' => 'karan_' . $suffix . '@fsaludoclub.test',
            'password' => bcrypt('password123'),
            'role' => 'player',
            'coins' => 1000,
        ]);

        $this->playerB = User::create([
            'name' => 'Vikram Seth',
            'username' => 'vikram_' . $suffix,
            'email' => 'vikram_' . $suffix . '@fsaludoclub.test',
            'password' => bcrypt('password123'),
            'role' => 'player',
            'coins' => 1000,
        ]);

        $this->outsider = User::create([
            'name' => 'Suresh Rao',
            'username' => 'suresh_' . $suffix,
            'email' => 'suresh_' . $suffix . '@fsaludoclub.test',
            'password' => bcrypt('password123'),
            'role' => 'player',
            'coins' => 1000,
        ]);
    }

    /**
     * Helper to create a battle with participants and deduct stakes
     */
    protected function createActiveBattle(int $entryFee = 100, int $deadlineMinutes = 15): LudoBattle
    {
        $battle = LudoBattle::create([
            'battle_uid' => 'LB-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'creator_id' => $this->playerA->id,
            'joiner_id' => $this->playerB->id,
            'entry_fee' => $entryFee,
            'prize' => (int) round($entryFee * 2 * 0.95),
            'status' => 'running',
            'room_code' => '98765432',
            'started_at' => now()->subMinutes(16),
            'submission_deadline_at' => now()->addMinutes($deadlineMinutes),
            'review_status' => 'auto_approved',
            'creator_claimed_result' => 'pending',
            'joiner_claimed_result' => 'pending',
        ]);

        // Deduct entry fee to simulate legitimate game start
        $this->playerA->decrement('coins', $entryFee);
        $this->playerB->decrement('coins', $entryFee);

        return $battle;
    }

    public function test_deadline_not_yet_expired_keeps_battle_running()
    {
        // Deadline is 10 minutes into the future
        $battle = $this->createActiveBattle(100, 10);

        $this->assertFalse($battle->isSubmissionDeadlineExpired());
        $this->assertNull($battle->getExpiredReviewStatusText());

        // Run expiry check
        $processed = $battle->checkAndProcessSubmissionExpiry();
        $this->assertFalse($processed);

        $battle->refresh();
        $this->assertEquals('running', $battle->status);
        $this->assertEquals('auto_approved', $battle->review_status);

        // No audit logs created
        $this->assertDatabaseMissing('ludo_admin_audit_logs', [
            'target_type' => 'LudoBattle',
            'target_id' => $battle->id,
            'action_type' => 'deadline_expired',
        ]);
    }

    public function test_one_sided_victory_claim_with_expired_deadline()
    {
        // Player A submitted Victory Claim, Player B did not, deadline passed
        $battle = $this->createActiveBattle(100, -2); // 2 minutes ago
        $battle->creator_claimed_result = 'won';
        $battle->save();

        $this->assertTrue($battle->isSubmissionDeadlineExpired());
        $this->assertEquals(
            'UNDER REVIEW — AWAITING OPPONENT CLAIM / DEADLINE EXPIRED',
            $battle->getExpiredReviewStatusText()
        );

        // Process expiry
        $updated = $battle->checkAndProcessSubmissionExpiry();
        $this->assertTrue($updated);

        $battle->refresh();
        $this->assertEquals('under_review', $battle->status);
        $this->assertEquals('under_review', $battle->review_status);

        // Verify no winner was automatically declared and no points credited yet
        $this->assertNull($battle->winner_id);
        $this->assertEquals(900, $this->playerA->fresh()->coins);
        $this->assertEquals(900, $this->playerB->fresh()->coins);

        // Verify audit trail logged
        $log = LudoAdminAuditLog::where('target_type', 'LudoBattle')
            ->where('target_id', $battle->id)
            ->where('action_type', 'deadline_expired')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('One-sided Victory Claim submitted by', $log->description);
        $this->assertStringContainsString('Karan Malhotra', $log->description);
    }

    public function test_no_claims_submitted_with_expired_deadline()
    {
        // Neither player submitted result, deadline passed
        $battle = $this->createActiveBattle(100, -5); // 5 minutes ago

        $this->assertTrue($battle->isSubmissionDeadlineExpired());
        $this->assertEquals(
            'SUBMISSION EXPIRED — ADMIN REVIEW REQUIRED',
            $battle->getExpiredReviewStatusText()
        );

        $updated = $battle->checkAndProcessSubmissionExpiry();
        $this->assertTrue($updated);

        $battle->refresh();
        $this->assertEquals('under_review', $battle->status);
        $this->assertEquals('under_review', $battle->review_status);

        // Stakes must NOT be forfeited automatically
        $this->assertNull($battle->winner_id);
        $this->assertEquals(900, $this->playerA->fresh()->coins);
        $this->assertEquals(900, $this->playerB->fresh()->coins);

        // Verify audit log
        $log = LudoAdminAuditLog::where('target_type', 'LudoBattle')
            ->where('target_id', $battle->id)
            ->where('action_type', 'deadline_expired')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Neither player submitted a match result before the deadline expired', $log->description);
    }

    public function test_expiry_processing_is_strictly_idempotent()
    {
        $battle = $this->createActiveBattle(100, -1);
        $battle->creator_claimed_result = 'won';
        $battle->save();

        // 1st run
        $firstRun = $battle->checkAndProcessSubmissionExpiry();
        $this->assertTrue($firstRun);

        // 2nd run immediately
        $secondRun = $battle->checkAndProcessSubmissionExpiry();
        $this->assertFalse($secondRun);

        // 3rd batch run via static method
        $batchProcessed = LudoBattle::processExpiredDeadlines();
        $this->assertEquals(0, $batchProcessed);

        // Audit log count must remain exactly 1
        $count = LudoAdminAuditLog::where('target_type', 'LudoBattle')
            ->where('target_id', $battle->id)
            ->where('action_type', 'deadline_expired')
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_player_sync_state_returns_expired_review_status_without_page_reload()
    {
        $battle = $this->createActiveBattle(100, -1);
        $battle->creator_claimed_result = 'won';
        $battle->save();

        // Player B polls syncState
        $response = $this->actingAs($this->playerB)->getJson(
            route('ludo.battles.syncState', ['battle_id' => $battle->id])
        );

        $response->assertStatus(200);
        $response->assertJson([
            'authenticated' => true,
            'battle' => [
                'id' => $battle->id,
                'status' => 'under_review',
                'review_status' => 'under_review',
                'is_deadline_expired' => true,
                'expired_review_status_text' => 'UNDER REVIEW — AWAITING OPPONENT CLAIM / DEADLINE EXPIRED',
                'review_badge_text' => 'UNDER REVIEW — AWAITING OPPONENT CLAIM / DEADLINE EXPIRED',
                'status_badge_text' => 'UNDER REVIEW',
            ],
        ]);
    }

    public function test_admin_live_feed_and_notifications_return_expired_status_badges()
    {
        $battle = $this->createActiveBattle(100, -1);
        // Neither submitted
        $battle->save();

        // Admin live notifications poll
        $response = $this->actingAs($this->admin)->getJson(route('admin.ludo.notifications.live'));
        $response->assertStatus(200);

        // Admin matches live feed poll
        $feedResponse = $this->actingAs($this->admin)->getJson(route('admin.ludo.matches.liveFeed'));
        $feedResponse->assertStatus(200);

        $feedJson = $feedResponse->json('matches');
        $matchInFeed = collect($feedJson)->firstWhere('id', $battle->id);

        $this->assertNotNull($matchInFeed);
        $this->assertEquals('under_review', $matchInFeed['status']);
        $this->assertEquals('SUBMISSION EXPIRED — ADMIN REVIEW REQUIRED', $matchInFeed['review_badge_text']);
        $this->assertTrue($matchInFeed['is_deadline_expired']);
    }

    public function test_player_cannot_bypass_expired_match_to_claim_auto_win()
    {
        $battle = $this->createActiveBattle(100, -2);
        // Battle expired with no previous claims
        $battle->save();

        // Player A tries submitting a late result
        $response = $this->actingAs($this->playerA)->postJson(
            route('ludo.battles.result', $battle->id),
            [
                'result' => 'won',
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'under_review',
        ]);

        $battle->refresh();
        $this->assertEquals('under_review', $battle->status);
        $this->assertEquals('under_review', $battle->review_status);
        $this->assertNull($battle->winner_id); // No auto-win
    }

    public function test_admin_can_resolve_expired_match_and_credit_ledger_safely()
    {
        $battle = $this->createActiveBattle(100, -3);
        $battle->creator_claimed_result = 'won';
        $battle->save();
        $battle->checkAndProcessSubmissionExpiry();

        $initialCoinsA = $this->playerA->fresh()->coins;
        $prize = $battle->prize;

        // Admin settles the match manually in favor of Player A (creator)
        $response = $this->actingAs($this->admin)->post(
            route('admin.ludo.matches.resolve', $battle->id),
            [
                'decision' => 'award_creator',
                'reason' => 'Opponent confirmed offline forfeiture after deadline expired.',
            ]
        );

        $response->assertRedirect();
        $battle->refresh();

        $this->assertEquals('completed', $battle->status);
        $this->assertEquals('verified', $battle->review_status);
        $this->assertEquals($this->playerA->id, $battle->winner_id);

        // Player A receives prize
        $this->assertEquals($initialCoinsA + $prize, $this->playerA->fresh()->coins);

        // Repeated settlement attempt fails / is rejected
        $repeatResponse = $this->actingAs($this->admin)->post(
            route('admin.ludo.matches.resolve', $battle->id),
            [
                'decision' => 'award_creator',
                'reason' => 'Duplicate attempt.',
            ]
        );
        $repeatResponse->assertSessionHas('error');

        // Coins must not be credited twice
        $this->assertEquals($initialCoinsA + $prize, $this->playerA->fresh()->coins);
    }

    public function test_admin_can_cancel_expired_match_and_refund_both_players()
    {
        $battle = $this->createActiveBattle(100, -3);
        $battle->checkAndProcessSubmissionExpiry();

        $initialCoinsA = $this->playerA->fresh()->coins;
        $initialCoinsB = $this->playerB->fresh()->coins;

        // Admin cancels match with refund
        $response = $this->actingAs($this->admin)->post(
            route('admin.ludo.matches.cancel', $battle->id),
            [
                'reason' => 'Neither player submitted results before deadline. Stakes refunded.',
            ]
        );

        $response->assertRedirect();
        $battle->refresh();

        $this->assertEquals('cancelled', $battle->status);
        $this->assertEquals('approved', $battle->refund_status);

        // Both players refunded entry fee
        $this->assertEquals($initialCoinsA + 100, $this->playerA->fresh()->coins);
        $this->assertEquals($initialCoinsB + 100, $this->playerB->fresh()->coins);
    }

    public function test_outsider_cannot_access_match_arena_or_private_data()
    {
        $battle = $this->createActiveBattle(100, -1);

        // Outsider attempts to view match arena
        $response = $this->actingAs($this->outsider)->get(route('ludo.battles.view', $battle->id));
        $response->assertStatus(403);

        // Outsider attempts syncState
        $syncResponse = $this->actingAs($this->outsider)->getJson(
            route('ludo.battles.syncState', ['battle_id' => $battle->id])
        );
        $syncResponse->assertStatus(200);
        // Outsider should NOT see room code
        $this->assertNull($syncResponse->json('battle.room_code'));
    }
}
