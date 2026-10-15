<?php

namespace Tests\Feature;

use App\Models\LudoAdminAuditLog;
use App\Models\LudoBattle;
use App\Models\LudoLedgerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WalletAuthAndBalanceResetTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected User $playerUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure superadmin user exists
        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@fsaludoclub.com'],
            [
                'name' => 'Fsaludoclub Super Admin',
                'password' => Hash::make('Admin@12345'),
                'role' => 'superadmin',
                'status' => 'active',
                'coins' => 0,
                'player_uid' => 'FSADMIN01',
                'referral_code' => 'FSADMIN',
            ]
        );

        // Ensure standard test player exists
        $this->playerUser = User::firstOrCreate(
            ['email' => 'testplayer_wallet@fsaludoclub.com'],
            [
                'name' => 'Test Wallet Player',
                'phone' => '9988776655',
                'password' => Hash::make('Secret@1234'),
                'role' => 'normal',
                'status' => 'active',
                'coins' => 0,
                'referral_balance' => 0.00,
                'player_uid' => 'LUDO99887',
                'referral_code' => 'TESTW1',
            ]
        );
    }

    /**
     * PART 1: Guest accessing wallet browser page redirects to Login with message and preserves intended URL.
     */
    public function test_guest_access_to_wallet_pages_redirects_to_login_with_message(): void
    {
        Auth::logout();

        $response = $this->get('/wallet');

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('message', 'Please log in to continue.');
        $this->assertEquals(url('/wallet'), session('url.intended'));
    }

    /**
     * PART 1: Guest submitting wallet forms via browser redirects to Login with message.
     */
    public function test_guest_wallet_form_submissions_redirect_to_login_with_message(): void
    {
        Auth::logout();

        // 1. Virtual reload form submission
        $response1 = $this->post('/wallet/deposit', ['amount' => 100]);
        $response1->assertStatus(302);
        $response1->assertRedirect(route('login'));
        $response1->assertSessionHas('message', 'Please log in to continue.');

        // 2. UPI deposit form submission
        $response2 = $this->post('/wallet/deposit/upi', [
            'amount' => 100,
            'utr_number' => '428912345678',
        ]);
        $response2->assertStatus(302);
        $response2->assertRedirect(route('login'));
        $response2->assertSessionHas('message', 'Please log in to continue.');

        // 3. Withdrawal request submission
        $response3 = $this->post('/wallet/withdraw', [
            'amount' => 100,
            'payout_method' => 'upi',
            'upi_id' => 'player@upi',
        ]);
        $response3->assertStatus(302);
        $response3->assertRedirect(route('login'));
        $response3->assertSessionHas('message', 'Please log in to continue.');
    }

    /**
     * PART 1: Guest AJAX/JSON requests return HTTP 401 with structured JSON and redirect route.
     */
    public function test_guest_wallet_and_balance_ajax_requests_return_401_json_with_redirect(): void
    {
        Auth::logout();

        // 1. GET /wallet expecting JSON
        $response1 = $this->getJson('/wallet');
        $response1->assertStatus(401);
        $response1->assertJson([
            'success' => false,
            'message' => 'Please log in to continue.',
            'redirect' => route('login'),
        ]);

        // 2. POST /wallet/deposit expecting JSON
        $response2 = $this->postJson('/wallet/deposit', ['amount' => 100]);
        $response2->assertStatus(401);
        $response2->assertJson([
            'success' => false,
            'message' => 'Please log in to continue.',
            'redirect' => route('login'),
        ]);

        // 3. POST /battles/create expecting JSON
        $response3 = $this->postJson('/battles/create', ['amount' => 100]);
        $response3->assertStatus(401);
        $response3->assertJson([
            'success' => false,
            'message' => 'Please log in to continue.',
            'redirect' => route('login'),
        ]);

        // 4. POST /battles/join/1 expecting JSON
        $response4 = $this->postJson('/battles/join/99999');
        $response4->assertStatus(401);
        $response4->assertJson([
            'success' => false,
            'message' => 'Please log in to continue.',
            'redirect' => route('login'),
        ]);
    }

    /**
     * PART 1: Authenticated player wallet routes continue working normally.
     */
    public function test_authenticated_wallet_routes_continue_working(): void
    {
        $response = $this->actingAs($this->playerUser)->get('/wallet');

        $response->assertStatus(200);
        $response->assertSee('TOTAL WALLET BALANCE');
        $response->assertSee('AVAILABLE COINS');
        $response->assertSee('REFERRAL COMMISSION');
    }

    /**
     * PART 1: Successful login redirects to safe intended destination and sanitizes admin paths for players.
     */
    public function test_successful_login_redirects_to_intended_safe_url_and_sanitizes_admin_paths(): void
    {
        Auth::logout();

        // Scenario A: Safe intended player page (e.g. /wallet)
        session(['url.intended' => url('/wallet')]);

        $response = $this->post('/login', [
            'email' => $this->playerUser->email,
            'password' => 'Secret@1234',
        ]);

        $response->assertRedirect(url('/wallet'));
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->playerUser->id, Auth::id());

        Auth::logout();

        // Scenario B: Unsafe intended admin page for normal player (e.g. /admin/ludo/dashboard)
        session(['url.intended' => url('/admin/ludo/dashboard')]);

        $response = $this->post('/login', [
            'email' => $this->playerUser->email,
            'password' => 'Secret@1234',
        ]);

        // Sanitized: must redirect to player home '/' and NOT the admin dashboard
        $response->assertRedirect(url('/'));
    }

    /**
     * PART 2: Balance reset sets all targeted account balances to zero.
     */
    public function test_all_targeted_account_balances_are_zero_after_reset(): void
    {
        // Credit temporary coins to player
        $this->playerUser->coins = 7500;
        $this->playerUser->referral_balance = 250.00;
        $this->playerUser->save();

        $this->assertGreaterThan(0, $this->playerUser->fresh()->coins);

        // Run balance reset command
        Artisan::call('ludo:reset-wallet-balances', ['--force' => true]);

        // Assert player balances are zero
        $freshPlayer = $this->playerUser->fresh();
        $this->assertEquals(0, $freshPlayer->coins);
        $this->assertEquals(0.00, (float) $freshPlayer->referral_balance);

        // Assert total circulating coins in entire database is 0
        $this->assertEquals(0, (int) User::sum('coins'));
        $this->assertEquals(0.00, (float) User::sum('referral_balance'));
    }

    /**
     * PART 2 & 3: Historical transactions remain intact with valid ledger adjustment entry.
     */
    public function test_historical_transactions_remain_intact_with_formal_ledger_adjustment(): void
    {
        // Add historical deposit to player
        LudoLedgerTransaction::recordAdjustment(
            $this->playerUser,
            1200,
            'deposit',
            'Historical tournament test reload',
            'successful'
        );

        $this->assertEquals(1200, $this->playerUser->fresh()->coins);

        // Run balance reset command
        Artisan::call('ludo:reset-wallet-balances', ['--force' => true]);

        // Fresh balance must be 0
        $this->assertEquals(0, $this->playerUser->fresh()->coins);

        // Historical deposit must still exist
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $this->playerUser->id,
            'type' => 'deposit',
            'points_delta' => 1200,
            'status' => 'successful',
        ]);

        // Reset ledger adjustment entry must exist
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $this->playerUser->id,
            'type' => 'admin_adjustment',
            'points_delta' => -1200,
            'balance_before' => 1200,
            'balance_after' => 0,
            'status' => 'successful',
            'description' => 'System-wide wallet balance reset to zero',
        ]);

        // Audit log must be recorded
        $this->assertDatabaseHas('ludo_admin_audit_logs', [
            'action_type' => 'wallet_balance_reset',
            'target_type' => 'User',
            'target_id' => $this->playerUser->id,
        ]);
    }

    /**
     * PART 2 & 4: Resetting twice is idempotent and does not create duplicate ledger adjustments.
     */
    public function test_reset_command_is_idempotent_and_creates_zero_duplicate_adjustments(): void
    {
        // First reset
        Artisan::call('ludo:reset-wallet-balances', ['--force' => true]);

        $ledgerCountBefore = LudoLedgerTransaction::count();

        // Second reset
        Artisan::call('ludo:reset-wallet-balances', ['--force' => true]);

        $ledgerCountAfter = LudoLedgerTransaction::count();

        // Must not create any new ledger records
        $this->assertEquals($ledgerCountBefore, $ledgerCountAfter);
        $this->assertEquals(0, (int) User::sum('coins'));
    }

    /**
     * PART 3: Admin Panel displays zero from actual database aggregates.
     */
    public function test_admin_panel_displays_zero_from_actual_database_queries(): void
    {
        // Ensure all balances are zero
        Artisan::call('ludo:reset-wallet-balances', ['--force' => true]);

        // 1. Admin Overview Dashboard
        $dashboardResponse = $this->actingAs($this->adminUser)->get('/admin/ludo/dashboard');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('🪙 0');

        // 2. Admin User Management index
        $usersResponse = $this->actingAs($this->adminUser)->get('/admin/ludo/users');
        $usersResponse->assertStatus(200);

        // 3. Admin Ledger desk
        $ledgerResponse = $this->actingAs($this->adminUser)->get('/admin/ludo/ledger');
        $ledgerResponse->assertStatus(200);
        $ledgerResponse->assertSee('Player Wallet Balances');
        $ledgerResponse->assertSee('🪙 0');
    }
}
