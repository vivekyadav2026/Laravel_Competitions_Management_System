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
        if (!Schema::hasTable('ludo_withdrawal_requests')) {
            Schema::create('ludo_withdrawal_requests', function (Blueprint $table) {
                $table->id();
                $table->string('withdrawal_uid', 32)->unique();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->unsignedInteger('amount'); // in INR (Coins)
                $table->string('payout_method', 32)->default('upi'); // upi, bank_transfer
                $table->string('upi_id', 120)->nullable();
                $table->string('bank_account_number', 64)->nullable();
                $table->string('bank_ifsc', 32)->nullable();
                $table->string('bank_name', 100)->nullable();
                $table->string('account_holder_name', 120)->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
                $table->string('admin_utr_reference', 64)->nullable(); // Payout UTR / Ref entered by admin upon settlement
                $table->text('admin_notes')->nullable();
                $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ludo_withdrawal_requests');
    }
};
