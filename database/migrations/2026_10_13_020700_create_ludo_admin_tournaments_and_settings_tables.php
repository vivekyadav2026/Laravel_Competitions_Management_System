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
        // 1. Add status and suspension reason to users if not present
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'suspended'])->default('active')->after('role');
            }
            if (!Schema::hasColumn('users', 'suspension_reason')) {
                $table->text('suspension_reason')->nullable()->after('status');
            }
        });

        // 2. Admin Action Logs (Comprehensive Audit Trail)
        Schema::create('ludo_admin_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('action_type', 50); // user_suspended, user_reactivated, match_cancelled, dispute_resolved, ledger_adjusted, content_updated, settings_updated
            $table->string('target_type', 50)->nullable(); // User, LudoBattle, LudoDispute, LudoLedgerTransaction, Setting
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'created_at']);
        });

        // 3. Ludo Tournaments Table (Bracketed Tournaments)
        Schema::create('ludo_tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('tournament_uid', 32)->unique();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->unsignedInteger('entry_fee')->default(100);
            $table->unsignedInteger('prize_pool')->default(1500);
            $table->unsignedInteger('max_players')->default(16); // 4, 8, 16, 32
            $table->enum('status', ['draft', 'published', 'running', 'completed', 'cancelled'])->default('draft')->index();
            $table->dateTime('registration_closes_at')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('rules_text')->nullable();
            $table->json('bracket_data')->nullable(); // Tree structure for rounds & match IDs
            $table->foreignId('created_by_admin_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('winner_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // 4. Tournament Participants
        Schema::create('ludo_tournament_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('ludo_tournaments')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('seed')->nullable();
            $table->unsignedInteger('current_round')->default(1);
            $table->enum('status', ['registered', 'active', 'eliminated', 'winner'])->default('registered');
            $table->timestamps();

            $table->unique(['tournament_id', 'user_id']);
        });

        // 5. System Settings (Key-Value store for CMS and Admin Settings)
        Schema::create('ludo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->text('value')->nullable();
            $table->string('group', 40)->default('general'); // general, rules, announcements, limits, themes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ludo_settings');
        Schema::dropIfExists('ludo_tournament_participants');
        Schema::dropIfExists('ludo_tournaments');
        Schema::dropIfExists('ludo_admin_audit_logs');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'suspension_reason']);
        });
    }
};
