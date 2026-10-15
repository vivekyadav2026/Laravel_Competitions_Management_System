<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\IndividualController;
use App\Http\Controllers\IndividualEventScoreController;
use App\Http\Controllers\IndividualCompetitionScoreController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TeamCompetitionScoreController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamEventScoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LudoController;
use App\Http\Controllers\LudoAdminController;
use Illuminate\Support\Facades\Route;

// Public Ludo Tournament Platform Routes
Route::get('/', [LudoController::class, 'home'])->name('ludo.home');
Route::get('/play', [LudoController::class, 'battles'])->name('ludo.play');
Route::get('/battles', [LudoController::class, 'battles'])->name('ludo.battles');
Route::get('/battles/load-more', [LudoController::class, 'loadMoreBattles'])->name('ludo.battles.loadMore');
Route::get('/rules', [LudoController::class, 'rules'])->name('ludo.rules');
Route::get('/support', [LudoController::class, 'support'])->name('ludo.support');
Route::get('/tournaments', [LudoController::class, 'tournaments'])->name('ludo.tournaments');

// Authentication routes
Route::get('/showregister', [AuthController::class, 'showRegister'])->name('register');
Route::get('/sign-up', [AuthController::class, 'showRegister']);
Route::get('/register', [AuthController::class, 'showRegister']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/showlogin', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp'])->name('auth.otp.send');
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.otp.verify');
Route::post('/auth/resend-otp', [AuthController::class, 'resendOtp'])->name('auth.otp.resend');

Route::get('/admin', function () {
    if (auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin', 'support_staff', 'moderator'])) {
        return redirect()->route('admin.ludo.dashboard');
    }
    return redirect()->route('admin.login');
});
Route::get('/admin/ludo', function () {
    if (auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin', 'support_staff', 'moderator'])) {
        return redirect()->route('admin.ludo.dashboard');
    }
    return redirect()->route('admin.login');
});
Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
Route::get('/admin/ludo/login', [AuthController::class, 'showAdminLogin']);
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout']); // Fallback GET logout

