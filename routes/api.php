<?php
use App\Http\Controllers\Api\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\LogoutController;
use App\Http\Controllers\Api\ImageUploadController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\BlogManageController;
use App\Http\Controllers\Api\PublicBlogController;

Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

Route::get('/profile', [ProfileController::class, 'show']);
Route::put('/profile', [ProfileController::class, 'update']);
Route::post('/logout', [LogoutController::class, 'logout']);
Route::post('/upload-image', [ImageUploadController::class, 'upload']);
Route::post('/blogs/create', [BlogController::class, 'store']);
Route::get('/my-blogs', [BlogController::class, 'index']);
Route::get('/blogs/{postid}', [BlogController::class, 'show']);
Route::put('/blogs/{postid}', [BlogManageController::class, 'update']);
Route::delete('/blogs/{postid}', [BlogManageController::class, 'destroy']);

});

Route::get('/public-blogs', [PublicBlogController::class, 'index']);
Route::get('/public-blogs/{postid}', [PublicBlogController::class, 'show']);

