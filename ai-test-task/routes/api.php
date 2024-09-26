<?php

use App\Http\Controllers\GptController;
use Illuminate\Support\Facades\Route;

Route::post('/query', [GptController::class, 'query']);
Route::post('/test', [GptController::class, 'handleQuery']);
Route::get('/example', function () {
    return response()->json(['message' => 'Hello, World!']);
});