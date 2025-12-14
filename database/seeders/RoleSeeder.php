<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'nombre' => 'Estudiante'],
            ['id' => 2, 'nombre' => 'Administrador'],
            ['id' => 3, 'nombre' => 'Docente'],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
