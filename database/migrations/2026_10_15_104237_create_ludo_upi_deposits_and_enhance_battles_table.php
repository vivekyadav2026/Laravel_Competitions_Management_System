<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create Direct UPI QR Deposits Table (No 3rd-party gateway)
        if (!Schema::hasTable('ludo_upi_deposits')) {
            Schema::create('ludo_upi_deposits', function (Blueprint $table) {
                $table->id();
                $table->string('deposit_uid', 32)->unique();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->unsignedInteger('amount'); // in INR (Coins)
                $table->string('utr_number', 64)->index();
                $table->string('upi_id', 120)->nullable();
                $table->string('screenshot_path', 255)->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
                $table->text('admin_notes')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Enhance Ludo Battles for Room Code, Cancellation Reason, and Requested State
        Schema::table('ludo_battles', function (Blueprint $table) {
            if (!Schema::hasColumn('ludo_battles', 'cancellation_reason')) {
                $table->string('cancellation_reason', 150)->nullable()->after('final_result');
            }
            if (!Schema::hasColumn('ludo_battles', 'cancelled_by_id')) {
                $table->foreignId('cancelled_by_id')->nullable()->constrained('users')->onDelete('set null')->after('cancellation_reason');
            }
            if (!Schema::hasColumn('ludo_battles', 'room_code_expires_at')) {
                $table->timestamp('room_code_expires_at')->nullable()->after('room_code');
            }
        });

        // Expand status column if it is an enum to support 'requested' state
        try {
            DB::statement("ALTER TABLE ludo_battles MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'open'");
        } catch (\Exception $e) {
            // Ignore if already string or modified
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ludo_upi_deposits');

        Schema::table('ludo_battles', function (Blueprint $table) {
            if (Schema::hasColumn('ludo_battles', 'cancelled_by_id')) {
                $table->dropForeign(['cancelled_by_id']);
                $table->dropColumn('cancelled_by_id');
            }
            if (Schema::hasColumn('ludo_battles', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
            if (Schema::hasColumn('ludo_battles', 'room_code_expires_at')) {
                $table->dropColumn('room_code_expires_at');
            }
        });
    }
};
