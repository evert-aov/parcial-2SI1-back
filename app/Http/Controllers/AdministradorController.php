<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Support\Facades\DB;

class AdministradorController extends Controller
{
    /**
     * Obtener todos los administradores
     */
    public function index(): JsonResponse
    {
        try {
            $administradores = Administrador::with('usuario.role')->get()->map(function ($admin) {
                return [
                    'codadministrador' => $admin->id,
                    'userid' => $admin->usuario->id,
                    'nombre' => $admin->usuario->nombre,
                    'apellido' => $admin->usuario->apellido,
                    'correo' => $admin->usuario->correo,
                    'ci' => $admin->usuario->ci,
                    'telefono' => $admin->usuario->telefono,
                    'sexo' => $admin->usuario->sexo,
                    'direccion' => $admin->usuario->direccion,
                    'activo' => $admin->usuario->activo,
                    'idrol' => $admin->usuario->rol_id,
                    'rol' => $admin->usuario->role->nombre,
                    'fecha_contrato' => $admin->fecha_contrato->format('Y-m-d'),
                ];
            });

            return response()->json($administradores);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al obtener administradores: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nuevo administrador
     */
    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Validar datos
            $request->validate([
                'nombre' => 'required|string',
                'apellido' => 'required|string',
                'correo' => 'required|email|unique:usuarios,correo',
                'ci' => 'required|string|unique:usuarios,ci',
                'contrasena' => 'required|string|min:6',
                'fecha_contrato' => 'required|date',
            ]);

            // Crear usuario con rol administrador (2)
            $usuario = Usuario::create([
                'rol_id' => 2, // Administrador
                'contrasena' => $request->contrasena,
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'telefono' => $request->telefono,
                'sexo' => $request->sexo,
                'correo' => $request->correo,
                'ci' => $request->ci,
                'direccion' => $request->direccion,
                'activo' => true,
            ]);

            // Crear administrador
            $administrador = Administrador::create([
                'usuario_id' => $usuario->id,
                'fecha_contrato' => $request->fecha_contrato,
            ]);

            DB::commit();

            return response()->json([
                'user_id' => $usuario->id,
                'cod_administrador' => $administrador->id,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al crear administrador: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener administrador específico
     */
    public function show($id): JsonResponse
    {
        try {
            $admin = Administrador::with('usuario.role')->findOrFail($id);

            return response()->json([
                'codadministrador' => $admin->id,
                'userid' => $admin->usuario->id,
                'nombre' => $admin->usuario->nombre,
                'apellido' => $admin->usuario->apellido,
                'correo' => $admin->usuario->correo,
                'ci' => $admin->usuario->ci,
                'telefono' => $admin->usuario->telefono,
                'sexo' => $admin->usuario->sexo,
                'direccion' => $admin->usuario->direccion,
                'activo' => $admin->usuario->activo,
                'idrol' => $admin->usuario->rol_id,
                'rol' => $admin->usuario->role->nombre,
                'fecha_contrato' => $admin->fecha_contrato->format('Y-m-d'),
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Administrador no encontrado'], 404);
        }
    }

    /**
     * Actualizar administrador
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $admin = Administrador::with('usuario')->findOrFail($id);

            // Actualizar usuario
            $admin->usuario->update([
                'nombre' => $request->nombre ?? $admin->usuario->nombre,
                'apellido' => $request->apellido ?? $admin->usuario->apellido,
                'telefono' => $request->telefono,
                'sexo' => $request->sexo,
                'direccion' => $request->direccion,
                'activo' => $request->activo ?? true,
            ]);

            // Actualizar administrador
            $admin->update([
                'fecha_contrato' => $request->fecha_contrato ?? $admin->fecha_contrato,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Administrador actualizado correctamente',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al actualizar administrador: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar administrador (desactivar)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $admin = Administrador::with('usuario')->findOrFail($id);

            // Desactivar usuario en lugar de eliminar
            $admin->usuario->update(['activo' => false]);

            return response()->json(['message' => 'Administrador desactivado correctamente']);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al eliminar administrador: ' . $e->getMessage()], 500);
        }
    }
}
