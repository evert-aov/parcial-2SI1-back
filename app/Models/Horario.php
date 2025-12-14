<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Horario extends Model
{
    use HasFactory;

    protected $table = 'horarios';

    protected $fillable = [
        'hora_inicio',
        'hora_fin',
        'nombre',
    ];

    protected $casts = [
        'hora_inicio' => 'datetime:H:i',
        'hora_fin' => 'datetime:H:i',
    ];

    /**
     * Relationship: Horario belongs to many Asignaciones through pivot
     */
    public function asignaciones(): BelongsToMany
    {
        return $this->belongsToMany(Asignacion::class, 'asignacion_horarios')
            ->withPivot('dia_id')
            ->withTimestamps();
    }

    /**
     * Accessor: Get formatted time range (HH:MM-HH:MM)
     */
    protected function rango(): Attribute
    {
        return Attribute::make(
            get: fn() => date('H:i', strtotime($this->hora_inicio)) . '-' . date('H:i', strtotime($this->hora_fin))
        );
    }

    /**
     * Scope: Order by hora_inicio
     */
    public function scopeOrdenados($query)
    {
        return $query->orderBy('hora_inicio');
    }
}
