<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::get('/articles', [ArticleController::class, 'allArticles']);

// Routes protégées par le middleware JWT
Route::middleware(['jwt.auth'])->group(function () {
    // Authentification et gestion du profil
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::put('update-profile', [AuthController::class, 'updateProfile']);
    Route::delete('delete-account', [AuthController::class, 'deleteAccount']);
    Route::post('verify-token', [AuthController::class, 'verifyToken']);

    // Gestion de l'image de profil
    Route::post('update-profile-img', [AuthController::class, 'updateProfileImg']);
    Route::get('profile-image', [AuthController::class, 'getProfileImage']);
    Route::delete('delete-profile-image', [AuthController::class, 'deleteProfileImage']);

    // Gestion des articles
    Route::post('/articles', [ArticleController::class, 'createArticle']);
    Route::get('/user-articles', [ArticleController::class, 'getUserArticles']);
    Route::get('/articles/{id}', [ArticleController::class, 'getArticle']);
    Route::put('/articles/{id}', [ArticleController::class, 'updateArticle']);
    Route::delete('/articles/{id}', [ArticleController::class, 'deleteArticle']);

    // Gestion des commentaires
    Route::post('/comment/{id}', [CommentController::class, 'comment']);
    Route::get('/comments/{id}', [CommentController::class, 'getComments']);
});
