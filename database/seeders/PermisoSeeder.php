<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermisoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos
        $permisos = [
            'ver_usuarios',
            'crear_usuarios',
            'editar_usuarios',
            'eliminar_usuarios',
            'ver_roles',
            'gestionar_roles',
            'ver_carreras',
            'gestionar_carreras',
            'ver_materias',
            'gestionar_materias',
            'ver_grupos',
            'gestionar_grupos',
            'ver_asignaciones',
            'gestionar_asignaciones',
        ];

        foreach ($permisos as $permiso) {
            Permiso::create(['nombre' => $permiso]);
        }

        // Asignar todos los permisos al rol Administrador
        $adminRole = Role::find(2);
        $allPermisos = Permiso::all()->pluck('id')->toArray();
        $adminRole->syncPermissions($allPermisos);

        // Asignar permisos limitados al rol Docente
        $docenteRole = Role::find(3);
        $docentePermisos = Permiso::whereIn('nombre', [
            'ver_materias',
            'ver_grupos',
            'ver_asignaciones',
        ])->pluck('id')->toArray();
        $docenteRole->syncPermissions($docentePermisos);
    }
}
