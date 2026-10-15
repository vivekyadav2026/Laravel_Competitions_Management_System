<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LudoBattle;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PlayAgainFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_match_arena_shows_disabled_play_again_and_waiting_notice_when_under_review()
    {
        $creator = User::factory()->create([
            'name' => 'Player A Creator',
            'username' => 'player_a_creator_101',
            'coins' => 1000,
        ]);

        $joiner = User::factory()->create([
            'name' => 'Player B Opponent',
            'username' => 'player_b_opp_202',
            'coins' => 1000,
        ]);

        $battle = LudoBattle::create([
            'battle_uid' => 'LB-TEST-REV1',
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'running',
            'review_status' => 'under_review',
            'creator_claimed_result' => 'won',
            'joiner_claimed_result' => 'won',
            'room_code' => '99887766',
        ]);

        // Acting as creator
        $response = $this->actingAs($creator)->get(route('ludo.battles.view', $battle->id));

        $response->assertStatus(200);
        $response->assertSee('Match Result Status');
        $response->assertSee('UNDER REVIEW');
        $response->assertSee('player_b_opp_202');
        $response->assertSee('btnPlayAgainDisabled');
        $response->assertSee('Play Again will unlock automatically once the match result is finalized.');
        // Verify previous result is not presented as final in the DOM
        $htmlBeforeScript = explode('<script', $response->getContent())[0];
        $this->assertStringNotContainsString('id="btnPlayAgainActive"', $htmlBeforeScript);
        $this->assertStringNotContainsString('Verified Winner:', $htmlBeforeScript);
        $response->assertSee('Match result is awaiting verification / under review.');
    }

    public function test_match_arena_shows_active_play_again_and_verified_winner_when_completed()
    {
        $creator = User::factory()->create([
            'name' => 'Player A Creator',
            'username' => 'player_a_creator_303',
            'coins' => 1000,
        ]);

        $joiner = User::factory()->create([
            'name' => 'Player B Opponent',
            'username' => 'player_b_opp_404',
            'coins' => 1000,
        ]);

        $battle = LudoBattle::create([
            'battle_uid' => 'LB-TEST-COMP1',
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'completed',
            'winner_id' => $creator->id,
            'review_status' => 'verified',
            'creator_claimed_result' => 'won',
            'joiner_claimed_result' => 'lost',
            'room_code' => '11223344',
            'completed_at' => now(),
        ]);

        // Acting as joiner (viewing opponent creator's win)
        $response = $this->actingAs($joiner)->get(route('ludo.battles.view', $battle->id));

        $response->assertStatus(200);
        $response->assertSee('Match Result Status');
        $response->assertSee('COMPLETED');
        $response->assertSee('Verified Winner:');
        $response->assertSee('Player A Creator');
        $response->assertSee('player_a_creator_303');
        $response->assertSee('btnPlayAgainActive');
        $response->assertSee(route('ludo.battles'));
        $htmlBeforeScript = explode('<script', $response->getContent())[0];
        $this->assertStringNotContainsString('id="btnPlayAgainDisabled"', $htmlBeforeScript);
        $this->assertStringContainsString('id="btnPlayAgainActive"', $htmlBeforeScript);
    }

    public function test_match_arena_shows_active_play_again_and_refund_notice_when_cancelled()
    {
        $creator = User::factory()->create([
            'name' => 'Player A Creator',
            'username' => 'player_a_creator_505',
            'coins' => 1000,
        ]);

        $joiner = User::factory()->create([
            'name' => 'Player B Opponent',
            'username' => 'player_b_opp_606',
            'coins' => 1000,
        ]);

        $battle = LudoBattle::create([
            'battle_uid' => 'LB-TEST-CANC1',
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'cancelled',
            'cancellation_reason' => 'Both players agreed to cancel',
            'refund_status' => 'approved',
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($creator)->get(route('ludo.battles.view', $battle->id));

        $response->assertStatus(200);
        $response->assertSee('Match Result Status');
        $response->assertSee('CANCELLED');
        $response->assertSee('Battle Cancelled');
        $response->assertSee('Both players agreed to cancel');
        $response->assertSee('player_b_opp_606');
        $response->assertSee('btnPlayAgainActive');
        $response->assertSee(route('ludo.battles'));
    }

    public function test_sync_state_returns_opponent_and_winner_usernames()
    {
        $creator = User::factory()->create([
            'name' => 'Player A',
            'username' => 'player_a_sync_707',
            'coins' => 1000,
        ]);

        $joiner = User::factory()->create([
            'name' => 'Player B',
            'username' => 'player_b_sync_808',
            'coins' => 1000,
        ]);

        $battle = LudoBattle::create([
            'battle_uid' => 'LB-SYNC-TEST1',
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 50,
            'prize' => 95,
            'status' => 'completed',
            'winner_id' => $creator->id,
            'room_code' => '55667788',
        ]);

        // Sync as joiner
        $response = $this->actingAs($joiner)->getJson(route('ludo.battles.syncState', ['battle_id' => $battle->id]));

        $response->assertStatus(200);
        $response->assertJson([
            'authenticated' => true,
            'battle' => [
                'id' => $battle->id,
                'status' => 'completed',
                'winner_id' => $creator->id,
                'winner_username' => '@player_a_sync_707',
                'opponent_username' => '@player_a_sync_707',
            ]
        ]);
    }

    public function test_play_again_leads_to_new_battle_creation_with_brand_new_battle_id()
    {
        $creator = User::factory()->create([
            'name' => 'Player A',
            'username' => 'player_a_new_909',
            'coins' => 1000,
        ]);

        $previousBattle = LudoBattle::create([
            'battle_uid' => 'LB-OLD-BATTLE',
            'creator_id' => $creator->id,
            'entry_fee' => 50,
            'prize' => 95,
            'status' => 'completed',
            'winner_id' => $creator->id,
        ]);

        // Creating a new battle via the battles creation flow
        $response = $this->actingAs($creator)->post(route('ludo.battles.create'), [
            'amount' => 100,
        ]);

        $response->assertStatus(302);
        
        $newBattle = LudoBattle::where('creator_id', $creator->id)
            ->where('id', '!=', $previousBattle->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($newBattle);
        $this->assertNotEquals($previousBattle->id, $newBattle->id);
        $this->assertNotEquals($previousBattle->battle_uid, $newBattle->battle_uid);
        $this->assertEquals('open', $newBattle->status);
    }
}
