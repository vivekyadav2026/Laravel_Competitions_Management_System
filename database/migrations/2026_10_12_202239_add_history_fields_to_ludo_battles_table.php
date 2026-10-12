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
            if (!Schema::hasColumn('ludo_battles', 'claimed_result')) {
                $table->enum('claimed_result', ['pending', 'won', 'lost', 'cancelled'])->default('pending')->after('winner_id');
            }
            if (!Schema::hasColumn('ludo_battles', 'final_result')) {
                $table->enum('final_result', ['pending', 'won', 'lost', 'cancelled', 'draw'])->default('pending')->after('claimed_result');
            }
            if (!Schema::hasColumn('ludo_battles', 'review_status')) {
                $table->enum('review_status', ['auto_approved', 'under_review', 'verified', 'disputed'])->default('auto_approved')->after('final_result');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ludo_battles', function (Blueprint $table) {
            $table->dropColumn(['claimed_result', 'final_result', 'review_status']);
        });
    }
};
