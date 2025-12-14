<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Usuario;
use App\Models\Asignacion;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear carreras
        $informatica = Carrera::create(['nombre' => 'Ingeniería de Sistemas']);
        $industrial = Carrera::create(['nombre' => 'Ingeniería Industrial']);

        // Crear grupos
        $grupoA = Grupo::create(['nombre' => 'Grupo A']);
        $grupoB = Grupo::create(['nombre' => 'Grupo B']);
        $grupoC = Grupo::create(['nombre' => 'Grupo C']);

        // Crear gestión actual
        $gestion = Gestion::create([
            'anio' => 2024,
            'periodo' => '2',
            'fecha_inicio' => '2024-08-01',
            'fecha_fin' => '2024-12-31',
        ]);

        // Crear materias para Ingeniería de Sistemas
        $materias = [
            ['carrera_id' => $informatica->id, 'sigla' => 'INF-111', 'nombre' => 'Introducción a la Programación'],
            ['carrera_id' => $informatica->id, 'sigla' => 'INF-121', 'nombre' => 'Estructura de Datos'],
            ['carrera_id' => $informatica->id, 'sigla' => 'INF-211', 'nombre' => 'Base de Datos I'],
            ['carrera_id' => $informatica->id, 'sigla' => 'INF-221', 'nombre' => 'Programación Web'],
            ['carrera_id' => $industrial->id, 'sigla' => 'IND-111', 'nombre' => 'Introducción a la Ingeniería'],
        ];

        foreach ($materias as $materiaData) {
            Materia::create($materiaData);
        }

        // Crear docentes
        $docente1Usuario = Usuario::create([
            'rol_id' => 3, // Docente
            'contrasena' => 'docente123',
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'correo' => 'juan.perez@ficct.edu.bo',
            'ci' => '11111111',
            'telefono' => '555-0101',
            'sexo' => 'M',
            'activo' => true,
        ]);

        $docente1 = Docente::create([
            'usuario_id' => $docente1Usuario->id,
            'especialidad' => 'Programación',
            'fecha_contrato' => '2020-01-15',
        ]);

        $docente2Usuario = Usuario::create([
            'rol_id' => 3, // Docente
            'contrasena' => 'docente123',
            'nombre' => 'María',
            'apellido' => 'González',
            'correo' => 'maria.gonzalez@ficct.edu.bo',
            'ci' => '22222222',
            'telefono' => '555-0102',
            'sexo' => 'F',
            'activo' => true,
        ]);

        $docente2 = Docente::create([
            'usuario_id' => $docente2Usuario->id,
            'especialidad' => 'Base de Datos',
            'fecha_contrato' => '2019-03-20',
        ]);

        // Crear asignaciones
        $materiasCreadas = Materia::where('carrera_id', $informatica->id)->get();

        // Asignar docente 1 a INF-111 y INF-121
        Asignacion::create([
            'docente_id' => $docente1->id,
            'materia_id' => $materiasCreadas->where('sigla', 'INF-111')->first()->id,
            'grupo_id' => $grupoA->id,
            'gestion_id' => $gestion->id,
        ]);

        Asignacion::create([
            'docente_id' => $docente1->id,
            'materia_id' => $materiasCreadas->where('sigla', 'INF-121')->first()->id,
            'grupo_id' => $grupoB->id,
            'gestion_id' => $gestion->id,
        ]);

        // Asignar docente 2 a INF-211 y INF-221
        Asignacion::create([
            'docente_id' => $docente2->id,
            'materia_id' => $materiasCreadas->where('sigla', 'INF-211')->first()->id,
            'grupo_id' => $grupoA->id,
            'gestion_id' => $gestion->id,
        ]);

        Asignacion::create([
            'docente_id' => $docente2->id,
            'materia_id' => $materiasCreadas->where('sigla', 'INF-221')->first()->id,
            'grupo_id' => $grupoC->id,
            'gestion_id' => $gestion->id,
        ]);
    }
}
