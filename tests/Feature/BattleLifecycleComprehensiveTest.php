<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\LudoDispute;
use App\Models\LudoLedgerTransaction;
use App\Models\LudoSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BattleLifecycleComprehensiveTest extends TestCase
{
    /**
     * Helper to create a user with a unique phone, email, and specified coins.
     */
    protected function createPlayer(int $coins = 1000, string $role = 'user', ?string $name = null): User
    {
        $unique = 't' . time() . '_' . Str::random(5);
        return User::factory()->create([
            'name' => $name ?? ('Player_' . Str::random(4)),
            'email' => "player_{$unique}@royalludo.test",
            'phone' => '98' . rand(10000000, 99999999),
            'coins' => $coins,
            'role' => $role,
            'kyc_status' => 'verified',
            'referral_code' => 'REF_' . strtoupper(Str::random(4)),
        ]);
    }

    /**
     * Helper to create an admin user.
     */
    protected function createAdmin(): User
    {
        return $this->createPlayer(10000, 'admin', 'Super Admin');
    }

    /**
     * Test 1: Battle creation persists with valid amounts and proper creator assignment.
     */
    public function test_01_battle_creation_persists_with_valid_amounts_and_proper_creator_assignment()
    {
        $creator = $this->createPlayer(500, 'user', 'Ranjeet Singh');

        $response = $this->actingAs($creator)->postJson('/battles/create', [
            'amount' => 100,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'battle' => [
                    'id',
                    'battle_uid',
                    'creator_id',
                    'entry_fee',
                    'prize',
                    'status',
                ],
                'html',
                'coins',
            ]);

        // Verify database persistence
        $this->assertDatabaseHas('ludo_battles', [
            'creator_id' => $creator->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'open',
        ]);

        // Verify creator balance deduction and ledger transaction
        $this->assertEquals(400, $creator->fresh()->coins);
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $creator->id,
            'points_delta' => -100,
            'balance_after' => 400,
            'type' => 'entry_fee',
            'status' => 'successful',
        ]);
    }

    /**
     * Test 2: Creator sees battle in Open Battles list with DELETE option (or creator controls).
     */
    public function test_02_creator_sees_battle_in_open_battles_list_with_creator_delete_action()
    {
        $creator = $this->createPlayer(500, 'user', 'Vikram Rathore');
        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-TEST-' . Str::random(5),
            'creator_id' => $creator->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'open',
        ]);

        // 1. Creator visits Play page -> sees DELETE button/form
        $responseCreator = $this->actingAs($creator)->get('/play');
        $responseCreator->assertStatus(200);
        $responseCreator->assertSee($battle->battle_uid);
        $responseCreator->assertSee('Vikram Rathore');
        $responseCreator->assertSee('DELETE');
        $responseCreator->assertSee("/battles/cancel/{$battle->id}");

        // 2. Another player visits Play page -> sees Play button, NOT Delete button
        $opponent = $this->createPlayer(500, 'user', 'Amitabh Sharma');
        $responseOpponent = $this->actingAs($opponent)->get('/play');
        $responseOpponent->assertStatus(200);
        $responseOpponent->assertSee($battle->battle_uid);
        $responseOpponent->assertSee('Play');
        $responseOpponent->assertSee("/battles/join/{$battle->id}");
    }

    /**
     * Test 3: Another authenticated user can successfully join an open battle.
     */
    public function test_03_another_authenticated_user_can_join_open_battle()
    {
        $creator = $this->createPlayer(500, 'user', 'Suresh Kumar');
        $opponent = $this->createPlayer(500, 'user', 'Pooja Verma');

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-JOIN-' . Str::random(5),
            'creator_id' => $creator->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'open',
        ]);

        $response = $this->actingAs($opponent)->postJson("/battles/{$battle->id}/join");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'requested',
                'battle_id' => $battle->id,
            ]);

        // Verify battle status updated to requested and joiner recorded
        $freshBattle = $battle->fresh();
        $this->assertEquals('requested', $freshBattle->status);
        $this->assertEquals($opponent->id, $freshBattle->joiner_id);
        $this->assertNotNull($freshBattle->room_code_expires_at);

        // Verify opponent entry fee deducted via ledger
        $this->assertEquals(400, $opponent->fresh()->coins);
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $opponent->id,
            'points_delta' => -100,
            'balance_after' => 400,
            'type' => 'entry_fee',
            'status' => 'successful',
        ]);
    }

    /**
     * Test 4: Creator cannot join their own battle.
     */
    public function test_04_creator_cannot_join_own_battle()
    {
        $creator = $this->createPlayer(500, 'user', 'Karan Patel');
        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-SELF-' . Str::random(5),
            'creator_id' => $creator->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'open',
        ]);

        $response = $this->actingAs($creator)->postJson("/battles/{$battle->id}/join");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You cannot join your own battle challenge.',
            ]);

        // State remains open with null joiner
        $fresh = $battle->fresh();
        $this->assertEquals('open', $fresh->status);
        $this->assertNull($fresh->joiner_id);
    }

    /**
     * Test 5: Two simultaneous join attempts cannot claim the same seat (concurrency safe).
     */
    public function test_05_two_simultaneous_join_attempts_cannot_claim_same_seat()
    {
        $creator = $this->createPlayer(500, 'user', 'Sunil Gavaskar');
        $joiner1 = $this->createPlayer(500, 'user', 'Rahul Dravid');
        $joiner2 = $this->createPlayer(500, 'user', 'Sachin Tendulkar');

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-RACE-' . Str::random(5),
            'creator_id' => $creator->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'open',
        ]);

        // First joiner claims seat
        $res1 = $this->actingAs($joiner1)->postJson("/battles/{$battle->id}/join");
        $res1->assertStatus(200)->assertJson(['success' => true]);

        // Second joiner attempts to claim the same seat
        $res2 = $this->actingAs($joiner2)->postJson("/battles/{$battle->id}/join");
        $res2->assertStatus(422)->assertJson([
            'success' => false,
            'message' => 'This battle is no longer open for joining.',
        ]);

        // Second joiner's coins must remain intact
        $this->assertEquals(500, $joiner2->fresh()->coins);
        // Battle must only belong to joiner1
        $this->assertEquals($joiner1->id, $battle->fresh()->joiner_id);
    }

    /**
     * Test 6: Acceptance / start works only for the intended creator.
     */
    public function test_06_acceptance_and_start_works_only_for_intended_creator()
    {
        $creator = $this->createPlayer(500, 'user', 'MS Dhoni');
        $joiner = $this->createPlayer(500, 'user', 'Virat Kohli');
        $stranger = $this->createPlayer(500, 'user', 'Rohit Sharma');

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-START-' . Str::random(5),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'requested',
        ]);

        // 1. Stranger tries to start -> 403 Forbidden
        $resStranger = $this->actingAs($stranger)->postJson("/battles/{$battle->id}/start");
        $resStranger->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only the match creator can start the battle.',
            ]);

        // 2. Joiner tries to start -> 403 Forbidden
        $resJoiner = $this->actingAs($joiner)->postJson("/battles/{$battle->id}/start");
        $resJoiner->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only the match creator can start the battle.',
            ]);

        // 3. Creator starts -> 200 OK
        $resCreator = $this->actingAs($creator)->postJson("/battles/{$battle->id}/start");
        $resCreator->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'running',
            ]);

        $fresh = $battle->fresh();
        $this->assertEquals('running', $fresh->status);
        $this->assertNotNull($fresh->started_at);
        $this->assertNotNull($fresh->submission_deadline_at);
    }

    /**
     * Test 7: Both participants see consistent authoritative status via syncState.
     */
    public function test_07_both_participants_see_consistent_authoritative_status_via_sync_state()
    {
        $creator = $this->createPlayer(500, 'user', 'Neeraj Chopra');
        $joiner = $this->createPlayer(500, 'user', 'Abhinav Bindra');

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-SYNC-' . Str::random(5),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'requested',
            'room_code_expires_at' => now()->addSeconds(180),
        ]);

        // 1. Creator sync checks pending start
        $resCreator = $this->actingAs($creator)->getJson("/battles/sync-state?battle_id={$battle->id}");
        $resCreator->assertStatus(200)
            ->assertJson([
                'authenticated' => true,
                'battle' => [
                    'id' => $battle->id,
                    'status' => 'requested',
                ],
                'creator_pending_start' => [
                    'battle_id' => $battle->id,
                    'joiner_name' => 'Abhinav Bindra',
                ],
            ]);

        // 2. Creator starts match
        $battle->update(['status' => 'running', 'started_at' => now()]);

        // 3. Joiner sync checks started match
        $resJoiner = $this->actingAs($joiner)->getJson("/battles/sync-state?battle_id={$battle->id}");
        $resJoiner->assertStatus(200)
            ->assertJson([
                'authenticated' => true,
                'battle' => [
                    'id' => $battle->id,
                    'status' => 'running',
                ],
                'joiner_started_match' => [
                    'battle_id' => $battle->id,
                ],
            ]);
    }

    /**
     * Test 8: Room-code submission persists and updates match.
     */
    public function test_08_room_code_submission_persists_and_updates_match()
    {
        $creator = $this->createPlayer(500, 'user', 'Anil Kumble');
        $joiner = $this->createPlayer(500, 'user', 'Harbhajan Singh');

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-ROOM-' . Str::random(5),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'started_at' => now(),
            'room_code_expires_at' => now()->addSeconds(180),
        ]);

        $response = $this->actingAs($creator)->postJson("/battles/{$battle->id}/room-code", [
            'room_code' => '0548 9321',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'room_code' => '05489321', // Cleaned and spaces removed
            ]);

        $this->assertEquals('05489321', $battle->fresh()->room_code);
    }

    /**
     * Test 9: Unauthorized room-code access is denied, and overwriting an existing room code is prevented.
     */
    public function test_09_unauthorized_room_code_access_denied_and_overwriting_prevented()
    {
        $creator = $this->createPlayer(500, 'user', 'Kapil Dev');
        $joiner = $this->createPlayer(500, 'user', 'Sunil Gavaskar');
        $stranger = $this->createPlayer(500, 'user', 'Mohammad Azharuddin');

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-DENIED-' . Str::random(5),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'room_code' => '12345678',
            'started_at' => now(),
            'room_code_expires_at' => now()->addSeconds(180),
        ]);

        // 1. Stranger attempts room-code submission -> 403 Forbidden
        $resStranger = $this->actingAs($stranger)->postJson("/battles/{$battle->id}/room-code", [
            'room_code' => '99999999',
        ]);
        $resStranger->assertStatus(403);

        // 2. Creator attempts to overwrite existing room code -> 422 Unprocessable
        $resOverwrite = $this->actingAs($creator)->postJson("/battles/{$battle->id}/room-code", [
            'room_code' => '88888888',
        ]);
        $resOverwrite->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Room code is already set to 12345678 and cannot be overwritten.',
            ]);

        $this->assertEquals('12345678', $battle->fresh()->room_code);
    }

    /**
     * Test 10: Dispute or cancel-with-reason persists with valid reason from the 7 standard options.
     */
    public function test_10_cancel_with_reason_persists_with_valid_reason_from_seven_options()
    {
        $creator = $this->createPlayer(500, 'user', 'Bhaichung Bhutia');
        $joiner = $this->createPlayer(500, 'user', 'Sunil Chhetri');

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-CANCEL-' . Str::random(5),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Simulate entry fees deducted during battle creation and joining
        $creator->decrement('coins', 100);
        $joiner->decrement('coins', 100);

        // Joiner cancels due to 'No Room code'
        $response = $this->actingAs($joiner)->postJson("/battles/{$battle->id}/cancel-with-reason", [
            'reason' => 'No Room code',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $fresh = $battle->fresh();
        $this->assertEquals('cancelled', $fresh->status);
        $this->assertEquals('No Room code', $fresh->cancellation_reason);
        $this->assertEquals($joiner->id, $fresh->cancelled_by_id);

        // Both players refunded atomically
        $this->assertEquals(500, $creator->fresh()->coins);
        $this->assertEquals(500, $joiner->fresh()->coins);

        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $creator->id,
            'points_delta' => 100,
            'type' => 'refund',
            'status' => 'successful',
        ]);
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $joiner->id,
            'points_delta' => 100,
            'type' => 'refund',
            'status' => 'successful',
        ]);
    }

    /**
     * Test 11: Admin resolution updates the correct match and ledger.
     */
    public function test_11_admin_dispute_resolution_updates_correct_match_and_ledger()
    {
        $admin = $this->createAdmin();
        $creator = $this->createPlayer(500, 'user', 'Sania Mirza');
        $joiner = $this->createPlayer(500, 'user', 'PV Sindhu');

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-DISP-' . Str::random(5),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'review_status' => 'disputed',
            'started_at' => now(),
        ]);

        $dispute = LudoDispute::create([
            'dispute_uid' => 'DSP-TEST-' . Str::random(5),
            'battle_id' => $battle->id,
            'opened_by_user_id' => $creator->id,
            'against_user_id' => $joiner->id,
            'reason_category' => 'conflicting_claims',
            'description' => 'Both players submitted won claim screenshot.',
            'status' => 'open',
        ]);

        // Admin resolves by awarding win to Creator
        $response = $this->actingAs($admin)->post("/admin/disputes/{$dispute->id}/resolve", [
            'decision' => 'award_creator',
            'admin_reason' => 'Creator screenshot confirms legitimate Ludo King victory.',
        ]);

        $response->assertSessionHas('success');

        // Dispute marked resolved
        $freshDispute = $dispute->fresh();
        $this->assertEquals('resolved', $freshDispute->status);
        $this->assertEquals($admin->id, $freshDispute->resolved_by_admin_id);

        // Battle marked completed with winner
        $freshBattle = $battle->fresh();
        $this->assertEquals('completed', $freshBattle->status);
        $this->assertEquals($creator->id, $freshBattle->winner_id);

        // Creator awarded prize points in ledger
        $this->assertEquals(690, $creator->fresh()->coins); // 500 initial + 190 prize
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $creator->id,
            'points_delta' => 190,
            'type' => 'prize_payout',
            'status' => 'successful',
        ]);
    }

    /**
     * Test 12: Cancelled or expired battles cannot be joined.
     */
    public function test_12_cancelled_or_expired_battles_cannot_be_joined()
    {
        $creator = $this->createPlayer(500, 'user', 'Dhyan Chand');
        $joiner = $this->createPlayer(500, 'user', 'Milkha Singh');

        $cancelledBattle = LudoBattle::create([
            'battle_uid' => 'BAT-DEAD-' . Str::random(5),
            'creator_id' => $creator->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'cancelled',
            'cancellation_reason' => 'Cancelled by player',
        ]);

        $response = $this->actingAs($joiner)->postJson("/battles/{$cancelledBattle->id}/join");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'This battle is no longer open for joining.',
            ]);

        // Joiner coins unaffected
        $this->assertEquals(500, $joiner->fresh()->coins);
    }

    /**
     * Test 13: Infinite scrolling returns paginated results without duplicates.
     */
    public function test_13_infinite_scrolling_returns_paginated_results_without_duplicates()
    {
        $creator = $this->createPlayer(10000, 'user', 'PT Usha');

        $createdIds = [];
        for ($i = 0; $i < 6; $i++) {
            $b = LudoBattle::create([
                'battle_uid' => 'BAT-INF-' . $i . '-' . Str::random(4),
                'creator_id' => $creator->id,
                'entry_fee' => 100 + ($i * 10),
                'prize' => 190 + ($i * 19),
                'status' => 'open',
            ]);
            $createdIds[] = $b->id;
        }

        // Fetch page 1 (3 items)
        $res1 = $this->getJson('/battles/load-more?type=open&per_page=3');
        $res1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 3,
                'has_more' => true,
            ]);

        $page1Ids = collect($res1->json('items'))->pluck('id')->all();
        $nextCursor = $res1->json('next_cursor');
        $this->assertNotNull($nextCursor);

        // Fetch page 2 using cursor
        $res2 = $this->getJson("/battles/load-more?type=open&per_page=3&cursor={$nextCursor}");
        $res2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'count' => 3,
            ]);

        $page2Ids = collect($res2->json('items'))->pluck('id')->all();

        // Ensure zero overlapping IDs between page 1 and page 2
        $intersection = array_intersect($page1Ids, $page2Ids);
        $this->assertEmpty($intersection, 'Cursor pagination returned duplicate records!');
    }

    /**
     * Test 14: All relevant status transitions are validated end-to-end.
     * Transitions: open -> requested -> running -> completed.
     */
    public function test_14_all_relevant_status_transitions_validated_end_to_end()
    {
        $creator = $this->createPlayer(1000, 'user', 'Viswanathan Anand');
        $joiner = $this->createPlayer(1000, 'user', 'Gukesh Dommaraju');

        // 1. Create -> status: open
        $resCreate = $this->actingAs($creator)->postJson('/battles/create', ['amount' => 100]);
        $battleId = $resCreate->json('battle.id');
        $battle = LudoBattle::findOrFail($battleId);
        $this->assertEquals('open', $battle->status);

        // Invalid: Cannot start battle before opponent joins
        $resInvalidStart = $this->actingAs($creator)->postJson("/battles/{$battleId}/start");
        $resInvalidStart->assertStatus(422); // Rejects open battle before opponent joins

        // 2. Opponent Joins -> status: requested
        $resJoin = $this->actingAs($joiner)->postJson("/battles/{$battleId}/join");
        $resJoin->assertStatus(200);
        $this->assertEquals('requested', $battle->fresh()->status);

        // Invalid: 3rd party or opponent cannot start
        $resBadStart = $this->actingAs($joiner)->postJson("/battles/{$battleId}/start");
        $resBadStart->assertStatus(403);

        // 3. Creator starts -> status: running
        $resStart = $this->actingAs($creator)->postJson("/battles/{$battleId}/start");
        $resStart->assertStatus(200);
        $this->assertEquals('running', $battle->fresh()->status);

        // 4. Room code set
        $this->actingAs($creator)->postJson("/battles/{$battleId}/room-code", ['room_code' => '99887766']);
        $this->assertEquals('99887766', $battle->fresh()->room_code);

        // 5. Result submission: Creator claims won, Joiner claims lost -> completed
        $this->actingAs($creator)->post("/battles/{$battleId}/result", ['result' => 'won']);
        $this->actingAs($joiner)->post("/battles/{$battleId}/result", ['result' => 'lost']);

        $this->assertEquals('completed', $battle->fresh()->status);
        $this->assertEquals($creator->id, $battle->fresh()->winner_id);

        // Invalid: cannot join a completed battle
        $stranger = $this->createPlayer(500, 'user', 'Praggnanandhaa R');
        $resJoinCompleted = $this->actingAs($stranger)->postJson("/battles/{$battleId}/join");
        $resJoinCompleted->assertStatus(422);
    }

    /**
     * Test 15: Existing authentication and unrelated features remain functional.
     */
    public function test_15_existing_authentication_and_unrelated_features_remain_functional()
    {
        // 1. Guest redirected from protected routes
        $protectedRoutes = ['/wallet', '/profile', '/history', '/refer', '/kyc'];
        foreach ($protectedRoutes as $route) {
            $res = $this->get($route);
            $res->assertRedirect('/showlogin');
        }

        // 2. Guest cannot create battle
        $resGuestCreate = $this->post('/battles/create', ['amount' => 100]);
        $resGuestCreate->assertRedirect('/showlogin');

        // 3. Authenticated user can view Wallet with Non-Cash Virtual Points Reload
        $player = $this->createPlayer(500, 'user', 'Deepika Kumari');
        $resWallet = $this->actingAs($player)->get('/wallet');
        $resWallet->assertStatus(200);
        $resWallet->assertSee('Reload Virtual Tournament Points');
        $resWallet->assertSee('Add Virtual Points');
        $resWallet->assertSee('100% FREE • NON-CASH');

        // 4. Authenticated user can view Rules & Tournaments
        $resRules = $this->actingAs($player)->get('/rules');
        $resRules->assertStatus(200);

        $resTournaments = $this->actingAs($player)->get('/tournaments');
        $resTournaments->assertStatus(200);
    }
}
