<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    use HasFactory;

    protected $table = 'aulas';

    protected $fillable = [
        'nombre',
        'codigo',
        'capacidad',
        'tipo',
        'edificio',
        'activo',
    ];

    protected $casts = [
        'capacidad' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Scope: Only active classrooms
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope: Order by codigo
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('codigo');
    }
}
