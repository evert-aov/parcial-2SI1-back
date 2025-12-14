<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    use HasFactory;

    protected $table = 'docentes';

    protected $fillable = [
        'usuario_id',
        'especialidad',
        'fecha_contrato',
    ];

    protected $casts = [
        'fecha_contrato' => 'date',
    ];

    /**
     * Relationship: Docente belongs to Usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relationship: Docente has many Asignaciones
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'docente_id');
    }

    /**
     * Get docente with usuario information
     */
    public static function conUsuario()
    {
        return static::with('usuario.role');
    }
}
