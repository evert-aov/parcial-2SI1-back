<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gestion extends Model
{
    use HasFactory;

    protected $table = 'gestiones';

    protected $fillable = [
        'anio',
        'periodo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'anio' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    /**
     * Relationship: Gestion has many Asignaciones
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'gestion_id');
    }

    /**
     * Get formatted gestion name
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->periodo}/{$this->anio}";
    }

    /**
     * Scope: Get current gestion
     */
    public function scopeActual($query)
    {
        $now = now();
        return $query->where('fecha_inicio', '<=', $now)
            ->where('fecha_fin', '>=', $now);
    }
}
