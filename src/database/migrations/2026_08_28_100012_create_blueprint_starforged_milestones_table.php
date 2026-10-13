<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_starforged_milestones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('blueprint_id')
                ->constrained('blueprints')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('milestone_number');
            $table->string('name')->nullable();
            $table->unsignedInteger('crafts_required');

            $table->unique(['blueprint_id', 'milestone_number']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_starforged_milestones');
    }
};
