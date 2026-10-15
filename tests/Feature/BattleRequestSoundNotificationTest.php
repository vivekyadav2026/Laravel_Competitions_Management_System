<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BattleRequestSoundNotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $creator;
    protected User $joiner;
    protected User $thirdPlayer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creator = User::create([
            'name' => 'Vikram Creator',
            'phone' => '9811122233',
            'email' => 'creator_' . uniqid() . '@fsaludoclub.test',
            'password' => bcrypt('secret123'),
            'role' => 'player',
            'coins' => 2000,
            'is_phone_verified' => true,
        ]);

        $this->joiner = User::create([
            'name' => 'Amit Joiner',
            'phone' => '9822233344',
            'email' => 'joiner_' . uniqid() . '@fsaludoclub.test',
            'password' => bcrypt('secret123'),
            'role' => 'player',
            'coins' => 2000,
            'is_phone_verified' => true,
        ]);

        $this->thirdPlayer = User::create([
            'name' => 'Suresh Third',
            'phone' => '9833344455',
            'email' => 'third_' . uniqid() . '@fsaludoclub.test',
            'password' => bcrypt('secret123'),
            'role' => 'player',
            'coins' => 2000,
            'is_phone_verified' => true,
        ]);
    }

    /**
     * Requirement 1 & 6: Sync endpoint accurately detects incoming battle request
     * for the creator only when in 'requested' status.
     */
    public function test_sync_endpoint_returns_pending_request_for_creator_when_battle_is_requested()
    {
        // 1. Creator creates battle (status = open)
        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-REQ-' . strtoupper(uniqid()),
            'creator_id' => $this->creator->id,
            'entry_fee' => 150,
            'prize' => 285,
            'status' => 'open',
            'game_type' => 'classic',
        ]);

        // Prior to joining, sync endpoint must return null for creator_pending_start
        $resBefore = $this->actingAs($this->creator)->getJson(route('ludo.battles.syncState'));
        $resBefore->assertStatus(200);
        $resBefore->assertJson([
            'authenticated' => true,
            'creator_pending_start' => null,
        ]);

        // 2. Joiner accepts battle (transitions to 'requested')
        $battle->update([
            'joiner_id' => $this->joiner->id,
            'status' => 'requested',
        ]);

        // 3. Creator polls syncState: Must return incoming request payload
        $resAfter = $this->actingAs($this->creator)->getJson(route('ludo.battles.syncState'));
        $resAfter->assertStatus(200);

        $data = $resAfter->json('creator_pending_start');
        $this->assertNotNull($data);
        $this->assertEquals($battle->id, $data['battle_id']);
        $this->assertEquals($battle->battle_uid, $data['battle_uid']);
        $this->assertEquals('requested', $data['status']);
        $this->assertEquals('Amit Joiner', $data['joiner_name']);
        $this->assertEquals(150, $data['entry_fee']);
        $this->assertEquals(285, $data['prize']);
        $this->assertStringStartsWith('req_' . $battle->id, $data['event_id']);
        $this->assertStringContainsString('START', $data['card_html']);
        $this->assertStringContainsString('CANCEL', $data['card_html']);
    }

    /**
     * Requirement 1 & 6: Unrelated users never receive another player's battle request.
     */
    public function test_sync_endpoint_does_not_leak_request_to_unrelated_player()
    {
        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-SEC-' . strtoupper(uniqid()),
            'creator_id' => $this->creator->id,
            'joiner_id' => $this->joiner->id,
            'entry_fee' => 200,
            'prize' => 380,
            'status' => 'requested',
            'game_type' => 'classic',
        ]);

        // Unrelated third player polls syncState
        $res = $this->actingAs($this->thirdPlayer)->getJson(route('ludo.battles.syncState'));
        $res->assertStatus(200);
        $res->assertJson([
            'authenticated' => true,
            'creator_pending_start' => null,
        ]);
    }

    /**
     * Requirement 1: Cancelled, completed, or already running battles do not return as pending request.
     */
    public function test_cancelled_or_running_battles_do_not_return_as_pending_start()
    {
        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-RUN-' . strtoupper(uniqid()),
            'creator_id' => $this->creator->id,
            'joiner_id' => $this->joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'game_type' => 'classic',
        ]);

        // Running battle is not pending start
        $resRunning = $this->actingAs($this->creator)->getJson(route('ludo.battles.syncState'));
        $resRunning->assertStatus(200);
        $resRunning->assertJson([
            'creator_pending_start' => null,
        ]);

        // Cancelled battle is not pending start
        $battle->update(['status' => 'cancelled']);
        $resCancelled = $this->actingAs($this->creator)->getJson(route('ludo.battles.syncState'));
        $resCancelled->assertStatus(200);
        $resCancelled->assertJson([
            'creator_pending_start' => null,
        ]);
    }

    /**
     * Requirement 3 & 4: Battle page properly renders sound toggle button and visual toast container.
     */
    public function test_sound_toggle_and_toast_elements_rendered_in_battles_view()
    {
        $response = $this->actingAs($this->creator)->get(route('ludo.battles'));

        $response->assertStatus(200);

        // Sound Toggle Button in Create a Battle Header
        $response->assertSee('id="ludoSoundToggleBtn"', false);
        $response->assertSee('Sound: ON');
        $response->assertSee('id="ludoSoundIcon"', false);

        // Visual Real-time Toast Notification container
        $response->assertSee('id="ludoBattleRequestToast"', false);
        $response->assertSee('New Battle Request!');
        $response->assertSee('id="ludoToastOpponentName"', false);
        $response->assertSee('id="ludoToastActionBtn"', false);
    }
}
