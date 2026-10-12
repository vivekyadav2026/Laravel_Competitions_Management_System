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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'player_uid')) {
                $table->string('player_uid', 20)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'hide_email')) {
                $table->boolean('hide_email')->default(true)->after('phone');
            }
            if (!Schema::hasColumn('users', 'hide_phone')) {
                $table->boolean('hide_phone')->default(true)->after('hide_email');
            }
            if (!Schema::hasColumn('users', 'kyc_status')) {
                $table->enum('kyc_status', ['unverified', 'pending', 'verified', 'rejected'])->default('unverified')->after('hide_phone');
            }
            if (!Schema::hasColumn('users', 'is_verified')) {
                $table->boolean('is_verified')->default(false)->after('kyc_status');
            }
            if (!Schema::hasColumn('users', 'referred_by_id')) {
                $table->foreignId('referred_by_id')->nullable()->constrained('users')->onDelete('set null')->after('is_verified');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
            $table->dropColumn([
                'player_uid',
                'avatar',
                'phone',
                'hide_email',
                'hide_phone',
                'kyc_status',
                'is_verified',
                'referred_by_id',
            ]);
        });
    }
};
