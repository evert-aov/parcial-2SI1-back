<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Lunes, Martes, Miércoles, etc.
            $table->string('abreviatura', 3); // Lun, Mar, Mie, etc.
            $table->tinyInteger('orden'); // 1-7 para ordenar
            $table->timestamps();

            $table->unique('nombre');
            $table->unique('abreviatura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dias');
    }
};
