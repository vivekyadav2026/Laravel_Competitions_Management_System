# FSALUDOCLUB — ADMIN DATA CLEANUP & ISOLATION REPORT

**Project:** Fsaludoclub Ludo Platform  
**Date:** October 15, 2026  
**Status:** Cleanup Executed & Verified  

---

## 1. Overview & Objective

During the comprehensive audit of the database and public lobby, 32 historical battle records were detected in the `requested` status. In real gameplay, when a second player accepts an open battle challenge, the match transitions to `requested`. At this stage, the creator has a short window (180 seconds) to click **START** or **CANCEL**.

Because several automated test runs had created matches and exited without concluding the matches, these 32 records remained lingering in the database. Furthermore, due to an unconstrained `whereIn('status', ['open', 'requested'])` query in `LudoController.php`, third-party players visiting `/battles` saw these matches rendered as cards with disabled **"Starting..."** buttons (such as Battle #1440 and Battle #1275).

This report documents the safe cancellation, ledger refund, and architectural isolation of those records.

---

## 2. Catalog of Cleaned Records

All 32 stale `requested` battles were inspected and safely transitioned to `cancelled` status with full ledger point refunds.

| Battle ID | Battle UID | Creator | Joiner | Entry Fee | Initial Status | Action Taken | Ledger Refund |
|---|---|---|---|---|---|---|---|
| **1114** | `BAT-JOIN-lExO5` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1116** | `BAT-RACE-sImiB` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1173** | `BAT-JOIN-CHvGH` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1175** | `BAT-RACE-rgPus` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1226** | `BAT-JOIN-moo1Y` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1228** | `BAT-RACE-25pev` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1275** | `BAT-SND-2B7FBB` | Arjun Sharma | Kavita Singh | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1290** | `BAT-JOIN-m4ldk` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1292** | `BAT-RACE-YUvAG` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1348** | `BAT-JOIN-bupKe` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1350** | `BAT-RACE-Zdfby` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1401** | `BAT-JOIN-QPSoQ` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1403** | `BAT-RACE-QhwFq` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1440** | `BAT-BLU-250` | Rohit Verma | wVUtQ | 250 | requested | Cancelled | Creator & Joiner Refunded |
| **1459** | `BAT-JOIN-d2yPf` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1461** | `BAT-RACE-ayXaK` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1518** | `BAT-JOIN-6kPVP` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1520** | `BAT-RACE-AyPJi` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1577** | `BAT-JOIN-VrmAx` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1579** | `BAT-RACE-5mQLe` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1622** | `BAT-JOIN-g8WcB` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1624** | `BAT-RACE-KvqVA` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1653** | `BAT-JOIN-G2KRr` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1655** | `BAT-RACE-2oHe5` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1709** | `BAT-JOIN-ASXLZ` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1711** | `BAT-RACE-MB2XB` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1768** | `BAT-JOIN-PZmcC` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1770** | `BAT-RACE-4sqo2` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1824** | `BAT-JOIN-qrRT3` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1826** | `BAT-RACE-bEgAp` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |
| **1880** | `BAT-JOIN-8dd0n` | Suresh Kumar | Pooja Verma | 50 | requested | Cancelled | Creator & Joiner Refunded |
| **1882** | `BAT-RACE-iAfYN` | Sunil Gavaskar | Rahul Dravid | 100 | requested | Cancelled | Creator & Joiner Refunded |

---

## 3. Financial & Ledger Safety Guarantees

The cleanup script executed inside MySQL transactions with row-level locks:
1. Checked for existing refund ledger entries to prevent duplicate credits.
2. Invoked `LudoLedgerTransaction::recordAdjustment()` to ensure user balances and double-entry transaction ledgers matched to the exact point.
3. Updated battle status to `cancelled` with reason `"Expired requested match cleaned up by system"`.
4. Zero records were deleted (`DELETE FROM`), preserving complete referential integrity.

---

## 4. Architectural Prevention of Future Stale Cards

To permanently prevent third-party players from seeing occupied `requested` matches with disabled "Starting..." buttons, the query scope was updated in `app/Http/Controllers/LudoController.php`:

### In `battles()` & `loadMoreBattles()`:
```php
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
```

### Result:
- **Creator** sees their requested battle card with **START** and **CANCEL** actions.
- **Joiner** sees the card with **Arena** link and status `"Waiting for creator..."`.
- **Third-Party Players** only receive battles with `status = 'open'` that can be actively challenged and played.
- Stale, occupied, or non-actionable cards are completely hidden from the public lobby.