// Protected Player Routes (Require Authentication)
Route::middleware('auth')->group(function () {
    // Battle Actions
    Route::post('/battles/create', [LudoController::class, 'createBattle'])->name('ludo.battles.create');
    Route::get('/battles-sync', [LudoController::class, 'syncState'])->name('ludo.battles.sync');
    Route::get('/battles/sync-state', [LudoController::class, 'syncState'])->name('ludo.battles.syncState');
    Route::get('/battles/{id}', [LudoController::class, 'viewBattle'])->name('ludo.battles.view')->whereNumber('id');
    Route::post('/battles/join/{id}', [LudoController::class, 'joinBattle'])->name('ludo.battles.join');
    Route::post('/battles/{id}/join', [LudoController::class, 'joinBattle']);
    Route::post('/battles/{id}/start', [LudoController::class, 'startBattle'])->name('ludo.battles.start');
    Route::post('/battles/{id}/room-code', [LudoController::class, 'setRoomCode'])->name('ludo.battles.roomCode');
    Route::post('/battles/{id}/room-code', [LudoController::class, 'setRoomCode'])->name('ludo.battles.room-code');
    Route::post('/battles/cancel/{id}', [LudoController::class, 'cancelBattle'])->name('ludo.battles.cancel');
    Route::post('/battles/{id}/cancel', [LudoController::class, 'cancelBattle']);
    Route::post('/battles/{id}/cancel-with-reason', [LudoController::class, 'cancelWithReason'])->name('ludo.battles.cancelReason');
    Route::post('/battles/{id}/cancel-with-reason', [LudoController::class, 'cancelWithReason'])->name('ludo.battles.cancel-with-reason');
    Route::post('/battles/{id}/result', [LudoController::class, 'submitBattleResult'])->name('ludo.battles.result');


    // Disputes
    Route::post('/battles/{id}/dispute', [LudoController::class, 'openDispute'])->name('ludo.dispute.open');
    Route::get('/disputes/{id}', [LudoController::class, 'viewDispute'])->name('ludo.dispute.view');
    Route::post('/disputes/{id}/evidence', [LudoController::class, 'submitDisputeEvidence'])->name('ludo.dispute.evidence');
    Route::get('/disputes/evidence/{id}/download', [LudoController::class, 'downloadDisputeEvidence'])->name('ludo.dispute.evidence.download');

    // Tournaments Participation
    Route::post('/tournaments/{id}/join', [LudoController::class, 'joinTournament'])->name('ludo.tournaments.join');

    // Refer & Earn
    Route::get('/refer', [LudoController::class, 'refer'])->name('ludo.refer');

    // Wallet & Points
    Route::get('/wallet', [LudoController::class, 'wallet'])->name('ludo.wallet');
    Route::get('/wallet/sync-state', [LudoController::class, 'walletSyncState'])->name('ludo.wallet.syncState');
    Route::post('/wallet/deposit', [LudoController::class, 'addVirtualPoints'])->name('ludo.wallet.deposit');
    Route::post('/wallet/deposit/upi', [LudoController::class, 'submitUpiDeposit'])->name('ludo.wallet.deposit.upi');
    Route::post('/wallet/withdraw', [LudoController::class, 'requestWithdrawal'])->name('ludo.wallet.withdraw');


    // Player Profile & Security
    Route::get('/profile', [LudoController::class, 'profile'])->name('ludo.profile');
    Route::post('/profile/update', [LudoController::class, 'updateProfile'])->name('ludo.profile.update');
    Route::post('/profile/password', [LudoController::class, 'updatePassword'])->name('ludo.profile.password');

    // History
    Route::get('/history', [LudoController::class, 'history'])->name('ludo.history');

    // KYC Verification & Document Access
    Route::get('/kyc', [LudoController::class, 'kyc'])->name('ludo.kyc');
    Route::post('/kyc/submit', [LudoController::class, 'submitKyc'])->name('ludo.kyc.submit');
    Route::get('/kyc/view/{id}/{side?}', [LudoController::class, 'viewKycDocument'])->name('ludo.kyc.view');
    Route::get('/kyc/download/{id}/{side?}', [LudoController::class, 'downloadKycDocument'])->name('ludo.kyc.download');

    // Support Tickets & Attachments
    Route::post('/support/ticket/create', [LudoController::class, 'createTicket'])->name('ludo.ticket.create');
    Route::get('/support/ticket/{id}', [LudoController::class, 'viewTicket'])->name('ludo.ticket.view');
    Route::post('/support/ticket/{id}/reply', [LudoController::class, 'replyTicket'])->name('ludo.ticket.reply');
    Route::get('/support/ticket/{id}/attachment', [LudoController::class, 'downloadTicketAttachment'])->name('ludo.ticket.attachment');

    // In-App Notifications
    Route::get('/notifications', [LudoController::class, 'notifications'])->name('ludo.notifications');
    Route::post('/notifications/read-all', [LudoController::class, 'markAllNotificationsRead'])->name('ludo.notifications.readAll');
    Route::post('/notifications/{id}/read', [LudoController::class, 'markNotificationRead'])->name('ludo.notifications.read');
});

// Admin Review Routes
Route::middleware(['auth', 'ludo.admin'])->group(function () {
    Route::post('/admin/kyc/{id}/review', [LudoController::class, 'adminReviewKyc'])->name('ludo.admin.kyc.review');
    Route::post('/admin/disputes/{id}/resolve', [LudoController::class, 'adminResolveDispute'])->name('ludo.admin.dispute.resolve');
});

