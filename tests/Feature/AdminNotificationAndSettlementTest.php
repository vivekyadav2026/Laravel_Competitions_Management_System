<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\LudoBattleDispute;
use App\Models\LudoDispute;
use App\Models\LudoKycSubmission;
use App\Models\LudoLedgerTransaction;
use App\Models\LudoSupportTicket;
use App\Models\LudoUpiDeposit;
use App\Models\LudoWithdrawalRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminNotificationAndSettlementTest extends TestCase
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
                'name' => 'Fsaludo Administrator',
                'phone' => '9876543210',
                'password' => bcrypt('Admin@12345'),
                'role' => 'admin',
                'status' => 'active',
                'coins' => 50000.00,
            ]
        );
        $this->admin->role = 'admin';
        $this->admin->status = 'active';
        $this->admin->save();

        $this->player = User::firstOrCreate(
            ['phone' => '9876000101'],
            [
                'name' => 'Arjun Sharma',
                'email' => 'arjun.sharma@gmail.com',
                'password' => bcrypt('Secret@123'),
                'role' => 'player',
                'status' => 'active',
                'coins' => 2000.00,
            ]
        );
        $this->player->coins = 2000.00;
        $this->player->save();
    }

    public function test_player_can_submit_withdrawal_request_and_coins_are_locked(): void
    {
        $response = $this->actingAs($this->player)->post(route('ludo.wallet.withdraw'), [
            'amount' => 500,
            'payout_method' => 'upi',
            'upi_id' => 'arjun@oksbi',
        ]);

        $response->assertSessionHas('success');
        $this->player->refresh();
        $this->assertEquals(1500.00, (float) $this->player->coins);

        $this->assertDatabaseHas('ludo_withdrawal_requests', [
            'user_id' => $this->player->id,
            'amount' => 500.00,
            'payout_method' => 'upi',
            'upi_id' => 'arjun@oksbi',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $this->player->id,
            'type' => 'admin_adjustment',
            'points_delta' => -500,
        ]);
    }

    public function test_player_cannot_withdraw_more_than_current_balance(): void
    {
        $this->player->coins = 100.00;
        $this->player->save();

        $response = $this->actingAs($this->player)->post(route('ludo.wallet.withdraw'), [
            'amount' => 500,
            'payout_method' => 'upi',
            'upi_id' => 'arjun@oksbi',
        ]);

        $response->assertSessionHas('error');
        $this->player->refresh();
        $this->assertEquals(100.00, (float) $this->player->coins);
    }

    public function test_admin_can_view_withdrawal_requests_desk(): void
    {
        LudoWithdrawalRequest::create([
            'withdrawal_uid' => 'WTH-TEST-001',
            'user_id' => $this->player->id,
            'amount' => 350.00,
            'payout_method' => 'upi',
            'upi_id' => 'arjun@paytm',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.ludo.withdrawals.index'));
        $response->assertStatus(200);
        $response->assertSee('WTH-TEST-001');
        $response->assertSee('arjun@paytm');
        $response->assertSee('Settle');
    }

    public function test_admin_can_approve_withdrawal_settlement(): void
    {
        $withdrawal = LudoWithdrawalRequest::create([
            'withdrawal_uid' => 'WTH-TEST-002',
            'user_id' => $this->player->id,
            'amount' => 400.00,
            'payout_method' => 'upi',
            'upi_id' => 'arjun@paytm',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.ludo.withdrawals.approve', $withdrawal->id), [
            'admin_utr_reference' => 'UTR123456789012',
            'admin_notes' => 'Payout sent via PhonePe Business',
        ]);

        $response->assertSessionHas('success');
        $withdrawal->refresh();
        $this->assertEquals('approved', $withdrawal->status);
        $this->assertEquals('UTR123456789012', $withdrawal->admin_utr_reference);
        $this->assertEquals($this->admin->id, $withdrawal->processed_by);
    }

    public function test_admin_reject_withdrawal_refunds_locked_coins_to_player(): void
    {
        // Player balance is 1500
        $this->player->coins = 1500.00;
        $this->player->save();

        $withdrawal = LudoWithdrawalRequest::create([
            'withdrawal_uid' => 'WTH-TEST-003',
            'user_id' => $this->player->id,
            'amount' => 500.00,
            'payout_method' => 'upi',
            'upi_id' => 'invalid_vpa@fake',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.ludo.withdrawals.reject', $withdrawal->id), [
            'reason' => 'UPI ID invalid or blocked by PSP',
        ]);

        $response->assertSessionHas('success');
        $withdrawal->refresh();
        $this->assertEquals('rejected', $withdrawal->status);
        $this->assertStringContainsString('UPI ID invalid', $withdrawal->admin_notes);

        // Player must get 500 coins back (1500 + 500 = 2000)
        $this->player->refresh();
        $this->assertEquals(2000.00, (float) $this->player->coins);

        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $this->player->id,
            'type' => 'refund',
            'points_delta' => 500,
        ]);
    }

    public function test_admin_notifications_live_endpoint_returns_json_counts(): void
    {
        // Create 1 pending deposit & 1 pending withdrawal
        LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-NOTIF-01',
            'user_id' => $this->player->id,
            'amount' => 250.00,
            'utr_number' => '112233445566',
            'status' => 'pending',
        ]);

        LudoWithdrawalRequest::create([
            'withdrawal_uid' => 'WTH-NOTIF-01',
            'user_id' => $this->player->id,
            'amount' => 300.00,
            'payout_method' => 'upi',
            'upi_id' => 'arjun@oksbi',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.ludo.notifications.live'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'counts' => [
                'deposits',
                'withdrawals',
                'kyc',
                'disputes',
                'tickets',
                'total',
            ],
            'timestamp',
        ]);

        $data = $response->json();
        $this->assertTrue($data['counts']['deposits'] >= 1);
        $this->assertTrue($data['counts']['withdrawals'] >= 1);
        $this->assertTrue($data['counts']['total'] >= 2);
    }
}
