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
        Schema::create('ludo_battles', function (Blueprint $table) {
            $table->id();
            $table->string('battle_uid', 32)->unique();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('joiner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedInteger('entry_fee')->default(50); // Virtual tournament points
            $table->unsignedInteger('prize')->default(95); // Calculated virtual prize
            $table->enum('status', ['open', 'requested', 'running', 'completed', 'cancelled'])->default('open')->index();
            $table->string('room_code', 30)->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ludo_battles');
    }
};
