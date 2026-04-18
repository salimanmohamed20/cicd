<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

// ─── Public Routes ────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Posts للعرض العام
Route::get('/posts',       [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);

// ─── Protected Routes ─────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::post('/posts',          [PostController::class, 'store']);
    Route::put('/posts/{post}',    [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
});
