<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Asignacion;
use App\Models\Aula;
use App\Models\Reserva;
use App\Models\Dia;
use App\Models\Horario;
use App\Exports\HorariosExport;
use App\Exports\AulasDisponiblesExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

class ReporteController extends Controller
{
    /**
     * Obtener estadísticas del dashboard
     */
    public function dashboardStats(): JsonResponse
    {
        try {
            // Docentes activos
            $docentesActivos = Docente::whereHas('usuario', function ($q) {
                $q->where('activo', true);
            })->count();

            // Total usuarios
            $totalUsuarios = DB::table('usuarios')->count();

            // Materias asignadas (asignaciones únicas)
            $materiasAsignadas = Asignacion::distinct('materia_id')->count('materia_id');

            // Total materias
            $totalMaterias = DB::table('materias')->count();

            // Aulas en uso (aulas con al menos una asignación)
            $aulasEnUso = DB::table('asignacion_horarios')
                ->distinct('aula_id')
                ->count('aula_id');

            // Total aulas
            $totalAulas = DB::table('aulas')->count();

            // Reservas por estado
            $reservasPendientes = Reserva::where('estado', 'pendiente')->count();
            $reservasAprobadas = Reserva::where('estado', 'aprobada')->count();
            $reservasRechazadas = Reserva::where('estado', 'rechazada')->count();
            $reservasCanceladas = Reserva::where('estado', 'cancelada')->count();

            // Asignaciones por día
            $asignacionesPorDia = DB::table('asignacion_horarios')
                ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
                ->select('dias.nombre as dia', 'dias.orden', DB::raw('count(*) as total'))
                ->groupBy('dias.id', 'dias.nombre', 'dias.orden')
                ->orderBy('dias.orden')
                ->get();

            return response()->json([
                'docentes_activos' => $docentesActivos,
                'total_usuarios' => $totalUsuarios,
                'materias_asignadas' => $materiasAsignadas,
                'total_materias' => $totalMaterias,
                'aulas_en_uso' => $aulasEnUso,
                'total_aulas' => $totalAulas,
                'reservas_pendientes' => $reservasPendientes,
                'reservas_aprobadas' => $reservasAprobadas,
                'reservas_rechazadas' => $reservasRechazadas,
                'reservas_canceladas' => $reservasCanceladas,
                'asignaciones_por_dia' => $asignacionesPorDia
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener estadísticas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener horarios semanales
     */
    public function horariosSemanales(Request $request): JsonResponse
    {
        try {
            $query = DB::table('asignacion_horarios')
                ->join('asignaciones', 'asignacion_horarios.asignacion_id', '=', 'asignaciones.id')
                ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
                ->join('horarios', 'asignacion_horarios.horario_id', '=', 'horarios.id')
                ->join('aulas', 'asignacion_horarios.aula_id', '=', 'aulas.id')
                ->join('materias', 'asignaciones.materia_id', '=', 'materias.id')
                ->join('grupos', 'asignaciones.grupo_id', '=', 'grupos.id')
                ->join('docentes', 'asignaciones.docente_id', '=', 'docentes.id')
                ->join('usuarios', 'docentes.usuario_id', '=', 'usuarios.id')
                ->join('carreras', 'materias.carrera_id', '=', 'carreras.id')
                ->select(
                    'dias.nombre as dia',
                    'dias.orden as dia_orden',
                    'horarios.hora_inicio',
                    'horarios.hora_fin',
                    'materias.nombre as materia',
                    'materias.sigla',
                    DB::raw("CONCAT(usuarios.nombre, ' ', usuarios.apellido) as docente"),
                    'grupos.nombre as grupo',
                    'aulas.codigo as aula',
                    'carreras.nombre as carrera'
                )
                ->orderBy('dias.orden')
                ->orderBy('horarios.hora_inicio');

            // Filtros opcionales
            if ($request->has('carrera_id')) {
                $query->where('materias.carrera_id', $request->carrera_id);
            }
            if ($request->has('grupo_id')) {
                $query->where('asignaciones.grupo_id', $request->grupo_id);
            }
            if ($request->has('docente_id')) {
                $query->where('asignaciones.docente_id', $request->docente_id);
            }

            $horarios = $query->get();

            return response()->json(['horarios' => $horarios]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener horarios: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Exportar horarios a Excel
     */
    public function exportarHorariosExcel(Request $request)
    {
        try {
            return Excel::download(
                new HorariosExport($request->all()),
                'horarios_semanales_' . now()->format('Y-m-d') . '.xlsx'
            );
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al generar Excel: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener aulas disponibles
     */
    public function aulasDisponibles(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'fecha' => 'required|date',
                'horario_id' => 'required|exists:horarios,id'
            ]);

            $fecha = $request->fecha;
            $horarioId = $request->horario_id;

            // Obtener información del horario seleccionado
            $horario = Horario::find($horarioId);
            if (!$horario) {
                return response()->json(['message' => 'Horario no encontrado'], 404);
            }

            // Obtener día de la semana (1=Lunes, 7=Domingo)
            $diaSemana = date('N', strtotime($fecha));

            // Buscar aulas ocupadas en ese día y horario
            $aulasOcupadas = DB::table('asignacion_horarios')
                ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
                ->where('dias.orden', $diaSemana)
                ->where('asignacion_horarios.horario_id', $horarioId)
                ->pluck('asignacion_horarios.aula_id');

            // También verificar reservas para esa fecha específica
            // Verificar solapamiento de horarios
            $aulasReservadas = Reserva::where('fecha', $fecha)
                ->where(function ($q) use ($horario) {
                    // Verificar si hay solapamiento de horarios
                    $q->where(function ($query) use ($horario) {
                        // Caso 1: La reserva empieza durante nuestro horario
                        $query->whereBetween('hora_inicio', [$horario->hora_inicio, $horario->hora_fin])
                            // Caso 2: La reserva termina durante nuestro horario
                            ->orWhereBetween('hora_fin', [$horario->hora_inicio, $horario->hora_fin])
                            // Caso 3: La reserva cubre completamente nuestro horario
                            ->orWhere(function ($q2) use ($horario) {
                                $q2->where('hora_inicio', '<=', $horario->hora_inicio)
                                    ->where('hora_fin', '>=', $horario->hora_fin);
                            });
                    });
                })
                ->whereIn('estado', ['pendiente', 'aprobada'])
                ->pluck('aula_id');

            $aulasNoDisponibles = $aulasOcupadas->merge($aulasReservadas)->unique();

            // Obtener aulas disponibles
            $aulasDisponibles = Aula::whereNotIn('id', $aulasNoDisponibles)
                ->where('activo', true)
                ->select('id', 'codigo', 'nombre', 'capacidad', 'tipo', 'edificio')
                ->orderBy('codigo')
                ->get();

            return response()->json([
                'aulas_disponibles' => $aulasDisponibles,
                'total' => $aulasDisponibles->count()
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al consultar aulas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Exportar aulas disponibles a Excel
     */
    public function exportarAulasDisponiblesExcel(Request $request)
    {
        try {
            return Excel::download(
                new AulasDisponiblesExport($request->all()),
                'aulas_disponibles_' . now()->format('Y-m-d') . '.xlsx'
            );
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al generar Excel: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Tablas permitidas para reportes dinámicos
     */
    protected $tablasPermitidas = [
        'usuarios' => 'Usuarios',
        'docentes' => 'Docentes',
        'materias' => 'Materias',
        'asignaciones' => 'Asignaciones',
        'aulas' => 'Aulas',
        'reservas' => 'Reservas',
        'carreras' => 'Carreras',
        'grupos' => 'Grupos',
        'gestiones' => 'Gestiones',
        'horarios' => 'Horarios',
        'dias' => 'Días',
    ];

    /**
     * Operadores permitidos
     */
    protected $operadoresPermitidos = ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'IN'];

    /**
     * Obtener tablas disponibles con sus columnas
     */
    public function tablasDisponibles(): JsonResponse
    {
        try {
            $tablas = [];

            foreach ($this->tablasPermitidas as $nombreTabla => $label) {
                $columnas = DB::getSchemaBuilder()->getColumnListing($nombreTabla);

                $columnasInfo = [];
                foreach ($columnas as $columna) {
                    $tipo = DB::getSchemaBuilder()->getColumnType($nombreTabla, $columna);
                    $columnasInfo[] = [
                        'nombre' => $columna,
                        'tipo' => $tipo,
                        'label' => ucfirst(str_replace('_', ' ', $columna))
                    ];
                }

                $tablas[] = [
                    'nombre' => $nombreTabla,
                    'label' => $label,
                    'columnas' => $columnasInfo
                ];
            }

            return response()->json(['tablas' => $tablas]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener tablas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generar reporte dinámico
     */
    public function generarReporteDinamico(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tabla' => 'required|string',
                'campos' => 'required|array',
                'campos.*' => 'string',
                'filtros' => 'nullable|array',
                'orden' => 'nullable|array',
                'limite' => 'nullable|integer|max:1000'
            ]);

            $tabla = $request->tabla;
            $campos = $request->campos;
            $filtros = $request->filtros ?? [];
            $orden = $request->orden ?? null;
            $limite = $request->limite ?? 100;

            // Validar tabla
            if (!isset($this->tablasPermitidas[$tabla])) {
                return response()->json(['message' => 'Tabla no permitida'], 403);
            }

            // Validar columnas
            $columnasDisponibles = DB::getSchemaBuilder()->getColumnListing($tabla);
            foreach ($campos as $campo) {
                if (!in_array($campo, $columnasDisponibles)) {
                    return response()->json(['message' => "Campo inválido: $campo"], 400);
                }
            }

            // Construir query
            $query = DB::table($tabla)->select($campos);

            // Aplicar filtros
            foreach ($filtros as $filtro) {
                if (!isset($filtro['campo'], $filtro['operador'], $filtro['valor'])) {
                    continue;
                }

                $campo = $filtro['campo'];
                $operador = $filtro['operador'];
                $valor = $filtro['valor'];

                // Validar campo y operador
                if (!in_array($campo, $columnasDisponibles)) {
                    continue;
                }
                if (!in_array($operador, $this->operadoresPermitidos)) {
                    continue;
                }

                // Aplicar filtro según operador
                if ($operador === 'IN') {
                    $query->whereIn($campo, is_array($valor) ? $valor : explode(',', $valor));
                } elseif ($operador === 'LIKE' || $operador === 'NOT LIKE') {
                    $query->where($campo, $operador, "%{$valor}%");
                } else {
                    $query->where($campo, $operador, $valor);
                }
            }

            // Aplicar ordenamiento
            if ($orden && isset($orden['campo'], $orden['direccion'])) {
                $campoOrden = $orden['campo'];
                $direccion = strtolower($orden['direccion']) === 'desc' ? 'desc' : 'asc';

                if (in_array($campoOrden, $columnasDisponibles)) {
                    $query->orderBy($campoOrden, $direccion);
                }
            }

            // Aplicar límite
            $query->limit($limite);

            $resultados = $query->get();

            return response()->json([
                'tabla' => $this->tablasPermitidas[$tabla],
                'total' => $resultados->count(),
                'datos' => $resultados
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al generar reporte: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Exportar reporte dinámico a Excel
     */
    public function exportarReporteDinamicoExcel(Request $request)
    {
        try {
            // Reutilizar la lógica de generarReporteDinamico pero retornar Excel
            $request->validate([
                'tabla' => 'required|string',
                'campos' => 'required|array',
            ]);

            $tabla = $request->tabla;

            if (!isset($this->tablasPermitidas[$tabla])) {
                return response()->json(['message' => 'Tabla no permitida'], 403);
            }

            return Excel::download(
                new \App\Exports\DynamicReportExport($request->all()),
                "reporte_{$tabla}_" . now()->format('Y-m-d') . '.xlsx'
            );
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al exportar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reporte de asistencias por docente
     */
    public function asistenciasPorDocente(Request $request): JsonResponse
    {
        try {
            $docenteId = $request->input('docente_id');
            $fechaInicio = $request->input('fecha_inicio', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'));
            $fechaFin = $request->input('fecha_fin', \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d'));

            if (!$docenteId) {
                return response()->json(['message' => 'Docente requerido'], 400);
            }

            $docente = Docente::with('usuario')->find($docenteId);
            if (!$docente) {
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }

            // Obtener asistencias
            $asistencias = \App\Models\Asistencia::with(['asignacionHorario'])
                ->where('docente_id', $docenteId)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->orderBy('fecha', 'desc')
                ->get();

            // Calcular estadísticas
            $total = $asistencias->count();
            $presentes = $asistencias->where('estado', 'presente')->count();
            $retrasados = $asistencias->where('estado', 'retrasado')->count();
            $faltas = $asistencias->where('estado', 'falta')->count();

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
                ]
            ]);
        } catch (Exception $e) {
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
            $fechaInicio = $request->input('fecha_inicio', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'));
            $fechaFin = $request->input('fecha_fin', \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d'));

            if (!$grupoId) {
                return response()->json(['message' => 'Grupo requerido'], 400);
            }

            $grupo = \App\Models\Grupo::find($grupoId);
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
            $asistencias = \App\Models\Asistencia::with(['docente.usuario', 'asignacionHorario'])
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
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener asistencias del grupo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
