<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('level')->unique();
            $table->unsignedBigInteger('xp_needed')->nullable();
            $table->decimal('crafting_speed_bonus', 8, 4)->nullable();
            $table->decimal('crafting_speed_bonus_t15plus', 8, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_levels');
    }
};
