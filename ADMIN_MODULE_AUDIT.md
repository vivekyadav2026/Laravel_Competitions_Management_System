# FSALUDOCLUB — ADMIN PANEL MODULE AUDIT (`ADMIN_MODULE_AUDIT.md`)

**Date:** 2026-10-13  
**Framework:** Laravel 8.x / PHP 8.x  
**Application:** Fsaludoclub Ludo Tournament & Battle Platform  

---

## 1. Executive Summary

An exhaustive technical audit was conducted across the existing Fsaludoclub Admin Panel (`LudoAdminController`, `LudoController`, `AuthController`, `LudoAdminRoleMiddleware`, `routes/web.php`, Eloquent models, migrations, and `resources/views/admin/ludo/*`).

While the foundational admin layout (`resources/views/layouts/admin.blade.php`) and core listings exist and query real database tables, several modules are incomplete, lack full CRUD or state-transition operations, miss dedicated admin workbench views, or lack dedicated modules altogether (such as **Referral Management**, **Reports & CSV Exports**, and a dedicated **Audit Logs Explorer**).

---

## 2. Module-by-Module Audit

### Module 1: Admin Authentication & Role Authorization
- **Existing Functionality:**
  - Dedicated Admin Login at `GET /admin/login` and `POST /admin/login` (`AuthController@showAdminLogin`, `AuthController@adminLogin`).
  - Route protection via `auth` and `ludo.admin` (`LudoAdminRoleMiddleware`).
- **Missing Functionality / Security Issues Found:**
  1. **Sub-role Permissions:** `LudoAdminRoleMiddleware` supports `support_staff` and `moderator` only when a `$permissionGroup` parameter is passed, but `routes/web.php` applies `ludo.admin` without granular checks, and `LudoController` hardcodes `in_array($user->role, ['admin', 'superadmin'])` for tickets, disputes, and KYC downloads.
  2. **Role Escalation Risk:** In `LudoAdminController@userUpdateRole`, any admin could assign `superadmin` or modify their own role without checking whether the acting administrator is a `superadmin` or attempting self-demotion.
- **Required Repairs:**
  - Enforce strict role-hierarchy checks in `userUpdateRole` and sensitive system/ledger actions.
  - Allow authorized staff roles (`admin`, `superadmin`, `support_staff`, `moderator`) appropriate access while restricting role assignment and system configuration changes to `admin`/`superadmin`.

---

### Module 2: Admin Overview Dashboard (`/admin/ludo/dashboard`)
- **Existing Functionality:**
  - Displays 8 top-level database counters, 5 recent disputes, and 8 recent audit logs.
- **Missing Functionality:**
  1. Missing breakdown of **Verified vs. Unverified KYC accounts**.
  2. Missing breakdown of **Tournaments** by status (`published`/upcoming, `running`, `completed`, `cancelled`, `draft`).
  3. Missing breakdown of **Open Battles** vs. **Running Battles** vs. **Cancelled Matches**.
  4. Missing **Referral Statistics** (total referrals, eligible referrals, total virtual bonus points awarded).
  5. Missing **Total Circulating Virtual Points Balance** across all players and net ledger credits/debits.
  6. Missing **Date-Range Filter** (`all`, `today`, `7d`, `30d`, or custom date range) for period-based operational metrics.
  7. Missing recent player registrations and recent match activity feeds.
- **Player-Side Dependencies:**
  - Reflects real-time player registrations, KYC submissions, battle creation/completion, dispute openings, and support tickets.

---

### Module 3: User Management (`/admin/ludo/users`)
- **Existing Functionality:**
  - Paginated user listing with search (`name`, `email`, `player_uid`, `phone`) and filters (`status`, `role`, `kyc_status`).
  - User Dossier (`GET /admin/ludo/users/{id}`) displaying profile summary, paginated battles, disputes, recent ledger transactions, KYC submissions, and support tickets.
  - Account suspension/reactivation (`POST /admin/ludo/users/{id}/status`) with mandatory reason and audit logging.
  - Role update (`POST /admin/ludo/users/{id}/role`) with audit logging.
- **Missing Functionality:**
  1. **Permitted Profile Corrections:** Admins cannot edit a user's display name, email, or phone number when assisting with account recovery or profile corrections.
  2. **Match Statistics Summary:** User Dossier does not display calculated match statistics (`total`, `won`, `lost`, `win_rate`, `points_earned`).
  3. **Referral Dossier Section:** User Dossier does not show who referred the user (`referrer`) or the list of players referred by this user (`referrals`).
  4. **Role Escalation Guard:** Prevent non-superadmins from granting `superadmin` or modifying existing `superadmin` accounts, and prevent self-role-demotion.
- **Player-Side Dependencies:**
  - Suspended users are immediately blocked from logging in (`AuthController`) or accessing protected routes (`LudoAdminRoleMiddleware`).
  - Profile corrections immediately reflect on player `/profile` and battle cards.

---

