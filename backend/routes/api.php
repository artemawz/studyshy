<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BlockController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\FriendController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\GroupMessageController;
use App\Http\Controllers\Api\MetaController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/students', [StudentController::class, 'index']);
Route::get('/students/{id}', [StudentController::class, 'show']);
Route::get('/filters', [MetaController::class, 'filters']);
Route::get('/stats', [MetaController::class, 'stats']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/users/me', [UserController::class, 'update']);
    Route::post('/users/me/avatar', [UserController::class, 'uploadAvatar']);

    Route::get('/friends', [FriendController::class, 'index']);
    Route::get('/friends/status/{userId}', [FriendController::class, 'status']);
    Route::post('/friends/request', [FriendController::class, 'request']);
    Route::post('/friends/{friendship}/accept', [FriendController::class, 'accept']);
    Route::post('/friends/{friendship}/decline', [FriendController::class, 'decline']);
    Route::delete('/friends/{friendship}', [FriendController::class, 'destroy']);

    Route::get('/chats', [ChatController::class, 'index']);
    Route::post('/chats', [ChatController::class, 'store']);
    Route::delete('/chats/{chat}', [ChatController::class, 'destroy']);
    Route::post('/chats/{chat}/accept', [ChatController::class, 'accept']);
    Route::post('/chats/{chat}/decline', [ChatController::class, 'decline']);
    Route::get('/chats/{chat}/messages', [ChatController::class, 'messages']);
    Route::get('/chats/{chat}/sync', [ChatController::class, 'sync']);
    Route::post('/chats/{chat}/typing', [ChatController::class, 'typing']);
    Route::post('/chats/{chat}/messages', [ChatController::class, 'sendMessage']);
    Route::patch('/chats/{chat}/messages/{message}', [ChatController::class, 'editMessage']);
    Route::delete('/chats/{chat}/messages/{message}', [ChatController::class, 'deleteMessage']);

    // Blockieren & Melden
    Route::get('/blocks', [BlockController::class, 'index']);
    Route::post('/blocks', [BlockController::class, 'store']);
    Route::delete('/blocks/{user}', [BlockController::class, 'destroy']);
    Route::post('/reports', [ReportController::class, 'store']);

    // Lerngruppen
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/groups', [GroupController::class, 'store']);
    Route::get('/groups/{group}', [GroupController::class, 'show']);
    Route::delete('/groups/{group}', [GroupController::class, 'destroy']);
    Route::post('/groups/{group}/join', [GroupController::class, 'join']);
    Route::post('/groups/{group}/leave', [GroupController::class, 'leave']);
    Route::get('/groups/{group}/messages', [GroupMessageController::class, 'index']);
    Route::get('/groups/{group}/sync', [GroupMessageController::class, 'sync']);
    Route::post('/groups/{group}/messages', [GroupMessageController::class, 'store']);
    Route::patch('/groups/{group}/messages/{message}', [GroupMessageController::class, 'update']);
    Route::delete('/groups/{group}/messages/{message}', [GroupMessageController::class, 'destroy']);

    // Schwarzes Brett / Pinnwand
    Route::get('/board', [BoardController::class, 'index']);
    Route::post('/board', [BoardController::class, 'store']);
    Route::delete('/board/{post}', [BoardController::class, 'destroy']);
    Route::get('/board/{post}/comments', [BoardController::class, 'comments']);
    Route::post('/board/{post}/comments', [BoardController::class, 'storeComment']);
    Route::delete('/board/comments/{comment}', [BoardController::class, 'destroyComment']);

    // Events / Treffen
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
    Route::post('/events/{event}/join', [EventController::class, 'join']);
    Route::post('/events/{event}/leave', [EventController::class, 'leave']);
});
