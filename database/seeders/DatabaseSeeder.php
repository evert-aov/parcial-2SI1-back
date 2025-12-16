<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call([
            RoleSeeder::class,
            PermisoSeeder::class,
        ]);

        // Create test admin user (idempotent - won't fail if already exists)
        \App\Models\Usuario::updateOrCreate(
            ['correo' => 'admin@test.com'], // Buscar por email
            [
                'rol_id' => 2, // Administrador
                'contrasena' => 'admin123',
                'nombre' => 'Admin',
                'apellido' => 'Sistema',
                'ci' => '12345678',
                'telefono' => '555-0100',
                'sexo' => 'M',
                'direccion' => 'Dirección de prueba',
                'activo' => true,
            ]
        );

        // Create test student user (idempotent)
        \App\Models\Usuario::updateOrCreate(
            ['correo' => 'student@test.com'], // Buscar por email
            [
                'rol_id' => 1, // Estudiante
                'contrasena' => 'student123',
                'nombre' => 'Estudiante',
                'apellido' => 'Prueba',
                'ci' => '87654321',
                'activo' => true,
            ]
        );

        // Seed academic data
        $this->call([
            AcademicSeeder::class,
        ]);
    }
}
