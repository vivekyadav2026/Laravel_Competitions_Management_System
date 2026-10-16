@props([
    'text' => '⭐ 🙏 🙏 आप सभी से निवेदन कि आप जिस नाम से kyc करें उसी नाम से डिपॉजिट करें अगर आपने केवाईसी नाम के अलावा किसी अन्य व्यक्ति के नाम से डिपॉजिट किया है तो आपकी आईडी को जीरो कर दिया जाएगा और विड्रोल भी उसी अकाउंट या यूपीआई पर होगा (KYC नाम व डिपॉजिट नाम और विड्रॉल नाम एक होना चाहिए) किसी भी समस्या के लिए व्हाट्सएप नंबर 7303859007 पर सम्पर्क करें'
])

<div class="ludo-announcement" role="alert" style="background: linear-gradient(135deg, rgba(8, 28, 48, 0.95) 0%, rgba(13, 20, 36, 0.98) 100%); border: 1.5px solid rgba(0, 210, 255, 0.35); border-radius: 14px; padding: 12px 14px; color: #ffffff; font-size: 11.5px; line-height: 1.55; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45); margin-bottom: 14px; text-align: left;">
    <div style="font-weight: 500;">
        {{ $slot->isEmpty() ? $text : $slot }}
    </div>
</div>
