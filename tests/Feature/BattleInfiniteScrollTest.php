<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class BattleInfiniteScrollTest extends TestCase
{
    /**
     * Verify that the Play/Battles page does not render numbered pagination controls.
     */
    public function test_play_page_does_not_contain_numbered_pagination_controls()
    {
        $response = $this->get('/play');
        $response->assertStatus(200);

        // Ensure visible numbered pagination controls are absent
        $response->assertDontSee('<ul class="pagination"', false);
        $response->assertDontSee('pagination::bootstrap-5', false);
        $response->assertDontSee('aria-label="&laquo; Previous"', false);
        $response->assertDontSee('aria-label="Next &raquo;"', false);

        // Ensure infinite scroll containers and sentinels are present
        $response->assertSee('id="open-battles-section"', false);
        $response->assertSee('id="open-battles-list"', false);
        $response->assertSee('id="open-battles-loader"', false);
        $response->assertSee('id="running-battles-section"', false);
        $response->assertSee('id="running-battles-list"', false);
        $response->assertSee('id="running-battles-loader"', false);
        $response->assertSee('ludo-sentinel', false);
        $response->assertSee('Load More Battles', false);
    }

    /**
     * Test the GET /battles/load-more endpoint for open battles.
     */
    public function test_load_more_open_battles_endpoint_returns_json_and_html()
    {
        $creator = User::factory()->create([
            'email' => 'inf_creator_' . uniqid() . '@royalludo.test',
            'coins' => 10000,
            'referral_code' => 'INF_' . Str::random(5),
        ]);

        // Create 7 open battles to guarantee pagination
        $createdIds = [];
        for ($i = 0; $i < 7; $i++) {
            $battle = LudoBattle::create([
                'battle_uid' => 'INF-OP-' . Str::random(8),
                'creator_id' => $creator->id,
                'entry_fee' => 100 + ($i * 50),
                'prize' => 190 + ($i * 95),
                'status' => 'open',
            ]);
            $createdIds[] = $battle->id;
        }

        $response = $this->getJson('/battles/load-more?type=open&per_page=5');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'open',
                'count' => 5,
                'has_more' => true,
            ])
            ->assertJsonStructure([
                'success',
                'type',
                'count',
                'has_more',
                'next_cursor',
                'html',
                'items' => [
                    '*' => [
                        'id',
                        'battle_uid',
                        'creator_id',
                        'creator_name',
                        'entry_fee',
                        'prize',
                        'status',
                    ]
                ]
            ]);

        $responseData = $response->json();
        $this->assertNotEmpty($responseData['html']);
        $this->assertStringContainsString('ludo-battle-card', $responseData['html']);
        $this->assertNotNull($responseData['next_cursor']);

        // Test cursor pagination for subsequent batch
        $nextCursor = $responseData['next_cursor'];
        $secondPageResponse = $this->getJson("/battles/load-more?type=open&cursor={$nextCursor}&per_page=5");

        $secondPageResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'open',
            ]);

        $secondData = $secondPageResponse->json();
        foreach ($secondData['items'] as $item) {
            $this->assertLessThan($nextCursor, $item['id']);
        }
    }

    /**
     * Test the GET /battles/load-more endpoint for running battles.
     */
    public function test_load_more_running_battles_endpoint_returns_json_and_html()
    {
        $creator = User::factory()->create([
            'email' => 'run_creator_' . uniqid() . '@royalludo.test',
            'coins' => 10000,
            'referral_code' => 'RUN1_' . Str::random(5),
        ]);
        $joiner = User::factory()->create([
            'email' => 'run_joiner_' . uniqid() . '@royalludo.test',
            'coins' => 10000,
            'referral_code' => 'RUN2_' . Str::random(5),
        ]);

        // Create running battles
        for ($i = 0; $i < 3; $i++) {
            LudoBattle::create([
                'battle_uid' => 'RUN-BT-' . Str::random(8),
                'creator_id' => $creator->id,
                'joiner_id' => $joiner->id,
                'entry_fee' => 200,
                'prize' => 380,
                'status' => 'running',
                'room_code' => '98765' . $i,
                'started_at' => now()->subMinutes($i * 5),
            ]);
        }

        $response = $this->getJson('/battles/load-more?type=running&per_page=5');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'running',
            ])
            ->assertJsonStructure([
                'success',
                'type',
                'count',
                'has_more',
                'html',
                'items' => [
                    '*' => [
                        'id',
                        'battle_uid',
                        'creator_id',
                        'creator_name',
                        'joiner_id',
                        'joiner_name',
                        'entry_fee',
                        'prize',
                        'status',
                    ]
                ]
            ]);

        $data = $response->json();
        $this->assertStringContainsString('ludo-running-card', $data['html']);
        $this->assertStringContainsString($creator->name, $data['html']);
        $this->assertStringContainsString($joiner->name, $data['html']);
    }

    /**
     * Test validation rules for the load-more endpoint.
     */
    public function test_load_more_battles_validates_request_parameters()
    {
        // Missing type parameter
        $response = $this->getJson('/battles/load-more');
        $response->assertStatus(422)
            ->assertJsonValidationErrors('type');

        // Invalid type parameter
        $response = $this->getJson('/battles/load-more?type=invalid_status');
        $response->assertStatus(422)
            ->assertJsonValidationErrors('type');

        // Invalid cursor parameter (must be positive integer)
        $response = $this->getJson('/battles/load-more?type=open&cursor=0');
        $response->assertStatus(422)
            ->assertJsonValidationErrors('cursor');

        // Exceeding per_page limit
        $response = $this->getJson('/battles/load-more?type=open&per_page=100');
        $response->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    /**
     * Test load-more endpoint when reaching end of records.
     */
    public function test_load_more_battles_handles_end_of_results()
    {
        // Query with an impossible cursor (e.g. 1 when all IDs are much higher)
        $response = $this->getJson('/battles/load-more?type=open&cursor=1');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'type' => 'open',
                'count' => 0,
                'has_more' => false,
                'next_cursor' => null,
                'items' => [],
            ]);
    }
}
