<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fsaludoclub Platform Branding Configuration
    |--------------------------------------------------------------------------
    */
    'brand_name' => env('LUDO_BRAND_NAME', 'Fsaludoclub'),
    'brand_display' => env('LUDO_BRAND_DISPLAY', 'FSALUDOCLUB'),
    'brand_tagline' => env('LUDO_BRAND_TAGLINE', 'Play Ludo • Win Glory • King of the Board'),
    'logo_path' => 'assets/images/fsaludoclub-logo.png',
    'logo_icon' => 'assets/images/fsaludoclub-icon.png',

    /*
    |--------------------------------------------------------------------------
    | Real-Money / UPI Deposit Operational Switch
    |--------------------------------------------------------------------------
    | Initial platform operates on free participation and non-cash virtual points.
    | Set to false by default for compliance.
    */
    'upi_deposits_enabled' => env('UPI_DEPOSITS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Direct UPI Payment Configuration (Disabled by default)
    |--------------------------------------------------------------------------
    */
    'upi' => [
        'id' => env('UPI_ID', '9631608403@paytm'),
        'payee_name' => env('UPI_PAYEE_NAME', 'FSALUDOCLUB'),
        'whatsapp' => env('UPI_WHATSAPP', '7303859007'),
        'auto_approve' => env('UPI_AUTO_APPROVE', false),
        'min_deposit' => (int) env('UPI_MIN_DEPOSIT', 50),
        'max_deposit' => (int) env('UPI_MAX_DEPOSIT', 50000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ludo Platform Support Configuration
    |--------------------------------------------------------------------------
    */
    'support' => [
        'heading' => env('LUDO_SUPPORT_HEADING', 'Support Center'),
        'subtitle' => env('LUDO_SUPPORT_SUBTITLE', 'Need assistance? Reach our support desk through official channels or ticket inquiry.'),
        'team_whatsapp_url' => env('LUDO_SUPPORT_WHATSAPP', 'https://wa.me/917303859007?text=Hello%20Fsaludoclub%20Support'),
        'whatsapp_number' => env('LUDO_SUPPORT_WHATSAPP_NUMBER', '7303859007'),
        'instagram_url' => env('LUDO_SUPPORT_INSTAGRAM', 'https://www.instagram.com/fsa.ludo.club'),
        'instagram_handle' => env('LUDO_SUPPORT_INSTAGRAM_HANDLE', 'fsa.ludo.club'),
        'telegram_url' => env('LUDO_SUPPORT_TELEGRAM', 'https://t.me/fsaludoclub'),
        'contact_url' => env('LUDO_SUPPORT_CONTACT_URL', 'mailto:support@fsaludoclub.com'),
        'availability_notice' => env('LUDO_SUPPORT_AVAILABILITY', 'Standard response time: Mon-Sun 10:00 AM - 10:00 PM IST'),
    ],


    /*
    |--------------------------------------------------------------------------
    | KYC Verification Policy
    |--------------------------------------------------------------------------
    */
    'kyc' => [
        'required_for_battles' => env('LUDO_KYC_REQUIRED_BATTLES', false),
        'required_for_redemptions' => env('LUDO_KYC_REQUIRED_REDEMPTIONS', true),
        'allowed_document_types' => ['aadhaar', 'pan', 'voter_id', 'driving_license'],
        'max_file_size_kb' => 5120, // 5MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Battle & Dispute Rules
    |--------------------------------------------------------------------------
    */
    'battle' => [
        'submission_window_minutes' => (int) env('LUDO_SUBMISSION_WINDOW_MINUTES', 15),
        'dispute_window_minutes' => (int) env('LUDO_DISPUTE_WINDOW_MINUTES', 60),
    ],
];
