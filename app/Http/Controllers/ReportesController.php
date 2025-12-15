<?php

namespace App\Http\Controllers;

use App\Models\AsignacionHorario;
use App\Models\Asistencia;
use App\Models\Aula;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Gestion;
use App\Services\JWTService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportesController extends Controller
{
    /**
     * Reporte de horarios semanales
     */
    public function horariosSemanales(Request $request): JsonResponse
    {
        try {
            $gestionId = $request->input('gestion_id');

            // Si no se especifica gestión, usar la activa
            if (!$gestionId) {
                $gestion = Gestion::where('fecha_inicio', '<=', Carbon::now())
                    ->where('fecha_fin', '>=', Carbon::now())
                    ->first();
                $gestionId = $gestion ? $gestion->id : null;
            }

            if (!$gestionId) {
                return response()->json(['message' => 'No hay gestión activa'], 404);
            }

            // Obtener todos los horarios de la gestión
            $horarios = DB::table('asignacion_horarios as ah')
                ->join('asignaciones as a', 'ah.asignacion_id', '=', 'a.id')
                ->join('gestiones as g', 'a.gestion_id', '=', 'g.id')
                ->join('materias as m', 'a.materia_id', '=', 'm.id')
                ->join('grupos as gr', 'a.grupo_id', '=', 'gr.id')
                ->join('docentes as d', 'a.docente_id', '=', 'd.id')
                ->join('usuarios as u', 'd.usuario_id', '=', 'u.id')
                ->join('aulas as au', 'ah.aula_id', '=', 'au.id')
                ->join('dias as di', 'ah.dia_id', '=', 'di.id')
                ->join('horarios as h', 'ah.horario_id', '=', 'h.id')
                ->where('g.id', $gestionId)
                ->select(
                    'di.nombre as dia',
                    'di.orden as dia_orden',
                    'h.hora_inicio',
                    'h.hora_fin',
                    'm.nombre as materia',
                    DB::raw("CONCAT(u.nombre, ' ', u.apellido) as docente"),
                    'gr.nombre as grupo',
                    'au.nombre as aula',
                    'm.codigo as materia_codigo'
                )
                ->orderBy('di.orden')
                ->orderBy('h.hora_inicio')
                ->get();

            // Agrupar por día y hora
            $horariosPorDia = [];
            foreach ($horarios as $horario) {
                $dia = $horario->dia;
                $horaKey = $horario->hora_inicio . '-' . $horario->hora_fin;

                if (!isset($horariosPorDia[$dia])) {
                    $horariosPorDia[$dia] = [];
                }

                if (!isset($horariosPorDia[$dia][$horaKey])) {
                    $horariosPorDia[$dia][$horaKey] = [
                        'hora_inicio' => $horario->hora_inicio,
                        'hora_fin' => $horario->hora_fin,
                        'clases' => []
                    ];
                }

                $horariosPorDia[$dia][$horaKey]['clases'][] = [
                    'materia' => $horario->materia,
                    'materia_codigo' => $horario->materia_codigo,
                    'docente' => $horario->docente,
                    'grupo' => $horario->grupo,
                    'aula' => $horario->aula
                ];
            }

            return response()->json([
                'gestion_id' => $gestionId,
                'horarios' => $horariosPorDia
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener horarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de asistencias por docente
     */
    public function asistenciasPorDocente(Request $request): JsonResponse
    {
        try {
            $docenteId = $request->input('docente_id');
            $fechaInicio = $request->input('fecha_inicio', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $fechaFin = $request->input('fecha_fin', Carbon::now()->endOfMonth()->format('Y-m-d'));

            if (!$docenteId) {
                return response()->json(['message' => 'Docente requerido'], 400);
            }

            $docente = Docente::with('usuario')->find($docenteId);
            if (!$docente) {
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }

            // Obtener asistencias
            $asistencias = Asistencia::with(['asignacionHorario'])
                ->where('docente_id', $docenteId)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->orderBy('fecha', 'desc')
                ->get();

            // Calcular estadísticas
            $total = $asistencias->count();
            $presentes = $asistencias->where('estado', 'presente')->count();
            $retrasados = $asistencias->where('estado', 'retrasado')->count();
            $faltas = $asistencias->where('estado', 'falta')->count();

            // Agrupar por fecha para gráfico
            $porFecha = $asistencias->groupBy(function ($item) {
                return Carbon::parse($item->fecha)->format('Y-m-d');
            })->map(function ($grupo) {
                return [
                    'total' => $grupo->count(),
                    'presentes' => $grupo->where('estado', 'presente')->count(),
                    'retrasados' => $grupo->where('estado', 'retrasado')->count(),
                    'faltas' => $grupo->where('estado', 'falta')->count(),
                ];
            });

            return response()->json([
                'docente' => [
                    'id' => $docente->id,
                    'nombre' => $docente->usuario->nombre . ' ' . $docente->usuario->apellido,
                    'correo' => $docente->usuario->correo
                ],
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin
                ],
                'estadisticas' => [
                    'total_clases' => $total,
                    'presentes' => $presentes,
                    'retrasados' => $retrasados,
                    'faltas' => $faltas,
                    'porcentaje_asistencia' => $total > 0 ? round(($presentes / $total) * 100, 2) : 0
                ],
                'por_fecha' => $porFecha,
                'detalle' => $asistencias
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener asistencias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de asistencias por grupo
     */
    public function asistenciasPorGrupo(Request $request): JsonResponse
    {
        try {
            $grupoId = $request->input('grupo_id');
            $fechaInicio = $request->input('fecha_inicio', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $fechaFin = $request->input('fecha_fin', Carbon::now()->endOfMonth()->format('Y-m-d'));

            if (!$grupoId) {
                return response()->json(['message' => 'Grupo requerido'], 400);
            }

            $grupo = Grupo::find($grupoId);
            if (!$grupo) {
                return response()->json(['message' => 'Grupo no encontrado'], 404);
            }

            // Obtener asignaciones del grupo
            $asignaciones = DB::table('asignaciones')
                ->where('grupo_id', $grupoId)
                ->pluck('id');

            // Obtener asignacion_horarios del grupo
            $asignacionHorarios = DB::table('asignacion_horarios')
                ->whereIn('asignacion_id', $asignaciones)
                ->pluck('id');

            // Obtener asistencias
            $asistencias = Asistencia::with(['docente.usuario', 'asignacionHorario'])
                ->whereIn('asignacion_horario_id', $asignacionHorarios)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->get();

            // Agrupar por docente
            $porDocente = $asistencias->groupBy('docente_id')->map(function ($grupo, $docenteId) {
                $docente = $grupo->first()->docente;
                $total = $grupo->count();
                $presentes = $grupo->where('estado', 'presente')->count();

                return [
                    'docente_id' => $docenteId,
                    'docente' => $docente->usuario->nombre . ' ' . $docente->usuario->apellido,
                    'total' => $total,
                    'presentes' => $presentes,
                    'retrasados' => $grupo->where('estado', 'retrasado')->count(),
                    'faltas' => $grupo->where('estado', 'falta')->count(),
                    'porcentaje' => $total > 0 ? round(($presentes / $total) * 100, 2) : 0
                ];
            })->values();

            return response()->json([
                'grupo' => [
                    'id' => $grupo->id,
                    'nombre' => $grupo->nombre
                ],
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin
                ],
                'por_docente' => $porDocente,
                'total_registros' => $asistencias->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener asistencias del grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de aulas disponibles
     */
    public function aulasDisponibles(Request $request): JsonResponse
    {
        try {
            $dia = $request->input('dia'); // Nombre del día: Lunes, Martes, etc.
            $horaInicio = $request->input('hora_inicio');
            $horaFin = $request->input('hora_fin');

            if (!$dia || !$horaInicio || !$horaFin) {
                return response()->json(['message' => 'Día, hora inicio y hora fin son requeridos'], 400);
            }

            // Obtener todas las aulas
            $todasLasAulas = Aula::all();

            // Obtener aulas ocupadas en ese horario y día
            $aulasOcupadas = DB::table('asignacion_horarios as ah')
                ->join('aulas as a', 'ah.aula_id', '=', 'a.id')
                ->join('dias as d', 'ah.dia_id', '=', 'd.id')
                ->join('horarios as h', 'ah.horario_id', '=', 'h.id')
                ->join('asignaciones as asig', 'ah.asignacion_id', '=', 'asig.id')
                ->join('materias as m', 'asig.materia_id', '=', 'm.id')
                ->join('grupos as g', 'asig.grupo_id', '=', 'g.id')
                ->where('d.nombre', $dia)
                ->where(function ($query) use ($horaInicio, $horaFin) {
                    // Verificar solapamiento de horarios
                    $query->where(function ($q) use ($horaInicio, $horaFin) {
                        $q->whereBetween('h.hora_inicio', [$horaInicio, $horaFin])
                            ->orWhereBetween('h.hora_fin', [$horaInicio, $horaFin])
                            ->orWhere(function ($q2) use ($horaInicio, $horaFin) {
                                $q2->where('h.hora_inicio', '<=', $horaInicio)
                                    ->where('h.hora_fin', '>=', $horaFin);
                            });
                    });
                })
                ->select(
                    'a.id',
                    'a.nombre',
                    'a.capacidad',
                    'a.edificio',
                    'h.hora_inicio',
                    'h.hora_fin',
                    'm.nombre as materia',
                    'g.nombre as grupo'
                )
                ->get();

            $idsOcupadas = $aulasOcupadas->pluck('id')->unique();

            // Filtrar aulas disponibles
            $aulasDisponibles = $todasLasAulas->filter(function ($aula) use ($idsOcupadas) {
                return !$idsOcupadas->contains($aula->id);
            })->values();

            return response()->json([
                'dia' => $dia,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
                'aulas_disponibles' => $aulasDisponibles,
                'aulas_ocupadas' => $aulasOcupadas,
                'total_disponibles' => $aulasDisponibles->count(),
                'total_ocupadas' => $aulasOcupadas->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener aulas disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
