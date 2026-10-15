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
        Schema::table('ludo_battles', function (Blueprint $table) {
            if (!Schema::hasColumn('ludo_battles', 'creator_evidence_path')) {
                $table->string('creator_evidence_path', 255)->nullable()->after('joiner_claimed_result');
            }
            if (!Schema::hasColumn('ludo_battles', 'joiner_evidence_path')) {
                $table->string('joiner_evidence_path', 255)->nullable()->after('creator_evidence_path');
            }
            if (!Schema::hasColumn('ludo_battles', 'refund_status')) {
                $table->string('refund_status', 32)->default('none')->after('cancellation_reason')->index();
            }
            if (!Schema::hasColumn('ludo_battles', 'refund_requested_by_id')) {
                $table->foreignId('refund_requested_by_id')->nullable()->constrained('users')->onDelete('set null')->after('refund_status');
            }
            if (!Schema::hasColumn('ludo_battles', 'refund_reason')) {
                $table->text('refund_reason')->nullable()->after('refund_requested_by_id');
            }
            if (!Schema::hasColumn('ludo_battles', 'refund_decision_reason')) {
                $table->text('refund_decision_reason')->nullable()->after('refund_reason');
            }
            if (!Schema::hasColumn('ludo_battles', 'admin_reviewed_at')) {
                $table->timestamp('admin_reviewed_at')->nullable()->after('refund_decision_reason');
            }
            if (!Schema::hasColumn('ludo_battles', 'admin_reviewed_by_id')) {
                $table->foreignId('admin_reviewed_by_id')->nullable()->constrained('users')->onDelete('set null')->after('admin_reviewed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ludo_battles', function (Blueprint $table) {
            $cols = [
                'creator_evidence_path',
                'joiner_evidence_path',
                'refund_status',
                'refund_requested_by_id',
                'refund_reason',
                'refund_decision_reason',
                'admin_reviewed_at',
                'admin_reviewed_by_id',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('ludo_battles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
