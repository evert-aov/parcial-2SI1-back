<?php

namespace App\Http\Middleware;

use App\Services\JWTService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class JWTMiddleware
{
    private $jwtService;
    
    public function __construct()
    {
        $this->jwtService = new JWTService();
    }
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        try {
            $authHeader = $request->header('Authorization');
            
            if (!$authHeader) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token no proporcionado'
                ], 401);
            }
            
            $token = $this->jwtService->extractTokenFromHeader($authHeader);
            $payload = $this->jwtService->validateToken($token);
            
            // Agregar datos del usuario al request
            $request->merge([
                'auth_user' => $payload,
                'user_id' => $payload['user_id'],
                'user_permissions' => $payload['permissions'] ?? []
            ]);
            
            // Verificar permisos si se especificaron
            if (!empty($permissions)) {
                $userPermissions = $payload['permissions'] ?? [];
                
                $hasPermission = false;
                foreach ($permissions as $permission) {
                    if (in_array($permission, $userPermissions)) {
                        $hasPermission = true;
                        break;
                    }
                }
                
                if (!$hasPermission) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos suficientes'
                    ], 403);
                }
            }
            
            return $next($request);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido: ' . $e->getMessage()
            ], 401);
        }
    }
}
