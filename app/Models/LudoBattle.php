<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\LudoAdminAuditLog;

class LudoBattle extends Model
{
    use HasFactory;

    protected $table = 'ludo_battles';

    protected $fillable = [
        'battle_uid',
        'creator_id',
        'joiner_id',
        'entry_fee',
        'prize',
        'status',
        'room_code',
        'winner_id',
        'claimed_result',
        'final_result',
        'review_status',
        'creator_claimed_result',
        'joiner_claimed_result',
        'creator_evidence_path',
        'joiner_evidence_path',
        'refund_status',
        'refund_requested_by_id',
        'refund_reason',
        'refund_decision_reason',
        'admin_reviewed_at',
        'admin_reviewed_by_id',
        'submission_deadline_at',
        'room_code_expires_at',
        'cancellation_reason',
        'cancelled_by_id',
        'dispute_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'entry_fee' => 'integer',
        'prize' => 'integer',
        'submission_deadline_at' => 'datetime',
        'room_code_expires_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'admin_reviewed_at' => 'datetime',
    ];


    /**
     * Match creator (player 1)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Match joiner / opponent (player 2)
     */
    public function joiner()
    {
        return $this->belongsTo(User::class, 'joiner_id');
    }

    /**
     * Winner
     */
    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    /**
     * Player who requested the refund/cancellation
     */
    public function refundRequestedBy()
    {
        return $this->belongsTo(User::class, 'refund_requested_by_id');
    }

    /**
     * Admin who reviewed/decided this battle
     */
    public function adminReviewedBy()
    {
        return $this->belongsTo(User::class, 'admin_reviewed_by_id');
    }

    /**
     * Compute platform commission and prize based on dynamic database settings / slabs.
     * 
     * @param int $entryFee
     * @return array ['pool' => int, 'commission' => int, 'prize' => int, 'rate_applied' => string]
     */
    public static function calculateCommission(int $entryFee): array
    {
        $pool = $entryFee * 2;
        $mode = LudoSetting::get('battle_commission_mode', 'percentage');

        // Check if slab mode is enabled
        if ($mode === 'slabs') {
            $slabsJson = LudoSetting::get('battle_commission_slabs');
            $slabs = is_string($slabsJson) ? json_decode($slabsJson, true) : $slabsJson;

            if (is_array($slabs) && !empty($slabs)) {
                foreach ($slabs as $slab) {
                    $min = (int) ($slab['min'] ?? 0);
                    $max = (int) ($slab['max'] ?? PHP_INT_MAX);

                    if ($entryFee >= $min && $entryFee <= $max) {
                        $type = $slab['type'] ?? 'percent';
                        $value = (float) ($slab['value'] ?? 5);

                        if ($type === 'fixed') {
                            $commission = (int) round($value);
                            $rateApplied = "Fixed: {$commission} coins";
                        } elseif ($type === 'percent_of_battle' || $type === 'percent_of_entry' || ($slab['basis'] ?? '') === 'battle' || ($slab['basis'] ?? '') === 'entry') {
                            $commission = (int) round($entryFee * ($value / 100));
                            $rateApplied = "{$value}% of battle amount";
                        } else {
                            // Percentage of total pool
                            $commission = (int) round($pool * ($value / 100));
                            $rateApplied = "{$value}% of pool";
                        }

                        $commission = max(0, min($commission, $pool));
                        $prize = $pool - $commission;

                        return [
                            'pool' => $pool,
                            'commission' => $commission,
                            'prize' => $prize,
                            'rate_applied' => $rateApplied,
                        ];
                    }
                }
            }
        }

        // Standard / Percentage mode (default: 5% platform fee on pool => 1.9x entry fee)
        $pctSetting = LudoSetting::get('battle_commission_percentage', '5');
        // Clean any '%' sign from setting
        $pct = (float) str_replace('%', '', (string) $pctSetting);
        if ($pct <= 0) {
            $pct = 5.0;
        }

        $commission = (int) round($pool * ($pct / 100));
        $commission = max(0, min($commission, $pool));
        $prize = $pool - $commission;

        return [
            'pool' => $pool,
            'commission' => $commission,
            'prize' => $prize,
            'rate_applied' => "{$pct}% standard platform commission",
        ];
    }

