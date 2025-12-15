<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\AsignacionHorario;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        'asignacion_horario_id',
        'docente_id',
        'fecha',
        'hora_marcada',
        'estado',
        'latitud',
        'longitud',
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date',
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
    ];

    /**
     * Relación con AsignacionHorario
     */
    public function asignacionHorario(): BelongsTo
    {
        return $this->belongsTo(AsignacionHorario::class, 'asignacion_horario_id');
    }

    /**
     * Relación con Docente
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    /**
     * Scope para filtrar por docente
     */
    public function scopeDelDocente($query, $docenteId)
    {
        return $query->where('docente_id', $docenteId);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }
}
