<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('blueprint_id')
                ->constrained('blueprints')
                ->cascadeOnDelete();

            $table->foreignId('component_id')
                ->constrained('blueprints')
                ->restrictOnDelete();

            $table->foreignId('quality_id')
                ->nullable()
                ->constrained('qualities')
                ->nullOnDelete();

            $table->unsignedInteger('amount');

            $table->unique(['blueprint_id', 'component_id', 'quality_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_components');
    }
};
