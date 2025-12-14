<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JWTService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class AuthController extends Controller
{
    private $userModel;
    private $jwtService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->jwtService = new JWTService();
    }

    /**
     * Login de usuario
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $email = $request->input('email');
            $password = $request->input('password');

            // Validar campos requeridos
            if (!$email || !$password) {
                return response()->json(['message' => 'Email y contraseña son requeridos'], 400);
            }

            // Buscar usuario por email con relaciones
            $user = User::where('correo', $email)
                ->where('activo', true)
                ->with(['role.permisos'])
                ->first();

            if (!$user) {
                return response()->json(['message' => 'No existe una cuenta con este email'], 401);
            }

            // Verificar contraseña
            if (!$user->verifyPassword($password)) {
                return response()->json(['message' => 'Contraseña incorrecta'], 401);
            }

            // Obtener permisos del usuario
            $permissions = $user->role->permisos->pluck('nombre')->toArray();

            // Generar JWT
            $payload = [
                'user_id' => $user->id,
                'email' => $user->correo,
                'rol' => $user->role->nombre,
                'permissions' => $permissions
            ];

            $token = $this->jwtService->generateToken($payload);

            // Actualizar último login (updated_at)
            $user->touch();

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'apellido' => $user->apellido,
                    'email' => $user->correo,
                    'rol' => $user->role->nombre,
                    'permissions' => $permissions
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error en el proceso de autenticación: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Registro de usuario
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $data = $request->all();

            // Validar campos requeridos
            $required = ['nombre', 'apellido', 'correo', 'ci', 'contrasena', 'rol_id'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return response()->json(['message' => "El campo {$field} es requerido"], 400);
                }
            }

            // Validar formato de email
            if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                return response()->json(['message' => 'El formato del email no es válido'], 400);
            }

            // Validar email único
            if (User::existsByEmail($data['correo'])) {
                return response()->json(['message' => 'Ya existe una cuenta registrada con este email'], 400);
            }

            // Validar CI único
            if (User::existsByCI($data['ci'])) {
                return response()->json(['message' => 'Ya existe una cuenta registrada con esta cédula de identidad'], 400);
            }

            // Validar longitud de contraseña
            if (strlen($data['contrasena']) < 6) {
                return response()->json(['message' => 'La contraseña debe tener al menos 6 caracteres'], 400);
            }

            // Crear usuario (el password se hashea automáticamente en el mutator)
            $user = User::create([
                'rol_id' => $data['rol_id'],
                'contrasena' => $data['contrasena'],
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'telefono' => $data['telefono'] ?? null,
                'sexo' => $data['sexo'] ?? null,
                'correo' => $data['correo'],
                'ci' => $data['ci'],
                'direccion' => $data['direccion'] ?? null,
                'activo' => true,
            ]);

            return response()->json(['user_id' => $user->id], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error al registrar usuario: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Validar token
     */
    public function validateToken(Request $request): JsonResponse
    {
        try {
            $authHeader = $request->header('Authorization');
            $token = $this->jwtService->extractTokenFromHeader($authHeader);
            $payload = $this->jwtService->validateToken($token);

            return response()->json($payload);
        } catch (Exception $e) {
            return response()->noContent(401);
        }
    }

    /**
     * Refrescar token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $authHeader = $request->header('Authorization');
            $token = $this->jwtService->extractTokenFromHeader($authHeader);
            $newToken = $this->jwtService->refreshToken($token);

            return response()->json(['token' => $newToken]);
        } catch (Exception $e) {
            return response()->noContent(401);
        }
    }

    /**
     * Logout (invalidar token del lado cliente)
     */
    public function logout(Request $request): JsonResponse
    {
        return response()->noContent();
    }
}
