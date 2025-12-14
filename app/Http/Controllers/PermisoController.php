<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class PermisoController extends Controller
{
    /**
     * Listar todos los permisos
     */
    public function index(): JsonResponse
    {
        try {
            $permisos = Permiso::all()->map(function ($permiso) {
                return [
                    'idpermiso' => $permiso->id,
                    'nombre' => $permiso->nombre,
                ];
            });

            return response()->json($permisos);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener permisos: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nuevo permiso
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'nombre' => 'required|string|unique:permisos,nombre',
            ]);

            $permiso = Permiso::create([
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'idpermiso' => $permiso->id,
                'nombre' => $permiso->nombre,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear permiso: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener permiso específico
     */
    public function show($id): JsonResponse
    {
        try {
            $permiso = Permiso::findOrFail($id);

            return response()->json([
                'idpermiso' => $permiso->id,
                'nombre' => $permiso->nombre,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Permiso no encontrado'], 404);
        }
    }

    /**
     * Actualizar permiso
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $permiso = Permiso::findOrFail($id);

            $request->validate([
                'nombre' => 'required|string|unique:permisos,nombre,' . $id,
            ]);

            $permiso->update([
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'idpermiso' => $permiso->id,
                'nombre' => $permiso->nombre,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al actualizar permiso: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar permiso
     */
    public function destroy($id): JsonResponse
    {
        try {
            $permiso = Permiso::findOrFail($id);
            $permiso->delete();

            return response()->json(['message' => 'Permiso eliminado correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar permiso: ' . $e->getMessage()], 500);
        }
    }
}