### Module 4: Tournament Management (`/admin/ludo/tournaments`)
- **Existing Functionality:**
  - Paginated tournament listing with search and status filter.
  - Create tournament (`POST /admin/ludo/tournaments`) in `draft` mode.
  - Tournament Workbench (`GET /admin/ludo/tournaments/{id}`) showing participants and single-elimination Round 1 bracket.
  - Status update (`POST /admin/ludo/tournaments/{id}/status`) and bracket generation (`POST /admin/ludo/tournaments/{id}/generate-bracket`).
- **Missing Functionality:**
  1. **Edit Tournament Details (`PUT/POST /admin/ludo/tournaments/{id}`):** No route, controller method, or UI form exists to edit an existing tournament's title, description, entry fee, prize pool, capacity, schedule (`starts_at`, `registration_closes_at`), or rules text.
  2. **Cancellation Refunds & Reason:** Transitioning a tournament to `cancelled` does not require a mandatory reason and does not refund paid `entry_fee` virtual points to registered participants!
  3. **Invalid Status Transitions & Winner Settlement:** Completed or cancelled tournaments are not guarded against invalid status reversals, and completing a tournament does not allow designating the tournament winner (`winner_user_id`) and crediting the tournament `prize_pool` via the authoritative ledger.
  4. **Close Registrations Action:** Missing one-click/explicit registration closure (`registration_closes_at = now()`) and bracket match winner recording.
- **Player-Side Dependencies:**
  - Published/running/completed tournaments and their participant counts, brackets, and statuses render directly on player `/tournaments` (`resources/views/ludo/tournaments.blade.php`).

---

### Module 5: Battle & Match Management (`/admin/ludo/matches`)
- **Existing Functionality:**
  - Paginated match listing with search (`battle_uid`, `room_code`, creator/joiner name) and filters (`status`, `review_status`).
  - Match Audit Dossier (`GET /admin/ludo/matches/{id}`).
  - Administrative match cancellation (`POST /admin/ludo/matches/{id}/cancel`) with mandatory reason and entry fee refunds to both participants.
- **Missing Functionality:**
  1. **Direct Result Review & Settlement (`POST /admin/ludo/matches/{id}/resolve`):** Matches flagged as `under_review` (e.g., expired 15-minute result submission timer) or `running` with submitted claims cannot be settled (award creator, award joiner, or verify) directly from the Match Audit Dossier unless a dispute was separately opened.
  2. **Date Range Filtering:** Match listing lacks `from` and `to` date filters.
  3. **Idempotency / Concurrency Lock:** `matchCancel` and result settlement must lock the `LudoBattle` row inside a DB transaction to prevent duplicate refunds or duplicate prize payouts on Completed/Cancelled matches.
- **Player-Side Dependencies:**
  - Match cancellations and result settlements immediately update player wallet balances (`/wallet`), match history (`/history`), and in-app notifications (`/notifications`).

---

### Module 6: Virtual-Points Management (`/admin/ludo/ledger`)
- **Existing Functionality:**
  - Paginated transaction ledger (`LudoLedgerTransaction`) with search (`tx_uid`, user name/email), `type`, and `status` filters.
  - Row-locked atomic balance adjustments (`LudoLedgerTransaction::recordAdjustment`) preventing negative balances and requiring a mandatory reason.
- **Missing Functionality:**
  1. **Ledger Overview & Balance Summary Metrics:** The `/admin/ludo/ledger` page lacks summary cards showing total circulating player balances, total virtual points credited, total battle entry fees, total prizes awarded, total refunds, total referral rewards, and net admin adjustments.
  2. **Standalone Adjustment Form on Ledger Desk:** Admins can only trigger `ledgerAdjust` from a specific user's dossier (`users/show.blade.php`), not directly from `/admin/ludo/ledger`.
  3. **Date Range Filters:** Missing `from` and `to` date filters on `/admin/ludo/ledger`.
- **Player-Side Dependencies:**
  - Every adjustment updates `users.coins` atomically and appears in the player's `/wallet` and `/history` transaction ledger.

---

### Module 7: Result Review & Dispute Management (`/admin/ludo/disputes`)
- **Existing Functionality:**
  - Paginated disputes listing (`GET /admin/ludo/disputes`) with search and status filter.
  - Dispute resolution handler (`POST /admin/disputes/{id}/resolve` -> `LudoController@adminResolveDispute`) supporting `award_creator`, `award_joiner`, `refund_both`, `request_evidence`, and `reject_dispute`.
- **Missing Functionality:**
  1. **Dedicated Admin Dispute Dossier View (`GET /admin/ludo/disputes/{id}`):** Clicking "Dossier" in the Admin Dispute Desk currently sends the admin to the player-facing mobile layout (`/disputes/{id}`) instead of a comprehensive Admin Console Dispute Workbench (`resources/views/admin/ludo/disputes/show.blade.php`).
  2. **Finalized Dispute Guard:** `adminResolveDispute` does not block re-resolving an already `resolved` dispute, which could cause duplicate prize payouts or refunds if submitted twice!
- **Player-Side Dependencies:**
  - Dispute status, admin decision rationale, wallet payouts/refunds, and in-app notifications synchronize with `/disputes/{id}`, `/wallet`, and `/history`.

