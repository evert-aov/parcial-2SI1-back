<?php

namespace App\Http\Controllers;

use App\Models\Dia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class DiaController extends Controller
{
    /**
     * Listar todos los días
     */
    public function index(): JsonResponse
    {
        try {
            $dias = Dia::ordenados()->get()->map(function ($dia) {
                return [
                    'iddia' => $dia->id,
                    'nombre' => $dia->nombre,
                    'abreviatura' => $dia->abreviatura,
                    'orden' => $dia->orden,
                ];
            });

            return response()->json($dias);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener días: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nuevo día
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'nombre' => 'required|string|unique:dias,nombre',
                'abreviatura' => 'required|string|max:3|unique:dias,abreviatura',
                'orden' => 'required|integer|min:1|max:7',
            ]);

            $dia = Dia::create([
                'nombre' => $request->nombre,
                'abreviatura' => $request->abreviatura,
                'orden' => $request->orden,
            ]);

            return response()->json([
                'iddia' => $dia->id,
                'nombre' => $dia->nombre,
                'abreviatura' => $dia->abreviatura,
                'orden' => $dia->orden,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear día: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener día específico
     */
    public function show($id): JsonResponse
    {
        try {
            $dia = Dia::findOrFail($id);

            return response()->json([
                'iddia' => $dia->id,
                'nombre' => $dia->nombre,
                'abreviatura' => $dia->abreviatura,
                'orden' => $dia->orden,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Día no encontrado'], 404);
        }
    }

    /**
     * Actualizar día
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $dia = Dia::findOrFail($id);

            $request->validate([
                'nombre' => 'sometimes|string|unique:dias,nombre,' . $id,
                'abreviatura' => 'sometimes|string|max:3|unique:dias,abreviatura,' . $id,
                'orden' => 'sometimes|integer|min:1|max:7',
            ]);

            $dia->update($request->only(['nombre', 'abreviatura', 'orden']));

            return response()->json([
                'iddia' => $dia->id,
                'nombre' => $dia->nombre,
                'abreviatura' => $dia->abreviatura,
                'orden' => $dia->orden,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al actualizar día: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar día
     */
    public function destroy($id): JsonResponse
    {
        try {
            $dia = Dia::findOrFail($id);
            $dia->delete();

            return response()->json(['message' => 'Día eliminado correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar día: ' . $e->getMessage()], 500);
        }
    }
}
