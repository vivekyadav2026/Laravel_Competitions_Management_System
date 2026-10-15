# FSALUDOCLUB — ADMIN DYNAMIC DATA IMPLEMENTATION

**Project:** Fsaludoclub Ludo Platform  
**Architecture:** Laravel 10 / PHP 8.2 / MySQL 8.0 / Bootstrap 5 / Vanilla JS  
**Status:** 100% Dynamic MySQL Integration Verified  

---

## 1. System Overview & Architectural Principles

The Fsaludoclub Admin Panel is engineered to provide real-time operational oversight and governance without any hardcoded mock data, fabricated numbers, or static placeholders. Every counter, metric card, activity stream, and data table derives its state strictly from ACID-compliant relational transactions in MySQL.

### Core Architecture Highlights:
1. **Zero-State Integrity:** Whenever a query yields no matching rows, the interface renders semantic empty state components rather than fallback placeholders. Count metrics strictly evaluate to `0` instead of mock defaults.
2. **Double-Entry Ledger Invariance:** All virtual-point movements (entry fees, prize distributions, refunds, admin adjustments, deposits) must flow through `LudoLedgerTransaction` with atomic row-locking (`lockForUpdate()`) to prevent race conditions.
3. **Optimized Eager Loading:** Queries traversing relationships employ targeted eager loading (`with(['creator', 'joiner', 'user'])`) and select specific foreign keys to eliminate \(N+1\) performance degradation across large datasets.
4. **Temporal Scoping & Telemetry:** Dashboard queries utilize Carbon date range filters (`all`, `today`, `7d`, `30d`, `custom`) passed via indexed columns (`created_at`).

---

## 2. Module Implementations & Technical Specifications

### Module 1: Admin Overview Dashboard
- **Controller:** `App\Http\Controllers\LudoAdminController::dashboard()`
- **Route:** `GET /admin/dashboard` (`admin.ludo.dashboard`)
- **Blade View:** `resources/views/admin/ludo/dashboard.blade.php`
- **Dynamic Queries Executed:**
  ```php
  // Live User Telemetry
  $registeredUsersCount = $applyDate(User::query())->count();
  $activeUsersCount     = $applyDate(User::where('status', 'active'))->count();
  $suspendedUsersCount  = $applyDate(User::where('status', 'suspended'))->count();
  $verifiedUsersCount   = $applyDate(User::where('kyc_status', 'verified'))->count();

  // Match & Battle Telemetry
  $openBattlesCount      = $applyDate(LudoBattle::where('status', 'open'))->count();
  $activeMatchesCount    = $applyDate(LudoBattle::where('status', 'running'))->count();
  $completedMatchesCount = $applyDate(LudoBattle::where('status', 'completed'))->count();
  $cancelledMatchesCount = $applyDate(LudoBattle::where('status', 'cancelled'))->count();
  $pendingResultsCount   = $applyDate(LudoBattle::where('review_status', 'under_review'))->count();

  // Direct UPI QR Payment Requests
  $pendingDepositsCount        = $applyDate(LudoUpiDeposit::where('status', 'pending'))->count();
  $approvedDepositsCount       = $applyDate(LudoUpiDeposit::where('status', 'approved'))->count();
  $rejectedDepositsCount       = $applyDate(LudoUpiDeposit::where('status', 'rejected'))->count();
  $totalDepositsApprovedAmount = (int) $applyDate(LudoUpiDeposit::where('status', 'approved'))->sum('amount');

  // Ledger Balances & Prize Pools
  $circulatingPoints   = (int) User::sum('coins');
  $virtualPointsIssued = (int) $applyDate(LudoLedgerTransaction::where('type', 'deposit')->where('status', 'successful'))->sum('points_delta');
  $virtualPrizesPaid   = (int) $applyDate(LudoLedgerTransaction::where('type', 'prize_payout')->where('status', 'successful'))->sum('points_delta');
  $virtualRefundsTotal = (int) $applyDate(LudoLedgerTransaction::where('type', 'refund')->where('status', 'successful'))->sum('points_delta');
  ```
- **Real Activity Streams:**
  - `recentAuditLogs`: 8 latest administrative actions from `ludo_admin_audit_logs`.
  - `recentDisputes`: 5 latest open disputes from `ludo_disputes`.
  - `recentUsers`: 5 latest registrations from `users`.
  - `recentMatches`: 5 latest matches with creator/joiner from `ludo_battles`.
  - `recentDeposits`: 5 latest UPI QR deposit requests with user relations from `ludo_upi_deposits`.