---

### Module 8: Referral Management (`/admin/ludo/referrals`)
- **Existing Functionality:**
  - `LudoReferral` model and `ludo_referrals` table exist; registration referral attribution in `AuthController@register` and player `/refer` view exist.
- **Missing Functionality:**
  1. **No Admin Referral Module Exists:** There is no route, controller action, or Blade view in the Admin Panel to inspect referrals, filter by status (`pending`, `eligible`, `rewarded`, `revoked`), detect duplicate/self referrals, or manage referral rewards.
  2. **Hardcoded Registration Referral Bonus:** `AuthController@register` hardcodes `50` virtual points instead of reading the admin-configured `referral_reward_points` setting and checking `referral_enabled`.
- **Player-Side Dependencies:**
  - Admin referral settings (`referral_reward_points`, `referral_commission`, `referral_enabled`) control signup referral rewards and display on `/refer`.

---

### Module 9: KYC Management (`/admin/ludo/kyc`)
- **Existing Functionality:**
  - Paginated KYC submission queue (`GET /admin/ludo/kyc`) with search, `status`, and `document_type` filters.
  - Protected document download (`GET /kyc/download/{id}/{side}`) restricted to document owner and admins.
  - Approve/Reject action (`POST /admin/kyc/{id}/review`) updating both `ludo_kyc_submissions` and `users.kyc_status` + audit log.
- **Missing Functionality:**
  1. **Reviewer Identity & Timestamp Display:** `kycIndex` does not eager-load `reviewer` or display `reviewer->name` and `reviewed_at` in the table.
  2. **Player Notification on KYC Verdict:** `adminReviewKyc` does not send an in-app notification to the user when their KYC is approved or rejected.
  3. **KYC Desk Summary Counters:** Missing pending/approved/rejected summary cards on `/admin/ludo/kyc`.

---

### Module 10: Support Management (`/admin/ludo/tickets`)
- **Existing Functionality:**
  - Paginated support ticket listing (`GET /admin/ludo/tickets`) with search, status, and category filters.
- **Missing Functionality / Bugs Found:**
  1. **Category Filter Mismatch:** `admin/ludo/tickets/index.blade.php` filters by `match_issue`, `deposit_issue`, `kyc_verification`, `account_query`, whereas player tickets are stored with `battle_issue`, `deposit_wallet`, `account_profile`, `kyc_issue`, `technical_bug`, `other`!
  2. **Dedicated Admin Ticket Workbench (`GET /admin/ludo/tickets/{id}`):** Clicking "Reply" takes the admin out of the Admin Panel into the mobile player view (`/support/ticket/{id}`). A dedicated Admin Ticket Workbench (`resources/views/admin/ludo/tickets/show.blade.php`) with status management (`open`, `in_progress`, `answered`, `resolved`, `closed`), priority updates, internal/staff reply, and attachment download is needed.

---

### Module 11: Rules, Announcements & Settings (`/admin/ludo/content` & `/admin/ludo/settings`)
- **Existing Functionality:**
  - Bilingual announcements (`announcement_hi`, `announcement_en`), bilingual rules (`rules_en`, `rules_hi`), FAQ (`faq_content`), and system settings (`platform_name`, `submission_window`, `referral_commission`, `maintenance_mode`, `support_email`, `support_whatsapp`, `support_telegram`, `support_hours`, `minimum_battle_amount`, `kyc_mandatory`).
- **Missing Functionality:**
  1. Missing configurable `referral_reward_points` (virtual points awarded per eligible referral signup) and `referral_enabled` toggle.
  2. Missing configurable `welcome_bonus_points` (virtual points awarded on new player registration, default 100).

---

### Module 12: Reports, Analytics & Audit Logs (`/admin/ludo/reports` & `/admin/ludo/audit-logs`)
- **Existing Functionality:**
  - `LudoAdminAuditLog` records actions and shows the latest 8 entries on the dashboard.
- **Missing Functionality:**
  1. **No Reports & Analytics Module (`/admin/ludo/reports`):** Missing date-filtered analytical reports for user registration trends, tournament activity, match completion/cancellation counts, result review workload, referral activity, virtual-points ledger summaries, and support ticket statistics.
  2. **No CSV Export (`GET /admin/ludo/reports/export/{type}`):** Missing authorized CSV exports for administrative reporting.
  3. **No Dedicated Audit Log Explorer (`/admin/ludo/audit-logs`):** Missing full paginated, searchable, filterable audit log table with action type, target resource, admin filter, IP address, and timestamps.

---

## 3. Database Schema & Migrations Assessment

All core tables (`users`, `ludo_battles`, `ludo_tournaments`, `ludo_tournament_participants`, `ludo_disputes`, `ludo_dispute_evidences`, `ludo_referrals`, `ludo_kyc_submissions`, `ludo_support_tickets`, `ludo_ticket_messages`, `ludo_ledger_transactions`, `ludo_settings`, `ludo_admin_audit_logs`, `notifications`) already exist and contain the necessary columns. No destructive migrations are needed.
