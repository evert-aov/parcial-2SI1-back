<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use HasFactory;

    protected $table = 'grupos';

    protected $fillable = [
        'nombre',
    ];

    /**
     * Relationship: Grupo has many Asignaciones
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'grupo_id');
    }
}
