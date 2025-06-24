<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ShopBackOrderController;

Route::get('/health', [HealthController::class, 'check']);

Route::post('/shopback/orders/create', [ShopBackOrderController::class, 'createDynamicQrOrder']);
Route::post('/shopback/orders/scan', [ShopBackOrderController::class, 'scanConsumerQr']);