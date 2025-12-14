<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class CarreraController extends Controller
{
    /**
     * Listar todas las carreras
     */
    public function index(): JsonResponse
    {
        try {
            $carreras = Carrera::all()->map(function ($carrera) {
                return [
                    'idcarrera' => $carrera->id,
                    'nombre' => $carrera->nombre,
                ];
            });

            return response()->json($carreras);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener carreras: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nueva carrera
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'nombre' => 'required|string|unique:carreras,nombre',
            ]);

            $carrera = Carrera::create([
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'idcarrera' => $carrera->id,
                'nombre' => $carrera->nombre,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear carrera: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener carrera específica
     */
    public function show($id): JsonResponse
    {
        try {
            $carrera = Carrera::findOrFail($id);

            return response()->json([
                'idcarrera' => $carrera->id,
                'nombre' => $carrera->nombre,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Carrera no encontrada'], 404);
        }
    }

    /**
     * Actualizar carrera
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $carrera = Carrera::findOrFail($id);

            $request->validate([
                'nombre' => 'required|string|unique:carreras,nombre,' . $id,
            ]);

            $carrera->update([
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'idcarrera' => $carrera->id,
                'nombre' => $carrera->nombre,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al actualizar carrera: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar carrera
     */
    public function destroy($id): JsonResponse
    {
        try {
            $carrera = Carrera::findOrFail($id);
            $carrera->delete();

            return response()->json(['message' => 'Carrera eliminada correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar carrera: ' . $e->getMessage()], 500);
        }
    }
}
