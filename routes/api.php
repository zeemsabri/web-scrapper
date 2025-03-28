<?php
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\InvoiceController; 
use App\Http\Controllers\XeroAuthController;

Route::post('/get-token', [TokenController::class, 'generateToken']);
Route::get('/verify-token', [TokenController::class, 'verifyToken']);

Route::middleware(\App\Http\Middleware\JWTMiddleware::class)->group(function () {
   Route::post('/products', [ProductController::class, 'store']); 
   Route::post('/add_job', [OrganizationController::class, 'store']);
   Route::post('/job_exists', [OrganizationController::class, 'job_exists']);
   Route::post('/push_invoice', [InvoiceController::class, 'push_invoice']);
   
}); 

Route::get('/xero/auth', [XeroAuthController::class, 'redirectToXero']);  
Route::get('/xero/callback', [XeroAuthController::class, 'handleCallback']); 
Route::get('/xero/refresh-token', [XeroAuthController::class, 'refreshToken']); 
Route::get('/xero/contacts', [XeroAuthController::class, 'getContacts']);  