    /**
     * Compute platform commission prize using authoritative dynamic configuration
     */
    public static function calculatePrize(int $entryFee): int
    {
        return self::calculateCommission($entryFee)['prize'];
    }

    /**
     * Dispute linked to this battle
     */
    public function dispute()
    {
        return $this->hasOne(LudoDispute::class, 'battle_id')->latestOfMany();
    }

    /**
     * Check if the submission deadline has expired without both players submitting.
     */
    public function isSubmissionDeadlineExpired(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        if (!$this->submission_deadline_at) {
            return false;
        }

        if (now()->lessThan($this->submission_deadline_at)) {
            return false;
        }

        $cClaim = $this->creator_claimed_result;
        $jClaim = $this->joiner_claimed_result;
        $cSub = ($cClaim && $cClaim !== 'pending');
        $jSub = ($jClaim && $jClaim !== 'pending');

        return !($cSub && $jSub);
    }

    /**
     * Get descriptive review status label for expired submission.
     */
    public function getExpiredReviewStatusText(): ?string
    {
        if (!$this->isSubmissionDeadlineExpired()) {
            return null;
        }

        $cClaim = $this->creator_claimed_result;
        $jClaim = $this->joiner_claimed_result;
        $cSub = ($cClaim && $cClaim !== 'pending');
        $jSub = ($jClaim && $jClaim !== 'pending');

        // Case 1: One player submitted Victory Claim ('won') and opponent did not
        if (($cSub && $cClaim === 'won' && !$jSub) || ($jSub && $jClaim === 'won' && !$cSub)) {
            return 'UNDER REVIEW — AWAITING OPPONENT CLAIM / DEADLINE EXPIRED';
        }

        // Case 2: Neither player submitted a result
        if (!$cSub && !$jSub) {
            return 'SUBMISSION EXPIRED — ADMIN REVIEW REQUIRED';
        }

        // Case 3: Other one-sided claim (lost/cancelled) and opponent did not
        if ($cSub xor $jSub) {
            return 'UNDER REVIEW — AWAITING OPPONENT CLAIM / DEADLINE EXPIRED';
        }

        return null;
    }

    /**
     * Return formatted review badge styling and text.
     */
    public function getFormattedReviewBadge(): array
    {
        $expiredText = $this->getExpiredReviewStatusText();
        if ($expiredText) {
            $isNoClaim = ($expiredText === 'SUBMISSION EXPIRED — ADMIN REVIEW REQUIRED');
            return [
                'text' => $expiredText,
                'class' => $isNoClaim ? 'bg-danger text-white' : 'bg-warning text-dark',
                'is_expired' => true,
            ];
        }

        $rev = $this->review_status ?? 'auto_approved';
        $class = ($rev === 'disputed') ? 'bg-danger text-white' : (($rev === 'under_review') ? 'bg-warning text-dark' : 'bg-dark border border-secondary text-info');

        return [
            'text' => strtoupper(str_replace('_', ' ', $rev)),
            'class' => $class,
            'is_expired' => false,
        ];
    }

    /**
     * Return formatted match status badge styling and text.
     */
    public function getFormattedStatusBadge(): array
    {
        $st = $this->status;
        $cls = ($st === 'completed') 
            ? 'bg-success text-white' 
            : (($st === 'running') 
                ? 'bg-primary text-white' 
                : (($st === 'cancelled') 
                    ? 'bg-danger text-white' 
                    : (($st === 'under_review') 
                        ? 'bg-warning text-dark' 
                        : 'bg-secondary text-white')));

        return [
            'text' => strtoupper(str_replace('_', ' ', $st)),
            'class' => $cls,
        ];
    }

