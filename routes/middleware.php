<?php
use App\Http\Middleware\JWTMiddleware;

return [
    'jwt.auth' => JWTMiddleware::class,
];
