<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class HorarioController extends Controller
{
    /**
     * Listar todos los horarios
     */
    public function index(): JsonResponse
    {
        try {
            $horarios = Horario::ordenados()->get()->map(function ($horario) {
                return [
                    'idhorario' => $horario->id,
                    'hora_inicio' => date('H:i', strtotime($horario->hora_inicio)),
                    'hora_fin' => date('H:i', strtotime($horario->hora_fin)),
                    'nombre' => $horario->nombre,
                    'rango' => $horario->rango,
                ];
            });

            return response()->json($horarios);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener horarios: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nuevo horario
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'nombre' => 'nullable|string',
            ]);

            $horario = Horario::create([
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'idhorario' => $horario->id,
                'hora_inicio' => date('H:i', strtotime($horario->hora_inicio)),
                'hora_fin' => date('H:i', strtotime($horario->hora_fin)),
                'nombre' => $horario->nombre,
                'rango' => $horario->rango,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear horario: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener horario específico
     */
    public function show($id): JsonResponse
    {
        try {
            $horario = Horario::findOrFail($id);

            return response()->json([
                'idhorario' => $horario->id,
                'hora_inicio' => date('H:i', strtotime($horario->hora_inicio)),
                'hora_fin' => date('H:i', strtotime($horario->hora_fin)),
                'nombre' => $horario->nombre,
                'rango' => $horario->rango,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Horario no encontrado'], 404);
        }
    }

    /**
     * Actualizar horario
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $horario = Horario::findOrFail($id);

            $request->validate([
                'hora_inicio' => 'sometimes|date_format:H:i',
                'hora_fin' => 'sometimes|date_format:H:i|after:hora_inicio',
                'nombre' => 'nullable|string',
            ]);

            $horario->update($request->only(['hora_inicio', 'hora_fin', 'nombre']));

            return response()->json([
                'idhorario' => $horario->id,
                'hora_inicio' => date('H:i', strtotime($horario->hora_inicio)),
                'hora_fin' => date('H:i', strtotime($horario->hora_fin)),
                'nombre' => $horario->nombre,
                'rango' => $horario->rango,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al actualizar horario: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar horario
     */
    public function destroy($id): JsonResponse
    {
        try {
            $horario = Horario::findOrFail($id);
            $horario->delete();

            return response()->json(['message' => 'Horario eliminado correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar horario: ' . $e->getMessage()], 500);
        }
    }
}
