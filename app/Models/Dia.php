<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dia extends Model
{
    use HasFactory;

    protected $table = 'dias';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    /**
     * Relationship: Dia belongs to many Asignaciones through pivot
     */
    public function asignaciones(): BelongsToMany
    {
        return $this->belongsToMany(Asignacion::class, 'asignacion_horarios')
            ->withPivot('horario_id')
            ->withTimestamps();
    }

    /**
     * Scope: Order by orden field
     */
    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }
}
