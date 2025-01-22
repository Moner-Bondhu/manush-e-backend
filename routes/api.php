<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ResponseController;
use App\Http\Controllers\Api\ScaleController;
use App\Http\Controllers\Api\UserController;

Route::get('/user', [UserController::class, 'get_user'])->middleware('auth:sanctum');
Route::post('/user/onboard', [UserController::class, 'complete_onboard'])->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'generate_otp']);
Route::post('/otp', [AuthController::class, 'login_with_otp']);

Route::post('/profile/create', [UserController::class, 'create_profile'])->middleware('auth:sanctum');

Route::get('/scales/{profile}', [ScaleController::class, 'index'])->middleware('auth:sanctum');
Route::get('/scale/{scale_id}', [ScaleController::class, 'show'])->middleware('auth:sanctum');
Route::post('/question/respond/{question_id}', [ResponseController::class, 'store'])->middleware('auth:sanctum');
