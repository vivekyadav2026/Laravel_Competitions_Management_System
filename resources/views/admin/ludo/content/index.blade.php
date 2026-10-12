@extends('layouts.admin')

@section('title', 'Content Management — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'CMS & Rules Content Editor')

@section('content')
    <div class="admin-card mb-4">
        <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <i class="fa-solid fa-pen-to-square text-info me-2"></i> Live Content, Announcements & Rules
        </h5>

        <form action="{{ route('admin.ludo.content.update') }}" method="POST">
            @csrf

            {{-- Announcements --}}
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label class="form-label text-white small fw-bold">
                        <i class="fa-solid fa-bullhorn text-warning me-1"></i> Hindi Announcement Ticker
                    </label>
                    <textarea name="announcement_hi" rows="3" class="form-control bg-dark text-light border-secondary small">{{ $announcement_hi }}</textarea>
                    <small class="text-secondary">Appears on the homepage lobby Hindi announcement banner.</small>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-white small fw-bold">
                        <i class="fa-solid fa-bullhorn text-info me-1"></i> English Announcement Ticker
                    </label>
                    <textarea name="announcement_en" rows="3" class="form-control bg-dark text-light border-secondary small">{{ $announcement_en }}</textarea>
                    <small class="text-secondary">Appears on the homepage and notifications ticker.</small>
                </div>
            </div>

            {{-- Bilingual Tournament Rules --}}
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6">
                    <label class="form-label text-white small fw-bold">
                        <i class="fa-solid fa-scale-balanced text-warning me-1"></i> Rules & Guidelines (English)
                    </label>
                    <textarea name="rules_en" rows="8" class="form-control bg-dark text-light border-secondary small font-monospace">{{ $rules_en }}</textarea>
                    <small class="text-secondary">Official English rules text displayed on /rules.</small>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-white small fw-bold">
                        <i class="fa-solid fa-scale-balanced text-primary me-1"></i> Rules & Guidelines (हिंदी)
                    </label>
                    <textarea name="rules_hi" rows="8" class="form-control bg-dark text-light border-secondary small">{{ $rules_hi }}</textarea>
                    <small class="text-secondary">आधिकारिक हिंदी नियम /rules?lang=hi पृष्ठ पर प्रदर्शित होते हैं।</small>
                </div>
            </div>

            {{-- FAQ --}}
            <div class="mb-4">
                <label class="form-label text-white small fw-bold">
                    <i class="fa-solid fa-circle-question text-success me-1"></i> Frequently Asked Questions (FAQ)
                </label>
                <textarea name="faq_content" rows="4" class="form-control bg-dark text-light border-secondary small">{{ $faq_content }}</textarea>
            </div>

            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-save me-1"></i> Save CMS Content & Rules
            </button>
        </form>
    </div>
@endsection
