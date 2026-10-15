<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\LudoLedgerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OpenBattlesConfirmationModalTest extends TestCase
{
    use DatabaseTransactions;

    protected User $creator;
    protected User $zeroBalancePlayer;
    protected User $sufficientPlayer;
    protected LudoBattle $openBattle;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Match Creator with phone number and registered name
        $this->creator = User::create([
            'name' => 'Karan Sharma',
            'phone' => '9812345678',
            'email' => 'karan_test_' . uniqid() . '@fsaludoclub.test',
            'password' => bcrypt('secret123'),
            'role' => 'player',
            'coins' => 2000,
            'is_phone_verified' => true,
        ]);

        // 2. Player with ZERO virtual points
        $this->zeroBalancePlayer = User::create([
            'name' => 'Rahul Zero',
            'phone' => '9876500000',
            'email' => 'zero_test_' . uniqid() . '@fsaludoclub.test',
            'password' => bcrypt('secret123'),
            'role' => 'player',
            'coins' => 0,
            'is_phone_verified' => true,
        ]);

        // 3. Player with SUFFICIENT virtual points (2,500 points)
        $this->sufficientPlayer = User::create([
            'name' => 'Priya Sufficient',
            'phone' => '9899911111',
            'email' => 'priya_test_' . uniqid() . '@fsaludoclub.test',
            'password' => bcrypt('secret123'),
            'role' => 'player',
            'coins' => 2500,
            'is_phone_verified' => true,
        ]);

        // Create an open battle for 100 points (prize 190)
        $this->openBattle = LudoBattle::create([
            'battle_uid' => 'BAT-MODAL-' . strtoupper(uniqid()),
            'creator_id' => $this->creator->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'open',
            'game_type' => 'classic',
        ]);
    }

    /**
     * Requirement 1, 2, 3, 4: Modal displays exact battle data and disables Accept & Play when balance is zero/insufficient.
     */
    public function test_modal_shows_exact_battle_data_and_disables_button_for_insufficient_balance()
    {
        $response = $this->actingAs($this->zeroBalancePlayer)->get(route('ludo.battles'));

        $response->assertStatus(200);

        // Verify exact battle UID and Creator Name from DB
        $response->assertSee($this->openBattle->battle_uid);
        $response->assertSee($this->creator->name);

        // Verify Entry Fee and Prize
        $response->assertSee('100');
        $response->assertSee('190');

        // Verify Modal ID matches battle id
        $response->assertSee('id="ludoPlayModal' . $this->openBattle->id . '"', false);

        // Verify Insufficient Points warning banner is rendered
        $response->assertSee('Insufficient Points Balance');
        $response->assertSee('You currently have <strong class="text-white user-coins-display">🪙 0</strong>', false);
        $response->assertSee('this battle requires <strong class="text-white">🪙 100</strong>', false);

        // Verify disabled button is rendered and Accept & Play button is absent for this user
        $response->assertSee('disabled title="Insufficient points balance"', false);
        $response->assertSee('Insufficient Points');
    }

    /**
     * Requirement 3, 4: Server strictly rejects join attempts when balance is insufficient (never trusting client).
     */
    public function test_server_enforces_insufficient_balance_rejection()
    {
        $response = $this->actingAs($this->zeroBalancePlayer)->postJson(
            route('ludo.battles.join', $this->openBattle->id)
        );

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment([
            'message' => 'Insufficient points! You have 🪙 0, but this battle requires 🪙 100.',
        ]);

        // Verify DB integrity: Battle remains open and no joiner is assigned
        $this->openBattle->refresh();
        $this->assertEquals('open', $this->openBattle->status);
        $this->assertNull($this->openBattle->joiner_id);

        // Verify Zero balance remains untouched
        $this->zeroBalancePlayer->refresh();
        $this->assertEquals(0, $this->zeroBalancePlayer->coins);
    }

    /**
     * Requirement 1, 2, 3: Modal enables Accept & Play when balance is sufficient.
     */
    public function test_modal_enables_accept_and_play_for_player_with_sufficient_balance()
    {
        $response = $this->actingAs($this->sufficientPlayer)->get(route('ludo.battles'));

        $response->assertStatus(200);

        // Modal should render verified balance box
        $response->assertSee('Your Current Balance:');
        $response->assertSee('2,500');
        $response->assertSee('Balance After Joining:');
        $response->assertSee('2,400');

        // Form and active Accept & Play button must be present
        $response->assertSee('class="d-inline ludo-join-battle-form"', false);
        $response->assertSee('Accept & Play', false);
        $response->assertDontSee('disabled title="Insufficient points balance"');
    }

    /**
     * Requirement 5: Sufficient balance player joins battle successfully inside database transaction.
     */
    public function test_successful_join_updates_database_and_deducts_points_via_ledger()
    {
        $initialCoins = $this->sufficientPlayer->coins;

        $response = $this->actingAs($this->sufficientPlayer)->postJson(
            route('ludo.battles.join', $this->openBattle->id)
        );

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'requested',
            'battle_id' => $this->openBattle->id,
            'coins' => $initialCoins - 100,
        ]);

        // Verify database persistence
        $this->openBattle->refresh();
        $this->assertEquals('requested', $this->openBattle->status);
        $this->assertEquals($this->sufficientPlayer->id, $this->openBattle->joiner_id);
        $this->assertNotNull($this->openBattle->room_code_expires_at);

        // Verify points deduction
        $this->sufficientPlayer->refresh();
        $this->assertEquals(2400, $this->sufficientPlayer->coins);

        // Verify ledger record
        $ledger = LudoLedgerTransaction::where('user_id', $this->sufficientPlayer->id)
            ->where('type', 'entry_fee')
            ->latest('id')
            ->first();

        $this->assertNotNull($ledger);
        $this->assertEquals(-100, $ledger->points_delta);
        $this->assertEquals(2400, $ledger->balance_after);
    }

    /**
     * Requirement 5: Concurrency check - prevents two players from claiming the same seat.
     */
    public function test_simultaneous_duplicate_join_is_rejected()
    {
        // First player joins successfully
        $res1 = $this->actingAs($this->sufficientPlayer)->postJson(
            route('ludo.battles.join', $this->openBattle->id)
        );
        $res1->assertStatus(200);

        // Second player with sufficient points attempts to join the same battle
        $player2 = User::create([
            'name' => 'Vikram Second',
            'phone' => '9888822222',
            'email' => 'vikram_' . uniqid() . '@fsaludoclub.test',
            'password' => bcrypt('secret123'),
            'role' => 'player',
            'coins' => 1000,
            'is_phone_verified' => true,
        ]);

        $res2 = $this->actingAs($player2)->postJson(
            route('ludo.battles.join', $this->openBattle->id)
        );

        $res2->assertStatus(422);
        $res2->assertJson([
            'success' => false,
            'message' => 'This battle is no longer open for joining.',
        ]);

        // Verify player2 was not charged
        $player2->refresh();
        $this->assertEquals(1000, $player2->coins);
    }

    /**
     * Requirement 6: Unavailable or cancelled battle returns clear 422 error.
     */
    public function test_cancelled_battle_returns_clear_error()
    {
        $this->openBattle->update(['status' => 'cancelled']);

        $response = $this->actingAs($this->sufficientPlayer)->postJson(
            route('ludo.battles.join', $this->openBattle->id)
        );

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'This battle is no longer open for joining.',
        ]);
    }

    /**
     * Requirement 8: Creator name is real registered user name and not fabricated.
     */
    public function test_creator_name_uses_real_registered_name()
    {
        $response = $this->actingAs($this->sufficientPlayer)->get(route('ludo.battles'));

        $response->assertSee('Karan Sharma');
        $response->assertSee('Challenge Karan Sharma');
    }

    /**
     * Requirement 9: Amount displayed in modal is independent of Create Battle input.
     */
    public function test_modal_amount_strictly_matches_selected_battle()
    {
        // Create another battle with different entry fee (e.g. 350)
        $customBattle = LudoBattle::create([
            'battle_uid' => 'BAT-350-' . strtoupper(uniqid()),
            'creator_id' => $this->creator->id,
            'entry_fee' => 350,
            'prize' => 665,
            'status' => 'open',
            'game_type' => 'classic',
        ]);

        $response = $this->actingAs($this->sufficientPlayer)->get(route('ludo.battles'));

        // Modal for customBattle must have 350 and 665
        $response->assertSee('id="ludoPlayModal' . $customBattle->id . '"', false);
        $response->assertSee('data-entry-fee="350"', false);
        $response->assertSee('₹350');
        $response->assertSee('₹665');
    }
}
