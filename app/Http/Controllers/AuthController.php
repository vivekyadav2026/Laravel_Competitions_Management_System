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
                    'coins' => 100, // Welcome points
                    'referral_code' => $uniqueRefCode,
                    'referred_by_id' => $referrer ? $referrer->id : null,
                ]);

                // Assign player_uid
                $newUser->update([
                    'player_uid' => 'LUDO' . str_pad($newUser->id, 5, '0', STR_PAD_LEFT),
                ]);

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

                    // Award virtual points to referrer
                    $referrer->increment('coins', 50);
                    $referrer->increment('referral_balance', 50.00);
                }
            });

            return redirect("/showlogin")->with('message', 'Registration successful! Please log in to begin playing.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

    }

    public function showLogin()
    {
        return view('authentication.sign_in');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Check if "Remember Me" is checked
        $remember = $request->filled('remember_me');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();
            if ($user->role === 'admin') {
                return redirect('/dashboard/users');
            } else if ($user->role === 'superadmin') {
                return redirect('/dashboard/admins');
            } else {
                return redirect('/');
            }
        } else {
            return redirect('/showlogin');
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }

}
