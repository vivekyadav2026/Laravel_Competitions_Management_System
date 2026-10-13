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
        DB::statement("ALTER TABLE `ludo_support_tickets` MODIFY COLUMN `status` ENUM('open', 'in_progress', 'answered', 'resolved', 'closed') NOT NULL DEFAULT 'open'");
        DB::statement("ALTER TABLE `ludo_support_tickets` MODIFY COLUMN `priority` ENUM('normal', 'high', 'urgent') NOT NULL DEFAULT 'normal'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE `ludo_support_tickets` SET `status` = 'closed' WHERE `status` = 'resolved'");
        DB::statement("UPDATE `ludo_support_tickets` SET `priority` = 'urgent' WHERE `priority` = 'high'");
        DB::statement("ALTER TABLE `ludo_support_tickets` MODIFY COLUMN `status` ENUM('open', 'in_progress', 'answered', 'closed') NOT NULL DEFAULT 'open'");
        DB::statement("ALTER TABLE `ludo_support_tickets` MODIFY COLUMN `priority` ENUM('normal', 'urgent') NOT NULL DEFAULT 'normal'");
    }
};
