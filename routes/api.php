<?php
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\OrganizationController;

Route::post('/get-token', [TokenController::class, 'generateToken']);
Route::get('/verify-token', [TokenController::class, 'verifyToken']);

Route::middleware(\App\Http\Middleware\JWTMiddleware::class)->group(function () {
   Route::post('/products', [ProductController::class, 'store']); 
   Route::post('/add_job', [OrganizationController::class, 'store']);

}); 




