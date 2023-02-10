<?php

use App\Http\Controllers\PostsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostsController::class, 'index'])->name('index');
Route::get('/posts/{id}', [PostsController::class, 'show'])->name('view-post');
Route::post('/posts/receive-email-response', [PostsController::class, 'receiveEmailResponse']);
Route::get('send-email', [PostsController::class, 'sendMails']);