// ==========================================
// FSALUDOCLUB ADMINISTRATION DASHBOARD ROUTES
// Protected by Auth and LudoAdminRoleMiddleware
// ==========================================
Route::middleware(['auth', 'ludo.admin'])->prefix('admin/ludo')->name('admin.ludo.')->group(function () {
    // 1. Overview Dashboard
    Route::get('/dashboard', [LudoAdminController::class, 'dashboard'])->name('dashboard');

    // 2. User Management
    Route::get('/users', [LudoAdminController::class, 'users'])->name('users.index');
    Route::get('/users/{id}', [LudoAdminController::class, 'userShow'])->name('users.show');
    Route::post('/users/{id}/profile', [LudoAdminController::class, 'userUpdateProfile'])->name('users.profile');
    Route::post('/users/{id}/status', [LudoAdminController::class, 'userToggleStatus'])->name('users.status');
    Route::post('/users/{id}/role', [LudoAdminController::class, 'userUpdateRole'])->name('users.role');
    Route::delete('/users/{id}', [LudoAdminController::class, 'userDestroy'])->name('users.destroy');

    // 3. Tournament Management
    Route::get('/tournaments', [LudoAdminController::class, 'tournaments'])->name('tournaments.index');
    Route::post('/tournaments', [LudoAdminController::class, 'tournamentStore'])->name('tournaments.store');
    Route::get('/tournaments/{id}', [LudoAdminController::class, 'tournamentShow'])->name('tournaments.show');
    Route::post('/tournaments/{id}/update', [LudoAdminController::class, 'tournamentUpdate'])->name('tournaments.update');
    Route::post('/tournaments/{id}/status', [LudoAdminController::class, 'tournamentUpdateStatus'])->name('tournaments.status');
    Route::post('/tournaments/{id}/close-registration', [LudoAdminController::class, 'tournamentCloseRegistration'])->name('tournaments.closeRegistration');
    Route::post('/tournaments/{id}/generate-bracket', [LudoAdminController::class, 'tournamentGenerateBracket'])->name('tournaments.bracket');

    // 4. Match & Result Management
    Route::get('/matches', [LudoAdminController::class, 'matches'])->name('matches.index');
    Route::get('/matches/live-feed', [LudoAdminController::class, 'matchesLiveFeed'])->name('matches.liveFeed');
    Route::get('/matches/{id}', [LudoAdminController::class, 'matchShow'])->name('matches.show');
    Route::post('/matches/{id}/cancel', [LudoAdminController::class, 'matchCancel'])->name('matches.cancel');
    Route::post('/matches/{id}/resolve', [LudoAdminController::class, 'matchResolveResult'])->name('matches.resolve');
    Route::post('/matches/{id}/refund/approve', [LudoAdminController::class, 'matchApproveRefund'])->name('matches.refund.approve');
    Route::post('/matches/{id}/refund/reject', [LudoAdminController::class, 'matchRejectRefund'])->name('matches.refund.reject');
    Route::get('/matches/{id}/evidence/{side}', [LudoAdminController::class, 'viewMatchEvidence'])->name('matches.evidence.view');

    // 5. Dispute Management
    Route::get('/disputes', [LudoAdminController::class, 'disputes'])->name('disputes.index');
    Route::get('/disputes/{id}', [LudoAdminController::class, 'disputeShow'])->name('disputes.show');

    // 6. Virtual Points Ledger & Adjustments
    Route::get('/ledger', [LudoAdminController::class, 'ledger'])->name('ledger.index');
    Route::post('/ledger/adjust', [LudoAdminController::class, 'ledgerAdjust'])->name('ledger.adjust');

    // 6.1 Direct UPI QR Deposits Review
    Route::get('/deposits', [LudoAdminController::class, 'upiDepositsIndex'])->name('deposits.index');
    Route::post('/deposits/{id}/approve', [LudoAdminController::class, 'approveUpiDeposit'])->name('deposits.approve');
    Route::post('/deposits/{id}/reject', [LudoAdminController::class, 'rejectUpiDeposit'])->name('deposits.reject');

    // 6.2 Player Withdrawals & Payout Settlement Desk
    Route::get('/withdrawals', [LudoAdminController::class, 'withdrawalsIndex'])->name('withdrawals.index');
    Route::post('/withdrawals/{id}/approve', [LudoAdminController::class, 'approveWithdrawal'])->name('withdrawals.approve');
    Route::post('/withdrawals/{id}/reject', [LudoAdminController::class, 'rejectWithdrawal'])->name('withdrawals.reject');

    // Real-Time Admin Notifications Live Feed
    Route::get('/notifications/live', [LudoAdminController::class, 'liveNotifications'])->name('notifications.live');
    Route::get('/sync-alerts', [LudoAdminController::class, 'liveNotifications'])->name('syncAlerts');
    Route::get('/deposits/live-feed', [LudoAdminController::class, 'upiDepositsLiveFeed'])->name('deposits.liveFeed');


    // 7. Referral Management
    Route::get('/referrals', [LudoAdminController::class, 'referrals'])->name('referrals.index');
    Route::post('/referrals/settings', [LudoAdminController::class, 'referralSettingsUpdate'])->name('referrals.settings');
    Route::post('/referrals/{id}/status', [LudoAdminController::class, 'referralUpdateStatus'])->name('referrals.status');

    // 8. Content Management (Announcements, Bilingual Rules, FAQ)
    Route::get('/content', [LudoAdminController::class, 'content'])->name('content.index');
    Route::post('/content', [LudoAdminController::class, 'contentUpdate'])->name('content.update');

    // 9. Platform Settings & Maintenance
    Route::get('/settings', [LudoAdminController::class, 'settings'])->name('settings.index');
    Route::get('/settings-overview', [LudoAdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [LudoAdminController::class, 'settingsUpdate'])->name('settings.update');

    // 10. Support Tickets Desk
    Route::get('/tickets', [LudoAdminController::class, 'tickets'])->name('tickets.index');
    Route::get('/tickets/{id}', [LudoAdminController::class, 'ticketShow'])->name('tickets.show');
    Route::post('/tickets/{id}/status', [LudoAdminController::class, 'ticketUpdateStatus'])->name('tickets.status');

    // 11. KYC Verifications Desk
    Route::get('/kyc', [LudoAdminController::class, 'kycIndex'])->name('kyc.index');

    // 12. Reports, CSV Exports & Audit Logs
    Route::get('/reports', [LudoAdminController::class, 'reports'])->name('reports.index');
    Route::get('/reports/export/{type}', [LudoAdminController::class, 'reportsExportCsv'])->name('reports.export');
    Route::get('/audit-logs', [LudoAdminController::class, 'auditLogs'])->name('audit.index');
});

// Legacy University Competitions home route
Route::get('/competitions-home', [UserController::class, 'showHome']);

// Authorize user for legacy competition routes
Route::middleware('auth')->group(function () {
    // competition details page
    Route::get('/competition-details/{id}', [CompetitionController::class, 'index']);
    // competition register
    Route::get('/competition-showregister/{id}', [CompetitionController::class, 'showRegister']);
    Route::post('/competition-register/{id}', [CompetitionController::class, 'competitionRegister']);

    // competition user rank
    Route::get('competition-rank/{id}', [CompetitionController::class, 'competitionRank']);

    // dashboard
    Route::middleware('admin')->group(function () {
        // admins
        Route::get('/dashboard/admins', [SuperAdminController::class, 'showAdmins'])->middleware('superadmin');
        Route::post('/dashboard/add-admin', [SuperAdminController::class, 'addAdmin'])->middleware('superadmin');
        Route::get('/dashboard/delete-admin/{id}', [SuperAdminController::class, 'deleteAdmin'])->middleware('superadmin');
        Route::put('/dashboard/update-admin/{id}', [SuperAdminController::class, 'updateAdmin'])->middleware('superadmin');
        // users
        Route::get('/dashboard/users', [UserController::class, 'showUsers']);
        Route::post('/dashboard/add-user', [UserController::class, 'addUser']);
        Route::get('/dashboard/delete-user/{id}', [UserController::class, 'deleteUser']);
        Route::put('/dashboard/update-user/{id}', [UserController::class, 'updateUser']);
        // competitions
        Route::get('/dashboard/competitions', [CompetitionController::class, 'showCompetitions']);
        Route::post('/dashboard/add-competition', [CompetitionController::class, 'addCompetition']);
        Route::get('/dashboard/delete-competition/{id}', [CompetitionController::class, 'deleteCompetition']);
        Route::put('/dashboard/update-competition/{id}', [CompetitionController::class, 'updateCompetition']);
        // events
        Route::get('/dashboard/events', [EventController::class, 'showEvents']);
        Route::post('/dashboard/add-event', [EventController::class, 'addEvent']);
        Route::get('/dashboard/delete-event/{id}', [EventController::class, 'deleteEvent']);
        Route::put('/dashboard/update-event/{id}', [EventController::class, 'updateEvent']);
        // teams
        Route::get('/dashboard/teams', [TeamController::class, 'showTeams']);
        Route::post('/dashboard/add-team', [TeamController::class, 'addTeam']);
        Route::get('/dashboard/delete-team/{id}', [TeamController::class, 'deleteTeam']);
        Route::put('/dashboard/update-team/{id}', [TeamController::class, 'updateTeam']);
        // teams events score
        Route::get('/dashboard/teams-events-score', [TeamEventScoreController::class, 'showTeamsEventScore']);
        Route::post('/dashboard/add-team-event-score', [TeamEventScoreController::class, 'addTeamEventScore']);
        Route::get('/dashboard/delete-team-event-score/{id}', [TeamEventScoreController::class, 'deleteTeamEventScore']);
        Route::put('/dashboard/update-team-event-score/{id}', [TeamEventScoreController::class, 'updateTeamEventScore']);
        // teams competitions score
        Route::get('/dashboard/teams-competitions-score', [TeamCompetitionScoreController::class, 'showTeamsCompetitionScore']);
        Route::post('/dashboard/add-team-competition-score', [TeamCompetitionScoreController::class, 'addTeamCompetitionScore']);
        Route::get('/dashboard/delete-team-competition-score/{id}', [TeamCompetitionScoreController::class, 'deleteTeamCompetitionScore']);
        Route::put('/dashboard/update-team-competition-score/{id}', [TeamCompetitionScoreController::class, 'updateTeamCompetitionScore']);
        // individuals
        Route::get('/dashboard/individuals', [IndividualController::class, 'showIndividuals']);
        Route::post('/dashboard/add-individual', [IndividualController::class, 'addIndividual']);
        Route::get('/dashboard/delete-individual/{id}', [IndividualController::class, 'deleteIndividual']);
        Route::put('/dashboard/update-individual/{id}', [IndividualController::class, 'updateIndividual']);
        // individuals events score
        Route::get('/dashboard/individuals-events-score', [IndividualEventScoreController::class, 'showIndividualsEventScore']);
        Route::post('/dashboard/add-individual-event-score', [IndividualEventScoreController::class, 'addIndividualEventScore']);
        Route::get('/dashboard/delete-individual-event-score/{id}', [IndividualEventScoreController::class, 'deleteIndividualEventScore']);
        Route::put('/dashboard/update-individual-event-score/{id}', [IndividualEventScoreController::class, 'updateIndividualEventScore']);
        // individuals competitions score
        Route::get('/dashboard/individuals-competitions-score', [IndividualCompetitionScoreController::class, 'showIndividualsCompetitionScore']);
        Route::post('/dashboard/add-individual-competition-score', [IndividualCompetitionScoreController::class, 'addIndividualsCompetitionScore']);
        Route::get('/dashboard/delete-individual-competition-score/{id}', [IndividualCompetitionScoreController::class, 'deleteIndividualsCompetitionScore']);
        Route::put('/dashboard/update-individual-competition-score/{id}', [IndividualCompetitionScoreController::class, 'updateIndividualsCompetitionScore']);
    });
});

if (app()->environment('local', 'testing')) {
    Route::get('/dev/login-as/{id}', function ($id) {
        $user = is_numeric($id) ? \App\Models\User::find($id) : \App\Models\User::where('email', $id)->first();
        if ($user) {
            \Illuminate\Support\Facades\Auth::login($user);
        }
        return redirect()->route('ludo.battles');
    });
}
