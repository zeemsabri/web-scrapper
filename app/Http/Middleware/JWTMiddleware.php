<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $secret_key = env('JWT_SECRET', '12345678'); 
        if ($request->is('api/get-token')) {
            return $next($request);
        }
        $token = $request->header('Authorization');

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return response()->json(['message' => 'Token required'], 401);
        }

        $token = str_replace('Bearer ', '', $token); 

        try {
            $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));

 
            $request->attributes->add(['decoded_token' => $decoded]);

            return $next($request);
        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json(['message' => 'Token expired'], 401);
        } catch (Exception $e) {
            return response()->json(['message' => 'Invalid Token', 'error' => $e->getMessage()], 401);
        }
    }
}