    /**
     * Automatically detect expired deadline and transition battle to under_review state idempotently.
     */
    public function checkAndProcessSubmissionExpiry(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        if (!$this->submission_deadline_at || now()->lessThan($this->submission_deadline_at)) {
            return false;
        }

        $cClaim = $this->creator_claimed_result;
        $jClaim = $this->joiner_claimed_result;
        $cSub = ($cClaim && $cClaim !== 'pending');
        $jSub = ($jClaim && $jClaim !== 'pending');

        if ($cSub && $jSub) {
            return false;
        }

        return DB::transaction(function () {
            $locked = self::lockForUpdate()->find($this->id);
            if (!$locked || in_array($locked->status, ['completed', 'cancelled'])) {
                return false;
            }

            if (!$locked->submission_deadline_at || now()->lessThan($locked->submission_deadline_at)) {
                return false;
            }

            $cClaim = $locked->creator_claimed_result;
            $jClaim = $locked->joiner_claimed_result;
            $cSub = ($cClaim && $cClaim !== 'pending');
            $jSub = ($jClaim && $jClaim !== 'pending');

            if ($cSub && $jSub) {
                return false;
            }

            $alreadyLogged = LudoAdminAuditLog::where('target_type', 'LudoBattle')
                ->where('target_id', $locked->id)
                ->where('action_type', 'deadline_expired')
                ->exists();

            $updated = false;
            if ($locked->status !== 'under_review' || $locked->review_status !== 'under_review') {
                $locked->status = 'under_review';
                $locked->review_status = 'under_review';
                $locked->save();
                $updated = true;
            }

            if (!$alreadyLogged) {
                $cUser = $locked->creator;
                $jUser = $locked->joiner;

                if (($cSub && $cClaim === 'won' && !$jSub) || ($jSub && $jClaim === 'won' && !$cSub)) {
                    $claimant = ($cSub && $cClaim === 'won') ? ($cUser->name ?? 'Player 1') : ($jUser->name ?? 'Player 2');
                    $claimantTag = ($cSub && $cClaim === 'won') ? ($cUser ? $cUser->formatted_username : '#'.$locked->creator_id) : ($jUser ? $jUser->formatted_username : '#'.$locked->joiner_id);
                    $scenarioDesc = "One-sided Victory Claim submitted by {$claimant} ({$claimantTag}). Opponent failed to submit claim before deadline expired. Held under review.";
                } elseif (!$cSub && !$jSub) {
                    $scenarioDesc = "Neither player submitted a match result before the deadline expired. Held under review for manual admin resolution.";
                } else {
                    $scenarioDesc = "Submission deadline expired with incomplete claims (Creator: '{$cClaim}', Joiner: '{$jClaim}'). Placed under administrative review.";
                }

                $deadlineStr = $locked->submission_deadline_at ? $locked->submission_deadline_at->format('Y-m-d H:i:s') : 'N/A';
                $startedStr = $locked->started_at ? $locked->started_at->format('Y-m-d H:i:s') : 'N/A';

                $adminUserId = User::whereIn('role', ['admin', 'superadmin'])->value('id') ?? User::first()?->id ?? 1;

                LudoAdminAuditLog::create([
                    'admin_id' => (Auth::id() && in_array(Auth::user()->role ?? '', ['admin', 'superadmin'])) ? Auth::id() : $adminUserId,
                    'action_type' => 'deadline_expired',
                    'target_type' => 'LudoBattle',
                    'target_id' => $locked->id,
                    'description' => "Submission deadline expired at {$deadlineStr} (Started at: {$startedStr}). {$scenarioDesc}",
                    'ip_address' => request()?->ip() ?? '127.0.0.1',
                ]);

                $updated = true;
            }

            // Sync attributes to current instance
            $this->status = $locked->status;
            $this->review_status = $locked->review_status;

            return $updated;
        });
    }

    /**
     * Batch process all running battles whose submission deadline has expired.
     */
    public static function processExpiredDeadlines(): int
    {
        $now = now();
        $expiredBattles = self::where('status', 'running')
            ->whereNotNull('submission_deadline_at')
            ->where('submission_deadline_at', '<=', $now)
            ->get();

        $count = 0;
        foreach ($expiredBattles as $b) {
            if ($b->checkAndProcessSubmissionExpiry()) {
                $count++;
            }
        }

        return $count;
    }
}
