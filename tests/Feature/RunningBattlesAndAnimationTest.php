<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\User;
use Database\Seeders\RunningBattleDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunningBattlesAndAnimationTest extends TestCase
{
    /**
     * Requirement 3: RunningBattleDemoSeeder creates valid running battles with consistent statuses
     */
    public function test_running_battle_demo_seeder_populates_valid_records()
    {
        $this->seed(RunningBattleDemoSeeder::class);

        $demoBattles = LudoBattle::where('battle_uid', 'like', 'DEMO-RUN-%')->get();
        $this->assertGreaterThanOrEqual(4, $demoBattles->count());

        foreach ($demoBattles as $battle) {
            $this->assertEquals('running', $battle->status);
            $this->assertNotNull($battle->creator_id);
            $this->assertNotNull($battle->joiner_id);
            $this->assertNotNull($battle->creator);
            $this->assertNotNull($battle->joiner);
            $this->assertEquals(LudoBattle::calculatePrize($battle->entry_fee), $battle->prize);
            $this->assertNotNull($battle->started_at);
        }
    }

    /**
     * Requirement 2: Battles page displays genuine Running Battles with real player names and data
     */
    public function test_running_battles_are_rendered_with_real_database_data()
    {
        $this->seed(RunningBattleDemoSeeder::class);
        LudoBattle::where('battle_uid', 'DEMO-RUN-100')->update(['started_at' => now()->addMinutes(5)]);

        $viewer = User::where('email', 'demo_arjun@fsaludoclub.test')->first();
        $response = $this->actingAs($viewer)->get(route('ludo.battles'));

        $response->assertStatus(200);
        $response->assertSee('DEMO-RUN-100');
        $response->assertSee('Arjun Sharma');
        $response->assertSee('Priya Patel');
        $response->assertSee('ENTRY FEE');
        $response->assertSee('WINNING PRIZE');
        $response->assertSee('Ref:');

        // Check CSS animation presence in the rendered HTML
        $response->assertSee('ludo-slide-in-right');
        $response->assertSee('applyCardStagger');
    }

    /**
     * Requirement 2 & 5: Spectator privacy — private room code is not exposed to unrelated users
     */
    public function test_spectator_does_not_see_private_room_code()
    {
        $tag = uniqid();
        $creator = User::factory()->create([
            'email' => "creator_{$tag}@fsaludoclub.test",
            'phone' => '9811' . rand(100000, 999999),
            'name' => 'Creator One',
        ]);
        $joiner = User::factory()->create([
            'email' => "joiner_{$tag}@fsaludoclub.test",
            'phone' => '9811' . rand(100000, 999999),
            'name' => 'Joiner Two',
        ]);
        $spectator = User::factory()->create([
            'email' => "spectator_{$tag}@fsaludoclub.test",
            'phone' => '9811' . rand(100000, 999999),
            'name' => 'Spectator Three',
        ]);

        $uid = 'BAT-PRIV-' . strtoupper($tag);
        $battle = LudoBattle::create([
            'battle_uid' => $uid,
            'creator_id' => $creator->id,
            'joiner_id' => $joiner->id,
            'entry_fee' => 200,
            'prize' => 380,
            'status' => 'running',
            'room_code' => 'SECRET8899',
            'started_at' => now()->addMinutes(15),
        ]);

        // Spectator view
        $spectatorResponse = $this->actingAs($spectator)->get(route('ludo.battles'));
        $spectatorResponse->assertStatus(200);
        $spectatorResponse->assertSee($uid);
        $spectatorResponse->assertSee('In Play');
        $spectatorResponse->assertDontSee('SECRET8899');

        // Participant view
        $participantResponse = $this->actingAs($creator)->get(route('ludo.battles'));
        $participantResponse->assertStatus(200);
        $participantResponse->assertSee($uid);
        $participantResponse->assertSee('Room: SECRET8899');
        $participantResponse->assertSee(route('ludo.battles.view', $battle->id));
    }

    /**
     * Requirement 4: Real-time syncState returns open and running battle IDs and running cards HTML
     */
    public function test_sync_state_returns_running_battles_and_ids()
    {
        $this->seed(RunningBattleDemoSeeder::class);

        $user = User::where('email', 'demo_arjun@fsaludoclub.test')->first();
        $response = $this->actingAs($user)->getJson(route('ludo.battles.syncState'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'authenticated',
            'user_id',
            'coins',
            'open_battle_ids',
            'running_battle_ids',
            'running_battles' => [
                '*' => ['id', 'battle_uid', 'status', 'card_html']
            ]
        ]);

        $data = $response->json();
        $this->assertNotEmpty($data['running_battle_ids']);
        $this->assertNotEmpty($data['running_battles']);
    }

    /**
     * Requirement 2: Completed, cancelled, and open battles are excluded from running list
     */
    public function test_completed_and_cancelled_battles_excluded_from_running_list()
    {
        $tag = uniqid();
        $user1 = User::factory()->create(['email' => "u1_{$tag}@fsaludoclub.test", 'phone' => '9811' . rand(100000, 999999)]);
        $user2 = User::factory()->create(['email' => "u2_{$tag}@fsaludoclub.test", 'phone' => '9811' . rand(100000, 999999)]);

        $completedUid = 'BAT-COMP-' . strtoupper($tag);
        $cancelledUid = 'BAT-CANC-' . strtoupper($tag);

        $completedBattle = LudoBattle::create([
            'battle_uid' => $completedUid,
            'creator_id' => $user1->id,
            'joiner_id' => $user2->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'completed',
            'started_at' => now()->subHours(1),
            'completed_at' => now()->subMinutes(40),
        ]);

        $cancelledBattle = LudoBattle::create([
            'battle_uid' => $cancelledUid,
            'creator_id' => $user1->id,
            'joiner_id' => $user2->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'cancelled',
            'started_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($user1)->get(route('ludo.battles'));
        $response->assertStatus(200);

        $response->assertDontSee($completedUid);
        $response->assertDontSee($cancelledUid);
    }
}
