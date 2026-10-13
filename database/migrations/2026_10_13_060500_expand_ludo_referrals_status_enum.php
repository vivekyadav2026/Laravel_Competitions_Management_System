<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `ludo_referrals` MODIFY COLUMN `status` ENUM('pending', 'eligible', 'rewarded', 'revoked') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE `ludo_referrals` SET `status` = 'pending' WHERE `status` = 'revoked'");
        DB::statement("ALTER TABLE `ludo_referrals` MODIFY COLUMN `status` ENUM('pending', 'eligible', 'rewarded') NOT NULL DEFAULT 'pending'");
    }
};
