<?php

namespace App\Http\Controllers;

use App\Models\LudoBattle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LudoController extends Controller
{
    /**
     * Home Lobby (Malik Ludo Reference Screenshots)
     */
    public function home()
    {
        $user = Auth::user();
        $coins = $user ? $user->coins : 0;
        $referral = $user ? $user->referral_balance : 0.00;

        return view('ludo.home', [
            'coins' => $coins,
            'referral' => $referral,
            'activeTab' => 'home',
        ]);
    }

    /**
     * Open & Running Battles Screen (Screenshot #5 & Play Page)
     */
    public function battles()
    {
        $user = Auth::user();
        $coins = $user ? $user->coins : 0;
        $referral = $user ? $user->referral_balance : 0.00;

        // Fetch Open Battles from database with pagination (5 per page)
        $openBattles = LudoBattle::with('creator')
            ->where('status', 'open')
            ->latest('id')
            ->paginate(5, ['*'], 'open_page');

        // Fetch Running Battles from database with pagination (5 per page)
        $runningBattles = LudoBattle::with(['creator', 'joiner'])
            ->where('status', 'running')
            ->latest('started_at')
            ->paginate(5, ['*'], 'running_page');

        return view('ludo.battles', [
            'coins' => $coins,
            'referral' => $referral,
            'openBattles' => $openBattles,
            'runningBattles' => $runningBattles,
            'activeTab' => 'home',
        ]);
    }

    /**
     * Create a Battle Action (Server Validation, Free/Virtual Points Only)
     */
    public function createBattle(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:50', 'max:50000'],
        ], [
            'amount.required' => 'Please enter the tournament points amount.',
            'amount.integer' => 'Tournament points must be a valid whole number.',
            'amount.min' => 'Minimum battle entry points is 50.',
            'amount.max' => 'Maximum battle entry points per challenge is 50,000.',
        ]);

        $amount = (int) $validated['amount'];

        // If user is authenticated, check their points; if guest, authenticate or use default demo user
        $user = Auth::user();
        if (!$user) {
            // Assign or login a demo guest player so testing is seamless
            $user = User::firstOrCreate(
                ['email' => 'player1@royalludo.test'],
                [
                    'name' => 'Royal_Player',
                    'password' => bcrypt('secret123'),
                    'coins' => 1000,
                    'referral_code' => strtoupper(Str::random(6)),
                ]
            );
            Auth::login($user);
        }

        // Check virtual points balance
        if ($user->coins < $amount) {
            return redirect()->back()
                ->with('error', "Insufficient tournament coins! You have 🪙 {$user->coins}, but need 🪙 {$amount}. Add virtual points from My Wallet.")
                ->withInput();
        }

        // Prevent duplicate open battles by the same creator for the same amount
        $existingOpen = LudoBattle::where('creator_id', $user->id)
            ->where('status', 'open')
            ->where('entry_fee', $amount)
            ->first();

        if ($existingOpen) {
            return redirect()->back()
                ->with('error', "You already have an active open battle for 🪙 {$amount} (ID: {$existingOpen->battle_uid}).");
        }

        // Use atomic ledger transaction for balance change
        \App\Models\LudoLedgerTransaction::recordAdjustment(
            $user,
            -$amount,
            'entry_fee',
            "Battle creation points entry for 🪙 {$amount}",
            'successful'
        );

        // Create the battle record
        $battle = LudoBattle::create([
            'battle_uid' => 'BAT-' . strtoupper(Str::random(8)),
            'creator_id' => $user->id,
            'entry_fee' => $amount,
            'prize' => LudoBattle::calculatePrize($amount),
            'status' => 'open',
            'claimed_result' => 'pending',
            'final_result' => 'pending',
            'review_status' => 'auto_approved',
        ]);

        return redirect()->back()->with('success', "🎉 Battle {$battle->battle_uid} created for 🪙 {$amount}! Waiting for an opponent to join.");
    }

    /**
     * Join an Open Battle Action
     */
    public function joinBattle(Request $request, $id)
    {
        $battle = LudoBattle::findOrFail($id);

        // Check if battle is open
        if ($battle->status !== 'open') {
            return redirect()->back()->with('error', 'This battle is no longer open for joining.');
        }

        $user = Auth::user();
        if (!$user) {
            $user = User::firstOrCreate(
                ['email' => 'challenger@royalludo.test'],
                [
                    'name' => 'Challenger_X',
                    'password' => bcrypt('secret123'),
                    'coins' => 2000,
                    'referral_code' => strtoupper(Str::random(6)),
                ]
            );
            Auth::login($user);
        }

        // Prevent creator from playing against themselves
        if ($battle->creator_id === $user->id) {
            return redirect()->back()->with('error', 'You cannot join your own battle challenge.');
        }

        // Check points balance
        if ($user->coins < $battle->entry_fee) {
            return redirect()->back()->with('error', "Insufficient coins! You need 🪙 {$battle->entry_fee} to accept this battle.");
        }

        // Deduct points from joiner atomically via ledger
        \App\Models\LudoLedgerTransaction::recordAdjustment(
            $user,
            -$battle->entry_fee,
            'entry_fee',
            "Joined Battle {$battle->battle_uid} vs {$battle->creator->name}",
            'successful'
        );

        // Transition battle to running state
        $battle->update([
            'joiner_id' => $user->id,
            'status' => 'running',
            'room_code' => (string) rand(10000000, 99999999),
            'started_at' => now(),
        ]);

        return redirect()->back()->with('success', "⚔️ Match started! You joined {$battle->battle_uid} against {$battle->creator->name}. Room code: {$battle->room_code}");
    }

    /**
     * Cancel an Open Battle (Refund points to creator)
     */
    public function cancelBattle(Request $request, $id)
    {
        $battle = LudoBattle::findOrFail($id);
        $user = Auth::user();

        if (!$user || $battle->creator_id !== $user->id) {
            return redirect()->back()->with('error', 'Unauthorized: You can only cancel your own open challenges.');
        }

        if ($battle->status !== 'open') {
            return redirect()->back()->with('error', 'Only open battles can be cancelled.');
        }

        // Refund points atomically via ledger
        \App\Models\LudoLedgerTransaction::recordAdjustment(
            $user,
            $battle->entry_fee,
            'refund',
            "Refund for cancelled Battle {$battle->battle_uid}",
            'successful'
        );

        $battle->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('success', "Battle {$battle->battle_uid} cancelled. 🪙 {$battle->entry_fee} refunded to your balance.");
    }

    /**
     * Refer & Earn Screen
     */
    public function refer()
    {
        $user = Auth::user();
        if (!$user) {
            $user = User::firstOrCreate(
                ['email' => 'player1@royalludo.test'],
                [
                    'name' => 'Royal_Player',
                    'password' => bcrypt('secret123'),
                    'coins' => 1000,
                    'referral_code' => '780547',
                    'player_uid' => 'LUDO00001',
                ]
            );
        }

        $coins = $user->coins;
        $referral = $user->referral_balance;
        $referralCode = $user->referral_code ?? '780547';

        // Fetch real legitimate referrals and history
        $referrals = \App\Models\LudoReferral::with('referredUser')
            ->where('referrer_id', $user->id)
            ->latest('id')
            ->paginate(5);

        $totalReferrals = \App\Models\LudoReferral::where('referrer_id', $user->id)->count();
        $totalEligibleRewards = \App\Models\LudoReferral::where('referrer_id', $user->id)
            ->whereIn('status', ['eligible', 'rewarded'])
            ->sum('reward_points');

        // Dynamic share URL using application's configured root
        $shareUrl = url('/showregister?ref=' . $referralCode);

        return view('ludo.refer', [
            'coins' => $coins,
            'referral' => $referral,
            'referralCode' => $referralCode,
            'shareUrl' => $shareUrl,
            'commission' => '3%',
            'totalReferrals' => $totalReferrals,
            'totalEligibleRewards' => $totalEligibleRewards,
            'referrals' => $referrals,
            'activeTab' => 'refer',
        ]);
    }

    /**
     * Support Center Screen
     */
    public function support()
    {
        $user = Auth::user();
        $coins = $user ? $user->coins : 0;
        $referral = $user ? $user->referral_balance : 0.00;

        return view('ludo.support', [
            'coins' => $coins,
            'referral' => $referral,
            'activeTab' => 'support',
        ]);
    }

    /**
     * Player Wallet Screen
     */
    public function wallet(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            $user = User::firstOrCreate(
                ['email' => 'player1@royalludo.test'],
                [
                    'name' => 'Royal_Player',
                    'password' => bcrypt('secret123'),
                    'coins' => 1000,
                    'referral_code' => '780547',
                    'player_uid' => 'LUDO00001',
                ]
            );
            Auth::login($user);
        }

        $coins = $user->coins;
        $referral = $user->referral_balance;

        // Fetch paginated transactions for this user
        $transactionsQuery = \App\Models\LudoLedgerTransaction::where('user_id', $user->id)
            ->latest('id');

        if ($request->filled('status')) {
            $transactionsQuery->where('status', $request->status);
        }

        $transactions = $transactionsQuery->paginate(6);

        return view('ludo.wallet', [
            'coins' => $coins,
            'referral' => $referral,
            'transactions' => $transactions,
            'activeTab' => 'wallet',
        ]);
    }

    /**
     * Add Virtual Points (Simulated Non-Cash Deposit Engine)
     */
    public function addVirtualPoints(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:50', 'max:50000'],
        ], [
            'amount.required' => 'Please select or enter the points amount.',
            'amount.min' => 'Minimum virtual points to add is 50.',
            'amount.max' => 'Maximum points per transaction is 50,000.',
        ]);

        $amount = (int) $validated['amount'];

        // Record credit via atomic ledger transaction
        \App\Models\LudoLedgerTransaction::recordAdjustment(
            $user,
            $amount,
            'deposit',
            "Virtual tournament points reload of 🪙 {$amount}",
            'successful'
        );

        return redirect()->back()->with('success', "🪙 {$amount} Virtual Points credited successfully to your wallet!");
    }

    /**
     * Match & Platform History Screen (3 Tabs: Game, Transaction, Referral)
     */
    public function history(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            $user = User::firstOrCreate(
                ['email' => 'player1@royalludo.test'],
                [
                    'name' => 'Royal_Player',
                    'password' => bcrypt('secret123'),
                    'coins' => 1000,
                    'referral_code' => '780547',
                    'player_uid' => 'LUDO00001',
                ]
            );
            Auth::login($user);
        }

        $coins = $user->coins;
        $referral = $user->referral_balance;

        // 1. Game History
        $gameQuery = LudoBattle::with(['creator', 'joiner', 'winner'])
            ->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)
                  ->orWhere('joiner_id', $user->id);
            })
            ->latest('id');

        if ($request->filled('game_status')) {
            $gameQuery->where('status', $request->game_status);
        }
        $gameHistory = $gameQuery->paginate(5, ['*'], 'game_page');

        // 2. Transaction History
        $txQuery = \App\Models\LudoLedgerTransaction::where('user_id', $user->id)
            ->latest('id');

        if ($request->filled('tx_type')) {
            $txQuery->where('type', $request->tx_type);
        }
        if ($request->filled('tx_status')) {
            $txQuery->where('status', $request->tx_status);
        }
        $txHistory = $txQuery->paginate(5, ['*'], 'tx_page');

        // 3. Referral History
        $referralHistory = \App\Models\LudoReferral::with('referredUser')
            ->where('referrer_id', $user->id)
            ->latest('id')
            ->paginate(5, ['*'], 'ref_page');

        return view('ludo.history', [
            'coins' => $coins,
            'referral' => $referral,
            'gameHistory' => $gameHistory,
            'txHistory' => $txHistory,
            'referralHistory' => $referralHistory,
            'activeTab' => 'home',
        ]);
    }

    /**
     * KYC Verification Screen
     */
    public function kyc()
    {
        $user = Auth::user();
        $coins = $user ? $user->coins : 0;
        $referral = $user ? $user->referral_balance : 0.00;

        return view('ludo.kyc', [
            'coins' => $coins,
            'referral' => $referral,
            'activeTab' => 'profile',
        ]);
    }
}
