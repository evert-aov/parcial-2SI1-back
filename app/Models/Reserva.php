<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory;

    protected $table = 'reservas';

    protected $fillable = [
        'titulo',
        'aula_id',
        'tipo',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'asistentes_estimados',
        'descripcion',
        'estado',
        'solicitante_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i',
        'asistentes_estimados' => 'integer',
    ];

    /**
     * Relación con Aula
     */
    public function aula()
    {
        return $this->belongsTo(Aula::class, 'aula_id');
    }

    /**
     * Relación con Usuario (solicitante)
     */
    public function solicitante()
    {
        return $this->belongsTo(Usuario::class, 'solicitante_id');
    }

    /**
     * Scope: Reservas pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope: Reservas aprobadas
     */
    public function scopeAprobadas($query)
    {
        return $query->where('estado', 'aprobada');
    }

    /**
     * Scope: Reservas futuras
     */
    public function scopeFuturas($query)
    {
        return $query->where('fecha', '>=', now()->toDateString());
    }

    /**
     * Scope: Ordenar por fecha y hora
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('fecha')->orderBy('hora_inicio');
    }
}
