<?php

namespace Database\Seeders;

use App\Models\Aula;
use Illuminate\Database\Seeder;

class AulaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $aulas = [
            // Aulas normales
            ['nombre' => 'Aula 1', 'codigo' => 'A01', 'capacidad' => 30, 'tipo' => 'Aula', 'edificio' => 'Edificio A'],
            ['nombre' => 'Aula 2', 'codigo' => 'A02', 'capacidad' => 30, 'tipo' => 'Aula', 'edificio' => 'Edificio A'],
            ['nombre' => 'Aula 3', 'codigo' => 'A03', 'capacidad' => 35, 'tipo' => 'Aula', 'edificio' => 'Edificio A'],
            ['nombre' => 'Aula 4', 'codigo' => 'A04', 'capacidad' => 35, 'tipo' => 'Aula', 'edificio' => 'Edificio A'],
            ['nombre' => 'Aula 5', 'codigo' => 'A05', 'capacidad' => 40, 'tipo' => 'Aula', 'edificio' => 'Edificio A'],

            ['nombre' => 'Aula 10', 'codigo' => 'B10', 'capacidad' => 30, 'tipo' => 'Aula', 'edificio' => 'Edificio B'],
            ['nombre' => 'Aula 11', 'codigo' => 'B11', 'capacidad' => 30, 'tipo' => 'Aula', 'edificio' => 'Edificio B'],
            ['nombre' => 'Aula 12', 'codigo' => 'B12', 'capacidad' => 35, 'tipo' => 'Aula', 'edificio' => 'Edificio B'],
            ['nombre' => 'Aula 13', 'codigo' => 'B13', 'capacidad' => 35, 'tipo' => 'Aula', 'edificio' => 'Edificio B'],
            ['nombre' => 'Aula 14', 'codigo' => 'B14', 'capacidad' => 40, 'tipo' => 'Aula', 'edificio' => 'Edificio B'],
            ['nombre' => 'Aula 15', 'codigo' => 'B15', 'capacidad' => 40, 'tipo' => 'Aula', 'edificio' => 'Edificio B'],

            // Laboratorios
            ['nombre' => 'Laboratorio de Computación A', 'codigo' => 'LAB-A', 'capacidad' => 25, 'tipo' => 'Laboratorio', 'edificio' => 'Edificio C'],
            ['nombre' => 'Laboratorio de Computación B', 'codigo' => 'LAB-B', 'capacidad' => 25, 'tipo' => 'Laboratorio', 'edificio' => 'Edificio C'],
            ['nombre' => 'Laboratorio de Física', 'codigo' => 'LAB-FIS', 'capacidad' => 20, 'tipo' => 'Laboratorio', 'edificio' => 'Edificio C'],
            ['nombre' => 'Laboratorio de Química', 'codigo' => 'LAB-QUI', 'capacidad' => 20, 'tipo' => 'Laboratorio', 'edificio' => 'Edificio C'],

            // Auditorios
            ['nombre' => 'Auditorio Principal', 'codigo' => 'AUD-1', 'capacidad' => 100, 'tipo' => 'Auditorio', 'edificio' => 'Edificio Principal'],
            ['nombre' => 'Auditorio Secundario', 'codigo' => 'AUD-2', 'capacidad' => 60, 'tipo' => 'Auditorio', 'edificio' => 'Edificio Principal'],
        ];

        foreach ($aulas as $aula) {
            Aula::create($aula);
        }
    }
}
