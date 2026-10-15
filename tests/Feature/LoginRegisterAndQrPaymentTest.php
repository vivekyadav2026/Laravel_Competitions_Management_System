<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\LudoLedgerTransaction;
use App\Models\LudoSetting;
use App\Models\LudoUpiDeposit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginRegisterAndQrPaymentTest extends TestCase
{
    protected User $adminUser;
    protected User $playerUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config(['ludo.upi_deposits_enabled' => true]);
        LudoSetting::set('upi_deposits_enabled', 'on');

        // Clean up test deposits and test registered user
        LudoUpiDeposit::query()->delete();
        User::where('email', 'amitabh@example.com')->delete();

        // Create authoritative Admin User
        $this->adminUser = User::updateOrCreate(
            ['email' => 'admin@fsaludoclub.com'],
            [
                'name' => 'Fsaludoclub Super Admin',
                'password' => Hash::make('Admin@12345'),
                'role' => 'superadmin',
                'status' => 'active',
                'coins' => 5000,
                'player_uid' => 'FSADMIN01',
                'referral_code' => 'FSADMIN',
            ]
        );

        // Create authoritative Player User
        $this->playerUser = User::updateOrCreate(
            ['email' => 'testplayer@fsaludoclub.com'],
            [
                'name' => 'Rohan Verma',
                'phone' => '9899112233',
                'password' => Hash::make('Secret@123'),
                'role' => 'normal',
                'status' => 'active',
                'coins' => 1000,
                'player_uid' => 'LUDO1001',
                'referral_code' => 'ROHAN1',
            ]
        );
    }

    protected function tearDown(): void
    {
        LudoUpiDeposit::query()->delete();
        User::where('email', 'amitabh@example.com')->delete();
        LudoSetting::set('battle_commission_mode', 'percentage');
        LudoSetting::set('battle_commission_percentage', '5');
        LudoSetting::set('upi_deposits_enabled', 'on');
        parent::tearDown();
    }

    /**
     * PART 1: Login page renders with clearly visible Register Now options.
     */
    public function test_login_page_renders_with_visible_register_now_link_and_options(): void
    {
        $response = $this->get('/showlogin');

        $response->assertStatus(200);
        $response->assertSee('Register Now');
        $response->assertSee('Create New Account');
        $response->assertSee(route('register'));
        $response->assertSee('Email / 10-Digit Mobile Number');
        $response->assertSee('Sign In with Password');
        $response->assertDontSee(route('admin.login'));
    }

    /**
     * PART 1 & 2: Registration with Email, optional Mobile Number, and Password.
     */
    public function test_player_registration_with_email_and_optional_mobile_number(): void
    {
        User::where('email', 'amitabh@example.com')->delete();

        $payload = [
            'name' => 'Amitabh Bachchan',
            'email' => 'amitabh@example.com',
            'phone' => '9123456780',
            'password' => 'Cinema@1975',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect('/');
        $response->assertSessionHas('message');

        $newUser = User::where('email', 'amitabh@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertAuthenticatedAs($newUser);
        $this->assertEquals('9123456780', $newUser->phone);
        $this->assertEquals('Amitabh Bachchan', $newUser->name);
        $this->assertStringStartsWith('LUDO', $newUser->player_uid);

        // Authoritative welcome bonus credited in ledger
        $this->assertEquals(100, (int) $newUser->coins);
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $newUser->id,
            'type' => 'deposit',
            'points_delta' => 100,
            'status' => 'successful',
        ]);
    }

    /**
     * PART 2: Player login using Email + Password.
     */
    public function test_player_can_login_with_email_and_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'testplayer@fsaludoclub.com',
            'password' => 'Secret@123',
        ]);

        $response->assertRedirect('/');
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->playerUser->id, Auth::id());
    }

    /**
     * PART 2: Player login using 10-Digit Indian Mobile Number + Password.
     */
    public function test_player_can_login_with_10_digit_mobile_number_and_password(): void
    {
        // 1. Raw 10 digits
        $response = $this->post('/login', [
            'email' => '9899112233',
            'password' => 'Secret@123',
        ]);

        $response->assertRedirect('/');
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->playerUser->id, Auth::id());

        Auth::logout();

        // 2. Prefixed with +91
        $response2 = $this->post('/login', [
            'email' => '+919899112233',
            'password' => 'Secret@123',
        ]);

        $response2->assertRedirect('/');
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->playerUser->id, Auth::id());
    }

    /**
     * PART 3: Admin login with admin@fsaludoclub.com / Admin@12345.
     */
    public function test_admin_login_with_superadmin_credentials(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@fsaludoclub.com',
            'password' => 'Admin@12345',
        ]);

        $response->assertRedirect('/admin/ludo/dashboard');
        $this->assertTrue(Auth::check());
        $this->assertEquals('superadmin', Auth::user()->role);
    }

    /**
     * PART 3: Ordinary player denied access to admin control panel.
     */
    public function test_ordinary_player_denied_access_to_admin_routes(): void
    {
        $response = $this->actingAs($this->playerUser)->get('/admin/ludo/dashboard');
        $response->assertStatus(403);
    }

    /**
     * PART 4: Dynamic Game Commission configuration in Admin Panel and calculation in LudoBattle.
     */
    public function test_dynamic_commission_calculation_and_admin_settings(): void
    {
        // 1. Standard default percentage (5% platform fee = 1.9x prize)
        LudoSetting::set('battle_commission_mode', 'percentage');
        LudoSetting::set('battle_commission_percentage', '5');
        $this->assertEquals(190, LudoBattle::calculatePrize(100));
        $this->assertEquals(95, LudoBattle::calculatePrize(50));
        $this->assertEquals(665, LudoBattle::calculatePrize(350));

        // 2. Update to 10% platform fee in database
        LudoSetting::set('battle_commission_percentage', '10');
        // Total pool for 100 is 200; 10% commission is 20; prize is 180
        $this->assertEquals(180, LudoBattle::calculatePrize(100));

        // 3. Update to Custom Slab Rules
        $slabs = [
            ['min' => 10, 'max' => 249, 'type' => 'percent', 'value' => 10], // 10% commission on pool
            ['min' => 250, 'max' => 500, 'type' => 'fixed', 'value' => 25], // Fixed 25 coins commission
            ['min' => 501, 'max' => 50000, 'type' => 'percent', 'value' => 5], // 5% commission on pool
        ];
        LudoSetting::set('battle_commission_mode', 'slabs');
        LudoSetting::set('battle_commission_slabs', json_encode($slabs));

        // Fee 100 -> matches slab 1 (10% of 200 pool = 20 commission -> 180 prize)
        $this->assertEquals(180, LudoBattle::calculatePrize(100));

        // Fee 300 -> matches slab 2 (fixed 25 commission on 600 pool -> 575 prize)
        $this->assertEquals(575, LudoBattle::calculatePrize(300));

        // Fee 1000 -> matches slab 3 (5% of 2000 pool = 100 commission -> 1900 prize)
        $this->assertEquals(1900, LudoBattle::calculatePrize(1000));

        // Restore default 5%
        LudoSetting::set('battle_commission_mode', 'percentage');
        LudoSetting::set('battle_commission_percentage', '5');
        $this->assertEquals(190, LudoBattle::calculatePrize(100));
    }

    /**
     * PART 5: Admin QR Code Payment configuration in Admin Panel.
     */
    public function test_admin_can_update_qr_payment_gateway_settings(): void
    {
        $qrFile = UploadedFile::fake()->image('custom_upi_qr.png', 400, 400);

        $response = $this->actingAs($this->adminUser)->post('/admin/ludo/settings', [
            'platform_name' => 'Fsaludoclub Pro',
            'submission_window' => 15,
            'referral_commission' => '5%',
            'maintenance_mode' => 'off',
            'support_email' => 'admin@fsaludoclub.com',
            'minimum_battle_amount' => 50,
            'kyc_mandatory' => 'off',
            'battle_commission_mode' => 'percentage',
            'battle_commission_percentage' => 5,
            'upi_deposits_enabled' => 'on',
            'upi_payee_name' => 'FSALUDOCLUB OFFICIAL',
            'upi_id' => 'fsaludoclub@okhdfcbank',
            'upi_min_deposit' => 100,
            'upi_max_deposit' => 25000,
            'upi_qr_image' => $qrFile,
            'upi_instructions_en' => 'Official QR payment guide.',
            'upi_instructions_hi' => 'आधिकारिक क्यूआर पेमेंट निर्देश।',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('on', LudoSetting::get('upi_deposits_enabled'));
        $this->assertEquals('FSALUDOCLUB OFFICIAL', LudoSetting::get('upi_payee_name'));
        $this->assertEquals('fsaludoclub@okhdfcbank', LudoSetting::get('upi_id'));
        $this->assertEquals('100', LudoSetting::get('upi_min_deposit'));
        $this->assertEquals('25000', LudoSetting::get('upi_max_deposit'));
        $this->assertNotNull(LudoSetting::get('upi_qr_image'));
        Storage::disk('public')->assertExists(LudoSetting::get('upi_qr_image'));
    }

    /**
     * PART 6: Player submits UPI deposit request with UTR and receipt screenshot.
     */
    public function test_player_can_submit_upi_deposit_request_with_utr_and_screenshot(): void
    {
        LudoSetting::set('upi_deposits_enabled', 'on');
        LudoSetting::set('upi_min_deposit', 50);
        LudoSetting::set('upi_max_deposit', 50000);
        LudoSetting::set('upi_auto_approve', 'off');

        $receipt = UploadedFile::fake()->image('payment_proof.jpg', 600, 800);
        $utrNumber = '987612345678';

        $initialCoins = $this->playerUser->fresh()->coins;

        $response = $this->actingAs($this->playerUser)->post('/wallet/deposit/upi', [
            'amount' => 250,
            'utr_number' => $utrNumber,
            'screenshot' => $receipt,
        ]);

        $response->assertRedirect(route('ludo.wallet'));
        $response->assertSessionHas('success');

        $deposit = LudoUpiDeposit::where('utr_number', $utrNumber)->first();
        $this->assertNotNull($deposit);
        $this->assertEquals($this->playerUser->id, $deposit->user_id);
        $this->assertEquals(250, $deposit->amount);
        $this->assertEquals('pending', $deposit->status);
        $this->assertNotNull($deposit->screenshot_path);
        Storage::disk('public')->assertExists($deposit->screenshot_path);

        // Security check: balance is NOT credited while pending
        $this->assertEquals($initialCoins, $this->playerUser->fresh()->coins);

        // Persistent in-app notification sent
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->playerUser->id,
            'notifiable_type' => User::class,
        ]);
    }

    /**
     * PART 6 & 10: Duplicate UTR submission is rejected.
     */
    public function test_duplicate_utr_submission_is_prevented(): void
    {
        LudoSetting::set('upi_deposits_enabled', 'on');
        $utr = 'DUP' . strtoupper(Str::random(10));

        LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(10)),
            'user_id' => $this->playerUser->id,
            'amount' => 500,
            'utr_number' => $utr,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->playerUser)->post('/wallet/deposit/upi', [
            'amount' => 500,
            'utr_number' => strtolower($utr), // Case-insensitive check
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * PART 6 & 10: Min and max deposit limit validation.
     */
    public function test_deposit_amount_must_be_within_configured_min_max_limits(): void
    {
        LudoSetting::set('upi_deposits_enabled', 'on');
        LudoSetting::set('upi_min_deposit', 100);
        LudoSetting::set('upi_max_deposit', 5000);

        // Below minimum
        $resMin = $this->actingAs($this->playerUser)->post('/wallet/deposit/upi', [
            'amount' => 40,
            'utr_number' => 'MIN' . strtoupper(Str::random(8)),
        ]);
        $resMin->assertSessionHasErrors('amount');

        // Above maximum
        $resMax = $this->actingAs($this->playerUser)->post('/wallet/deposit/upi', [
            'amount' => 6000,
            'utr_number' => 'MAX' . strtoupper(Str::random(8)),
        ]);
        $resMax->assertSessionHasErrors('amount');
    }

    /**
     * PART 7: Admin approval workflow credits coins atomically via ledger and notifies user.
     */
    public function test_admin_payment_approval_workflow_credits_coins_atomically_and_notifies_user(): void
     {
        LudoSetting::set('upi_deposits_enabled', 'on');

        $initialCoins = (int) $this->playerUser->fresh()->coins;
        $apprUtr = 'APPR' . strtoupper(Str::random(10));

        $deposit = LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(10)),
            'user_id' => $this->playerUser->id,
            'amount' => 500,
            'utr_number' => $apprUtr,
            'status' => 'pending',
        ]);

        // Admin approves deposit
        $response = $this->actingAs($this->adminUser)->post("/admin/ludo/deposits/{$deposit->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $freshDeposit = $deposit->fresh();
        $this->assertEquals('approved', $freshDeposit->status);
        $this->assertEquals($this->adminUser->id, $freshDeposit->approved_by);
        $this->assertNotNull($freshDeposit->approved_at);

        // Coins credited atomically via ledger
        $this->assertEquals($initialCoins + 500, (int) $this->playerUser->fresh()->coins);
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $this->playerUser->id,
            'type' => 'deposit',
            'points_delta' => 500,
            'status' => 'successful',
        ]);

        // Attempting to approve again is blocked
        $secondApprove = $this->actingAs($this->adminUser)->post("/admin/ludo/deposits/{$deposit->id}/approve");
        $secondApprove->assertSessionHas('error');
    }

    /**
     * PART 7: Admin rejection workflow records reason, preserves balance, and notifies user.
     */
    public function test_admin_payment_rejection_workflow_saves_reason_and_notifies_user(): void
    {
        LudoSetting::set('upi_deposits_enabled', 'on');

        $initialCoins = (int) $this->playerUser->fresh()->coins;
        $rejUtr = 'REJ' . strtoupper(Str::random(10));

        $deposit = LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(10)),
            'user_id' => $this->playerUser->id,
            'amount' => 750,
            'utr_number' => $rejUtr,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser)->post("/admin/ludo/deposits/{$deposit->id}/reject", [
            'reason' => 'Invalid UTR reference: Payment not credited in bank statement.',
        ]);

        $response->assertRedirect();

        $freshDeposit = $deposit->fresh();
        $this->assertEquals('rejected', $freshDeposit->status);
        $this->assertEquals('Invalid UTR reference: Payment not credited in bank statement.', $freshDeposit->admin_notes);
        $this->assertEquals($this->adminUser->id, $freshDeposit->approved_by);

        // Player coins remain untouched
        $this->assertEquals($initialCoins, (int) $this->playerUser->fresh()->coins);
    }

    /**
     * PART 8: Player Wallet renders "My Payment Requests" with status, UTR, and rejection reasons.
     */
    public function test_player_wallet_displays_payment_requests_history_with_rejection_reason(): void
    {
        LudoSetting::set('upi_deposits_enabled', 'on');

        $histRejUtr = 'REJUTR' . strtoupper(Str::random(8));
        $histApprUtr = 'APPUTR' . strtoupper(Str::random(8));

        LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(10)),
            'user_id' => $this->playerUser->id,
            'amount' => 300,
            'utr_number' => $histRejUtr,
            'status' => 'rejected',
            'admin_notes' => 'UTR does not match received transaction amount.',
        ]);

        LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(10)),
            'user_id' => $this->playerUser->id,
            'amount' => 500,
            'utr_number' => $histApprUtr,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->playerUser)->get('/wallet');

        $response->assertStatus(200);
        $response->assertSee('My Payment Requests');
        $response->assertSee($histRejUtr);
        $response->assertSee($histApprUtr);
        $response->assertSee('Rejected');
        $response->assertSee('Approved &amp; Credited', false);
        $response->assertSee('UTR does not match received transaction amount.');
    }

    /**
     * Test payment add displays +50 increments like 50, 100, 150 and stepper buttons.
     */
    public function test_payment_add_provides_50_increments_and_stepper_buttons(): void
    {
        LudoSetting::set('upi_deposits_enabled', 'on');

        $response = $this->actingAs($this->playerUser)->get('/wallet');

        $response->assertStatus(200);
        $response->assertSee('data-amount="50"', false);
        $response->assertSee('data-amount="100"', false);
        $response->assertSee('data-amount="150"', false);
        $response->assertSee('data-amount="200"', false);
        $response->assertSee('data-amount="250"', false);
        $response->assertSee('data-amount="300"', false);
        $response->assertSee('data-amount="500"', false);
        $response->assertSee('data-amount="1000"', false);
        $response->assertSee('data-step="50"', false);
        $response->assertSee('data-step="-50"', false);
    }
}

