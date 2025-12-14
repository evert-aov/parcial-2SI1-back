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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->foreignId('aula_id')->constrained('aulas')->onDelete('cascade');
            $table->string('tipo'); // Ej: "Reunión", "Evento", "Examen", "Conferencia"
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->integer('asistentes_estimados')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('estado')->default('pendiente'); // pendiente, aprobada, rechazada, cancelada
            $table->foreignId('solicitante_id')->nullable()->constrained('usuarios')->onDelete('set null');
            $table->timestamps();

            // Índices para mejorar consultas
            $table->index(['aula_id', 'fecha']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
