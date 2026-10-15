<?php

namespace Tests\Feature;

use App\Helpers\IndianNames;
use App\Models\LudoBattle;
use App\Models\LudoLedgerTransaction;
use App\Models\LudoUpiDeposit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MalikLudoCompleteWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $creator;
    protected User $opponent;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test Indian players
        $this->creator = User::create([
            'name' => 'Rahul Sharma',
            'email' => 'rahul_' . Str::random(6) . '@example.com',
            'password' => bcrypt('password123'),
            'phone' => '987654' . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT),
            'role' => 'player',
            'coins' => 1000,
            'is_phone_verified' => true,
        ]);

        $this->opponent = User::create([
            'name' => 'Aman Verma',
            'email' => 'aman_' . Str::random(6) . '@example.com',
            'password' => bcrypt('password123'),
            'phone' => '987655' . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT),
            'role' => 'player',
            'coins' => 1000,
            'is_phone_verified' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Vikram Admin',
            'email' => 'admin_' . Str::random(6) . '@example.com',
            'password' => bcrypt('admin123'),
            'phone' => '987656' . str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT),
            'role' => 'admin',
            'coins' => 5000,
            'is_phone_verified' => true,
        ]);

        config(['ludo.upi_deposits_enabled' => true]);
        \App\Models\LudoSetting::set('upi_deposits_enabled', 'on');
    }

    /**
     * Test UPI deposit rejection when feature flag is disabled (Non-Cash compliance).
     */
    public function test_upi_deposit_is_rejected_when_feature_flag_is_disabled()
    {
        config(['ludo.upi_deposits_enabled' => false]);
        \App\Models\LudoSetting::set('upi_deposits_enabled', 'off');

        $response = $this->actingAs($this->creator)->post('/wallet/deposit/upi', [
            'amount' => 500,
            'utr_number' => 'UTR12345678',
        ]);

        $response->assertRedirect(route('ludo.wallet'));
        $response->assertSessionHas('error');
    }

    /**
     * Test Indian Names Helper integrity.
     */
    public function test_indian_names_helper_provides_authentic_names()
    {
        $names = IndianNames::all();
        $this->assertIsArray($names);
        $this->assertGreaterThan(50, count($names));

        $randomName = IndianNames::getRandomName();
        $this->assertIsString($randomName);
        $this->assertNotEmpty($randomName);
        $this->assertContains($randomName, $names);
    }

    /**
     * Test Direct UPI QR Deposit Submission & Verification.
     */
    public function test_player_can_submit_upi_deposit_utr()
    {
        $utr = 'UTR' . strtoupper(Str::random(10));

        $response = $this->actingAs($this->creator)->post('/wallet/deposit/upi', [
            'amount' => 500,
            'utr_number' => $utr,
        ]);

        $response->assertRedirect(route('ludo.wallet'));

        $this->assertDatabaseHas('ludo_upi_deposits', [
            'user_id' => $this->creator->id,
            'amount' => 500,
            'utr_number' => $utr,
            'status' => 'pending',
        ]);
    }

    /**
     * Test Duplicate UTR submission rejection.
     */
    public function test_duplicate_utr_submission_is_rejected()
    {
        $utr = 'UTR' . strtoupper(Str::random(10));

        LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-TEST1',
            'user_id' => $this->creator->id,
            'amount' => 250,
            'utr_number' => $utr,
            'upi_id' => '9631608403@paytm',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->opponent)->post('/wallet/deposit/upi', [
            'amount' => 250,
            'utr_number' => $utr,
        ]);

        $response->assertSessionHas('error');
    }

    /**
     * Test Admin Approval of Direct UPI Deposit credits wallet points.
     */
    public function test_admin_can_approve_upi_deposit()
    {
        $utr = 'UTR' . strtoupper(Str::random(10));
        $initialCoins = $this->creator->coins;

        $deposit = LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(8)),
            'user_id' => $this->creator->id,
            'amount' => 300,
            'utr_number' => $utr,
            'upi_id' => '9631608403@paytm',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/ludo/deposits/{$deposit->id}/approve");

        $response->assertSessionHas('success');

        $deposit->refresh();
        $this->assertEquals('approved', $deposit->status);
        $this->assertEquals($this->admin->id, $deposit->approved_by);

        $this->creator->refresh();
        $this->assertEquals($initialCoins + 300, $this->creator->coins);

        // Check ledger transaction recorded
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $this->creator->id,
            'type' => 'deposit',
            'points_delta' => 300,
            'status' => 'successful',
        ]);
    }

    /**
     * Test Admin Rejection of Direct UPI Deposit with reason.
     */
    public function test_admin_can_reject_upi_deposit_with_reason()
    {
        $deposit = LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(8)),
            'user_id' => $this->creator->id,
            'amount' => 100,
            'utr_number' => 'FAKE12345678',
            'upi_id' => '9631608403@paytm',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/ludo/deposits/{$deposit->id}/reject", [
            'reason' => 'Payment not reflected in bank account',
        ]);

        $response->assertSessionHas('success');

        $deposit->refresh();
        $this->assertEquals('rejected', $deposit->status);
        $this->assertEquals('Payment not reflected in bank account', $deposit->rejection_reason);
    }

    /**
     * Test Complete Game Match Flow matching Screenshots:
     * 1. Creator sets battle (50 -> 95 prize)
     * 2. Opponent joins -> requested status
     * 3. Creator starts match -> running status
     * 4. Upload Ludo King room code
     * 5. Match arena renders
     * 6. Cancel with 7 reasons modal refunds coins
     */
    public function test_complete_game_workflow_matches_screenshots()
    {
        // 1. Creator creates battle for 100 points
        $response = $this->actingAs($this->creator)->post('/battles/create', [
            'amount' => 100,
        ]);
        $response->assertSessionHas('success');

        $battle = LudoBattle::where('creator_id', $this->creator->id)
            ->where('status', 'open')
            ->first();

        $this->assertNotNull($battle);
        $this->assertEquals(100, $battle->entry_fee);
        $this->assertEquals(190, $battle->prize); // 1.9x ratio matches screenshots (100 -> 190)

        // Creator coins deducted
        $this->creator->refresh();
        $this->assertEquals(900, $this->creator->coins);

        // 2. Opponent joins battle
        $response = $this->actingAs($this->opponent)->post("/battles/{$battle->id}/join");
        $response->assertRedirect(route('ludo.battles.view', $battle->id));

        $battle->refresh();
        $this->assertEquals('requested', $battle->status);
        $this->assertEquals($this->opponent->id, $battle->joiner_id);

        // Opponent coins deducted
        $this->opponent->refresh();
        $this->assertEquals(900, $this->opponent->coins);

        // 3. Creator clicks START
        $response = $this->actingAs($this->creator)->post("/battles/{$battle->id}/start");
        $response->assertRedirect(route('ludo.battles.view', $battle->id));

        $battle->refresh();
        $this->assertEquals('running', $battle->status);
        $this->assertNotNull($battle->room_code_expires_at);

        // 4. Set Ludo King Room Code
        $response = $this->actingAs($this->creator)->post("/battles/{$battle->id}/room-code", [
            'room_code' => '05839201',
        ]);
        $response->assertSessionHas('success');

        $battle->refresh();
        $this->assertEquals('05839201', $battle->room_code);

        // 5. View Match Arena Screen (Screenshot #4)
        $response = $this->actingAs($this->creator)->get("/battles/{$battle->id}");
        $response->assertStatus(200);
        $response->assertSee('05839201');
        $response->assertSee('Rahul Sharma');
        $response->assertSee('Aman Verma');
        $response->assertSee('Cancel Match');
        $response->assertSee('Ludo King');

        // 6. Cancel match with specific reason (Screenshot #5 Reason: "Opponent Abusing")
        $response = $this->actingAs($this->opponent)->post("/battles/{$battle->id}/cancel-with-reason", [
            'reason' => 'Opponent Abusing',
        ]);
        $response->assertRedirect(route('ludo.battles'));

        $battle->refresh();
        $this->assertEquals('cancelled', $battle->status);
        $this->assertEquals('Opponent Abusing', $battle->cancellation_reason);

        // Both players refunded their entry coins (900 + 100 = 1000)
        $this->creator->refresh();
        $this->opponent->refresh();
        $this->assertEquals(1000, $this->creator->coins);
        $this->assertEquals(1000, $this->opponent->coins);
    }
}
