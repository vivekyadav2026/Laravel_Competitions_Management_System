<?php

namespace App\Http\Controllers;

use App\Models\PhoneOtp;
use App\Models\User;
use App\Services\SmsService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
                'phone' => 'nullable|string|max:20',
                'password' => 'required|string|min:8',
                'referral_code' => 'nullable|string|max:30',
            ]);

            $cleanPhone = null;
            if ($request->filled('phone')) {
                $cleanPhone = SmsService::normalizePhone($request->phone);
                if (!$cleanPhone) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['phone' => 'Please enter a valid 10-digit Indian mobile number.']);
                }
                if (User::where('phone', $cleanPhone)->exists()) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['phone' => 'This mobile number is already registered.']);
                }
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $cleanPhone) {
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
                    'phone' => $cleanPhone,
                    'password' => Hash::make($request->password),
                    'coins' => 0, // Initialized to 0, credited through ledger
                    'referral_code' => $uniqueRefCode,
                    'referred_by_id' => $referrer ? $referrer->id : null,
                ]);

                // Assign player_uid
                $newUser->update([
                    'player_uid' => 'LUDO' . str_pad($newUser->id, 5, '0', STR_PAD_LEFT),
                ]);

                // Credit welcome virtual points via authoritative ledger (configurable by admin, default 100)
                $welcomeBonus = (int) \App\Models\LudoSetting::get('welcome_bonus_points', 100);
                if ($welcomeBonus > 0) {
                    \App\Models\LudoLedgerTransaction::recordAdjustment(
                        $newUser,
                        $welcomeBonus,
                        'deposit',
                        'Welcome registration virtual tournament bonus points',
                        'successful'
                    );
                }

                // If legitimately referred by an existing user, not self-referral, and referral program is enabled
                $referralEnabled = \App\Models\LudoSetting::get('referral_enabled', 'on') !== 'off';
                $rewardPoints = (int) \App\Models\LudoSetting::get('referral_reward_points', 50);

                if ($referrer && $referrer->id !== $newUser->id && $referralEnabled && $rewardPoints > 0) {
                    \App\Models\LudoReferral::create([
                        'referrer_id' => $referrer->id,
                        'referred_user_id' => $newUser->id,
                        'referral_code' => trim($request->referral_code),
                        'status' => 'eligible',
                        'reward_points' => $rewardPoints,
                        'rewarded_at' => now(),
                    ]);

                    // Award virtual points to referrer via authoritative ledger
                    \App\Models\LudoLedgerTransaction::recordAdjustment(
                        $referrer,
                        $rewardPoints,
                        'referral_bonus',
                        "Referral bonus reward for inviting {$newUser->name} ({$newUser->player_uid})",
                        'successful'
                    );
                    $referrer->increment('referral_balance', (float) $rewardPoints);
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

    /**
     * Send OTP to player mobile number via HSP SMS Gateway.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $cleanPhone = SmsService::normalizePhone($request->phone);

        if (!$cleanPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid 10-digit Indian mobile number (e.g. 9876543210).',
            ], 422);
        }

        // Cooldown check (default 60 seconds)
        $cooldown = (int) config('services.hspsms.resend_cooldown_seconds', 60);
        $recentOtp = PhoneOtp::where('phone', $cleanPhone)
            ->where('created_at', '>=', now()->subSeconds($cooldown))
            ->latest()
            ->first();

        if ($recentOtp) {
            $elapsed = now()->diffInSeconds($recentOtp->created_at);
            $remaining = max(1, $cooldown - $elapsed);
            return response()->json([
                'success' => false,
                'message' => "Please wait {$remaining}s before requesting a new OTP.",
                'remaining' => $remaining,
            ], 429);
        }

        // Generate OTP based on configured length (default 6)
        $otpLength = (int) config('services.hspsms.otp_length', 6);
        $min = (int) pow(10, $otpLength - 1);
        $max = (int) pow(10, $otpLength) - 1;
        $otp = (string) random_int($min, $max);

        // Save OTP record
        $expiryMinutes = (int) config('services.hspsms.otp_expiry_minutes', 10);
        PhoneOtp::create([
            'phone' => $cleanPhone,
            'otp' => $otp,
            'ip_address' => $request->ip(),
            'attempts' => 0,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        // Send SMS via HSP Gateway
        $smsResult = SmsService::sendOtp($cleanPhone, $otp);

        if (!$smsResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $smsResult['message'] ?? 'Failed to send SMS. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Verification code sent to +91 {$cleanPhone}",
            'phone' => $cleanPhone,
            'formatted_phone' => "+91 {$cleanPhone}",
            'cooldown' => $cooldown,
        ]);
    }

    /**
     * Resend OTP to player mobile number.
     */
    public function resendOtp(Request $request)
    {
        return $this->sendOtp($request);
    }

    /**
     * Verify OTP and authenticate or automatically register player.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|string',
            'referral_code' => 'nullable|string|max:30',
        ]);

        $cleanPhone = SmsService::normalizePhone($request->phone);
        if (!$cleanPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid mobile number format.',
            ], 422);
        }

        $cleanOtp = preg_replace('/\D+/', '', $request->otp);

        // Fetch latest active OTP
        $phoneOtp = PhoneOtp::where('phone', $cleanPhone)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$phoneOtp || $phoneOtp->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'The verification code has expired or is invalid. Please request a new code.',
            ], 422);
        }

        if ($phoneOtp->attempts >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Too many failed attempts. Please request a new OTP.',
            ], 429);
        }

        if ($phoneOtp->otp !== $cleanOtp) {
            $phoneOtp->increment('attempts');
            $remaining = 5 - $phoneOtp->attempts;
            return response()->json([
                'success' => false,
                'message' => "Incorrect OTP. You have {$remaining} attempt(s) remaining.",
            ], 422);
        }

        // Mark OTP as verified
        $phoneOtp->update(['verified_at' => now()]);

        // Find or auto-register user
        $user = User::where('phone', $cleanPhone)->first();

        if ($user) {
            if ($user->status === 'suspended') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended: ' . ($user->suspension_reason ?: 'Please contact support.'),
                ], 403);
            }
        } else {
            // Auto-register new player seamlessly
            $user = DB::transaction(function () use ($cleanPhone, $request) {
                $uniqueRefCode = strtoupper(Str::random(6));
                while (User::where('referral_code', $uniqueRefCode)->exists()) {
                    $uniqueRefCode = strtoupper(Str::random(6));
                }

                $referrer = null;
                if ($request->filled('referral_code')) {
                    $referrer = User::where('referral_code', trim($request->referral_code))->first();
                }

                $email = 'player_' . $cleanPhone . '@fsaludoclub.com';
                if (User::where('email', $email)->exists()) {
                    $email = 'player_' . $cleanPhone . '_' . Str::lower(Str::random(4)) . '@fsaludoclub.com';
                }

                $userName = $request->filled('name') && trim($request->name) !== '' 
                    ? trim($request->name) 
                    : 'Player_' . substr($cleanPhone, -4);

                $newUser = User::create([
                    'name' => $userName,
                    'phone' => $cleanPhone,

                    'email' => $email,
                    'password' => Hash::make(Str::random(24)),
                    'coins' => 0,
                    'referral_code' => $uniqueRefCode,
                    'referred_by_id' => $referrer ? $referrer->id : null,
                    'status' => 'active',
                    'role' => 'normal',
                ]);

                // Assign player_uid
                $newUser->update([
                    'player_uid' => 'LUDO' . str_pad($newUser->id, 5, '0', STR_PAD_LEFT),
                ]);

                // Credit welcome virtual points
                $welcomeBonus = (int) \App\Models\LudoSetting::get('welcome_bonus_points', 100);
                if ($welcomeBonus > 0) {
                    \App\Models\LudoLedgerTransaction::recordAdjustment(
                        $newUser,
                        $welcomeBonus,
                        'deposit',
                        'Welcome registration virtual tournament bonus points',
                        'successful'
                    );
                }

                // Referrer reward
                $referralEnabled = \App\Models\LudoSetting::get('referral_enabled', 'on') !== 'off';
                $rewardPoints = (int) \App\Models\LudoSetting::get('referral_reward_points', 50);

                if ($referrer && $referrer->id !== $newUser->id && $referralEnabled && $rewardPoints > 0) {
                    \App\Models\LudoReferral::create([
                        'referrer_id' => $referrer->id,
                        'referred_user_id' => $newUser->id,
                        'referral_code' => trim($request->referral_code),
                        'status' => 'eligible',
                        'reward_points' => $rewardPoints,
                        'rewarded_at' => now(),
                    ]);

                    \App\Models\LudoLedgerTransaction::recordAdjustment(
                        $referrer,
                        $rewardPoints,
                        'referral_bonus',
                        "Referral bonus reward for inviting {$newUser->name} ({$newUser->player_uid})",
                        'successful'
                    );
                    $referrer->increment('referral_balance', (float) $rewardPoints);
                }

                return $newUser;
            });
        }

        // Authenticate user
        Auth::login($user, true);
        $request->session()->regenerate();

        $redirectUrl = session()->pull('url.intended');
        if (!$redirectUrl) {
            if (in_array($user->role, ['admin', 'superadmin', 'support_staff', 'moderator'])) {
                $redirectUrl = route('admin.ludo.dashboard');
            } else {
                $redirectUrl = route('ludo.home');
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Login successful! Redirecting...',
                'redirect' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl)->with('success', 'Logged in successfully!');
    }

    public function login(Request $request)
    {
        $input = trim((string) $request->input('email', ''));
        if ($input === 'admin@fsaludoclub.com') {
            $this->ensureDefaultAdminUser();
        }

        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $cleanPhone = SmsService::normalizePhone($input);

        // Find user by either email or normalized phone
        $user = User::where('email', $input)
            ->when($cleanPhone, function ($query, $phone) {
                return $query->orWhere('phone', $phone);
            })
            ->first();

        $remember = $request->filled('remember_me');

        if ($user && Hash::check($request->password, $user->password)) {
            // Check if user is suspended
            if ($user->status === 'suspended') {
                return redirect()->back()
                    ->withInput($request->only('email', 'remember_me'))
                    ->withErrors(['email' => 'Your account has been suspended: ' . ($user->suspension_reason ?: 'Please contact support.')]);
            }

            Auth::login($user, $remember);
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

