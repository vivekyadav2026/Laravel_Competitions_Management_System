@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Battles & Tournaments')

@section('content')
    {{-- Notice Banner --}}
    <x-ludo.announcement>
        {!! $announcement ?? '<span class="fw-bold">⭐ 🙏 🙏 सभी यूजर ध्यान दें:</span> मैच शुरू होने के 15 मिनट के अंदर रूम कोड डालें। किसी भी विवाद की स्थिति में स्क्रीनशॉट अनिवार्य है।' !!}
    </x-ludo.announcement>

    {{-- Create a Battle Section (Screenshot #5 Top Card) --}}
    <div class="ludo-create-battle-card" id="create-battle">
        <div class="ludo-create-battle-header">
            <div class="ludo-create-battle-title">
                <i class="fa-solid fa-gamepad text-warning"></i>
                <span>Create a Battle!</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button id="ludoSoundToggleBtn" class="ludo-sound-toggle-btn" type="button" title="Toggle Battle Alert Sound">
                    <i class="fa-solid fa-volume-high text-info" id="ludoSoundIcon"></i>
                    <span id="ludoSoundLabel">Sound: ON</span>
                </button>
                <button class="ludo-rules-btn" type="button" data-bs-toggle="modal" data-bs-target="#ludoRulesModal">
                    <span>Rules</span> <i class="fa-solid fa-circle-info ms-1"></i>
                </button>
            </div>
        </div>

        <form action="{{ route('ludo.battles.create') }}" method="POST" id="createBattleForm">
            @csrf
            <div class="ludo-battle-input-group">
                <span class="text-warning fs-5">🪙</span>
                <input 
                    type="number" 
                    name="amount" 
                    id="ludoBattleAmountInput"
                    class="ludo-battle-input @error('amount') is-invalid @enderror" 
                    placeholder="Enter points (min {{ $minBattlePoints ?? 50 }})" 
                    value="{{ old('amount', $minBattlePoints ?? 50) }}"
                    min="{{ $minBattlePoints ?? 50 }}" 
                    max="50000"
                    step="50" 
                    required
                >
                <div class="d-flex align-items-center gap-1 me-1">
                    <button type="button" class="ludo-step-btn ludo-step-btn-minus px-2 py-1" id="btnBattleMinus50" title="Minus 50" style="font-size: 11.5px; border-radius: 8px;">-50</button>
                    <button type="button" class="ludo-step-btn ludo-step-btn-plus px-2 py-1" id="btnBattlePlus50" title="Add 50" style="font-size: 11.5px; border-radius: 8px;">+50</button>
                </div>
                <button type="submit" class="ludo-btn-set" id="btnSetBattle">
                    <i class="fa-solid fa-plus small"></i> Set
                </button>
            </div>
            <div class="d-flex align-items-center gap-1 mt-2 flex-wrap">
                <span class="text-secondary small me-1" style="font-size: 11px;">Quick:</span>
                <button type="button" class="btn btn-sm btn-dark border border-secondary border-opacity-50 text-light py-0 px-2 small ludo-battle-quick-chip" data-amount="50" style="font-size: 11px; border-radius: 6px;">🪙50</button>
                <button type="button" class="btn btn-sm btn-dark border border-secondary border-opacity-50 text-light py-0 px-2 small ludo-battle-quick-chip" data-amount="100" style="font-size: 11px; border-radius: 6px;">🪙100</button>
                <button type="button" class="btn btn-sm btn-dark border border-secondary border-opacity-50 text-light py-0 px-2 small ludo-battle-quick-chip" data-amount="150" style="font-size: 11px; border-radius: 6px;">🪙150</button>
                <button type="button" class="btn btn-sm btn-dark border border-secondary border-opacity-50 text-light py-0 px-2 small ludo-battle-quick-chip" data-amount="200" style="font-size: 11px; border-radius: 6px;">🪙200</button>
                <button type="button" class="btn btn-sm btn-dark border border-secondary border-opacity-50 text-light py-0 px-2 small ludo-battle-quick-chip" data-amount="250" style="font-size: 11px; border-radius: 6px;">🪙250</button>
                <button type="button" class="btn btn-sm btn-dark border border-secondary border-opacity-50 text-light py-0 px-2 small ludo-battle-quick-chip" data-amount="500" style="font-size: 11px; border-radius: 6px;">🪙500</button>
            </div>
            @error('amount')
                <div class="text-danger small mt-2 fw-semibold">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $message }}
                </div>
            @enderror
            <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                <span class="text-secondary small" style="font-size: 10.5px;">Virtual Points only &bull; Free Fair Play</span>
                <span class="text-info small fw-bold" id="ludoEstPrizeBadge" style="font-size: 10.5px;">Est. Win Prize: 🪙 95 (10% fee)</span>
            </div>
        </form>
    </div>

    {{-- Floating Real-Time Battle Request Alert Toast --}}
    <div id="ludoBattleRequestToast" class="ludo-request-toast" role="alert" aria-live="assertive" style="display: none;">
        <div class="ludo-request-toast-inner">
            <div class="ludo-request-toast-header">
                <span class="ludo-request-toast-title">
                    <span class="text-warning">⚔️</span> New Battle Request!
                </span>
                <button type="button" class="ludo-request-toast-close" id="ludoToastCloseBtn" aria-label="Close">&times;</button>
            </div>
            <div class="ludo-request-toast-body">
                <div class="ludo-request-toast-msg">
                    <strong class="text-white" id="ludoToastOpponentName">Opponent</strong> is ready to play.
                </div>
                <div class="ludo-request-toast-details">
                    <span class="ludo-toast-badge ludo-toast-uid" id="ludoToastUid">BAT-0000</span>
                    <span class="ludo-toast-badge ludo-toast-entry" id="ludoToastEntry">🪙 50</span>
                    <span class="ludo-toast-badge ludo-toast-status" id="ludoToastStatus">REQUESTED</span>
                </div>
            </div>
            <div class="ludo-request-toast-footer">
                <button type="button" class="btn ludo-toast-btn-action" id="ludoToastActionBtn">
                    <i class="fa-solid fa-play me-1"></i> View Request & Start
                </button>
            </div>
        </div>
    </div>

    {{-- OPEN BATTLES Gold Banner --}}
    <x-ludo.gold-banner 
        title="OPEN BATTLES" 
        subtitle="Choose your battle and win big" 
    />

    {{-- Open Battles List Container --}}
    <div class="mb-4" id="open-battles-section">
        <div id="open-battles-list" class="d-flex flex-column gap-2" data-initial-cursor="{{ $openBattles->last()?->id ?? 0 }}" data-has-more="{{ $openBattlesHasMore ? 'true' : 'false' }}">
            @forelse($openBattles as $battle)
                @include('ludo.partials.open_battle_card', ['battle' => $battle])
            @empty
                <div id="open-battles-empty">
                    <x-ludo.empty-state 
                        title="No Open Battles Right Now"
                        message="Be the first to challenge other players! Set an entry amount above to create a battle."
                    />
                </div>
            @endforelse
        </div>

        {{-- Open Battles Infinite Scroll Sentinel & Loader --}}
        <div id="open-battles-loader" class="ludo-infinite-status text-center py-2" data-type="open">
            <div class="ludo-loader-spinner d-none py-2">
                <span class="spinner-border spinner-border-sm text-info me-1" role="status" aria-hidden="true"></span>
                <span class="text-info small fw-semibold">Loading more open battles...</span>
            </div>
            <div class="ludo-loader-end {{ !$openBattlesHasMore && count($openBattles) > 0 ? '' : 'd-none' }} py-2 text-secondary small" style="font-size: 11px;">
                <i class="fa-solid fa-flag-checkered me-1 text-warning"></i> You have reached the end of open battles
            </div>
            <div class="ludo-loader-error d-none py-2">
                <span class="text-danger small me-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Failed to load battles</span>
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2 small ludo-retry-btn" style="font-size: 11px;">
                    Retry <i class="fa-solid fa-rotate-right ms-1"></i>
                </button>
            </div>
            <div class="ludo-loader-fallback {{ $openBattlesHasMore ? '' : 'd-none' }} py-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-4 fw-bold ludo-load-more-btn" style="font-size: 11px;">
                    Load More Battles <i class="fa-solid fa-chevron-down ms-1"></i>
                </button>
            </div>
            {{-- Sentinel element for IntersectionObserver --}}
            <div class="ludo-sentinel" style="height: 1px; width: 100%;"></div>
        </div>
    </div>

    {{-- RUNNING BATTLES Gold Banner --}}
    <x-ludo.gold-banner 
        title="RUNNING BATTLES" 
        subtitle="Join running battles and compete to win" 
    />

    {{-- Running Battles List Container --}}
    <div class="mb-4" id="running-battles-section">
        <div id="running-battles-list" class="d-flex flex-column gap-2" data-initial-cursor="{{ $runningBattles->last()?->id ?? 0 }}" data-has-more="{{ $runningBattlesHasMore ? 'true' : 'false' }}">
            @forelse($runningBattles as $running)
                @include('ludo.partials.running_battle_card', ['running' => $running])
            @empty
                <div id="running-battles-empty">
                    <x-ludo.empty-state 
                        title="No Matches Currently In Progress"
                        message="Matches will appear here as soon as two players begin a contest."
                    />
                </div>
            @endforelse
        </div>

        {{-- Running Battles Infinite Scroll Sentinel & Loader --}}
        <div id="running-battles-loader" class="ludo-infinite-status text-center py-2" data-type="running">
            <div class="ludo-loader-spinner d-none py-2">
                <span class="spinner-border spinner-border-sm text-info me-1" role="status" aria-hidden="true"></span>
                <span class="text-info small fw-semibold">Loading more running battles...</span>
            </div>
            <div class="ludo-loader-end {{ !$runningBattlesHasMore && count($runningBattles) > 0 ? '' : 'd-none' }} py-2 text-secondary small" style="font-size: 11px;">
                <i class="fa-solid fa-flag-checkered me-1 text-warning"></i> You have reached the end of running battles
            </div>
            <div class="ludo-loader-error d-none py-2">
                <span class="text-danger small me-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> Failed to load matches</span>
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2 small ludo-retry-btn" style="font-size: 11px;">
                    Retry <i class="fa-solid fa-rotate-right ms-1"></i>
                </button>
            </div>
            <div class="ludo-loader-fallback {{ $runningBattlesHasMore ? '' : 'd-none' }} py-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-4 fw-bold ludo-load-more-btn" style="font-size: 11px;">
                    Load More Battles <i class="fa-solid fa-chevron-down ms-1"></i>
                </button>
            </div>
            {{-- Sentinel element for IntersectionObserver --}}
            <div class="ludo-sentinel" style="height: 1px; width: 100%;"></div>
        </div>
    </div>

    {{-- Rules Modal --}}
    <div class="modal fade" id="ludoRulesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg px-2">
            <div class="modal-content ludo-modal-dialog" style="max-height: 85vh; border: 1.5px solid #38bdf8; background: #070e24;">
                <div class="modal-header border-secondary border-opacity-25 pb-2 pt-3 px-3">
                    <div>
                        <h5 class="modal-title fw-bold fs-6 text-white mb-0">
                            <i class="fa-solid fa-scale-balanced text-warning me-2"></i> Ludo Rules & Fair Play Policy
                        </h5>
                        <div class="text-secondary small mt-0.5" style="font-size: 11px;">
                            Read all rules carefully before playing &bull; खेलने से पहले सभी नियमों को ध्यान से पढ़ें।
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group btn-group-sm" style="border: 1px solid rgba(56, 189, 248, 0.4); border-radius: 16px; overflow: hidden;">
                            <button type="button" class="btn btn-sm btn-info text-dark fw-bold px-2 py-0.5" id="rulesModalTabHi" style="font-size: 11px;">हिंदी</button>
                            <button type="button" class="btn btn-sm btn-dark text-light px-2 py-0.5" id="rulesModalTabEn" style="font-size: 11px;">English</button>
                        </div>
                        <button type="button" class="btn-close btn-close-white ms-1" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <div class="modal-body text-light py-2 px-3" style="font-size: 12.5px;">
                    {{-- Alert Box --}}
                    <div class="p-2.5 rounded-3 mb-3" style="background: rgba(234, 179, 8, 0.12); border: 1px solid rgba(234, 179, 8, 0.5);">
                        <div class="d-flex align-items-start gap-2">
                            <span class="text-warning fs-6">⚠️</span>
                            <div class="small" style="font-size: 11.5px; line-height: 1.45;">
                                <strong class="text-warning">अनिवार्य सूचना:</strong> हर गेम को स्क्रीन रिकॉर्ड करना जरूरी है! विवाद या ऑटो-एग्जिट की स्थिति में वीडियो प्रूफ अनिवार्य होगा।
                            </div>
                        </div>
                    </div>

                    {{-- Commission Rates Slabs Card --}}
                    <div class="p-2.5 rounded-3 mb-3" style="background: linear-gradient(135deg, rgba(14, 30, 68, 0.9), rgba(7, 16, 40, 0.95)); border: 1.5px solid #22d3ee;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-info text-dark fw-bold px-2" style="font-size: 10.5px;">
                                <i class="fa-solid fa-percent me-1"></i> कमीशन दरें (Commission Rates)
                            </span>
                            <span class="text-secondary small" style="font-size: 10px;">{{ config('ludo.brand_name', 'Fsaludoclub') }} Official</span>
                        </div>
                        <div class="row g-1.5">
                            <div class="col-12 col-md-4">
                                <div class="p-2 rounded-2" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(56, 189, 248, 0.3);">
                                    <div class="text-info fw-bold" style="font-size: 11px;">&lt; 250 Coins: 10% Commission</div>
                                    <div class="text-light small mt-0.5" style="font-size: 10.5px;">250 Coins से कम की बैटल पर बैटल राशि का 10% कमीशन।</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-2 rounded-2" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(234, 179, 8, 0.4);">
                                    <div class="text-warning fw-bold" style="font-size: 11px;">250 – 500 Coins: Flat 25 Coins</div>
                                    <div class="text-light small mt-0.5" style="font-size: 10.5px;">250 से 500 Coins के बीच बैटल पर फ्लैट 25 Coins कमीशन।</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="p-2 rounded-2" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(16, 185, 129, 0.4);">
                                    <div class="text-success fw-bold" style="font-size: 11px;">&gt; 500 Coins: 5% Commission</div>
                                    <div class="text-light small mt-0.5" style="font-size: 10.5px;">500 Coins से अधिक की बैटल पर बैटल राशि का 5% कमीशन।</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Hindi Rules Container --}}
                    <div id="rulesModalContentHi" class="d-flex flex-column gap-2">
                        <div class="p-2.5 rounded-2" style="background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-danger text-white px-1.5 py-0.5" style="font-size: 10px;">1</span>
                                <strong class="text-white small">Autoexit & 100% Loss</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                यदि आप जान भुजकर Autoexit करते है तो भी आपको 100% Loss कर दिया जायेगा ! यदि दोनों प्लेयर में किसी की काटी खुली नहीं तो उसे हम कैंसिल कर सकते है !
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(245, 158, 11, 0.1); border-left: 3px solid #f59e0b;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-warning text-dark px-1.5 py-0.5" style="font-size: 10px;">2</span>
                                <strong class="text-white small">टोकन स्थिति एवं लॉस प्रतिशत (30% vs 100%)</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                यदि एक टोकन बाहर है और घर के पास है तो 30% Loss दिया जायेगा लेकिन यदि गेम खेला गया है और 2 काटी बहार आयी हो तो गेम को लेफ्ट करने वाले को 100% Loss कर दिया जायेगा !
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(251, 191, 36, 0.1); border-left: 3px solid #fbbf24;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-warning text-dark px-1.5 py-0.5" style="font-size: 10px;">3</span>
                                <strong class="text-white small">Autoexit 1 टोकन & एडमिन का अंतिम निर्णय</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                Autoexit में यदि 1 टोकन बाहर है तो गेम कैंसिल किया जा सकता है लेकिन यदि आपने गेम जान भुजकर में छोड़ा होगा तो आपको Loss ही दिया जायेगा इसमें अंतिम निर्णय Admin का होगा !
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(34, 211, 238, 0.1); border-left: 3px solid #22d3ee;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-info text-dark px-1.5 py-0.5" style="font-size: 10px;">4</span>
                                <strong class="text-white small">Autoexit विवाद एवं अनिवार्य वीडियो प्रूफ</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                यदि आपको लगता है की Opponent ने जानभूझकर गेम को Autoexit में छोड़ा है लेकिन Admin ने कैंसिल कर दिया है तो आपसे वीडियो प्रूफ माँगा जायेगा इसलिए हर गेम को रिकॉर्ड करना जरुरी है ! यदि आप वीडियो प्रूफ नहीं देते है तो गेम रिजल्ट एडमिन के अनुसार ही अपडेट किया जायेगा चाहे आप विन हो या गेम कैंसिल हो !
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(56, 189, 248, 0.1); border-left: 3px solid #38bdf8;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-primary text-white px-1.5 py-0.5" style="font-size: 10px;">5</span>
                                <strong class="text-white small">15 मिनट के अंदर रिजल्ट पोस्ट करना अनिवार्य</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                Game समाप्त होने के 15 मिनट के अंदर रिजल्ट डालना आवश्यक है अन्यथा Opponent के रिजल्ट के आधार पर गेम अपडेट कर दिया जायेगा चाहे आप जीते या हारे और इसमें पूरी ज़िम्मेदारी आपकी होगी इसमें बाद में कोई बदलाव नहीं किया जा सकता है !
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-danger text-white px-1.5 py-0.5" style="font-size: 10px;">6</span>
                                <strong class="text-white small">गलत स्क्रीनशॉट पर सीधा Cancel एवं Live Chat</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                Win होने के बाद आप गलत स्क्रीनशॉट डालते है तो गेम को सीधा Cancel कर दिया जायेगा इसलिए यदि आप स्क्रीनशॉट लेना भूल गए है तो पहले Live Chat में एडमिन को संपर्क करे उसके बाद ही उनके बताये अनुसार रिजल्ट पोस्ट करे !
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(16, 185, 129, 0.1); border-left: 3px solid #10b981;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-success text-white px-1.5 py-0.5" style="font-size: 10px;">7</span>
                                <strong class="text-white small">बिना काटी खुले लेफ्ट एवं गेम कैंसिल</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                दोनों प्लेयर की टोकन (काटी) घर से बाहर न आयी हो तो लेफ्ट होकर गेम कैंसिल किया जा सकता है ! [कैंसिल प्रूफ करने के लिए वीडियो आवश्यक होगा]
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(249, 115, 22, 0.1); border-left: 3px solid #f97316;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-warning text-dark px-1.5 py-0.5" style="font-size: 10px;">8</span>
                                <strong class="text-white small">'कैंसिल' रिजल्ट डालने के बाद गेम प्ले न करें</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                'कैंसिल' रिजल्ट डालने के बाद गेम प्ले करके जीत जाते है तो उसमे हमारी कोई ज़िम्मेदारी नहीं होगी अतः गेम कैंसिल करने के बाद स्टार्ट न करे अन्यथा वो कैंसिल ही माना जायेगा।
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-danger text-white px-1.5 py-0.5" style="font-size: 10px;">9</span>
                                <strong class="text-white small">रिजल्ट अपरिवर्तनीय एवं गलत रिजल्ट पर पेनल्टी</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                एक बार रिजल्ट डालने के बाद बदला नहीं जा सकता है इसलिए सोच समझकर रिजल्ट पोस्ट करे गलत रिजल्ट डालने पर पेनल्टी भी लगायी जाएगी चाहे आपने वो गलती से डाला हो या जान भुजकर।
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(168, 85, 247, 0.1); border-left: 3px solid #a855f7;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge text-white px-1.5 py-0.5" style="background: #9333ea; font-size: 10px;">10</span>
                                <strong class="text-white small">Fresh आईडी गेम एवं पासा उपयोग का प्रमाण</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                Fresh आईडी गेम के मामले में केवल पासा के उपयोग के प्रमाण पर रद्द कर दिया जाएगा गोटी के खुले होने के बावजूद नहीं ? गेम का रिकॉर्ड होना जरूरी है अगर कोई अपनी आईडी का नाम नो फ्रेश कर रखा तो नो फ्रेश आईडी ही टेक करे जिनका इंटरेस्ट हो वही टेक करे टेबल को ( इसका अंतिम रिजल्ट एडमिन देगा )।
                            </div>
                        </div>
                    </div>

                    {{-- English Rules Container --}}
                    <div id="rulesModalContentEn" class="d-flex flex-column gap-2 d-none">
                        <div class="p-2.5 rounded-2" style="background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-danger text-white px-1.5 py-0.5" style="font-size: 10px;">1</span>
                                <strong class="text-white small">Auto-Exit & 100% Loss</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                If you deliberately trigger an Auto-exit, you receive an immediate 100% Loss. If neither player's token has opened yet, the match can be safely cancelled.
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(245, 158, 11, 0.1); border-left: 3px solid #f59e0b;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-warning text-dark px-1.5 py-0.5" style="font-size: 10px;">2</span>
                                <strong class="text-white small">Token Status & Loss % (30% vs 100%)</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                If only 1 token is out and near the home base, a 30% Loss penalty applies. However, if gameplay has progressed and 2 or more tokens are out, leaving causes a 100% Loss!
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(251, 191, 36, 0.1); border-left: 3px solid #fbbf24;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-warning text-dark px-1.5 py-0.5" style="font-size: 10px;">3</span>
                                <strong class="text-white small">Auto-Exit with 1 Token vs Admin Decision</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                In Auto-exit with 1 token out, the match may be cancelled. However, if deliberately abandoned, a Loss will be awarded. The Admin provides the final verdict.
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(34, 211, 238, 0.1); border-left: 3px solid #22d3ee;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-info text-dark px-1.5 py-0.5" style="font-size: 10px;">4</span>
                                <strong class="text-white small">Auto-Exit Disputes & Mandatory Video Proof</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                If you believe your opponent deliberately triggered an Auto-exit, video proof is required. Screen recording every game is mandatory! Without video proof, Admin discretion is final.
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(56, 189, 248, 0.1); border-left: 3px solid #38bdf8;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-primary text-white px-1.5 py-0.5" style="font-size: 10px;">5</span>
                                <strong class="text-white small">15-Minute Result Submission Window</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                Results must be posted within 15 minutes of completion. Otherwise, the game will be updated based solely on your opponent's result with full liability on you!
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-danger text-white px-1.5 py-0.5" style="font-size: 10px;">6</span>
                                <strong class="text-white small">Wrong Screenshot = Direct Cancel & Live Support</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                Submitting a wrong screenshot on win will directly Cancel the game! If you forgot to take a screenshot, contact Admin via Live Chat before posting results.
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(16, 185, 129, 0.1); border-left: 3px solid #10b981;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-success text-white px-1.5 py-0.5" style="font-size: 10px;">7</span>
                                <strong class="text-white small">Zero Tokens Out Safe Cancellation</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                If neither player's tokens have opened out of the home base, you may leave and cancel the match. [Video proof is mandatory for cancellation claim].
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(249, 115, 22, 0.1); border-left: 3px solid #f97316;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-warning text-dark px-1.5 py-0.5" style="font-size: 10px;">8</span>
                                <strong class="text-white small">Do NOT Play After Claiming 'Cancel'</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                If you post a 'Cancel' result and continue playing to win, the platform bears zero liability. Once cancelled, the match remains Cancelled.
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-danger text-white px-1.5 py-0.5" style="font-size: 10px;">9</span>
                                <strong class="text-white small">Irreversible Submissions & Penalty</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                Once posted, match results cannot be altered. Submitting false results incurs penalties whether done mistakenly or deliberately.
                            </div>
                        </div>

                        <div class="p-2.5 rounded-2" style="background: rgba(168, 85, 247, 0.1); border-left: 3px solid #a855f7;">
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge text-white px-1.5 py-0.5" style="background: #9333ea; font-size: 10px;">10</span>
                                <strong class="text-white small">Fresh ID Policy & Dice Roll Proof</strong>
                            </div>
                            <div class="text-light small ps-1" style="font-size: 11.5px; line-height: 1.5;">
                                In Fresh ID games, cancellation requires video proof of dice usage, not solely whether tokens are open. Screen recording is mandatory.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-secondary border-opacity-25 pt-2 pb-2 px-3 d-flex justify-content-between align-items-center">
                    <a href="{{ route('ludo.rules') }}" target="_blank" class="text-info small text-decoration-none">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Full Policy Page
                    </a>
                    <button type="button" class="btn btn-sm btn-info rounded-pill px-4 text-dark fw-bold" data-bs-dismiss="modal">
                        I Understand &bull; समझ गया
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Rules Modal Language Switcher (हिंदी / English)
    const rulesTabHi = document.getElementById('rulesModalTabHi');
    const rulesTabEn = document.getElementById('rulesModalTabEn');
    const rulesContentHi = document.getElementById('rulesModalContentHi');
    const rulesContentEn = document.getElementById('rulesModalContentEn');

    if (rulesTabHi && rulesTabEn && rulesContentHi && rulesContentEn) {
        rulesTabHi.addEventListener('click', function () {
            rulesTabHi.className = 'btn btn-sm btn-info text-dark fw-bold px-2 py-0.5';
            rulesTabEn.className = 'btn btn-sm btn-dark text-light px-2 py-0.5';
            rulesContentHi.classList.remove('d-none');
            rulesContentEn.classList.add('d-none');
        });

        rulesTabEn.addEventListener('click', function () {
            rulesTabEn.className = 'btn btn-sm btn-info text-dark fw-bold px-2 py-0.5';
            rulesTabHi.className = 'btn btn-sm btn-dark text-light px-2 py-0.5';
            rulesContentEn.classList.remove('d-none');
            rulesContentHi.classList.add('d-none');
        });
    }

    class BattleInfiniteScroll {
        constructor(config) {
            this.type = config.type; // 'open' | 'running'
            this.listEl = document.getElementById(config.listId);
            this.loaderEl = document.getElementById(config.loaderId);
            this.endpoint = config.endpoint;
            this.perPage = config.perPage || 5;

            if (!this.listEl || !this.loaderEl) {
                return;
            }

            this.spinnerEl = this.loaderEl.querySelector('.ludo-loader-spinner');
            this.endEl = this.loaderEl.querySelector('.ludo-loader-end');
            this.errorEl = this.loaderEl.querySelector('.ludo-loader-error');
            this.fallbackContainer = this.loaderEl.querySelector('.ludo-loader-fallback');
            this.fallbackBtn = this.loaderEl.querySelector('.ludo-load-more-btn');
            this.retryBtn = this.loaderEl.querySelector('.ludo-retry-btn');
            this.sentinelEl = this.loaderEl.querySelector('.ludo-sentinel');
            this.emptyEl = document.getElementById(config.emptyId);

            this.cursor = this.listEl.dataset.initialCursor ? parseInt(this.listEl.dataset.initialCursor, 10) : null;
            this.hasMore = this.listEl.dataset.hasMore === 'true';
            this.isLoading = false;
            this.loadedIds = new Set();

            // Register existing IDs to prevent duplicates
            this.listEl.querySelectorAll('[data-battle-id]').forEach(el => {
                const id = parseInt(el.getAttribute('data-battle-id'), 10);
                if (id) this.loadedIds.add(id);
            });

            this.init();
        }

        init() {
            if (this.fallbackBtn) {
                this.fallbackBtn.addEventListener('click', () => this.fetchNextBatch());
            }

            if (this.retryBtn) {
                this.retryBtn.addEventListener('click', () => {
                    this.hideError();
                    this.fetchNextBatch();
                });
            }

            if (!this.hasMore) {
                this.onEndReached();
                return;
            }

            this.setupIntersectionObserver();
        }

        setupIntersectionObserver() {
            if (!('IntersectionObserver' in window) || !this.sentinelEl) {
                // Fallback to manual button if IntersectionObserver not available
                if (this.fallbackContainer && this.hasMore) {
                    this.fallbackContainer.classList.remove('d-none');
                }
                return;
            }

            this.observer = new IntersectionObserver((entries) => {
                const entry = entries[0];
                if (entry && entry.isIntersecting && this.hasMore && !this.isLoading) {
                    this.fetchNextBatch();
                }
            }, {
                root: null,
                rootMargin: '250px',
                threshold: 0.01
            });

            this.observer.observe(this.sentinelEl);
        }

        showLoading() {
            this.isLoading = true;
            if (this.spinnerEl) this.spinnerEl.classList.remove('d-none');
            if (this.fallbackContainer) this.fallbackContainer.classList.add('d-none');
            this.hideError();
        }

        hideLoading() {
            this.isLoading = false;
            if (this.spinnerEl) this.spinnerEl.classList.add('d-none');
        }

        showError() {
            if (this.errorEl) this.errorEl.classList.remove('d-none');
            if (this.fallbackContainer) this.fallbackContainer.classList.add('d-none');
        }

        hideError() {
            if (this.errorEl) this.errorEl.classList.add('d-none');
        }

        onEndReached() {
            this.hasMore = false;
            if (this.observer) {
                this.observer.disconnect();
            }
            if (this.fallbackContainer) {
                this.fallbackContainer.classList.add('d-none');
            }
            if (this.endEl && this.loadedIds.size > 0) {
                this.endEl.classList.remove('d-none');
            }
        }

        async fetchNextBatch() {
            if (this.isLoading || !this.hasMore) return;

            this.showLoading();

            try {
                const url = new URL(this.endpoint, window.location.origin);
                url.searchParams.set('type', this.type);
                if (this.cursor) {
                    url.searchParams.set('cursor', this.cursor);
                }
                url.searchParams.set('per_page', this.perPage);

                const response = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Server returned status ${response.status}`);
                }

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.message || 'Failed to load battles');
                }

                this.cursor = data.next_cursor;
                this.hasMore = Boolean(data.has_more);

                if (data.html && data.html.trim().length > 0) {
                    // Remove empty placeholder if any
                    if (this.emptyEl) {
                        this.emptyEl.remove();
                        this.emptyEl = null;
                    }

                    // Parse returned HTML and append elements
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.html;

                    // Append while avoiding any duplicate card IDs
                    const children = Array.from(tempDiv.children);
                    children.forEach(child => {
                        const cardId = child.getAttribute('data-battle-id') 
                            ? parseInt(child.getAttribute('data-battle-id'), 10) 
                            : null;

                        if (cardId) {
                            if (!this.loadedIds.has(cardId)) {
                                this.loadedIds.add(cardId);
                                this.listEl.appendChild(child);
                            }
                        } else {
                            // If it's a modal or non-card node associated with the card
                            this.listEl.appendChild(child);
                        }
                    });
                    ensureModalsOnBody();
                    applyCardStagger(this.listEl);
                }

                this.hideLoading();

                if (!this.hasMore) {
                    this.onEndReached();
                } else {
                    // Show fallback button as alternative if user stops scrolling
                    if (this.fallbackContainer) {
                        this.fallbackContainer.classList.remove('d-none');
                    }
                }
            } catch (error) {
                console.error(`Error loading more ${this.type} battles:`, error);
                this.hideLoading();
                this.showError();
            }
        }
    }

    // Initialize Open Battles Infinite Scroller
    const openBattlesScroller = new BattleInfiniteScroll({
        type: 'open',
        listId: 'open-battles-list',
        loaderId: 'open-battles-loader',
        emptyId: 'open-battles-empty',
        endpoint: "{{ route('ludo.battles.loadMore') }}",
        perPage: 5
    });

    // Initialize Running Battles Infinite Scroller
    const runningBattlesScroller = new BattleInfiniteScroll({
        type: 'running',
        listId: 'running-battles-list',
        loaderId: 'running-battles-loader',
        emptyId: 'running-battles-empty',
        endpoint: "{{ route('ludo.battles.loadMore') }}",
        perPage: 5
    });

    // AJAX Battle Creation & Seamless Prepend (Section B Requirement #8 & #9)
    const createBattleForm = document.getElementById('createBattleForm');
    const btnSetBattle = document.getElementById('btnSetBattle');
    const openBattlesList = document.getElementById('open-battles-list');
    const openBattlesEmpty = document.getElementById('open-battles-empty');

    if (createBattleForm && btnSetBattle) {
        createBattleForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            if (btnSetBattle.disabled) return;
            btnSetBattle.disabled = true;
            const originalBtnHtml = btnSetBattle.innerHTML;
            btnSetBattle.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Set';

            const formData = new FormData(createBattleForm);

            try {
                const response = await fetch("{{ route('ludo.battles.create') }}", {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Remove empty state if present
                    if (openBattlesEmpty) {
                        openBattlesEmpty.classList.add('d-none');
                    }

                    // Prepend new battle card HTML to open battles list
                    if (data.html && openBattlesList) {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data.html;
                        const newCard = tempDiv.firstElementChild;
                        if (newCard) {
                            openBattlesList.prepend(newCard);
                            if (openBattlesScroller && data.battle?.id) {
                                openBattlesScroller.loadedIds.add(data.battle.id);
                            }
                            ensureModalsOnBody();
                            applyCardStagger(openBattlesList);
                        }
                    }

                    // Update user's coin badge in top header if present
                    const headerCoins = document.querySelectorAll('.user-coins-display, .header-coin-val, #userCoinsBadge');
                    headerCoins.forEach(badge => {
                        if (data.coins !== undefined) {
                            badge.textContent = Number(data.coins).toLocaleString();
                        }
                    });

                    // Toast / Alert success message
                    const alertContainer = document.querySelector('.ludo-page-content');
                    if (alertContainer) {
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show rounded-3 small py-2 px-3 mb-3';
                        alertDiv.role = 'alert';
                        alertDiv.innerHTML = `<span><i class="fa-solid fa-circle-check me-2"></i> ${data.message}</span><button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>`;
                        alertContainer.prepend(alertDiv);
                        setTimeout(() => alertDiv.remove(), 4000);
                    }
                } else {
                    if (response.status === 401 || data.redirect) {
                        window.location.href = data.redirect || '{{ route("login") }}';
                        return;
                    }
                    alert(data.message || 'Failed to create battle. Please check your points balance.');
                }
            } catch (err) {
                console.error('Battle creation failed:', err);
                // Fallback to traditional submit if fetch failed
                createBattleForm.submit();
            } finally {
                btnSetBattle.disabled = false;
                btnSetBattle.innerHTML = originalBtnHtml;
            }
        });
    }

    // Helper to display alert banners at top of page
    function showLudoAlert(type, message) {
        const alertContainer = document.querySelector('.ludo-page-content');
        if (!alertContainer) return;

        const iconClass = type === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-danger';
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show rounded-3 small py-2 px-3 mb-3`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `<span><i class="fa-solid ${iconClass} me-2"></i> ${message}</span><button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>`;
        alertContainer.prepend(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }

    // Staggered Right-Slide Entrance Animation for Battle Cards (Requirement #1)
    function applyCardStagger(container) {
        if (!container) return;
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const cards = container.querySelectorAll('.ludo-battle-card:not(.ludo-card-animated), .ludo-running-card:not(.ludo-card-animated)');
        cards.forEach((card, idx) => {
            card.style.setProperty('--card-stagger', idx);
            card.classList.add('ludo-slide-in-right');
            card.classList.add('ludo-card-animated');

            card.addEventListener('animationend', () => {
                card.classList.remove('ludo-slide-in-right');
                card.style.removeProperty('--card-stagger');
            }, { once: true });
        });
    }
    window.applyCardStagger = applyCardStagger;

    // Pre-emptively move all modals to body so backdrop never covers or dims modal content
    function ensureModalsOnBody() {
        document.querySelectorAll('.modal').forEach(modal => {
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }
        });
    }
    ensureModalsOnBody();
    applyCardStagger(document.getElementById('open-battles-list'));
    applyCardStagger(document.getElementById('running-battles-list'));

    // Dynamic verification when confirmation modal opens
    document.addEventListener('show.bs.modal', function (event) {
        const modalEl = event.target;
        if (!modalEl.classList.contains('modal') || !modalEl.id.startsWith('ludoPlayModal')) return;

        // Ensure modal is attached directly to document.body to avoid stacking context dimming
        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }

        const battleId = modalEl.getAttribute('data-battle-id');
        const entryFee = parseInt(modalEl.getAttribute('data-entry-fee'), 10);
        
        // Find current user balance from header
        const headerCoinEl = document.querySelector('.user-coins-display');
        if (!headerCoinEl || isNaN(entryFee)) return;

        const rawCoins = headerCoinEl.textContent.replace(/[^0-9]/g, '');
        const currentCoins = parseInt(rawCoins, 10) || 0;

        if (currentCoins < entryFee) {
            // Balance became insufficient during the session
            const footer = modalEl.querySelector('.modal-footer');
            if (footer) {
                footer.innerHTML = `
                    <div class="ludo-modal-footer-insufficient">
                        <button type="button" class="btn ludo-modal-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <div class="ludo-modal-actions-right">
                            <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3 py-1 fw-bold text-white disabled d-none" disabled title="Insufficient points balance">
                                <i class="fa-solid fa-lock me-1"></i> Insufficient Points
                            </button>
                            <a href="{{ url('/wallet') }}" class="btn ludo-modal-btn-add-points">
                                <i class="fa-solid fa-coins text-warning me-1"></i> Add Points
                            </a>
                        </div>
                    </div>
                `;
            }
        }
    });

    // Delegated AJAX Join Battle Submission (Item #5 & #6)
    document.addEventListener('submit', async function (e) {
        const form = e.target.closest('.ludo-join-battle-form');
        if (!form) return;

        e.preventDefault();
        const submitBtn = form.querySelector('.ludo-btn-accept-play');
        const modalEl = form.closest('.modal');
        const battleId = form.getAttribute('data-battle-id');

        if (submitBtn) {
            if (submitBtn.disabled) return;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Joining...';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: new FormData(form)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Close modal ONLY after confirmed successful response
                if (modalEl) {
                    const bsModal = bootstrap.Modal.getInstance(modalEl);
                    if (bsModal) bsModal.hide();
                }

                // Update header coins display
                if (data.coins !== undefined) {
                    document.querySelectorAll('.user-coins-display, .header-coin-val, #userCoinsBadge').forEach(badge => {
                        badge.textContent = Number(data.coins).toLocaleString();
                    });
                }

                showLudoAlert('success', data.message || 'Battle accepted! Redirecting to Arena...');
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                if (response.status === 401 || data.redirect) {
                    window.location.href = data.redirect || '{{ route("login") }}';
                    return;
                }
                // Battle unavailable, cancelled, already joined, or insufficient balance
                // Show clear alert inside modal so user is informed without silent closure
                const modalBody = modalEl ? modalEl.querySelector('.modal-body') : null;
                if (modalBody) {
                    let inModalAlert = modalBody.querySelector('.ludo-inmodal-error');
                    if (!inModalAlert) {
                        inModalAlert = document.createElement('div');
                        inModalAlert.className = 'alert alert-danger py-2 px-3 mb-2 small rounded-3 d-flex align-items-center gap-2 ludo-inmodal-error';
                        modalBody.prepend(inModalAlert);
                    }
                    inModalAlert.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-danger fs-5"></i><div>${data.message || 'Unable to join this battle challenge.'}</div>`;
                }

                showLudoAlert('danger', data.message || 'Unable to join this battle challenge.');

                // If battle is unavailable or already taken, remove stale card and refresh
                if (battleId) {
                    const staleCard = document.querySelector(`.ludo-battle-card[data-battle-id="${battleId}"]`);
                    if (staleCard) {
                        staleCard.classList.add('opacity-50');
                        setTimeout(() => staleCard.remove(), 400);
                        if (openBattlesScroller) {
                            openBattlesScroller.loadedIds.delete(parseInt(battleId, 10));
                            // Trigger background refresh/fetch if needed
                            if (openBattlesScroller.hasMore) {
                                openBattlesScroller.fetchNextBatch();
                            }
                        }
                    }
                }
            }
        } catch (err) {
            console.error('Join battle failed:', err);
            showLudoAlert('danger', 'Network error while accepting battle. Please try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-play me-1"></i> Accept & Play';
            }
        }
    });

    // =========================================================================
    // REAL-TIME BATTLE REQUEST SOUND & VISUAL NOTIFICATION
    // =========================================================================

    // 1. Audio Synthesizer (Web Audio API)
    let ludoAudioCtx = null;
    let isSoundMuted = localStorage.getItem('ludo_battle_sound_enabled') === 'false';

    function initAudioContext() {
        if (!ludoAudioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                ludoAudioCtx = new AudioContext();
            }
        }
        if (ludoAudioCtx && ludoAudioCtx.state === 'suspended') {
            ludoAudioCtx.resume().catch(() => {});
        }
    }

    // Unlock AudioContext on genuine user interaction
    ['click', 'touchstart', 'keydown'].forEach(evt => {
        document.addEventListener(evt, function unlockAudio() {
            initAudioContext();
        }, { once: false, passive: true });
    });

    // Pleasant gaming chime synthesis
    function playBattleRequestChime() {
        if (isSoundMuted) return;

        try {
            initAudioContext();
            if (!ludoAudioCtx) return;

            const now = ludoAudioCtx.currentTime;
            // 4-note ascending high-definition gaming notification chime
            const notes = [
                { freq: 523.25, start: 0.00, dur: 0.10 }, // C5
                { freq: 659.25, start: 0.09, dur: 0.10 }, // E5
                { freq: 783.99, start: 0.18, dur: 0.12 }, // G5
                { freq: 1046.50, start: 0.28, dur: 0.36 } // C6
            ];

            notes.forEach(note => {
                const osc = ludoAudioCtx.createOscillator();
                const gain = ludoAudioCtx.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(note.freq, now + note.start);

                gain.gain.setValueAtTime(0.001, now + note.start);
                gain.gain.exponentialRampToValueAtTime(0.20, now + note.start + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, now + note.start + note.dur);

                osc.connect(gain);
                gain.connect(ludoAudioCtx.destination);

                osc.start(now + note.start);
                osc.stop(now + note.start + note.dur + 0.05);
            });
        } catch (err) {
            console.debug('Audio playback not allowed or failed:', err);
        }
    }
    window.playBattleRequestChime = playBattleRequestChime;

    // 2. Sound Toggle UI Management
    const soundToggleBtn = document.getElementById('ludoSoundToggleBtn');
    const soundIcon = document.getElementById('ludoSoundIcon');
    const soundLabel = document.getElementById('ludoSoundLabel');

    function updateSoundToggleUI() {
        if (!soundToggleBtn || !soundIcon || !soundLabel) return;
        if (isSoundMuted) {
            soundToggleBtn.classList.add('is-muted');
            soundIcon.className = 'fa-solid fa-volume-xmark text-secondary';
            soundLabel.textContent = 'Sound: OFF';
            soundToggleBtn.title = 'Sound is muted (Click to enable)';
        } else {
            soundToggleBtn.classList.remove('is-muted');
            soundIcon.className = 'fa-solid fa-volume-high text-info';
            soundLabel.textContent = 'Sound: ON';
            soundToggleBtn.title = 'Sound is active (Click to mute)';
        }
    }

    updateSoundToggleUI();

    if (soundToggleBtn) {
        soundToggleBtn.addEventListener('click', function () {
            isSoundMuted = !isSoundMuted;
            localStorage.setItem('ludo_battle_sound_enabled', isSoundMuted ? 'false' : 'true');
            updateSoundToggleUI();

            if (!isSoundMuted) {
                initAudioContext();
                // Play a brief test ping
                try {
                    if (ludoAudioCtx) {
                        const now = ludoAudioCtx.currentTime;
                        const osc = ludoAudioCtx.createOscillator();
                        const gain = ludoAudioCtx.createGain();
                        osc.frequency.setValueAtTime(880, now);
                        gain.gain.setValueAtTime(0.001, now);
                        gain.gain.exponentialRampToValueAtTime(0.12, now + 0.01);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.12);
                        osc.connect(gain);
                        gain.connect(ludoAudioCtx.destination);
                        osc.start(now);
                        osc.stop(now + 0.15);
                    }
                } catch (e) {}
                showLudoToast('Battle alert sounds enabled 🔊');
            } else {
                showLudoToast('Battle alert sounds muted 🔇');
            }
        });
    }

    // 3. Visual Toast Notification Controller
    const toastEl = document.getElementById('ludoBattleRequestToast');
    const toastCloseBtn = document.getElementById('ludoToastCloseBtn');
    const toastActionBtn = document.getElementById('ludoToastActionBtn');
    const toastOpponentName = document.getElementById('ludoToastOpponentName');
    const toastUid = document.getElementById('ludoToastUid');
    const toastEntry = document.getElementById('ludoToastEntry');
    const toastStatus = document.getElementById('ludoToastStatus');
    let toastTimeout = null;

    function showBattleRequestToast(data) {
        if (!toastEl) return;
        if (toastOpponentName) toastOpponentName.textContent = data.joiner_name || 'Opponent';
        if (toastUid) toastUid.textContent = data.battle_uid || 'BAT-MATCH';
        if (toastEntry) toastEntry.textContent = '🪙 ' + (data.entry_fee || '0');
        if (toastStatus) toastStatus.textContent = (data.status || 'requested').toUpperCase();

        toastEl.style.display = 'block';

        if (toastTimeout) clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            toastEl.style.display = 'none';
        }, 12000);

        if (toastActionBtn) {
            toastActionBtn.onclick = function () {
                toastEl.style.display = 'none';
                const card = document.querySelector(`.ludo-battle-card[data-battle-id="${data.battle_id}"]`);
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    card.classList.add('battle-card--highlighted');
                    setTimeout(() => card.classList.remove('battle-card--highlighted'), 3000);
                }
            };
        }
    }
    window.showBattleRequestToast = showBattleRequestToast;

    if (toastCloseBtn) {
        toastCloseBtn.addEventListener('click', function () {
            if (toastEl) toastEl.style.display = 'none';
        });
    }

    // 4. Initial Seed to Prevent Audio / Toast for Pre-existing Requests on Initial Page Load
    const preExistingRequestedCard = document.querySelector('.battle-card-requested[data-battle-id]');
    let lastProcessedEventId = preExistingRequestedCard 
        ? 'pre_existing_' + preExistingRequestedCard.getAttribute('data-battle-id')
        : (sessionStorage.getItem('ludo_last_seen_req_event') || null);

    // 5. Smart Real-time Lobby Polling (Rate-limited, pause on tab hidden)
    let isLobbyPolling = false;
    async function pollLobbyState() {
        if (document.hidden || isLobbyPolling) return;
        isLobbyPolling = true;

        try {
            const res = await fetch("{{ route('ludo.battles.syncState') }}", {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (res.ok) {
                const data = await res.json();

                // A. Check incoming battle request for creator ('requested' status)
                if (data.creator_pending_start) {
                    const pending = data.creator_pending_start;
                    const eventId = pending.event_id || ('req_' + pending.battle_id);
                    const lastCrossTabEvent = localStorage.getItem('ludo_last_played_sound_event');

                    const isNewEvent = (eventId !== lastProcessedEventId) && (eventId !== lastCrossTabEvent);

                    if (isNewEvent) {
                        lastProcessedEventId = eventId;
                        sessionStorage.setItem('ludo_last_seen_req_event', eventId);
                        localStorage.setItem('ludo_last_played_sound_event', eventId);

                        // Play audio chime (if sound is enabled)
                        if (typeof window.playBattleRequestChime === 'function') {
                            window.playBattleRequestChime();
                        } else {
                            playBattleRequestChime();
                        }

                        // Show styled visual toast notification
                        showBattleRequestToast(pending);
                    }

                    // In-place card update from open to requested mode without reloading the page
                    const existingCard = document.querySelector(`.ludo-battle-card[data-battle-id="${pending.battle_id}"]`);
                    if (existingCard && pending.card_html) {
                        if (!existingCard.classList.contains('battle-card-requested')) {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = pending.card_html.trim();
                            const newCard = tempDiv.firstElementChild;
                            if (newCard) {
                                existingCard.replaceWith(newCard);
                                newCard.classList.add('battle-card--highlighted');
                                setTimeout(() => newCard.classList.remove('battle-card--highlighted'), 3500);
                            }
                        }
                    }
                }

                // B. If joiner's match was started by creator -> redirect to Match Arena!
                if (data.joiner_started_match && data.joiner_started_match.arena_url) {
                    clearInterval(lobbySyncInterval);
                    window.location.href = data.joiner_started_match.arena_url;
                    return;
                }

                // C. Synchronize Open Battles (Add newly created battles, remove battles that were started or cancelled)
                if (Array.isArray(data.open_battle_ids)) {
                    const openList = document.getElementById('open-battles-list');
                    if (openList) {
                        const renderedOpenCards = openList.querySelectorAll('.ludo-battle-card[data-battle-id]');
                        renderedOpenCards.forEach(card => {
                            const id = parseInt(card.getAttribute('data-battle-id'), 10);
                            if (!data.open_battle_ids.includes(id)) {
                                card.remove();
                            }
                        });

                        // Dynamically render newly created battles or update state without page reload
                        if (Array.isArray(data.open_battles)) {
                            data.open_battles.slice().reverse().forEach(item => {
                                const existingCard = openList.querySelector(`.ludo-battle-card[data-battle-id="${item.id}"]`);
                                if (!existingCard && item.card_html) {
                                    const emptyEl = document.getElementById('open-battles-empty');
                                    if (emptyEl) emptyEl.remove();

                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = item.card_html.trim();
                                    const newCard = tempDiv.firstElementChild;
                                    if (newCard) {
                                        openList.prepend(newCard);
                                        applyCardStagger(openList);
                                    }
                                } else if (existingCard && item.card_html) {
                                    // Update card state if changed (e.g. from open to requested)
                                    const tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = item.card_html.trim();
                                    const updatedCard = tempDiv.firstElementChild;
                                    if (updatedCard && updatedCard.className !== existingCard.className) {
                                        updatedCard.classList.add('ludo-card-animated');
                                        existingCard.replaceWith(updatedCard);
                                    }
                                }
                            });
                        }

                        if (openList.querySelectorAll('.ludo-battle-card').length === 0 && !document.getElementById('open-battles-empty')) {
                            const emptyDiv = document.createElement('div');
                            emptyDiv.id = 'open-battles-empty';
                            emptyDiv.innerHTML = `
                                <div class="ludo-empty-state text-center py-4 px-3 my-2" style="background: rgba(14, 20, 44, 0.6); border: 1px dashed rgba(0, 210, 255, 0.2); border-radius: 16px;">
                                    <i class="fa-solid fa-gamepad fs-3 text-secondary mb-2 d-block"></i>
                                    <h6 class="text-white fw-bold mb-1">No Open Battles Right Now</h6>
                                    <p class="text-secondary small mb-0">Be the first to challenge other players! Set an entry amount above to create a battle.</p>
                                </div>`;
                            openList.appendChild(emptyDiv);
                        }
                    }
                }

                // D. Synchronize Running Battles (Add new running matches, remove completed/cancelled, update without re-animating)
                if (Array.isArray(data.running_battle_ids) && Array.isArray(data.running_battles)) {
                    const runningList = document.getElementById('running-battles-list');
                    if (runningList) {
                        // Remove completed or cancelled battles
                        const renderedRunningCards = runningList.querySelectorAll('.ludo-running-card[data-battle-id]');
                        renderedRunningCards.forEach(card => {
                            const id = parseInt(card.getAttribute('data-battle-id'), 10);
                            if (!data.running_battle_ids.includes(id)) {
                                card.remove();
                            }
                        });

                        // Add new running battles or update in-place
                        data.running_battles.forEach(item => {
                            const existingCard = runningList.querySelector(`.ludo-running-card[data-battle-id="${item.id}"]`);
                            if (!existingCard && item.card_html) {
                                const emptyEl = document.getElementById('running-battles-empty');
                                if (emptyEl) emptyEl.remove();

                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = item.card_html.trim();
                                const newCard = tempDiv.firstElementChild;
                                if (newCard) {
                                    runningList.prepend(newCard);
                                    applyCardStagger(runningList);
                                }
                            } else if (existingCard && item.card_html) {
                                // Update card content if changed without replaying entrance animation
                                const tempDiv = document.createElement('div');
                                tempDiv.innerHTML = item.card_html.trim();
                                const updatedCard = tempDiv.firstElementChild;
                                if (updatedCard) {
                                    updatedCard.classList.add('ludo-card-animated');
                                    existingCard.replaceWith(updatedCard);
                                }
                            }
                        });

                        if (runningList.querySelectorAll('.ludo-running-card').length === 0 && !document.getElementById('running-battles-empty')) {
                            const emptyDiv = document.createElement('div');
                            emptyDiv.id = 'running-battles-empty';
                            emptyDiv.innerHTML = `
                                <div class="ludo-empty-state text-center py-4 px-3 my-2" style="background: rgba(22, 16, 46, 0.6); border: 1px dashed rgba(168, 85, 247, 0.25); border-radius: 16px;">
                                    <i class="fa-solid fa-trophy fs-3 text-secondary mb-2 d-block"></i>
                                    <h6 class="text-white fw-bold mb-1">No Matches Currently In Progress</h6>
                                    <p class="text-secondary small mb-0">Matches will appear here as soon as two players begin a contest.</p>
                                </div>`;
                            runningList.appendChild(emptyDiv);
                        }
                    }
                }
            }
        } catch (err) {
            console.debug('Lobby sync poll error:', err);
        } finally {
            isLobbyPolling = false;
        }
    }

    const lobbySyncInterval = setInterval(pollLobbyState, 3000);

    // Fast wake-up when user tabs back into the browser
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            pollLobbyState();
        }
    });

    // Battle amount +50 / -50 steppers & quick chips & Est. Win Prize
    const battleInput = document.getElementById('ludoBattleAmountInput');
    const battleMinusBtn = document.getElementById('btnBattleMinus50');
    const battlePlusBtn = document.getElementById('btnBattlePlus50');
    const estPrizeBadge = document.getElementById('ludoEstPrizeBadge');

    function updateEstPrizeBadge() {
        if (!battleInput || !estPrizeBadge) return;
        const amount = parseInt(battleInput.value, 10) || 50;
        const pool = amount * 2;
        let fee = 0;
        let feeLabel = '';
        if (amount < 250) {
            fee = Math.round(amount * 0.10);
            feeLabel = '10% fee';
        } else if (amount <= 500) {
            fee = 25;
            feeLabel = 'flat 25 fee';
        } else {
            fee = Math.round(amount * 0.05);
            feeLabel = '5% fee';
        }
        const prize = pool - fee;
        estPrizeBadge.textContent = `Est. Win Prize: 🪙 ${prize} (${feeLabel})`;
    }

    if (battleInput && battleMinusBtn && battlePlusBtn) {
        battleMinusBtn.addEventListener('click', function() {
            const min = parseInt(battleInput.min, 10) || 50;
            const max = parseInt(battleInput.max, 10) || 50000;
            let val = (parseInt(battleInput.value, 10) || min) - 50;
            if (val < min) val = min;
            if (val > max) val = max;
            battleInput.value = val;
            updateEstPrizeBadge();
        });

        battlePlusBtn.addEventListener('click', function() {
            const min = parseInt(battleInput.min, 10) || 50;
            const max = parseInt(battleInput.max, 10) || 50000;
            let val = (parseInt(battleInput.value, 10) || min) + 50;
            if (val < min) val = min;
            if (val > max) val = max;
            battleInput.value = val;
            updateEstPrizeBadge();
        });

        battleInput.addEventListener('input', updateEstPrizeBadge);
        updateEstPrizeBadge();
    }

    document.querySelectorAll('.ludo-battle-quick-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            if (battleInput) {
                battleInput.value = this.dataset.amount;
                updateEstPrizeBadge();
            }
        });
    });
});
</script>
@endpush
