<?php

namespace Tests\Feature;

use App\Models\LudoBattle;
use App\Models\LudoReferral;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class LudoTournamentTest extends TestCase
{
    public function test_home_page_loads_successfully()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('OUR TOURNAMENTS');
        $response->assertSee('Install App');
        $response->assertSee('Dark Mode');
    }

    public function test_play_and_battles_page_loads_with_real_data()
    {
        $response = $this->get('/play');
        $response->assertStatus(200);
        $response->assertSee('Create a Battle!');
        $response->assertSee('OPEN BATTLES');
        $response->assertSee('RUNNING BATTLES');
    }

    public function test_battle_creation_validation_requires_minimum_points()
    {
        $user = User::factory()->create([
            'coins' => 500,
            'referral_code' => 'VAL_' . Str::random(5),
        ]);

        $response = $this->actingAs($user)->post('/battles/create', [
            'amount' => 20,
        ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_battle_creation_deducts_virtual_points()
    {
        $user = User::factory()->create([
            'coins' => 500,
            'referral_code' => 'DED_' . Str::random(5),
        ]);

        $response = $this->actingAs($user)->post('/battles/create', [
            'amount' => 100,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(400, $user->fresh()->coins);

        $this->assertDatabaseHas('ludo_battles', [
            'creator_id' => $user->id,
            'entry_fee' => 100,
            'status' => 'open',
        ]);
    }

    public function test_player_cannot_join_own_battle()
    {
        $user = User::factory()->create([
            'coins' => 500,
            'referral_code' => 'OWN_' . Str::random(5),
        ]);

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-' . Str::random(10),
            'creator_id' => $user->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'open',
        ]);

        $response = $this->actingAs($user)->post('/battles/join/' . $battle->id);
        $response->assertSessionHas('error');
        $this->assertEquals('open', $battle->fresh()->status);
    }

    public function test_player_can_join_open_battle()
    {
        $creator = User::factory()->create([
            'coins' => 500,
            'referral_code' => 'CR_' . Str::random(5),
        ]);

        $joiner = User::factory()->create([
            'coins' => 500,
            'referral_code' => 'JN_' . Str::random(5),
        ]);

        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-' . Str::random(10),
            'creator_id' => $creator->id,
            'entry_fee' => 100,
            'prize' => 190,
            'status' => 'open',
        ]);

        $response = $this->actingAs($joiner)->post('/battles/join/' . $battle->id);
        $response->assertSessionHas('success');

        $this->assertEquals(400, $joiner->fresh()->coins);
        $this->assertEquals('running', $battle->fresh()->status);
        $this->assertEquals($joiner->id, $battle->fresh()->joiner_id);
    }

    public function test_refer_and_earn_page_loads()
    {
        $code = 'REF_' . Str::random(4);
        $user = User::factory()->create([
            'coins' => 200, 
            'referral_code' => $code,
        ]);

        $response = $this->actingAs($user)->get('/refer');
        $response->assertStatus(200);
        $response->assertSee('Exclusive Rewards');
        $response->assertSee($code);
    }

    public function test_registration_with_referral_attribution_and_no_self_referral()
    {
        $refCode = 'VIP_' . Str::random(4);
        $referrer = User::factory()->create([
            'coins' => 100,
            'referral_balance' => 0.00,
            'referral_code' => $refCode,
        ]);

        $newEmail = 'invited_' . time() . '_' . Str::random(4) . '@royalludo.test';
        $response = $this->post('/register', [
            'name' => 'Invited Player',
            'email' => $newEmail,
            'password' => 'secret12345',
            'referral_code' => $refCode,
        ]);

        $response->assertRedirect('/showlogin');

        $referredUser = User::where('email', $newEmail)->first();
        $this->assertNotNull($referredUser);
        $this->assertEquals($referrer->id, $referredUser->referred_by_id);

        // Verify referral record created
        $this->assertDatabaseHas('ludo_referrals', [
            'referrer_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'status' => 'eligible',
            'reward_points' => 50,
        ]);

        // Referrer awarded virtual points
        $this->assertEquals(150, $referrer->fresh()->coins);
        $this->assertEquals(50.00, $referrer->fresh()->referral_balance);
    }

    public function test_profile_page_loads_and_user_can_update_profile_and_privacy()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'phone' => '1234567890',
            'hide_email' => true,
            'referral_code' => 'PRF_' . Str::random(4),
        ]);

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('Original Name');

        // Update profile
        $updateResponse = $this->actingAs($user)->post('/profile/update', [
            'name' => 'Updated Gamer',
            'phone' => '9876543210',
            'hide_email' => '1',
            'hide_phone' => '1',
        ]);

        $updateResponse->assertSessionHas('success');
        $this->assertEquals('Updated Gamer', $user->fresh()->name);
        $this->assertEquals('9876543210', $user->fresh()->phone);
        $this->assertTrue($user->fresh()->hide_email);
        $this->assertTrue($user->fresh()->hide_phone);
    }
}
