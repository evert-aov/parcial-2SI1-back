<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class AulaController extends Controller
{
    /**
     * Listar todas las aulas
     */
    public function index(): JsonResponse
    {
        try {
            $aulas = Aula::ordenadas()->get()->map(function ($aula) {
                return [
                    'idaula' => $aula->id,
                    'nombre' => $aula->nombre,
                    'codigo' => $aula->codigo,
                    'capacidad' => $aula->capacidad,
                    'tipo' => $aula->tipo,
                    'edificio' => $aula->edificio,
                    'activo' => $aula->activo,
                ];
            });

            return response()->json($aulas);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener aulas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nueva aula
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'nombre' => 'required|string',
                'codigo' => 'required|string|unique:aulas,codigo',
                'capacidad' => 'nullable|integer|min:1',
                'tipo' => 'nullable|string',
                'edificio' => 'nullable|string',
            ]);

            $aula = Aula::create([
                'nombre' => $request->nombre,
                'codigo' => $request->codigo,
                'capacidad' => $request->capacidad,
                'tipo' => $request->tipo,
                'edificio' => $request->edificio,
                'activo' => true,
            ]);

            return response()->json([
                'idaula' => $aula->id,
                'nombre' => $aula->nombre,
                'codigo' => $aula->codigo,
                'capacidad' => $aula->capacidad,
                'tipo' => $aula->tipo,
                'edificio' => $aula->edificio,
                'activo' => $aula->activo,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear aula: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener aula específica
     */
    public function show($id): JsonResponse
    {
        try {
            $aula = Aula::findOrFail($id);

            return response()->json([
                'idaula' => $aula->id,
                'nombre' => $aula->nombre,
                'codigo' => $aula->codigo,
                'capacidad' => $aula->capacidad,
                'tipo' => $aula->tipo,
                'edificio' => $aula->edificio,
                'activo' => $aula->activo,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Aula no encontrada'], 404);
        }
    }

    /**
     * Actualizar aula
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $aula = Aula::findOrFail($id);

            $request->validate([
                'nombre' => 'sometimes|string',
                'codigo' => 'sometimes|string|unique:aulas,codigo,' . $id,
                'capacidad' => 'nullable|integer|min:1',
                'tipo' => 'nullable|string',
                'edificio' => 'nullable|string',
                'activo' => 'sometimes|boolean',
            ]);

            $aula->update($request->only(['nombre', 'codigo', 'capacidad', 'tipo', 'edificio', 'activo']));

            return response()->json([
                'idaula' => $aula->id,
                'nombre' => $aula->nombre,
                'codigo' => $aula->codigo,
                'capacidad' => $aula->capacidad,
                'tipo' => $aula->tipo,
                'edificio' => $aula->edificio,
                'activo' => $aula->activo,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al actualizar aula: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar aula
     */
    public function destroy($id): JsonResponse
    {
        try {
            $aula = Aula::findOrFail($id);
            $aula->delete();

            return response()->json(['message' => 'Aula eliminada correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar aula: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verificar disponibilidad de aulas para un día y horario específico
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'dia_id' => 'required|exists:dias,id',
                'horario_id' => 'required|exists:horarios,id',
                'asignacion_id' => 'nullable|exists:asignaciones,id', // Para excluir al editar
            ]);

            // Obtener aulas ocupadas en ese día y horario
            $aulasOcupadas = \DB::table('asignacion_horarios')
                ->where('dia_id', $request->dia_id)
                ->where('horario_id', $request->horario_id)
                ->when($request->asignacion_id, function ($query) use ($request) {
                    return $query->where('asignacion_id', '!=', $request->asignacion_id);
                })
                ->pluck('aula_id')
                ->toArray();

            // Obtener aulas disponibles
            $aulasDisponibles = Aula::activas()
                ->whereNotIn('id', $aulasOcupadas)
                ->ordenadas()
                ->get()
                ->map(function ($aula) {
                    return [
                        'idaula' => $aula->id,
                        'nombre' => $aula->nombre,
                        'codigo' => $aula->codigo,
                        'capacidad' => $aula->capacidad,
                        'tipo' => $aula->tipo,
                        'edificio' => $aula->edificio,
                    ];
                });

            return response()->json($aulasDisponibles);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al verificar disponibilidad: ' . $e->getMessage()], 500);
        }
    }
}
