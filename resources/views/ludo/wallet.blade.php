@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — My Wallet & Add Points')

@section('content')
    {{-- Floating Live Toast Notification Container (Top-Center just below header) --}}
    <div id="playerToastContainer" class="ludo-player-toast-container" style="position: fixed; top: calc(var(--ludo-header-height, 64px) + 12px); left: 50%; transform: translateX(-50%); z-index: 99999; width: min(440px, calc(100vw - 24px)); max-width: 94vw; pointer-events: none; box-sizing: border-box; display: flex; flex-direction: column; align-items: center;"></div>

    {{-- Sandbox / Demo Notice Banner (Shown Only When Virtual Points Reload Is Enabled) --}}
    @if(!empty($virtualPointsEnabled))
    <x-ludo.announcement>
        <span class="fw-bold">ℹ️ डेमो मोड सूचना:</span> सैंडबॉक्स/डेमो मोड सक्रिय है। टूर्नामेंट और प्रैक्टिस के लिए वर्चुअल पॉइंट्स नीचे से रीलोड कर सकते हैं।
    </x-ludo.announcement>
    @endif

    {{-- Total Balance Summary Card --}}
    <div class="ludo-refer-container p-3 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <span class="text-secondary small d-block">TOTAL WALLET BALANCE</span>
                <h3 class="fw-bold text-white mb-0">🪙 <span id="playerWalletTotalBalance">{{ number_format($coins ?? 0) }}</span> <span class="fs-6 text-info">Points</span></h3>
            </div>
            <img src="{{ asset('assets/ludo/coins-sack.svg') }}" alt="Coins" style="width: 48px; height: 48px;">
        </div>

        <div class="row g-2 text-center">
            <div class="col-6">
                <div class="p-2 rounded-3" style="background: rgba(14, 42, 92, 0.7); border: 1px solid #1d4ed8;">
                    <div class="text-secondary small" style="font-size: 10px;">AVAILABLE COINS</div>
                    <div class="fw-bold text-white fs-6">🪙 <span id="playerWalletAvailableBalance">{{ number_format($coins ?? 0) }}</span></div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 rounded-3" style="background: rgba(67, 27, 92, 0.7); border: 1px solid #7e22ce;">
                    <div class="text-secondary small" style="font-size: 10px;">REFERRAL COMMISSION</div>
                    <div class="fw-bold text-success fs-6">🪙 <span id="playerWalletReferralBalance">{{ number_format($referral ?? 0, 2) }}</span></div>
                </div>
            </div>
        </div>
    </div>

    @if($isUpiEnabled ?? false)
        {{-- Direct UPI QR Code Payment Card --}}
        <div class="ludo-amount-card mb-4" id="upiPaymentSection">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="ludo-amount-title mb-0 fs-5">
                    <i class="fa-solid fa-qrcode text-warning me-2"></i> Add Coins via Direct UPI QR
                </h2>
                <span class="badge bg-success text-white px-2 py-1" style="font-size: 9px; letter-spacing: 0.5px;">
                    0% FEES • DIRECT UPI
                </span>
            </div>

            {{-- Step 1: Select Amount --}}
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label text-light fw-bold small mb-0">1. Choose Amount to Add (₹)</label>
                    <span class="text-secondary small" style="font-size: 11px;">
                        Min: ₹{{ number_format($minDeposit ?? 50) }} &bull; Max: ₹{{ number_format($maxDeposit ?? 50000) }}
                    </span>
                </div>
                <div class="ludo-amount-input-box mb-2">
                    <div class="ludo-rupee-circle">&#8377;</div>
                    <div class="ludo-amount-input-meta">
                        <div class="ludo-amount-label">ENTER AMOUNT (₹)</div>
                        <input type="number" id="ludoDepositAmount" class="ludo-amount-input-field" value="{{ max($minDeposit ?? 50, 50) }}" min="{{ $minDeposit ?? 50 }}" max="{{ $maxDeposit ?? 50000 }}" step="50" required>
                    </div>
                    <div class="d-flex align-items-center gap-1 pe-1">
                        <button type="button" class="ludo-step-btn ludo-step-btn-minus" data-target="#ludoDepositAmount" data-step="-50" title="Decrease by ₹50">-50</button>
                        <button type="button" class="ludo-step-btn ludo-step-btn-plus" data-target="#ludoDepositAmount" data-step="50" title="Add ₹50">+50</button>
                    </div>
                </div>

                {{-- Amount Pills Grid (50, 100, 150, 200, 250, 300, 500, 1000) --}}
                <div class="ludo-amount-grid mb-3" id="upiAmountPills">
                    <button type="button" class="ludo-amount-pill active" data-amount="50">&#8377;50</button>
                    <button type="button" class="ludo-amount-pill" data-amount="100">&#8377;100</button>
                    <button type="button" class="ludo-amount-pill" data-amount="150">&#8377;150</button>
                    <button type="button" class="ludo-amount-pill" data-amount="200">&#8377;200</button>
                    <button type="button" class="ludo-amount-pill" data-amount="250">&#8377;250</button>
                    <button type="button" class="ludo-amount-pill" data-amount="300">&#8377;300</button>
                    <button type="button" class="ludo-amount-pill" data-amount="500">&#8377;500</button>
                    <button type="button" class="ludo-amount-pill" data-amount="1000">&#8377;1000</button>
                </div>
            </div>

            {{-- Step 2: QR Code & Payee Details Container --}}
            <div class="ludo-qr-container text-center p-3 rounded-3 mb-3" style="background: rgba(6, 12, 29, 0.95); border: 1.5px solid rgba(0, 210, 255, 0.35); box-shadow: 0 0 25px rgba(0, 210, 255, 0.15);">
                <div class="text-secondary small fw-bold mb-2 text-uppercase" style="letter-spacing: 0.5px;">
                    2. Scan &amp; Pay ₹<span id="qrAmountDisplay" class="text-warning fs-5 fw-bold">{{ max($minDeposit ?? 50, 50) }}</span>
                </div>

                <div class="ludo-qr-image-wrapper p-2 d-inline-flex align-items-center justify-content-center bg-white rounded-3 shadow-lg mb-2" style="min-width: 216px; min-height: 216px;">
                    @if(!empty($qrImage))
                        <img 
                            id="activeUpiQr" 
                            src="{{ \App\Services\FileUploadService::url($qrImage) }}" 
                            alt="Official UPI QR Code" 
                            style="width: 200px; height: 200px; object-fit: contain; display: block;"
                            onerror="this.style.display='none'; document.getElementById('dynamicUpiQrWrapper').style.display='block'; if(window.renderDynamicQr) window.renderDynamicQr();"
                        >
                    @endif
                    <div id="dynamicUpiQrWrapper" style="{{ !empty($qrImage) ? 'display: none;' : 'display: block;' }}">
                        <canvas id="upiQrCanvas" width="200" height="200" style="width: 200px; height: 200px; display: block; border-radius: 4px;"></canvas>
                        <img id="dynamicUpiQr" style="display: none; width: 200px; height: 200px;" alt="UPI Payment QR Code">
                    </div>
                </div>

                <div class="mb-2">
                    <span class="text-secondary small">Payee Name:</span>
                    <strong class="text-white small ms-1">{{ $payeeName ?? 'FSALUDOCLUB ENTERPRISES' }}</strong>
                </div>

                <div class="d-flex justify-content-center align-items-center gap-2 mb-2 text-secondary small" style="font-size: 11px;">
                    <span class="badge bg-dark border border-secondary border-opacity-50 text-light px-2 py-1">Paytm</span>
                    <span class="badge bg-dark border border-secondary border-opacity-50 text-light px-2 py-1">PhonePe</span>
                    <span class="badge bg-dark border border-secondary border-opacity-50 text-light px-2 py-1">Google Pay</span>
                    <span class="badge bg-dark border border-secondary border-opacity-50 text-light px-2 py-1">BHIM UPI</span>
                </div>

                <a 
                    id="mobileUpiIntentBtn" 
                    href="upi://pay?pa={{ $upiId ?? '9631608403@paytm' }}&pn={{ urlencode($payeeName ?? 'FSALUDOCLUB ENTERPRISES') }}&am={{ max($minDeposit ?? 50, 50) }}&cu=INR&tn=Deposit%20User%20{{ auth()->id() }}" 
                    class="btn btn-outline-info rounded-pill w-100 fw-bold py-2 mb-2 d-sm-none shadow-sm"
                >
                    <i class="fa-solid fa-mobile-screen-button me-2"></i> Pay Directly via UPI App
                </a>

                <div class="p-2 rounded-2 d-flex justify-content-between align-items-center" style="background: rgba(14, 42, 92, 0.6); border: 1px solid #1e3a8a;">
                    <div class="text-start">
                        <span class="text-secondary small d-block" style="font-size: 9.5px;">UPI ID / VPA</span>
                        <span class="text-warning font-monospace fw-bold small" id="upiIdText">{{ $upiId ?? '9631608403@paytm' }}</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-info rounded-pill px-3 py-1 fw-bold text-dark" id="btnCopyUpiId">
                        <i class="fa-solid fa-copy me-1"></i> <span id="copyUpiBtnText">Copy</span>
                    </button>
                </div>
            </div>

            {{-- Instructions Guide --}}
            @if(!empty($instructionsHi) || !empty($instructionsEn))
                <div class="p-2 rounded-3 mb-3 small" style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.08);">
                    <div class="fw-bold text-warning mb-1" style="font-size: 11.5px;">
                        <i class="fa-solid fa-circle-info me-1"></i> भुगतान निर्देश (Instructions):
                    </div>
                    @if(!empty($instructionsHi))
                        <div class="text-light opacity-90 small mb-1" style="white-space: pre-line; font-size: 11px;">{{ $instructionsHi }}</div>
                    @endif
                    @if(!empty($instructionsEn))
                        <div class="text-secondary small" style="white-space: pre-line; font-size: 11px;">{{ $instructionsEn }}</div>
                    @endif
                </div>
            @endif

            {{-- Step 3: UTR Submission Form --}}
            <form action="{{ route('ludo.wallet.deposit.upi') }}" method="POST" enctype="multipart/form-data" id="upiDepositSubmitForm">
                @csrf
                <input type="hidden" name="amount" id="formDepositAmount" value="{{ max($minDeposit ?? 50, 50) }}">

                <div class="mb-3">
                    <label class="form-label text-light fw-bold small">
                        3. Enter 12-Digit UTR / UPI Reference Number <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark text-warning border-secondary border-opacity-50">
                            <i class="fa-solid fa-receipt"></i>
                        </span>
                        <input 
                            type="text" 
                            name="utr_number" 
                            id="inputUtrNumber" 
                            class="form-control bg-dark text-light border-secondary border-opacity-50 font-monospace fw-bold" 
                            placeholder="e.g. 428912345678" 
                            required 
                            minlength="8" 
                            maxlength="25"
                            autocomplete="off"
                        >
                    </div>
                    <div class="text-secondary small mt-1" style="font-size: 11px;">
                        Found in your UPI app payment details (UTR / Transaction ID / Ref No).
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-light fw-bold small">
                        Payment Screenshot <span class="text-secondary fw-normal">(Optional for faster approval)</span>
                    </label>
                    <input type="file" name="screenshot" class="form-control bg-dark text-light border-secondary border-opacity-50 small" accept=".jpg,.jpeg,.png,.webp">
                </div>

                <button type="submit" class="ludo-btn-gold-next w-100 py-3 fw-bold fs-6" id="btnSubmitUtr">
                    <i class="fa-solid fa-paper-plane me-2"></i> Submit Payment Proof &amp; Add Coins
                </button>
            </form>
        </div>
    @endif

    {{-- Non-Cash Virtual Points Reload Card (Controlled via Admin Settings / Sandbox) --}}
    @if(!empty($virtualPointsEnabled) || (auth()->check() && in_array(auth()->user()->role, ['admin', 'superadmin'])))
    <div class="ludo-amount-card mb-4" id="virtualPointsSection">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="ludo-amount-title mb-0 fs-5">
                <i class="fa-solid fa-coins text-warning me-2"></i> Reload Virtual Tournament Points
            </h2>
            <span class="badge bg-info text-dark px-2 py-1 fw-bold" style="font-size: 9px;">
                100% FREE • NON-CASH
            </span>
        </div>

        <p class="small text-secondary mb-3">
            Demo/Sandbox Coin Reload: Reload virtual coins for testing practice and tournament battles. Real user deposits must be made via the official UPI QR code above.
        </p>

        <form action="{{ route('ludo.wallet.deposit') }}" method="POST" id="virtualPointsDepositForm">
            @csrf
            <div class="mb-3">
                <label class="form-label text-light fw-bold small">Select or Enter Points Amount (🪙)</label>
                <div class="ludo-amount-input-box mb-2">
                    <div class="ludo-rupee-circle">🪙</div>
                    <div class="ludo-amount-input-meta">
                        <div class="ludo-amount-label">VIRTUAL POINTS</div>
                        <input type="number" name="amount" id="ludoVirtualAmount" class="ludo-amount-input-field" value="50" min="50" max="50000" step="50" required>
                    </div>
                    <div class="d-flex align-items-center gap-1 pe-1">
                        <button type="button" class="ludo-step-btn ludo-step-btn-minus" data-target="#ludoVirtualAmount" data-step="-50" title="Decrease by 50">-50</button>
                        <button type="button" class="ludo-step-btn ludo-step-btn-plus" data-target="#ludoVirtualAmount" data-step="50" title="Add 50">+50</button>
                    </div>
                </div>

                {{-- Amount Pills Grid (50, 100, 150, 200, 250, 300, 500, 1000) --}}
                <div class="ludo-amount-grid mb-3" id="virtualAmountPills">
                    <button type="button" class="ludo-amount-pill active" data-amount="50">🪙50</button>
                    <button type="button" class="ludo-amount-pill" data-amount="100">🪙100</button>
                    <button type="button" class="ludo-amount-pill" data-amount="150">🪙150</button>
                    <button type="button" class="ludo-amount-pill" data-amount="200">🪙200</button>
                    <button type="button" class="ludo-amount-pill" data-amount="250">🪙250</button>
                    <button type="button" class="ludo-amount-pill" data-amount="300">🪙300</button>
                    <button type="button" class="ludo-amount-pill" data-amount="500">🪙500</button>
                    <button type="button" class="ludo-amount-pill" data-amount="1000">🪙1,000</button>
                </div>
            </div>

            <button type="submit" class="ludo-btn-gold-next w-100 py-3 fw-bold fs-6" id="btnDepositVirtual">
                <i class="fa-solid fa-plus-circle me-1"></i> Add Virtual Points
            </button>
        </form>
    </div>
    @endif

    {{-- PART 8: PLAYER PAYMENT REQUEST HISTORY SECTION --}}
    <div class="mb-4" id="userPaymentRequestsSection" style="{{ (isset($paymentRequests) && $paymentRequests->count() > 0) ? '' : 'display: none;' }}">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-white mb-0 fs-6">
                <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i> My Payment Requests
            </h5>
            <span class="badge bg-dark border border-secondary text-secondary" id="userPaymentRequestsCountBadge" style="font-size: 10px;">
                {{ isset($paymentRequests) ? $paymentRequests->count() : 0 }} Requests
            </span>
        </div>

        <div id="userPaymentRequestsList">
            @if(isset($paymentRequests))
                @foreach($paymentRequests as $dep)
                    @php
                        $statusBadge = match($dep->status) {
                            'approved' => 'bg-success text-white',
                            'rejected' => 'bg-danger text-white',
                            default => 'bg-warning text-dark'
                        };
                        $statusLabel = match($dep->status) {
                            'approved' => 'Approved & Credited',
                            'rejected' => 'Rejected',
                            default => 'Pending Verification'
                        };
                        $statusIcon = match($dep->status) {
                            'approved' => 'fa-circle-check',
                            'rejected' => 'fa-circle-xmark',
                            default => 'fa-clock'
                        };
                    @endphp
                    <div class="p-3 rounded-3 mb-2 player-deposit-card" id="playerDepositCard_{{ $dep->id }}" data-id="{{ $dep->id }}" data-status="{{ $dep->status }}" style="background: rgba(14, 25, 51, 0.85); border: 1px solid {{ $dep->status === 'approved' ? 'rgba(34, 197, 94, 0.35)' : ($dep->status === 'rejected' ? 'rgba(239, 68, 68, 0.35)' : 'rgba(234, 179, 8, 0.35)') }};">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div>
                                <span class="fw-bold text-white fs-6">🪙 {{ number_format($dep->amount) }} Coins</span>
                                <span class="text-secondary small ms-1">(₹{{ number_format($dep->amount) }})</span>
                            </div>
                            <span class="badge {{ $statusBadge }} d-inline-flex align-items-center gap-1 status-badge" style="font-size: 10px;">
                                <i class="fa-solid {{ $statusIcon }}"></i> <span class="status-label-text">{{ $statusLabel }}</span>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center text-secondary small" style="font-size: 11px;">
                            <span>
                                UTR: <strong class="font-monospace text-info">{{ $dep->utr_number }}</strong> &bull;
                                <span class="text-secondary">{{ $dep->deposit_uid }}</span>
                            </span>
                            <span>{{ $dep->created_at ? $dep->created_at->format('d M, h:i A') : 'Recent' }}</span>
                        </div>

                        {{-- Status messages --}}
                        <div class="status-notes-container">
                            @if($dep->status === 'rejected' && !empty($dep->admin_notes))
                                <div class="mt-2 p-2 rounded small text-danger" style="background: rgba(220, 53, 69, 0.15); border: 1px solid rgba(220, 53, 69, 0.3); font-size: 11.5px;">
                                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                                    <strong>Rejection Reason:</strong> {{ $dep->admin_notes }}
                                </div>
                            @elseif($dep->status === 'approved')
                                <div class="mt-1 small text-success" style="font-size: 11px;">
                                    <i class="fa-solid fa-check-double me-1"></i> Confirmed &amp; credited to wallet ledger by platform admin.
                                </div>
                            @else
                                <div class="mt-1 small text-warning" style="font-size: 11px;">
                                    <i class="fa-solid fa-spinner fa-spin me-1"></i> Awaiting admin verification. Typically verified within 5-15 minutes.
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- PART 9: PLAYER WITHDRAWAL & SETTLEMENT SECTION --}}
    <div class="ludo-amount-card mb-4" id="withdrawalSection">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="ludo-amount-title mb-0 fs-5">
                <i class="fa-solid fa-money-bill-transfer text-warning me-2"></i> Withdraw Coins (पेआउट सेटलमेंट)
            </h2>
            <span class="badge bg-success text-white px-2 py-1" style="font-size: 9px; letter-spacing: 0.5px;">
                FAST PAYOUT • 0% DEDUCTION
            </span>
        </div>

        <p class="small text-secondary mb-3">
            अपने जीते हुए कॉइन्स सीधे अपने UPI ID (PhonePe, GPay, Paytm) या बैंक खाते में ट्रांसफर करें। एडमिन द्वारा सत्यापन के बाद 15-30 मिनट में भुगतान सेटल कर दिया जाता है।
        </p>

        <form action="{{ route('ludo.wallet.withdraw') }}" method="POST" id="withdrawalSubmitForm">
            @csrf

            {{-- 1. Select / Enter Withdrawal Amount --}}
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label text-light fw-bold small mb-0">1. Enter Withdrawal Amount (₹)</label>
                    <span class="text-secondary small" style="font-size: 11px;">
                        Available: <strong class="text-warning">🪙 {{ number_format($coins ?? 0) }}</strong> &bull; Min: ₹{{ number_format($minWithdrawal ?? 100) }}
                    </span>
                </div>
                <div class="ludo-amount-input-box mb-2">
                    <div class="ludo-rupee-circle">&#8377;</div>
                    <div class="ludo-amount-input-meta">
                        <div class="ludo-amount-label">WITHDRAWAL AMOUNT (₹)</div>
                        <input type="number" name="amount" id="ludoWithdrawalAmount" class="ludo-amount-input-field" value="{{ max($minWithdrawal ?? 100, 100) }}" min="{{ $minWithdrawal ?? 100 }}" max="{{ min(max($coins ?? 50000, 100), $maxWithdrawal ?? 50000) }}" step="50" required>
                    </div>
                    <div class="d-flex align-items-center gap-1 pe-1">
                        <button type="button" class="ludo-step-btn" onclick="adjustWithdrawAmount(-50)">-50</button>
                        <button type="button" class="ludo-step-btn" onclick="adjustWithdrawAmount(50)">+50</button>
                    </div>
                </div>

                {{-- Amount Pills Grid (100, 200, 500, 1000, 2000, 5000) --}}
                <div class="ludo-amount-grid mb-3" id="withdrawAmountPills">
                    <button type="button" class="ludo-amount-pill active" onclick="setWithdrawAmount(100)">&#8377;100</button>
                    <button type="button" class="ludo-amount-pill" onclick="setWithdrawAmount(200)">&#8377;200</button>
                    <button type="button" class="ludo-amount-pill" onclick="setWithdrawAmount(500)">&#8377;500</button>
                    <button type="button" class="ludo-amount-pill" onclick="setWithdrawAmount(1000)">&#8377;1,000</button>
                    <button type="button" class="ludo-amount-pill" onclick="setWithdrawAmount(2000)">&#8377;2,000</button>
                    <button type="button" class="ludo-amount-pill" onclick="setWithdrawAmount(5000)">&#8377;5,000</button>
                </div>
            </div>

            {{-- 2. Select Payout Method --}}
            <div class="mb-3">
                <label class="form-label text-light fw-bold small mb-2">2. Choose Payout Destination</label>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="p-2 rounded-3 d-flex align-items-center gap-2 border w-100 cursor-pointer" style="background: rgba(14, 42, 92, 0.6); border-color: #3b82f6 !important;" id="labelPayoutUpi">
                            <input type="radio" name="payout_method" value="upi" checked onchange="togglePayoutMethod('upi')" class="form-check-input m-0">
                            <div class="text-start">
                                <div class="text-white small fw-bold"><i class="fa-solid fa-mobile-screen text-info me-1"></i> UPI ID (Instant)</div>
                                <span class="text-secondary" style="font-size: 10px;">GPay, PhonePe, Paytm</span>
                            </div>
                        </label>
                    </div>
                    <div class="col-6">
                        <label class="p-2 rounded-3 d-flex align-items-center gap-2 border w-100 cursor-pointer" style="background: rgba(15, 23, 42, 0.6); border-color: rgba(255, 255, 255, 0.1) !important;" id="labelPayoutBank">
                            <input type="radio" name="payout_method" value="bank_transfer" onchange="togglePayoutMethod('bank')" class="form-check-input m-0">
                            <div class="text-start">
                                <div class="text-white small fw-bold"><i class="fa-solid fa-building-columns text-warning me-1"></i> Bank Account</div>
                                <span class="text-secondary" style="font-size: 10px;">IMPS / NEFT</span>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- UPI Field Container --}}
                <div id="payoutUpiContainer">
                    <label class="form-label text-secondary small fw-semibold">Your Active UPI ID / VPA <span class="text-danger">*</span></label>
                    <div class="input-group mb-1">
                        <span class="input-group-text bg-dark text-info border-secondary border-opacity-50">
                            <i class="fa-solid fa-at"></i>
                        </span>
                        <input 
                            type="text" 
                            name="upi_id" 
                            id="inputPayoutUpiId" 
                            class="form-control bg-dark text-light border-secondary border-opacity-50 font-monospace" 
                            placeholder="e.g. 9876543210@paytm or name@okaxis" 
                            value="{{ old('upi_id') }}"
                        >
                    </div>
                    <div class="text-secondary small" style="font-size: 11px;">
                        Ensure this UPI ID is active and correctly linked to your bank account.
                    </div>
                </div>

                {{-- Bank Account Container (Hidden by default) --}}
                <div id="payoutBankContainer" style="display: none;">
                    <div class="mb-2">
                        <label class="form-label text-secondary small">Account Holder Name <span class="text-danger">*</span></label>
                        <input type="text" name="account_holder_name" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Full name as in passbook">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-7">
                            <label class="form-label text-secondary small">Account Number <span class="text-danger">*</span></label>
                            <input type="text" name="bank_account_number" class="form-control form-control-sm bg-dark text-light border-secondary font-monospace" placeholder="Bank Account No.">
                        </div>
                        <div class="col-5">
                            <label class="form-label text-secondary small">IFSC Code <span class="text-danger">*</span></label>
                            <input type="text" name="bank_ifsc" class="form-control form-control-sm bg-dark text-light border-secondary font-monospace" placeholder="SBIN0001234">
                        </div>
                    </div>
                    <div>
                        <label class="form-label text-secondary small">Bank Name (Optional)</label>
                        <input type="text" name="bank_name" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="e.g. State Bank of India">
                    </div>
                </div>
            </div>

            <button type="submit" class="ludo-btn-gold-next w-100 py-3 fw-bold fs-6" id="btnSubmitWithdrawal">
                <i class="fa-solid fa-paper-plane me-2"></i> Submit Withdrawal Request (पैसे निकालें)
            </button>
        </form>
    </div>

    {{-- PART 10: PLAYER WITHDRAWAL HISTORY SECTION --}}
    @if(isset($withdrawalRequests) && $withdrawalRequests->count() > 0)
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-white mb-0 fs-6">
                    <i class="fa-solid fa-money-bill-transfer text-warning me-1"></i> My Withdrawal Requests (निकासी इतिहास)
                </h5>
                <span class="badge bg-dark border border-secondary text-secondary" style="font-size: 10px;">
                    {{ $withdrawalRequests->count() }} Payouts
                </span>
            </div>

            @foreach($withdrawalRequests as $wth)
                @php
                    $wStatusBadge = match($wth->status) {
                        'approved' => 'bg-success text-white',
                        'rejected' => 'bg-danger text-white',
                        default => 'bg-warning text-dark'
                    };
                    $wStatusLabel = match($wth->status) {
                        'approved' => 'Settled & Paid (खाते में भेजा गया)',
                        'rejected' => 'Rejected & Refunded (अस्वीकृत/वापस)',
                        default => 'Pending Verification (प्रतीक्षारत)'
                    };
                    $wStatusIcon = match($wth->status) {
                        'approved' => 'fa-circle-check',
                        'rejected' => 'fa-circle-xmark',
                        default => 'fa-clock'
                    };
                @endphp
                <div class="p-3 rounded-3 mb-2" style="background: rgba(14, 25, 51, 0.85); border: 1px solid {{ $wth->status === 'approved' ? 'rgba(34, 197, 94, 0.35)' : ($wth->status === 'rejected' ? 'rgba(239, 68, 68, 0.35)' : 'rgba(234, 179, 8, 0.35)') }};">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <span class="fw-bold text-white fs-6">₹{{ number_format($wth->amount) }}</span>
                            <span class="text-secondary small ms-1">(🪙 {{ number_format($wth->amount) }} Coins)</span>
                        </div>
                        <span class="badge {{ $wStatusBadge }} d-inline-flex align-items-center gap-1" style="font-size: 10px;">
                            <i class="fa-solid {{ $wStatusIcon }}"></i> {{ $wStatusLabel }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center text-secondary small" style="font-size: 11px;">
                        <span>
                            To: <strong class="font-monospace text-info">{{ $wth->upi_id ?? $wth->bank_account_number }}</strong> &bull;
                            <span class="text-secondary">{{ $wth->withdrawal_uid }}</span>
                        </span>
                        <span>{{ $wth->created_at ? $wth->created_at->format('d M, h:i A') : 'Recent' }}</span>
                    </div>

                    @if($wth->status === 'approved')
                        <div class="mt-2 p-2 rounded small text-success" style="background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(34, 197, 94, 0.25); font-size: 11px;">
                            <i class="fa-solid fa-check-double me-1"></i>
                            <strong>Payment Settled:</strong> Transferred to your UPI. Ref/UTR: <strong class="font-monospace">{{ $wth->admin_utr_reference ?? 'Verified' }}</strong>
                        </div>
                    @elseif($wth->status === 'rejected')
                        <div class="mt-2 p-2 rounded small text-danger" style="background: rgba(220, 53, 69, 0.15); border: 1px solid rgba(220, 53, 69, 0.3); font-size: 11px;">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>
                            <strong>Rejection Reason:</strong> {{ $wth->admin_notes }} &bull; Coins safely refunded to wallet.
                        </div>
                    @else
                        <div class="mt-1 small text-warning" style="font-size: 11px;">
                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Admin verification in progress. Funds sent via UPI within 15-30 minutes.
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Recent Transaction Ledger Cards --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-white mb-0 fs-6">
                <i class="fa-solid fa-receipt text-info me-1"></i> Points History
            </h5>
            <a href="{{ url('/history') }}" class="small text-info fw-bold text-decoration-none" style="font-size: 11px;">
                View All &rarr;
            </a>
        </div>

        @forelse($transactions as $tx)
            @php
                $statusClass = match($tx->status) {
                    'successful' => 'status-success',
                    'pending' => 'bg-warning text-dark',
                    'expired' => 'bg-secondary text-white',
                    default => 'status-failed'
                };
                $borderColor = match($tx->status) {
                    'successful' => 'rgba(34, 197, 94, 0.5)',
                    'pending' => 'rgba(234, 179, 8, 0.5)',
                    default => 'rgba(239, 68, 68, 0.5)'
                };
            @endphp
            <div class="ludo-history-card mb-2" style="border-color: {{ $borderColor }};">
                <div class="ludo-history-card-status {{ $statusClass }}">
                    {{ strtoupper($tx->status) }}
                </div>
                <div class="ludo-history-card-body">
                    <div class="ludo-history-info-left">
                        <div class="ludo-history-info-row">
                            <span class="label">Type:</span>
                            <span class="text-white text-capitalize">{{ str_replace('_', ' ', $tx->type) }}</span>
                        </div>
                        <div class="ludo-history-info-row">
                            <span class="label">Ref:</span>
                            <span class="text-info font-monospace">{{ $tx->tx_uid }}</span>
                        </div>
                        <div class="small text-secondary" style="font-size: 11px;">
                            {{ $tx->description }}
                        </div>
                    </div>

                    <div class="ludo-history-badge">
                        <span class="{{ $tx->points_delta >= 0 ? 'text-success' : 'text-danger' }} fw-bold fs-6">
                            {{ $tx->points_delta >= 0 ? '+' : '' }}{{ number_format($tx->points_delta) }}
                        </span>
                        <span class="fs-6">&#129689;</span>
                    </div>
                </div>
                <div class="ludo-history-card-footer justify-content-between">
                    <span>&#128197; {{ $tx->created_at ? $tx->created_at->format('d M Y @ h:i A') : 'Recent' }}</span>
                    <span>Bal After: 🪙 {{ number_format($tx->balance_after) }}</span>
                </div>
            </div>
        @empty
            <x-ludo.empty-state 
                title="No Transactions Recorded"
                message="You haven't made any point reloads or match entries yet. Choose an amount above to reload your tournament points."
            />
        @endforelse

        @if($transactions->hasPages())
            <div class="d-flex justify-content-center my-3 ludo-pagination-wrapper">
                {{ $transactions->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script src="{{ versioned_asset('js/qrcode.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const upiId = "{{ $upiId ?? '7303859007@paytm' }}";
            const payeeName = "{{ $payeeName ?? 'FSALUDOCLUB' }}";
            const userId = "{{ auth()->id() ?? '' }}";

            const virtualAmountInput = document.getElementById('ludoVirtualAmount');
            const amountInput = document.getElementById('ludoDepositAmount');
            const formAmountInput = document.getElementById('formDepositAmount');
            const qrAmountDisplay = document.getElementById('qrAmountDisplay');
            const qrImg = document.getElementById('dynamicUpiQr');
            const qrCanvas = document.getElementById('upiQrCanvas');
            const mobileIntentBtn = document.getElementById('mobileUpiIntentBtn');
            const pills = document.querySelectorAll('.ludo-amount-pill');

            window.renderDynamicQr = function(amount) {
                const num = parseInt(amount || (amountInput ? amountInput.value : 50), 10);
                const safeAmt = (!isNaN(num) && num >= 1) ? num : 50;
                const upiUri = `upi://pay?pa=${encodeURIComponent(upiId)}&pn=${encodeURIComponent(payeeName)}&am=${safeAmt}&cu=INR&tn=Deposit%20User%20${userId}`;

                if (window.QRCode && qrCanvas) {
                    try {
                        window.QRCode.toCanvas(qrCanvas, upiUri, {
                            width: 200,
                            margin: 1,
                            color: {
                                dark: '#000000',
                                light: '#ffffff'
                            }
                        }, function(error) {
                            if (error) {
                                console.warn('Canvas QR render error, using fallback DataURL', error);
                                if (window.QRCode.toDataURL) {
                                    window.QRCode.toDataURL(upiUri, { width: 200, margin: 1 }, function(err, url) {
                                        if (!err && qrImg) {
                                            qrCanvas.style.display = 'none';
                                            qrImg.src = url;
                                            qrImg.style.display = 'block';
                                        }
                                    });
                                }
                            } else {
                                qrCanvas.style.display = 'block';
                                if (qrImg) qrImg.style.display = 'none';
                            }
                        });
                    } catch (e) {
                        console.error('QR code generation error:', e);
                    }
                } else if (qrImg) {
                    qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=${encodeURIComponent(upiUri)}`;
                    qrImg.style.display = 'block';
                }
            };

            function updateQrAndLinks(amount) {
                const num = parseInt(amount, 10);
                const safeAmt = (!isNaN(num) && num >= 1) ? num : 50;
                
                if (formAmountInput) formAmountInput.value = safeAmt;
                if (qrAmountDisplay) qrAmountDisplay.textContent = safeAmt;

                const upiUri = `upi://pay?pa=${encodeURIComponent(upiId)}&pn=${encodeURIComponent(payeeName)}&am=${safeAmt}&cu=INR&tn=Deposit%20User%20${userId}`;
                
                window.renderDynamicQr(safeAmt);

                if (mobileIntentBtn) {
                    mobileIntentBtn.href = upiUri;
                }
            }

            // Initial render
            window.renderDynamicQr(amountInput ? amountInput.value : 50);

            // Sync active pill in card based on amount
            function syncCardPills(card, val) {
                if (!card) return;
                const intVal = parseInt(val, 10);
                card.querySelectorAll('.ludo-amount-pill').forEach(p => {
                    if (parseInt(p.dataset.amount, 10) === intVal) {
                        p.classList.add('active');
                    } else {
                        p.classList.remove('active');
                    }
                });
            }

            // Step buttons (+50 / -50)
            document.querySelectorAll('.ludo-step-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetSelector = this.dataset.target;
                    const step = parseInt(this.dataset.step, 10) || 50;
                    const input = document.querySelector(targetSelector);
                    if (!input) return;

                    const min = parseInt(input.min, 10) || 50;
                    const max = parseInt(input.max, 10) || 50000;
                    let currentVal = parseInt(input.value, 10);
                    if (isNaN(currentVal) || currentVal < min) currentVal = min;

                    let newVal = currentVal + step;
                    if (newVal < min) newVal = min;
                    if (newVal > max) newVal = max;

                    input.value = newVal;

                    const card = input.closest('.ludo-amount-card');
                    syncCardPills(card, newVal);

                    if (input === amountInput) {
                        updateQrAndLinks(newVal);
                    }
                });
            });

            // UPI Pills
            document.querySelectorAll('#upiAmountPills .ludo-amount-pill').forEach(pill => {
                pill.addEventListener('click', function() {
                    const card = this.closest('.ludo-amount-card');
                    const amt = parseInt(this.dataset.amount, 10);
                    if (amountInput) amountInput.value = amt;
                    syncCardPills(card, amt);
                    updateQrAndLinks(amt);
                });
            });

            // Virtual Pills
            document.querySelectorAll('#virtualAmountPills .ludo-amount-pill').forEach(pill => {
                pill.addEventListener('click', function() {
                    const card = this.closest('.ludo-amount-card');
                    const amt = parseInt(this.dataset.amount, 10);
                    if (virtualAmountInput) virtualAmountInput.value = amt;
                    syncCardPills(card, amt);
                });
            });

            if (amountInput) {
                amountInput.addEventListener('input', function() {
                    const card = this.closest('.ludo-amount-card');
                    syncCardPills(card, this.value);
                    updateQrAndLinks(this.value);
                });
            }

            if (virtualAmountInput) {
                virtualAmountInput.addEventListener('input', function() {
                    const card = this.closest('.ludo-amount-card');
                    syncCardPills(card, this.value);
                });
            }

            // Copy UPI ID button
            const copyBtn = document.getElementById('btnCopyUpiId');
            const copyText = document.getElementById('copyUpiBtnText');
            if (copyBtn) {
                copyBtn.addEventListener('click', function() {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(upiId).then(() => {
                            copyText.textContent = 'Copied!';
                            copyBtn.classList.remove('btn-info');
                            copyBtn.classList.add('btn-success');
                            setTimeout(() => {
                                copyText.textContent = 'Copy';
                                copyBtn.classList.remove('btn-success');
                                copyBtn.classList.add('btn-info');
                            }, 2500);
                        });
                    }
                });
            }

            // Native Web Audio Synthesizer Chime for Player Wallet (No external asset downloads)
            function playWalletChime(type) {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    const ctx = new AudioCtx();
                    if (ctx.state === 'suspended') ctx.resume();
                    const now = ctx.currentTime;

                    if (type === 'celebrate') {
                        // 3-tone ascending victory arpeggio (C5 523Hz -> E5 659.25Hz -> G5 783.99Hz)
                        [523.25, 659.25, 783.99].forEach((freq, idx) => {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.type = 'triangle';
                            const noteTime = now + (idx * 0.12);
                            osc.frequency.setValueAtTime(freq, noteTime);
                            gain.gain.setValueAtTime(0.2, noteTime);
                            gain.gain.exponentialRampToValueAtTime(0.001, noteTime + 0.35);
                            osc.start(noteTime);
                            osc.stop(noteTime + 0.35);
                        });
                    } else {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(440, now);
                        gain.gain.setValueAtTime(0.15, now);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                        osc.start(now);
                        osc.stop(now + 0.3);
                    }
                } catch (e) {}
            }

            // Player In-App Floating Toast Notification (Top-Center just below header)
            function showWalletToast(title, message, type) {
                let container = document.getElementById('playerToastContainer');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'playerToastContainer';
                    container.className = 'ludo-player-toast-container';
                    container.style.position = 'fixed';
                    container.style.top = 'calc(var(--ludo-header-height, 64px) + 12px)';
                    container.style.left = '50%';
                    container.style.transform = 'translateX(-50%)';
                    container.style.zIndex = '99999';
                    container.style.width = 'min(440px, calc(100vw - 24px))';
                    container.style.maxWidth = '94vw';
                    container.style.pointerEvents = 'none';
                    container.style.boxSizing = 'border-box';
                    container.style.display = 'flex';
                    container.style.flexDirection = 'column';
                    container.style.alignItems = 'center';
                    document.body.appendChild(container);
                }

                const toastId = 'p_toast_' + Date.now();
                const borderColor = type === 'success' ? '#22c55e' : (type === 'danger' ? '#ef4444' : '#eab308');
                const glowColor = type === 'success' ? 'rgba(34, 197, 94, 0.35)' : (type === 'danger' ? 'rgba(239, 68, 68, 0.35)' : 'rgba(234, 179, 8, 0.35)');
                const icon = type === 'success' ? '🎉' : (type === 'danger' ? '❌' : 'ℹ️');

                const html = `
                    <div id="${toastId}" class="p-3 mb-2 rounded-3 text-white shadow-lg d-flex align-items-start gap-2 ludo-player-toast" style="width: 100%; background: rgba(10, 18, 38, 0.96); border: 1.5px solid ${borderColor}; backdrop-filter: blur(12px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.85), 0 0 16px ${glowColor}; pointer-events: auto; animation: playerToastSlideDown 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;">
                        <div class="fs-5">${icon}</div>
                        <div class="flex-grow-1 min-w-0">
                            <strong class="d-block small text-white">${title}</strong>
                            <span class="small text-light opacity-90" style="font-size: 11.5px;">${message}</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white btn-sm ms-auto" aria-label="Close" onclick="dismissWalletToast('${toastId}')"></button>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', html);
                setTimeout(() => {
                    dismissWalletToast(toastId);
                }, 6500);
            }
            window.showWalletToast = showWalletToast;
            window.dismissWalletToast = dismissWalletToast;

            function dismissWalletToast(id) {
                const el = document.getElementById(id);
                if (el) {
                    el.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-12px)';
                    setTimeout(() => el.remove(), 350);
                }
            }

            // AJAX submission for UPI Deposit Form (instant response, no full reload)
            const upiForm = document.getElementById('upiDepositSubmitForm');
            const submitUtrBtn = document.getElementById('btnSubmitUtr');

            if (upiForm && submitUtrBtn) {
                upiForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    submitUtrBtn.disabled = true;
                    submitUtrBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Submitting Proof...';

                    const formData = new FormData(upiForm);

                    try {
                        const res = await fetch(upiForm.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });

                        const data = await res.json();

                        if (res.ok && data.success) {
                            showWalletToast('Request Submitted!', data.message, 'success');
                            playWalletChime('notice');

                            // Reset inputs
                            const utrInput = document.getElementById('inputUtrNumber');
                            if (utrInput) utrInput.value = '';
                            const fileInput = upiForm.querySelector('input[type="file"]');
                            if (fileInput) fileInput.value = '';

                            // Prepend card to list
                            const requestsSection = document.getElementById('userPaymentRequestsSection');
                            const requestsList = document.getElementById('userPaymentRequestsList');
                            if (requestsSection) requestsSection.style.display = 'block';

                            if (requestsList && data.deposit) {
                                const cardId = 'playerDepositCard_' + data.deposit.id;
                                const isAppr = data.deposit.status === 'approved';
                                const cardHtml = `
                                    <div class="p-3 rounded-3 mb-2 player-deposit-card" id="${cardId}" data-id="${data.deposit.id}" data-status="${data.deposit.status}" style="background: rgba(14, 25, 51, 0.85); border: 1px solid ${isAppr ? 'rgba(34, 197, 94, 0.35)' : 'rgba(234, 179, 8, 0.35)'};">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div>
                                                <span class="fw-bold text-white fs-6">🪙 ${Number(data.deposit.amount).toLocaleString()} Coins</span>
                                                <span class="text-secondary small ms-1">(₹${Number(data.deposit.amount).toLocaleString()})</span>
                                            </div>
                                            <span class="badge ${isAppr ? 'bg-success text-white' : 'bg-warning text-dark'} d-inline-flex align-items-center gap-1 status-badge" style="font-size: 10px;">
                                                <i class="fa-solid ${isAppr ? 'fa-circle-check' : 'fa-clock'}"></i>
                                                <span class="status-label-text">${isAppr ? 'Approved & Credited' : 'Pending Verification'}</span>
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center text-secondary small" style="font-size: 11px;">
                                            <span>
                                                UTR: <strong class="font-monospace text-info">${data.deposit.utr_number}</strong> &bull;
                                                <span class="text-secondary">${data.deposit.deposit_uid}</span>
                                            </span>
                                            <span>Just now</span>
                                        </div>
                                        <div class="status-notes-container">
                                            <div class="mt-1 small ${isAppr ? 'text-success' : 'text-warning'}" style="font-size: 11px;">
                                                ${isAppr ? '<i class="fa-solid fa-check-double me-1"></i> Credited to wallet.' : '<i class="fa-solid fa-spinner fa-spin me-1"></i> Awaiting admin verification.'}
                                            </div>
                                        </div>
                                    </div>
                                `;
                                requestsList.insertAdjacentHTML('afterbegin', cardHtml);
                            }

                            // Trigger immediate sync
                            pollWalletSyncState();
                        } else {
                            showWalletToast('Submission Error', data.message || 'Could not submit payment request.', 'danger');
                        }
                    } catch (err) {
                        showWalletToast('Network Error', 'Please check your connection and try again.', 'danger');
                    } finally {
                        submitUtrBtn.disabled = false;
                        submitUtrBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i> Submit Payment Proof & Add Coins';
                    }
                });
            }

            // Real-Time Polling for Player Wallet Balance & Payment Approval/Rejection
            let lastKnownCoins = {{ (int) ($coins ?? 0) }};
            const knownDepositStatuses = {};
            document.querySelectorAll('.player-deposit-card').forEach(card => {
                const depId = card.getAttribute('data-id');
                const depStatus = card.getAttribute('data-status');
                if (depId && depStatus) knownDepositStatuses[depId] = depStatus;
            });

            let isSyncingWallet = false;
            async function pollWalletSyncState() {
                if (document.hidden || isSyncingWallet) return;
                isSyncingWallet = true;

                try {
                    const res = await fetch("{{ route('ludo.wallet.syncState') }}?_t=" + Date.now(), {
                        cache: 'no-store',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Cache-Control': 'no-cache, no-store, must-revalidate',
                            'Pragma': 'no-cache'
                        }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.authenticated) {
                            const newCoins = Number(data.coins);

                            // 1. Balance Update Detection
                            if (newCoins !== lastKnownCoins) {
                                lastKnownCoins = newCoins;
                                const formatted = newCoins.toLocaleString();

                                // Update Wallet Page Displays
                                const totalBalEl = document.getElementById('playerWalletTotalBalance');
                                if (totalBalEl) totalBalEl.textContent = formatted;
                                const availBalEl = document.getElementById('playerWalletAvailableBalance');
                                if (availBalEl) availBalEl.textContent = formatted;

                                // Update Topbar & Drawer Balances
                                document.querySelectorAll('.user-coins-display').forEach(el => {
                                    el.textContent = formatted;
                                });
                            }

                            // 2. Deposit Approval/Rejection State Changes
                            if (Array.isArray(data.recent_deposits)) {
                                data.recent_deposits.forEach(dep => {
                                    const prevStatus = knownDepositStatuses[dep.id];
                                    const card = document.getElementById('playerDepositCard_' + dep.id);

                                    if (prevStatus === 'pending' && dep.status === 'approved') {
                                        // 🎉 Transitioned to APPROVED!
                                        knownDepositStatuses[dep.id] = 'approved';
                                        playWalletChime('celebrate');
                                        showWalletToast(
                                            'Deposit Approved!',
                                            `₹${Number(dep.amount).toLocaleString()} coins credited to your wallet. Balance updated!`,
                                            'success'
                                        );

                                        if (card) {
                                            card.setAttribute('data-status', 'approved');
                                            card.style.borderColor = 'rgba(34, 197, 94, 0.4)';
                                            const badge = card.querySelector('.status-badge');
                                            if (badge) {
                                                badge.className = 'badge bg-success text-white d-inline-flex align-items-center gap-1 status-badge';
                                                badge.innerHTML = '<i class="fa-solid fa-circle-check"></i> <span class="status-label-text">Approved & Credited</span>';
                                            }
                                            const notes = card.querySelector('.status-notes-container');
                                            if (notes) {
                                                notes.innerHTML = '<div class="mt-1 small text-success" style="font-size: 11px;"><i class="fa-solid fa-check-double me-1"></i> Confirmed & credited to wallet ledger by platform admin.</div>';
                                            }
                                        }
                                    } else if (prevStatus === 'pending' && dep.status === 'rejected') {
                                        // ⚠️ Transitioned to REJECTED!
                                        knownDepositStatuses[dep.id] = 'rejected';
                                        showWalletToast(
                                            'Deposit Rejected',
                                            `₹${Number(dep.amount).toLocaleString()} (UTR: ${dep.utr_number}) was rejected: ${dep.admin_notes || 'Could not verify proof.'}`,
                                            'danger'
                                        );

                                        if (card) {
                                            card.setAttribute('data-status', 'rejected');
                                            card.style.borderColor = 'rgba(239, 68, 68, 0.4)';
                                            const badge = card.querySelector('.status-badge');
                                            if (badge) {
                                                badge.className = 'badge bg-danger text-white d-inline-flex align-items-center gap-1 status-badge';
                                                badge.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> <span class="status-label-text">Rejected</span>';
                                            }
                                            const notes = card.querySelector('.status-notes-container');
                                            if (notes) {
                                                notes.innerHTML = `<div class="mt-2 p-2 rounded small text-danger" style="background: rgba(220, 53, 69, 0.15); border: 1px solid rgba(220, 53, 69, 0.3); font-size: 11.5px;"><i class="fa-solid fa-circle-exclamation me-1"></i> <strong>Rejection Reason:</strong> ${dep.admin_notes || 'Invalid transfer proof'}</div>`;
                                            }
                                        }
                                    } else if (!prevStatus) {
                                        knownDepositStatuses[dep.id] = dep.status;
                                    }
                                });
                            }
                        }
                    }
                } catch (err) {
                    console.debug('Wallet live sync error:', err);
                } finally {
                    isSyncingWallet = false;
                }
            }

            // Start 3-second live sync interval on player wallet
            setInterval(pollWalletSyncState, 3000);

            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) pollWalletSyncState();
            });

            const wthForm = document.getElementById('withdrawalSubmitForm');
            const wthBtn = document.getElementById('btnSubmitWithdrawal');
            if (wthForm && wthBtn) {
                wthForm.addEventListener('submit', function() {
                    wthBtn.disabled = true;
                    wthBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting Withdrawal...';
                });
            }
        });

        window.setWithdrawAmount = function(amt) {
            const input = document.getElementById('ludoWithdrawalAmount');
            if (input) input.value = amt;
            document.querySelectorAll('#withdrawAmountPills .ludo-amount-pill').forEach(function(p) {
                var pillAmt = parseInt(p.textContent.replace(/[^0-9]/g, ''), 10);
                p.classList.toggle('active', pillAmt === amt);
            });
        };

        window.adjustWithdrawAmount = function(step) {
            const input = document.getElementById('ludoWithdrawalAmount');
            if (!input) return;
            const min = parseInt(input.min, 10) || 100;
            const max = parseInt(input.max, 10) || 50000;
            var val = parseInt(input.value, 10) || min;
            val = Math.max(min, Math.min(max, val + step));
            input.value = val;
            window.setWithdrawAmount(val);
        };

        window.togglePayoutMethod = function(method) {
            const upiBox = document.getElementById('payoutUpiContainer');
            const bankBox = document.getElementById('payoutBankContainer');
            const lblUpi = document.getElementById('labelPayoutUpi');
            const lblBank = document.getElementById('labelPayoutBank');
            const upiInput = document.getElementById('inputPayoutUpiId');

            if (method === 'upi') {
                if (upiBox) upiBox.style.display = 'block';
                if (bankBox) bankBox.style.display = 'none';
                if (lblUpi) { lblUpi.style.borderColor = '#3b82f6'; lblUpi.style.background = 'rgba(14, 42, 92, 0.6)'; }
                if (lblBank) { lblBank.style.borderColor = 'rgba(255, 255, 255, 0.1)'; lblBank.style.background = 'rgba(15, 23, 42, 0.6)'; }
                if (upiInput) upiInput.required = true;
            } else {
                if (upiBox) upiBox.style.display = 'none';
                if (bankBox) bankBox.style.display = 'block';
                if (lblBank) { lblBank.style.borderColor = '#3b82f6'; lblBank.style.background = 'rgba(14, 42, 92, 0.6)'; }
                if (lblUpi) { lblUpi.style.borderColor = 'rgba(255, 255, 255, 0.1)'; lblUpi.style.background = 'rgba(15, 23, 42, 0.6)'; }
                if (upiInput) upiInput.required = false;
            }
        };
    </script>
    @endpush
@endsection
