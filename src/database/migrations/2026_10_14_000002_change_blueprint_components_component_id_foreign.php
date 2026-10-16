<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blueprint_components', function (Blueprint $table) {
            $table->dropForeign(['component_id']);

            $table->foreign('component_id')
                ->references('id')
                ->on('crafting_materials')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $nonReversibleCount = DB::table('blueprint_components')
            ->join('crafting_materials', 'blueprint_components.component_id', '=', 'crafting_materials.id')
            ->whereNull('crafting_materials.blueprint_id')
            ->count();

        if ($nonReversibleCount > 0) {
            throw new RuntimeException(sprintf(
                'No se puede revertir la migracion 2026_10_14_000002: %d registro(s) en '
                .'blueprint_components referencian materiales de tipo quest_component, sigil '
                .'o enchantment que no pueden representarse en el esquema anterior '
                .'(component_id debia apuntar a blueprints.id).',
                $nonReversibleCount
            ));
        }

        Schema::table('blueprint_components', function (Blueprint $table) {
            $table->dropForeign(['component_id']);
        });

        DB::statement(
            'UPDATE blueprint_components SET component_id = ('
            .'SELECT cm.blueprint_id FROM crafting_materials cm '
            .'WHERE cm.id = blueprint_components.component_id)'
        );

        Schema::table('blueprint_components', function (Blueprint $table) {
            $table->foreign('component_id')
                ->references('id')
                ->on('blueprints')
                ->restrictOnDelete();
        });
    }
};
