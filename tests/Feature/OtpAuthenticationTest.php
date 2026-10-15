<?php

namespace Tests\Feature;

use App\Models\PhoneOtp;
use App\Models\User;
use App\Models\LudoSetting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class OtpAuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake HSP SMS Gateway API
        Http::fake([
            'http://sms.hspsms.com/*' => Http::response([
                ['responseCode' => 'Message SuccessFully Submitted'],
                ['msgid' => '12345678'],
            ], 200),
        ]);
    }

    protected function randomPhone(): string
    {
        return '9' . str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
    }

    /**
     * Rejects invalid or short phone numbers.
     */
    public function test_send_otp_validates_phone_number()
    {
        $response = $this->postJson('/auth/send-otp', [
            'phone' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Sends OTP to valid 10-digit Indian phone number.
     */
    public function test_send_otp_success_creates_db_record()
    {
        $phone = $this->randomPhone();

        $response = $this->postJson('/auth/send-otp', [
            'phone' => '+91 ' . $phone,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'phone' => $phone,
            ]);

        $this->assertDatabaseHas('phone_otps', [
            'phone' => $phone,
        ]);

        $otpRecord = PhoneOtp::where('phone', $phone)->latest()->first();
        $this->assertNotNull($otpRecord);
        $this->assertEquals(6, strlen($otpRecord->otp));
        $this->assertTrue($otpRecord->isValid());
    }

    /**
     * Prevents spamming OTP within cooldown window.
     */
    public function test_send_otp_rate_limiting_cooldown()
    {
        $phone = $this->randomPhone();

        // First attempt
        $res1 = $this->postJson('/auth/send-otp', ['phone' => $phone]);
        $res1->assertStatus(200);

        // Immediate second attempt within cooldown
        $res2 = $this->postJson('/auth/send-otp', ['phone' => $phone]);
        $res2->assertStatus(429)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Incorrect OTP increments attempts and is rejected.
     */
    public function test_verify_otp_rejects_incorrect_code()
    {
        $phone = $this->randomPhone();
        PhoneOtp::create([
            'phone' => $phone,
            'otp' => '654321',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/auth/verify-otp', [
            'phone' => $phone,
            'otp' => '111111',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $otpRecord = PhoneOtp::where('phone', $phone)->latest()->first();
        $this->assertEquals(1, $otpRecord->attempts);
        $this->assertNull($otpRecord->verified_at);
        $this->assertGuest();
    }

    /**
     * Valid OTP logs in existing user.
     */
    public function test_verify_otp_authenticates_existing_user()
    {
        $phone = $this->randomPhone();
        $user = User::factory()->create([
            'phone' => $phone,
            'status' => 'active',
            'role' => 'normal',
        ]);

        PhoneOtp::create([
            'phone' => $phone,
            'otp' => '998877',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/auth/verify-otp', [
            'phone' => $phone,
            'otp' => '998877',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertAuthenticatedAs($user);

        $otpRecord = PhoneOtp::where('phone', $phone)->latest()->first();
        $this->assertNotNull($otpRecord->verified_at);
    }

    /**
     * Valid OTP auto-registers new player with welcome bonus.
     */
    public function test_verify_otp_auto_registers_new_player()
    {
        $phone = $this->randomPhone();

        PhoneOtp::create([
            'phone' => $phone,
            'otp' => '123456',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/auth/verify-otp', [
            'phone' => $phone,
            'otp' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'phone' => $phone,
        ]);

        $newUser = User::where('phone', $phone)->first();
        $this->assertNotNull($newUser);
        $this->assertAuthenticatedAs($newUser);
        $this->assertStringStartsWith('LUDO', $newUser->player_uid);
        $this->assertEquals(100, $newUser->coins); // Welcome bonus
    }

    /**
     * Suspended user is prevented from logging in via OTP.
     */
    public function test_suspended_user_blocked_on_otp_login()
    {
        $phone = $this->randomPhone();
        User::factory()->create([
            'phone' => $phone,
            'status' => 'suspended',
            'suspension_reason' => 'Violation of fair play',
        ]);

        PhoneOtp::create([
            'phone' => $phone,
            'otp' => '554433',
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/auth/verify-otp', [
            'phone' => $phone,
            'otp' => '554433',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertGuest();
    }
}
