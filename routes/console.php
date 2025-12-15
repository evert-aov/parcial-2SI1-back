<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programar registro automático de faltas
Schedule::command('asistencias:registrar-faltas')
    ->dailyAt('23:00')
    ->timezone('America/La_Paz')
    ->description('Registrar faltas automáticas para clases sin asistencia');
