<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenController extends Controller
{


    private $secret_key;
    public function __construct()
    {
        $this->secret_key = env('JWT_SECRET', 'default_secret');
    }

    public function generateToken(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|string',
            'client_secret' => 'required|string'
        ]);

  
        $clients = [
            'estimateone_client' => '2Yd+zK$#8TqNp!rVh@F3eWxJ7B^C&GmL',
        ];


        if (!isset($clients[$validated['client_id']]) || $clients[$validated['client_id']] !== $validated['client_secret']) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }


        $payload = [
            'iss' => "Laravel API",
            'sub' => $validated['client_id'],
            'iat' => now()->timestamp,
            'exp' => now()->addDays(7)->timestamp
        ];

        $token = JWT::encode($payload, $this->secret_key, 'HS256');

        return response()->json(['token' => $token]);
    }

    public function verifyToken(Request $request)
    {
        $authHeader = $request->header('Authorization');
    
        if (!$authHeader) {
            return response()->json(['message' => 'Token required'], 401);
        }
    
        $token = str_replace('Bearer ', '', $authHeader);
    
        try {
            $decoded = JWT::decode($token, new Key($this->secret_key, 'HS256'));
            return response()->json(['message' => 'Token is valid', 'data' => $decoded]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid Token', 'error' => $e->getMessage()], 401);
        }
    }
    
}
