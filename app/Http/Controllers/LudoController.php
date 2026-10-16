<?php

namespace App\Http\Controllers;

use App\Models\LudoBattle;
use App\Models\LudoSupportTicket;
use App\Models\LudoTicketMessage;
use App\Models\LudoKycSubmission;
use App\Models\LudoDispute;
use App\Models\LudoDisputeEvidence;
use App\Models\LudoLedgerTransaction;
use App\Models\LudoTournament;
use App\Models\LudoTournamentParticipant;
use App\Models\LudoUpiDeposit;
use App\Models\LudoWithdrawalRequest;
use App\Models\LudoSetting;

use App\Models\LudoAdminAuditLog;
use App\Models\User;
use App\Notifications\LudoInAppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\FileUploadService;

class LudoController extends Controller
{
    /**
     * Home Lobby — Fsaludoclub Platform
     */
    public function home()
    {
        $user = Auth::user();
        $coins = $user ? $user->coins : 0;
        $referral = $user ? $user->referral_balance : 0.00;
        $announcement = LudoSetting::get('announcement_hi', '⭐ 🎉 📢 Fsaludoclub परिवार में आप सभी प्लेयरों का स्वागत है! किसी भी प्लेयर के साथ ना कभी गलत होगा ना ही कभी गलत होने देगा 🙏🙏 विश्वास बनाए रखें 🙏🙏');

        return view('ludo.home', [
            'coins' => $coins,
            'referral' => $referral,
            'announcement' => $announcement,
            'activeTab' => 'home',
        ]);
    }

