<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
    ];

    /**
     * Relationship: Role has many Usuarios
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }

    /**
     * Relationship: Role belongs to many Permisos
     */
    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(
            Permiso::class,
            'rol_permisos',
            'rol_id',
            'permiso_id'
        )->withTimestamps();
    }

    /**
     * Assign a permission to this role
     */
    public function assignPermission(int $permisoId): void
    {
        $this->permisos()->syncWithoutDetaching([$permisoId]);
    }

    /**
     * Remove a permission from this role
     */
    public function removePermission(int $permisoId): void
    {
        $this->permisos()->detach($permisoId);
    }

    /**
     * Sync permissions (replace all permissions with new ones)
     */
    public function syncPermissions(array $permisoIds): void
    {
        $this->permisos()->sync($permisoIds);
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permisoNombre): bool
    {
        return $this->permisos()->where('nombre', $permisoNombre)->exists();
    }
}
