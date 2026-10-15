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
        if (!Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 60)->nullable()->after('player_uid');
            });
        }

        // Backfill existing users with deterministic unique usernames
        $users = DB::table('users')->orderBy('id')->get();
        $slugCounts = [];

        foreach ($users as $u) {
            $nameStr = (string) ($u->name ?? 'player');
            $clean = strtolower(preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-'], '_', strtolower(trim($nameStr)))));
            if (empty($clean)) {
                $clean = 'player';
            }
            $clean = substr($clean, 0, 35);
            $slugCounts[$clean] = ($slugCounts[$clean] ?? 0) + 1;
        }

        $assigned = [];
        foreach ($users as $u) {
            $nameStr = (string) ($u->name ?? 'player');
            $clean = strtolower(preg_replace('/[^a-z0-9_]/', '', str_replace([' ', '-'], '_', strtolower(trim($nameStr)))));
            if (empty($clean)) {
                $clean = 'player';
            }
            $clean = substr($clean, 0, 35);

            if (($slugCounts[$clean] ?? 0) > 1) {
                $candidate = $clean . '_' . $u->id;
            } else {
                $candidate = $clean;
            }

            if (in_array($candidate, $assigned, true)) {
                $candidate = $clean . '_' . $u->id;
            }

            $suffix = 1;
            while (in_array($candidate, $assigned, true)) {
                $candidate = $clean . '_' . $u->id . '_' . $suffix;
                $suffix++;
            }

            $assigned[] = $candidate;
            DB::table('users')->where('id', $u->id)->update([
                'username' => $candidate,
            ]);
        }

        // Apply unique constraint index
        Schema::table('users', function (Blueprint $table) {
            $table->unique('username', 'users_username_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_username_unique');
                $table->dropColumn('username');
            });
        }
    }
};
