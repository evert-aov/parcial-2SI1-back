<?php

namespace App\Console\Commands;

use App\Models\Asistencia;
use App\Models\Gestion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegistrarFaltasAutomaticas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asistencias:registrar-faltas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Registra automáticamente como falta las clases donde el docente no marcó asistencia';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando registro automático de faltas...');

        // Obtener fecha y día actual
        $fechaHoy = Carbon::today()->format('Y-m-d');
        $horaActual = Carbon::now();
        
        $diasSemana = [
            'Monday' => 'Lunes',
            'Tuesday' => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday' => 'Jueves',
            'Friday' => 'Viernes',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];
        
        $diaIngles = Carbon::now()->format('l');
        $diaHoy = $diasSemana[$diaIngles];

        $this->info("Fecha: {$fechaHoy} - Día: {$diaHoy}");

        // Obtener gestión activa
        $gestionActiva = Gestion::where('fecha_inicio', '<=', $fechaHoy)
            ->where('fecha_fin', '>=', $fechaHoy)
            ->first();

        if (!$gestionActiva) {
            $this->warn('No hay gestión activa para la fecha actual');
            return 0;
        }

        // Buscar todas las clases del día que ya pasaron
        $clasesDelDia = DB::table('asignacion_horarios')
            ->join('asignaciones', 'asignacion_horarios.asignacion_id', '=', 'asignaciones.id')
            ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
            ->join('horarios', 'asignacion_horarios.horario_id', '=', 'horarios.id')
            ->select(
                'asignacion_horarios.id as asignacion_horario_id',
                'asignaciones.docente_id',
                'horarios.hora_fin',
                'dias.nombre as dia'
            )
            ->where('dias.nombre', $diaHoy)
            ->where('asignaciones.gestion_id', $gestionActiva->id)
            ->get();

        $this->info("Clases encontradas para hoy: {$clasesDelDia->count()}");

        $faltasRegistradas = 0;

        foreach ($clasesDelDia as $clase) {
            // Verificar si la clase ya pasó
            $horaFin = Carbon::parse($clase->hora_fin);
            
            if ($horaActual->lessThan($horaFin)) {
                // La clase aún no termina, saltar
                continue;
            }

            // Verificar si ya existe registro de asistencia
            $asistenciaExistente = Asistencia::where('asignacion_horario_id', $clase->asignacion_horario_id)
                ->where('docente_id', $clase->docente_id)
                ->where('fecha', $fechaHoy)
                ->first();

            if ($asistenciaExistente) {
                // Ya hay registro, saltar
                continue;
            }

            // Crear registro de falta automática
            Asistencia::create([
                'asignacion_horario_id' => $clase->asignacion_horario_id,
                'docente_id' => $clase->docente_id,
                'fecha' => $fechaHoy,
                'hora_marcada' => $horaFin->format('H:i:s'),
                'estado' => 'falta',
                'observaciones' => 'Falta registrada automáticamente - No se escaneó QR'
            ]);

            $faltasRegistradas++;
        }

        $this->info("✓ Faltas registradas automáticamente: {$faltasRegistradas}");
        
        return 0;
    }
}
