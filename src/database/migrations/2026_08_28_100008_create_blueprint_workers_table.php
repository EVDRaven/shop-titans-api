<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_workers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('blueprint_id')
                ->constrained('blueprints')
                ->cascadeOnDelete();

            $table->foreignId('worker_id')
                ->constrained('workers')
                ->restrictOnDelete();

            $table->unsignedInteger('level_required');

            $table->unique(['blueprint_id', 'worker_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_workers');
    }
};
