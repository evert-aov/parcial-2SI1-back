<?php

namespace Database\Seeders;

use App\Models\Horario;
use Illuminate\Database\Seeder;

class HorarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $horarios = [
            ['hora_inicio' => '07:00', 'hora_fin' => '08:30', 'nombre' => 'Primera hora'],
            ['hora_inicio' => '08:30', 'hora_fin' => '10:00', 'nombre' => 'Segunda hora'],
            ['hora_inicio' => '10:00', 'hora_fin' => '11:30', 'nombre' => 'Tercera hora'],
            ['hora_inicio' => '11:30', 'hora_fin' => '13:00', 'nombre' => 'Cuarta hora'],
            ['hora_inicio' => '13:00', 'hora_fin' => '14:30', 'nombre' => 'Quinta hora'],
            ['hora_inicio' => '14:30', 'hora_fin' => '16:00', 'nombre' => 'Sexta hora'],
            ['hora_inicio' => '16:00', 'hora_fin' => '17:30', 'nombre' => 'Séptima hora'],
            ['hora_inicio' => '17:30', 'hora_fin' => '19:00', 'nombre' => 'Octava hora'],
            ['hora_inicio' => '19:00', 'hora_fin' => '20:30', 'nombre' => 'Novena hora'],
            ['hora_inicio' => '20:30', 'hora_fin' => '22:00', 'nombre' => 'Décima hora'],
            // Horarios adicionales basados en la imagen
            ['hora_inicio' => '07:00', 'hora_fin' => '09:15', 'nombre' => null],
            ['hora_inicio' => '09:15', 'hora_fin' => '11:30', 'nombre' => null],
            ['hora_inicio' => '13:45', 'hora_fin' => '16:00', 'nombre' => null],
            ['hora_inicio' => '16:00', 'hora_fin' => '18:15', 'nombre' => null],
            ['hora_inicio' => '18:15', 'hora_fin' => '19:45', 'nombre' => null],
            ['hora_inicio' => '18:15', 'hora_fin' => '20:30', 'nombre' => null],
            ['hora_inicio' => '19:45', 'hora_fin' => '21:15', 'nombre' => null],
        ];

        foreach ($horarios as $horario) {
            Horario::create($horario);
        }
    }
}
