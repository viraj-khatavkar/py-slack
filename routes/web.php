<?php

use App\Http\Controllers\Api\MessageChildrenController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/channels/{channel}', [ChannelController::class, 'index'])->name('channels.index');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
Route::get('/api/messages/{message}', [MessageController::class, 'show'])->name('api.messages.show');
Route::get('/api/messages/{message}/children', [MessageChildrenController::class, 'index'])->name('api.message-children');
Route::get('/api/users/search', [UserSearchController::class, 'index'])->name('api.users.search');
