<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enchantments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type');
            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE enchantments ADD CONSTRAINT chk_enchantments_type '
                ."CHECK (type IN ('Element', 'Spirit'))"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enchantments');
    }
};
