<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Support Tickets
        Schema::create('ludo_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_uid', 32)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('category', ['battle_issue', 'deposit_wallet', 'account_profile', 'kyc_issue', 'technical_bug', 'other'])->default('other');
            $table->string('subject', 190);
            $table->text('description');
            $table->enum('status', ['open', 'in_progress', 'answered', 'closed'])->default('open')->index();
            $table->enum('priority', ['normal', 'urgent'])->default('normal');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_mime', 100)->nullable();
            $table->timestamps();
        });

        // 2. Support Ticket Messages / Conversation
        Schema::create('ludo_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('ludo_support_tickets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_admin')->default(false);
            $table->text('message');
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });

        // 3. KYC Submissions
        Schema::create('ludo_kyc_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('document_type', 40); // aadhaar, pan, voter_id, driving_license
            $table->string('document_number', 80);
            $table->string('front_image_path');
            $table->string('back_image_path')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('admin_reason')->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // 4. Disputes Table
        Schema::create('ludo_disputes', function (Blueprint $table) {
            $table->id();
            $table->string('dispute_uid', 32)->unique();
            $table->foreignId('battle_id')->constrained('ludo_battles')->onDelete('cascade');
            $table->foreignId('opened_by_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('against_user_id')->constrained('users')->onDelete('cascade');
            $table->string('reason_category', 60); // wrong_result, opponent_left, fake_screenshot, app_crash, timer_expired, other
            $table->text('description');
            $table->enum('status', ['open', 'under_review', 'more_evidence_requested', 'resolved', 'rejected'])->default('open')->index();
            $table->string('evidence_path')->nullable();
            $table->text('admin_decision_reason')->nullable();
            $table->foreignId('resolved_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        // 5. Dispute Evidence Logs (Multi-party evidence submissions)
        Schema::create('ludo_dispute_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispute_id')->constrained('ludo_disputes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->string('file_path');
            $table->string('file_mime', 100)->nullable();
            $table->timestamps();
        });

        // 6. Extend ludo_battles with submission timer & dispute flag if not already present
        Schema::table('ludo_battles', function (Blueprint $table) {
            if (!Schema::hasColumn('ludo_battles', 'creator_claimed_result')) {
                $table->enum('creator_claimed_result', ['pending', 'won', 'lost', 'cancelled'])->default('pending')->after('review_status');
            }
            if (!Schema::hasColumn('ludo_battles', 'joiner_claimed_result')) {
                $table->enum('joiner_claimed_result', ['pending', 'won', 'lost', 'cancelled'])->default('pending')->after('creator_claimed_result');
            }
            if (!Schema::hasColumn('ludo_battles', 'submission_deadline_at')) {
                $table->timestamp('submission_deadline_at')->nullable()->after('joiner_claimed_result');
            }
            if (!Schema::hasColumn('ludo_battles', 'dispute_id')) {
                $table->foreignId('dispute_id')->nullable()->constrained('ludo_disputes')->onDelete('set null')->after('submission_deadline_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ludo_battles', function (Blueprint $table) {
            $table->dropForeign(['dispute_id']);
            $table->dropColumn(['creator_claimed_result', 'joiner_claimed_result', 'submission_deadline_at', 'dispute_id']);
        });

        Schema::dropIfExists('ludo_dispute_evidences');
        Schema::dropIfExists('ludo_disputes');
        Schema::dropIfExists('ludo_kyc_submissions');
        Schema::dropIfExists('ludo_ticket_messages');
        Schema::dropIfExists('ludo_support_tickets');
    }
};
