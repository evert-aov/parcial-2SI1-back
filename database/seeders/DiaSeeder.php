<?php

namespace Database\Seeders;

use App\Models\Dia;
use Illuminate\Database\Seeder;

class DiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dias = [
            ['nombre' => 'Lunes', 'abreviatura' => 'Lun', 'orden' => 1],
            ['nombre' => 'Martes', 'abreviatura' => 'Mar', 'orden' => 2],
            ['nombre' => 'Miércoles', 'abreviatura' => 'Mie', 'orden' => 3],
            ['nombre' => 'Jueves', 'abreviatura' => 'Jue', 'orden' => 4],
            ['nombre' => 'Viernes', 'abreviatura' => 'Vie', 'orden' => 5],
            ['nombre' => 'Sábado', 'abreviatura' => 'Sab', 'orden' => 6],
            ['nombre' => 'Domingo', 'abreviatura' => 'Dom', 'orden' => 7],
        ];

        foreach ($dias as $dia) {
            Dia::create($dia);
        }
    }
}
