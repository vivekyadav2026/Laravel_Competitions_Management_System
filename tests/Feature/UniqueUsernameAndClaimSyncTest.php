<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UniqueUsernameAndClaimSyncTest extends TestCase
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
            'name' => 'Arjun Sharma',
            'username' => 'arjun_sharma_' . $suffix,
            'email' => 'arjun_' . $suffix . '@fsaludoclub.test',
            'password' => bcrypt('password123'),
            'role' => 'player',
            'coins' => 1000,
        ]);

        $this->playerB = User::create([
            'name' => 'Rohit Verma',
            'username' => 'rohit_verma_' . $suffix,
            'email' => 'rohit_' . $suffix . '@fsaludoclub.test',
            'password' => bcrypt('password123'),
            'role' => 'player',
            'coins' => 1000,
        ]);

        $this->outsider = User::create([
            'name' => 'Outsider Player',
            'username' => 'outsider_' . $suffix,
            'email' => 'outsider_' . $suffix . '@fsaludoclub.test',
            'password' => bcrypt('password123'),
            'role' => 'player',
            'coins' => 1000,
        ]);
    }

    public function test_username_uniqueness_enforced_case_insensitively_on_registration()
    {
        $existing = $this->playerA->username;

        // Try registering with same username in uppercase
        $response = $this->post('/register', [
            'name' => 'Duplicate User',
            'username' => strtoupper($existing),
            'email' => 'unique_' . uniqid() . '@test.com',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_profile_update_validates_username_uniqueness_and_ignores_self()
    {
        // Player B cannot take Player A's username
        $response = $this->actingAs($this->playerB)->post(route('ludo.profile.update'), [
            'name' => $this->playerB->name,
            'username' => $this->playerA->username,
        ]);
        $response->assertSessionHasErrors('username');

        // Player B can keep their own username
        $responseSelf = $this->actingAs($this->playerB)->post(route('ludo.profile.update'), [
            'name' => 'Rohit Verma Updated',
            'username' => $this->playerB->username,
        ]);
        $responseSelf->assertSessionHasNoErrors();
        $this->playerB->refresh();
        $this->assertEquals('Rohit Verma Updated', $this->playerB->name);
    }

    public function test_admin_match_audit_dossier_displays_participant_usernames_and_ids()
    {
        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-AUDIT-' . strtoupper(uniqid()),
            'creator_id' => $this->playerA->id,
            'joiner_id' => $this->playerB->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'review_status' => 'under_review',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.ludo.matches.show', $battle->id));
        $response->assertStatus(200);

        // Creator details
        $response->assertSee($this->playerA->name);
        $response->assertSee('@' . $this->playerA->username);
        $response->assertSee('User ID: #' . $this->playerA->id);

        // Opponent details
        $response->assertSee($this->playerB->name);
        $response->assertSee('@' . $this->playerB->username);
        $response->assertSee('User ID: #' . $this->playerB->id);

        // Settlement verdict dropdown options reference participant User IDs
        $response->assertSee('value="' . $this->playerA->id . '"', false);
        $response->assertSee('value="' . $this->playerB->id . '"', false);
    }

    public function test_admin_settlement_accepts_participant_user_id_and_rejects_outsider()
    {
        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-SETTLE-' . strtoupper(uniqid()),
            'creator_id' => $this->playerA->id,
            'joiner_id' => $this->playerB->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'review_status' => 'under_review',
        ]);

        // Attempt awarding to non-participant outsider
        $responseOutsider = $this->actingAs($this->admin)->post(route('admin.ludo.matches.resolve', $battle->id), [
            'decision' => (string) $this->outsider->id,
            'reason' => 'Invalid outsider selection test',
        ]);
        $responseOutsider->assertSessionHasErrors('decision');

        // Award to participant Player A using their User ID
        $responseWinner = $this->actingAs($this->admin)->post(route('admin.ludo.matches.resolve', $battle->id), [
            'decision' => (string) $this->playerA->id,
            'reason' => 'Legitimate creator victory confirmed via screenshots',
        ]);
        $responseWinner->assertSessionHasNoErrors();

        $battle->refresh();
        $this->assertEquals('completed', $battle->status);
        $this->assertEquals($this->playerA->id, $battle->winner_id);
    }

    public function test_player_victory_claim_submission_and_realtime_sync_for_opponent()
    {
        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-CLAIM-' . strtoupper(uniqid()),
            'creator_id' => $this->playerA->id,
            'joiner_id' => $this->playerB->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'room_code' => '994821',
        ]);

        // Player A submits victory claim
        $responseA = $this->actingAs($this->playerA)->postJson(route('ludo.battles.result', $battle->id), [
            'result' => 'won',
        ]);
        $responseA->assertStatus(200);
        $responseA->assertJson([
            'success' => true,
            'status' => 'pending_opponent',
            'claimed' => 'won',
            'message' => 'Your victory claim has been submitted. Awaiting verification.',
        ]);

        // Player B polls syncState without refreshing
        $responseB = $this->actingAs($this->playerB)->getJson(route('ludo.battles.syncState', ['battle_id' => $battle->id]));
        $responseB->assertStatus(200);
        $responseB->assertJson([
            'authenticated' => true,
            'battle' => [
                'id' => $battle->id,
                'status' => 'running',
                'creator_claimed_result' => 'won',
                'opponent_claimed_result' => 'won',
                'opponent_username' => '@' . $this->playerA->username,
                'claim_status_message' => "Your opponent (@{$this->playerA->username}) has submitted a Victory Claim. Awaiting verification.",
            ],
        ]);
    }

    public function test_insufficient_balance_returns_structured_json_with_wallet_link()
    {
        $brokePlayer = User::create([
            'name' => 'Broke Gamer',
            'username' => 'broke_' . uniqid(),
            'email' => 'broke_' . uniqid() . '@test.com',
            'password' => bcrypt('password123'),
            'role' => 'player',
            'coins' => 40,
        ]);

        // Create Battle check
        $resCreate = $this->actingAs($brokePlayer)->postJson(route('ludo.battles.create'), [
            'amount' => 100,
        ]);
        $resCreate->assertStatus(422);
        $resCreate->assertJson([
            'success' => false,
            'error_code' => 'INSUFFICIENT_BALANCE',
            'available_coins' => 40,
            'required_coins' => 100,
            'wallet_url' => route('ludo.wallet'),
        ]);
        $this->assertStringContainsString('Insufficient balance. Your available balance is 40 points, but this battle requires 100 points.', $resCreate->json('message'));

        // Join Battle check
        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-JOIN-BROKE-' . strtoupper(uniqid()),
            'creator_id' => $this->playerA->id,
            'entry_fee' => 200,
            'prize' => 380,
            'status' => 'open',
        ]);

        $resJoin = $this->actingAs($brokePlayer)->postJson(route('ludo.battles.join', $battle->id));
        $resJoin->assertStatus(422);
        $resJoin->assertJson([
            'success' => false,
            'error_code' => 'INSUFFICIENT_BALANCE',
            'available_coins' => 40,
            'required_coins' => 200,
            'wallet_url' => route('ludo.wallet'),
        ]);
        $this->assertStringContainsString('Insufficient balance. Your available balance is 40 points, but this battle requires 200 points.', $resJoin->json('message'));

        // Balance untouched
        $brokePlayer->refresh();
        $this->assertEquals(40, $brokePlayer->coins);
    }
}