---

### Module 2: User Management & Player Dossiers
- **Controller:** `App\Http\Controllers\LudoAdminController::users()` & `userShow()`
- **Route:** `GET /admin/users`, `GET /admin/users/{id}`
- **Blade View:** `resources/views/admin/ludo/users/index.blade.php`, `show.blade.php`
- **Dynamic Queries:**
  - Paginated player records (`User::paginate(15)`).
  - Multi-field keyword search across `name`, `email`, `player_uid`, `phone`, and `referral_code`.
  - Filtering by `role` (`user`, `admin`, `superadmin`), `status` (`active`, `suspended`), and `kyc_status` (`unverified`, `pending`, `verified`, `rejected`).
  - Full player dossier aggregates: total matches played, wins, losses, win rate calculation, total coins, referral balance, recent transactions, disputes, and uploaded KYC documents.
  - Safe administrative adjustments: balance credits/debits via atomic ledger adjustment, account suspension/reactivation, and phone/name corrections with audit log trail.

---

### Module 3: Match & Battle Operations Desk
- **Controller:** `App\Http\Controllers\LudoAdminController::matches()` & `matchShow()`
- **Route:** `GET /admin/matches`, `GET /admin/matches/{id}`
- **Blade View:** `resources/views/admin/ludo/matches/index.blade.php`, `show.blade.php`
- **Dynamic Queries:**
  - Filter by lifecycle status: `open`, `requested`, `running`, `completed`, `cancelled`.
  - Audit filter for matches requiring administrator review (`review_status = 'under_review'`), identifying 15-minute submission timeouts where one or both players failed to submit screenshots.
  - Administrative match cancellation with automated dual-player point refund and audit log recording.
  - Manual winner declaration with prize distribution and commission deduction.

---

### Module 4: Tournament Manager & Automated Bracket Trees
- **Controller:** `App\Http\Controllers\LudoAdminController::tournaments()`, `tournamentStore()`, `tournamentBracket()`
- **Route:** `GET /admin/tournaments`, `POST /admin/tournaments`, `GET /admin/tournaments/{id}/bracket`
- **Blade View:** `resources/views/admin/ludo/tournaments/index.blade.php`, `bracket.blade.php`
- **Dynamic Implementation:**
  - Dynamic tournament configuration: entry points, prize pool, max players (4, 8, 16, 32, 64), scheduled start time.
  - Single-elimination dynamic bracket generation powered by real registered participants in `ludo_tournament_participants`.
  - Real-time seed assignment and progression through quarter-finals, semi-finals, and finals.

---

### Module 5: Virtual Points Ledger & Financial Audit
- **Controller:** `App\Http\Controllers\LudoAdminController::ledgerIndex()`
- **Route:** `GET /admin/ledger`
- **Blade View:** `resources/views/admin/ludo/ledger/index.blade.php`
- **Dynamic Implementation:**
  - Audited double-entry ledger stream with pagination.
  - Type filters: `deposit`, `entry_fee`, `prize_payout`, `refund`, `admin_credit`, `admin_debit`, `referral_bonus`.
  - Aggregates calculate circulating supply, lifetime deposits, lifetime prize distributions, and refund totals.
  - Concurrency safety: all adjustments invoke `LudoLedgerTransaction::recordAdjustment()`.

---

### Module 6: Direct UPI QR Payment Desk
- **Controller:** `App\Http\Controllers\LudoAdminController::upiDepositsIndex()`, `upiDepositApprove()`, `upiDepositReject()`
- **Route:** `GET /admin/deposits`, `POST /admin/deposits/{id}/approve`, `POST /admin/deposits/{id}/reject`
- **Blade View:** `resources/views/admin/ludo/deposits/index.blade.php`
- **Dynamic Implementation:**
  - Searches by UTR reference number or player UID.
  - Displays submitted payment proof screenshot via secure image modal.
  - Approval flow: Atomic database transaction locks user record, increments `coins` by deposit amount, records a `successful` ledger transaction, transitions deposit status to `approved`, and dispatches an in-app notification to the player.
  - Rejection flow: Requires administrative rejection reason, marks status as `rejected`, and dispatches an alert notification with the rejection rationale.

---

