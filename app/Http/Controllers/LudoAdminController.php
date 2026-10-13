<?php

namespace App\Http\Controllers;

use App\Models\LudoAdminAuditLog;
use App\Models\LudoBattle;
use App\Models\LudoDispute;
use App\Models\LudoDisputeEvidence;
use App\Models\LudoKycSubmission;
use App\Models\LudoLedgerTransaction;
use App\Models\LudoReferral;
use App\Models\LudoSetting;
use App\Models\LudoSupportTicket;
use App\Models\LudoTournament;
use App\Models\LudoTournamentParticipant;
use App\Models\User;
use App\Notifications\LudoInAppNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LudoAdminController extends Controller
{
    /**
     * Ensure current user is a senior administrator (admin or superadmin) for sensitive governance actions
     */
    protected function requireSeniorAdmin(): void
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['admin', 'superadmin'])) {
            abort(403, 'Unauthorized: Senior Administrator privileges are required for this operation.');
        }
    }

    /**
     * Resolve optional date range filter from request
     */
    protected function resolveDateRange(Request $request): array
    {
        $period = $request->input('period', 'all');
        $from = null;
        $to = null;

        if ($period === 'today') {
            $from = Carbon::today()->startOfDay();
            $to = Carbon::today()->endOfDay();
        } elseif ($period === '7d') {
            $from = Carbon::now()->subDays(7)->startOfDay();
            $to = Carbon::now()->endOfDay();
        } elseif ($period === '30d') {
            $from = Carbon::now()->subDays(30)->startOfDay();
            $to = Carbon::now()->endOfDay();
        } elseif ($request->filled('from') || $request->filled('to')) {
            $period = 'custom';
            if ($request->filled('from')) {
                $from = Carbon::parse($request->input('from'))->startOfDay();
            }
            if ($request->filled('to')) {
                $to = Carbon::parse($request->input('to'))->endOfDay();
            }
        }

        return [$period, $from, $to];
    }

    /**
     * PHASE 2 — Admin Overview Dashboard with Real Database Metrics & Date Filters
     */
    public function dashboard(Request $request)
    {
        [$period, $from, $to] = $this->resolveDateRange($request);

        $applyDate = function ($query, $column = 'created_at') use ($from, $to) {
            if ($from) {
                $query->where($column, '>=', $from);
            }
            if ($to) {
                $query->where($column, '<=', $to);
            }
            return $query;
        };

        // User Metrics
        $registeredUsersCount = $applyDate(User::query())->count();
        $activeUsersCount = $applyDate(User::where('status', 'active'))->count();
        $suspendedUsersCount = $applyDate(User::where('status', 'suspended'))->count();
        $verifiedUsersCount = $applyDate(User::where('kyc_status', 'verified'))->count();
        $unverifiedUsersCount = max(0, $registeredUsersCount - $verifiedUsersCount);

        // Tournament Metrics
        $tournamentsCount = $applyDate(LudoTournament::query())->count();
        $upcomingTournamentsCount = $applyDate(LudoTournament::where('status', 'published'))->count();
        $runningTournamentsCount = $applyDate(LudoTournament::where('status', 'running'))->count();
        $completedTournamentsCount = $applyDate(LudoTournament::where('status', 'completed'))->count();
        $cancelledTournamentsCount = $applyDate(LudoTournament::where('status', 'cancelled'))->count();

        // Battle / Match Metrics
        $openBattlesCount = $applyDate(LudoBattle::where('status', 'open'))->count();
        $activeMatchesCount = $applyDate(LudoBattle::where('status', 'running'))->count();
        $completedMatchesCount = $applyDate(LudoBattle::where('status', 'completed'))->count();
        $cancelledMatchesCount = $applyDate(LudoBattle::where('status', 'cancelled'))->count();
        $pendingResultsCount = $applyDate(LudoBattle::where('review_status', 'under_review'))->count();

        // Dispute, Support & KYC Queues
        $openDisputesCount = $applyDate(LudoDispute::whereIn('status', ['open', 'under_review', 'more_evidence_requested']))->count();
        $supportTicketsCount = $applyDate(LudoSupportTicket::whereIn('status', ['open', 'in_progress']))->count();
        $pendingKycCount = $applyDate(LudoKycSubmission::where('status', 'pending'))->count();

        // Referral Metrics
        $totalReferralsCount = $applyDate(LudoReferral::query())->count();
        $eligibleReferralsCount = $applyDate(LudoReferral::where('status', 'eligible'))->count();
        $referralPointsAwarded = (int) $applyDate(LudoReferral::where('status', 'eligible'))->sum('reward_points');

        // Virtual-Points Balances & Ledger Totals
        $circulatingPoints = (int) User::sum('coins');
        $virtualPointsIssued = (int) $applyDate(LudoLedgerTransaction::where('type', 'deposit')->where('status', 'successful'))->sum('points_delta');
        $virtualPrizesPaid = (int) $applyDate(LudoLedgerTransaction::where('type', 'prize_payout')->where('status', 'successful'))->sum('points_delta');
        $virtualRefundsTotal = (int) $applyDate(LudoLedgerTransaction::where('type', 'refund')->where('status', 'successful'))->sum('points_delta');

        // Recent Activity Feeds (Real Database Records)
        $recentAuditLogs = LudoAdminAuditLog::with('admin')
            ->latest('id')
            ->take(8)
            ->get();

        $recentDisputes = LudoDispute::with(['openedBy', 'againstUser', 'battle'])
            ->latest('id')
            ->take(5)
            ->get();

        $recentUsers = User::latest('id')->take(5)->get();

        $recentMatches = LudoBattle::with(['creator', 'joiner', 'winner'])
            ->latest('id')
            ->take(5)
            ->get();

        return view('admin.ludo.dashboard', [
            'metrics' => [
                'registered_users' => $registeredUsersCount,
                'active_users' => $activeUsersCount,
                'suspended_users' => $suspendedUsersCount,
                'verified_users' => $verifiedUsersCount,
                'unverified_users' => $unverifiedUsersCount,
                'tournaments' => $tournamentsCount,
                'tournaments_upcoming' => $upcomingTournamentsCount,
                'tournaments_running' => $runningTournamentsCount,
                'tournaments_completed' => $completedTournamentsCount,
                'tournaments_cancelled' => $cancelledTournamentsCount,
                'open_battles' => $openBattlesCount,
                'active_matches' => $activeMatchesCount,
                'completed_matches' => $completedMatchesCount,
                'cancelled_matches' => $cancelledMatchesCount,
                'pending_results' => $pendingResultsCount,
                'open_disputes' => $openDisputesCount,
                'support_tickets' => $supportTicketsCount,
                'pending_kyc' => $pendingKycCount,
                'total_referrals' => $totalReferralsCount,
                'eligible_referrals' => $eligibleReferralsCount,
                'referral_points_awarded' => $referralPointsAwarded,
                'circulating_points' => $circulatingPoints,
                'points_issued' => $virtualPointsIssued,
                'points_prizes_paid' => $virtualPrizesPaid,
                'points_refunded' => $virtualRefundsTotal,
            ],
            'period' => $period,
            'from' => $from ? $from->format('Y-m-d') : '',
            'to' => $to ? $to->format('Y-m-d') : '',
            'recentAuditLogs' => $recentAuditLogs,
            'recentDisputes' => $recentDisputes,
            'recentUsers' => $recentUsers,
            'recentMatches' => $recentMatches,
            'activeSection' => 'dashboard',
        ]);
    }

    /**
     * PHASE 3 — User Management: List, Search, Filter
     */
    public function users(Request $request)
    {
        $query = User::query()->latest('id');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('player_uid', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('referral_code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->kyc_status);
        }

        $users = $query->paginate(12)->withQueryString();

        return view('admin.ludo.users.index', [
            'users' => $users,
            'activeSection' => 'users',
        ]);
    }

    /**
     * View User Dossier (Profile, Match Stats, Referrals, Matches, Disputes, KYC, Ledger, Audit Logs)
     */
    public function userShow($id)
    {
        $user = User::with(['kycSubmissions.reviewer', 'referrer', 'referrals.referredUser'])->findOrFail($id);

        $battleStats = $user->getBattleStats();

        $battles = LudoBattle::with(['creator', 'joiner', 'winner'])
            ->where(function ($q) use ($user) {
                $q->where('creator_id', $user->id)
                  ->orWhere('joiner_id', $user->id);
            })
            ->latest('id')
            ->paginate(6, ['*'], 'battles_page');

        $disputes = LudoDispute::with(['battle', 'resolver'])
            ->where(function ($q) use ($user) {
                $q->where('opened_by_user_id', $user->id)
                  ->orWhere('against_user_id', $user->id);
            })
            ->latest('id')
            ->get();

        $transactions = LudoLedgerTransaction::where('user_id', $user->id)
            ->latest('id')
            ->take(15)
            ->get();

        $tickets = LudoSupportTicket::where('user_id', $user->id)
            ->latest('id')
            ->take(10)
            ->get();

        $userAuditLogs = LudoAdminAuditLog::with('admin')
            ->where('target_type', 'User')
            ->where('target_id', $user->id)
            ->latest('id')
            ->take(10)
            ->get();

        return view('admin.ludo.users.show', [
            'user' => $user,
            'battleStats' => $battleStats,
            'battles' => $battles,
            'disputes' => $disputes,
            'transactions' => $transactions,
            'tickets' => $tickets,
            'userAuditLogs' => $userAuditLogs,
            'activeSection' => 'users',
        ]);
    }

    /**
     * Permitted Profile Corrections by Administrator (Audited)
     */
    public function userUpdateProfile(Request $request, $id)
    {
        $this->requireSeniorAdmin();

        $targetUser = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $targetUser->id],
            'phone' => ['nullable', 'string', 'max:25'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $before = "name={$targetUser->name}, email={$targetUser->email}, phone=" . ($targetUser->phone ?? 'null');
        $targetUser->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);
        $after = "name={$targetUser->name}, email={$targetUser->email}, phone=" . ($targetUser->phone ?? 'null');

        LudoAdminAuditLog::log(
            'user_profile_corrected',
            "Corrected profile for user #{$targetUser->id} ({$before} -> {$after}). Reason: {$validated['reason']}",
            'User',
            $targetUser->id
        );

        return redirect()->back()->with('success', "Profile details for {$targetUser->name} updated and recorded in audit log.");
    }

    /**
     * Toggle User Suspension (with mandatory reason)
     */
    public function userToggleStatus(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);

        // Prevent self suspension
        if ($targetUser->id === Auth::id()) {
            return redirect()->back()->with('error', 'You cannot suspend your own account.');
        }

        // Prevent non-superadmin from suspending a superadmin
        if ($targetUser->role === 'superadmin' && Auth::user()->role !== 'superadmin') {
            abort(403, 'Only a Superadmin can alter the status of a Superadmin account.');
        }

        $validated = $request->validate([
            'action' => ['required', 'in:suspend,reactivate'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        if ($validated['action'] === 'suspend') {
            $targetUser->update([
                'status' => 'suspended',
                'suspension_reason' => $validated['reason'],
            ]);
            LudoAdminAuditLog::log('user_suspended', "Suspended user {$targetUser->name} (#{$targetUser->id}). Reason: {$validated['reason']}", 'User', $targetUser->id);
            return redirect()->back()->with('success', "User {$targetUser->name} has been suspended.");
        } else {
            $targetUser->update([
                'status' => 'active',
                'suspension_reason' => null,
            ]);
            LudoAdminAuditLog::log('user_reactivated', "Reactivated user {$targetUser->name} (#{$targetUser->id}). Reason: {$validated['reason']}", 'User', $targetUser->id);
            return redirect()->back()->with('success', "User {$targetUser->name} has been reactivated.");
        }
    }

    /**
     * Update User Role (Restricted to Senior Admins, prevents unauthorized role escalation)
     */
    public function userUpdateRole(Request $request, $id)
    {
        $this->requireSeniorAdmin();

        $actor = Auth::user();
        $targetUser = User::findOrFail($id);

        $validated = $request->validate([
            'role' => ['required', 'in:normal,user,admin,superadmin,support_staff,moderator'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Prevent self-demotion
        if ($targetUser->id === $actor->id && $validated['role'] !== $actor->role) {
            return redirect()->back()->with('error', 'You cannot modify your own administrative role.');
        }

        // Only superadmin can grant superadmin or modify an existing superadmin
        if (($validated['role'] === 'superadmin' || $targetUser->role === 'superadmin') && $actor->role !== 'superadmin') {
            abort(403, 'Unauthorized role escalation: Only a Superadmin can assign or modify the Superadmin role.');
        }

        $oldRole = $targetUser->role;
        $targetUser->update(['role' => $validated['role']]);

        $reasonNote = !empty($validated['reason']) ? " Reason: {$validated['reason']}" : '';
        LudoAdminAuditLog::log('role_updated', "Changed role of {$targetUser->name} from {$oldRole} to {$validated['role']}.{$reasonNote}", 'User', $targetUser->id);

        return redirect()->back()->with('success', "User role updated to {$validated['role']}.");
    }

    /**
     * PHASE 4 — Tournament Management: List Tournaments
     */
    public function tournaments(Request $request)
    {
        $query = LudoTournament::with(['creator', 'winner'])->withCount('participants')->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('tournament_uid', 'like', "%{$s}%");
            });
        }

        $tournaments = $query->paginate(10)->withQueryString();

        return view('admin.ludo.tournaments.index', [
            'tournaments' => $tournaments,
            'activeSection' => 'tournaments',
        ]);
    }

    /**
     * Create Tournament
     */
    public function tournamentStore(Request $request)
    {
        $this->requireSeniorAdmin();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'entry_fee' => ['required', 'integer', 'min:0', 'max:50000'],
            'prize_pool' => ['required', 'integer', 'min:50', 'max:500000'],
            'max_players' => ['required', 'integer', 'in:4,8,16,32,64'],
            'starts_at' => ['required', 'date'],
            'registration_closes_at' => ['nullable', 'date'],
            'rules_text' => ['nullable', 'string'],
        ]);

        $tournament = LudoTournament::create([
            'tournament_uid' => 'TRN-' . strtoupper(Str::random(8)),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'entry_fee' => (int) $validated['entry_fee'],
            'prize_pool' => (int) $validated['prize_pool'],
            'max_players' => (int) $validated['max_players'],
            'status' => 'draft',
            'starts_at' => $validated['starts_at'],
            'registration_closes_at' => $validated['registration_closes_at'] ?? Carbon::parse($validated['starts_at'])->subMinutes(30),
            'rules_text' => $validated['rules_text'] ?? null,
            'created_by_admin_id' => Auth::id(),
        ]);

        LudoAdminAuditLog::log('tournament_created', "Created tournament '{$tournament->title}' ({$tournament->tournament_uid})", 'LudoTournament', $tournament->id);

        return redirect()->route('admin.ludo.tournaments.show', $tournament->id)->with('success', "Tournament created in draft mode.");
    }

    /**
     * Edit / Update Tournament Details
     */
    public function tournamentUpdate(Request $request, $id)
    {
        $this->requireSeniorAdmin();

        $tournament = LudoTournament::withCount('participants')->findOrFail($id);

        if (in_array($tournament->status, ['completed', 'cancelled'])) {
            return redirect()->back()->with('error', 'Completed or cancelled tournaments cannot be modified.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'entry_fee' => ['required', 'integer', 'min:0', 'max:50000'],
            'prize_pool' => ['required', 'integer', 'min:50', 'max:500000'],
            'max_players' => ['required', 'integer', 'in:4,8,16,32,64'],
            'starts_at' => ['required', 'date'],
            'registration_closes_at' => ['nullable', 'date'],
            'rules_text' => ['nullable', 'string'],
        ]);

        if ((int) $validated['max_players'] < $tournament->participants_count) {
            return redirect()->back()->with('error', "Capacity ({$validated['max_players']}) cannot be set lower than current registered participants ({$tournament->participants_count}).");
        }

        if ($tournament->participants_count > 0 && (int) $validated['entry_fee'] !== (int) $tournament->entry_fee) {
            return redirect()->back()->with('error', 'Entry fee cannot be changed after participants have already registered.');
        }

        $tournament->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'entry_fee' => (int) $validated['entry_fee'],
            'prize_pool' => (int) $validated['prize_pool'],
            'max_players' => (int) $validated['max_players'],
            'starts_at' => $validated['starts_at'],
            'registration_closes_at' => $validated['registration_closes_at'] ?? $tournament->registration_closes_at,
            'rules_text' => $validated['rules_text'] ?? null,
        ]);

        LudoAdminAuditLog::log('tournament_updated', "Updated configuration for tournament '{$tournament->title}' ({$tournament->tournament_uid})", 'LudoTournament', $tournament->id);

        return redirect()->back()->with('success', 'Tournament configuration updated successfully.');
    }

    /**
     * Show Tournament & Bracket Workbench
     */
    public function tournamentShow($id)
    {
        $tournament = LudoTournament::with(['creator', 'winner', 'participants.user'])->findOrFail($id);

        $tournamentAuditLogs = LudoAdminAuditLog::with('admin')
            ->where('target_type', 'LudoTournament')
            ->where('target_id', $tournament->id)
            ->latest('id')
            ->take(10)
            ->get();

        return view('admin.ludo.tournaments.show', [
            'tournament' => $tournament,
            'tournamentAuditLogs' => $tournamentAuditLogs,
            'activeSection' => 'tournaments',
        ]);
    }

    /**
     * Close Tournament Registrations Immediately
     */
    public function tournamentCloseRegistration($id)
    {
        $this->requireSeniorAdmin();

        $tournament = LudoTournament::findOrFail($id);
        $tournament->update([
            'registration_closes_at' => now(),
        ]);

        LudoAdminAuditLog::log('tournament_registration_closed', "Closed registrations for tournament '{$tournament->title}' ({$tournament->tournament_uid})", 'LudoTournament', $tournament->id);

        return redirect()->back()->with('success', 'Tournament registrations have been closed.');
    }

    /**
     * Change Tournament Status (Publish / Run / Complete with Winner Payout / Cancel with Participant Refunds)
     */
    public function tournamentUpdateStatus(Request $request, $id)
    {
        $this->requireSeniorAdmin();

        $tournament = LudoTournament::with('participants.user')->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,published,running,completed,cancelled'],
            'reason' => ['nullable', 'string', 'max:500'],
            'winner_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $tournament->status;

        if ($oldStatus === $newStatus) {
            return redirect()->back()->with('success', "Tournament is already in {$newStatus} status.");
        }

        // Prevent invalid transitions from terminal states
        if (in_array($oldStatus, ['completed', 'cancelled'])) {
            return redirect()->back()->with('error', "Cannot transition a {$oldStatus} tournament back to {$newStatus}.");
        }

        // Cancellation requires a reason and refunds registered participants
        if ($newStatus === 'cancelled') {
            if (empty($validated['reason']) || strlen(trim($validated['reason'])) < 5) {
                return redirect()->back()->withErrors(['reason' => 'A mandatory cancellation reason (at least 5 characters) is required when cancelling a tournament.']);
            }

            $reason = trim($validated['reason']);

            DB::transaction(function () use ($tournament, $reason) {
                foreach ($tournament->participants as $participant) {
                    if ($tournament->entry_fee > 0 && $participant->user) {
                        LudoLedgerTransaction::recordAdjustment(
                            $participant->user,
                            $tournament->entry_fee,
                            'refund',
                            "Tournament {$tournament->tournament_uid} cancelled: {$reason}",
                            'successful',
                            $reason,
                            Auth::id()
                        );
                        $participant->user->notify(new LudoInAppNotification(
                            "🪙 Tournament Cancelled & Refunded",
                            "Tournament '{$tournament->title}' was cancelled ({$reason}). 🪙 {$tournament->entry_fee} points refunded.",
                            "tournament",
                            url('/tournaments')
                        ));
                    }
                }

                $tournament->update([
                    'status' => 'cancelled',
                    'completed_at' => now(),
                ]);
            });

            LudoAdminAuditLog::log('tournament_cancelled', "Cancelled tournament '{$tournament->title}' ({$tournament->tournament_uid}) and refunded {$tournament->participants->count()} participants. Reason: {$reason}", 'LudoTournament', $tournament->id);

            return redirect()->back()->with('success', "Tournament cancelled and entry points refunded to all {$tournament->participants->count()} registered participants.");
        }

        // Completion awards prize_pool to designated winner if selected
        if ($newStatus === 'completed') {
            $winnerUserId = $validated['winner_user_id'] ?? $tournament->winner_user_id;

            DB::transaction(function () use ($tournament, $winnerUserId) {
                $updateData = [
                    'status' => 'completed',
                    'completed_at' => now(),
                ];

                if ($winnerUserId) {
                    $participant = $tournament->participants()->where('user_id', $winnerUserId)->first();
                    if ($participant) {
                        $updateData['winner_user_id'] = $winnerUserId;
                        $participant->update(['status' => 'winner']);
                        $tournament->participants()->where('user_id', '!=', $winnerUserId)->update(['status' => 'eliminated']);

                        $winner = User::find($winnerUserId);
                        if ($winner && $tournament->prize_pool > 0) {
                            LudoLedgerTransaction::recordAdjustment(
                                $winner,
                                $tournament->prize_pool,
                                'prize_payout',
                                "Championship Prize for Tournament '{$tournament->title}' ({$tournament->tournament_uid})",
                                'successful',
                                "Tournament champion settlement",
                                Auth::id()
                            );
                            $winner->notify(new LudoInAppNotification(
                                "🏆 Tournament Champion!",
                                "Congratulations! You won '{$tournament->title}' and earned 🪙 {$tournament->prize_pool} virtual points!",
                                "tournament",
                                url('/tournaments')
                            ));
                        }
                    }
                }

                $tournament->update($updateData);
            });

            LudoAdminAuditLog::log('tournament_status', "Changed status of tournament '{$tournament->title}' from {$oldStatus} to completed", 'LudoTournament', $tournament->id);

            return redirect()->back()->with('success', 'Tournament marked as completed.');
        }

        $tournament->update(['status' => $newStatus]);

        $reasonSuffix = !empty($validated['reason']) ? " Reason: {$validated['reason']}" : '';
        LudoAdminAuditLog::log('tournament_status', "Changed status of tournament '{$tournament->title}' from {$oldStatus} to {$newStatus}.{$reasonSuffix}", 'LudoTournament', $tournament->id);

        return redirect()->back()->with('success', "Tournament status transitioned to {$newStatus}.");
    }

    /**
     * Generate Tournament Bracket
     */
    public function tournamentGenerateBracket($id)
    {
        $tournament = LudoTournament::with('participants')->findOrFail($id);

        if ($tournament->participants->count() < 2) {
            return redirect()->back()->with('error', 'At least 2 registered participants are required to generate a bracket.');
        }

        $tournament->generateBracket();

        LudoAdminAuditLog::log('tournament_bracket', "Generated single elimination bracket for '{$tournament->title}'", 'LudoTournament', $tournament->id);

        return redirect()->back()->with('success', 'Tournament bracket generated successfully.');
    }

    /**
     * PHASE 5 — Match Management: Search, Date Filter, View, Cancel or Settle Result with Reason
     */
    public function matches(Request $request)
    {
        $query = LudoBattle::with(['creator', 'joiner', 'winner', 'dispute'])->latest('id');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('battle_uid', 'like', "%{$s}%")
                  ->orWhere('room_code', 'like', "%{$s}%")
                  ->orWhereHas('creator', function ($qc) use ($s) {
                      $qc->where('name', 'like', "%{$s}%")->orWhere('player_uid', 'like', "%{$s}%");
                  })
                  ->orWhereHas('joiner', function ($qj) use ($s) {
                      $qj->where('name', 'like', "%{$s}%")->orWhere('player_uid', 'like', "%{$s}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('review_status')) {
            $query->where('review_status', $request->review_status);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $matches = $query->paginate(12)->withQueryString();

        return view('admin.ludo.matches.index', [
            'matches' => $matches,
            'activeSection' => 'matches',
        ]);
    }

    /**
     * Show Match Details Dossier
     */
    public function matchShow($id)
    {
        $match = LudoBattle::with(['creator', 'joiner', 'winner', 'dispute.evidences.user'])->findOrFail($id);

        $matchTransactions = LudoLedgerTransaction::with('user')
            ->where('description', 'like', "%{$match->battle_uid}%")
            ->latest('id')
            ->get();

        $matchAuditLogs = LudoAdminAuditLog::with('admin')
            ->where('target_type', 'LudoBattle')
            ->where('target_id', $match->id)
            ->latest('id')
            ->get();

        return view('admin.ludo.matches.show', [
            'match' => $match,
            'matchTransactions' => $matchTransactions,
            'matchAuditLogs' => $matchAuditLogs,
            'activeSection' => 'matches',
        ]);
    }

    /**
     * Cancel Match Administratively & Refund Both (Concurrency-safe)
     */
    public function matchCancel(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'reason.required' => 'A mandatory reason is required to cancel a match.',
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $match = LudoBattle::with(['creator', 'joiner', 'dispute'])->lockForUpdate()->findOrFail($id);

            if ($match->status === 'cancelled') {
                return redirect()->back()->with('error', 'This match has already been cancelled.');
            }

            if ($match->status === 'completed') {
                return redirect()->back()->with('error', 'Completed matches with finalized prize payouts cannot be cancelled.');
            }

            // Refund creator
            if ($match->creator) {
                LudoLedgerTransaction::recordAdjustment(
                    $match->creator,
                    $match->entry_fee,
                    'refund',
                    "Admin match cancellation for {$match->battle_uid}: {$validated['reason']}",
                    'successful',
                    $validated['reason'],
                    Auth::id()
                );

                $match->creator->notify(new LudoInAppNotification(
                    "🪙 Match Cancelled & Refunded",
                    "Battle {$match->battle_uid} was cancelled by Admin ({$validated['reason']}). 🪙 {$match->entry_fee} refunded.",
                    "ledger",
                    url('/wallet')
                ));
            }

            // Refund joiner if joined
            if ($match->joiner) {
                LudoLedgerTransaction::recordAdjustment(
                    $match->joiner,
                    $match->entry_fee,
                    'refund',
                    "Admin match cancellation for {$match->battle_uid}: {$validated['reason']}",
                    'successful',
                    $validated['reason'],
                    Auth::id()
                );

                $match->joiner->notify(new LudoInAppNotification(
                    "🪙 Match Cancelled & Refunded",
                    "Battle {$match->battle_uid} was cancelled by Admin ({$validated['reason']}). 🪙 {$match->entry_fee} refunded.",
                    "ledger",
                    url('/wallet')
                ));
            }

            $match->update([
                'status' => 'cancelled',
                'final_result' => 'cancelled',
                'review_status' => 'auto_approved',
                'completed_at' => now(),
            ]);

            if ($match->dispute && $match->dispute->status !== 'resolved') {
                $match->dispute->update([
                    'status' => 'resolved',
                    'admin_decision_reason' => "Match cancelled and refunded: {$validated['reason']}",
                    'resolved_by_admin_id' => Auth::id(),
                    'resolved_at' => now(),
                ]);
            }

            LudoAdminAuditLog::log('match_cancelled', "Administratively cancelled match {$match->battle_uid}. Reason: {$validated['reason']}", 'LudoBattle', $match->id);

            return redirect()->back()->with('success', "Match {$match->battle_uid} cancelled and entry fees refunded to players.");
        });
    }

    /**
     * Resolve / Settle Match Result Directly from Match Dossier (For Pending Result Audits & Running Matches)
     */
    public function matchResolveResult(Request $request, $id)
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:award_creator,award_joiner,mark_verified'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        return DB::transaction(function () use ($id, $validated) {
            $match = LudoBattle::with(['creator', 'joiner', 'dispute'])->lockForUpdate()->findOrFail($id);

            if ($validated['decision'] === 'mark_verified') {
                $match->update(['review_status' => 'verified']);
                LudoAdminAuditLog::log('match_verified', "Verified match {$match->battle_uid}. Reason: {$validated['reason']}", 'LudoBattle', $match->id);
                return redirect()->back()->with('success', "Match {$match->battle_uid} marked as verified.");
            }

            if (in_array($match->status, ['completed', 'cancelled'])) {
                return redirect()->back()->with('error', 'This match has already been settled or cancelled.');
            }

            if (!$match->joiner_id) {
                return redirect()->back()->with('error', 'Cannot award victory on an open match without an opponent.');
            }

            $winner = $validated['decision'] === 'award_creator' ? $match->creator : $match->joiner;

            $match->update([
                'status' => 'completed',
                'winner_id' => $winner->id,
                'final_result' => 'won',
                'review_status' => 'verified',
                'completed_at' => now(),
            ]);

            LudoLedgerTransaction::recordAdjustment(
                $winner,
                $match->prize,
                'prize_payout',
                "Won Battle {$match->battle_uid} (Admin Result Review: {$validated['reason']})",
                'successful',
                $validated['reason'],
                Auth::id()
            );

            $winner->notify(new LudoInAppNotification(
                "🏆 Match Result Verified — Victory!",
                "Admin verified Battle {$match->battle_uid} in your favor! 🪙 {$match->prize} virtual points credited.",
                "match",
                url('/history')
            ));

            if ($match->dispute && $match->dispute->status !== 'resolved') {
                $match->dispute->update([
                    'status' => 'resolved',
                    'admin_decision_reason' => "Awarded to {$winner->name}: {$validated['reason']}",
                    'resolved_by_admin_id' => Auth::id(),
                    'resolved_at' => now(),
                ]);
            }

            LudoAdminAuditLog::log('match_result_settled', "Settled match {$match->battle_uid} in favor of {$winner->name} (#{$winner->id}). Reason: {$validated['reason']}", 'LudoBattle', $match->id);

            return redirect()->back()->with('success', "Match {$match->battle_uid} settled in favor of {$winner->name} and 🪙 {$match->prize} credited.");
        });
    }

    /**
     * PHASE 7 — Dispute Management: List, Filter, and Dedicated Admin Dispute Dossier
     */
    public function disputes(Request $request)
    {
        $query = LudoDispute::with(['battle', 'openedBy', 'againstUser', 'resolver'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('reason_category')) {
            $query->where('reason_category', $request->reason_category);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('dispute_uid', 'like', "%{$s}%")
                  ->orWhereHas('battle', function ($qb) use ($s) {
                      $qb->where('battle_uid', 'like', "%{$s}%");
                  })
                  ->orWhereHas('openedBy', function ($qo) use ($s) {
                      $qo->where('name', 'like', "%{$s}%");
                  })
                  ->orWhereHas('againstUser', function ($qa) use ($s) {
                      $qa->where('name', 'like', "%{$s}%");
                  });
            });
        }

        $disputes = $query->paginate(10)->withQueryString();

        $summary = [
            'open' => LudoDispute::where('status', 'open')->count(),
            'under_review' => LudoDispute::where('status', 'under_review')->count(),
            'more_evidence_requested' => LudoDispute::where('status', 'more_evidence_requested')->count(),
            'resolved' => LudoDispute::where('status', 'resolved')->count(),
            'rejected' => LudoDispute::where('status', 'rejected')->count(),
        ];

        return view('admin.ludo.disputes.index', [
            'disputes' => $disputes,
            'summary' => $summary,
            'activeSection' => 'disputes',
        ]);
    }

    /**
     * Dedicated Admin Dispute Dossier View
     */
    public function disputeShow($id)
    {
        $dispute = LudoDispute::with(['battle.creator', 'battle.joiner', 'openedBy', 'againstUser', 'resolver', 'evidences.user'])->findOrFail($id);

        $disputeAuditLogs = LudoAdminAuditLog::with('admin')
            ->where('target_type', 'LudoDispute')
            ->where('target_id', $dispute->id)
            ->latest('id')
            ->get();

        return view('admin.ludo.disputes.show', [
            'dispute' => $dispute,
            'disputeAuditLogs' => $disputeAuditLogs,
            'activeSection' => 'disputes',
        ]);
    }

    /**
     * PHASE 6 — Virtual Points Ledger Inspection & Manual Adjustments
     */
    public function ledger(Request $request)
    {
        $query = LudoLedgerTransaction::with(['user', 'admin'])->latest('id');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('tx_uid', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%")
                  ->orWhereHas('user', function ($qu) use ($s) {
                      $qu->where('name', 'like', "%{$s}%")
                         ->orWhere('email', 'like', "%{$s}%")
                         ->orWhere('player_uid', 'like', "%{$s}%");
                  });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $transactions = $query->paginate(15)->withQueryString();

        $summary = [
            'total_user_balances' => (int) User::sum('coins'),
            'total_deposits' => (int) LudoLedgerTransaction::where('type', 'deposit')->where('status', 'successful')->sum('points_delta'),
            'total_entry_fees' => abs((int) LudoLedgerTransaction::where('type', 'entry_fee')->where('status', 'successful')->sum('points_delta')),
            'total_prizes' => (int) LudoLedgerTransaction::where('type', 'prize_payout')->where('status', 'successful')->sum('points_delta'),
            'total_refunds' => (int) LudoLedgerTransaction::where('type', 'refund')->where('status', 'successful')->sum('points_delta'),
            'total_referrals' => (int) LudoLedgerTransaction::where('type', 'referral_bonus')->where('status', 'successful')->sum('points_delta'),
            'total_admin_adjustments' => (int) LudoLedgerTransaction::where('type', 'admin_adjustment')->where('status', 'successful')->sum('points_delta'),
        ];

        $usersList = User::select('id', 'name', 'email', 'player_uid', 'coins')->orderBy('name')->limit(200)->get();

        return view('admin.ludo.ledger.index', [
            'transactions' => $transactions,
            'summary' => $summary,
            'usersList' => $usersList,
            'activeSection' => 'ledger',
        ]);
    }

    /**
     * Perform Authorized Ledger Adjustment
     */
    public function ledgerAdjust(Request $request)
    {
        $this->requireSeniorAdmin();

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ], [
            'reason.required' => 'Mandatory audit explanation is required for all administrative adjustments.',
            'reason.min' => 'Explanation must be at least 8 characters.',
        ]);

        $targetUser = User::findOrFail($validated['user_id']);
        $amount = (int) $validated['amount'];
        $reason = $validated['reason'];

        try {
            $tx = LudoLedgerTransaction::recordAdjustment(
                $targetUser,
                $amount,
                'admin_adjustment',
                "Administrative balance adjustment: " . ($amount > 0 ? "+{$amount}" : "{$amount}"),
                'successful',
                $reason,
                Auth::id()
            );

            LudoAdminAuditLog::log('ledger_adjusted', "Adjusted balance for {$targetUser->name} by {$amount} points. Reason: {$reason}", 'LudoLedgerTransaction', $tx->id);

            return redirect()->back()->with('success', "Balance adjustment executed successfully (Ref: {$tx->tx_uid}).");
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', "Adjustment failed: " . $e->getMessage());
        }
    }

    /**
     * PHASE 8 — Referral Management: Records, Configuration, Duplicate Detection & Status Control
     */
    public function referrals(Request $request)
    {
        $query = LudoReferral::with(['referrer', 'referredUser'])->latest('id');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('referral_code', 'like', "%{$s}%")
                  ->orWhereHas('referrer', function ($qr) use ($s) {
                      $qr->where('name', 'like', "%{$s}%")
                         ->orWhere('email', 'like', "%{$s}%")
                         ->orWhere('player_uid', 'like', "%{$s}%");
                  })
                  ->orWhereHas('referredUser', function ($qu) use ($s) {
                      $qu->where('name', 'like', "%{$s}%")
                         ->orWhere('email', 'like', "%{$s}%")
                         ->orWhere('player_uid', 'like', "%{$s}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $referrals = $query->paginate(12)->withQueryString();

        // Duplicate / Suspicious Referral Detection
        $duplicateReferredUserIds = LudoReferral::select('referred_user_id')
            ->groupBy('referred_user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('referred_user_id')
            ->toArray();

        $summary = [
            'total_referrals' => LudoReferral::count(),
            'eligible_referrals' => LudoReferral::where('status', 'eligible')->count(),
            'revoked_referrals' => LudoReferral::where('status', 'revoked')->count(),
            'total_reward_points' => (int) LudoReferral::where('status', 'eligible')->sum('reward_points'),
            'duplicate_anomalies' => count($duplicateReferredUserIds),
        ];

        $referralSettings = [
            'referral_enabled' => LudoSetting::get('referral_enabled', 'on'),
            'referral_reward_points' => LudoSetting::get('referral_reward_points', '50'),
            'referral_commission' => LudoSetting::get('referral_commission', '3%'),
        ];

        return view('admin.ludo.referrals.index', [
            'referrals' => $referrals,
            'summary' => $summary,
            'duplicateReferredUserIds' => $duplicateReferredUserIds,
            'referralSettings' => $referralSettings,
            'activeSection' => 'referrals',
        ]);
    }

    /**
     * Update Referral Program Configuration
     */
    public function referralSettingsUpdate(Request $request)
    {
        $this->requireSeniorAdmin();

        $validated = $request->validate([
            'referral_enabled' => ['required', 'in:on,off'],
            'referral_reward_points' => ['required', 'integer', 'min:0', 'max:5000'],
            'referral_commission' => ['required', 'string', 'max:20'],
        ]);

        foreach ($validated as $k => $v) {
            LudoSetting::set($k, $v, 'referral');
        }

        LudoAdminAuditLog::log('referral_settings_updated', "Updated referral configuration (enabled={$validated['referral_enabled']}, reward_points={$validated['referral_reward_points']}, commission={$validated['referral_commission']})");

        return redirect()->back()->with('success', 'Referral program configuration updated successfully.');
    }

    /**
     * Update Referral Record Status (Approve / Revoke with Audit Trail)
     */
    public function referralUpdateStatus(Request $request, $id)
    {
        $this->requireSeniorAdmin();

        $referral = LudoReferral::with(['referrer', 'referredUser'])->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:eligible,revoked'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $oldStatus = $referral->status;
        $newStatus = $validated['status'];

        if ($oldStatus === $newStatus) {
            return redirect()->back()->with('success', "Referral is already marked as {$newStatus}.");
        }

        $referral->update(['status' => $newStatus]);

        LudoAdminAuditLog::log(
            'referral_status_updated',
            "Changed referral #{$referral->id} ({$referral->referral_code}) from {$oldStatus} to {$newStatus}. Reason: {$validated['reason']}",
            'LudoReferral',
            $referral->id
        );

        return redirect()->back()->with('success', "Referral record #{$referral->id} status updated to {$newStatus}.");
    }

    /**
     * PHASE 11 — Content Management: Announcements, Rules in Hindi & English, FAQ
     */
    public function content(Request $request)
    {
        $announcementHindi = LudoSetting::get('announcement_hi', '👑 Fsaludoclub में आपका स्वागत है! निष्पक्ष खेलें और शानदार वर्चुअल रिवार्ड्स जीतें।');
        $announcementEnglish = LudoSetting::get('announcement_en', 'Welcome to Fsaludoclub! Play fair and compete for virtual points glory.');
        $rulesEnglish = LudoSetting::get('rules_en', "1. Classic 2-Player mode only.\n2. Room Code must be joined within 5 minutes.\n3. 15-minute submission window for match results.");
        $rulesHindi = LudoSetting::get('rules_hi', "1. केवल क्लासिक 2-खिलाड़ी मोड में खेलें।\n2. 5 मिनट के भीतर रूम कोड से जुड़ें।\n3. परिणाम सबमिट करने की 15 मिनट की समय सीमा।");
        $faqContent = LudoSetting::get('faq_content', "Q: Are points real money?\nA: No, points are non-cash virtual gaming points only.");

        return view('admin.ludo.content.index', [
            'announcement_hi' => $announcementHindi,
            'announcement_en' => $announcementEnglish,
            'rules_en' => $rulesEnglish,
            'rules_hi' => $rulesHindi,
            'faq_content' => $faqContent,
            'activeSection' => 'content',
        ]);
    }

    /**
     * Update Content & Rules
     */
    public function contentUpdate(Request $request)
    {
        $this->requireSeniorAdmin();

        $validated = $request->validate([
            'announcement_hi' => ['nullable', 'string', 'max:500'],
            'announcement_en' => ['nullable', 'string', 'max:500'],
            'rules_en' => ['nullable', 'string'],
            'rules_hi' => ['nullable', 'string'],
            'faq_content' => ['nullable', 'string'],
        ]);

        foreach ($validated as $key => $val) {
            LudoSetting::set($key, $val, 'content');
        }

        LudoAdminAuditLog::log('content_updated', "Updated CMS announcements and bilingual rules content.");

        return redirect()->back()->with('success', 'CMS content and rules saved successfully.');
    }

    /**
     * System Settings: Platform branding, deadlines, limits, referral rewards, maintenance
     */
    public function settings()
    {
        $settings = [
            'platform_name' => LudoSetting::get('platform_name', 'Fsaludoclub'),
            'submission_window' => LudoSetting::get('submission_window', config('ludo.battle.submission_window_minutes', 15)),
            'referral_commission' => LudoSetting::get('referral_commission', '3%'),
            'referral_reward_points' => LudoSetting::get('referral_reward_points', '50'),
            'referral_enabled' => LudoSetting::get('referral_enabled', 'on'),
            'welcome_bonus_points' => LudoSetting::get('welcome_bonus_points', '100'),
            'maintenance_mode' => LudoSetting::get('maintenance_mode', 'off'),
            'support_email' => LudoSetting::get('support_email', 'support@fsaludoclub.test'),
            'support_whatsapp' => LudoSetting::get('support_whatsapp', '+91 9876543210'),
            'support_telegram' => LudoSetting::get('support_telegram', 'https://t.me/fsaludoclub'),
            'support_hours' => LudoSetting::get('support_hours', 'Mon-Sun 10:00 AM - 10:00 PM IST'),
            'minimum_battle_amount' => LudoSetting::get('minimum_battle_amount', '50'),
            'kyc_mandatory' => LudoSetting::get('kyc_mandatory', 'off'),
        ];

        return view('admin.ludo.settings.index', [
            'settings' => $settings,
            'activeSection' => 'settings',
        ]);
    }

    /**
     * Update Platform Settings
     */
    public function settingsUpdate(Request $request)
    {
        $this->requireSeniorAdmin();

        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:100'],
            'submission_window' => ['required', 'integer', 'min:5', 'max:120'],
            'referral_commission' => ['required', 'string', 'max:20'],
            'referral_reward_points' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'referral_enabled' => ['nullable', 'in:on,off'],
            'welcome_bonus_points' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'maintenance_mode' => ['required', 'in:on,off'],
            'support_email' => ['required', 'email'],
            'support_whatsapp' => ['nullable', 'string', 'max:30'],
            'support_telegram' => ['nullable', 'string', 'max:255'],
            'support_hours' => ['nullable', 'string', 'max:100'],
            'minimum_battle_amount' => ['required', 'integer', 'min:10', 'max:1000'],
            'kyc_mandatory' => ['required', 'in:on,off'],
        ]);

        foreach ($validated as $k => $v) {
            if (!is_null($v)) {
                LudoSetting::set($k, $v, 'system');
            }
        }

        LudoAdminAuditLog::log('settings_updated', "Updated platform system configurations and policy limits.");

        return redirect()->back()->with('success', 'System configurations updated.');
    }

    /**
     * PHASE 10 — Support Tickets Management Desk
     */
    public function tickets(Request $request)
    {
        $query = LudoSupportTicket::with(['user'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('ticket_uid', 'like', "%{$s}%")
                  ->orWhere('subject', 'like', "%{$s}%")
                  ->orWhereHas('user', function ($qu) use ($s) {
                      $qu->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                  });
            });
        }

        $tickets = $query->paginate(12)->withQueryString();

        $summary = [
            'open' => LudoSupportTicket::where('status', 'open')->count(),
            'in_progress' => LudoSupportTicket::where('status', 'in_progress')->count(),
            'answered' => LudoSupportTicket::where('status', 'answered')->count(),
            'closed' => LudoSupportTicket::whereIn('status', ['resolved', 'closed'])->count(),
        ];

        return view('admin.ludo.tickets.index', [
            'tickets' => $tickets,
            'summary' => $summary,
            'activeSection' => 'tickets',
        ]);
    }

    /**
     * Dedicated Admin Support Ticket Workbench
     */
    public function ticketShow($id)
    {
        $ticket = LudoSupportTicket::with(['user', 'messages.user'])->findOrFail($id);

        return view('admin.ludo.tickets.show', [
            'ticket' => $ticket,
            'activeSection' => 'tickets',
        ]);
    }

    /**
     * Update Ticket Status & Priority
     */
    public function ticketUpdateStatus(Request $request, $id)
    {
        $ticket = LudoSupportTicket::with('user')->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,answered,resolved,closed'],
            'priority' => ['nullable', 'in:normal,high,urgent'],
        ]);

        $oldStatus = $ticket->status;
        $ticket->update([
            'status' => $validated['status'],
            'priority' => $validated['priority'] ?? $ticket->priority,
        ]);

        if ($ticket->user && $oldStatus !== $validated['status']) {
            $ticket->user->notify(new LudoInAppNotification(
                "🎫 Support Ticket Status Updated",
                "Your support ticket #{$ticket->ticket_uid} status is now " . strtoupper(str_replace('_', ' ', $validated['status'])) . ".",
                "support",
                url('/support/ticket/' . $ticket->id)
            ));
        }

        LudoAdminAuditLog::log('ticket_status_updated', "Updated ticket {$ticket->ticket_uid} status from {$oldStatus} to {$validated['status']}", 'LudoSupportTicket', $ticket->id);

        return redirect()->back()->with('success', "Ticket {$ticket->ticket_uid} updated to {$validated['status']}.");
    }

    /**
     * PHASE 9 — KYC Verifications Management Desk
     */
    public function kycIndex(Request $request)
    {
        $query = LudoKycSubmission::with(['user', 'reviewer'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('document_number', 'like', "%{$s}%")
                  ->orWhereHas('user', function ($qu) use ($s) {
                      $qu->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                  });
            });
        }

        $submissions = $query->paginate(12)->withQueryString();

        $summary = [
            'pending' => LudoKycSubmission::where('status', 'pending')->count(),
            'approved' => LudoKycSubmission::where('status', 'approved')->count(),
            'rejected' => LudoKycSubmission::where('status', 'rejected')->count(),
        ];

        return view('admin.ludo.kyc.index', [
            'submissions' => $submissions,
            'summary' => $summary,
            'activeSection' => 'kyc',
        ]);
    }

    /**
     * PHASE 12 — Reports & Analytics Workbench
     */
    public function reports(Request $request)
    {
        [$period, $from, $to] = $this->resolveDateRange($request);

        $applyDate = function ($query, $column = 'created_at') use ($from, $to) {
            if ($from) {
                $query->where($column, '>=', $from);
            }
            if ($to) {
                $query->where($column, '<=', $to);
            }
            return $query;
        };

        $reportData = [
            'users' => [
                'total_registered' => $applyDate(User::query())->count(),
                'active' => $applyDate(User::where('status', 'active'))->count(),
                'suspended' => $applyDate(User::where('status', 'suspended'))->count(),
                'kyc_verified' => $applyDate(User::where('kyc_status', 'verified'))->count(),
                'kyc_pending' => $applyDate(User::where('kyc_status', 'pending'))->count(),
            ],
            'tournaments' => [
                'total' => $applyDate(LudoTournament::query())->count(),
                'published' => $applyDate(LudoTournament::where('status', 'published'))->count(),
                'running' => $applyDate(LudoTournament::where('status', 'running'))->count(),
                'completed' => $applyDate(LudoTournament::where('status', 'completed'))->count(),
                'cancelled' => $applyDate(LudoTournament::where('status', 'cancelled'))->count(),
                'total_participations' => $applyDate(LudoTournamentParticipant::query())->count(),
            ],
            'matches' => [
                'total' => $applyDate(LudoBattle::query())->count(),
                'open' => $applyDate(LudoBattle::where('status', 'open'))->count(),
                'running' => $applyDate(LudoBattle::where('status', 'running'))->count(),
                'completed' => $applyDate(LudoBattle::where('status', 'completed'))->count(),
                'cancelled' => $applyDate(LudoBattle::where('status', 'cancelled'))->count(),
                'pending_reviews' => $applyDate(LudoBattle::where('review_status', 'under_review'))->count(),
                'disputed_matches' => $applyDate(LudoBattle::where('review_status', 'disputed'))->count(),
            ],
            'disputes' => [
                'total' => $applyDate(LudoDispute::query())->count(),
                'open' => $applyDate(LudoDispute::whereIn('status', ['open', 'under_review', 'more_evidence_requested']))->count(),
                'resolved' => $applyDate(LudoDispute::where('status', 'resolved'))->count(),
                'rejected' => $applyDate(LudoDispute::where('status', 'rejected'))->count(),
            ],
            'referrals' => [
                'total' => $applyDate(LudoReferral::query())->count(),
                'eligible' => $applyDate(LudoReferral::where('status', 'eligible'))->count(),
                'revoked' => $applyDate(LudoReferral::where('status', 'revoked'))->count(),
                'virtual_points_rewarded' => (int) $applyDate(LudoReferral::where('status', 'eligible'))->sum('reward_points'),
            ],
            'virtual_points' => [
                'circulating_balance' => (int) User::sum('coins'),
                'deposits_credited' => (int) $applyDate(LudoLedgerTransaction::where('type', 'deposit')->where('status', 'successful'))->sum('points_delta'),
                'entry_fees_staked' => abs((int) $applyDate(LudoLedgerTransaction::where('type', 'entry_fee')->where('status', 'successful'))->sum('points_delta')),
                'prizes_awarded' => (int) $applyDate(LudoLedgerTransaction::where('type', 'prize_payout')->where('status', 'successful'))->sum('points_delta'),
                'refunds_returned' => (int) $applyDate(LudoLedgerTransaction::where('type', 'refund')->where('status', 'successful'))->sum('points_delta'),
                'referral_bonuses' => (int) $applyDate(LudoLedgerTransaction::where('type', 'referral_bonus')->where('status', 'successful'))->sum('points_delta'),
                'admin_adjustments' => (int) $applyDate(LudoLedgerTransaction::where('type', 'admin_adjustment')->where('status', 'successful'))->sum('points_delta'),
            ],
            'tickets' => [
                'total' => $applyDate(LudoSupportTicket::query())->count(),
                'open' => $applyDate(LudoSupportTicket::where('status', 'open'))->count(),
                'in_progress' => $applyDate(LudoSupportTicket::where('status', 'in_progress'))->count(),
                'answered' => $applyDate(LudoSupportTicket::where('status', 'answered'))->count(),
                'closed' => $applyDate(LudoSupportTicket::whereIn('status', ['resolved', 'closed']))->count(),
            ],
        ];

        return view('admin.ludo.reports.index', [
            'report' => $reportData,
            'period' => $period,
            'from' => $from ? $from->format('Y-m-d') : '',
            'to' => $to ? $to->format('Y-m-d') : '',
            'activeSection' => 'reports',
        ]);
    }

    /**
     * Authorized CSV Export for Administrative Reports
     */
    public function reportsExportCsv(Request $request, string $type): StreamedResponse
    {
        $this->requireSeniorAdmin();

        $allowedTypes = ['users', 'tournaments', 'matches', 'ledger', 'disputes', 'referrals', 'tickets', 'audit_logs'];
        if (!in_array($type, $allowedTypes)) {
            abort(404, 'Unsupported report export type.');
        }

        LudoAdminAuditLog::log('report_exported', "Exported CSV report for module: {$type}");

        $filename = 'fsaludoclub_' . $type . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($type) {
            $handle = fopen('php://output', 'w');

            if ($type === 'users') {
                fputcsv($handle, ['ID', 'Player UID', 'Name', 'Email', 'Phone', 'Role', 'Status', 'KYC Status', 'Virtual Points Balance', 'Referral Code', 'Registered At']);
                User::orderBy('id')->chunk(200, function ($rows) use ($handle) {
                    foreach ($rows as $u) {
                        fputcsv($handle, [$u->id, $u->player_uid, $u->name, $u->email, $u->phone, $u->role, $u->status, $u->kyc_status, $u->coins, $u->referral_code, $u->created_at]);
                    }
                });
            } elseif ($type === 'tournaments') {
                fputcsv($handle, ['ID', 'Tournament UID', 'Title', 'Entry Points', 'Prize Pool (Virtual Points)', 'Capacity', 'Status', 'Starts At', 'Completed At']);
                LudoTournament::orderBy('id')->chunk(200, function ($rows) use ($handle) {
                    foreach ($rows as $t) {
                        fputcsv($handle, [$t->id, $t->tournament_uid, $t->title, $t->entry_fee, $t->prize_pool, $t->max_players, $t->status, $t->starts_at, $t->completed_at]);
                    }
                });
            } elseif ($type === 'matches') {
                fputcsv($handle, ['ID', 'Battle UID', 'Creator ID', 'Joiner ID', 'Winner ID', 'Entry Points', 'Virtual Prize', 'Status', 'Review Status', 'Room Code', 'Created At']);
                LudoBattle::orderBy('id')->chunk(200, function ($rows) use ($handle) {
                    foreach ($rows as $m) {
                        fputcsv($handle, [$m->id, $m->battle_uid, $m->creator_id, $m->joiner_id, $m->winner_id, $m->entry_fee, $m->prize, $m->status, $m->review_status, $m->room_code, $m->created_at]);
                    }
                });
            } elseif ($type === 'ledger') {
                fputcsv($handle, ['Tx UID', 'User ID', 'Type', 'Virtual Points Delta', 'Balance Before', 'Balance After', 'Status', 'Description', 'Mandatory Reason', 'Created At']);
                LudoLedgerTransaction::orderBy('id')->chunk(200, function ($rows) use ($handle) {
                    foreach ($rows as $tx) {
                        fputcsv($handle, [$tx->tx_uid, $tx->user_id, $tx->type, $tx->points_delta, $tx->balance_before, $tx->balance_after, $tx->status, $tx->description, $tx->reason, $tx->created_at]);
                    }
                });
            } elseif ($type === 'disputes') {
                fputcsv($handle, ['Dispute UID', 'Battle ID', 'Opened By User ID', 'Against User ID', 'Category', 'Status', 'Decision Reason', 'Resolved At']);
                LudoDispute::orderBy('id')->chunk(200, function ($rows) use ($handle) {
                    foreach ($rows as $d) {
                        fputcsv($handle, [$d->dispute_uid, $d->battle_id, $d->opened_by_user_id, $d->against_user_id, $d->reason_category, $d->status, $d->admin_decision_reason, $d->resolved_at]);
                    }
                });
            } elseif ($type === 'referrals') {
                fputcsv($handle, ['ID', 'Referrer ID', 'Referred User ID', 'Referral Code', 'Status', 'Virtual Reward Points', 'Rewarded At']);
                LudoReferral::orderBy('id')->chunk(200, function ($rows) use ($handle) {
                    foreach ($rows as $r) {
                        fputcsv($handle, [$r->id, $r->referrer_id, $r->referred_user_id, $r->referral_code, $r->status, $r->reward_points, $r->rewarded_at]);
                    }
                });
            } elseif ($type === 'tickets') {
                fputcsv($handle, ['Ticket UID', 'User ID', 'Category', 'Priority', 'Status', 'Subject', 'Created At']);
                LudoSupportTicket::orderBy('id')->chunk(200, function ($rows) use ($handle) {
                    foreach ($rows as $t) {
                        fputcsv($handle, [$t->ticket_uid, $t->user_id, $t->category, $t->priority, $t->status, $t->subject, $t->created_at]);
                    }
                });
            } elseif ($type === 'audit_logs') {
                fputcsv($handle, ['ID', 'Admin ID', 'Action Type', 'Target Type', 'Target ID', 'Description', 'IP Address', 'Created At']);
                LudoAdminAuditLog::orderBy('id')->chunk(200, function ($rows) use ($handle) {
                    foreach ($rows as $log) {
                        fputcsv($handle, [$log->id, $log->admin_id, $log->action_type, $log->target_type, $log->target_id, $log->description, $log->ip_address, $log->created_at]);
                    }
                });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * PHASE 12 — Dedicated Administrative Audit Logs Explorer
     */
    public function auditLogs(Request $request)
    {
        $query = LudoAdminAuditLog::with('admin')->latest('id');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('action_type', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%")
                  ->orWhereHas('admin', function ($qa) use ($s) {
                      $qa->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                  });
            });
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->target_type);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $logs = $query->paginate(20)->withQueryString();

        $actionTypes = LudoAdminAuditLog::select('action_type')->distinct()->orderBy('action_type')->pluck('action_type');
        $targetTypes = LudoAdminAuditLog::select('target_type')->whereNotNull('target_type')->distinct()->orderBy('target_type')->pluck('target_type');

        return view('admin.ludo.audit.index', [
            'logs' => $logs,
            'actionTypes' => $actionTypes,
            'targetTypes' => $targetTypes,
            'activeSection' => 'audit',
        ]);
    }
}
