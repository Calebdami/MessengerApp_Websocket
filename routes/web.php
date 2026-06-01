<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Redirection racine
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('conversations.index')
        : redirect()->route('login');
});
// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Conversations
    Route::get('/',                                     [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations',                        [ConversationController::class, 'index']);
    Route::post('/conversations',                       [ConversationController::class, 'store'])->name('conversations.store');
    Route::post('/conversations/group',                 [ConversationController::class, 'storeGroup'])->name('conversations.group');
    Route::get('/conversations/{conversation}',         [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/archive',[ConversationController::class, 'archive'])->name('conversations.archive');
    Route::post('/conversations/{conversation}/pin',    [ConversationController::class, 'pin'])->name('conversations.pin');
    Route::post('/conversations/{conversation}/mute',   [ConversationController::class, 'mute'])->name('conversations.mute');

    // Messages
    Route::post('/conversations/{conversation}/messages',        [MessageController::class, 'store'])->name('messages.store');
    Route::get('/conversations/{conversation}/messages/more',    [MessageController::class, 'loadMore'])->name('messages.more');
    Route::post('/conversations/{conversation}/messages/read',   [MessageController::class, 'markRead'])->name('messages.read');
    Route::post('/conversations/{conversation}/typing',          [MessageController::class, 'typing'])->name('messages.typing');
    Route::post('/messages/{message}/react',  [MessageController::class, 'react'])->name('messages.react');
    Route::put('/messages/{message}',         [MessageController::class, 'edit'])->name('messages.edit');
    Route::delete('/messages/{message}',      [MessageController::class, 'destroy'])->name('messages.destroy');

    // Appels
    Route::post('/conversations/{conversation}/call', [CallController::class, 'initiate'])->name('calls.initiate');
    Route::post('/calls/{uuid}/signal',               [CallController::class, 'signal'])->name('calls.signal');

    // Utilisateurs
    Route::get('/users/search',                    [UserController::class, 'search'])->name('users.search');
    Route::post('/users/status',                   [UserController::class, 'updateStatus'])->name('users.status');
    Route::get('/settings',                        [UserController::class, 'settings'])->name('settings');
    Route::post('/settings/profile',               [UserController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password',              [UserController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/preferences',           [UserController::class, 'updatePreferences'])->name('settings.preferences');
    Route::get('/notifications',                   [UserController::class, 'notifications'])->name('notifications');
});
