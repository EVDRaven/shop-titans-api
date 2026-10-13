<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('worker_key')->unique();
            $table->string('default_person_name');
            $table->unsignedInteger('unlock_level_required')->nullable();
            $table->string('building_prerequisite')->nullable();
            $table->unsignedBigInteger('gold_cost')->nullable();
            $table->unsignedInteger('gem_cost')->nullable();
            $table->text('blueprint_unlocks_raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
