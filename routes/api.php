<?php

use App\Http\Controllers\Api\VendorBillSyncController;
use Illuminate\Support\Facades\Route;

Route::post('/vendor-bills/sync', [VendorBillSyncController::class, 'store']);
Route::get('/vendor-bills/{orderId}/{billType}', [VendorBillSyncController::class, 'show']);
