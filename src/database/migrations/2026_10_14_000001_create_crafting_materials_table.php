<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crafting_materials', function (Blueprint $table) {
            $table->id();

            $table->string('source_key')->unique();
            $table->string('name');

            $table->enum('material_type', [
                'blueprint',
                'quest_component',
                'sigil',
                'enchantment',
            ]);

            $table->foreignId('blueprint_id')
                ->nullable()
                ->constrained('blueprints')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('tier')->nullable();
            $table->unsignedBigInteger('gold_value')->nullable();

            $table->string('quest_area')->nullable();
            $table->unsignedInteger('fuse_time_seconds')->nullable();
            $table->unsignedTinyInteger('worker_level_required')->nullable();
            $table->unsignedInteger('worker_xp')->nullable();

            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE crafting_materials ADD CONSTRAINT chk_material_type_blueprint_id CHECK ('
                ."(material_type = 'blueprint' AND blueprint_id IS NOT NULL) "
                ."OR (material_type IN ('enchantment', 'quest_component', 'sigil') AND blueprint_id IS NULL)"
                .')'
            );

            DB::statement(
                'ALTER TABLE crafting_materials ADD CONSTRAINT chk_source_key_format CHECK ('
                ."(material_type = 'blueprint' AND source_key LIKE 'blueprint:%') "
                ."OR (material_type = 'enchantment' AND source_key LIKE 'enchantment:%') "
                ."OR (material_type = 'quest_component' AND source_key LIKE 'quest_component:%') "
                ."OR (material_type = 'sigil' AND source_key LIKE 'sigil:%')"
                .')'
            );
        }

        DB::statement(
            'CREATE UNIQUE INDEX idx_crafting_materials_blueprint_id_unique '
            .'ON crafting_materials (blueprint_id) '
            .'WHERE blueprint_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_crafting_materials_blueprint_id_unique');

        Schema::dropIfExists('crafting_materials');
    }
};
