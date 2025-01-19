<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

Route::get('/user', [UserController::class, 'get_user'])->middleware('auth:sanctum');
Route::post('/user/onboard', [UserController::class, 'complete_onboard'])->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'generate_otp']);
Route::post('/otp', [AuthController::class, 'login_with_otp']);

Route::post('/profile/create', [UserController::class, 'create_profile'])->middleware('auth:sanctum');
