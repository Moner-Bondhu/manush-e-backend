<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\DevelopmentProfileController;
use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\ScenarioCoachController;
use App\Http\Controllers\Api\ExperienceBlueprintController;
use App\Http\Controllers\Api\ResponseController;
use App\Http\Controllers\Api\ScaleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileSetupController;

Route::get('/user', [UserController::class, 'get_user'])->middleware('auth:sanctum');
Route::post('/user/onboard', [UserController::class, 'complete_onboard'])->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'generate_otp']);
Route::post('/otp', [AuthController::class, 'login_with_otp']);

Route::post('/profile/create', [UserController::class, 'create_profile'])->middleware('auth:sanctum');
Route::get('/profile-setup/{role}', [ProfileSetupController::class, 'show'])->middleware('auth:sanctum');
Route::put('/profile-setup/{role}', [ProfileSetupController::class, 'update'])->middleware('auth:sanctum');

Route::get('/scales/{profile}', [ScaleController::class, 'index'])->middleware('auth:sanctum');
Route::get('/assessment-library', [ScaleController::class, 'library'])->middleware('auth:sanctum');
Route::get('/scale/{scale_id}', [ScaleController::class, 'show'])->middleware('auth:sanctum');
Route::post('/question/respond/{question_id}', [ResponseController::class, 'store'])->middleware('auth:sanctum');

Route::get('/games', [GameController::class, 'index'])->middleware('auth:sanctum');
Route::post('/games/{game}/sessions', [GameController::class, 'start'])->middleware('auth:sanctum');
Route::post('/game-sessions/{gameSession}/events', [GameController::class, 'recordEvents'])->middleware('auth:sanctum');
Route::post('/game-sessions/{gameSession}/complete', [GameController::class, 'complete'])->middleware('auth:sanctum');
Route::get('/profiles/{profile}/development', [DevelopmentProfileController::class, 'show'])->middleware('auth:sanctum');
Route::get('/portal/parent', [PortalController::class, 'parentOverview'])->middleware('auth:sanctum');
Route::get('/portal/teacher', [PortalController::class, 'teacherOverview'])->middleware('auth:sanctum');
Route::post('/scenario-coach/respond', [ScenarioCoachController::class, 'respond'])->middleware('auth:sanctum');
Route::get('/experience-blueprints', [ExperienceBlueprintController::class, 'index'])->middleware('auth:sanctum');
Route::post('/experience-blueprints', [ExperienceBlueprintController::class, 'store'])->middleware('auth:sanctum');
Route::post('/experience-blueprints/assist', [ExperienceBlueprintController::class, 'assist'])->middleware('auth:sanctum');
Route::post('/experience-blueprints/{experienceBlueprint}/publish', [ExperienceBlueprintController::class, 'publish'])->middleware('auth:sanctum');
