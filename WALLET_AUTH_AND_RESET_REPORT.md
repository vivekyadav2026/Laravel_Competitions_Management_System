# FSALUDOCLUB — UNAUTHENTICATED REDIRECT FIX & WALLET BALANCE RESET REPORT

**Date:** 2026-10-15  
**Platform:** FSALUDOCLUB Gaming & Tournament Management System  
**Environment:** Production / PHP 8.2.12 / Laravel 10.46.0 / MySQL (`ludotournament`)  
**Test Suite Status:** 154 Tests Passed, 1,179 Assertions, 0 Failures (19.24s)

---

## 1. Executive Summary

This report documents the resolution of two platform-critical requirements:
1. **Unauthenticated Redirect Overhaul:** Guest users accessing protected wallet pages, submitting balance/points forms, or triggering frontend AJAX/fetch balance actions no longer receive raw `"Unauthenticated."` exception texts or broken modal dialogs. Browser requests redirect immediately to the named Login route (`/showlogin`) with flash message `"Please log in to continue."` while preserving the intended safe URL. JavaScript/AJAX requests receive a clean HTTP 401 JSON response with `'redirect'` destination, intercepted globally by the client application.
2. **Safe Idempotent Wallet Balance Reset:** Every existing user account balance in the platform (11 total accounts, including administrators and demo players) has been systematically and safely reset to zero (`0` virtual coins and `₹0.00` referral balance) using authoritative ledger adjustments (`admin_adjustment`), preserving all underlying deposit records, game battle records, and payment history.

---

## 2. Root Cause of the "Unauthenticated" Issue

| Path / Mechanism | Previous Behavior | Root Cause |
|---|---|---|
| **Exception Handler** | Laravel returned default `{"message": "Unauthenticated."}` on JSON requests and threw unhandled responses for certain requests. | `app/Exceptions/Handler.php` lacked an overridden `unauthenticated()` method, allowing framework defaults to output raw `Unauthenticated.` text. |
| **Middleware Check** | `app/Http/Middleware/Authenticate.php` only checked `$request->expectsJson()`, missing `$request->ajax()` calls from older jQuery/fetch routines. | Requests sending `X-Requested-With: XMLHttpRequest` without explicit `Accept: application/json` were not consistently classified. |
| **Controller Actions** | `LudoController.php` contained hardcoded `return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);` without redirection hints. | Controllers had manual auth checks returning raw strings rather than standard structured responses with `'redirect' => route('login')`. |
| **Frontend Fetch Callers** | Frontend JavaScript (`battles.blade.php`) alerted `data.message` directly upon receiving non-200 responses. | Because `data.message` was `"Unauthenticated."`, browser alert boxes popped up displaying raw `Unauthenticated.` text instead of redirecting the user to `/showlogin`. |
| **Login Redirection** | `AuthController.php` did not sanitize `session('url.intended')` against administrative routes. | A normal player who attempted to open an administrative link and then logged in could be forwarded into an admin endpoint yielding 403 Forbidden. |

---

## 3. Routes, Middleware & Files Modified

### 1. `app/Exceptions/Handler.php`
- **Changes:** Overrode `unauthenticated($request, AuthenticationException $exception)`:
  - If `$request->expectsJson() || $request->ajax() || $this->shouldReturnJson()`: returns HTTP 401 with structured JSON:
    ```json
    {
      "success": false,
      "message": "Please log in to continue.",
      "redirect": "http://127.0.0.1:8000/showlogin"
    }
    ```
  - For browser requests: returns `redirect()->guest(route('login'))->with('error', 'Please log in to continue.')->with('message', 'Please log in to continue.')`.

### 2. `app/Http/Middleware/Authenticate.php`
- **Changes:** Enhanced `redirectTo(Request $request)` to return `null` if `$request->expectsJson() || $request->ajax()`, else `route('login')`.

