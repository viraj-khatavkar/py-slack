<?php

use App\Http\Controllers\Api\MessageChildrenController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('channels.index', 'momentum'));
Route::get('/channels/{channel}', [ChannelController::class, 'index'])->name('channels.index');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
Route::get('/api/messages/{message}/children', [MessageChildrenController::class, 'index'])->name('api.message-children');
