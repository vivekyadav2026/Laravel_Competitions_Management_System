<?php

namespace Tests\Feature;

use App\Models\LudoLedgerTransaction;
use App\Models\LudoReferral;
use App\Models\LudoSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReferralSystemAndAutoLoginTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        LudoSetting::set('referral_enabled', 'on', 'referral');
        LudoSetting::set('referral_reward_points', '50', 'referral');
        LudoSetting::set('welcome_bonus_points', '100', 'general');
    }

    /**
     * Requirement 1: Registration must auto-login the player directly into the user panel (/)
     * without requiring them to log in again.
     */
    public function test_new_player_registration_auto_authenticates_and_redirects_to_user_panel(): void
    {
        $uniquePhone = '98' . random_int(10000000, 99999999);
        $uniqueEmail = 'direct_player_' . Str::random(8) . '@fsaludotest.com';

        $response = $this->post('/register', [
            'name' => 'Rohan Sharma',
            'email' => $uniqueEmail,
            'phone' => $uniquePhone,
            'password' => 'DirectPass@123',
        ]);

        // Must redirect to User Panel / Lobby (/)
        $response->assertRedirect('/');

        // Player must be immediately logged in
        $registeredUser = User::where('email', $uniqueEmail)->first();
        $this->assertNotNull($registeredUser);
        $this->assertAuthenticatedAs($registeredUser);

        // Player gets 100 welcome bonus points
        $this->assertEquals(100, (int) $registeredUser->coins);
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $registeredUser->id,
            'type' => 'deposit',
            'points_delta' => 100,
            'status' => 'successful',
        ]);
    }

    /**
     * Requirement 2: Referral system works end-to-end.
     * Referrer invites friend -> Friend registers with referral code ->
     * Both receive bonus points -> Referral appears on player's /refer page and admin desk.
     */
    public function test_referral_attribution_and_bonus_crediting_end_to_end(): void
    {
        // 1. Create Referrer (User A)
        $refCode = 'REF' . strtoupper(Str::random(3));
        $referrer = User::factory()->create([
            'name' => 'Aarav Mehta',
            'coins' => 200,
            'referral_balance' => 0.00,
            'referral_code' => $refCode,
            'role' => 'player',
            'status' => 'active',
        ]);

        $uniquePhone = '97' . random_int(10000000, 99999999);
        $uniqueEmail = 'friend_' . Str::random(8) . '@fsaludotest.com';

        // 2. Friend (User B) registers using lowercase referral code (case-insensitivity test)
        $response = $this->post('/register', [
            'name' => 'Pooja Verma',
            'email' => $uniqueEmail,
            'phone' => $uniquePhone,
            'password' => 'FriendPass@123',
            'referral_code' => strtolower($refCode), // Lowercase to verify robust case-insensitivity
        ]);

        $response->assertRedirect('/');

        // 3. Friend is immediately authenticated and has welcome bonus
        $referredUser = User::where('email', $uniqueEmail)->first();
        $this->assertNotNull($referredUser);
        $this->assertAuthenticatedAs($referredUser);
        $this->assertEquals($referrer->id, $referredUser->referred_by_id);
        $this->assertEquals(100, (int) $referredUser->coins);

        // 4. Referrer (User A) received 50 referral bonus points in coins & referral_balance
        $referrer->refresh();
        $this->assertEquals(250, (int) $referrer->coins);
        $this->assertEquals(50.00, (float) $referrer->referral_balance);

        // 5. Check LudoReferral record in database
        $this->assertDatabaseHas('ludo_referrals', [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'referral_code' => $refCode,
            'status' => 'eligible',
            'reward_points' => 50,
        ]);

        // 6. Check Referrer's ledger transaction
        $this->assertDatabaseHas('ludo_ledger_transactions', [
            'user_id' => $referrer->id,
            'type' => 'referral_bonus',
            'points_delta' => 50,
            'balance_after' => 250,
            'status' => 'successful',
        ]);

        // 7. Verify Referrer's /refer page displays the referral history and stats
        $referPageResponse = $this->actingAs($referrer)->get('/refer');
        $referPageResponse->assertStatus(200);
        $referPageResponse->assertSee($refCode);
        $referPageResponse->assertSee('Pooja Verma');
        $referPageResponse->assertSee('+50');
        $referPageResponse->assertSee('1 Friends Joined');

        // 8. Verify Admin /admin/ludo/referrals desk displays the referral
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $adminReferralsResponse = $this->actingAs($admin)->get('/admin/ludo/referrals');
        $adminReferralsResponse->assertStatus(200);
        $adminReferralsResponse->assertSee($refCode);
        $adminReferralsResponse->assertSee('Aarav Mehta');
        $adminReferralsResponse->assertSee('Pooja Verma');
        $adminReferralsResponse->assertSee('Eligible');
    }

    /**
     * Requirement 3: Intended URL sanitization.
     * If a guest had an intended admin URL stored in session, registering as a player
     * must NEVER redirect them into the admin panel.
     */
    public function test_registration_sanitizes_intended_admin_url(): void
    {
        $uniquePhone = '96' . random_int(10000000, 99999999);
        $uniqueEmail = 'safe_player_' . Str::random(8) . '@fsaludotest.com';

        // Simulate guest attempting to access admin route first
        $response = $this->withSession(['url.intended' => url('/admin/ludo/dashboard')])
            ->post('/register', [
                'name' => 'Safe Player',
                'email' => $uniqueEmail,
                'phone' => $uniquePhone,
                'password' => 'SafePass@123',
            ]);

        // Must NOT redirect to /admin/ludo/dashboard, must safely redirect to /
        $response->assertRedirect('/');

        $newUser = User::where('email', $uniqueEmail)->first();
        $this->assertAuthenticatedAs($newUser);
    }
}
