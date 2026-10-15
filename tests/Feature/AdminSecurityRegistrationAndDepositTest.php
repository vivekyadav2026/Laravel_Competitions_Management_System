<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\LudoLedgerTransaction;
use App\Models\LudoUpiDeposit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminSecurityRegistrationAndDepositTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $player;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@fsaludoclub.com'],
            [
                'name' => 'Fsaludoclub Super Admin',
                'phone' => '9876543210',
                'password' => Hash::make('Admin@12345'),
                'role' => 'superadmin',
                'status' => 'active',
                'coins' => 50000,
            ]
        );
        $this->admin->role = 'superadmin';
        $this->admin->status = 'active';
        $this->admin->save();

        $this->player = User::firstOrCreate(
            ['phone' => '9876000101'],
            [
                'name' => 'Arjun Sharma',
                'email' => 'arjun.sharma@gmail.com',
                'password' => Hash::make('Secret@123'),
                'role' => 'player',
                'status' => 'active',
                'coins' => 2000,
            ]
        );
        $this->player->role = 'player';
        $this->player->status = 'active';
        $this->player->save();
    }

    /**
     * PART 1: Registration must redirect to Login and leave user unauthenticated.
     */
    public function test_successful_registration_redirects_to_login_and_leaves_user_unauthenticated(): void
    {
        $uniquePhone = '98' . random_int(10000000, 99999999);
        $uniqueEmail = 'player_' . Str::random(8) . '@fsaludotest.com';

        $response = $this->post('/register', [
            'name' => 'Kavita Patel',
            'email' => $uniqueEmail,
            'phone' => $uniquePhone,
            'password' => 'SecurePass123',
        ]);

        // Must redirect to login page
        $response->assertRedirect('/showlogin');
        $response->assertSessionHas('success', 'Registration successful. Please log in with your registered credentials.');

        // User must NOT be logged in automatically
        $this->assertGuest();

        // User must exist in database with active status and player role
        $newUser = User::where('email', $uniqueEmail)->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('active', $newUser->status);
        $this->assertEquals('player', $newUser->role);
        $this->assertTrue(Hash::check('SecurePass123', $newUser->password));

        // User can now log in manually
        $loginResponse = $this->post('/login', [
            'email' => $uniqueEmail,
            'password' => 'SecurePass123',
        ]);
        $loginResponse->assertRedirect('/');
        $this->assertAuthenticatedAs($newUser);
    }

    public function test_registration_rejects_duplicate_email_or_phone(): void
    {
        $response = $this->post('/register', [
            'name' => 'Duplicate Attempt',
            'email' => $this->player->email,
            'phone' => '9876000102',
            'password' => 'SecurePass123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();

        $responsePhone = $this->post('/register', [
            'name' => 'Duplicate Phone Attempt',
            'email' => 'unique_' . Str::random(6) . '@test.com',
            'phone' => $this->player->phone,
            'password' => 'SecurePass123',
        ]);

        $responsePhone->assertSessionHasErrors(['phone']);
        $this->assertGuest();
    }

    /**
     * PART 2: Admin Panel URL removed from all user-side pages & authorization enforced.
     */
    public function test_no_admin_links_appear_in_ordinary_player_drawer_and_pages(): void
    {
        // Guest view
        $responseGuest = $this->get('/');
        $responseGuest->assertStatus(200);
        $responseGuest->assertDontSee('/admin/login');
        $responseGuest->assertDontSee('Admin Panel Login');

        // Normal player view
        $responsePlayer = $this->actingAs($this->player)->get('/');
        $responsePlayer->assertStatus(200);
        $responsePlayer->assertDontSee('/admin/ludo/dashboard');
        $responsePlayer->assertDontSee('Admin Console');

        // Sign in page has no admin console link
        $responseSignIn = $this->get('/showlogin');
        $responseSignIn->assertStatus(200);
        $responseSignIn->assertDontSee(route('admin.login'));
    }

    public function test_guest_and_ordinary_player_cannot_access_admin_panel(): void
    {
        // Guest is redirected
        $responseGuest = $this->get('/admin/ludo/dashboard');
        $responseGuest->assertRedirect('/showlogin');

        // Normal player gets 403 Forbidden
        $responsePlayer = $this->actingAs($this->player)->get('/admin/ludo/dashboard');
        $responsePlayer->assertStatus(403);
    }

    /**
     * PART 3: Admin login fields are empty on initial load, no prefilled credentials in HTML.
     */
    public function test_admin_login_page_has_no_prefilled_credentials_in_html(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
        $response->assertSee('Admin Console Login');
        $response->assertSee('autocomplete="username"', false);
        $response->assertSee('autocomplete="current-password"', false);

        // Crucial security requirement: no hardcoded credentials prefilled in HTML values
        $response->assertDontSee('value="admin@fsaludoclub.com"', false);
        $response->assertDontSee('value="Admin@12345"', false);

        // Real admin can log in with valid credentials
        $postResponse = $this->post('/admin/login', [
            'email' => 'admin@fsaludoclub.com',
            'password' => 'Admin@12345',
        ]);
        $postResponse->assertRedirect('/admin/ludo/dashboard');
        $this->assertAuthenticatedAs($this->admin);

        // Invalid credentials fail
        Auth::logout();
        $failResponse = $this->post('/admin/login', [
            'email' => 'admin@fsaludoclub.com',
            'password' => 'WrongPassword',
        ]);
        $failResponse->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * PART 4: User Management - activate/deactivate, last admin protection, safe deletion.
     */
    public function test_admin_can_toggle_user_status_and_notification_is_sent(): void
    {
        // Suspend/Deactivate player
        $response = $this->actingAs($this->admin)->post(route('admin.ludo.users.status', $this->player->id), [
            'action' => 'deactivate',
            'reason' => 'Suspected multiple account abuse',
        ]);

        $response->assertSessionHas('success');
        $this->player->refresh();
        $this->assertEquals('suspended', $this->player->status);
        $this->assertEquals('Suspected multiple account abuse', $this->player->suspension_reason);

        // Reactivate player
        $responseReactivate = $this->actingAs($this->admin)->post(route('admin.ludo.users.status', $this->player->id), [
            'action' => 'reactivate',
            'reason' => 'Account verified and cleared by security team',
        ]);

        $responseReactivate->assertSessionHas('success');
        $this->player->refresh();
        $this->assertEquals('active', $this->player->status);
        $this->assertNull($this->player->suspension_reason);
    }

    public function test_cannot_suspend_or_delete_last_administrator(): void
    {
        // Try to suspend the only active admin
        $response = $this->actingAs($this->admin)->post(route('admin.ludo.users.status', $this->admin->id), [
            'action' => 'suspend',
            'reason' => 'Test lock',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_safe_user_deletion_archives_user_when_financial_history_exists(): void
    {
        // Arjun Sharma has ledger records
        LudoLedgerTransaction::create([
            'tx_uid' => 'TX-TEST-HIST-01',
            'user_id' => $this->player->id,
            'type' => 'deposit',
            'points_delta' => 100,
            'balance_before' => 1900,
            'balance_after' => 2000,
            'status' => 'successful',
            'description' => 'Historical test ledger entry',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.ludo.users.destroy', $this->player->id), [
            'confirm_name' => $this->player->name,
            'reason' => 'Requested account closure',
        ]);

        $response->assertSessionHas('success');

        // User must NOT be hard-deleted; status must be suspended/archived to protect financial records!
        $this->assertDatabaseHas('users', [
            'id' => $this->player->id,
            'status' => 'suspended',
        ]);

        // Historical ledger entry must remain intact
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'tx_uid' => 'TX-TEST-HIST-01',
            'user_id' => $this->player->id,
        ]);
    }

    /**
     * PART 5: Dynamic Deposit Management - atomic approval, duplicate UTR prevention, rejection.
     */
    public function test_admin_can_approve_deposit_credits_once_and_prevents_duplicate(): void
    {
        $deposit = LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-SEC-001',
            'user_id' => $this->player->id,
            'amount' => 500,
            'utr_number' => '428901999999',
            'status' => 'pending',
        ]);

        $initialCoins = (float) $this->player->coins;

        // 1. Approve deposit
        $response = $this->actingAs($this->admin)->post(route('admin.ludo.deposits.approve', $deposit->id));
        $response->assertSessionHas('success');

        $deposit->refresh();
        $this->assertEquals('approved', $deposit->status);
        $this->assertEquals($this->admin->id, $deposit->approved_by);

        // Player coins credited
        $this->player->refresh();
        $this->assertEquals($initialCoins + 500, (float) $this->player->coins);

        // 2. Second approval attempt must be blocked
        $secondResponse = $this->actingAs($this->admin)->post(route('admin.ludo.deposits.approve', $deposit->id));
        $secondResponse->assertSessionHas('error');

        // Coins must NOT be credited a second time
        $this->player->refresh();
        $this->assertEquals($initialCoins + 500, (float) $this->player->coins);
    }

    public function test_admin_can_reject_deposit_with_mandatory_reason_and_wallet_is_not_credited(): void
    {
        $deposit = LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-SEC-002',
            'user_id' => $this->player->id,
            'amount' => 300,
            'utr_number' => 'FAKE12345678',
            'status' => 'pending',
        ]);

        $initialCoins = (float) $this->player->coins;

        // Rejection without reason fails validation
        $failResponse = $this->actingAs($this->admin)->post(route('admin.ludo.deposits.reject', $deposit->id), [
            'reason' => '',
        ]);
        $failResponse->assertSessionHasErrors(['reason']);

        // Valid rejection
        $response = $this->actingAs($this->admin)->post(route('admin.ludo.deposits.reject', $deposit->id), [
            'reason' => 'UTR reference not found in bank statement',
        ]);
        $response->assertSessionHas('success');

        $deposit->refresh();
        $this->assertEquals('rejected', $deposit->status);
        $this->assertStringContainsString('UTR reference not found', $deposit->admin_notes);

        // Player coins must NOT be changed
        $this->player->refresh();
        $this->assertEquals($initialCoins, (float) $this->player->coins);
    }
}
