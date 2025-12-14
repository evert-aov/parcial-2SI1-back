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
        Schema::create('aulas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: "Aula 12", "Laboratorio A"
            $table->string('codigo')->unique(); // Ej: "A12", "LAB-A"
            $table->integer('capacidad')->nullable(); // Capacidad de estudiantes
            $table->string('tipo')->nullable(); // Ej: "Aula", "Laboratorio", "Auditorio"
            $table->string('edificio')->nullable(); // Ej: "Edificio A", "Pabellón 1"
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aulas');
    }
};
