# FSALUDOCLUB — DIRECT UPI QR CODE PAYMENT GATEWAY REPORT

**Date:** October 15, 2026  
**Status:** Completed & Fully Verified  
**Scope:** Direct UPI QR Configuration, File Upload, Player Wallet Payment Flow, UTR Validation, Administrative Review Desk, Atomic Ledger Credit, In-App Notifications, Security.

---

## 1. Architecture Overview (No Third-Party Gateway)

In compliance with direct peer-to-merchant UPI standards and cost-efficiency requirements, **Fsaludoclub** does not route funds through third-party payment aggregators (such as Razorpay, Cashfree, or Paytm PG). Instead, a native **Direct UPI QR Engine** is integrated:

```
[Admin Configures Gateway in Admin Panel]
  ├── Uploads QR Code Image (Stored in storage/app/public/admin/upi/)
  ├── Sets Payee Name, UPI ID (VPA), Min/Max Limits
  └── Sets Instructions (English & Hindi)
            │
            ▼
[Player Opens Wallet Screen (/wallet)]
  ├── Views Admin's Dynamic QR Code & Payee Details
  ├── Scans QR using any Indian UPI App (PhonePe, GPay, Paytm, BHIM)
  ├── Submits Amount, 12-digit UTR/Ref Number, & Receipt Screenshot
  └── System locks balance in "Pending" status & prevents duplicate UTR
            │
            ▼
[Admin Review Desk (/admin/ludo/deposits)]
  ├── Inspects Player UTR, Timestamp, Amount, and Proof Receipt Modal
  ├── Action: APPROVE
  │     ├── DB Transaction with lockForUpdate()
  │     ├── Authoritative credit via LudoLedgerTransaction
  │     ├── Increments player coins
  │     └── Dispatches LudoInAppNotification to Player
  └── Action: REJECT
        ├── Prompts for mandatory rejection reason
        ├── Preserves wallet balance (no deduction/reversal needed)
        └── Dispatches LudoInAppNotification with clear rejection reason
            │
            ▼
[Player Wallet History Section]
  └── Displays "My Payment Requests" table with status pill & rejection explanation
```

---

## 2. Database Schema

### Table: `ludo_upi_deposits`
```sql
CREATE TABLE `ludo_upi_deposits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deposit_uid` varchar(32) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `utr_number` varchar(64) NOT NULL,
  `upi_id` varchar(128) DEFAULT NULL,
  `screenshot_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ludo_upi_deposits_deposit_uid_unique` (`deposit_uid`),
  KEY `ludo_upi_deposits_user_id_index` (`user_id`),
  KEY `ludo_upi_deposits_utr_number_index` (`utr_number`),
  KEY `ludo_upi_deposits_status_index` (`status`),
  CONSTRAINT `ludo_upi_deposits_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 3. Admin Configuration Desk (`/admin/ludo/settings`)

The administrative interface provides a dedicated **Direct UPI Payment Gateway** configuration suite:
- **Global Feature Toggle:** Instant ON/OFF switch (`upi_deposits_enabled`). When disabled, the QR code card is cleanly hidden from player wallets, and deposit submissions are rejected with HTTP 403.
- **Payee Details:** Merchant Name (`upi_payee_name`) and VPA / UPI ID (`upi_id`, e.g., `9631608403@paytm`).
- **QR Code Image Upload:** Supports JPG, PNG, and WebP images up to 4MB. Uploaded images are stored with hashed filenames in public storage and previewed immediately.
- **Transaction Limits:** Minimum deposit (default: ₹50) and Maximum deposit per transaction (default: ₹50,000).
- **Bilingual Guidelines:** Separate English and Hindi step-by-step instructions rendered on the player's wallet card.

---

## 4. Player Wallet Experience (`/wallet`)

When direct UPI deposits are enabled:
1. **Dynamic QR Presentation:** Displays the platform's QR code image with gold glowing borders, payee name, and a one-click **"Copy UPI ID"** button.
2. **Deposit Submission Form:**
   - Input for Deposit Amount with live min/max limit feedback.
   - Input for 12-digit UPI Reference / UTR Number.
   - File attachment field for payment receipt screenshot (JPG, PNG, WebP up to 5MB).
3. **Double Submission Protection:** The submit button disables upon click to prevent double-clicks.
4. **"My Payment Requests" History:**
   - Real-time table displaying past deposit requests.
   - Shows Deposit UID, Date & Time, Amount, UTR Number, and Status Badge:
     - 🟡 **Pending Verification** (Waiting for admin)
     - 🟢 **Approved & Credited** (Coins added to balance)
     - 🔴 **Rejected** (With explicit red alert explaining the admin's rejection reason)
5. **Non-Cash Virtual Points Fallback:** The wallet maintains the existing instant Virtual Points reload card below the QR card, ensuring players without UPI can always continue testing and playing free tournament battles.

---

## 5. Administrative Review & Approval Workflow (`/admin/ludo/deposits`)

The admin review desk at `/admin/ludo/deposits` equips administrators with:
1. **Metrics Counters:** Total Pending Requests, Total Approved, and Total Amount Credited.
2. **Proof Modal:** In-modal receipt preview dialog allowing administrators to inspect full-resolution payment screenshots without leaving the page.
3. **Atomic Approval Action (`POST /admin/ludo/deposits/{id}/approve`):**
   - Locks the deposit record using `lockForUpdate()`.
   - Verifies the deposit status is strictly `'pending'` to prevent double-credit.
   - Updates status to `'approved'`, records `approved_by` and `approved_at`.
   - Executes authoritative ledger transaction:
     ```php
     LudoLedgerTransaction::recordAdjustment(
         $player,
         $deposit->amount,
         'deposit',
         "Direct UPI QR Deposit (UTR: {$deposit->utr_number})",
         'successful'
     );
     ```
   - Automatically dispatches a persistent in-app notification (`LudoInAppNotification`) informing the player of their credited balance.
4. **Atomic Rejection Action (`POST /admin/ludo/deposits/{id}/reject`):**
   - Requires a valid rejection explanation (e.g. *"UTR not found on bank statement"* or *"Amount mismatch"*).
   - Locks deposit row, transitions status to `'rejected'`, and stores `admin_notes`.
   - Leaves player coins unchanged (zero leakage).
   - Dispatches in-app notification containing the rejection reason.

---

## 6. Security & Fraud Mitigations

1. **UTR De-Duplication:** Before creating any deposit, the system cleans the UTR (removing non-alphanumeric noise, converting to uppercase) and checks for active submissions:
   ```php
   $existing = LudoUpiDeposit::where('utr_number', $cleanUtr)
       ->whereIn('status', ['approved', 'pending'])
       ->first();
   ```
   If found, the request is immediately aborted with a user-facing warning.
2. **Pessimistic Concurrency Locking:** Approval operations execute inside `DB::transaction()` with `lockForUpdate()` on both `LudoUpiDeposit` and `User` records. Even if two administrators click "Approve" at the exact same millisecond, the second transaction sees status `'approved'` and safely fails with zero double credit.
3. **MIME-Type & Extension Verification:** Uploaded receipt screenshots are validated against strictly allowed mime types (`jpeg,png,jpg,webp`) and capped at 5MB.
4. **IDOR & Route Protection:** Deposit actions are guarded by the `ludo.admin` middleware. Regular players cannot access administrative deposit review or approve their own submissions.
