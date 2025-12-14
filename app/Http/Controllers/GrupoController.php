<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class GrupoController extends Controller
{
    /**
     * Listar todos los grupos
     */
    public function index(): JsonResponse
    {
        try {
            $grupos = Grupo::all()->map(function ($grupo) {
                return [
                    'idgrupo' => $grupo->id,
                    'nombre' => $grupo->nombre,
                ];
            });

            return response()->json($grupos);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener grupos: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nuevo grupo
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'nombre' => 'required|string|unique:grupos,nombre',
            ]);

            $grupo = Grupo::create([
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'idgrupo' => $grupo->id,
                'nombre' => $grupo->nombre,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear grupo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener grupo específico
     */
    public function show($id): JsonResponse
    {
        try {
            $grupo = Grupo::findOrFail($id);

            return response()->json([
                'idgrupo' => $grupo->id,
                'nombre' => $grupo->nombre,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Grupo no encontrado'], 404);
        }
    }

    /**
     * Actualizar grupo
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $grupo = Grupo::findOrFail($id);

            $request->validate([
                'nombre' => 'required|string|unique:grupos,nombre,' . $id,
            ]);

            $grupo->update([
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'idgrupo' => $grupo->id,
                'nombre' => $grupo->nombre,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al actualizar grupo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar grupo
     */
    public function destroy($id): JsonResponse
    {
        try {
            $grupo = Grupo::findOrFail($id);
            $grupo->delete();

            return response()->json(['message' => 'Grupo eliminado correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar grupo: ' . $e->getMessage()], 500);
        }
    }
}
