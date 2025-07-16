<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlogPostController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\ContactController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);

Route::get('/tags', [TagController::class, 'index']);
Route::get('/tags/{slug}', [TagController::class, 'show']);

// Rutas públicas de contacto
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/contact/captcha', [ContactController::class, 'generateCaptcha']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Rutas admin para posts (con prefijo para evitar conflictos)
    Route::get('/admin/posts/{id}', [BlogPostController::class, 'showById'])->where('id', '[0-9]+');
    Route::get('/admin/posts', [BlogPostController::class, 'index']);
    Route::post('/posts', [BlogPostController::class, 'store']);
    Route::put('/posts/{blogPost}', [BlogPostController::class, 'update']);
    Route::delete('/posts/{blogPost}', [BlogPostController::class, 'destroy']);

    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('/tags', [TagController::class, 'store']);
    Route::put('/tags/{tag}', [TagController::class, 'update']);
    Route::delete('/tags/{tag}', [TagController::class, 'destroy']);

    Route::get('/images', [ImageController::class, 'index']);
    Route::post('/images', [ImageController::class, 'store']);
    Route::post('/images/upload', [ImageController::class, 'store']);
    Route::post('/images/editor-upload', [ImageController::class, 'uploadForEditor']);
    Route::post('/images/cleanup', [ImageController::class, 'cleanupUnused']);
    Route::delete('/images/{image}', [ImageController::class, 'destroy']);

    // Rutas admin de contacto
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::get('/contacts/stats', [ContactController::class, 'getStats']);
    Route::get('/contacts/{contact}', [ContactController::class, 'show']);
    Route::patch('/contacts/{contact}/read', [ContactController::class, 'markAsRead']);
    Route::patch('/contacts/{contact}/unread', [ContactController::class, 'markAsUnread']);
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy']);
});

// Rutas públicas para posts (solo posts publicados) - van al final
Route::get('/posts', [BlogPostController::class, 'index']);
Route::get('/posts/{slug}', [BlogPostController::class, 'show']);
