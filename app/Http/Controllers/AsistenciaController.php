<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Docente;
use App\Models\AsignacionHorario;
use App\Services\JWTService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;
use Exception;

class AsistenciaController extends Controller
{
    /**
     * Generar código QR para una clase
     */
    public function generarQR(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'asignacion_horario_id' => 'required|exists:asignacion_horarios,id',
                'fecha' => 'required|date'
            ]);

            $asignacionHorario = DB::table('asignacion_horarios')
                ->join('horarios', 'asignacion_horarios.horario_id', '=', 'horarios.id')
                ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
                ->join('asignaciones', 'asignacion_horarios.asignacion_id', '=', 'asignaciones.id')
                ->join('materias', 'asignaciones.materia_id', '=', 'materias.id')
                ->select(
                    'asignacion_horarios.*',
                    'horarios.hora_inicio',
                    'horarios.hora_fin',
                    'dias.nombre as dia',
                    'materias.nombre as materia'
                )
                ->where('asignacion_horarios.id', $request->asignacion_horario_id)
                ->first();

            if (!$asignacionHorario) {
                return response()->json(['message' => 'Asignación de horario no encontrada'], 404);
            }

            // Crear token único con datos encriptados
            $data = [
                'asignacion_horario_id' => $request->asignacion_horario_id,
                'fecha' => $request->fecha,
                'token' => bin2hex(random_bytes(16)),
                'expires_at' => now()->addHours(2)->toDateTimeString()
            ];

            $encrypted = encrypt($data);

            // Generar QR en formato SVG (no requiere imagick)
            $qrCode = QrCode::format('svg')
                ->size(400)
                ->margin(2)
                ->generate($encrypted);

            return response()->json([
                'message' => 'QR generado exitosamente',
                'qr' => base64_encode($qrCode),
                'data' => $encrypted,
                'info' => [
                    'materia' => $asignacionHorario->materia,
                    'dia' => $asignacionHorario->dia,
                    'hora_inicio' => $asignacionHorario->hora_inicio,
                    'hora_fin' => $asignacionHorario->hora_fin,
                    'fecha' => $request->fecha,
                    'valido_hasta' => $data['expires_at']
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al generar QR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar asistencia escaneando QR
     */
    public function marcarAsistencia(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'qr_data' => 'required|string',
                'latitud' => 'nullable|numeric',
                'longitud' => 'nullable|numeric'
            ]);

            // Obtener docente autenticado
            $token = $request->bearerToken();
            $jwtService = new JWTService();
            $payload = $jwtService->validateToken($token);

            $docente = Docente::where('usuario_id', $payload['user_id'])->first();

            if (!$docente) {
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }

            // Desencriptar datos del QR
            try {
                $data = decrypt($request->qr_data);
            } catch (Exception $e) {
                return response()->json(['message' => 'QR inválido o corrupto'], 400);
            }

            // Validar expiración del QR
            if (Carbon::parse($data['expires_at'])->isPast()) {
                return response()->json(['message' => 'El QR ha expirado'], 400);
            }

            // Obtener información de la asignación
            $asignacionHorario = DB::table('asignacion_horarios')
                ->join('asignaciones', 'asignacion_horarios.asignacion_id', '=', 'asignaciones.id')
                ->join('horarios', 'asignacion_horarios.horario_id', '=', 'horarios.id')
                ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
                ->select(
                    'asignacion_horarios.*',
                    'asignaciones.docente_id',
                    'horarios.hora_inicio',
                    'horarios.hora_fin',
                    'dias.nombre as dia'
                )
                ->where('asignacion_horarios.id', $data['asignacion_horario_id'])
                ->first();

            if (!$asignacionHorario) {
                return response()->json(['message' => 'Asignación no encontrada'], 404);
            }

            // Validar que el docente esté asignado a esta clase
            if ($asignacionHorario->docente_id != $docente->id) {
                return response()->json(['message' => 'No estás asignado a esta clase'], 403);
            }

            // Validar que sea la fecha correcta
            $fechaQR = Carbon::parse($data['fecha']);
            $hoy = Carbon::today();

            if (!$fechaQR->isSameDay($hoy)) {
                return response()->json(['message' => 'Este QR es para otra fecha'], 400);
            }

            // Verificar si ya marcó asistencia
            $asistenciaExistente = Asistencia::where('asignacion_horario_id', $data['asignacion_horario_id'])
                ->where('docente_id', $docente->id)
                ->where('fecha', $data['fecha'])
                ->first();

            if ($asistenciaExistente) {
                return response()->json([
                    'message' => 'Ya has marcado asistencia para esta clase',
                    'asistencia' => $asistenciaExistente
                ], 400);
            }

            // Calcular estado basado en la hora
            $horaMarcada = Carbon::now();
            $horaInicio = Carbon::parse($asignacionHorario->hora_inicio);

            $estado = $this->calcularEstado($horaInicio, $horaMarcada);

            // Crear registro de asistencia
            $asistencia = Asistencia::create([
                'asignacion_horario_id' => $data['asignacion_horario_id'],
                'docente_id' => $docente->id,
                'fecha' => $data['fecha'],
                'hora_marcada' => $horaMarcada->format('H:i:s'),
                'estado' => $estado,
                'latitud' => $request->latitud,
                'longitud' => $request->longitud
            ]);

            return response()->json([
                'message' => 'Asistencia marcada exitosamente',
                'asistencia' => $asistencia,
                'estado' => $estado,
                'hora_marcada' => $horaMarcada->format('H:i:s'),
                'hora_inicio' => $horaInicio->format('H:i:s'),
                'diferencia_minutos' => $horaMarcada->diffInMinutes($horaInicio, false)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al marcar asistencia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular estado de asistencia basado en la hora
     */
    private function calcularEstado(Carbon $horaInicio, Carbon $horaMarcada): string
    {
        $diferencia = $horaMarcada->diffInMinutes($horaInicio, false);

        if ($diferencia <= 0) {
            return 'presente'; // Marcó antes o a tiempo
        } elseif ($diferencia <= 10) {
            return 'retrasado'; // 1-10 minutos tarde
        } else {
            return 'falta'; // Más de 10 minutos tarde
        }
    }

    /**
     * Obtener asistencias del docente autenticado
     */
    public function misAsistencias(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();
            $jwtService = new JWTService();
            $payload = $jwtService->validateToken($token);

            $docente = Docente::where('usuario_id', $payload['user_id'])->first();

            if (!$docente) {
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }

            $query = Asistencia::with(['asignacionHorario'])
                ->where('docente_id', $docente->id);

            // Filtros opcionales
            if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
                $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $asistencias = $query->orderBy('fecha', 'desc')
                ->orderBy('hora_marcada', 'desc')
                ->get();

            // Calcular estadísticas
            $total = $asistencias->count();
            $presentes = $asistencias->where('estado', 'presente')->count();
            $retrasados = $asistencias->where('estado', 'retrasado')->count();
            $faltas = $asistencias->where('estado', 'falta')->count();

            return response()->json([
                'asistencias' => $asistencias,
                'estadisticas' => [
                    'total' => $total,
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
     * Reporte de asistencias (Admin)
     */
    public function reporte(Request $request): JsonResponse
    {
        try {
            $query = Asistencia::with(['docente.usuario', 'asignacionHorario']);

            // Filtros
            if ($request->has('docente_id')) {
                $query->where('docente_id', $request->docente_id);
            }

            if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
                $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $asistencias = $query->orderBy('fecha', 'desc')->get();

            // Estadísticas generales
            $total = $asistencias->count();
            $presentes = $asistencias->where('estado', 'presente')->count();
            $retrasados = $asistencias->where('estado', 'retrasado')->count();
            $faltas = $asistencias->where('estado', 'falta')->count();

            return response()->json([
                'asistencias' => $asistencias,
                'estadisticas' => [
                    'total' => $total,
                    'presentes' => $presentes,
                    'retrasados' => $retrasados,
                    'faltas' => $faltas,
                    'porcentaje_asistencia' => $total > 0 ? round((($presentes + $retrasados) / $total) * 100, 2) : 0
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al generar reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener QR codes automáticos del horario del docente para hoy
     */
    public function misQRsHoy(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();
            $jwtService = new JWTService();
            $payload = $jwtService->validateToken($token);

            $docente = Docente::where('usuario_id', $payload['user_id'])->first();

            if (!$docente) {
                return response()->json(['message' => 'Docente no encontrado'], 404);
            }

            // Obtener día de la semana actual (en español)
            $diasSemana = [
                'Monday' => 'Lunes',
                'Tuesday' => 'Martes',
                'Wednesday' => 'Miércoles',
                'Thursday' => 'Jueves',
                'Friday' => 'Viernes',
                'Saturday' => 'Sábado',
                'Sunday' => 'Domingo'
            ];

            $diaIngles = Carbon::now()->format('l'); // Obtiene el día en inglés
            $diaHoy = $diasSemana[$diaIngles]; // Convierte a español
            $fechaHoy = Carbon::today()->format('Y-m-d');

            // Obtener asignaciones del docente para el día de hoy
            $asignacionesHoy = DB::table('asignacion_horarios')
                ->join('asignaciones', 'asignacion_horarios.asignacion_id', '=', 'asignaciones.id')
                ->join('gestiones', 'asignaciones.gestion_id', '=', 'gestiones.id')
                ->join('horarios', 'asignacion_horarios.horario_id', '=', 'horarios.id')
                ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
                ->join('materias', 'asignaciones.materia_id', '=', 'materias.id')
                ->join('aulas', 'asignacion_horarios.aula_id', '=', 'aulas.id')
                ->join('grupos', 'asignaciones.grupo_id', '=', 'grupos.id')
                ->select(
                    'asignacion_horarios.id as asignacion_horario_id',
                    'materias.nombre as materia',
                    'grupos.nombre as grupo',
                    'aulas.nombre as aula',
                    'horarios.hora_inicio',
                    'horarios.hora_fin',
                    'dias.nombre as dia'
                )
                ->where('asignaciones.docente_id', $docente->id)
                ->where('dias.nombre', $diaHoy)
                ->where('gestiones.fecha_inicio', '<=', $fechaHoy)
                ->where('gestiones.fecha_fin', '>=', $fechaHoy)
                ->orderBy('horarios.hora_inicio')
                ->get();

            $qrCodes = [];

            foreach ($asignacionesHoy as $asignacion) {
                // Verificar si ya marcó asistencia
                $yaMarco = Asistencia::where('asignacion_horario_id', $asignacion->asignacion_horario_id)
                    ->where('docente_id', $docente->id)
                    ->where('fecha', $fechaHoy)
                    ->first();

                // Crear token único con datos encriptados
                $data = [
                    'asignacion_horario_id' => $asignacion->asignacion_horario_id,
                    'fecha' => $fechaHoy,
                    'token' => bin2hex(random_bytes(16)),
                    'expires_at' => Carbon::parse($asignacion->hora_fin)->addHours(1)->toDateTimeString()
                ];

                $encrypted = encrypt($data);

                // Generar QR en formato SVG (no requiere imagick)
                $qrCode = QrCode::format('svg')
                    ->size(300)
                    ->margin(2)
                    ->generate($encrypted);

                $qrCodes[] = [
                    'asignacion_horario_id' => $asignacion->asignacion_horario_id,
                    'materia' => $asignacion->materia,
                    'grupo' => $asignacion->grupo,
                    'aula' => $asignacion->aula,
                    'hora_inicio' => $asignacion->hora_inicio,
                    'hora_fin' => $asignacion->hora_fin,
                    'qr_svg' => $qrCode, // SVG como texto
                    'qr_data' => $encrypted,
                    'ya_marco' => $yaMarco ? true : false,
                    'estado_marcado' => $yaMarco ? $yaMarco->estado : null,
                    'hora_marcada' => $yaMarco ? $yaMarco->hora_marcada : null
                ];
            }

            return response()->json([
                'fecha' => $fechaHoy,
                'dia' => $diaHoy,
                'total_clases' => count($qrCodes),
                'clases' => $qrCodes
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener QR codes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
