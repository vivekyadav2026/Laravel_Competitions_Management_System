@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Tournament Rules & Fair Play Policy')

@section('content')
    {{-- Language Toggle Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="fw-bold text-white mb-0" style="font-size: 18px;">
            <i class="fa-solid fa-scale-balanced text-warning me-1"></i> 
            {{ $lang === 'hi' ? 'नियम और दिशानिर्देश' : 'Rules & Guidelines' }}
        </h4>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('ludo.rules', ['lang' => 'en']) }}" class="btn {{ $lang === 'en' ? 'btn-primary' : 'btn-outline-secondary text-light' }}" style="font-size: 11px;">
                English
            </a>
            <a href="{{ route('ludo.rules', ['lang' => 'hi']) }}" class="btn {{ $lang === 'hi' ? 'btn-primary' : 'btn-outline-secondary text-light' }}" style="font-size: 11px;">
                हिंदी
            </a>
        </div>
    </div>

    @if(!empty($customRules))
        <div class="p-3 rounded-3 mb-3" style="background: linear-gradient(165deg, #132247, #0a1733); border: 1.5px solid #38bdf8;">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="fa-solid fa-bullhorn text-warning"></i>
                <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">{{ $lang === 'hi' ? 'आधिकारिक व्यवस्थापक दिशानिर्देश' : 'Official Administration Policy' }}</h5>
            </div>
            <div class="text-light small" style="white-space: pre-line; line-height: 1.6;">
                {{ $customRules }}
            </div>
        </div>
    @endif

    @if($lang === 'hi')
        {{-- HINDI VERSION --}}
        <div class="d-flex flex-column gap-3 mb-4">
            {{-- 1. सामान्य नियम --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #2563eb;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary">1</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">सामान्य टूर्नामेंट नियम (General Rules)</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>सभी मैच केवल <strong>Classic 2-Player Match</strong> मोड में आधिकारिक लूडो ऐप पर खेले जाने चाहिए।</li>
                    <li>चुनौती स्वीकार करने वाले दोनों खिलाड़ियों को दिए गए <strong>Room Code</strong> का उपयोग करके तुरंत गेम शुरू करना होगा।</li>
                    <li>किसी भी प्रकार की चीटिंग, ऑटो-क्लिकर, या थर्ड-पार्टी मॉड का उपयोग पूर्णतः प्रतिबंधित है और इससे खाता स्थायी रूप से ब्लॉक हो सकता है।</li>
                </ul>
            </div>

            {{-- 2. ऑटो-एग्जिट और अधूरा मैच --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid rgba(234, 179, 8, 0.4);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark">2</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">ऑटो-एग्जिट और अधूरा मैच (Auto-Exit & Incomplete Matches)</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>यदि कोई खिलाड़ी मैच के बीच में गेम से बाहर निकल जाता है (Left match), तो दूसरा खिलाड़ी वैध स्क्रीनशॉट सबमिट करके जीत का दावा कर सकता है।</li>
                    <li>यदि सर्वर या लूडो ऐप क्रैश होता है, तो दोनों खिलाड़ियों को पुनः प्रयास करने या आपसी सहमति से मैच रद्द करने की सलाह दी जाती है।</li>
                </ul>
            </div>

            {{-- 3. परिणाम और साक्ष्य की आवश्यकता --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #38bdf8;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-info text-dark">3</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">साक्ष्य आवश्यकताएं एवं 15 मिनट की समय सीमा (Evidence & 15-Min Window)</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>मैच खत्म होने के बाद परिणाम सबमिट करने की समय सीमा <strong>15 मिनट</strong> है।</li>
                    <li><strong>महत्वपूर्ण नीति:</strong> समय सीमा समाप्त होने (Expired submission) पर स्वचालित रूप से प्रतिद्वंद्वी को जीत नहीं दी जाती; बल्कि प्रशासक द्वारा मैन्युअल समीक्षा की जाती है।</li>
                    <li>जीत का दावा करते समय स्पष्ट <strong>Final Score Screen</strong> का स्क्रीनशॉट आवश्यक है।</li>
                </ul>
            </div>

            {{-- 4. रद्द करने की नीति --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid rgba(255,255,255,0.15);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-secondary">4</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">रद्द करने की नीति (Cancellation Policy)</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>जब तक कोई विरोधी मैच में शामिल नहीं होता, तब तक क्रिएटर बिना किसी कटौती के मैच रद्द कर सकता है।</li>
                    <li>मैच शुरू होने के बाद यदि रूम कोड काम नहीं करता, तो दोनों खिलाड़ी रद्द करने का विकल्प चुन सकते हैं।</li>
                </ul>
            </div>

            {{-- 5. फ्रेश आईडी नीति --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid rgba(168, 85, 247, 0.4);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-purple text-white" style="background: #9333ea;">5</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">फ्रेश आईडी नीति (Fresh ID Policy)</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>फेयर-प्ले सुनिश्चित करने के लिए सभी खिलाड़ियों को वास्तविक और सक्रिय लूडो आईडी के साथ खेलना चाहिए।</li>
                    <li>फेक या डमी आईडी से दूसरों को गुमराह करना सख्त वर्जित है।</li>
                </ul>
            </div>

            {{-- 6. विवाद निपटान और प्रशासनिक निर्णय --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #ef4444;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-danger">6</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">विवाद निपटान (Dispute Resolution & Admin Discretion)</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>यदि दोनों खिलाड़ी अलग-अलग परिणाम का दावा करते हैं, तो सिस्टम स्वचालित रूप से एक विवाद (Dispute) दर्ज करेगा।</li>
                    <li>प्रशासक दोनों पक्षों के स्क्रीनशॉट और साक्ष्य देखकर लिखित निर्णय देगा।</li>
                    <li>झूठा स्क्रीनशॉट अपलोड करने वाले का खाता बंद किया जा सकता है।</li>
                </ul>
            </div>

            {{-- 7. वर्चुअल पॉइंट्स नीति --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #10b981;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success">7</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">वर्चुअल पॉइंट्स नीति (Virtual Points Policy)</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>यह प्लेटफॉर्म केवल गैर-नकद (Non-Cash), गैर-हस्तांतरणीय (Non-Transferable) वर्चुअल टूर्नामेंट पॉइंट्स का उपयोग करता है।</li>
                    <li>वर्चुअल पॉइंट्स का कोई वास्तविक नकद मूल्य या रिडेम्पशन नहीं है।</li>
                </ul>
            </div>
        </div>

    @else
        {{-- ENGLISH VERSION --}}
        <div class="d-flex flex-column gap-3 mb-4">
            {{-- 1. General Rules --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #2563eb;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary">1</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">General Tournament Rules</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>All matches must be played strictly in <strong>Classic 2-Player Match</strong> format on the official Ludo app.</li>
                    <li>Both participants must join the match room using the designated 8-digit <strong>Room Code</strong> within 5 minutes of starting.</li>
                    <li>Any form of cheating, emulator automation, multi-accounting, or unfair assistance is strictly prohibited and subject to permanent blacklisting.</li>
                </ul>
            </div>

            {{-- 2. Auto-exit and Incomplete Matches --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid rgba(234, 179, 8, 0.4);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark">2</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">Auto-Exit & Incomplete Matches</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>If an opponent exits mid-match ("Left Match" modal appears), take an immediate, uncropped screenshot to substantiate your victory claim.</li>
                    <li>In the event of an unresolvable server failure or official app crash before the match begins, both players are advised to mutually cancel to receive automatic point refunds.</li>
                </ul>
            </div>

            {{-- 3. Evidence Requirements & 15-Minute Deadline --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #38bdf8;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-info text-dark">3</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">Evidence Requirements & 15-Minute Submission Deadline</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>Both players must submit their match result within a <strong>15-minute window</strong> following match start.</li>
                    <li><strong>Strict Anti-Arbitrary Penalty Policy:</strong> Submissions that cross the 15-minute mark are never automatically awarded as a loss or given to the opponent. Instead, the match is placed under <strong>Administrative Review</strong> for fair verification.</li>
                    <li>Uncropped screenshots showing the clear winning screen, user handles, and room code are mandatory when claiming victory.</li>
                </ul>
            </div>

            {{-- 4. Cancellation Policy --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid rgba(255,255,255,0.15);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-secondary">4</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">Cancellation Policy</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>Open challenges that have not yet been accepted by an opponent may be cancelled anytime with a 100% instant point refund to the creator.</li>
                    <li>Once running, matches can only be cancelled by mutual consensus or administrative dispute resolution.</li>
                </ul>
            </div>

            {{-- 5. Fresh ID Policy --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid rgba(168, 85, 247, 0.4);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge text-white" style="background: #9333ea;">5</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">Fresh ID Policy</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>To maintain leaderboard and fair play integrity, players should maintain genuine gameplay profiles.</li>
                    <li>Impersonation or falsifying player handles to bypass matchmaking controls is grounds for account suspension.</li>
                </ul>
            </div>

            {{-- 6. Dispute Resolution & Administrative Discretion --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #ef4444;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-danger">6</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">Dispute Resolution & Administrative Decisions</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>If both participants claim conflicting outcomes (e.g. both claim 'Won'), the platform opens an auditable dispute file.</li>
                    <li>Authorized administrators review all uploaded visual screenshots, timestamps, and statements before issuing a written verdict.</li>
                    <li>Tampered, edited, or fabricated screenshots result in automatic forfeiture and punitive account bans.</li>
                </ul>
            </div>

            {{-- 7. Virtual-Points Policy --}}
            <div class="p-3 rounded-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #10b981;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success">7</span>
                    <h5 class="fw-bold text-white mb-0" style="font-size: 14.5px;">Virtual Points & Compliance Policy</h5>
                </div>
                <ul class="text-light small ps-3 mb-0" style="font-size: 12.5px; line-height: 1.6;">
                    <li>{{ config('ludo.brand_name', 'Fsaludoclub') }} functions strictly under a non-cash, non-transferable virtual tournament points engine.</li>
                    <li>Virtual points carry no real-money monetary equivalent, cannot be withdrawn as cash, and are designed purely for entertainment and competitive ranking.</li>
                </ul>
            </div>
        </div>
    @endif
@endsection
