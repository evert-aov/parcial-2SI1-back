<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTService
{
    private $key;
    private $algorithm = 'HS256';
    private $expiration = 86400; // 24 horas
    
    public function __construct()
    {
        $this->key = env('APP_KEY', 'your-secret-key');
    }
    
    public function generateToken($payload)
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->expiration;
        
        return JWT::encode($payload, $this->key, $this->algorithm);
    }
    
    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->key, $this->algorithm));
            return (array) $decoded;
        } catch (Exception $e) {
            throw new Exception('Token inválido: ' . $e->getMessage());
        }
    }
    
    public function refreshToken($token)
    {
        $payload = $this->validateToken($token);
        
        // Remover timestamps anteriores
        unset($payload['iat']);
        unset($payload['exp']);
        
        return $this->generateToken($payload);
    }
    
    public function extractTokenFromHeader($authHeader)
    {
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            throw new Exception('Token no proporcionado');
        }
        
        return substr($authHeader, 7);
    }
}