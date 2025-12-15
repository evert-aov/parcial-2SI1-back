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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_horario_id')->constrained('asignacion_horarios')->onDelete('cascade');
            $table->foreignId('docente_id')->constrained('docentes')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora_marcada');
            $table->enum('estado', ['presente', 'retrasado', 'falta'])->default('falta');
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices para mejorar rendimiento
            $table->index(['docente_id', 'fecha']);
            $table->index('estado');
            $table->index('fecha');

            // Constraint único: un docente solo puede marcar una vez por clase
            $table->unique(['asignacion_horario_id', 'docente_id', 'fecha'], 'unique_asistencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
