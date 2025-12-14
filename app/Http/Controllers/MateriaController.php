<?php

namespace App\Http\Controllers;

use App\Models\Materia;
use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class MateriaController extends Controller
{
    /**
     * Listar todas las materias
     */
    public function index(): JsonResponse
    {
        try {
            $materias = Materia::with('carrera')->get()->map(function ($materia) {
                return [
                    'idmateria' => $materia->id,
                    'idcarrera' => $materia->carrera_id,
                    'sigla' => $materia->sigla,
                    'nombre' => $materia->nombre,
                    'carrera' => $materia->carrera->nombre ?? null,
                ];
            });

            return response()->json($materias);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener materias: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nueva materia
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'idcarrera' => 'required|exists:carreras,id',
                'sigla' => 'required|string',
                'nombre' => 'required|string',
            ]);

            $materia = Materia::create([
                'carrera_id' => $request->idcarrera,
                'sigla' => $request->sigla,
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'idmateria' => $materia->id,
                'idcarrera' => $materia->carrera_id,
                'sigla' => $materia->sigla,
                'nombre' => $materia->nombre,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear materia: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener materia específica
     */
    public function show($id): JsonResponse
    {
        try {
            $materia = Materia::with('carrera')->findOrFail($id);

            return response()->json([
                'idmateria' => $materia->id,
                'idcarrera' => $materia->carrera_id,
                'sigla' => $materia->sigla,
                'nombre' => $materia->nombre,
                'carrera' => $materia->carrera->nombre ?? null,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Materia no encontrada'], 404);
        }
    }

    /**
     * Actualizar materia
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $materia = Materia::findOrFail($id);

            $request->validate([
                'idcarrera' => 'sometimes|exists:carreras,id',
                'sigla' => 'sometimes|string',
                'nombre' => 'sometimes|string',
            ]);

            $materia->update([
                'carrera_id' => $request->idcarrera ?? $materia->carrera_id,
                'sigla' => $request->sigla ?? $materia->sigla,
                'nombre' => $request->nombre ?? $materia->nombre,
            ]);

            return response()->json([
                'idmateria' => $materia->id,
                'idcarrera' => $materia->carrera_id,
                'sigla' => $materia->sigla,
                'nombre' => $materia->nombre,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al actualizar materia: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar materia
     */
    public function destroy($id): JsonResponse
    {
        try {
            $materia = Materia::findOrFail($id);
            $materia->delete();

            return response()->json(['message' => 'Materia eliminada correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar materia: ' . $e->getMessage()], 500);
        }
    }
}
