<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Las cuatro columnas fueron creadas apuntando a `elements` y el importador
        // nunca las pobló. Se aborta si contienen datos para evitar perdida silenciosa.
        $this->assertAffinityColumnsAreEmpty();

        // AZ: se sustituye la columna unica por la tabla 1:N blueprint_elemental_affinities.
        Schema::table('blueprints', function (Blueprint $table) {
            $table->dropForeign(['elemental_affinity_id']);
        });

        Schema::table('blueprints', function (Blueprint $table) {
            $table->dropColumn('elemental_affinity_id');
        });

        // BA: spirit_affinity_id pasa a apuntar al nuevo catalogo spirit_affinities.
        Schema::table('blueprints', function (Blueprint $table) {
            $table->dropForeign(['spirit_affinity_id']);
            $table->foreign('spirit_affinity_id')
                ->references('id')
                ->on('spirit_affinities')
                ->nullOnDelete();
        });

        // BB: built_in_element_id pasa a apuntar al catalogo enchantments.
        Schema::table('blueprints', function (Blueprint $table) {
            $table->dropForeign(['built_in_element_id']);
            $table->foreign('built_in_element_id')
                ->references('id')
                ->on('enchantments')
                ->nullOnDelete();
        });

        // BC: built_in_spirit_id pasa a apuntar al nuevo catalogo spirits.
        Schema::table('blueprints', function (Blueprint $table) {
            $table->dropForeign(['built_in_spirit_id']);
            $table->foreign('built_in_spirit_id')
                ->references('id')
                ->on('spirits')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Revertir a `elements` solo es seguro si no hay datos: tras un import, los
        // IDs de BA/BB/BC pertenecen a spirit_affinities/enchantments/spirits y no
        // pueden reinterpretarse como IDs de `elements` (mismo numero, catalogo
        // distinto) sin corromper los datos en silencio. Si hay datos, se aborta
        // antes de tocar el esquema.
        $this->assertAffinityColumnsAreEmpty();
        $this->assertElementalAffinitiesTableIsEmpty();

        // Restaura las tres FKs hacia `elements` y recrea la columna AZ.
        Schema::table('blueprints', function (Blueprint $table) {
            $table->dropForeign(['spirit_affinity_id']);
            $table->foreign('spirit_affinity_id')
                ->references('id')
                ->on('elements')
                ->nullOnDelete();
        });

        Schema::table('blueprints', function (Blueprint $table) {
            $table->dropForeign(['built_in_element_id']);
            $table->foreign('built_in_element_id')
                ->references('id')
                ->on('elements')
                ->nullOnDelete();
        });

        Schema::table('blueprints', function (Blueprint $table) {
            $table->dropForeign(['built_in_spirit_id']);
            $table->foreign('built_in_spirit_id')
                ->references('id')
                ->on('elements')
                ->nullOnDelete();
        });

        Schema::table('blueprints', function (Blueprint $table) {
            $table->foreignId('elemental_affinity_id')
                ->nullable()
                ->constrained('elements')
                ->nullOnDelete();
        });
    }

    private function assertAffinityColumnsAreEmpty(): void
    {
        $columns = [
            'elemental_affinity_id',
            'spirit_affinity_id',
            'built_in_element_id',
            'built_in_spirit_id',
        ];

        foreach ($columns as $column) {
            if (! Schema::hasColumn('blueprints', $column)) {
                continue;
            }

            $count = DB::table('blueprints')->whereNotNull($column)->count();

            if ($count > 0) {
                throw new RuntimeException(sprintf(
                    'No se puede aplicar la migracion 2026_10_16_000005: la columna '
                    .'blueprints.%s contiene %d valor(es) no nulo(s). Se requiere revision '
                    .'manual para migrarlos a los nuevos catalogos antes de cambiar el esquema.',
                    $column,
                    $count
                ));
            }
        }
    }

    private function assertElementalAffinitiesTableIsEmpty(): void
    {
        if (! Schema::hasTable('blueprint_elemental_affinities')) {
            return;
        }

        $count = DB::table('blueprint_elemental_affinities')->count();

        if ($count > 0) {
            throw new RuntimeException(sprintf(
                'No se puede revertir la migracion 2026_10_16_000005: la tabla '
                .'blueprint_elemental_affinities contiene %d fila(s) y la relacion 1:N no '
                .'puede colapsarse de forma fiel en la columna unica '
                .'blueprints.elemental_affinity_id. Se requiere revision manual antes de revertir.',
                $count
            ));
        }
    }
};
