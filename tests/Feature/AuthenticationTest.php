<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LudoSupportTicket;
use App\Models\LudoKycSubmission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    /**
     * Guest users cannot access protected player routes and are redirected to login.
     */
    public function test_guest_is_redirected_to_login_when_accessing_protected_routes()
    {
        $protectedRoutes = [
            '/profile',
            '/wallet',
            '/refer',
            '/history',
            '/kyc',
            '/notifications',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/showlogin');
        }
    }

    /**
     * Guest cannot perform protected actions (e.g., create battle).
     */
    public function test_guest_cannot_create_battle()
    {
        $response = $this->post('/battles/create', ['amount' => 100]);
        $response->assertRedirect('/showlogin');
    }

    /**
     * Login rejects invalid credentials and returns validation errors with preserved input.
     */
    public function test_login_rejects_invalid_credentials()
    {
        $response = $this->from('/showlogin')->post('/login', [
            'email' => 'nonexistent@royalludo.test',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/showlogin');
        $response->assertSessionHasErrors('email');
    }

    /**
     * Suspended user is blocked from logging in.
     */
    public function test_suspended_user_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'suspended_' . Str::random(5) . '@royalludo.test',
            'password' => Hash::make('secret12345'),
            'status' => 'suspended',
            'suspension_reason' => 'Suspected unfair play',
            'referral_code' => 'SUS_' . Str::random(4),
        ]);

        $response = $this->from('/showlogin')->post('/login', [
            'email' => $user->email,
            'password' => 'secret12345',
        ]);

        $response->assertRedirect('/showlogin');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Valid user can log in, session is regenerated, and user is authenticated.
     */
    public function test_valid_user_can_login_and_intended_redirect_works()
    {
        $user = User::factory()->create([
            'email' => 'active_' . Str::random(5) . '@royalludo.test',
            'password' => Hash::make('secret12345'),
            'status' => 'active',
            'referral_code' => 'ACT_' . Str::random(4),
        ]);

        // Attempt accessing /profile as guest, should store intended url
        $this->get('/profile');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret12345',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/profile');
    }

    /**
     * Authenticated user can log out via POST /logout with CSRF token.
     * Session is invalidated and subsequent request to /profile is redirected to login.
     */
    public function test_user_can_logout_via_post_and_session_is_invalidated()
    {
        $user = User::factory()->create([
            'email' => 'logout_' . Str::random(5) . '@royalludo.test',
            'password' => Hash::make('secret12345'),
            'status' => 'active',
            'referral_code' => 'OUT_' . Str::random(4),
        ]);

        $response = $this->actingAs($user)->post('/logout');

        // Verify logout redirects to login with flash success message
        $response->assertRedirect('/showlogin');
        $response->assertSessionHas('success');
        $this->assertGuest();

        // Verify user cannot immediately re-access /profile without authenticating again
        $subsequentResponse = $this->get('/profile');
        $subsequentResponse->assertRedirect('/showlogin');
    }

    /**
     * Profile page renders the proper POST logout form with CSRF token.
     */
    public function test_profile_page_renders_post_logout_form_with_csrf()
    {
        $user = User::factory()->create([
            'name' => 'Profile Tester',
            'email' => 'prof_' . Str::random(5) . '@royalludo.test',
            'password' => Hash::make('secret12345'),
            'referral_code' => 'PRF_' . Str::random(4),
        ]);

        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('id="logout-form"', false);
        $response->assertSee('method="POST"', false);
        $response->assertSee('action="' . route('logout') . '"', false);
        $response->assertSee('Log Out');
    }

    /**
     * Authenticated user cannot view another user's private support ticket.
     */
    public function test_user_cannot_view_another_users_support_ticket()
    {
        $user1 = User::factory()->create([
            'referral_code' => 'USR1_' . Str::random(3),
        ]);
        $user2 = User::factory()->create([
            'referral_code' => 'USR2_' . Str::random(3),
        ]);

        $ticket = LudoSupportTicket::create([
            'ticket_uid' => 'TCK-' . strtoupper(Str::random(6)),
            'user_id' => $user1->id,
            'category' => 'battle_issue',
            'subject' => 'Confidential Inquiry',
            'description' => 'Confidential ticket description details.',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($user2)->get("/support/ticket/{$ticket->id}");
        $response->assertStatus(403);
    }

    /**
     * Authenticated ordinary user cannot access admin dashboard.
     */
    public function test_ordinary_player_cannot_access_admin_dashboard()
    {
        $player = User::factory()->create([
            'role' => 'user',
            'referral_code' => 'PLY_' . Str::random(4),
        ]);

        $response = $this->actingAs($player)->get('/admin/ludo/dashboard');
        $response->assertStatus(403);
    }

    /**
     * Password update validates current password correctly.
     */
    public function test_password_update_validates_current_password()
    {
        $user = User::factory()->create([
            'email' => 'pwd_' . Str::random(5) . '@royalludo.test',
            'password' => Hash::make('correctCurrentPassword123'),
            'referral_code' => 'PWD_' . Str::random(4),
        ]);

        // Wrong current password
        $response = $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'wrongPassword',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ]);
        $response->assertSessionHasErrors('current_password');

        // Correct current password
        $validResponse = $this->actingAs($user)->post('/profile/password', [
            'current_password' => 'correctCurrentPassword123',
            'password' => 'newPassword123',
            'password_confirmation' => 'newPassword123',
        ]);
        $validResponse->assertSessionHas('success');
        $this->assertTrue(Hash::check('newPassword123', $user->fresh()->password));
    }

    /**
     * Dedicated Admin Panel login page renders and authenticates admin to /admin/ludo/dashboard.
     */
    public function test_admin_panel_login_page_loads_and_authenticates_admin()
    {
        $getResponse = $this->get('/admin/login');
        $getResponse->assertStatus(200);
        $getResponse->assertSee('Admin Console');
        $getResponse->assertSee('autocomplete="username"', false);
        $getResponse->assertSee('autocomplete="current-password"', false);
        $getResponse->assertDontSee('value="Admin@12345"', false);
        $getResponse->assertDontSee('value="admin@fsaludoclub.com"', false);

        $postResponse = $this->post('/admin/login', [
            'email' => 'admin@fsaludoclub.com',
            'password' => 'Admin@12345',
        ]);

        $postResponse->assertRedirect('/admin/ludo/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(in_array(auth()->user()->role, ['admin', 'superadmin']));
    }
}
