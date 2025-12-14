<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asignacion extends Model
{
    use HasFactory;

    protected $table = 'asignaciones';

    protected $fillable = [
        'docente_id',
        'materia_id',
        'grupo_id',
        'gestion_id',
    ];

    /**
     * Relationship: Asignacion belongs to Docente
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id');
    }

    /**
     * Relationship: Asignacion belongs to Materia
     */
    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'materia_id');
    }

    /**
     * Relationship: Asignacion belongs to Grupo
     */
    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    /**
     * Relationship: Asignacion belongs to Gestion
     */
    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class, 'gestion_id');
    }

    /**
     * Relationship: Asignacion belongs to many Dias through pivot
     */
    public function dias()
    {
        return $this->belongsToMany(Dia::class, 'asignacion_horarios')
            ->withPivot('horario_id')
            ->withTimestamps();
    }

    /**
     * Relationship: Asignacion belongs to many Horarios through pivot
     */
    public function horarios()
    {
        return $this->belongsToMany(Horario::class, 'asignacion_horarios')
            ->withPivot('dia_id')
            ->withTimestamps();
    }

    /**
     * Get all asignaciones with full information
     */
    public static function conTodo()
    {
        return static::with([
            'docente.usuario',
            'materia.carrera',
            'grupo',
            'gestion',
            'dias',
            'horarios'
        ]);
    }

    /**
     * Scope: Filter by gestion
     */
    public function scopePorGestion($query, $gestionId)
    {
        return $query->where('gestion_id', $gestionId);
    }

    /**
     * Scope: Filter by docente
     */
    public function scopePorDocente($query, $docenteId)
    {
        return $query->where('docente_id', $docenteId);
    }
}
