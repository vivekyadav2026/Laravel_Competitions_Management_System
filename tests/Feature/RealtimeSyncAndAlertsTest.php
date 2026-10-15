<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\LudoDispute;
use App\Models\LudoUpiDeposit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RealtimeSyncAndAlertsTest extends TestCase
{
    protected function createPlayer(int $coins = 1000, string $role = 'user'): User
    {
        $unique = 't' . time() . '_' . Str::random(6);
        return User::factory()->create([
            'name' => 'Player_' . Str::random(4),
            'email' => "player_{$unique}@fsaludoclub.test",
            'phone' => '98' . rand(10000000, 99999999),
            'coins' => $coins,
            'role' => $role,
            'kyc_status' => 'verified',
            'referral_code' => 'REF_' . strtoupper(Str::random(4)),
        ]);
    }

    protected function createAdmin(): User
    {
        return $this->createPlayer(5000, 'admin');
    }

    /**
     * 1. Player wallet sync endpoint requires authentication
     */
    public function test_player_wallet_sync_requires_authentication()
    {
        $response = $this->getJson(route('ludo.wallet.syncState'));
        $response->assertStatus(401);
    }

    /**
     * 2. Player wallet sync returns authoritative balance and recent deposits
     */
    public function test_player_wallet_sync_returns_balance_and_deposits()
    {
        $player = $this->createPlayer(750);

        $dep = LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(8)),
            'user_id' => $player->id,
            'amount' => 500,
            'utr_number' => 'UTR_' . Str::random(8),
            'upi_id' => 'admin@upi',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($player)->getJson(route('ludo.wallet.syncState'));

        $response->assertStatus(200)
            ->assertJson([
                'authenticated' => true,
                'user_id' => $player->id,
                'coins' => 750,
            ]);

        $data = $response->json();
        $this->assertIsArray($data['recent_deposits']);
        $this->assertEquals($dep->id, $data['recent_deposits'][0]['id']);
        $this->assertEquals('pending', $data['recent_deposits'][0]['status']);
    }

    /**
     * 3. Admin sync alerts endpoint requires admin privileges (403 for players)
     */
    public function test_admin_sync_alerts_requires_admin_role()
    {
        $player = $this->createPlayer(500);
        $response = $this->actingAs($player)->getJson(route('admin.ludo.notifications.live'));
        $response->assertStatus(403);
    }

    /**
     * 4. Admin sync alerts returns correct counts and latest deposit details
     */
    public function test_admin_sync_alerts_returns_correct_pending_counts_and_latest()
    {
        $admin = $this->createAdmin();
        $player = $this->createPlayer(200);

        $dep = LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(8)),
            'user_id' => $player->id,
            'amount' => 1200,
            'utr_number' => 'UTR_' . Str::random(8),
            'upi_id' => 'admin@upi',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.ludo.notifications.live'));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json();
        $this->assertGreaterThanOrEqual(1, $data['counts']['deposits']);
        $this->assertNotNull($data['latest']['deposit']);
        $this->assertEquals(1200, $data['latest']['deposit']['amount']);
    }

    /**
     * 5. Match arena sync endpoint protects room code from strangers
     */
    public function test_match_arena_sync_protects_room_code_from_strangers()
    {
        $creator = $this->createPlayer(1000);
        $joiner = $this->createPlayer(1000);
        $stranger = $this->createPlayer(1000);

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-' . strtoupper(Str::random(8)),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'room_code' => '98765432',
            'review_status' => 'auto_approved',
            'refund_status' => 'none',
        ]);

        // Stranger query
        $res = $this->actingAs($stranger)->getJson(route('ludo.battles.syncState', ['battle_id' => $battle->id]));
        $res->assertStatus(200);
        $data = $res->json();

        $this->assertNull($data['battle']['room_code'], 'Strangers must not see private room codes');
        $this->assertFalse($data['battle']['authorized']);
    }

    /**
     * 6. Match arena sync endpoint reveals room code to creator and joiner
     */
    public function test_match_arena_sync_reveals_room_code_to_participants()
    {
        $creator = $this->createPlayer(1000);
        $joiner = $this->createPlayer(1000);

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-' . strtoupper(Str::random(8)),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'room_code' => '55443322',
            'review_status' => 'auto_approved',
            'refund_status' => 'none',
        ]);

        // Creator query
        $resCreator = $this->actingAs($creator)->getJson(route('ludo.battles.syncState', ['battle_id' => $battle->id]));
        $resCreator->assertStatus(200);
        $this->assertEquals('55443322', $resCreator->json('battle.room_code'));
        $this->assertTrue($resCreator->json('battle.authorized'));

        // Joiner query
        $resJoiner = $this->actingAs($joiner)->getJson(route('ludo.battles.syncState', ['battle_id' => $battle->id]));
        $resJoiner->assertStatus(200);
        $this->assertEquals('55443322', $resJoiner->json('battle.room_code'));
        $this->assertTrue($resJoiner->json('battle.authorized'));
    }

    /**
     * 7. Approving a deposit updates player sync state balance and status immediately
     */
    public function test_approving_deposit_updates_player_sync_state()
    {
        $admin = $this->createAdmin();
        $player = $this->createPlayer(200);

        $dep = LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(8)),
            'user_id' => $player->id,
            'amount' => 300,
            'utr_number' => 'UTR_' . Str::random(8),
            'upi_id' => 'admin@upi',
            'status' => 'pending',
        ]);

        // Admin approves via JSON
        $apprRes = $this->actingAs($admin)->postJson(route('admin.ludo.deposits.approve', $dep->id));
        $apprRes->assertStatus(200)
            ->assertJson(['success' => true]);

        // Player sync endpoint reflects updated balance
        $syncRes = $this->actingAs($player)->getJson(route('ludo.wallet.syncState'));
        $syncRes->assertStatus(200);
        $this->assertEquals(500, $syncRes->json('coins'));
        $this->assertEquals('approved', $syncRes->json('recent_deposits.0.status'));
    }

    /**
     * 8. Submitting a dispute updates admin sync alerts with new dispute details
     */
    public function test_submitting_dispute_updates_admin_sync_alerts()
    {
        $admin = $this->createAdmin();
        $creator = $this->createPlayer(1000);
        $joiner = $this->createPlayer(1000);

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-' . strtoupper(Str::random(8)),
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'review_status' => 'auto_approved',
            'refund_status' => 'none',
        ]);

        // Creator opens a dispute with required evidence and valid reason_category
        Storage::fake('uploads');
        $evidence = UploadedFile::fake()->image('dispute_evidence.png');

        $dispRes = $this->actingAs($creator)->post(route('ludo.dispute.open', $battle->id), [
            'reason_category' => 'wrong_result',
            'description' => 'Opponent submitted incorrect match claim after game.',
            'evidence' => $evidence,
        ]);
        $dispRes->assertSessionHasNoErrors();

        // Admin sync alerts sees the dispute
        $alertsRes = $this->actingAs($admin)->getJson(route('admin.ludo.notifications.live'));
        $alertsRes->assertStatus(200);
        $data = $alertsRes->json();
        $this->assertGreaterThanOrEqual(1, $data['counts']['disputes']);
        $this->assertNotNull($data['latest']['dispute']);
    }

    /**
     * 9. Admin deposits live feed endpoint returns pending counts and items
     */
    public function test_admin_deposits_live_feed_endpoint()
    {
        $admin = $this->createAdmin();
        $player = $this->createPlayer(100);

        LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(8)),
            'user_id' => $player->id,
            'amount' => 450,
            'utr_number' => 'UTR_' . Str::random(8),
            'upi_id' => 'admin@upi',
            'status' => 'pending',
        ]);

        $res = $this->actingAs($admin)->getJson(route('admin.ludo.deposits.liveFeed'));
        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $res->json();
        $this->assertGreaterThanOrEqual(1, $data['counts']['pending']);
        $this->assertNotEmpty($data['deposits']);
    }

    /**
     * 10. Admin matches live feed endpoint returns matches with statuses
     */
    public function test_admin_matches_live_feed_endpoint()
    {
        $admin = $this->createAdmin();
        $creator = $this->createPlayer(1000);

        LudoBattle::create([
            'battle_uid' => 'BAT-' . strtoupper(Str::random(8)),
            'creator_id' => $creator->id,
            'entry_fee' => 50,
            'prize' => 95,
            'status' => 'open',
            'review_status' => 'auto_approved',
            'refund_status' => 'none',
        ]);

        $res = $this->actingAs($admin)->getJson(route('admin.ludo.matches.liveFeed'));
        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $res->json();
        $this->assertNotEmpty($data['matches']);
    }
}