### Module 7: KYC Verification & Compliance Desk
- **Controller:** `App\Http\Controllers\LudoAdminController::kycIndex()`, `kycApprove()`, `kycReject()`
- **Route:** `GET /admin/kyc`, `POST /admin/kyc/{id}/approve`, `POST /admin/kyc/{id}/reject`
- **Blade View:** `resources/views/admin/ludo/kyc/index.blade.php`
- **Dynamic Implementation:**
  - Displays pending Aadhaar / PAN card submissions with document number masks.
  - Streams encrypted document images via secure authenticated admin controller route `admin.ludo.kyc.document`.
  - Approval updates `users.kyc_status = 'verified'` and timestamp.
  - Rejection updates `users.kyc_status = 'rejected'` and records admin notes.

---

### Module 8: Disputes & Evidence Adjudication
- **Controller:** `App\Http\Controllers\LudoAdminController::disputesIndex()`, `disputeShow()`, `disputeResolve()`
- **Route:** `GET /admin/disputes`, `GET /admin/disputes/{id}`, `POST /admin/disputes/{id}/resolve`
- **Blade View:** `resources/views/admin/ludo/disputes/index.blade.php`, `show.blade.php`
- **Dynamic Implementation:**
  - Indexes all player contest claims (`opened_by_user_id`, `against_user_id`, `battle_id`).
  - View screen renders submitted winning screenshots and chat timestamps from both parties.
  - Resolution actions:
    1. Declare Player 1 Winner: Allocates prize points to P1, records commission, closes dispute.
    2. Declare Player 2 Winner: Allocates prize points to P2, records commission, closes dispute.
    3. Cancel Battle & Refund Both: Refunds entry points to both players, marks battle cancelled.

---

### Module 9: Support Tickets & In-App Customer Service
- **Controller:** `App\Http\Controllers\LudoAdminController::ticketsIndex()`, `ticketShow()`, `ticketReply()`, `ticketClose()`
- **Route:** `GET /admin/tickets`, `GET /admin/tickets/{id}`, `POST /admin/tickets/{id}/reply`
- **Blade View:** `resources/views/admin/ludo/tickets/index.blade.php`, `show.blade.php`
- **Dynamic Implementation:**
  - Threaded conversation UI between administrators and players.
  - Category filters: `deposit_issue`, `gameplay_bug`, `kyc_query`, `general_inquiry`.
  - Priority management: `low`, `medium`, `high`, `urgent`.
  - Replies dispatch real-time in-app notifications to the ticket submitter.

---

### Module 10: Referral Management
- **Controller:** `App\Http\Controllers\LudoAdminController::referralsIndex()`
- **Route:** `GET /admin/referrals`
- **Blade View:** `resources/views/admin/ludo/referrals/index.blade.php`
- **Dynamic Implementation:**
  - Tracks referrer (`referrer_id`) and referred player (`referred_user_id`).
  - Monitors referral eligibility conditions (registration vs. first match played).
  - Calculates awarded referral points and eligible payout queues.

---

### Module 11: Commission Rates & Platform Settings
- **Controller:** `App\Http\Controllers\LudoAdminController::settingsIndex()`, `settingsUpdate()`
- **Route:** `GET /admin/settings`, `POST /admin/settings`
- **Blade View:** `resources/views/admin/ludo/settings/index.blade.php`
- **Dynamic Implementation:**
  - Persisted in key-value store `ludo_settings`.
  - Manages `admin_commission_percent` (e.g. 5% platform fee), `minimum_battle_amount` (e.g. 50 points), `submission_window_minutes` (e.g. 15 minutes), `upi_deposits_enabled` (`on`/`off`), and `upi_merchant_id`.
  - Validates ranges strictly (e.g. commission 0% - 25%).
  - Logs every setting modification in `ludo_admin_audit_logs`.

---

### Module 12: Audit Trail & Data Export
- **Controller:** `App\Http\Controllers\LudoAdminController::auditLogs()`, `reportsIndex()`, `exportCsv()`
- **Route:** `GET /admin/audit-logs`, `GET /admin/reports`, `GET /admin/reports/export`
- **Blade View:** `resources/views/admin/ludo/audit_logs/index.blade.php`, `reports/index.blade.php`
- **Dynamic Implementation:**
  - Complete history of administrative interventions with admin ID, action type, IP address, user-agent, and JSON change payload.
  - Streaming CSV export engine: exports users, battles, ledger transactions, and deposits directly from MySQL cursors with chunking (1,000 rows/chunk) to prevent memory exhaustion on large datasets.
