<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('authentication.sign_up');
    }

    public function register(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'referral_code' => 'nullable|string|max:30',
            ]);

            \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
                // Generate a unique referral code and player UID for new player
                $uniqueRefCode = strtoupper(\Illuminate\Support\Str::random(6));
                while (User::where('referral_code', $uniqueRefCode)->exists()) {
                    $uniqueRefCode = strtoupper(\Illuminate\Support\Str::random(6));
                }

                $referrer = null;
                if ($request->filled('referral_code')) {
                    $referrer = User::where('referral_code', trim($request->referral_code))->first();
                }

                $newUser = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'coins' => 0, // Initialized to 0, credited through ledger
                    'referral_code' => $uniqueRefCode,
                    'referred_by_id' => $referrer ? $referrer->id : null,
                ]);

                // Assign player_uid
                $newUser->update([
                    'player_uid' => 'LUDO' . str_pad($newUser->id, 5, '0', STR_PAD_LEFT),
                ]);

                // Credit welcome virtual points via authoritative ledger
                \App\Models\LudoLedgerTransaction::recordAdjustment(
                    $newUser,
                    100,
                    'deposit',
                    'Welcome registration virtual tournament bonus points',
                    'successful'
                );

                // If legitimately referred by an existing user and not self-referral
                if ($referrer && $referrer->id !== $newUser->id) {
                    \App\Models\LudoReferral::create([
                        'referrer_id' => $referrer->id,
                        'referred_user_id' => $newUser->id,
                        'referral_code' => trim($request->referral_code),
                        'status' => 'eligible',
                        'reward_points' => 50, // 50 virtual reward points
                        'rewarded_at' => now(),
                    ]);

                    // Award virtual points to referrer via authoritative ledger
                    \App\Models\LudoLedgerTransaction::recordAdjustment(
                        $referrer,
                        50,
                        'referral_bonus',
                        "Referral bonus reward for inviting {$newUser->name} ({$newUser->player_uid})",
                        'successful'
                    );
                    $referrer->increment('referral_balance', 50.00);
                }
            });

            return redirect("/showlogin")->with('message', 'Registration successful! Please log in to begin playing.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

    }

    protected function ensureDefaultAdminUser(): void
    {
        if (User::where('email', 'admin@fsaludoclub.com')->exists()) {
            return;
        }

        $refCode = 'FSADMIN';
        if (User::where('referral_code', $refCode)->exists()) {
            $refCode = 'FSADM' . strtoupper(\Illuminate\Support\Str::random(3));
        }

        $uid = 'FSADMIN01';
        if (User::where('player_uid', $uid)->exists()) {
            $uid = 'FSADM' . random_int(100, 999);
        }

        User::create([
            'name' => 'Fsaludoclub Super Admin',
            'email' => 'admin@fsaludoclub.com',
            'password' => Hash::make('Admin@12345'),
            'role' => 'superadmin',
            'status' => 'active',
            'coins' => 5000,
            'player_uid' => $uid,
            'referral_code' => $refCode,
        ]);
    }

    public function showLogin()
    {
        return view('authentication.sign_in');
    }

    public function showAdminLogin()
    {
        $this->ensureDefaultAdminUser();

        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin', 'support_staff', 'moderator'])) {
            return redirect()->route('admin.ludo.dashboard');
        }

        return view('admin.ludo.login');
    }

    public function adminLogin(Request $request)
    {
        $this->ensureDefaultAdminUser();

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->filled('remember_me');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if ($user->status === 'suspended') {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->back()
                    ->withInput($request->only('email', 'remember_me'))
                    ->withErrors(['email' => 'This administrator account has been suspended.']);
            }

            if (!in_array($user->role, ['admin', 'superadmin', 'support_staff', 'moderator'])) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->back()
                    ->withInput($request->only('email', 'remember_me'))
                    ->withErrors(['email' => 'Access denied. This account does not have Administrator privileges.']);
            }

            $request->session()->regenerate();

            return redirect()->intended('/admin/ludo/dashboard');
        }

        return redirect()->back()
            ->withInput($request->only('email', 'remember_me'))
            ->withErrors(['email' => 'The provided admin credentials do not match our records.']);
    }

    public function login(Request $request)
    {
        if ($request->input('email') === 'admin@fsaludoclub.com') {
            $this->ensureDefaultAdminUser();
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check if "Remember Me" is checked
        $remember = $request->filled('remember_me');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Check if user is suspended
            if ($user->status === 'suspended') {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->back()
                    ->withInput($request->only('email', 'remember_me'))
                    ->withErrors(['email' => 'Your account has been suspended. Please contact support.']);
            }

            // Regenerate session to prevent session fixation
            $request->session()->regenerate();

            if (in_array($user->role, ['admin', 'superadmin', 'support_staff', 'moderator'])) {
                return redirect()->intended('/admin/ludo/dashboard');
            } else {
                return redirect()->intended('/');
            }
        }

        return redirect()->back()
            ->withInput($request->only('email', 'remember_me'))
            ->withErrors(['email' => 'The provided credentials do not match our records.']);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}
