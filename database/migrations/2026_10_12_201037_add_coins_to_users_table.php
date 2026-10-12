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
            if (!Schema::hasColumn('users', 'coins')) {
                $table->unsignedInteger('coins')->default(100)->after('role'); // Starting welcome points
            }
            if (!Schema::hasColumn('users', 'referral_balance')) {
                $table->decimal('referral_balance', 10, 2)->default(0.00)->after('coins');
            }
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 20)->nullable()->unique()->after('referral_balance');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['coins', 'referral_balance', 'referral_code']);
        });
    }
};
