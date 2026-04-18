<?php

use App\Http\Controllers\API\V1\ApiClientController;
use App\Http\Controllers\API\V1\AuditLogController;
use App\Http\Controllers\API\V1\CustomerController;
use App\Http\Controllers\API\V1\DocumentCancelController;
use App\Http\Controllers\API\V1\DocumentEmitController;
use App\Http\Controllers\API\V1\DocumentIndexController;
use App\Http\Controllers\API\V1\DocumentShowController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\TenantController;
use App\Http\Middleware\AuthenticateFiscalApi;
use App\Http\Middleware\EnsureFiscalRequestContext;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware([EnsureFiscalRequestContext::class, AuthenticateFiscalApi::class])->group(function () {
    Route::get('documents', DocumentIndexController::class);
    Route::post('documents/emit', [DocumentEmitController::class, 'store']);
    Route::post('documents/cancel', [DocumentCancelController::class, 'store']);
    Route::get('documents/{id}', [DocumentShowController::class, 'show'])->whereNumber('id');

    Route::get('audit-logs', [AuditLogController::class, 'index']);
    Route::get('audit-logs/{audit_log}', [AuditLogController::class, 'show']);

    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('tenants', TenantController::class);
    Route::apiResource('api-clients', ApiClientController::class)->except(['destroy']);
});
