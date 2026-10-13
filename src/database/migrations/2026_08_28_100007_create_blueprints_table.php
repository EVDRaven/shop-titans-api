<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprints', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('item_categories')
                ->nullOnDelete();

            $table->string('unlock_prerequisite')->nullable();

            $table->unsignedInteger('research_scrolls')->nullable();
            $table->unsignedInteger('antique_tokens')->nullable();

            $table->unsignedTinyInteger('tier');

            $table->unsignedBigInteger('gold_value');
            $table->unsignedInteger('craft_time_seconds');
            $table->string('craft_time_formatted')->nullable();

            $table->decimal('value_per_second', 14, 4)->nullable();

            $table->unsignedInteger('merchant_xp');
            $table->decimal('merchant_xp_per_second', 14, 4)->nullable();

            $table->unsignedInteger('worker_xp');
            $table->unsignedInteger('fusion_xp');
            $table->unsignedInteger('favor');
            $table->unsignedInteger('airship_power');

            $table->integer('atk')->nullable();
            $table->integer('def')->nullable();
            $table->integer('hp')->nullable();
            $table->integer('eva')->nullable();
            $table->integer('crit')->nullable();
            $table->integer('mana_shield')->nullable();

            $table->foreignId('elemental_affinity_id')
                ->nullable()
                ->constrained('elements')
                ->nullOnDelete();

            $table->foreignId('spirit_affinity_id')
                ->nullable()
                ->constrained('elements')
                ->nullOnDelete();

            $table->foreignId('built_in_element_id')
                ->nullable()
                ->constrained('elements')
                ->nullOnDelete();

            $table->foreignId('built_in_spirit_id')
                ->nullable()
                ->constrained('elements')
                ->nullOnDelete();

            $table->unsignedInteger('discount_energy')->default(0);
            $table->unsignedInteger('surcharge_energy')->default(0);
            $table->unsignedInteger('suggest_energy')->default(0);
            $table->unsignedInteger('speed_up_energy')->default(0);

            $table->timestamp('available_as_antique_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprints');
    }
};
