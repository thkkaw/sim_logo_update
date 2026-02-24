<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\SimLogoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/simlogo', [SimLogoController::class, 'index']);
Route::get('/simlogo/report', [ReportController::class, 'download']);
