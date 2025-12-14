<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Grupo;
use App\Models\Gestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class AsignacionController extends Controller
{
    /**
     * Listar todas las asignaciones con horarios
     */
    public function index(): JsonResponse
    {
        try {
            $asignaciones = Asignacion::with([
                'docente.usuario',
                'materia.carrera',
                'grupo',
                'gestion'
            ])->get()->map(function ($asignacion) {
                // Obtener horarios agrupados por día
                $horariosPorDia = DB::table('asignacion_horarios')
                    ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
                    ->join('horarios', 'asignacion_horarios.horario_id', '=', 'horarios.id')
                    ->leftJoin('aulas', 'asignacion_horarios.aula_id', '=', 'aulas.id')
                    ->where('asignacion_horarios.asignacion_id', $asignacion->id)
                    ->select(
                        'dias.id as dia_id',
                        'dias.nombre as dia_nombre',
                        'dias.abreviatura as dia_abreviatura',
                        'horarios.id as horario_id',
                        'horarios.hora_inicio',
                        'horarios.hora_fin',
                        'aulas.id as aula_id',
                        'aulas.codigo as aula_codigo',
                        'aulas.nombre as aula_nombre'
                    )
                    ->orderBy('dias.orden')
                    ->orderBy('horarios.hora_inicio')
                    ->get()
                    ->groupBy('dia_id')
                    ->map(function ($horarios, $diaId) {
                        return [
                            'dia_id' => $diaId,
                            'dia_nombre' => $horarios->first()->dia_nombre,
                            'dia_abreviatura' => $horarios->first()->dia_abreviatura,
                            'horarios' => $horarios->map(function ($h) {
                                return [
                                    'horario_id' => $h->horario_id,
                                    'hora_inicio' => date('H:i', strtotime($h->hora_inicio)),
                                    'hora_fin' => date('H:i', strtotime($h->hora_fin)),
                                    'rango' => date('H:i', strtotime($h->hora_inicio)) . '-' . date('H:i', strtotime($h->hora_fin)),
                                    'aula_id' => $h->aula_id,
                                    'aula_codigo' => $h->aula_codigo,
                                    'aula_nombre' => $h->aula_nombre,
                                ];
                            })->values()
                        ];
                    })->values();

                return [
                    'idasignacion' => $asignacion->id,
                    'docente_id' => $asignacion->docente_id,
                    'docente' => $asignacion->docente->usuario->nombre . ' ' . $asignacion->docente->usuario->apellido,
                    'materia_id' => $asignacion->materia_id,
                    'materia_sigla' => $asignacion->materia->sigla,
                    'materia_nombre' => $asignacion->materia->nombre,
                    'carrera' => $asignacion->materia->carrera->nombre,
                    'grupo_id' => $asignacion->grupo_id,
                    'grupo' => $asignacion->grupo->nombre,
                    'gestion_id' => $asignacion->gestion_id,
                    'gestion' => $asignacion->gestion->anio . '-' . $asignacion->gestion->periodo,
                    'horarios' => $horariosPorDia,
                ];
            });

            return response()->json($asignaciones);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener asignaciones: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nueva asignación con horarios
     */
    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'docente_id' => 'required|exists:docentes,id',
                'materia_id' => 'required|exists:materias,id',
                'grupo_id' => 'required|exists:grupos,id',
                'gestion_id' => 'required|exists:gestiones,id',
                'horarios' => 'required|array|min:1',
                'horarios.*.dia_id' => 'required|exists:dias,id',
                'horarios.*.horario_id' => 'required|exists:horarios,id',
                'horarios.*.aula_id' => 'required|exists:aulas,id',
            ]);

            // Verificar si ya existe esta asignación
            $asignacionExistente = Asignacion::where('docente_id', $request->docente_id)
                ->where('materia_id', $request->materia_id)
                ->where('grupo_id', $request->grupo_id)
                ->where('gestion_id', $request->gestion_id)
                ->exists();

            if ($asignacionExistente) {
                return response()->json([
                    'message' => 'Esta asignación ya existe. El docente ya está asignado a esta materia y grupo en esta gestión.'
                ], 422);
            }

            // Crear asignación
            $asignacion = Asignacion::create([
                'docente_id' => $request->docente_id,
                'materia_id' => $request->materia_id,
                'grupo_id' => $request->grupo_id,
                'gestion_id' => $request->gestion_id,
            ]);

            // Agregar horarios con validación de disponibilidad de aula y docente
            foreach ($request->horarios as $horario) {
                // Verificar si el docente ya tiene clase en ese horario
                $docenteOcupado = DB::table('asignacion_horarios')
                    ->join('asignaciones', 'asignacion_horarios.asignacion_id', '=', 'asignaciones.id')
                    ->where('asignaciones.docente_id', $request->docente_id)
                    ->where('asignacion_horarios.dia_id', $horario['dia_id'])
                    ->where('asignacion_horarios.horario_id', $horario['horario_id'])
                    ->exists();

                if ($docenteOcupado) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'El docente ya tiene una clase asignada en ese horario'
                    ], 422);
                }

                // Verificar si el aula está disponible
                $aulaOcupada = DB::table('asignacion_horarios')
                    ->where('dia_id', $horario['dia_id'])
                    ->where('horario_id', $horario['horario_id'])
                    ->where('aula_id', $horario['aula_id'])
                    ->exists();

                if ($aulaOcupada) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'El aula ya está ocupada en ese horario'
                    ], 422);
                }

                DB::table('asignacion_horarios')->insert([
                    'asignacion_id' => $asignacion->id,
                    'dia_id' => $horario['dia_id'],
                    'horario_id' => $horario['horario_id'],
                    'aula_id' => $horario['aula_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'idasignacion' => $asignacion->id,
                'message' => 'Asignación creada correctamente',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            // Verificar si es un error de constraint único
            if (strpos($e->getMessage(), 'unique constraint') !== false || strpos($e->getMessage(), 'Unique violation') !== false) {
                return response()->json([
                    'message' => 'Esta asignación ya existe. El docente ya está asignado a esta materia y grupo en esta gestión.'
                ], 422);
            }
            return response()->json(['message' => 'Error al crear asignación: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener asignación específica
     */
    public function show($id): JsonResponse
    {
        try {
            $asignacion = Asignacion::with([
                'docente.usuario',
                'materia.carrera',
                'grupo',
                'gestion'
            ])->findOrFail($id);

            $horariosPorDia = DB::table('asignacion_horarios')
                ->join('dias', 'asignacion_horarios.dia_id', '=', 'dias.id')
                ->join('horarios', 'asignacion_horarios.horario_id', '=', 'horarios.id')
                ->leftJoin('aulas', 'asignacion_horarios.aula_id', '=', 'aulas.id')
                ->where('asignacion_horarios.asignacion_id', $asignacion->id)
                ->select(
                    'dias.id as dia_id',
                    'dias.nombre as dia_nombre',
                    'dias.abreviatura as dia_abreviatura',
                    'horarios.id as horario_id',
                    'horarios.hora_inicio',
                    'horarios.hora_fin',
                    'aulas.id as aula_id',
                    'aulas.codigo as aula_codigo',
                    'aulas.nombre as aula_nombre'
                )
                ->orderBy('dias.orden')
                ->orderBy('horarios.hora_inicio')
                ->get()
                ->groupBy('dia_id')
                ->map(function ($horarios, $diaId) {
                    return [
                        'dia_id' => $diaId,
                        'dia_nombre' => $horarios->first()->dia_nombre,
                        'dia_abreviatura' => $horarios->first()->dia_abreviatura,
                        'horarios' => $horarios->map(function ($h) {
                            return [
                                'horario_id' => $h->horario_id,
                                'hora_inicio' => date('H:i', strtotime($h->hora_inicio)),
                                'hora_fin' => date('H:i', strtotime($h->hora_fin)),
                                'rango' => date('H:i', strtotime($h->hora_inicio)) . '-' . date('H:i', strtotime($h->hora_fin)),
                                'aula_id' => $h->aula_id,
                                'aula_codigo' => $h->aula_codigo,
                                'aula_nombre' => $h->aula_nombre,
                            ];
                        })->values()
                    ];
                })->values();

            return response()->json([
                'idasignacion' => $asignacion->id,
                'docente_id' => $asignacion->docente_id,
                'docente' => $asignacion->docente->usuario->nombre . ' ' . $asignacion->docente->usuario->apellido,
                'materia_id' => $asignacion->materia_id,
                'materia_sigla' => $asignacion->materia->sigla,
                'materia_nombre' => $asignacion->materia->nombre,
                'carrera' => $asignacion->materia->carrera->nombre,
                'grupo_id' => $asignacion->grupo_id,
                'grupo' => $asignacion->grupo->nombre,
                'gestion_id' => $asignacion->gestion_id,
                'gestion' => $asignacion->gestion->anio . '-' . $asignacion->gestion->periodo,
                'horarios' => $horariosPorDia,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Asignación no encontrada'], 404);
        }
    }

    /**
     * Actualizar asignación y horarios
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $asignacion = Asignacion::findOrFail($id);

            $request->validate([
                'docente_id' => 'sometimes|exists:docentes,id',
                'materia_id' => 'sometimes|exists:materias,id',
                'grupo_id' => 'sometimes|exists:grupos,id',
                'gestion_id' => 'sometimes|exists:gestiones,id',
                'horarios' => 'sometimes|array',
                'horarios.*.dia_id' => 'required_with:horarios|exists:dias,id',
                'horarios.*.horario_id' => 'required_with:horarios|exists:horarios,id',
                'horarios.*.aula_id' => 'required_with:horarios|exists:aulas,id',
            ]);

            // Actualizar asignación
            $asignacion->update($request->only(['docente_id', 'materia_id', 'grupo_id', 'gestion_id']));

            // Si se envían horarios, actualizar
            if ($request->has('horarios')) {
                // Eliminar horarios existentes
                DB::table('asignacion_horarios')
                    ->where('asignacion_id', $asignacion->id)
                    ->delete();

                // Agregar nuevos horarios con validación
                foreach ($request->horarios as $horario) {
                    // Obtener el docente_id actual (puede haber sido actualizado)
                    $docenteId = $request->has('docente_id') ? $request->docente_id : $asignacion->docente_id;

                    // Verificar si el docente ya tiene clase en ese horario (excluyendo esta asignación)
                    $docenteOcupado = DB::table('asignacion_horarios')
                        ->join('asignaciones', 'asignacion_horarios.asignacion_id', '=', 'asignaciones.id')
                        ->where('asignaciones.docente_id', $docenteId)
                        ->where('asignacion_horarios.dia_id', $horario['dia_id'])
                        ->where('asignacion_horarios.horario_id', $horario['horario_id'])
                        ->where('asignacion_horarios.asignacion_id', '!=', $asignacion->id)
                        ->exists();

                    if ($docenteOcupado) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'El docente ya tiene una clase asignada en ese horario'
                        ], 422);
                    }

                    // Verificar disponibilidad del aula (excluyendo esta asignación)
                    $aulaOcupada = DB::table('asignacion_horarios')
                        ->where('dia_id', $horario['dia_id'])
                        ->where('horario_id', $horario['horario_id'])
                        ->where('aula_id', $horario['aula_id'])
                        ->where('asignacion_id', '!=', $asignacion->id)
                        ->exists();

                    if ($aulaOcupada) {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'El aula ya está ocupada en ese horario'
                        ], 422);
                    }

                    DB::table('asignacion_horarios')->insert([
                        'asignacion_id' => $asignacion->id,
                        'dia_id' => $horario['dia_id'],
                        'horario_id' => $horario['horario_id'],
                        'aula_id' => $horario['aula_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Asignación actualizada correctamente']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            // Verificar si es un error de constraint único
            if (strpos($e->getMessage(), 'unique constraint') !== false || strpos($e->getMessage(), 'Unique violation') !== false) {
                return response()->json([
                    'message' => 'Esta asignación ya existe. El docente ya está asignado a esta materia y grupo en esta gestión.'
                ], 422);
            }
            return response()->json(['message' => 'Error al actualizar asignación: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar asignación
     */
    public function destroy($id): JsonResponse
    {
        try {
            $asignacion = Asignacion::findOrFail($id);
            $asignacion->delete(); // Cascade delete will remove asignacion_horarios

            return response()->json(['message' => 'Asignación eliminada correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar asignación: ' . $e->getMessage()], 500);
        }
    }
}
