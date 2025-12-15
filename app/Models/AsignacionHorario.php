<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsignacionHorario extends Model
{
    protected $table = 'asignacion_horarios';

    protected $fillable = [
        'asignacion_id',
        'dia_id',
        'horario_id',
        'aula_id',
    ];

    /**
     * Relación con Asignacion
     */
    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class, 'asignacion_id');
    }

    /**
     * Relación con Dia
     */
    public function dia(): BelongsTo
    {
        return $this->belongsTo(Dia::class, 'dia_id');
    }

    /**
     * Relación con Horario
     */
    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class, 'horario_id');
    }

    /**
     * Relación con Aula
     */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class, 'aula_id');
    }

    /**
     * Relación con Asistencias
     */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'asignacion_horario_id');
    }

    /**
     * Obtener información completa de la asignación de horario
     */
    public function scopeConTodo($query)
    {
        return $query->with([
            'asignacion.docente.usuario',
            'asignacion.materia',
            'asignacion.grupo',
            'asignacion.gestion',
            'dia',
            'horario',
            'aula'
        ]);
    }

    /**
     * Scope para filtrar por docente
     */
    public function scopePorDocente($query, $docenteId)
    {
        return $query->whereHas('asignacion', function ($q) use ($docenteId) {
            $q->where('docente_id', $docenteId);
        });
    }

    /**
     * Scope para filtrar por día
     */
    public function scopePorDia($query, $diaId)
    {
        return $query->where('dia_id', $diaId);
    }
}
