@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — ' . ($lang === 'hi' ? 'लूडो टूर्नामेंट नियम एवं फेयर प्ले नीति' : 'Tournament Rules & Fair Play Policy'))

@section('content')
    {{-- Header with Language Switcher --}}
    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-secondary border-opacity-25">
        <div>
            <h4 class="fw-bold text-white mb-0" style="font-size: 19px; letter-spacing: -0.3px;">
                <i class="fa-solid fa-scale-balanced text-warning me-2"></i>
                {{ $lang === 'hi' ? 'नियम और दिशानिर्देश' : 'Rules & Guidelines' }}
            </h4>
            <div class="text-secondary small mt-1" style="font-size: 11.5px;">
                {{ $lang === 'hi' ? 'खेलने से पहले सभी नियमों को ध्यान से पढ़ें।' : 'Read all rules carefully before playing.' }}
            </div>
        </div>
        <div class="btn-group btn-group-sm shadow-sm" style="border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 20px; overflow: hidden;">
            <a href="{{ route('ludo.rules', ['lang' => 'hi']) }}" class="btn {{ $lang === 'hi' ? 'btn-info text-dark fw-bold' : 'btn-dark text-light' }}" style="font-size: 11.5px; padding: 4px 12px;">
                🇮🇳 हिंदी
            </a>
            <a href="{{ route('ludo.rules', ['lang' => 'en']) }}" class="btn {{ $lang === 'en' ? 'btn-info text-dark fw-bold' : 'btn-dark text-light' }}" style="font-size: 11.5px; padding: 4px 12px;">
                🌐 English
            </a>
        </div>
    </div>

    @if(!empty($customRules))
        <div class="p-3 rounded-3 mb-3" style="background: linear-gradient(165deg, #132247, #0a1733); border: 1.5px solid #38bdf8; box-shadow: 0 4px 14px rgba(56, 189, 248, 0.15);">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="fa-solid fa-bullhorn text-warning"></i>
                <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">{{ $lang === 'hi' ? 'आधिकारिक व्यवस्थापक दिशानिर्देश' : 'Official Administration Policy' }}</h5>
            </div>
            <div class="text-light small" style="white-space: pre-line; line-height: 1.6;">
                {{ $customRules }}
            </div>
        </div>
    @endif

    {{-- Important Announcement Alert Banner --}}
    <div class="p-3 rounded-3 mb-3" style="background: linear-gradient(135deg, rgba(234, 179, 8, 0.15), rgba(15, 23, 42, 0.95)); border: 1.5px solid rgba(234, 179, 8, 0.6); box-shadow: 0 4px 16px rgba(234, 179, 8, 0.12);">
        <div class="d-flex align-items-start gap-2">
            <span class="fs-5 text-warning">⚠️</span>
            <div>
                <h6 class="fw-bold text-warning mb-1" style="font-size: 13.5px;">
                    {{ $lang === 'hi' ? 'अनिवार्य सूचना — फेयर प्ले एवं वीडियो रिकॉर्डिंग' : 'Mandatory Fair Play & Video Recording Notice' }}
                </h6>
                <p class="text-light small mb-0" style="font-size: 12px; line-height: 1.5;">
                    {{ $lang === 'hi' 
                        ? 'हर मैच को शुरू से अंत तक स्क्रीन रिकॉर्ड करना अनिवार्य है। विवाद या ऑटो-एग्जिट की स्थिति में केवल वीडियो प्रूफ ही मान्य होगा। बिना वीडियो प्रमाण के एडमिन का निर्णय ही सर्वोपरि होगा।' 
                        : 'Screen recording each match from start to finish is mandatory. In case of dispute or auto-exit, video evidence is the only accepted proof. Without video proof, the Admin decision is final.' }}
                </p>
            </div>
        </div>
    </div>

    {{-- SECTION: COMMISSION RATES (कमीशन दरें) --}}
    <div class="p-3 rounded-3 mb-4" style="background: linear-gradient(165deg, #0d1b3e, #070f24); border: 1.5px solid #38bdf8; box-shadow: 0 6px 20px rgba(56, 189, 248, 0.15);">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-info text-dark fw-bold px-2 py-1" style="font-size: 11px;">
                    <i class="fa-solid fa-percent me-1"></i> {{ $lang === 'hi' ? 'कमीशन दरें' : 'Commission Rates' }}
                </span>
                <h5 class="fw-bold text-white mb-0" style="font-size: 15px;">
                    {{ $lang === 'hi' ? 'बैटल कमीशन एवं प्राइज पूल नियम' : 'Battle Commission & Prize Structure' }}
                </h5>
            </div>
            <span class="badge bg-dark text-warning border border-warning border-opacity-50 small">
                {{ config('ludo.brand_name', 'Fsaludoclub') }}
            </span>
        </div>

        <div class="row g-2 mt-1">
            {{-- Slab 1: Below 250 --}}
            <div class="col-12 col-md-4">
                <div class="p-2.5 rounded-2 h-100" style="background: rgba(15, 28, 63, 0.85); border: 1px solid rgba(56, 189, 248, 0.35);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-primary text-white" style="font-size: 10.5px;">&lt; 250 Coins</span>
                        <span class="text-warning fw-bold small">10% Commission</span>
                    </div>
                    <div class="text-light fw-bold" style="font-size: 12px;">
                        {{ $lang === 'hi' ? '250 Coins से कम की बैटल' : 'Battles below 250 Coins' }}
                    </div>
                    <div class="text-secondary small mt-1" style="font-size: 11px; line-height: 1.4;">
                        {{ $lang === 'hi' 
                            ? 'बैटल राशि पर 10% कमीशन लिया जाएगा।' 
                            : '10% commission will be charged on battle amount.' }}
                    </div>
                    <div class="mt-2 pt-1 border-top border-secondary border-opacity-25 text-info small" style="font-size: 10.5px;">
                        <strong>{{ $lang === 'hi' ? 'उदाहरण:' : 'Example:' }}</strong> 🪙 100 Battle &rarr; 🪙 190 Prize (🪙 10 Fee)
                    </div>
                </div>
            </div>

            {{-- Slab 2: 250 to 500 --}}
            <div class="col-12 col-md-4">
                <div class="p-2.5 rounded-2 h-100" style="background: rgba(15, 28, 63, 0.85); border: 1px solid rgba(234, 179, 8, 0.45);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-warning text-dark fw-bold" style="font-size: 10.5px;">250 – 500 Coins</span>
                        <span class="text-warning fw-bold small">Flat 25 Coins</span>
                    </div>
                    <div class="text-light fw-bold" style="font-size: 12px;">
                        {{ $lang === 'hi' ? '250 से 500 Coins के बीच बैटल' : 'Battles 250 to 500 Coins' }}
                    </div>
                    <div class="text-secondary small mt-1" style="font-size: 11px; line-height: 1.4;">
                        {{ $lang === 'hi' 
                            ? 'फ्लैट 25 Coins कमीशन लगेगा।' 
                            : 'Flat 25 Coins commission will be charged.' }}
                    </div>
                    <div class="mt-2 pt-1 border-top border-secondary border-opacity-25 text-warning small" style="font-size: 10.5px;">
                        <strong>{{ $lang === 'hi' ? 'उदाहरण:' : 'Example:' }}</strong> 🪙 250 &rarr; 🪙 475 Prize | 🪙 500 &rarr; 🪙 975 Prize
                    </div>
                </div>
            </div>

            {{-- Slab 3: Above 500 --}}
            <div class="col-12 col-md-4">
                <div class="p-2.5 rounded-2 h-100" style="background: rgba(15, 28, 63, 0.85); border: 1px solid rgba(16, 185, 129, 0.45);">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-success text-white fw-bold" style="font-size: 10.5px;">&gt; 500 Coins</span>
                        <span class="text-success fw-bold small">5% Commission</span>
                    </div>
                    <div class="text-light fw-bold" style="font-size: 12px;">
                        {{ $lang === 'hi' ? '500 Coins से अधिक की बैटल' : 'Battles above 500 Coins' }}
                    </div>
                    <div class="text-secondary small mt-1" style="font-size: 11px; line-height: 1.4;">
                        {{ $lang === 'hi' 
                            ? 'बैटल राशि पर 5% कमीशन लिया जाएगा।' 
                            : '5% commission will be charged on battle amount.' }}
                    </div>
                    <div class="mt-2 pt-1 border-top border-secondary border-opacity-25 text-success small" style="font-size: 10.5px;">
                        <strong>{{ $lang === 'hi' ? 'उदाहरण:' : 'Example:' }}</strong> 🪙 1000 Battle &rarr; 🪙 1950 Prize (🪙 50 Fee)
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION: THE 10 OFFICIAL LUDO RULES --}}
    @if($lang === 'hi')
        {{-- HINDI VERSION (हिंदी नियम) --}}
        <div class="d-flex flex-column gap-3 mb-4">

            {{-- 1. Autoexit & 100% Loss --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #ef4444; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 11px;">नियम 1</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            सामान्य टूर्नामेंट नियम — Autoexit & 100% Loss
                        </h5>
                    </div>
                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger small">100% Loss</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    यदि आप <strong>जान भुजकर Autoexit</strong> करते है तो भी आपको <strong>100% Loss</strong> कर दिया जायेगा ! यदि दोनों प्लेयर में किसी की काटी खुली नहीं तो उसे हम <strong>कैंसिल</strong> कर सकते है !
                </div>
            </div>

            {{-- 2. Token Status & Loss % --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #f59e0b; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 11px;">नियम 2</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            टोकन स्थिति एवं लॉस प्रतिशत (30% vs 100% Loss)
                        </h5>
                    </div>
                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning small">30% / 100% Loss</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    यदि एक टोकन बाहर है और घर के पास है तो <strong>30% Loss</strong> दिया जायेगा लेकिन यदि गेम खेला गया है और <strong>2 काटी बहार आयी हो</strong> तो गेम को लेफ्ट करने वाले को <strong>100% Loss</strong> कर दिया जायेगा !
                </div>
            </div>

            {{-- 3. Autoexit with 1 Token vs Deliberate Exit --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #fbbf24; box-shadow: 0 4px 14px rgba(251, 191, 36, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 11px;">नियम 3</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Autoexit 1 टोकन एवं एडमिन का अंतिम निर्णय
                        </h5>
                    </div>
                    <span class="badge bg-secondary text-light small">Admin Final Decision</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    Autoexit में यदि <strong>1 टोकन बाहर है</strong> तो गेम <strong>कैंसिल</strong> किया जा सकता है लेकिन यदि आपने गेम <strong>जान भुजकर</strong> में छोड़ा होगा तो आपको <strong>Loss</strong> ही दिया जायेगा इसमें <strong>अंतिम निर्णय Admin का होगा !</strong>
                </div>
            </div>

            {{-- 4. Video Proof for Autoexit --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #22d3ee; box-shadow: 0 4px 14px rgba(34, 211, 238, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info text-dark fw-bold px-2 py-1" style="font-size: 11px;">नियम 4</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Autoexit विवाद एवं वीडियो प्रूफ की अनिवार्यता
                        </h5>
                    </div>
                    <span class="badge bg-info bg-opacity-25 text-info border border-info small">📹 Video Mandatory</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    यदि आपको लगता है की Opponent ने जानभूझकर गेम को Autoexit में छोड़ा है लेकिन Admin ने कैंसिल कर दिया है तो आपसे <strong>वीडियो प्रूफ माँगा जायेगा इसलिए हर गेम को रिकॉर्ड करना जरुरी है !</strong> यदि आप वीडियो प्रूफ नहीं देते है तो गेम रिजल्ट एडमिन के अनुसार ही अपडेट किया जायेगा चाहे आप विन हो या गेम कैंसिल हो !
                </div>
            </div>

            {{-- 5. 15-Minute Result Submission Window --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #38bdf8; box-shadow: 0 4px 14px rgba(56, 189, 248, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white fw-bold px-2 py-1" style="font-size: 11px;">नियम 5</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            15 मिनट के अंदर रिजल्ट पोस्ट करने की समय-सीमा
                        </h5>
                    </div>
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary small">⏱️ 15-Min Limit</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    Game समाप्त होने के <strong>15 मिनट के अंदर रिजल्ट डालना आवश्यक है</strong> अन्यथा Opponent के रिजल्ट के आधार पर गेम अपडेट कर दिया जायेगा चाहे आप जीते या हारे और इसमें <strong>पूरी ज़िम्मेदारी आपकी होगी</strong> इसमें बाद में कोई बदलाव नहीं किया जा सकता है !
                </div>
            </div>

            {{-- 6. Fake Screenshot Penalty & Live Support --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #ef4444; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 11px;">नियम 6</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            गलत स्क्रीनशॉट पर सीधा Cancel एवं Live Chat
                        </h5>
                    </div>
                    <span class="badge bg-danger text-white small">🚫 Direct Cancel</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    Win होने के बाद आप <strong>गलत स्क्रीनशॉट</strong> डालते है तो गेम को <strong>सीधा Cancel कर दिया जायेगा</strong> इसलिए यदि आप स्क्रीनशॉट लेना भूल गए है तो <strong>पहले Live Chat में एडमिन को संपर्क करे</strong> उसके बाद ही उनके बताये अनुसार रिजल्ट पोस्ट करे !
                </div>
            </div>

            {{-- 7. Zero Tokens Open Safe Cancellation --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #10b981; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success text-white fw-bold px-2 py-1" style="font-size: 11px;">नियम 7</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            काटी खुली न होने पर लेफ्ट एवं गेम कैंसिल
                        </h5>
                    </div>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success small">✅ Safe Cancel</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    दोनों प्लेयर की <strong>टोकन (काटी) घर से बाहर न आयी हो</strong> तो लेफ्ट होकर गेम कैंसिल किया जा सकता है ! <strong>[कैंसिल प्रूफ करने के लिए वीडियो आवश्यक होगा]</strong>
                </div>
            </div>

            {{-- 8. No Gameplay After Submitting Cancel --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #f97316; box-shadow: 0 4px 14px rgba(249, 115, 22, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 11px;">नियम 8</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            'कैंसिल' रिजल्ट डालने के बाद गेम प्ले न करें
                        </h5>
                    </div>
                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning small">No Liability</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    'कैंसिल' रिजल्ट डालने के बाद गेम प्ले करके जीत जाते है तो उसमे <strong>हमारी कोई ज़िम्मेदारी नहीं होगी</strong> अतः गेम कैंसिल करने के बाद स्टार्ट न करे अन्यथा वो <strong>कैंसिल ही माना जायेगा</strong>।
                </div>
            </div>

            {{-- 9. Irreversible Result Submission & Penalty --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #ef4444; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 11px;">नियम 9</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            रिजल्ट अपरिवर्तनीय एवं पेनल्टी का नियम
                        </h5>
                    </div>
                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger small">⚠️ Penalty Rule</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    एक बार <strong>रिजल्ट डालने के बाद बदला नहीं जा सकता है</strong> इसलिए सोच समझकर रिजल्ट पोस्ट करे, <strong>गलत रिजल्ट डालने पर पेनल्टी भी लगायी जाएगी</strong> चाहे आपने वो गलती से डाला हो या जान भुजकर।
                </div>
            </div>

            {{-- 10. Fresh ID Policy & Dice Usage Proof --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #a855f7; box-shadow: 0 4px 14px rgba(168, 85, 247, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-purple text-white fw-bold px-2 py-1" style="background: #9333ea; font-size: 11px;">नियम 10</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Fresh आईडी गेम एवं पासा उपयोग का प्रमाण
                        </h5>
                    </div>
                    <span class="badge text-white small" style="background: rgba(147, 51, 234, 0.3); border: 1px solid #9333ea;">🎲 Fresh ID Rule</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    Fresh आईडी गेम के मामले में <strong>केवल पासा के उपयोग के प्रमाण पर रद्द कर दिया जाएगा गोटी के खुले होने के बावजूद नहीं ?</strong> गेम का <strong>रिकॉर्ड होना जरूरी है</strong> अगर कोई अपनी आईडी का नाम <em>'नो फ्रेश'</em> कर रखा तो नो फ्रेश आईडी ही टेक करे जिनका इंटरेस्ट हो वही टेक करे टेबल को <strong>(इसका अंतिम रिजल्ट एडमिन देगा)</strong>।
                </div>
            </div>

        </div>

    @else
        {{-- ENGLISH VERSION --}}
        <div class="d-flex flex-column gap-3 mb-4">

            {{-- 1. Autoexit & 100% Loss --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #ef4444; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 11px;">Rule 1</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            General Tournament Rules — Auto-Exit & 100% Loss Penalty
                        </h5>
                    </div>
                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger small">100% Loss</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    If you <strong>deliberately trigger an Auto-exit</strong>, you will be penalized with an immediate <strong>100% Loss</strong>! However, if neither player's token (goti) has opened out of the home base, the match can be safely cancelled.
                </div>
            </div>

            {{-- 2. Token Status & Loss % --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #f59e0b; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 11px;">Rule 2</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Token Position & Loss Percentage (30% vs 100% Loss)
                        </h5>
                    </div>
                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning small">30% / 100% Loss</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    If only 1 token has exited and remains near the home yard, a <strong>30% Loss penalty</strong> applies. However, if gameplay has progressed and <strong>2 or more tokens are out on the board</strong>, the player leaving the match receives a <strong>100% Loss</strong>!
                </div>
            </div>

            {{-- 3. Autoexit with 1 Token vs Deliberate Exit --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #fbbf24; box-shadow: 0 4px 14px rgba(251, 191, 36, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 11px;">Rule 3</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Auto-Exit with 1 Token vs Admin Discretion
                        </h5>
                    </div>
                    <span class="badge bg-secondary text-light small">Admin Final Decision</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    In Auto-exit scenarios where <strong>only 1 token is out</strong>, the match may be cancelled. However, if you have <strong>deliberately abandoned the match</strong>, a <strong>Loss will be awarded</strong>. In all cases, <strong>the final decision rests solely with the Admin!</strong>
                </div>
            </div>

            {{-- 4. Video Proof for Autoexit --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #22d3ee; box-shadow: 0 4px 14px rgba(34, 211, 238, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info text-dark fw-bold px-2 py-1" style="font-size: 11px;">Rule 4</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Auto-Exit Disputes & Mandatory Video Recording Proof
                        </h5>
                    </div>
                    <span class="badge bg-info bg-opacity-25 text-info border border-info small">📹 Video Mandatory</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    If you believe your opponent deliberately triggered an Auto-exit but Admin has marked the game cancelled, <strong>video recording proof will be demanded</strong>. Hence, <strong>screen recording every match is mandatory!</strong> Without video proof, the match outcome will be finalized solely per Admin discretion, whether win or cancel.
                </div>
            </div>

            {{-- 5. 15-Minute Result Submission Window --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #38bdf8; box-shadow: 0 4px 14px rgba(56, 189, 248, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white fw-bold px-2 py-1" style="font-size: 11px;">Rule 5</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Strict 15-Minute Result Submission Window
                        </h5>
                    </div>
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary small">⏱️ 15-Min Limit</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    You must submit your match result <strong>within 15 minutes of game completion</strong>. Otherwise, the game will be updated based solely on the opponent's submitted result (win or loss), and <strong>full liability lies with you</strong>. No modifications will be entertained thereafter!
                </div>
            </div>

            {{-- 6. Fake Screenshot Penalty & Live Support --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #ef4444; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 11px;">Rule 6</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Incorrect Screenshot = Direct Cancellation & Live Chat
                        </h5>
                    </div>
                    <span class="badge bg-danger text-white small">🚫 Direct Cancel</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    If you win but post an <strong>incorrect or fake screenshot</strong>, the match will be <strong>directly Cancelled</strong>! If you forgot to capture a screenshot, <strong>contact Admin via Live Chat first</strong> and post results only according to their instructions.
                </div>
            </div>

            {{-- 7. Zero Tokens Open Safe Cancellation --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #10b981; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success text-white fw-bold px-2 py-1" style="font-size: 11px;">Rule 7</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Zero Tokens Out Safe Cancellation
                        </h5>
                    </div>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success small">✅ Safe Cancel</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    If neither player's tokens (gotis) have exited the home yard (all 4 tokens locked inside), you may leave and cancel the match. <strong>[Video proof is mandatory to substantiate cancellation]</strong>.
                </div>
            </div>

            {{-- 8. No Gameplay After Submitting Cancel --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #f97316; box-shadow: 0 4px 14px rgba(249, 115, 22, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 11px;">Rule 8</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Do NOT Resume Gameplay After Submitting 'Cancel'
                        </h5>
                    </div>
                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning small">No Liability</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    If you submit a 'Cancel' result and subsequently continue playing and win, <strong>the platform bears zero liability</strong>. Do not restart or play after claiming cancel; it will remain marked as Cancelled.
                </div>
            </div>

            {{-- 9. Irreversible Result Submission & Penalty --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #ef4444; box-shadow: 0 4px 14px rgba(239, 68, 68, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 11px;">Rule 9</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Irreversible Results & Strict False Result Penalty
                        </h5>
                    </div>
                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger small">⚠️ Penalty Rule</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    Once a match result is submitted, <strong>it CANNOT be changed</strong>. Verify carefully before posting. <strong>Submitting a false result attracts severe penalties</strong>, whether posted mistakenly or intentionally.
                </div>
            </div>

            {{-- 10. Fresh ID Policy & Dice Usage Proof --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #161a2e, #0c1020); border: 1.5px solid #a855f7; box-shadow: 0 4px 14px rgba(168, 85, 247, 0.12);">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-purple text-white fw-bold px-2 py-1" style="background: #9333ea; font-size: 11px;">Rule 10</span>
                        <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">
                            Fresh ID Match Policy & Dice Roll Proof
                        </h5>
                    </div>
                    <span class="badge text-white small" style="background: rgba(147, 51, 234, 0.3); border: 1px solid #9333ea;">🎲 Fresh ID Rule</span>
                </div>
                <div class="text-light small ps-1" style="font-size: 12.5px; line-height: 1.6;">
                    In Fresh ID matches, cancellation will only be granted <strong>upon video proof of dice usage, not solely whether tokens are open</strong>. Full screen recording of the game is mandatory. If an ID has named itself <em>'No Fresh'</em>, only non-fresh accounts should accept the challenge; join only if interested. <strong>(The Admin gives the final verdict)</strong>.
                </div>
            </div>

        </div>
    @endif

    {{-- Bottom Action Button --}}
    <div class="text-center mt-3 mb-4">
        <a href="{{ route('ludo.battles') }}" class="btn btn-warning rounded-pill px-5 fw-bold shadow-lg" style="font-size: 13.5px;">
            <i class="fa-solid fa-gamepad me-1"></i> {{ $lang === 'hi' ? 'बैटल एरीना में वापस जाएं' : 'Return to Battle Arena' }}
        </a>
    </div>
@endsection
