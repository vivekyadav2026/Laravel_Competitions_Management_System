# FSALUDOCLUB — DYNAMIC GAME COMMISSION IMPLEMENTATION REPORT

**Date:** October 15, 2026  
**Status:** Completed & Fully Verified  
**Scope:** Dynamic Game Commission Engine, Flat Percentage Mode, Tier/Slab Mode, MySQL Setting Store, Non-Retroactive Match Settlement Guarantee.

---

## 1. Overview & Business Logic

In the reference Fsaludoclub battle design, each 2-player match pools both players' entry fees:
$$\text{Total Pool} = 2 \times \text{Entry Fee}$$

Previously, the platform used a hardcoded default where the winner receives $1.9\times$ the entry fee (i.e. a $5\%$ commission on the total pool or $10\%$ of one player's entry fee).

The system has now been upgraded to a **Dynamic Commission Architecture** where administrators can configure how battle prizes are calculated across the platform, while preserving backward compatibility and existing match records.

---

## 2. Dynamic Commission Modes

### Mode A: Flat Percentage
Admin defines a single platform commission percentage $P$ applied to the total prize pool.
$$\text{Commission Amount} = \text{round}\left( \text{Total Pool} \times \frac{P}{100} \right)$$
$$\text{Winning Prize} = \text{Total Pool} - \text{Commission Amount}$$

*Example ($P = 5\%$):*
- Entry Fee: ₹100
- Total Pool: ₹200
- Commission: ₹10
- Winning Prize: ₹190

### Mode B: Slab / Tiered System
Admin defines progressive slabs (stored as JSON) specifying commission rules based on the battle entry fee:
```json
[
  {"min_fee": 1, "max_fee": 200, "type": "percentage", "rate": 10},
  {"min_fee": 201, "max_fee": 500, "type": "fixed", "rate": 25},
  {"min_fee": 501, "max_fee": 100000, "type": "percentage", "rate": 5}
]
```
If an entry fee matches a slab:
- **`percentage`:** Commission is $\text{round}\left( \text{Total Pool} \times \frac{\text{rate}}{100} \right)$
- **`fixed`:** Commission is a flat amount ($\text{rate}$)

If no slab matches, the system gracefully falls back to the default percentage.

---

## 3. Database Schema & Storage

Commission settings are stored dynamically in the MySQL `ludo_settings` table:

```sql
CREATE TABLE `ludo_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ludo_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Stored Keys
- `battle_commission_mode`: `'percentage'` or `'slabs'` (default: `'percentage'`).
- `battle_commission_percentage`: Decimal/Integer percentage string, e.g. `'5'`.
- `battle_commission_slabs`: Formatted JSON array representing tier thresholds and fee rates.

---

## 4. Core Implementation in `LudoBattle` Model

```php
public static function calculateCommission(int $entryFee): array
{
    $pool = $entryFee * 2;
    $mode = LudoSetting::get('battle_commission_mode', 'percentage');

    if ($mode === 'slabs') {
        $slabsJson = LudoSetting::get('battle_commission_slabs');
        $slabs = is_string($slabsJson) ? json_decode($slabsJson, true) : null;

        if (is_array($slabs) && count($slabs) > 0) {
            foreach ($slabs as $slab) {
                $min = (int) ($slab['min_fee'] ?? 0);
                $max = (int) ($slab['max_fee'] ?? PHP_INT_MAX);
                $type = $slab['type'] ?? 'percentage';
                $rate = (float) ($slab['rate'] ?? 5);

                if ($entryFee >= $min && $entryFee <= $max) {
                    if ($type === 'fixed') {
                        $commission = min($pool, (int) round($rate));
                    } else {
                        $commission = (int) round(($pool * $rate) / 100);
                    }
                    $prize = max(0, $pool - $commission);
                    return ['commission' => $commission, 'prize' => $prize, 'mode' => 'slabs'];
                }
            }
        }
    }

    // Default: Flat percentage
    $pct = (float) LudoSetting::get('battle_commission_percentage', 5);
    $commission = (int) round(($pool * $pct) / 100);
    $prize = max(0, $pool - $commission);

    return ['commission' => $commission, 'prize' => $prize, 'mode' => 'percentage'];
}

public static function calculatePrize(int $entryFee): int
{
    $calc = self::calculateCommission($entryFee);
    return (int) $calc['prize'];
}
```

---

## 5. Non-Retroactive Settlement Guarantee

Changing platform commission settings in the Admin Panel affects **only new battles created after the change**.

1. **Snapshotted Fields at Creation:** When a battle is created, both `entry_fee` and `prize` are calculated and saved as explicit columns in the `ludo_battles` table:
   ```php
   $battle = LudoBattle::create([
       'creator_id' => $user->id,
       'entry_fee' => $entryFee,
       'prize' => LudoBattle::calculatePrize($entryFee), // Computed at creation
       'status' => 'open',
   ]);
   ```
2. **Immutable Match Settlement:** During match conclusion (player win submission, admin dispute resolution, or cancellation), all coin distribution and ledger credits read the **immutable stored prize** from the database (`$battle->prize`):
   ```php
   $winnerPrize = (int) $battle->prize;
   LudoLedgerTransaction::recordAdjustment(
       $winner,
       $winnerPrize,
       'win',
       "Prize for winning Battle #{$battle->id}",
       'successful'
   );
   ```
3. Existing completed, running, or disputed matches retain their originally agreed prize and cannot suffer retroactive alteration.

---

## 6. Admin Panel Commission Desk (`/admin/ludo/settings`)

In the Admin Settings view (`resources/views/admin/ludo/settings/index.blade.php`), a dedicated card titled **"Battle Commission & Prize Engine"** features:
- **Commission Mode Toggle:** Select between Flat Percentage or Tiered/Slab Based.
- **Default Percentage Input:** Direct input with step and percentage validation (0% to 50%).
- **JSON Slab Editor:** Code block editor with syntax example and schema validator.
- **Live Interactive Calculator:** Real-time JavaScript simulation where administrators can enter sample entry fee amounts (e.g., ₹50, ₹100, ₹500, ₹1000) and immediately preview the exact Total Pool, Platform Commission, and Winner's Take-Home Prize.
