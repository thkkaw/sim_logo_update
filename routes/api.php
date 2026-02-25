<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\SimLogoController;

Route::post('/simlogo', [SimLogoController::class, 'index']);
Route::get('/simlogo/report', [ReportController::class, 'download']);
