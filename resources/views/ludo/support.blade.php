@extends('layouts.player')

@section('title', 'Royal Ludo — Support Center')

@section('content')
    {{-- Support Header --}}
    <div class="ludo-support-header">
        <h2 class="ludo-support-title">
            SUPPORT <span>CENTER</span>
        </h2>
        <div class="ludo-support-subtitle">
            We are available 24x7 to help you
        </div>
    </div>

    {{-- Support Action Cards List --}}
    <div class="ludo-support-list">
        {{-- Card 1: Our Support Team --}}
        <a href="https://api.whatsapp.com/send?phone=919876543210&text=Hello%20Royal%20Ludo%20Support,%20I%20need%20assistance" 
           target="_blank" 
           class="ludo-support-item">
            <img src="{{ asset('assets/ludo/avatar-support.svg') }}" alt="Support Team" class="ludo-support-avatar">
            <div class="ludo-support-item-info">
                <div class="ludo-support-item-title">Our Support Team</div>
                <div class="ludo-support-item-sub text-success fw-semibold">
                    <span class="live-dot d-inline-block me-1" style="width: 6px; height: 6px; background: #22c55e; border-radius: 50%;"></span>
                    Typically replies in 2 mins
                </div>
            </div>
            <i class="fa-solid fa-chevron-right text-info"></i>
        </a>

        {{-- Card 2: Telegram Support --}}
        <a href="https://t.me/royalludoofficial" 
           target="_blank" 
           class="ludo-support-item">
            <img src="{{ asset('assets/ludo/telegram.svg') }}" alt="Telegram Support" class="ludo-support-avatar">
            <div class="ludo-support-item-info">
                <div class="ludo-support-item-title">Telegram Support</div>
                <div class="ludo-support-item-sub">Official Channel</div>
            </div>
            <i class="fa-solid fa-chevron-right text-info"></i>
        </a>

        {{-- Card 3: Contact Us / WhatsApp Voice Assistance --}}
        <a href="https://api.whatsapp.com/send?phone=919876543210&text=I%20want%20to%20talk%20to%20Royal%20Ludo%20Helpdesk" 
           target="_blank" 
           class="ludo-support-item">
            <img src="{{ asset('assets/ludo/whatsapp.svg') }}" alt="WhatsApp Support" class="ludo-support-avatar">
            <div class="ludo-support-item-info">
                <div class="ludo-support-item-title">Contact Us</div>
                <div class="ludo-support-item-sub">Voice Assistance</div>
            </div>
            <i class="fa-solid fa-chevron-right text-info"></i>
        </a>
    </div>

    {{-- Verified Security Tag --}}
    <div class="ludo-support-verified-tag">
        <i class="fa-solid fa-shield-halved me-1"></i> SECURE & VERIFIED SUPPORT
    </div>
@endsection
