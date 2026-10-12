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
        Schema::create('ludo_ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('tx_uid', 32)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['deposit', 'entry_fee', 'prize_payout', 'refund', 'referral_bonus', 'admin_adjustment'])->index();
            $table->integer('points_delta'); // Positive for credits, negative for debits
            $table->unsignedInteger('balance_before');
            $table->unsignedInteger('balance_after');
            $table->enum('status', ['pending', 'successful', 'failed', 'expired'])->default('successful')->index();
            $table->string('description', 255);
            $table->text('reason')->nullable(); // Mandatory for admin adjustments
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ludo_ledger_transactions');
    }
};