### 3. `app/Http/Controllers/LudoController.php`
- **Changes:** Updated unauthenticated guards across `wallet`, `submitUpiDeposit`, `requestWithdrawal`, `addVirtualPoints`, `createBattle`, `joinBattle`, `startBattle`, `setRoomCode`, `cancelBattle`, and `cancelWithReason`. All now return consistent 401 JSON with redirect on AJAX or redirect to login with session flash on browser requests.

### 4. `app/Http/Controllers/AuthController.php`
- **Changes:** In `login()` and `verifyOtp()`, added sanitization for `url.intended`:
  - If a standard player had an administrative URL (`/admin/...`) in `url.intended`, the URL is purged and the player is safely routed to `/` (home).
  - Safe intended player URLs (e.g. `/wallet`, `/history`, `/profile`) are honored and redirected to smoothly.

### 5. `public/js/ludo-app.js` & `resources/views/ludo/battles.blade.php`
- **Changes:**
  - Added a global `window.fetch` interceptor in `ludo-app.js` catching HTTP 401 responses and automatically routing the window location to `data.redirect || '/showlogin'`.
  - Added direct 401 checks in `createBattle` and `joinBattle` AJAX handlers.

### 6. `app/Console/Commands/ResetWalletBalancesCommand.php`
- **Changes:** Created dedicated Artisan command `php artisan ludo:reset-wallet-balances` with `--dry-run` and `--force` options.

---

## 4. Database Schema Inspection & Affected Balance Sources

An exhaustive audit of the `ludotournament` database schema identified the following columns and tables:

| Table | Column | Type | Role in Platform Balance |
|---|---|---|---|
| `users` | `coins` | `int(10) unsigned` | **Authoritative spendable virtual points / coin balance.** Used for battle entry fees, payouts, withdrawals, and dashboard circulating totals. |
| `users` | `referral_balance` | `decimal(10,2)` | **Spendable referral commission balance.** |
| `ludo_ledger_transactions` | `points_delta` | `int(11)` | Transaction delta (+/-) credited or debited. |
| `ludo_ledger_transactions` | `balance_before` | `int(10) unsigned` | Snapshot of spendable coins before transaction. |
| `ludo_ledger_transactions` | `balance_after` | `int(10) unsigned` | Snapshot of spendable coins after transaction. |
| `ludo_referrals` | `reward_points` | `int(11)` | Historical record of bonus points rewarded per referral relationship. |
| `ludo_admin_audit_logs` | N/A | Table | System audit trail recording administrative actions. |

**No other balance columns or secondary balance caches exist in the database.**  
`events.event_points` is part of a legacy competition structure and does not affect player wallets.

---

## 5. Wallet Balance Reset Execution Details

### A. Pre-Reset Account Breakdown (Target Database: `ludotournament`)

| User ID | Player UID | Name | Role | Status | Previous Coins | Previous Referral | Adjustment Delta |
|---|---|---|---|---|---|---|---|
| 1 | FSADMIN01 | Fsaludoclub Superadmin | superadmin | active | 100,000 | 0.00 | -100,000 |
| 2 | N/A | Fsaludoclub Admin | admin | active | 50,000 | 0.00 | -50,000 |
| 3 | DEMO00003 | Arjun Sharma | normal | active | 50,000 | 0.00 | -50,000 |
| 4 | DEMO00004 | Rohit Verma | normal | active | 50,500 | 0.00 | -50,500 |
| 5 | DEMO00005 | Priya Patel | normal | active | 50,000 | 0.00 | -50,000 |
| 6 | DEMO00006 | Vikram Singh | normal | active | 50,000 | 0.00 | -50,000 |
| 7 | DEMO00007 | Neha Gupta | normal | active | 50,000 | 0.00 | -50,000 |
| 8 | DEMO00008 | Suresh Raina | normal | active | 50,000 | 0.00 | -50,000 |
| 9 | DEMO00009 | Rahul Sharma | normal | active | 50,000 | 0.00 | -50,000 |
| 10 | DEMO00010 | Deepak Yadav | normal | active | 50,000 | 0.00 | -50,000 |
| 24 | LUDO00024 | Abhishek123 | normal | active | 100 | 0.00 | -100 |

