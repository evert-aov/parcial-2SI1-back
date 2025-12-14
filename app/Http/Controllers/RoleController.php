<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class RoleController extends Controller
{
    /**
     * Listar todos los roles
     */
    public function index(): JsonResponse
    {
        try {
            $roles = Role::all()->map(function ($role) {
                return [
                    'idrol' => $role->id,
                    'nombre' => $role->nombre,
                ];
            });

            return response()->json($roles);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener roles: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener rol específico
     */
    public function show($id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            return response()->json([
                'idrol' => $role->id,
                'nombre' => $role->nombre,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }
    }

    /**
     * Crear nuevo rol
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'nombre' => 'required|string|unique:roles,nombre',
            ]);

            $role = Role::create([
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'idrol' => $role->id,
                'nombre' => $role->nombre,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al crear rol: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar rol
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            $request->validate([
                'nombre' => 'required|string|unique:roles,nombre,' . $id,
            ]);

            $role->update([
                'nombre' => $request->nombre,
            ]);

            return response()->json([
                'idrol' => $role->id,
                'nombre' => $role->nombre,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al actualizar rol: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar rol
     */
    public function destroy($id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();

            return response()->json(['message' => 'Rol eliminado correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar rol: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener permisos de un rol
     */
    public function getPermisos($id): JsonResponse
    {
        try {
            $role = Role::with('permisos')->findOrFail($id);

            $permisos = $role->permisos->map(function ($permiso) {
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
     * Asignar permiso a rol
     */
    public function asignarPermiso(Request $request, $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            $request->validate([
                'idpermiso' => 'required|exists:permisos,id',
            ]);

            $role->assignPermission($request->idpermiso);

            return response()->json(['message' => 'Permiso asignado correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al asignar permiso: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remover permiso de rol
     */
    public function removerPermiso($idRol, $idPermiso): JsonResponse
    {
        try {
            $role = Role::findOrFail($idRol);
            $role->removePermission($idPermiso);

            return response()->json(['message' => 'Permiso removido correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al remover permiso: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sincronizar permisos (reemplazar todos)
     */
    public function sincronizarPermisos(Request $request, $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            $request->validate([
                'permisos' => 'required|array',
                'permisos.*' => 'exists:permisos,id',
            ]);

            $role->syncPermissions($request->permisos);

            return response()->json(['message' => 'Permisos sincronizados correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al sincronizar permisos: ' . $e->getMessage()], 500);
        }
    }
}
