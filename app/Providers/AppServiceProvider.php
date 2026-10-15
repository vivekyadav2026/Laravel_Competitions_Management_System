<?php

namespace App\Providers;

use App\Models\Competition;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('components.navbar', function ($view) {
            $competitions = Competition::get(['id', 'competition_name']);
            $view->with('competitions', $competitions);
        });

        // View Composer for Admin layout to inject real-time pending notification counts & list
        View::composer(['layouts.admin', 'admin.*'], function ($view) {
            try {
                $pendingDepositsCount = \Illuminate\Support\Facades\Schema::hasTable('ludo_upi_deposits')
                    ? \App\Models\LudoUpiDeposit::where('status', 'pending')->count()
                    : 0;

                $pendingWithdrawalsCount = \Illuminate\Support\Facades\Schema::hasTable('ludo_withdrawal_requests')
                    ? \App\Models\LudoWithdrawalRequest::where('status', 'pending')->count()
                    : 0;

                $pendingKycCount = \Illuminate\Support\Facades\Schema::hasTable('ludo_kyc_submissions')
                    ? \App\Models\LudoKycSubmission::where('status', 'pending')->count()
                    : 0;

                $pendingDisputesCount = \Illuminate\Support\Facades\Schema::hasTable('ludo_disputes')
                    ? \App\Models\LudoDispute::where('status', 'open')->count()
                    : 0;

                $pendingTicketsCount = \Illuminate\Support\Facades\Schema::hasTable('ludo_support_tickets')
                    ? \App\Models\LudoSupportTicket::where('status', 'open')->count()
                    : 0;

                $totalPendingNotifications = $pendingDepositsCount + $pendingWithdrawalsCount + $pendingKycCount + $pendingDisputesCount + $pendingTicketsCount;

                // Build collection of recent pending activities for the topbar notification dropdown
                $recentPendingActivities = collect();

                if ($pendingDepositsCount > 0) {
                    $deposits = \App\Models\LudoUpiDeposit::with('user')->where('status', 'pending')->latest('id')->take(4)->get();
                    foreach ($deposits as $d) {
                        $recentPendingActivities->push([
                            'id' => 'dep-' . $d->id,
                            'title' => '₹' . number_format($d->amount) . ' Deposit Pending',
                            'description' => 'UTR: ' . $d->utr_number . ' • ' . ($d->user->name ?? 'Player'),
                            'url' => route('admin.ludo.deposits.index'),
                            'icon' => 'fa-solid fa-qrcode text-success',
                            'badge' => 'Deposit',
                            'badge_class' => 'bg-success',
                            'created_at' => $d->created_at,
                        ]);
                    }
                }

                if ($pendingWithdrawalsCount > 0) {
                    $withdrawals = \App\Models\LudoWithdrawalRequest::with('user')->where('status', 'pending')->latest('id')->take(4)->get();
                    foreach ($withdrawals as $w) {
                        $recentPendingActivities->push([
                            'id' => 'wth-' . $w->id,
                            'title' => '₹' . number_format($w->amount) . ' Payout Settlement Pending',
                            'description' => ($w->upi_id ? 'UPI: ' . $w->upi_id : 'Bank: ' . $w->bank_account_number) . ' • ' . ($w->user->name ?? 'Player'),
                            'url' => route('admin.ludo.withdrawals.index'),
                            'icon' => 'fa-solid fa-money-bill-transfer text-warning',
                            'badge' => 'Payout',
                            'badge_class' => 'bg-warning text-dark',
                            'created_at' => $w->created_at,
                        ]);
                    }
                }

                if ($pendingKycCount > 0) {
                    $kycs = \App\Models\LudoKycSubmission::with('user')->where('status', 'pending')->latest('id')->take(4)->get();
                    foreach ($kycs as $k) {
                        $recentPendingActivities->push([
                            'id' => 'kyc-' . $k->id,
                            'title' => 'KYC Verification Pending',
                            'description' => ucfirst($k->document_type ?? 'Document') . ' • ' . ($k->user->name ?? 'Player'),
                            'url' => route('admin.ludo.kyc.index'),
                            'icon' => 'fa-solid fa-id-card text-primary',
                            'badge' => 'KYC',
                            'badge_class' => 'bg-primary',
                            'created_at' => $k->created_at,
                        ]);
                    }
                }

                if ($pendingDisputesCount > 0) {
                    $disputes = \App\Models\LudoDispute::with(['battle', 'opener'])->where('status', 'open')->latest('id')->take(4)->get();
                    foreach ($disputes as $disp) {
                        $recentPendingActivities->push([
                            'id' => 'disp-' . $disp->id,
                            'title' => 'Match Dispute Open',
                            'description' => ($disp->battle->battle_uid ?? 'Battle') . ' • ' . ($disp->opener->name ?? 'Player'),
                            'url' => route('admin.ludo.disputes.show', $disp->id),
                            'icon' => 'fa-solid fa-gavel text-danger',
                            'badge' => 'Dispute',
                            'badge_class' => 'bg-danger',
                            'created_at' => $disp->created_at,
                        ]);
                    }
                }

                if ($pendingTicketsCount > 0) {
                    $tickets = \App\Models\LudoSupportTicket::with('user')->where('status', 'open')->latest('id')->take(4)->get();
                    foreach ($tickets as $t) {
                        $recentPendingActivities->push([
                            'id' => 'tkt-' . $t->id,
                            'title' => 'Support Ticket #' . $t->ticket_uid,
                            'description' => \Illuminate\Support\Str::limit($t->subject ?? '', 28) . ' • ' . ($t->user->name ?? 'Player'),
                            'url' => route('admin.ludo.tickets.show', $t->id),
                            'icon' => 'fa-solid fa-headset text-info',
                            'badge' => 'Ticket',
                            'badge_class' => 'bg-info text-dark',
                            'created_at' => $t->created_at,
                        ]);
                    }
                }

                $recentPendingActivities = $recentPendingActivities->sortByDesc('created_at')->values()->take(10);

                $view->with([
                    'pendingDepositsCount' => $pendingDepositsCount,
                    'pendingWithdrawalsCount' => $pendingWithdrawalsCount,
                    'pendingKycCount' => $pendingKycCount,
                    'pendingDisputesCount' => $pendingDisputesCount,
                    'pendingTicketsCount' => $pendingTicketsCount,
                    'totalPendingNotifications' => $totalPendingNotifications,
                    'recentPendingActivities' => $recentPendingActivities,
                ]);
            } catch (\Throwable $e) {
                // Graceful fallback during early migration states
                $view->with([
                    'pendingDepositsCount' => 0,
                    'pendingWithdrawalsCount' => 0,
                    'pendingKycCount' => 0,
                    'pendingDisputesCount' => 0,
                    'pendingTicketsCount' => 0,
                    'totalPendingNotifications' => 0,
                    'recentPendingActivities' => collect(),
                ]);
            }
        });
    }
}
