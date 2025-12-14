<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materia extends Model
{
    use HasFactory;

    protected $table = 'materias';

    protected $fillable = [
        'carrera_id',
        'sigla',
        'nombre',
    ];

    /**
     * Relationship: Materia belongs to Carrera
     */
    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    /**
     * Relationship: Materia has many Asignaciones
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'materia_id');
    }

    /**
     * Get materia with carrera information
     */
    public static function conCarrera()
    {
        return static::with('carrera');
    }

    /**
     * Get formatted materia name with sigla
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->sigla} - {$this->nombre}";
    }
}