- **Total Registered Accounts:** 11  
- **Accounts with Non-Zero Coins:** 11  
- **Total Coins to Zero:** 550,600  
- **Total Referral Commission:** ₹0.00  

### B. Backup Status
Before any data was changed, full copies of the affected tables were created directly in MySQL:
- `users_backup_20261015_164402`
- `ludo_ledger_backup_20261015_164402`

### C. Reset Mechanism & Ledger Preservation
The reset was executed inside an atomic transaction using row-level locking (`lockForUpdate`):
1. For every account with `coins > 0`, a formal ledger transaction of type `'admin_adjustment'` was created:
   - `points_delta`: `-$currentCoins`
   - `balance_before`: `$currentCoins`
   - `balance_after`: `0`
   - `status`: `'successful'`
   - `description`: `'System-wide wallet balance reset to zero'`
   - `reason`: `'Authorized platform balance reset by administrator'`
   - `created_by_admin_id`: 1
2. `$user->coins` was set to `0`.
3. `$user->referral_balance` was set to `0.00`.
4. Audit log entry recorded in `ludo_admin_audit_logs`.
5. Historical deposits, entry fees, and battle payouts were **100% preserved**. No records were deleted.

### D. Idempotence Verification
Running `php artisan ludo:reset-wallet-balances --force` a second time verified:
- Accounts adjusted: `0`
- Ledger entries created: `0`
- Zero duplicate entries or repeated adjustments.

---

## 6. Admin Panel Balance Display Verification

The Admin Panel computes all statistics and table displays dynamically from live database queries:
- **Admin Dashboard (`/admin/ludo/dashboard`):** Circulating points displays `🪙 0` (queried via `User::sum('coins')`).
- **User Management (`/admin/ludo/users`):** Every user card and table row displays `0 Coins`.
- **Ledger Management (`/admin/ludo/ledger`):** "Player Wallet Balances" summary displays `🪙 0`.
- **User Dossier (`/admin/ludo/users/{id}`):** Shows `Coins: 0` and `Referral Balance: 0.00`.

---

## 7. Automated Test Suite Results

The dedicated feature test suite `tests/Feature/WalletAuthAndBalanceResetTest.php` was executed alongside the complete application test suite:

```bash
php artisan test
```

```
Tests:    154 passed (1179 assertions)
Duration: 19.24s
Failures: 0
```

### Breakdown of Wallet & Reset Tests:
1. `test_guest_access_to_wallet_pages_redirects_to_login_with_message` — **PASS**
2. `test_guest_wallet_form_submissions_redirect_to_login_with_message` — **PASS**
3. `test_guest_wallet_and_balance_ajax_requests_return_401_json_with_redirect` — **PASS**
4. `test_authenticated_wallet_routes_continue_working` — **PASS**
5. `test_successful_login_redirects_to_intended_safe_url_and_sanitizes_admin_paths` — **PASS**
6. `test_all_targeted_account_balances_are_zero_after_reset` — **PASS**
7. `test_historical_transactions_remain_intact_with_formal_ledger_adjustment` — **PASS**
8. `test_reset_command_is_idempotent_and_creates_zero_duplicate_adjustments` — **PASS**
9. `test_admin_panel_displays_zero_from_actual_database_queries` — **PASS**

---

## 8. Remaining Risks & Blockers

- **Zero blockers identified.**
- **No data loss:** Table backups remain preserved in `users_backup_*` and `ludo_ledger_backup_*`.
- **Production readiness:** The Artisan command `ludo:reset-wallet-balances` is idempotent, repeatable, and available for automated or manual execution at any time.
