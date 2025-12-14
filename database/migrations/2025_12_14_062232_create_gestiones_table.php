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
        Schema::create('gestiones', function (Blueprint $table) {
            $table->id();
            $table->integer('anio');
            $table->string('periodo'); // Ej: "1/2024", "2/2024"
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['anio', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gestiones');
    }
};
