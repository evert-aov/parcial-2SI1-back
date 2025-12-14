<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class GestionController extends Controller
{
    /**
     * Listar todas las gestiones
     */
    public function index(): JsonResponse
    {
        try {
            $gestiones = Gestion::orderBy('anio', 'desc')
                ->orderBy('periodo', 'desc')
                ->get()
                ->map(function ($gestion) {
                    return [
                        'idgestion' => $gestion->id,
                        'anio' => $gestion->anio,
                        'periodo' => $gestion->periodo,
                        'fecha_inicio' => $gestion->fecha_inicio->format('Y-m-d'),
                        'fecha_fin' => $gestion->fecha_fin->format('Y-m-d'),
                        'nombre_completo' => $gestion->nombreCompleto,
                    ];
                });

            return response()->json($gestiones);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener gestiones: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nueva gestión
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'anio' => 'required|integer|min:2000|max:2100',
                'periodo' => 'required|integer|min:1|max:2',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
            ]);

            $gestion = Gestion::create([
                'anio' => $request->anio,
                'periodo' => $request->periodo,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
            ]);

            return response()->json([
                'idgestion' => $gestion->id,
                'anio' => $gestion->anio,
                'periodo' => $gestion->periodo,
                'fecha_inicio' => $gestion->fecha_inicio->format('Y-m-d'),
                'fecha_fin' => $gestion->fecha_fin->format('Y-m-d'),
                'nombre_completo' => $gestion->nombreCompleto,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear gestión: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener gestión específica
     */
    public function show($id): JsonResponse
    {
        try {
            $gestion = Gestion::findOrFail($id);

            return response()->json([
                'idgestion' => $gestion->id,
                'anio' => $gestion->anio,
                'periodo' => $gestion->periodo,
                'fecha_inicio' => $gestion->fecha_inicio->format('Y-m-d'),
                'fecha_fin' => $gestion->fecha_fin->format('Y-m-d'),
                'nombre_completo' => $gestion->nombreCompleto,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Gestión no encontrada'], 404);
        }
    }

    /**
     * Actualizar gestión
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $gestion = Gestion::findOrFail($id);

            $request->validate([
                'anio' => 'sometimes|integer|min:2000|max:2100',
                'periodo' => 'sometimes|integer|min:1|max:2',
                'fecha_inicio' => 'sometimes|date',
                'fecha_fin' => 'sometimes|date|after:fecha_inicio',
            ]);

            $gestion->update($request->only(['anio', 'periodo', 'fecha_inicio', 'fecha_fin']));

            return response()->json([
                'idgestion' => $gestion->id,
                'anio' => $gestion->anio,
                'periodo' => $gestion->periodo,
                'fecha_inicio' => $gestion->fecha_inicio->format('Y-m-d'),
                'fecha_fin' => $gestion->fecha_fin->format('Y-m-d'),
                'nombre_completo' => $gestion->nombreCompleto,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al actualizar gestión: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar gestión
     */
    public function destroy($id): JsonResponse
    {
        try {
            $gestion = Gestion::findOrFail($id);
            $gestion->delete();

            return response()->json(['message' => 'Gestión eliminada correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar gestión: ' . $e->getMessage()], 500);
        }
    }
}
