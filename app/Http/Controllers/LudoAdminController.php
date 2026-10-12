<?php

namespace App\Http\Controllers;

use App\Models\LudoAdminAuditLog;
use App\Models\LudoBattle;
use App\Models\LudoDispute;
use App\Models\LudoDisputeEvidence;
use App\Models\LudoKycSubmission;
use App\Models\LudoLedgerTransaction;
use App\Models\LudoSetting;
use App\Models\LudoSupportTicket;
use App\Models\LudoTournament;
use App\Models\LudoTournamentParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LudoAdminController extends Controller
{
    /**
     * Admin Overview Dashboard with Real Database Metrics
     */
    public function dashboard()
    {
        // Real DB metrics
        $registeredUsersCount = User::count();
        $activeUsersCount = User::where('status', 'active')->count();
        $suspendedUsersCount = User::where('status', 'suspended')->count();
        $tournamentsCount = LudoTournament::count();
        $activeMatchesCount = LudoBattle::where('status', 'running')->count();
        $completedMatchesCount = LudoBattle::where('status', 'completed')->count();
        $pendingResultsCount = LudoBattle::where('review_status', 'under_review')->count();
        $openDisputesCount = LudoDispute::whereIn('status', ['open', 'under_review', 'more_evidence_requested'])->count();
        $supportTicketsCount = LudoSupportTicket::whereIn('status', ['open', 'in_progress'])->count();
        $virtualPointsIssued = (int) LudoLedgerTransaction::where('type', 'deposit')->where('status', 'successful')->sum('points_delta');

        // Recent Audit Activity
        $recentAuditLogs = LudoAdminAuditLog::with('admin')
            ->latest('id')
            ->take(8)
            ->get();

        // Recent Urgent Disputes & Tickets
        $recentDisputes = LudoDispute::with(['openedBy', 'againstUser', 'battle'])
            ->latest('id')
            ->take(5)
            ->get();

        return view('admin.ludo.dashboard', [
            'metrics' => [
                'registered_users' => $registeredUsersCount,
                'active_users' => $activeUsersCount,
                'suspended_users' => $suspendedUsersCount,
                'tournaments' => $tournamentsCount,
                'active_matches' => $activeMatchesCount,
                'completed_matches' => $completedMatchesCount,
                'pending_results' => $pendingResultsCount,
                'open_disputes' => $openDisputesCount,
                'support_tickets' => $supportTicketsCount,
                'points_issued' => $virtualPointsIssued,
            ],
            'recentAuditLogs' => $recentAuditLogs,
            'recentDisputes' => $recentDisputes,
            'activeSection' => 'dashboard',
        ]);
    }

    /**
     * User Management: List, Search, Filter
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
                  ->orWhere('phone', 'like', "%{$s}%");
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
     * View User Dossier (Profile, Matches, Disputes, KYC, Ledger)
     */
    public function userShow($id)
    {
        $user = User::with(['kycSubmissions'])->findOrFail($id);

        $battles = LudoBattle::where('creator_id', $user->id)
            ->orWhere('joiner_id', $user->id)
            ->latest('id')
            ->paginate(6, ['*'], 'battles_page');

        $disputes = LudoDispute::where('opened_by_user_id', $user->id)
            ->orWhere('against_user_id', $user->id)
            ->latest('id')
            ->get();

        $transactions = LudoLedgerTransaction::where('user_id', $user->id)
            ->latest('id')
            ->take(10)
            ->get();

        $tickets = LudoSupportTicket::where('user_id', $user->id)
            ->latest('id')
            ->take(5)
            ->get();

        return view('admin.ludo.users.show', [
            'user' => $user,
            'battles' => $battles,
            'disputes' => $disputes,
            'transactions' => $transactions,
            'tickets' => $tickets,
            'activeSection' => 'users',
        ]);
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
     * Update User Role (Permissions management)
     */
    public function userUpdateRole(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);

        $validated = $request->validate([
            'role' => ['required', 'in:normal,admin,superadmin,support_staff,moderator'],
        ]);

        $oldRole = $targetUser->role;
        $targetUser->update(['role' => $validated['role']]);

        LudoAdminAuditLog::log('role_updated', "Changed role of {$targetUser->name} from {$oldRole} to {$validated['role']}", 'User', $targetUser->id);

        return redirect()->back()->with('success', "User role updated to {$validated['role']}.");
    }

    /**
     * Tournament Management: List Tournaments
     */
    public function tournaments(Request $request)
    {
        $query = LudoTournament::with(['creator', 'winner'])->withCount('participants')->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where('title', 'like', "%{$s}%")->orWhere('tournament_uid', 'like', "%{$s}%");
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
            'registration_closes_at' => $validated['registration_closes_at'] ?? now()->addDays(2),
            'rules_text' => $validated['rules_text'] ?? null,
            'created_by_admin_id' => Auth::id(),
        ]);

        LudoAdminAuditLog::log('tournament_created', "Created tournament '{$tournament->title}' ({$tournament->tournament_uid})", 'LudoTournament', $tournament->id);

        return redirect()->route('admin.ludo.tournaments.show', $tournament->id)->with('success', "Tournament created in draft mode.");
    }

    /**
     * Show Tournament & Bracket Workbench
     */
    public function tournamentShow($id)
    {
        $tournament = LudoTournament::with(['creator', 'winner', 'participants.user'])->findOrFail($id);

        return view('admin.ludo.tournaments.show', [
            'tournament' => $tournament,
            'activeSection' => 'tournaments',
        ]);
    }

    /**
     * Change Tournament Status (Publish / Cancel / Complete)
     */
    public function tournamentUpdateStatus(Request $request, $id)
    {
        $tournament = LudoTournament::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,published,running,completed,cancelled'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $oldStatus = $tournament->status;
        $tournament->update(['status' => $validated['status']]);

        if ($validated['status'] === 'completed') {
            $tournament->update(['completed_at' => now()]);
        }

        LudoAdminAuditLog::log('tournament_status', "Changed status of tournament '{$tournament->title}' from {$oldStatus} to {$validated['status']}", 'LudoTournament', $tournament->id);

        return redirect()->back()->with('success', "Tournament status transitioned to {$validated['status']}.");
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

        $bracket = $tournament->generateBracket();

        LudoAdminAuditLog::log('tournament_bracket', "Generated single elimination bracket for '{$tournament->title}'", 'LudoTournament', $tournament->id);

        return redirect()->back()->with('success', 'Tournament bracket generated successfully.');
    }

    /**
     * Match Management: Search, View, Cancel with Reason
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
                      $qc->where('name', 'like', "%{$s}%");
                  })
                  ->orWhereHas('joiner', function ($qj) use ($s) {
                      $qj->where('name', 'like', "%{$s}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('review_status')) {
            $query->where('review_status', $request->review_status);
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

        return view('admin.ludo.matches.show', [
            'match' => $match,
            'activeSection' => 'matches',
        ]);
    }

    /**
     * Cancel Match Administratively & Refund Both
     */
    public function matchCancel(Request $request, $id)
    {
        $match = LudoBattle::with(['creator', 'joiner'])->findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'reason.required' => 'A mandatory reason is required to cancel a match.',
        ]);

        if ($match->status === 'cancelled') {
            return redirect()->back()->with('error', 'This match has already been cancelled.');
        }

        // Refund creator
        LudoLedgerTransaction::recordAdjustment(
            $match->creator,
            $match->entry_fee,
            'refund',
            "Admin match cancellation for {$match->battle_uid}: {$validated['reason']}",
            'successful'
        );

        // Refund joiner if joined
        if ($match->joiner) {
            LudoLedgerTransaction::recordAdjustment(
                $match->joiner,
                $match->entry_fee,
                'refund',
                "Admin match cancellation for {$match->battle_uid}: {$validated['reason']}",
                'successful'
            );
        }

        $match->update([
            'status' => 'cancelled',
            'final_result' => 'cancelled',
            'review_status' => 'auto_approved',
            'completed_at' => now(),
        ]);

        LudoAdminAuditLog::log('match_cancelled', "Administratively cancelled match {$match->battle_uid}. Reason: {$validated['reason']}", 'LudoBattle', $match->id);

        return redirect()->back()->with('success', "Match {$match->battle_uid} cancelled and entry fees refunded to players.");
    }

    /**
     * Dispute Management: List and Filter
     */
    public function disputes(Request $request)
    {
        $query = LudoDispute::with(['battle', 'openedBy', 'againstUser', 'resolver'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where('dispute_uid', 'like', "%{$s}%")
                  ->orWhereHas('battle', function ($qb) use ($s) {
                      $qb->where('battle_uid', 'like', "%{$s}%");
                  });
        }

        $disputes = $query->paginate(10)->withQueryString();

        return view('admin.ludo.disputes.index', [
            'disputes' => $disputes,
            'activeSection' => 'disputes',
        ]);
    }

    /**
     * Virtual Points Ledger Inspection & Manual Adjustments
     */
    public function ledger(Request $request)
    {
        $query = LudoLedgerTransaction::with('user')->latest('id');

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where('tx_uid', 'like', "%{$s}%")
                  ->orWhereHas('user', function ($qu) use ($s) {
                      $qu->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                  });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->paginate(15)->withQueryString();

        return view('admin.ludo.ledger.index', [
            'transactions' => $transactions,
            'activeSection' => 'ledger',
        ]);
    }

    /**
     * Perform Authorized Ledger Adjustment
     */
    public function ledgerAdjust(Request $request)
    {
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
     * Content Management: Announcements, Rules in Hindi & English, Settings
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
     * System Settings: Platform branding, deadlines, limits, maintenance
     */
    public function settings()
    {
        $settings = [
            'platform_name' => LudoSetting::get('platform_name', 'Fsaludoclub'),
            'submission_window' => LudoSetting::get('submission_window', config('ludo.battle.submission_window_minutes', 15)),
            'referral_commission' => LudoSetting::get('referral_commission', '3%'),
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
        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:100'],
            'submission_window' => ['required', 'integer', 'min:5', 'max:120'],
            'referral_commission' => ['required', 'string', 'max:20'],
            'maintenance_mode' => ['required', 'in:on,off'],
            'support_email' => ['required', 'email'],
            'support_whatsapp' => ['nullable', 'string', 'max:30'],
            'support_telegram' => ['nullable', 'string', 'max:255'],
            'support_hours' => ['nullable', 'string', 'max:100'],
            'minimum_battle_amount' => ['required', 'integer', 'min:10', 'max:1000'],
            'kyc_mandatory' => ['required', 'in:on,off'],
        ]);

        foreach ($validated as $k => $v) {
            LudoSetting::set($k, $v, 'system');
        }

        LudoAdminAuditLog::log('settings_updated', "Updated platform system configurations and policy limits.");

        return redirect()->back()->with('success', 'System configurations updated.');
    }

    /**
     * Support Tickets Management Desk
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

        return view('admin.ludo.tickets.index', [
            'tickets' => $tickets,
            'activeSection' => 'tickets',
        ]);
    }

    /**
     * KYC Verifications Management Desk
     */
    public function kycIndex(Request $request)
    {
        $query = LudoKycSubmission::with(['user'])->latest('id');

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

        return view('admin.ludo.kyc.index', [
            'submissions' => $submissions,
            'activeSection' => 'kyc',
        ]);
    }
}
