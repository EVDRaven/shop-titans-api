<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blueprint_elemental_affinities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('blueprint_id')
                ->constrained('blueprints')
                ->cascadeOnDelete();

            $table->foreignId('element_id')
                ->constrained('elements')
                ->restrictOnDelete();

            // Posicion original del elemento en la celda AZ (1 = prioritario).
            $table->unsignedTinyInteger('position');

            // Valor literal de la celda AZ tal como aparece en el Excel (auditoria).
            $table->string('raw_value')->nullable();

            $table->unique(['blueprint_id', 'element_id']);
            $table->unique(['blueprint_id', 'position']);

            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE blueprint_elemental_affinities ADD CONSTRAINT '
                .'chk_blueprint_elemental_affinities_position CHECK (position IN (1, 2))'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blueprint_elemental_affinities');
    }
};
