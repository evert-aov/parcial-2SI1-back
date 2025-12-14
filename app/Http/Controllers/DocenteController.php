<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Usuario;
use App\Services\JWTService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\DB;

class DocenteController extends Controller
{
    /**
     * Obtener todos los docentes
     */
    public function index(): JsonResponse
    {
        try {
            $docentes = Docente::with('usuario.role')->get()->map(function ($docente) {
                return [
                    'coddocente' => $docente->id,
                    'userid' => $docente->usuario->id,
                    'nombre' => $docente->usuario->nombre,
                    'apellido' => $docente->usuario->apellido,
                    'correo' => $docente->usuario->correo,
                    'ci' => $docente->usuario->ci,
                    'telefono' => $docente->usuario->telefono,
                    'sexo' => $docente->usuario->sexo,
                    'direccion' => $docente->usuario->direccion,
                    'activo' => $docente->usuario->activo,
                    'idrol' => $docente->usuario->rol_id,
                    'rol' => $docente->usuario->role->nombre,
                    'especialidad' => $docente->especialidad,
                    'fecha_contrato' => $docente->fecha_contrato->format('Y-m-d'),
                ];
            });

            return response()->json($docentes);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener docentes: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nuevo docente
     */
    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validar datos
            $request->validate([
                'nombre' => 'required|string',
                'apellido' => 'required|string',
                'correo' => 'required|email|unique:usuarios,correo',
                'ci' => 'required|string|unique:usuarios,ci',
                'contrasena' => 'required|string|min:6',
                'fecha_contrato' => 'required|date',
            ]);

            // Crear usuario con rol docente (3)
            $usuario = Usuario::create([
                'rol_id' => 3, // Docente
                'contrasena' => $request->contrasena,
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'telefono' => $request->telefono,
                'sexo' => $request->sexo,
                'correo' => $request->correo,
                'ci' => $request->ci,
                'direccion' => $request->direccion,
                'activo' => true,
            ]);

            // Crear docente
            $docente = Docente::create([
                'usuario_id' => $usuario->id,
                'especialidad' => $request->especialidad,
                'fecha_contrato' => $request->fecha_contrato,
            ]);

            DB::commit();

            return response()->json([
                'user_id' => $usuario->id,
                'cod_docente' => $docente->id,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear docente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener docente específico
     */
    public function show($id): JsonResponse
    {
        try {
            $docente = Docente::with('usuario.role')->findOrFail($id);

            return response()->json([
                'coddocente' => $docente->id,
                'userid' => $docente->usuario->id,
                'nombre' => $docente->usuario->nombre,
                'apellido' => $docente->usuario->apellido,
                'correo' => $docente->usuario->correo,
                'ci' => $docente->usuario->ci,
                'telefono' => $docente->usuario->telefono,
                'sexo' => $docente->usuario->sexo,
                'direccion' => $docente->usuario->direccion,
                'activo' => $docente->usuario->activo,
                'idrol' => $docente->usuario->rol_id,
                'rol' => $docente->usuario->role->nombre,
                'especialidad' => $docente->especialidad,
                'fecha_contrato' => $docente->fecha_contrato->format('Y-m-d'),
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Docente no encontrado'], 404);
        }
    }

    /**
     * Actualizar docente
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $docente = Docente::with('usuario')->findOrFail($id);

            // Actualizar usuario
            $docente->usuario->update([
                'nombre' => $request->nombre ?? $docente->usuario->nombre,
                'apellido' => $request->apellido ?? $docente->usuario->apellido,
                'telefono' => $request->telefono,
                'sexo' => $request->sexo,
                'direccion' => $request->direccion,
                'activo' => $request->activo ?? true,
            ]);

            // Actualizar docente
            $docente->update([
                'especialidad' => $request->especialidad,
                'fecha_contrato' => $request->fecha_contrato ?? $docente->fecha_contrato,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Docente actualizado correctamente',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar docente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar docente (desactivar)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $docente = Docente::with('usuario')->findOrFail($id);

            // Desactivar usuario en lugar de eliminar
            $docente->usuario->update(['activo' => false]);

            return response()->json(['message' => 'Docente desactivado correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar docente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener carga horaria del docente autenticado
     */
    public function miCargaHoraria(Request $request): JsonResponse
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json(['message' => 'Token no proporcionado'], 401);
            }

            // Decodificar token para obtener user_id
            $jwtService = new \App\Services\JWTService();
            $payload = $jwtService->validateToken($token);
            $userId = $payload['user_id'] ?? null;

            if (!$userId) {
                return response()->json(['message' => 'Token inválido: no contiene user_id'], 401);
            }

            // Buscar docente por usuario_id
            $docente = Docente::where('usuario_id', $userId)
                ->with([
                    'usuario',
                    'asignaciones.materia.carrera',
                    'asignaciones.grupo',
                    'asignaciones.gestion',
                    'asignaciones.dias',
                    'asignaciones.horarios'
                ])
                ->first();

            if (!$docente) {
                return response()->json([
                    'message' => 'No se encontró perfil de docente para este usuario',
                    'user_id' => $userId
                ], 404);
            }

            // Formatear respuesta
            $asignaciones = $docente->asignaciones->map(function ($asignacion) {
                // Obtener los horarios desde la tabla pivot
                $horariosData = DB::table('asignacion_horarios')
                    ->where('asignacion_id', $asignacion->id)
                    ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
                    ->join('horarios', 'asignacion_horarios.horario_id', '=', 'horarios.id')
                    ->join('aulas', 'asignacion_horarios.aula_id', '=', 'aulas.id')
                    ->select(
                        'dias.nombre as dia',
                        'dias.abreviatura as dia_abrev',
                        'dias.orden as dia_orden',
                        'horarios.hora_inicio as inicio',
                        'horarios.hora_fin as fin',
                        'aulas.codigo as aula',
                        'aulas.nombre as aula_nombre'
                    )
                    ->orderBy('dias.orden')
                    ->get();

                return [
                    'id' => $asignacion->id,
                    'materia' => $asignacion->materia->nombre,
                    'sigla' => $asignacion->materia->sigla,
                    'carrera' => $asignacion->materia->carrera->nombre,
                    'grupo' => $asignacion->grupo->nombre,
                    'gestion' => $asignacion->gestion->nombre,
                    'horarios' => $horariosData,
                    'total_horas_semana' => $horariosData->count() * 1.5
                ];
            });

            return response()->json([
                'docente' => [
                    'nombre' => $docente->usuario->nombre . ' ' . $docente->usuario->apellido,
                    'especialidad' => $docente->especialidad,
                ],
                'estadisticas' => [
                    'total_materias' => $asignaciones->count(),
                    'total_horas' => $asignaciones->sum('total_horas_semana'),
                ],
                'asignaciones' => $asignaciones
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener carga horaria: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
