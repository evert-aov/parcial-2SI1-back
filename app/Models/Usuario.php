<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'usuarios';

    protected $fillable = [
        'rol_id',
        'contrasena',
        'nombre',
        'apellido',
        'telefono',
        'sexo',
        'correo',
        'ci',
        'direccion',
        'activo',
    ];

    protected $hidden = [
        'contrasena',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relationship: Usuario belongs to Role
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    /**
     * Relationship: Usuario has one Administrador
     */
    public function administrador()
    {
        return $this->hasOne(Administrador::class, 'usuario_id');
    }

    /**
     * Relationship: Usuario has one Docente
     */
    public function docente()
    {
        return $this->hasOne(Docente::class, 'usuario_id');
    }

    /**
     * Get user permissions through role
     */
    public function getPermissionsAttribute()
    {
        return $this->role->permisos->pluck('nombre')->toArray();
    }

    /**
     * Hash password when setting
     */
    public function setContrasenaAttribute($value)
    {
        $this->attributes['contrasena'] = Hash::make($value);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return Hash::check($password, $this->contrasena);
    }

    /**
     * Scope: Only active users
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Find user by email
     */
    public static function findByEmail(string $email)
    {
        return static::where('correo', $email)->active()->first();
    }

    /**
     * Check if email exists
     */
    public static function existsByEmail(string $email, ?int $excludeId = null): bool
    {
        $query = static::where('correo', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if CI exists
     */
    public static function existsByCI(string $ci, ?int $excludeId = null): bool
    {
        $query = static::where('ci', $ci);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