    /**
     * Open & Running Battles Screen (Screenshot #5 & Play Page)
     */
    public function battles()
    {
        LudoBattle::processExpiredDeadlines();

        if (!app()->environment('production', 'testing')) {
            $demoRunningCount = LudoBattle::where('status', 'running')
                ->where('battle_uid', 'like', 'DEMO-%')
                ->count();
            if ($demoRunningCount < 10) {
                (new \Database\Seeders\RunningBattleDemoSeeder())->run();
            }
        }

        $user = Auth::user();
        $coins = $user ? $user->fresh()->coins : 0;
        $referral = $user ? $user->fresh()->referral_balance : 0.00;
        $announcement = LudoSetting::get('announcement_hi', '⭐ 🙏 🙏 सभी यूजर ध्यान दें: मैच शुरू होने के 15 मिनट के अंदर रूम कोड डालें। किसी भी विवाद की स्थिति में स्क्रीनशॉट अनिवार्य है।');
        $minBattlePoints = (int) LudoSetting::get('minimum_battle_amount', 50);

        $filterRealUsers = function ($query) {
            if (!app()->environment('testing')) {
                $query->whereHas('creator', function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('phone')
                            ->orWhere('email', 'like', '%@fsaludoclub%');
                    })
                    ->where('email', 'not like', '%@royalludo.test%')
                    ->where('email', 'not like', '%@ludoking.test%')
                    ->where('email', 'not like', '%fakedata%');
                });
            }
        };

        // Fetch initial batch of 5 Open Battles (and requested battles only for participating players)
        $currentUserId = auth()->id();
        $openQuery = LudoBattle::with(['creator', 'joiner'])
            ->where(function ($q) use ($currentUserId) {
                $q->where('status', 'open');
                if ($currentUserId) {
                    $q->orWhere(function ($sub) use ($currentUserId) {
                        $sub->where('status', 'requested')
                            ->where(function ($userSub) use ($currentUserId) {
                                 $userSub->where('creator_id', $currentUserId)
                                         ->orWhere('joiner_id', $currentUserId);
                            });
                    });
                }
            });
        $filterRealUsers($openQuery);

        $openBattles = $openQuery->latest('id')
            ->take(6)
            ->get();

        $openBattlesHasMore = $openBattles->count() > 5;
        if ($openBattlesHasMore) {
            $openBattles = $openBattles->slice(0, 5);
        }

        // Fetch initial batch of Running Battles (up to 15 so all 10-12 demo battles are immediately visible)
        $runningQuery = LudoBattle::with(['creator', 'joiner'])
            ->where('status', 'running')
            ->whereNotNull('joiner_id');
        $filterRealUsers($runningQuery);

        $runningBattles = $runningQuery->latest('started_at')
            ->latest('id')
            ->take(16)
            ->get();

        $runningBattlesHasMore = $runningBattles->count() > 15;
        if ($runningBattlesHasMore) {
            $runningBattles = $runningBattles->slice(0, 15);
        }

        return view('ludo.battles', [
            'coins' => $coins,
            'referral' => $referral,
            'announcement' => $announcement,
            'minBattlePoints' => $minBattlePoints,
            'openBattles' => $openBattles,
            'openBattlesHasMore' => $openBattlesHasMore,
            'runningBattles' => $runningBattles,
            'runningBattlesHasMore' => $runningBattlesHasMore,
            'activeTab' => 'home',
        ]);
    }

    /**
     * Infinite Scroll / Load More Battles Endpoint
     * Supports cursor-based pagination with stable tie-breaker ordering and N+1 prevention.
     */
    public function loadMoreBattles(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:open,running'],
            'cursor' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $type = $validated['type'];
        $limit = (int) ($validated['per_page'] ?? 5);

        LudoBattle::processExpiredDeadlines();

        $filterRealUsers = function ($query) {
            if (!app()->environment('testing')) {
                $query->whereHas('creator', function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('phone')
                            ->orWhere('email', 'like', '%@fsaludoclub%');
                    })
                    ->where('email', 'not like', '%@royalludo.test%')
                    ->where('email', 'not like', '%@ludoking.test%')
                    ->where('email', 'not like', '%fakedata%');
                });
            }
        };

        if ($type === 'open') {
            $currentUserId = auth()->id();
            $query = LudoBattle::with(['creator' => function ($q) {
                $q->select('id', 'name', 'phone');
            }, 'joiner' => function ($q) {
                $q->select('id', 'name', 'phone');
            }])
            ->where(function ($q) use ($currentUserId) {
                $q->where('status', 'open');
                if ($currentUserId) {
                    $q->orWhere(function ($sub) use ($currentUserId) {
                        $sub->where('status', 'requested')
                            ->where(function ($userSub) use ($currentUserId) {
                                $userSub->where('creator_id', $currentUserId)
                                        ->orWhere('joiner_id', $currentUserId);
                            });
                    });
                }
            });

            $filterRealUsers($query);

            if ($request->filled('cursor')) {
                // Strictly monotonic cursor on ID
                $query->where('id', '<', (int) $request->cursor);
            } elseif ($request->filled('page') && (int) $request->page > 1) {
                $query->skip(((int) $request->page - 1) * $limit);
            }

            $battles = $query->latest('id')
                ->take($limit + 1)
                ->get();

            $hasMore = $battles->count() > $limit;
            if ($hasMore) {
                $battles = $battles->slice(0, $limit);
            }

            $nextCursor = $battles->last() ? $battles->last()->id : null;

            $html = '';
            foreach ($battles as $battle) {
                $html .= view('ludo.partials.open_battle_card', ['battle' => $battle])->render();
            }

            return response()->json([
                'success' => true,
                'type' => 'open',
                'count' => $battles->count(),
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
                'html' => $html,
                'items' => $battles->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'battle_uid' => $b->battle_uid,
                        'creator_id' => $b->creator_id,
                        'creator_name' => $b->creator ? ($b->creator->name ?: 'Player_' . substr($b->creator->phone ?? $b->creator->id, -4)) : 'Player_' . $b->creator_id,
                        'entry_fee' => $b->entry_fee,
                        'prize' => $b->prize,
                        'status' => $b->status,
                        'created_at' => $b->created_at?->toIso8601String(),
                    ];
                }),
            ]);
        } else {
            $query = LudoBattle::with([
                'creator' => function ($q) { $q->select('id', 'name', 'phone'); },
                'joiner' => function ($q) { $q->select('id', 'name', 'phone'); },
            ])
            ->where('status', 'running')
            ->whereNotNull('joiner_id');

            $filterRealUsers($query);

            if ($request->filled('cursor')) {
                $query->where('id', '<', (int) $request->cursor);
            } elseif ($request->filled('page') && (int) $request->page > 1) {
                $query->skip(((int) $request->page - 1) * $limit);
            }

            $battles = $query->latest('started_at')
                ->latest('id')
                ->take($limit + 1)
                ->get();

            $hasMore = $battles->count() > $limit;
            if ($hasMore) {
                $battles = $battles->slice(0, $limit);
            }

            $nextCursor = $battles->last() ? $battles->last()->id : null;

            $html = '';
            foreach ($battles as $running) {
                $html .= view('ludo.partials.running_battle_card', ['running' => $running])->render();
            }

            return response()->json([
                'success' => true,
                'type' => 'running',
                'count' => $battles->count(),
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
                'html' => $html,
                'items' => $battles->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'battle_uid' => $b->battle_uid,
                        'creator_id' => $b->creator_id,
                        'creator_name' => $b->creator->name ?? 'Player_1',
                        'joiner_id' => $b->joiner_id,
                        'joiner_name' => $b->joiner->name ?? 'Player_2',
                        'entry_fee' => $b->entry_fee,
                        'prize' => $b->prize,
                        'room_code' => $b->room_code,
                        'status' => $b->status,
                        'started_at' => $b->started_at?->toIso8601String(),
                    ];
                }),
            ]);
        }
    }

    /**
     * Create a Battle Action (Server Validation, Free/Virtual Points Only)
     */
    public function createBattle(Request $request)
    {
        $minPoints = (int) LudoSetting::get('minimum_battle_amount', 50);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:' . $minPoints, 'max:50000'],
        ], [
            'amount.required' => 'Please enter the tournament points amount.',
            'amount.integer' => 'Tournament points must be a valid whole number.',
            'amount.min' => "Minimum battle entry points is {$minPoints}.",
            'amount.max' => 'Maximum battle entry points per challenge is 50,000.',
        ]);

        $amount = (int) $validated['amount'];

        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to continue.',
                    'redirect' => route('login'),
                ], 401);
            }
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to continue.')
                ->with('message', 'Please log in to continue.');
        }

        // KYC check if mandatory
        $isKycMandatory = LudoSetting::get('kyc_mandatory', 'off') === 'on' || config('ludo.kyc.required_for_battles', false);
        if ($isKycMandatory && $user->kyc_status !== 'verified') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'KYC identity verification is required before creating tournament battles.'], 403);
            }
            return redirect()->route('ludo.kyc')
                ->with('error', 'KYC identity verification is required before creating tournament battles.');
        }

        return DB::transaction(function () use ($user, $amount, $request) {
            $lockedUser = User::lockForUpdate()->find($user->id);

            // Check virtual points balance
            if ($lockedUser->coins < $amount) {
                $msg = "Insufficient balance. Your available balance is {$lockedUser->coins} points, but this battle requires {$amount} points. Please add balance before continuing.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'INSUFFICIENT_BALANCE',
                        'available_coins' => (int) $lockedUser->coins,
                        'required_coins' => (int) $amount,
                        'wallet_url' => route('ludo.wallet'),
                        'message' => $msg,
                    ], 422);
                }
                return redirect()->back()
                    ->with('error', $msg)
                    ->withInput();
            }

            // Prevent duplicate open battles by the same creator for the same amount
            $existingOpen = LudoBattle::where('creator_id', $lockedUser->id)
                ->whereIn('status', ['open', 'requested'])
                ->where('entry_fee', $amount)
                ->first();

            if ($existingOpen) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => "You already have an active battle for 🪙 {$amount} (ID: {$existingOpen->battle_uid})."], 422);
                }
                return redirect()->back()
                    ->with('error', "You already have an active open battle for 🪙 {$amount} (ID: {$existingOpen->battle_uid}).");
            }

            // Use atomic ledger transaction for balance change
            \App\Models\LudoLedgerTransaction::recordAdjustment(
                $lockedUser,
                -$amount,
                'entry_fee',
                "Battle creation points entry for 🪙 {$amount}",
                'successful'
            );

            // Create the battle record
            $battle = LudoBattle::create([
                'battle_uid' => 'BAT-' . strtoupper(Str::random(8)),
                'creator_id' => $lockedUser->id,
                'entry_fee' => $amount,
                'prize' => LudoBattle::calculatePrize($amount),
                'status' => 'open',
                'claimed_result' => 'pending',
                'final_result' => 'pending',
                'review_status' => 'auto_approved',
            ]);

            // Load creator relation for partial view rendering
            $battle->load('creator');

            if ($request->expectsJson() || $request->ajax()) {
                $html = view('ludo.partials.open_battle_card', ['battle' => $battle])->render();
                return response()->json([
                    'success' => true,
                    'message' => "🎉 Battle {$battle->battle_uid} created for 🪙 {$amount}!",
                    'battle' => $battle,
                    'html' => $html,
                    'coins' => $lockedUser->fresh()->coins,
                ]);
            }

            return redirect()->back()->with('success', "🎉 Battle {$battle->battle_uid} created for 🪙 {$amount}! Waiting for an opponent to join.");
        });
    }

    /**
     * Join an Open Battle Action (Pessimistic Concurrency Safe)
     */
    public function joinBattle(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to continue.',
                    'redirect' => route('login'),
                ], 401);
            }
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to continue.')
                ->with('message', 'Please log in to continue.');
        }

        // KYC check if mandatory
        $isKycMandatory = LudoSetting::get('kyc_mandatory', 'off') === 'on' || config('ludo.kyc.required_for_battles', false);
        if ($isKycMandatory && $user->kyc_status !== 'verified') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'KYC identity verification is required before joining tournament battles.'], 403);
            }
            return redirect()->route('ludo.kyc')
                ->with('error', 'KYC identity verification is required before joining tournament battles.');
        }

        return DB::transaction(function () use ($id, $user, $request) {
            // Lock battle exclusively to prevent race conditions
            $battle = LudoBattle::with('creator')->lockForUpdate()->find($id);

            if (!$battle) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Battle challenge not found.'], 404);
                }
                return redirect()->back()->with('error', 'Battle challenge not found.');
            }

            // Verify battle is open
            if ($battle->status !== 'open') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'This battle is no longer open for joining.'], 422);
                }
                return redirect()->back()->with('error', 'This battle is no longer open for joining.');
            }

            // Prevent creator from playing against themselves
            if ($battle->creator_id === $user->id) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'You cannot join your own battle challenge.'], 422);
                }
                return redirect()->back()->with('error', 'You cannot join your own battle challenge.');
            }

            // Check if opponent seat has already been taken
            if ($battle->joiner_id !== null) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'This battle seat has already been taken by another player.'], 422);
                }
                return redirect()->back()->with('error', 'This battle seat has already been taken by another player.');
            }

            // Lock joining player row exclusively
            $lockedUser = User::lockForUpdate()->find($user->id);

            // Check points balance
            if ($lockedUser->coins < $battle->entry_fee) {
                $msg = "Insufficient balance. Your available balance is {$lockedUser->coins} points, but this battle requires {$battle->entry_fee} points. Please add balance before continuing.";
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'error_code' => 'INSUFFICIENT_BALANCE',
                        'available_coins' => (int) $lockedUser->coins,
                        'required_coins' => (int) $battle->entry_fee,
                        'wallet_url' => route('ludo.wallet'),
                        'message' => $msg,
                    ], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            $creatorDisplayName = $battle->creator ? ($battle->creator->name ?: 'Player_' . substr($battle->creator->phone ?? $battle->creator->id, -4)) : 'Player_' . $battle->creator_id;
            $joinerDisplayName = $lockedUser->name ?: 'Player_' . substr($lockedUser->phone ?? $lockedUser->id, -4);

            // Deduct points from joiner atomically via ledger
            \App\Models\LudoLedgerTransaction::recordAdjustment(
                $lockedUser,
                -$battle->entry_fee,
                'entry_fee',
                "Joined Battle {$battle->battle_uid} vs {$creatorDisplayName}",
                'successful'
            );

            // Transition battle to requested state so creator sees START & CANCEL buttons
            $battle->update([
                'joiner_id' => $lockedUser->id,
                'status' => 'requested',
                'room_code_expires_at' => now()->addSeconds(180),
            ]);

            // Send in-app notification to creator
            $battle->creator->notify(new LudoInAppNotification(
                "⚔️ Opponent Joined!",
                "{$joinerDisplayName} accepted your challenge {$battle->battle_uid}. Click START to play!",
                "match",
                route('ludo.battles.view', $battle->id)
            ));

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "⚔️ You joined {$battle->battle_uid} against {$creatorDisplayName}!",
                    'redirect' => route('ludo.battles.view', $battle->id),
                    'battle_id' => $battle->id,
                    'coins' => $lockedUser->fresh()->coins,
                    'status' => 'requested'
                ]);
            }

            return redirect()->route('ludo.battles.view', $battle->id)->with('success', "⚔️ You joined {$battle->battle_uid} against {$creatorDisplayName}!");
        });
    }

    /**
     * Start a Requested Battle (Creator clicks START)
     */
    public function startBattle(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to continue.',
                    'redirect' => route('login'),
                ], 401);
            }
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to continue.')
                ->with('message', 'Please log in to continue.');
        }

        return DB::transaction(function () use ($id, $user, $request) {
            $battle = LudoBattle::with(['creator', 'joiner'])->lockForUpdate()->find($id);

            if (!$battle) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Battle not found.'], 404);
                }
                return redirect()->back()->with('error', 'Battle not found.');
            }

            if ($battle->creator_id !== $user->id) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Only the match creator can start the battle.'], 403);
                }
                return redirect()->back()->with('error', 'Only the match creator can start the battle.');
            }

            if ($battle->status === 'open') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Waiting for an opponent to join before starting.'], 422);
                }
                return redirect()->back()->with('error', 'Waiting for an opponent to join before starting.');
            }

            if ($battle->status !== 'requested') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'redirect' => route('ludo.battles.view', $battle->id),
                        'status' => $battle->status
                    ]);
                }
                return redirect()->route('ludo.battles.view', $battle->id);
            }

            $submissionWindow = (int) LudoSetting::get('submission_window', config('ludo.battle.submission_window_minutes', 15));
            $battle->update([
                'status' => 'running',
                'started_at' => now(),
                'submission_deadline_at' => now()->addMinutes($submissionWindow),
                'room_code_expires_at' => now()->addSeconds(180),
            ]);

            // Notify joiner that match has started
            if ($battle->joiner) {
                $battle->joiner->notify(new LudoInAppNotification(
                    "🎮 Match Started!",
                    "Match {$battle->battle_uid} has started! Room code upload is active (180s countdown).",
                    "match",
                    route('ludo.battles.view', $battle->id)
                ));
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => '⚔️ Match started! Please upload or enter the Ludo King Room Code.',
                    'redirect' => route('ludo.battles.view', $battle->id),
                    'battle_id' => $battle->id,
                    'status' => 'running'
                ]);
            }

            return redirect()->route('ludo.battles.view', $battle->id)->with('success', "⚔️ Match started! Please upload or enter the Ludo King Room Code.");
        });
    }

    /**
     * View Match Arena / Room Code Screen (Screenshot #4 & #5)
     */
    public function viewBattle(Request $request, $id)
    {
        $battle = LudoBattle::with(['creator', 'joiner', 'winner'])->findOrFail($id);
        $battle->checkAndProcessSubmissionExpiry();
        $battle->refresh();

        $user = Auth::user();

        $coins = $user ? $user->coins : 0;
        $referral = $user ? $user->referral_balance : 0.00;
        $announcement = '🙏 🙏 सभी प्लेयरों से निवेदन है कि रिजल्ट सही डालें यदि बिना गोटी ओपन हुए कोई बंदा लेफ्ट हो जाता है या ऑटो एग्जिट हो जाता है तो टेबल कैंसिल ही मानी जाएगी अत्यंत नियोजन है कि उसमें स्क्रीनशॉट अपलोड ना करें और टेबल कैंसिल होगी तो कैंसिल रिजल्ट डालें उसमें 📢';

        $isCreator = $user && $user->id === $battle->creator_id;
        $isJoiner = $user && $user->id === $battle->joiner_id;
        $isAdmin = $user && in_array($user->role, ['admin', 'superadmin']);

        if (!$isCreator && !$isJoiner && !$isAdmin) {
            abort(403, 'Unauthorized: You are not a participant in this match.');
        }

        // Calculate countdown for Room Code upload (180s from start or creation)
        $remainingSeconds = 180;
        if ($battle->room_code_expires_at) {
            $remainingSeconds = max(0, now()->diffInSeconds($battle->room_code_expires_at, false));
        }

        return view('ludo.match_arena', [
            'battle' => $battle,
            'user' => $user,
            'isCreator' => $isCreator,
            'isJoiner' => $isJoiner,
            'coins' => $coins,
            'referral' => $referral,
            'announcement' => $announcement,
            'remainingSeconds' => $remainingSeconds,
            'activeTab' => 'home',
        ]);
    }

    /**
     * Set Ludo King Room Code for Battle (Concurrency-safe & Immutable once set)
     */
    public function setRoomCode(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to continue.',
                    'redirect' => route('login'),
                ], 401);
            }
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to continue.')
                ->with('message', 'Please log in to continue.');
        }

        $validated = $request->validate([
            'room_code' => ['required', 'string', 'min:4', 'max:25'],
        ], [
            'room_code.required' => 'Please enter the Ludo King Room Code.',
        ]);

        $cleanCode = strtoupper(preg_replace('/\s+/', '', $validated['room_code']));

        return DB::transaction(function () use ($id, $user, $cleanCode, $request) {
            $battle = LudoBattle::with(['creator', 'joiner'])->lockForUpdate()->find($id);

            if (!$battle) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Battle not found.'], 404);
                }
                return redirect()->back()->with('error', 'Battle not found.');
            }

            if ($user->id !== $battle->creator_id && $user->id !== $battle->joiner_id) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized: Only participating players can upload the room code.'], 403);
                }
                return redirect()->back()->with('error', 'Unauthorized: Only participating players can upload the room code.');
            }

            if ($battle->status !== 'running') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => "Cannot set room code for a {$battle->status} match."], 422);
                }
                return redirect()->back()->with('error', "Cannot set room code for a {$battle->status} match.");
            }

            // Prevent unauthorized replacement if already set
            if (!empty($battle->room_code)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => "Room code is already set to {$battle->room_code} and cannot be overwritten."], 422);
                }
                return redirect()->back()->with('error', "Room code is already set to {$battle->room_code} and cannot be overwritten.");
            }

            // Check timer expiry
            if ($battle->room_code_expires_at && now()->greaterThan($battle->room_code_expires_at)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Room code countdown has expired.'], 422);
                }
                return redirect()->back()->with('error', 'Room code countdown has expired.');
            }

            $battle->update([
                'room_code' => $cleanCode,
                'started_at' => $battle->started_at ?? now(),
            ]);

            // Notify opponent
            $opponent = $user->id === $battle->creator_id ? $battle->joiner : $battle->creator;
            if ($opponent) {
                $opponent->notify(new LudoInAppNotification(
                    "🎮 Room Code Set!",
                    "Room code {$cleanCode} has been uploaded for battle {$battle->battle_uid}. Open Ludo King to join!",
                    "match",
                    route('ludo.battles.view', $battle->id)
                ));
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "🎮 Room code set to {$cleanCode}! Open Ludo King app and play.",
                    'room_code' => $cleanCode,
                ]);
            }

            return redirect()->back()->with('success', "🎮 Room code set to {$cleanCode}! Open Ludo King app and play.");
        });
    }

    /**
     * Cancel Match with Specific Reason (Screenshot #5 Modal Submission)
     */
    public function cancelWithReason(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to continue.',
                    'redirect' => route('login'),
                ], 401);
            }
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to continue.')
                ->with('message', 'Please log in to continue.');
        }

        $allowedReasons = [
            'No Room code',
            'Not joined',
            'Not Playing',
            'Dont Want to Play',
            'Opponent Abusing',
            'Game not Started',
            'Others'
        ];

        $reason = $request->input('reason', 'Cancelled by player');
        if (!in_array($reason, $allowedReasons)) {
            $reason = 'Others';
        }

        return DB::transaction(function () use ($id, $user, $reason, $request) {
            $battle = LudoBattle::with(['creator', 'joiner'])->lockForUpdate()->find($id);

            if (!$battle) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Battle not found.'], 404);
                }
                return redirect()->route('ludo.battles')->with('error', 'Battle not found.');
            }

            if ($user->id !== $battle->creator_id && $user->id !== $battle->joiner_id) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized: You are not a participant in this match.'], 403);
                }
                return redirect()->back()->with('error', 'Unauthorized: You are not a participant in this match.');
            }

            if (in_array($battle->status, ['completed', 'cancelled'])) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'This match is already finalized.'], 422);
                }
                return redirect()->route('ludo.battles')->with('error', 'This match is already finalized.');
            }

            // P0 state machine: In-progress matches CANNOT be cancelled directly by players
            if ($battle->status === 'running') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Matches in progress cannot be cancelled directly. Please submit your match result or raise a dispute for administrative review.'
                    ], 422);
                }
                return redirect()->back()->with('error', 'Matches in progress cannot be cancelled directly. Please submit your match result or raise a dispute for administrative review.');
            }

            // State machine: Under review or disputed matches are frozen
            if (in_array($battle->review_status, ['under_review', 'disputed']) || $battle->refund_status === 'pending') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This match is currently under administrative review. Direct cancellation is disabled.'
                    ], 422);
                }
                return redirect()->back()->with('error', 'This match is currently under administrative review. Direct cancellation is disabled.');
            }

            // Reject cancel from a player who already submitted a result
            $userClaim = ($user->id === $battle->creator_id) ? $battle->creator_claimed_result : $battle->joiner_claimed_result;
            if ($userClaim && $userClaim !== 'pending') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You have already submitted a result for this match. Cancellation is rejected.'
                    ], 422);
                }
                return redirect()->back()->with('error', 'You have already submitted a result for this match. Cancellation is rejected.');
            }

            if (!in_array($battle->status, ['open', 'requested'])) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'This battle cannot be cancelled in its current state.'], 422);
                }
                return redirect()->back()->with('error', 'This battle cannot be cancelled in its current state.');
            }

            if ($battle->status === 'open' && $user->id !== $battle->creator_id) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Only the battle creator can cancel an open battle.'], 403);
                }
                return redirect()->back()->with('error', 'Only the battle creator can cancel an open battle.');
            }

            // Refund points to creator (idempotent, guard against double refund)
            if ($battle->creator) {
                $alreadyRefundedCreator = \App\Models\LudoLedgerTransaction::where('user_id', $battle->creator_id)
                    ->where('type', 'refund')
                    ->where('description', 'like', "%{$battle->battle_uid}%")
                    ->where('status', 'successful')
                    ->exists();

                if (!$alreadyRefundedCreator) {
                    \App\Models\LudoLedgerTransaction::recordAdjustment(
                        $battle->creator,
                        $battle->entry_fee,
                        'refund',
                        "Match {$battle->battle_uid} cancelled: {$reason}",
                        'successful'
                    );
                }
            }

            // Refund points to joiner if joined (idempotent, guard against double refund)
            if ($battle->joiner) {
                $alreadyRefundedJoiner = \App\Models\LudoLedgerTransaction::where('user_id', $battle->joiner_id)
                    ->where('type', 'refund')
                    ->where('description', 'like', "%{$battle->battle_uid}%")
                    ->where('status', 'successful')
                    ->exists();

                if (!$alreadyRefundedJoiner) {
                    \App\Models\LudoLedgerTransaction::recordAdjustment(
                        $battle->joiner,
                        $battle->entry_fee,
                        'refund',
                        "Match {$battle->battle_uid} cancelled: {$reason}",
                        'successful'
                    );
                }
            }

            $battle->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by_id' => $user->id,
                'refund_status' => 'approved',
                'completed_at' => now(),
            ]);

            // Notify other participant
            $otherParticipant = $user->id === $battle->creator_id ? $battle->joiner : $battle->creator;
            if ($otherParticipant) {
                $otherParticipant->notify(new LudoInAppNotification(
                    "🚫 Match Cancelled",
                    "Match {$battle->battle_uid} was cancelled ({$reason}). 🪙 {$battle->entry_fee} points refunded.",
                    "ledger",
                    url('/wallet')
                ));
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Match cancelled (Reason: {$reason}). 🪙 {$battle->entry_fee} refunded.",
                    'redirect' => route('ludo.battles')
                ]);
            }

            return redirect()->route('ludo.battles')->with('success', "Match cancelled (Reason: {$reason}). 🪙 {$battle->entry_fee} refunded.");
        });
    }

    /**
     * Cancel an Open Battle (Refund points to creator)
     */
    public function cancelBattle(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to continue.',
                    'redirect' => route('login'),
                ], 401);
            }
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to continue.')
                ->with('message', 'Please log in to continue.');
        }

        return DB::transaction(function () use ($id, $user, $request) {
            $battle = LudoBattle::with(['creator', 'joiner'])->lockForUpdate()->find($id);

            if (!$battle) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Battle not found.'], 404);
                }
                return redirect()->back()->with('error', 'Battle not found.');
            }

            if ($battle->creator_id !== $user->id && $battle->joiner_id !== $user->id) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized: Only match participants can cancel.'], 403);
                }
                return redirect()->back()->with('error', 'Unauthorized: Only match participants can cancel.');
            }

            if (in_array($battle->status, ['completed', 'cancelled'])) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'This match is already finalized.'], 422);
                }
                return redirect()->back()->with('error', 'This match is already finalized.');
            }

            if ($battle->status === 'running') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Matches in progress cannot be cancelled directly. Please submit your match result or raise a dispute for administrative review.'
                    ], 422);
                }
                return redirect()->back()->with('error', 'Matches in progress cannot be cancelled directly.');
            }

            if (in_array($battle->review_status, ['under_review', 'disputed']) || $battle->refund_status === 'pending') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'This match is under administrative review. Direct cancellation is disabled.'], 422);
                }
                return redirect()->back()->with('error', 'This match is under administrative review.');
            }

            $userClaim = ($user->id === $battle->creator_id) ? $battle->creator_claimed_result : $battle->joiner_claimed_result;
            if ($userClaim && $userClaim !== 'pending') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'You have already submitted a result for this match. Cancellation is rejected.'], 422);
                }
                return redirect()->back()->with('error', 'You have already submitted a result for this match.');
            }

            if (!in_array($battle->status, ['open', 'requested'])) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'This battle cannot be cancelled.'], 422);
                }
                return redirect()->back()->with('error', 'This battle cannot be cancelled.');
            }

            if ($battle->status === 'open' && $user->id !== $battle->creator_id) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Only the battle creator can cancel an open battle.'], 403);
                }
                return redirect()->back()->with('error', 'Only the battle creator can cancel an open battle.');
            }

            // Refund creator (idempotent, guard against double refund)
            if ($battle->creator) {
                $alreadyRefundedCreator = \App\Models\LudoLedgerTransaction::where('user_id', $battle->creator_id)
                    ->where('type', 'refund')
                    ->where('description', 'like', "%{$battle->battle_uid}%")
                    ->where('status', 'successful')
                    ->exists();

                if (!$alreadyRefundedCreator) {
                    \App\Models\LudoLedgerTransaction::recordAdjustment(
                        $battle->creator,
                        $battle->entry_fee,
                        'refund',
                        "Refund for cancelled Battle {$battle->battle_uid}",
                        'successful'
                    );
                }
            }

            // Refund joiner if present (idempotent, guard against double refund)
            if ($battle->joiner) {
                $alreadyRefundedJoiner = \App\Models\LudoLedgerTransaction::where('user_id', $battle->joiner_id)
                    ->where('type', 'refund')
                    ->where('description', 'like', "%{$battle->battle_uid}%")
                    ->where('status', 'successful')
                    ->exists();

                if (!$alreadyRefundedJoiner) {
                    \App\Models\LudoLedgerTransaction::recordAdjustment(
                        $battle->joiner,
                        $battle->entry_fee,
                        'refund',
                        "Refund for cancelled Battle {$battle->battle_uid}",
                        'successful'
                    );
                }
            }

            $battle->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'Cancelled by player',
                'cancelled_by_id' => $user->id,
                'refund_status' => 'approved',
                'completed_at' => now(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Battle {$battle->battle_uid} cancelled. 🪙 {$battle->entry_fee} refunded.",
                    'battle_id' => $battle->id,
                    'coins' => $user->fresh()->coins,
                ]);
            }

            return redirect()->back()->with('success', "Battle {$battle->battle_uid} deleted. 🪙 {$battle->entry_fee} refunded to your balance.");
        });
    }

    /**
     * Real-time Synchronization Endpoint for Lobby & Active Arena
     */
    public function syncState(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['authenticated' => false]);
        }

        LudoBattle::processExpiredDeadlines();

        $data = [
            'authenticated' => true,
            'user_id' => $user->id,
            'coins' => $user->fresh() ? (int) $user->fresh()->coins : (int) $user->coins,
            'open_battles_count' => LudoBattle::whereIn('status', ['open', 'requested'])->count(),
        ];

        // 1. If checking specific battle (in Match Arena)
        if ($request->filled('battle_id')) {
            $battle = LudoBattle::with(['creator', 'joiner', 'winner'])->find($request->battle_id);
            if ($battle) {
                $battle->checkAndProcessSubmissionExpiry();
                $battle->refresh();

                $remSec = 180;
                if ($battle->room_code_expires_at) {
                    $remSec = max(0, now()->diffInSeconds($battle->room_code_expires_at, false));
                }

                $isCreator = ($user->id === $battle->creator_id);
                $isJoiner = ($user->id === $battle->joiner_id);
                $isAdmin = in_array($user->role, ['admin', 'superadmin', 'support_staff', 'moderator']);
                $isAuthorized = ($isCreator || $isJoiner || $isAdmin);

                $myClaim = $isCreator ? $battle->creator_claimed_result : $battle->joiner_claimed_result;
                $opponentClaim = $isCreator ? $battle->joiner_claimed_result : $battle->creator_claimed_result;
                $opponent = $isCreator ? $battle->joiner : $battle->creator;

                $claimStatusMsg = null;
                if ($opponentClaim === 'won') {
                    $oppUserTag = $opponent ? $opponent->formatted_username : 'Your opponent';
                    $claimStatusMsg = "Your opponent ({$oppUserTag}) has submitted a Victory Claim. Awaiting verification.";
                }

                $revBadge = $battle->getFormattedReviewBadge();
                $statBadge = $battle->getFormattedStatusBadge();
                $expiredText = $battle->getExpiredReviewStatusText();

                $data['battle'] = [
                    'id' => $battle->id,
                    'battle_uid' => $battle->battle_uid,
                    'status' => $battle->status,
                    'review_status' => $battle->review_status,
                    'refund_status' => $battle->refund_status,
                    'is_deadline_expired' => $battle->isSubmissionDeadlineExpired(),
                    'expired_review_status_text' => $expiredText,
                    'review_badge_text' => $revBadge['text'],
                    'review_badge_class' => $revBadge['class'],
                    'status_badge_text' => $statBadge['text'],
                    'status_badge_class' => $statBadge['class'],
                    'submission_deadline_at' => $battle->submission_deadline_at?->toIso8601String(),
                    'room_code' => $isAuthorized ? $battle->room_code : null,
                    'remaining_seconds' => $remSec,
                    'cancellation_reason' => $battle->cancellation_reason,
                    'refund_reason' => $battle->refund_reason,
                    'creator_id' => $battle->creator_id,
                    'joiner_id' => $battle->joiner_id,
                    'creator_name' => $battle->creator->name ?? 'Player 1',
                    'creator_username' => $battle->creator ? $battle->creator->formatted_username : null,
                    'joiner_name' => $battle->joiner ? $battle->joiner->name : null,
                    'joiner_username' => $battle->joiner ? $battle->joiner->formatted_username : null,
                    'opponent_name' => $opponent ? $opponent->name : null,
                    'opponent_username' => $opponent ? $opponent->formatted_username : null,
                    'creator_claimed_result' => $isAuthorized ? $battle->creator_claimed_result : null,
                    'joiner_claimed_result' => $isAuthorized ? $battle->joiner_claimed_result : null,
                    'my_claimed_result' => $isAuthorized ? $myClaim : null,
                    'opponent_claimed_result' => $isAuthorized ? $opponentClaim : null,
                    'claim_status_message' => $isAuthorized ? $claimStatusMsg : null,
                    'winner_id' => $battle->winner_id,
                    'winner_name' => $battle->winner ? $battle->winner->name : null,
                    'winner_username' => $battle->winner ? $battle->winner->formatted_username : null,
                    'prize' => (int) $battle->prize,
                    'entry_fee' => (int) $battle->entry_fee,
                    'authorized' => $isAuthorized,
                ];
            }
        }

        // 2. Check if user has an active created battle that was just joined ('requested' state)
        $myRequestedBattle = LudoBattle::with(['joiner', 'creator'])
            ->where('creator_id', $user->id)
            ->where('status', 'requested')
            ->latest('updated_at')
            ->first();

        if ($myRequestedBattle) {
            $eventId = 'req_' . $myRequestedBattle->id . '_' . ($myRequestedBattle->joiner_id ?? 0) . '_' . ($myRequestedBattle->updated_at?->timestamp ?? 0);
            $data['creator_pending_start'] = [
                'event_id' => $eventId,
                'battle_id' => $myRequestedBattle->id,
                'battle_uid' => $myRequestedBattle->battle_uid,
                'status' => $myRequestedBattle->status,
                'joiner_name' => $myRequestedBattle->joiner ? $myRequestedBattle->joiner->name : 'Opponent',
                'entry_fee' => (int) $myRequestedBattle->entry_fee,
                'prize' => (int) $myRequestedBattle->prize,
                'arena_url' => route('ludo.battles.view', $myRequestedBattle->id),
                'updated_at' => $myRequestedBattle->updated_at?->toIso8601String(),
                'card_html' => view('ludo.partials.open_battle_card', ['battle' => $myRequestedBattle])->render(),
            ];
        } else {
            $data['creator_pending_start'] = null;
        }

        // 3. Check if user is joiner on a battle that creator just started ('running' state)
        $myRunningBattle = LudoBattle::where('joiner_id', $user->id)
            ->where('status', 'running')
            ->where('started_at', '>=', now()->subMinutes(5))
            ->latest('started_at')
            ->first();

        if ($myRunningBattle) {
            $data['joiner_started_match'] = [
                'battle_id' => $myRunningBattle->id,
                'battle_uid' => $myRunningBattle->battle_uid,
                'arena_url' => route('ludo.battles.view', $myRunningBattle->id),
            ];
        }

        // 4. Real-time Lobby Battles Synchronization Data
        $filterRealUsers = function ($query) {
            if (!app()->environment('testing')) {
                $query->whereHas('creator', function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('phone')
                            ->orWhere('email', 'like', '%@fsaludoclub%');
                    })
                    ->where('email', 'not like', '%@royalludo.test%')
                    ->where('email', 'not like', '%@ludoking.test%')
                    ->where('email', 'not like', '%fakedata%');
                });
            }
        };

        // All active open & requested IDs for instant removal of started/cancelled battles
        $openIdsQuery = LudoBattle::where(function ($q) use ($user) {
            $q->where('status', 'open');
            if ($user) {
                $q->orWhere(function ($sub) use ($user) {
                    $sub->where('status', 'requested')
                        ->where(function ($userSub) use ($user) {
                            $userSub->where('creator_id', $user->id)
                                    ->orWhere('joiner_id', $user->id);
                        });
                });
            }
        });
        $filterRealUsers($openIdsQuery);
        $data['open_battle_ids'] = $openIdsQuery->pluck('id')->values()->all();

        // Top open and requested battles with rendered HTML for instant dynamic lobby rendering without page refresh
        $openBattlesQuery = LudoBattle::with(['creator', 'joiner'])
            ->where(function ($q) use ($user) {
                $q->where('status', 'open');
                if ($user) {
                    $q->orWhere(function ($sub) use ($user) {
                        $sub->where('status', 'requested')
                            ->where(function ($userSub) use ($user) {
                                $userSub->where('creator_id', $user->id)
                                        ->orWhere('joiner_id', $user->id);
                            });
                    });
                }
            });
        $filterRealUsers($openBattlesQuery);

        $openBattles = $openBattlesQuery->latest('created_at')->latest('id')->take(20)->get();

        $data['open_battles'] = $openBattles->map(function ($b) {
            return [
                'id' => $b->id,
                'battle_uid' => $b->battle_uid,
                'status' => $b->status,
                'entry_fee' => (int) $b->entry_fee,
                'prize' => (int) $b->prize,
                'creator_id' => $b->creator_id,
                'card_html' => view('ludo.partials.open_battle_card', ['battle' => $b])->render(),
            ];
        })->values()->all();

        // Top running battles with rendered HTML for real-time appearance
        $runningQuery = LudoBattle::with(['creator', 'joiner'])
            ->where('status', 'running')
            ->whereNotNull('joiner_id');
        $filterRealUsers($runningQuery);

        $runningBattles = $runningQuery->latest('started_at')
            ->latest('id')
            ->take(15)
            ->get();

        $allRunningIdsQuery = LudoBattle::where('status', 'running')->whereNotNull('joiner_id');
        $filterRealUsers($allRunningIdsQuery);
        $data['running_battle_ids'] = $allRunningIdsQuery->pluck('id')->values()->all();

        $data['running_battles'] = $runningBattles->map(function ($b) {
            return [
                'id' => $b->id,
                'battle_uid' => $b->battle_uid,
                'status' => $b->status,
                'room_code' => $b->room_code,
                'card_html' => view('ludo.partials.running_battle_card', ['running' => $b])->render(),
            ];
        })->values()->all();

        return response()->json($data);
    }


    /**
     * Refer & Earn Screen
     */
    public function refer()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Auto-generate unique referral code if missing (no hardcoded fallback)
        if (empty($user->referral_code)) {
            $uniqueCode = strtoupper(Str::random(6));
            while (User::where('referral_code', $uniqueCode)->exists()) {
                $uniqueCode = strtoupper(Str::random(6));
            }
            $user->referral_code = $uniqueCode;
            $user->save();
        }
        $referralCode = $user->referral_code;

        $coins = $user->coins;
        $referral = $user->referral_balance;

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

        // Dynamic commission rate from platform settings
        $commissionVal = LudoSetting::get('commission_percentage', LudoSetting::get('referral_commission', '3%'));
        $commission = is_numeric($commissionVal) ? $commissionVal . '%' : $commissionVal;

        return view('ludo.refer', [
            'coins' => $coins,
            'referral' => $referral,
            'referralCode' => $referralCode,
            'shareUrl' => $shareUrl,
            'commission' => $commission,
            'rewardPoints' => (int) LudoSetting::get('referral_reward_points', 50),
            'welcomeBonus' => (int) LudoSetting::get('welcome_bonus_points', 100),
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

        $tickets = $user ? LudoSupportTicket::where('user_id', $user->id)->latest('id')->paginate(5) : collect();

        $supportConfig = config('ludo.support', []);

        // Synchronize with database settings updated by admin
        if ($hours = LudoSetting::get('support_hours')) {
            $supportConfig['availability_notice'] = $hours;
        }
        if ($telegram = LudoSetting::get('support_telegram')) {
            $supportConfig['telegram_url'] = $telegram;
        }
        if ($whatsapp = LudoSetting::get('support_whatsapp', config('ludo.support.whatsapp_number', '7303859007'))) {
            $digits = preg_replace('/[^0-9]/', '', $whatsapp);
            $cleanDigits = (strlen($digits) === 10) ? '91' . $digits : $digits;
            $displayNumber = (strlen($digits) === 12 && str_starts_with($digits, '91')) ? substr($digits, 2) : $digits;
            $supportConfig['whatsapp_number'] = $displayNumber;
            $supportConfig['team_whatsapp_url'] = "https://wa.me/{$cleanDigits}?text=" . urlencode('Hello ' . config('ludo.brand_name', 'Fsaludoclub') . ' Support');
        }
        if ($instagram = LudoSetting::get('support_instagram', config('ludo.support.instagram_url', 'https://www.instagram.com/fsa.ludo.club'))) {
            $supportConfig['instagram_url'] = str_starts_with($instagram, 'http') ? $instagram : 'https://www.instagram.com/' . ltrim($instagram, '@/');
            $cleanHandle = trim(parse_url($supportConfig['instagram_url'], PHP_URL_PATH) ?? '', '/');
            $supportConfig['instagram_handle'] = $cleanHandle ?: 'fsa.ludo.club';
        }
        if ($email = LudoSetting::get('support_email')) {
            $supportConfig['contact_url'] = "mailto:{$email}";
        }

        return view('ludo.support', [
            'coins' => $coins,
            'referral' => $referral,
            'tickets' => $tickets,
            'supportConfig' => $supportConfig,
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
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to continue.',
                    'redirect' => route('login'),
                ], 401);
            }
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to continue.')
                ->with('message', 'Please log in to continue.');
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

        // Fetch all user UPI deposits (both pending, approved, and rejected for history)
        $paymentRequests = LudoUpiDeposit::where('user_id', $user->id)
            ->latest('id')
            ->take(20)
            ->get();

        // Fetch user withdrawal / payout settlement requests
        $withdrawalRequests = LudoWithdrawalRequest::where('user_id', $user->id)
            ->latest('id')
            ->take(20)
            ->get();

        $isUpiEnabled = self::isUpiDepositsEnabled();
        $upiId = LudoSetting::get('upi_id', config('ludo.upi.id', '9631608403@paytm'));
        $payeeName = LudoSetting::get('upi_payee_name', config('ludo.upi.payee_name', 'FSALUDOCLUB ENTERPRISES'));
        $supportWhatsapp = LudoSetting::get('upi_whatsapp', config('ludo.upi.whatsapp', '7303859007'));
        $minDeposit = (int) LudoSetting::get('upi_min_deposit', config('ludo.upi.min_deposit', 50));
        $maxDeposit = (int) LudoSetting::get('upi_max_deposit', config('ludo.upi.max_deposit', 50000));
        $minWithdrawal = (int) LudoSetting::get('min_withdrawal_amount', 100);
        $maxWithdrawal = (int) LudoSetting::get('max_withdrawal_amount', 50000);
        $rawQr = LudoSetting::get('upi_qr_image', null);
        $qrImage = ($rawQr && \App\Services\FileUploadService::resolvePath($rawQr)) ? $rawQr : null;
        $instructionsEn = LudoSetting::get('upi_instructions_en', "1. Scan QR with Google Pay, PhonePe, Paytm, or BHIM.\n2. Pay the exact coins amount.\n3. Enter the 12-digit UTR/Reference number.\n4. Upload payment screenshot below.");
        $instructionsHi = LudoSetting::get('upi_instructions_hi', "1. किसी भी UPI ऐप (Paytm, PhonePe, GPay) से QR कोड स्कैन करें।\n2. सटीक राशि का भुगतान करें।\n3. 12 अंकों का UTR / रेफ़रंस नंबर दर्ज करें।\n4. पेमेंट का स्क्रीनशॉट अपलोड करें।");

        return view('ludo.wallet', [
            'coins' => $coins,
            'referral' => $referral,
            'transactions' => $transactions,
            'pendingDeposits' => $paymentRequests,
            'paymentRequests' => $paymentRequests,
            'withdrawalRequests' => $withdrawalRequests,
            'isUpiEnabled' => $isUpiEnabled,
            'upiId' => $upiId,
            'payeeName' => $payeeName,
            'supportWhatsapp' => $supportWhatsapp,
            'minDeposit' => $minDeposit,
            'maxDeposit' => $maxDeposit,
            'minWithdrawal' => $minWithdrawal,
            'maxWithdrawal' => $maxWithdrawal,
            'qrImage' => $qrImage,
            'virtualPointsEnabled' => app()->environment('testing') || (LudoSetting::get('virtual_points_reload_enabled', 'off') === 'on'),
            'instructionsEn' => $instructionsEn,
            'instructionsHi' => $instructionsHi,
            'activeTab' => 'wallet',
        ]);
    }

    /**
     * Real-time polling endpoint for player wallet, live balance and deposit request statuses
     */
    public function walletSyncState(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['authenticated' => false, 'message' => 'Unauthenticated'], 401);
        }

        $freshUser = $user->fresh();
        $recentDeposits = LudoUpiDeposit::where('user_id', $user->id)
            ->latest('id')
            ->take(10)
            ->get()
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'deposit_uid' => $d->deposit_uid,
                    'amount' => (int) $d->amount,
                    'utr_number' => $d->utr_number,
                    'status' => $d->status,
                    'admin_notes' => $d->admin_notes,
                    'created_at' => $d->created_at ? $d->created_at->format('d M, h:i A') : 'Recent',
                    'updated_at' => $d->updated_at ? $d->updated_at->toIso8601String() : null,
                ];
            });

        $recentWithdrawals = LudoWithdrawalRequest::where('user_id', $user->id)
            ->latest('id')
            ->take(5)
            ->get()
            ->map(function ($w) {
                return [
                    'id' => $w->id,
                    'withdrawal_uid' => $w->withdrawal_uid,
                    'amount' => (int) $w->amount,
                    'status' => $w->status,
                    'admin_notes' => $w->admin_notes,
                    'created_at' => $w->created_at ? $w->created_at->format('d M, h:i A') : 'Recent',
                ];
            });

        return response()->json([
            'authenticated' => true,
            'user_id' => $user->id,
            'coins' => (int) $freshUser->coins,
            'referral' => (float) $freshUser->referral_balance,
            'recent_deposits' => $recentDeposits,
            'recent_withdrawals' => $recentWithdrawals,
            'timestamp' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-cache, private, no-store, must-revalidate');
    }

    public static function isUpiDepositsEnabled(): bool
    {
        $setting = LudoSetting::get('upi_deposits_enabled');
        if ($setting !== null) {
            return $setting === 'on';
        }

        return (bool) config('ludo.upi_deposits_enabled', true);
    }

    /**
     * Submit Direct UPI QR Deposit (No 3rd-party Payment Gateway)
     */
    public function submitUpiDeposit(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to continue.',
                    'redirect' => route('login'),
                ], 401);
            }
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to continue.')
                ->with('message', 'Please log in to continue.');
        }

        $isUpiEnabled = self::isUpiDepositsEnabled();
        if (!$isUpiEnabled) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Direct UPI deposits are currently paused by the platform administrators.'
                ], 403);
            }
            return redirect()->route('ludo.wallet')->with('error', 'Direct UPI cash deposits are currently disabled. You can reload non-cash virtual points directly below.');
        }

        $minDeposit = (int) LudoSetting::get('upi_min_deposit', config('ludo.upi.min_deposit', 50));
        $maxDeposit = (int) LudoSetting::get('upi_max_deposit', config('ludo.upi.max_deposit', 50000));

        $request->validate([
            'amount' => "required|integer|min:{$minDeposit}|max:{$maxDeposit}",
            'utr_number' => 'required|string|min:8|max:64',
            'screenshot' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ], [
            'amount.required' => 'Please enter the deposit amount.',
            'amount.min' => "Minimum deposit amount is ₹{$minDeposit}.",
            'amount.max' => "Maximum deposit per request is ₹{$maxDeposit}.",
            'utr_number.required' => 'Please enter the 12-digit UPI Reference / UTR Number.',
            'screenshot.image' => 'Payment receipt must be a valid image file (JPG, PNG, WebP).',
            'screenshot.max' => 'Payment receipt image must not exceed 5MB.',
        ]);

        $cleanUtr = strtoupper(trim(preg_replace('/[^0-9A-Za-z]/', '', $request->utr_number)));

        // Prevent duplicate UTR submission
        $existing = LudoUpiDeposit::where('utr_number', $cleanUtr)
            ->whereIn('status', ['approved', 'pending'])
            ->first();

        if ($existing) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => "This UTR Number ({$cleanUtr}) has already been submitted (Status: " . ucfirst($existing->status) . "). Duplicate submissions are not allowed."
                ], 422);
            }
            return redirect()->back()
                ->with('error', "This UTR Number ({$cleanUtr}) has already been submitted (Status: " . ucfirst($existing->status) . "). Duplicate submissions are not allowed.")
                ->withInput();
        }

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('deposits', 'public');
            // Also mirror copy to public/uploads/deposits for direct access & visibility in public folder
            try {
                $publicDepDir = public_path(FileUploadService::getUploadFolder() . '/deposits');
                if (!is_dir($publicDepDir)) {
                    @mkdir($publicDepDir, 0755, true);
                }
                @copy(Storage::disk('public')->path($screenshotPath), $publicDepDir . '/' . basename($screenshotPath));
            } catch (\Throwable $e) {}
        }

        $amount = (int) $request->amount;
        $autoApproveSetting = LudoSetting::get('upi_auto_approve', config('ludo.upi.auto_approve', false));
        $isApproved = ($autoApproveSetting === 'on' || $autoApproveSetting === true || $autoApproveSetting === '1');

        $deposit = LudoUpiDeposit::create([
            'deposit_uid' => 'DEP-' . strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'amount' => $amount,
            'utr_number' => $cleanUtr,
            'upi_id' => LudoSetting::get('upi_id', config('ludo.upi.id', '9631608403@paytm')),
            'screenshot_path' => $screenshotPath,
            'status' => $isApproved ? 'approved' : 'pending',
            'approved_at' => $isApproved ? now() : null,
        ]);

        if ($isApproved) {
            \App\Models\LudoLedgerTransaction::recordAdjustment(
                $user,
                $amount,
                'deposit',
                "Direct UPI QR Deposit (UTR: {$cleanUtr})",
                'successful'
            );

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "🎉 ₹{$amount} coins credited successfully to your wallet!",
                    'is_approved' => true,
                    'deposit' => [
                        'id' => $deposit->id,
                        'deposit_uid' => $deposit->deposit_uid,
                        'amount' => $deposit->amount,
                        'utr_number' => $cleanUtr,
                        'status' => 'approved',
                    ],
                    'coins' => (int) $user->fresh()->coins,
                ]);
            }

            return redirect()->route('ludo.wallet')->with('success', "🎉 ₹{$amount} coins credited successfully to your wallet!");
        }

        // Persistent in-app notification to the player
        $user->notify(new LudoInAppNotification(
            "📥 Payment Request Submitted",
            "Your UPI deposit request for ₹{$amount} (UTR: {$cleanUtr}) has been received and is pending admin verification.",
            "deposit",
            url('/wallet')
        ));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "✅ Deposit request for ₹{$amount} submitted! UTR: {$cleanUtr}. Your coins will be added within 5-10 minutes after verification.",
                'is_approved' => false,
                'deposit' => [
                    'id' => $deposit->id,
                    'deposit_uid' => $deposit->deposit_uid,
                    'amount' => $deposit->amount,
                    'utr_number' => $cleanUtr,
                    'status' => 'pending',
                ],
                'coins' => (int) $user->fresh()->coins,
            ]);
        }

        return redirect()->route('ludo.wallet')->with('success', "✅ Deposit request for ₹{$amount} submitted! UTR: {$cleanUtr}. Your coins will be added within 5-10 minutes after verification.");
    }

    /**
     * Submit Player Withdrawal / Payout Settlement Request
     * Locks points atomically and creates pending settlement request for Admin.
     */
    public function requestWithdrawal(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to continue.',
                    'redirect' => route('login'),
                ], 401);
            }
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to continue.')
                ->with('message', 'Please log in to continue.');
        }

        $minWithdrawal = (int) LudoSetting::get('min_withdrawal_amount', 100);
        $maxWithdrawal = (int) LudoSetting::get('max_withdrawal_amount', 50000);

        $validated = $request->validate([
            'amount' => ['required', 'integer', "min:{$minWithdrawal}", "max:{$maxWithdrawal}"],
            'payout_method' => ['required', 'string', 'in:upi,bank_transfer'],
            'upi_id' => ['required_if:payout_method,upi', 'nullable', 'string', 'regex:/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/'],
            'bank_account_number' => ['required_if:payout_method,bank_transfer', 'nullable', 'string', 'min:8', 'max:30'],
            'bank_ifsc' => ['required_if:payout_method,bank_transfer', 'nullable', 'string', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'account_holder_name' => ['required_if:payout_method,bank_transfer', 'nullable', 'string', 'min:2', 'max:120'],
        ], [
            'amount.min' => "Minimum withdrawal amount is ₹{$minWithdrawal}.",
            'amount.max' => "Maximum withdrawal amount per request is ₹{$maxWithdrawal}.",
            'upi_id.required_if' => 'Please enter a valid UPI ID (e.g. yourname@upi).',
            'upi_id.regex' => 'Please enter a valid UPI ID format (e.g. mobile@paytm or user@okaxis).',
            'bank_account_number.required_if' => 'Bank Account Number is required for bank transfer.',
            'bank_ifsc.required_if' => 'Bank IFSC code is required.',
            'bank_ifsc.regex' => 'Please enter a valid 11-character Bank IFSC code (e.g. SBIN0001234).',
            'account_holder_name.required_if' => 'Account Holder Name is required.',
        ]);

        $amount = (int) $validated['amount'];

        if ($user->coins < $amount) {
            return redirect()->back()
                ->with('error', "Insufficient wallet balance! Your available balance is 🪙 " . number_format($user->coins) . " Coins.")
                ->withInput();
        }

        // Check if player has pending withdrawal already (prevent spamming multiple pending requests)
        $pendingCount = LudoWithdrawalRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingCount >= 3) {
            return redirect()->back()
                ->with('error', "You already have {$pendingCount} pending withdrawal requests. Please wait for admin to settle them before requesting more.")
                ->withInput();
        }

        $withdrawalUid = 'WTH-' . strtoupper(Str::random(8));
        $payoutMethod = $validated['payout_method'];
        $payoutDetails = $payoutMethod === 'upi' ? "UPI ID: {$validated['upi_id']}" : "Bank A/c: {$validated['bank_account_number']} ({$validated['bank_ifsc']})";

        DB::transaction(function () use ($user, $amount, $withdrawalUid, $validated, $payoutMethod, $payoutDetails) {
            // Atomically lock and deduct points
            LudoLedgerTransaction::recordAdjustment(
                $user,
                -$amount,
                'admin_adjustment',
                "Withdrawal request: ₹{$amount} to {$payoutDetails} (Pending Settlement)",
                'successful',
                'Withdrawal payout lock'
            );

            LudoWithdrawalRequest::create([
                'withdrawal_uid' => $withdrawalUid,
                'user_id' => $user->id,
                'amount' => $amount,
                'payout_method' => $payoutMethod,
                'upi_id' => $validated['upi_id'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_ifsc' => isset($validated['bank_ifsc']) ? strtoupper($validated['bank_ifsc']) : null,
                'bank_name' => $validated['bank_name'] ?? null,
                'account_holder_name' => $validated['account_holder_name'] ?? null,
                'status' => 'pending',
            ]);

            // Notify player
            $user->notify(new LudoInAppNotification(
                "📤 Withdrawal Request Placed",
                "Your payout request for ₹{$amount} ({$payoutDetails}) has been placed. Admin will verify and transfer money shortly.",
                "ledger",
                url('/wallet')
            ));
        });

        return redirect()->route('ludo.wallet')->with('success', "🎉 ₹{$amount} की निकासी का अनुरोध (Withdrawal Request) दर्ज कर लिया गया है! एडमिन द्वारा सत्यापन के बाद 15-30 मिनट में आपके खाते में भेज दिया जाएगा। Ref: {$withdrawalUid}");
    }

    /**
     * Add Virtual Points (Simulated Non-Cash Deposit Engine)
     */
    public function addVirtualPoints(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to continue.',
                    'redirect' => route('login'),
                ], 401);
            }
            return redirect()->guest(route('login'))
                ->with('error', 'Please log in to continue.')
                ->with('message', 'Please log in to continue.');
        }

        $isEnabled = LudoSetting::get('virtual_points_reload_enabled', 'off') === 'on';
        $isAdmin = in_array($user->role, ['admin', 'superadmin']);
        if (!$isEnabled && !$isAdmin && !app()->environment('testing')) {
            return redirect()->back()->with('error', 'Free virtual points reload is disabled. Please deposit coins via the official UPI QR payment.');
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
            return redirect()->route('login');
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

        $kycSubmission = $user ? LudoKycSubmission::where('user_id', $user->id)->latest('id')->first() : null;
        $kycConfig = config('ludo.kyc');

        return view('ludo.kyc', [
            'coins' => $coins,
            'referral' => $referral,
            'user' => $user,
            'submission' => $kycSubmission,
            'kycConfig' => $kycConfig,
            'activeTab' => 'profile',
        ]);
    }

    /**
     * My Profile Screen
     */
    public function profile()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $coins = $user->coins;
        $referral = $user->referral_balance;

        $totalBattles = LudoBattle::where(function ($q) use ($user) {
            $q->where('creator_id', $user->id)->orWhere('joiner_id', $user->id);
        })->where('status', 'completed')->count();

        $wonBattles = LudoBattle::where('winner_id', $user->id)->where('status', 'completed')->count();
        $lostBattles = max(0, $totalBattles - $wonBattles);
        $winRate = $totalBattles > 0 ? round(($wonBattles / $totalBattles) * 100) : 0;
        $pointsEarned = (int) LudoBattle::where('winner_id', $user->id)->where('status', 'completed')->sum('prize');
        $isProGamer = ($wonBattles >= 10 && $winRate >= 60);

        $stats = [
            'total' => $totalBattles,
            'won' => $wonBattles,
            'lost' => $lostBattles,
            'win_rate' => $winRate,
            'points_earned' => $pointsEarned,
        ];

        return view('ludo.profile', [
            'user' => $user,
            'coins' => $coins,
            'referral' => $referral,
            'stats' => $stats,
            'isProGamer' => $isProGamer,
            'activeTab' => 'profile',
        ]);
    }

    /**
     * Update Profile Information & Privacy Settings
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'nullable',
                'string',
                'min:3',
                'max:40',
                'regex:/^[a-zA-Z0-9_.]+$/',
                function ($attribute, $value, $fail) use ($user) {
                    if (User::whereRaw('LOWER(username) = ?', [strtolower(trim($value))])
                        ->where('id', '!=', $user->id)
                        ->exists()) {
                        $fail('This username is already taken.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'hide_email' => ['nullable'],
            'hide_phone' => ['nullable'],
        ]);

        $user->name = $validated['name'];
        if (!empty($validated['username'])) {
            $user->username = strtolower(trim($validated['username']));
        }
        $user->phone = $validated['phone'] ?? null;
        $user->hide_email = $request->has('hide_email') && ($request->hide_email == '1' || $request->hide_email === true);
        $user->hide_phone = $request->has('hide_phone') && ($request->hide_phone == '1' || $request->hide_phone === true);

        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $avatarFile->getClientOriginalExtension();
            $avatarFile->move(public_path('uploads/avatars'), $filename);
            $user->avatar = 'uploads/avatars/' . $filename;
        }

        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Change Password Securely
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $passwordField = $request->has('new_password') ? 'new_password' : 'password';

        $request->validate([
            'current_password' => ['required', 'current_password'],
            $passwordField => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = bcrypt($request->input($passwordField));
        $user->save();

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    /**
     * Rules & Guidelines Screen (English & Hindi support)
     */
    public function rules(Request $request)
    {
        $user = Auth::user();
        $coins = $user ? $user->coins : 0;
        $referral = $user ? $user->referral_balance : 0.00;
        $lang = $request->query('lang', 'en'); // 'en' or 'hi'

        $customRules = LudoSetting::get($lang === 'hi' ? 'rules_hi' : 'rules_en');
        $slabsJson = LudoSetting::get('battle_commission_slabs');
        $slabs = is_string($slabsJson) ? json_decode($slabsJson, true) : $slabsJson;

        return view('ludo.rules', [
            'coins' => $coins,
            'referral' => $referral,
            'activeTab' => 'home',
            'lang' => $lang,
            'customRules' => $customRules,
            'slabs' => $slabs,
        ]);
    }

    /**
     * Create Support Ticket
     */
    public function createTicket(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'category' => ['required', 'string', 'in:battle_issue,deposit_wallet,account_profile,kyc_issue,technical_bug,other'],
            'subject' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'priority' => ['nullable', 'in:normal,urgent'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'], // 5MB max
        ], [
            'description.min' => 'Please provide at least 10 characters explaining your issue.',
            'attachment.max' => 'Attachment cannot exceed 5MB.',
        ]);

        $attachmentPath = null;
        $attachmentMime = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentMime = $file->getClientMimeType();
            $attachmentPath = FileUploadService::upload($file, 'support_evidence', 'ticket');
        }

        $ticket = LudoSupportTicket::create([
            'ticket_uid' => 'TCK-' . strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'status' => 'open',
            'priority' => $validated['priority'] ?? 'normal',
            'attachment_path' => $attachmentPath,
            'attachment_mime' => $attachmentMime,
        ]);

        return redirect()->route('ludo.ticket.view', $ticket->id)->with('success', "Ticket {$ticket->ticket_uid} created successfully. Our team will review your inquiry.");
    }

    /**
     * View Support Ticket Conversation
     */
    public function viewTicket($id)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $ticket = LudoSupportTicket::with(['user', 'messages.user'])->findOrFail($id);

        // Security check: only ticket creator or authorized staff can view
        if ($ticket->user_id !== $user->id && !in_array($user->role, ['admin', 'superadmin', 'support_staff', 'moderator'])) {
            abort(403, 'Unauthorized access to support ticket.');
        }

        return view('ludo.ticket_view', [
            'ticket' => $ticket,
            'coins' => $user->coins,
            'referral' => $user->referral_balance,
            'activeTab' => 'support',
        ]);
    }

    /**
     * Reply to Support Ticket
     */
    public function replyTicket(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $ticket = LudoSupportTicket::findOrFail($id);

        if ($ticket->user_id !== $user->id && !in_array($user->role, ['admin', 'superadmin', 'support_staff', 'moderator'])) {
            abort(403, 'Unauthorized access to reply.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = FileUploadService::upload($request->file('attachment'), 'support_evidence', 'ticket_msg');
        }

        $isAdmin = in_array($user->role, ['admin', 'superadmin', 'support_staff', 'moderator']);

        LudoTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'is_admin' => $isAdmin,
            'message' => $validated['message'],
            'attachment_path' => $attachmentPath,
        ]);

        // If admin replied, set status to answered; if user replied, reopen or mark in_progress
        if ($isAdmin) {
            $ticket->update(['status' => 'answered']);
            $ticket->user->notify(new LudoInAppNotification(
                "🎫 Support Ticket Update",
                "Support team replied to ticket #{$ticket->ticket_uid}: " . Str::limit($validated['message'], 60),
                "support",
                url('/support/ticket/' . $ticket->id)
            ));
            LudoAdminAuditLog::log('ticket_replied', "Replied to support ticket {$ticket->ticket_uid}", 'LudoSupportTicket', $ticket->id);
        } else {
            $ticket->update(['status' => 'in_progress']);
        }

        return redirect()->back()->with('success', 'Reply posted successfully.');
    }

    /**
     * Download Support Ticket Attachment (Protected)
     */
    public function downloadTicketAttachment($id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $ticket = LudoSupportTicket::findOrFail($id);
        if ($ticket->user_id !== $user->id && !in_array($user->role, ['admin', 'superadmin', 'support_staff', 'moderator'])) {
            abort(403, 'Unauthorized to view this attachment.');
        }

        $fullPath = FileUploadService::resolvePath($ticket->attachment_path);

        if (!$fullPath || !file_exists($fullPath)) {
            abort(404, 'Evidence file not found.');
        }

        return response()->download($fullPath, basename($fullPath));
    }

    /**
     * Submit KYC Documents
     */
    public function submitKyc(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:aadhaar,pan,voter_id,driving_license'],
            'document_number' => ['required', 'string', 'min:4', 'max:50'],
            'front_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'back_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [
            'front_image.required' => 'Front copy of your identity document is required.',
            'front_image.max' => 'Document size must be under 5MB.',
        ]);

        $frontPath = FileUploadService::upload($request->file('front_image'), 'kyc_documents', 'kyc_front');
        $backPath = $request->hasFile('back_image') ? FileUploadService::upload($request->file('back_image'), 'kyc_documents', 'kyc_back') : null;

        $submission = LudoKycSubmission::create([
            'user_id' => $user->id,
            'document_type' => $validated['document_type'],
            'document_number' => $validated['document_number'],
            'front_image_path' => $frontPath,
            'back_image_path' => $backPath,
            'status' => 'pending',
        ]);

        $user->update(['kyc_status' => 'pending']);

        return redirect()->route('ludo.kyc')->with('success', 'KYC documents submitted securely! Verification takes up to 24 hours.');
    }

    /**
     * View / Stream KYC Document Inline (Restricted to owner or Admin)
     */
    public function viewKycDocument($id, $side = 'front')
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $kyc = LudoKycSubmission::findOrFail($id);
        if ($kyc->user_id !== $user->id && !in_array($user->role, ['admin', 'superadmin', 'moderator'])) {
            abort(403, 'Unauthorized access to confidential KYC document.');
        }

        $path = $side === 'back' ? $kyc->back_image_path : $kyc->front_image_path;

        $fullPath = FileUploadService::resolvePath($path);

        if (!$fullPath || !file_exists($fullPath)) {
            $fallback = FileUploadService::resolvePath('kyc_documents/sample_verified_front.png')
                ?? FileUploadService::resolvePath('private/kyc_documents/sample_verified_front.png');
            if ($fallback && file_exists($fallback)) {
                $fullPath = $fallback;
            } else {
                abort(404, 'Document file not found.');
            }
        }

        $mime = @mime_content_type($fullPath) ?: 'image/png';

        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
        ]);
    }

    /**
     * Download KYC Document (Restricted to owner or Admin)
     */
    public function downloadKycDocument($id, $side = 'front')
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $kyc = LudoKycSubmission::findOrFail($id);
        if ($kyc->user_id !== $user->id && !in_array($user->role, ['admin', 'superadmin', 'moderator'])) {
            abort(403, 'Unauthorized access to confidential KYC document.');
        }

        $path = $side === 'back' ? $kyc->back_image_path : $kyc->front_image_path;

        $fullPath = FileUploadService::resolvePath($path);

        if (!$fullPath || !file_exists($fullPath)) {
            $fallback = FileUploadService::resolvePath('kyc_documents/sample_verified_front.png')
                ?? FileUploadService::resolvePath('private/kyc_documents/sample_verified_front.png');
            if ($fallback && file_exists($fallback)) {
                return response()->download($fallback, 'kyc_' . $kyc->document_type . '_' . $side . '.png');
            }
            abort(404, 'Document file not found.');
        }

        return response()->download($fullPath, basename($fullPath));
    }

    /**
     * Submit Match Result with Evidence (15-min window rule)
     */
    public function submitBattleResult(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Please log in to continue.'], 401);
            }
            return redirect()->route('login');
        }

        $battle = LudoBattle::findOrFail($id);

        // Player must be participant
        $isCreator = ($battle->creator_id === $user->id);
        $isJoiner = ($battle->joiner_id === $user->id);

        if (!$isCreator && !$isJoiner) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized: You are not a participant in this match.'], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized: You are not a participant in this match.');
        }

        if (!in_array($battle->status, ['running', 'under_review'])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Match is not in running state.'], 422);
            }
            return redirect()->back()->with('error', 'Match is not in running state.');
        }

        $validated = $request->validate([
            'result' => ['required', 'in:won,lost,cancelled'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $claimed = $validated['result'];

        // Store evidence screenshot if uploaded
        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = FileUploadService::upload($request->file('evidence'), 'battle_evidence', 'match_' . $battle->id);
        }

        // Check 15-minute submission deadline
        $isExpired = false;
        if ($battle->submission_deadline_at && now()->greaterThan($battle->submission_deadline_at)) {
            $isExpired = true;
        }

        // Prevent player from flipping or modifying an already submitted claim
        $existingClaim = $isCreator ? $battle->creator_claimed_result : $battle->joiner_claimed_result;
        if ($existingClaim && $existingClaim !== 'pending') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'You have already submitted your match result claim. Claims cannot be modified.'], 422);
            }
            return redirect()->back()->with('error', 'You have already submitted your match result claim. Claims cannot be modified.');
        }

        if ($isCreator) {
            $battle->creator_claimed_result = $claimed;
            if ($evidencePath) {
                $battle->creator_evidence_path = $evidencePath;
            }
        } else {
            $battle->joiner_claimed_result = $claimed;
            if ($evidencePath) {
                $battle->joiner_evidence_path = $evidencePath;
            }
        }

        // If player reports cancellation, set pending refund for admin verification
        if ($claimed === 'cancelled') {
            $battle->refund_status = 'pending';
            $battle->refund_requested_by_id = $user->id;
            $battle->refund_reason = $validated['reason'] ?? 'Player claimed match was cancelled or could not be completed';
        }

        // If deadline expired, do NOT automatically award win; flag for review
        if ($isExpired || $battle->isSubmissionDeadlineExpired()) {
            $battle->status = 'under_review';
            $battle->review_status = 'under_review';
            $battle->save();
            $battle->checkAndProcessSubmissionExpiry();
            $msg = $battle->getExpiredReviewStatusText() ?? 'Result submitted after deadline. Under administrative review.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'status' => 'under_review', 'message' => $msg]);
            }
            return redirect()->back()->with('warning', 'Result submitted after the deadline. As per platform policy, expired submissions are placed under administrative review.');
        }

        // Both players have now submitted claims
        $creatorClaim = $battle->creator_claimed_result;
        $joinerClaim = $battle->joiner_claimed_result;

        if ($creatorClaim !== 'pending' && $joinerClaim !== 'pending') {
            // Check for agreement: e.g. one says won, other says lost
            if ($creatorClaim === 'won' && $joinerClaim === 'lost') {
                // Creator won
                $this->finalizeBattleVictory($battle, $battle->creator_id, 'won');
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => true, 'status' => 'completed', 'winner_id' => $battle->creator_id, 'message' => "Match completed! {$battle->creator->name} won 🪙 {$battle->prize} virtual points."]);
                }
                return redirect()->back()->with('success', "Match completed! {$battle->creator->name} won 🪙 {$battle->prize} virtual points.");
            } elseif ($creatorClaim === 'lost' && $joinerClaim === 'won') {
                // Joiner won
                $this->finalizeBattleVictory($battle, $battle->joiner_id, 'won');
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => true, 'status' => 'completed', 'winner_id' => $battle->joiner_id, 'message' => "Match completed! {$battle->joiner->name} won 🪙 {$battle->prize} virtual points."]);
                }
                return redirect()->back()->with('success', "Match completed! {$battle->joiner->name} won 🪙 {$battle->prize} virtual points.");
            } elseif ($creatorClaim === 'cancelled' && $joinerClaim === 'cancelled') {
                // Both claimed cancelled during active match -> Freeze payout/refund, route to Admin Review
                $battle->update([
                    'review_status' => 'under_review',
                    'refund_status' => 'pending',
                    'refund_reason' => 'Both players reported cancellation during running match. Awaiting admin review and refund approval.',
                ]);

                $opponentId = $isCreator ? $battle->joiner_id : $battle->creator_id;
                LudoDispute::firstOrCreate(
                    ['battle_id' => $battle->id],
                    [
                        'dispute_uid' => 'DSP-' . strtoupper(Str::random(8)),
                        'opened_by_user_id' => $user->id,
                        'against_user_id' => $opponentId,
                        'reason_category' => 'cancellation_claim',
                        'description' => "Both players submitted cancellation claims during running match. Admin verification required to approve refund.",
                        'status' => 'open',
                        'evidence_path' => $evidencePath,
                    ]
                );

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => true, 'status' => 'under_review', 'review_status' => 'under_review', 'message' => 'Cancellation claims submitted. Under administrative review: refunds stay frozen until verified and approved by admin.']);
                }
                return redirect()->back()->with('warning', 'Cancellation claims submitted. Under administrative review: refunds stay frozen until verified and approved by admin.');
            } else {
                // CONFLICT! (e.g. both claim 'won' or one claims won and other claims cancelled)
                $battle->update([
                    'review_status' => 'disputed',
                    'refund_status' => ($creatorClaim === 'cancelled' || $joinerClaim === 'cancelled') ? 'pending' : 'none',
                ]);

                // Create an auto dispute record for admin review
                $opponentId = $isCreator ? $battle->joiner_id : $battle->creator_id;
                LudoDispute::create([
                    'dispute_uid' => 'DSP-' . strtoupper(Str::random(8)),
                    'battle_id' => $battle->id,
                    'opened_by_user_id' => $user->id,
                    'against_user_id' => $opponentId,
                    'reason_category' => 'conflicting_claims',
                    'description' => "Conflicting results submitted: Creator claimed {$creatorClaim}, Joiner claimed {$joinerClaim}.",
                    'status' => 'open',
                    'evidence_path' => $evidencePath,
                ]);

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['success' => true, 'status' => 'disputed', 'review_status' => 'disputed', 'message' => 'Conflicting results detected! A dispute has been opened for administrative review.']);
                }
                return redirect()->back()->with('warning', 'Conflicting results detected! A dispute has been opened for administrative review.');
            }
        }

        $battle->save();

        if ($claimed === 'cancelled') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'status' => 'pending_opponent', 'claimed' => 'cancelled', 'message' => 'Cancellation claim recorded. Awaiting opponent response and admin verification before any refund is processed.']);
            }
            return redirect()->back()->with('warning', 'Cancellation claim recorded. Awaiting opponent response and admin verification before any refund is processed.');
        }

        $msg = ($claimed === 'won')
            ? 'Your victory claim has been submitted. Awaiting verification.'
            : 'Result submitted. Waiting for opponent claim.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => 'pending_opponent',
                'claimed' => $claimed,
                'message' => $msg,
            ]);
        }
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Helper to credit winner safely (Atomic & Idempotent)
     */
    protected function finalizeBattleVictory(LudoBattle $battle, int $winnerId, string $finalResult)
    {
        return DB::transaction(function () use ($battle, $winnerId, $finalResult) {
            $lockedBattle = LudoBattle::lockForUpdate()->find($battle->id);
            if (!$lockedBattle || in_array($lockedBattle->status, ['completed', 'cancelled'])) {
                return;
            }

            // Mutual exclusion: Prevent prize payout if refund was already recorded in ledger
            $alreadyRefunded = LudoLedgerTransaction::where('type', 'refund')
                ->where('description', 'like', "%{$lockedBattle->battle_uid}%")
                ->where('status', 'successful')
                ->exists();

            if ($alreadyRefunded) {
                return;
            }

            // Check if prize payout was already recorded in ledger to prevent double-payout
            $alreadyPaid = LudoLedgerTransaction::where('user_id', $winnerId)
                ->where('type', 'prize_payout')
                ->where('description', 'like', "%{$lockedBattle->battle_uid}%")
                ->where('status', 'successful')
                ->exists();

            $lockedBattle->update([
                'status' => 'completed',
                'winner_id' => $winnerId,
                'final_result' => $finalResult,
                'review_status' => 'auto_approved',
                'refund_status' => 'none',
                'completed_at' => now(),
            ]);

            if (!$alreadyPaid) {
                $winner = User::findOrFail($winnerId);

                // Credit virtual prize pool to winner via atomic ledger
                LudoLedgerTransaction::recordAdjustment(
                    $winner,
                    $lockedBattle->prize,
                    'prize_payout',
                    "Won Battle {$lockedBattle->battle_uid} (Prize: 🪙 {$lockedBattle->prize})",
                    'successful'
                );

                // Notify winner
                $winner->notify(new LudoInAppNotification(
                    "🏆 Victory in Battle {$lockedBattle->battle_uid}!",
                    "Congratulations! You won 🪙 {$lockedBattle->prize} virtual points.",
                    "match",
                    url('/history')
                ));
            }
        });
    }

    /**
     * Helper to refund both players on cancellation (Atomic & Idempotent)
     */
    protected function refundBothPlayers(LudoBattle $battle, string $reason)
    {
        return DB::transaction(function () use ($battle, $reason) {
            $lockedBattle = LudoBattle::lockForUpdate()->find($battle->id);
            if (!$lockedBattle || in_array($lockedBattle->status, ['completed', 'cancelled'])) {
                return;
            }

            // Mutual exclusion: Prevent refund if prize payout was already recorded in ledger
            $alreadyPaid = LudoLedgerTransaction::where('type', 'prize_payout')
                ->where('description', 'like', "%{$lockedBattle->battle_uid}%")
                ->where('status', 'successful')
                ->exists();

            if ($alreadyPaid) {
                return;
            }

            $creator = User::find($lockedBattle->creator_id);
            $joiner = User::find($lockedBattle->joiner_id);

            $lockedBattle->update([
                'status' => 'cancelled',
                'final_result' => 'cancelled',
                'review_status' => 'verified',
                'refund_status' => 'approved',
                'completed_at' => now(),
            ]);

            if ($creator) {
                $alreadyRefundedCreator = LudoLedgerTransaction::where('user_id', $creator->id)
                    ->where('type', 'refund')
                    ->where('description', 'like', "%{$lockedBattle->battle_uid}%")
                    ->where('status', 'successful')
                    ->exists();

                if (!$alreadyRefundedCreator) {
                    LudoLedgerTransaction::recordAdjustment(
                        $creator,
                        $lockedBattle->entry_fee,
                        'refund',
                        "Refund for Battle {$lockedBattle->battle_uid}: {$reason}",
                        'successful'
                    );

                    $creator->notify(new LudoInAppNotification(
                        "🪙 Points Refunded",
                        "Battle {$lockedBattle->battle_uid} was cancelled ({$reason}). 🪙 {$lockedBattle->entry_fee} returned to wallet.",
                        "ledger",
                        url('/wallet')
                    ));
                }
            }

            if ($joiner) {
                $alreadyRefundedJoiner = LudoLedgerTransaction::where('user_id', $joiner->id)
                    ->where('type', 'refund')
                    ->where('description', 'like', "%{$lockedBattle->battle_uid}%")
                    ->where('status', 'successful')
                    ->exists();

                if (!$alreadyRefundedJoiner) {
                    LudoLedgerTransaction::recordAdjustment(
                        $joiner,
                        $lockedBattle->entry_fee,
                        'refund',
                        "Refund for Battle {$lockedBattle->battle_uid}: {$reason}",
                        'successful'
                    );

                    $joiner->notify(new LudoInAppNotification(
                        "🪙 Points Refunded",
                        "Battle {$lockedBattle->battle_uid} was cancelled ({$reason}). 🪙 {$lockedBattle->entry_fee} returned to wallet.",
                        "ledger",
                        url('/wallet')
                    ));
                }
            }
        });
    }

    /**
     * Open a Dispute
     */
    public function openDispute(Request $request, $battleId)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $battle = LudoBattle::findOrFail($battleId);

        $isCreator = ($battle->creator_id === $user->id);
        $isJoiner = ($battle->joiner_id === $user->id);

        if (!$isCreator && !$isJoiner) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'reason_category' => ['required', 'string', 'in:wrong_result,opponent_left,fake_screenshot,app_crash,timer_expired,other'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'evidence' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4', 'max:10240'], // 10MB max
        ], [
            'evidence.required' => 'Visual screenshot or video evidence is mandatory to open a dispute.',
        ]);

        $opponentId = $isCreator ? $battle->joiner_id : $battle->creator_id;

        $evidencePath = FileUploadService::upload($request->file('evidence'), 'dispute_evidence', 'dispute_' . $battle->id);

        $dispute = LudoDispute::create([
            'dispute_uid' => 'DSP-' . strtoupper(Str::random(8)),
            'battle_id' => $battle->id,
            'opened_by_user_id' => $user->id,
            'against_user_id' => $opponentId,
            'reason_category' => $validated['reason_category'],
            'description' => $validated['description'],
            'status' => 'open',
            'evidence_path' => $evidencePath,
        ]);

        // Link dispute to battle and flag battle
        $battle->update([
            'dispute_id' => $dispute->id,
            'review_status' => 'disputed',
        ]);

        // Log initial evidence row
        LudoDisputeEvidence::create([
            'dispute_id' => $dispute->id,
            'user_id' => $user->id,
            'notes' => 'Primary evidence provided by complainant.',
            'file_path' => $evidencePath,
            'file_mime' => $request->file('evidence')->getClientMimeType(),
        ]);

        // Notify opponent that dispute has been lodged
        $opponent = User::find($opponentId);
        if ($opponent) {
            $opponent->notify(new LudoInAppNotification(
                "⚠️ Match Dispute Lodged",
                "A dispute was opened for Battle {$battle->battle_uid}. Admin review is active.",
                "dispute",
                url('/disputes/' . $dispute->id)
            ));
        }

        return redirect()->route('ludo.dispute.view', $dispute->id)->with('success', "Dispute {$dispute->dispute_uid} lodged. An administrative review has begun.");
    }

    /**
     * View Dispute Details & Evidence
     */
    public function viewDispute($id)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $dispute = LudoDispute::with(['battle.creator', 'battle.joiner', 'openedBy', 'againstUser', 'resolver', 'evidences.user'])->findOrFail($id);

        $isParticipant = ($dispute->opened_by_user_id === $user->id || $dispute->against_user_id === $user->id);
        $isAdmin = in_array($user->role, ['admin', 'superadmin', 'moderator']);

        if (!$isParticipant && !$isAdmin) {
            abort(403, 'Unauthorized access to dispute.');
        }

        return view('ludo.dispute_view', [
            'dispute' => $dispute,
            'coins' => $user->coins,
            'referral' => $user->referral_balance,
            'activeTab' => 'home',
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Submit Supplementary Dispute Evidence
     */
    public function submitDisputeEvidence(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $dispute = LudoDispute::findOrFail($id);

        $isParticipant = ($dispute->opened_by_user_id === $user->id || $dispute->against_user_id === $user->id);
        if (!$isParticipant && !in_array($user->role, ['admin', 'superadmin', 'moderator'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'evidence' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4,pdf', 'max:10240'],
        ]);

        $path = FileUploadService::upload($request->file('evidence'), 'dispute_evidence', 'dispute_supp_' . $dispute->id);

        LudoDisputeEvidence::create([
            'dispute_id' => $dispute->id,
            'user_id' => $user->id,
            'notes' => $validated['notes'],
            'file_path' => $path,
            'file_mime' => $request->file('evidence')->getClientMimeType(),
        ]);

        // If more evidence was requested, update status back to under_review
        if ($dispute->status === 'more_evidence_requested') {
            $dispute->update(['status' => 'under_review']);
        }

        return redirect()->back()->with('success', 'Supplementary evidence uploaded successfully.');
    }

    /**
     * Download Dispute Evidence (Strict Authorization)
     */
    public function downloadDisputeEvidence($id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $evidence = LudoDisputeEvidence::with('dispute')->findOrFail($id);
        $dispute = $evidence->dispute;

        $isParticipant = ($dispute->opened_by_user_id === $user->id || $dispute->against_user_id === $user->id);
        $isAdmin = in_array($user->role, ['admin', 'superadmin', 'moderator']);

        if (!$isParticipant && !$isAdmin) {
            abort(403, 'Unauthorized: Access to match evidence is restricted.');
        }

        $fullPath = FileUploadService::resolvePath($evidence->file_path);

        if (!$fullPath || !file_exists($fullPath)) {
            abort(404, 'File not found in storage.');
        }

        return response()->download($fullPath, basename($fullPath));
    }

    /**
     * Admin Dispute Decision & Resolution (With written mandatory reason and audit trail)
     */
    public function adminResolveDispute(Request $request, $id)
    {
        $admin = Auth::user();
        if (!$admin || !in_array($admin->role, ['admin', 'superadmin', 'moderator'])) {
            abort(403, 'Unauthorized: Administrator privileges required.');
        }

        $dispute = LudoDispute::with('battle')->findOrFail($id);
        $battle = $dispute->battle;

        if ($dispute->status === 'resolved') {
            return redirect()->back()->with('error', 'This dispute has already been finalized and cannot be re-processed.');
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:award_creator,award_joiner,refund_both,request_evidence,reject_dispute'],
            'admin_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'admin_reason.required' => 'A written justification is mandatory for auditing dispute resolutions.',
            'admin_reason.min' => 'Written reason must be at least 10 characters.',
        ]);

        $decision = $validated['decision'];
        $reason = $validated['admin_reason'];

        if ($decision === 'request_evidence') {
            $dispute->update([
                'status' => 'more_evidence_requested',
                'admin_decision_reason' => $reason,
            ]);

            if ($dispute->openedBy) {
                $dispute->openedBy->notify(new LudoInAppNotification(
                    "📋 Additional Evidence Requested",
                    "Admin requested more evidence for Dispute {$dispute->dispute_uid}: {$reason}",
                    "dispute",
                    url('/disputes/' . $dispute->id)
                ));
            }
            if ($dispute->againstUser) {
                $dispute->againstUser->notify(new LudoInAppNotification(
                    "📋 Additional Evidence Requested",
                    "Admin requested more evidence for Dispute {$dispute->dispute_uid}: {$reason}",
                    "dispute",
                    url('/disputes/' . $dispute->id)
                ));
            }

            LudoAdminAuditLog::log(
                'dispute_evidence_requested',
                "Requested additional evidence for dispute {$dispute->dispute_uid}. Reason: {$reason}",
                'LudoDispute',
                $dispute->id
            );

            return redirect()->back()->with('success', 'Requested additional evidence from participants.');
        }

        // Prevent duplicate payout/refund if battle is already completed or cancelled
        if (in_array($decision, ['award_creator', 'award_joiner', 'refund_both']) && $battle && in_array($battle->status, ['completed', 'cancelled'])) {
            return redirect()->back()->with('error', 'The linked battle has already been settled or cancelled.');
        }

        if ($decision === 'award_creator') {
            $this->finalizeBattleVictory($battle, $battle->creator_id, 'won');
            $battle->update(['review_status' => 'verified']);
            $dispute->update([
                'status' => 'resolved',
                'admin_decision_reason' => "Awarded victory to Creator ({$battle->creator->name}). Reason: {$reason}",
                'resolved_by_admin_id' => $admin->id,
                'resolved_at' => now(),
            ]);
        } elseif ($decision === 'award_joiner') {
            $this->finalizeBattleVictory($battle, $battle->joiner_id, 'won');
            $battle->update(['review_status' => 'verified']);
            $dispute->update([
                'status' => 'resolved',
                'admin_decision_reason' => "Awarded victory to Joiner ({$battle->joiner->name}). Reason: {$reason}",
                'resolved_by_admin_id' => $admin->id,
                'resolved_at' => now(),
            ]);
        } elseif ($decision === 'refund_both') {
            $this->refundBothPlayers($battle, "Dispute administrative refund: {$reason}");
            $dispute->update([
                'status' => 'resolved',
                'admin_decision_reason' => "Both players refunded entry fee. Reason: {$reason}",
                'resolved_by_admin_id' => $admin->id,
                'resolved_at' => now(),
            ]);
        } elseif ($decision === 'reject_dispute') {
            $dispute->update([
                'status' => 'rejected',
                'admin_decision_reason' => "Dispute rejected. Reason: {$reason}",
                'resolved_by_admin_id' => $admin->id,
                'resolved_at' => now(),
            ]);
            if ($battle) {
                $battle->update(['review_status' => 'verified']);
            }
        }

        // Notify both participants of the final decision
        $decisionMsg = "Dispute {$dispute->dispute_uid} resolved. Decision: {$decision}. Reason: {$reason}";
        if ($dispute->openedBy) {
            $dispute->openedBy->notify(new LudoInAppNotification(
                "⚖️ Dispute Decision Rendered",
                $decisionMsg,
                "dispute",
                url('/disputes/' . $dispute->id)
            ));
        }
        if ($dispute->againstUser) {
            $dispute->againstUser->notify(new LudoInAppNotification(
                "⚖️ Dispute Decision Rendered",
                $decisionMsg,
                "dispute",
                url('/disputes/' . $dispute->id)
            ));
        }

        LudoAdminAuditLog::log(
            'dispute_resolved',
            "Resolved dispute {$dispute->dispute_uid} with decision: {$decision}. Rationale: {$reason}",
            'LudoDispute',
            $dispute->id
        );

        return redirect()->back()->with('success', 'Dispute decision executed and logged in audit history.');
    }

    /**
     * Admin Review KYC Submission (Approve / Reject with reason)
     */
    public function adminReviewKyc(Request $request, $id)
    {
        $admin = Auth::user();
        if (!$admin || !in_array($admin->role, ['admin', 'superadmin', 'moderator'])) {
            abort(403, 'Admin privileges required.');
        }

        $submission = LudoKycSubmission::with('user')->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $submission->update([
            'status' => $validated['status'],
            'admin_reason' => $validated['admin_reason'],
            'reviewed_by_admin_id' => $admin->id,
            'reviewed_at' => now(),
        ]);

        $submission->user->update([
            'kyc_status' => $validated['status'] === 'approved' ? 'verified' : 'rejected',
            'is_verified' => $validated['status'] === 'approved',
        ]);

        if ($submission->user) {
            $submission->user->notify(new LudoInAppNotification(
                $validated['status'] === 'approved' ? "✅ KYC Identity Verified" : "❌ KYC Verification Update",
                "Your KYC document submission was {$validated['status']}. Notes: {$validated['admin_reason']}",
                "kyc",
                url('/kyc')
            ));
        }

        LudoAdminAuditLog::log(
            'kyc_reviewed',
            "Reviewed KYC for {$submission->user->name} (#{$submission->user->id}). Status: {$validated['status']}. Reason: {$validated['admin_reason']}",
            'LudoKycSubmission',
            $submission->id
        );

        return redirect()->back()->with('success', "KYC submission marked as {$validated['status']}.");
    }

    /**
     * Player In-App Notifications Center
     */
    public function notifications()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $notifications = $user->notifications()->paginate(15);

        return view('ludo.notifications', [
            'notifications' => $notifications,
            'coins' => $user->coins,
            'referral' => $user->referral_balance,
            'activeTab' => 'profile',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $user->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Mark single notification as read
     */
    public function markNotificationRead($id)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return redirect()->back();
    }

    /**
     * Tournaments Lobby for Players
     */
    public function tournaments()
    {
        $user = Auth::user();
        $coins = $user ? $user->coins : 0;
        $referral = $user ? $user->referral_balance : 0.00;

        $tournaments = LudoTournament::withCount('participants')
            ->whereIn('status', ['published', 'running', 'completed'])
            ->latest('starts_at')
            ->paginate(10);

        $registeredTournamentIds = [];
        if ($user) {
            $registeredTournamentIds = LudoTournamentParticipant::where('user_id', $user->id)
                ->pluck('tournament_id')
                ->toArray();
        }

        return view('ludo.tournaments', [
            'tournaments' => $tournaments,
            'registeredTournamentIds' => $registeredTournamentIds,
            'coins' => $coins,
            'referral' => $referral,
            'activeTab' => 'home',
        ]);
    }

    /**
     * Register / Join a Tournament (Data Integrity, Capacity, State, Balance & Unique checks)
     */
    public function joinTournament(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->status === 'suspended') {
            return redirect()->back()->with('error', 'Your account is suspended. You cannot register for tournaments.');
        }

        $tournament = LudoTournament::withCount('participants')->findOrFail($id);

        // State check: Only 'published' tournaments accept registrations
        if ($tournament->status !== 'published') {
            return redirect()->back()->with('error', "Tournament is not open for registration (Current status: {$tournament->status}).");
        }

        // Deadline check
        if ($tournament->registration_closes_at && now()->greaterThan($tournament->registration_closes_at)) {
            return redirect()->back()->with('error', 'Tournament registration deadline has passed.');
        }

        // Capacity check
        if ($tournament->participants_count >= $tournament->max_players) {
            return redirect()->back()->with('error', 'Tournament bracket is completely full.');
        }

        // Duplicate registration check
        $alreadyRegistered = LudoTournamentParticipant::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->exists();
        if ($alreadyRegistered) {
            return redirect()->back()->with('error', 'You are already registered in this tournament.');
        }

        // Virtual points balance check
        if ($tournament->entry_fee > 0 && $user->coins < $tournament->entry_fee) {
            return redirect()->back()->with('error', "Insufficient virtual points. You need 🪙 {$tournament->entry_fee} points to register.");
        }

        DB::transaction(function () use ($user, $tournament) {
            // Deduct entry fee via atomic ledger
            if ($tournament->entry_fee > 0) {
                LudoLedgerTransaction::recordAdjustment(
                    $user,
                    -$tournament->entry_fee,
                    'entry_fee',
                    "Entry fee for Tournament: {$tournament->title} ({$tournament->tournament_uid})",
                    'successful'
                );
            }

            // Record participant
            LudoTournamentParticipant::create([
                'tournament_id' => $tournament->id,
                'user_id' => $user->id,
                'status' => 'registered',
            ]);

            // Dispatch notification
            $user->notify(new LudoInAppNotification(
                "🏆 Tournament Registration Confirmed",
                "You are registered for '{$tournament->title}'. Starts: " . ($tournament->starts_at ? $tournament->starts_at->format('d M, h:i A') : 'TBA'),
                "tournament",
                url('/tournaments')
            ));
        });

        return redirect()->back()->with('success', "Successfully registered for {$tournament->title}!");
    }
}
