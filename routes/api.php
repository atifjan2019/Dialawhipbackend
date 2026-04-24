<?php

use App\Http\Controllers\Api\V1\Admin\AdminCustomerController;
use App\Http\Controllers\Api\V1\Admin\AdminDriverController;
use App\Http\Controllers\Api\V1\Admin\AdminOrderController;
use App\Http\Controllers\Api\V1\Admin\AdminProductController;
use App\Http\Controllers\Api\V1\Admin\AdminReportController;
use App\Http\Controllers\Api\V1\Admin\AdminServiceAreaController;
use App\Http\Controllers\Api\V1\Admin\AdminSettingsController;
use App\Http\Controllers\Api\V1\Admin\AdminVerificationController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Customer\AddressController;
use App\Http\Controllers\Api\V1\Customer\CheckoutController;
use App\Http\Controllers\Api\V1\Customer\IdVerificationController;
use App\Http\Controllers\Api\V1\Customer\OrderController;
use App\Http\Controllers\Api\V1\Driver\DeliveryController;
use App\Http\Controllers\Api\V1\Shop\CatalogController;
use App\Http\Controllers\Api\V1\Shop\PricingController;
use App\Http\Controllers\Api\V1\Shop\PublicSettingsController;
use App\Http\Controllers\Api\V1\Shop\ServiceAreaController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhooks — no auth, outside v1 prefix per master prompt §2.5
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks')->group(function () {
    Route::post('stripe', [StripeWebhookController::class, 'handle'])->name('webhooks.stripe');
});

/*
|--------------------------------------------------------------------------
| v1 API
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware(['throttle:api'])->group(function () {

    // Public
    Route::get('categories', [CatalogController::class, 'categories']);
    Route::get('products', [CatalogController::class, 'products']);
    Route::get('products/{product}', [CatalogController::class, 'product']);
    Route::get('service-areas/check', [ServiceAreaController::class, 'check']);
    Route::get('settings/public', [PublicSettingsController::class, 'index']);
    Route::post('checkout/preview', [PricingController::class, 'preview']);

    // Auth
    Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
    });

    // Customer (auth + role:customer)
    Route::middleware(['auth:sanctum', 'role:customer', 'idempotent'])->group(function () {
        Route::get('me/orders', [OrderController::class, 'index']);
        Route::get('me/orders/{order}', [OrderController::class, 'show']);

        Route::get('me/addresses', [AddressController::class, 'index']);
        Route::post('me/addresses', [AddressController::class, 'store']);
        Route::patch('me/addresses/{address}', [AddressController::class, 'update']);
        Route::delete('me/addresses/{address}', [AddressController::class, 'destroy']);

        Route::post('checkout/session', [CheckoutController::class, 'session']);
        Route::get('checkout/confirm/{sessionId}', [CheckoutController::class, 'confirm']);

        Route::get('me/verifications', [IdVerificationController::class, 'index']);
        Route::post('me/verifications', [IdVerificationController::class, 'store']);
    });

    // Admin/Staff
    Route::middleware(['auth:sanctum', 'role:admin,staff', 'idempotent'])->prefix('admin')->group(function () {
        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::get('orders/{order}', [AdminOrderController::class, 'show']);
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'transition']);
        Route::patch('orders/{order}/assign', [AdminOrderController::class, 'assign']);

        Route::get('customers', [AdminCustomerController::class, 'index']);
        Route::get('customers/{customer}', [AdminCustomerController::class, 'show']);

        Route::get('products', [AdminProductController::class, 'index']);
        Route::post('products', [AdminProductController::class, 'store']);
        Route::get('products/{product}', [AdminProductController::class, 'show']);
        Route::patch('products/{product}', [AdminProductController::class, 'update']);
        Route::delete('products/{product}', [AdminProductController::class, 'destroy']);

        Route::get('drivers', [AdminDriverController::class, 'index']);
        Route::post('drivers', [AdminDriverController::class, 'store']);

        Route::get('reports/revenue', [AdminReportController::class, 'revenue']);
        Route::get('reports/top-products', [AdminReportController::class, 'topProducts']);
        Route::get('exports/orders.csv', [AdminReportController::class, 'exportOrders']);

        Route::get('verifications', [AdminVerificationController::class, 'index']);
        Route::get('verifications/{verification}', [AdminVerificationController::class, 'show']);
        Route::get('verifications/{verification}/download', [AdminVerificationController::class, 'download']);
        Route::post('verifications/{verification}/approve', [AdminVerificationController::class, 'approve']);
        Route::post('verifications/{verification}/reject', [AdminVerificationController::class, 'reject']);

        // Website settings — logo, favicon, social, address, phone, SEO, legal, maintenance, etc.
        Route::get('settings', [AdminSettingsController::class, 'index']);
        Route::put('settings', [AdminSettingsController::class, 'update']);
        Route::post('settings/upload', [AdminSettingsController::class, 'upload']);
        Route::patch('settings/{key}', [AdminSettingsController::class, 'updateOne'])->where('key', '[A-Za-z0-9_.\-]+');

        // Delivery charges — postcode-based service areas
        Route::get('service-areas', [AdminServiceAreaController::class, 'index']);
        Route::post('service-areas', [AdminServiceAreaController::class, 'store']);
        Route::post('service-areas/bulk', [AdminServiceAreaController::class, 'bulkUpsert']);
        Route::get('service-areas/{serviceArea}', [AdminServiceAreaController::class, 'show']);
        Route::patch('service-areas/{serviceArea}', [AdminServiceAreaController::class, 'update']);
        Route::delete('service-areas/{serviceArea}', [AdminServiceAreaController::class, 'destroy']);
    });

    // Driver
    Route::middleware(['auth:sanctum', 'role:driver', 'idempotent'])->prefix('driver')->group(function () {
        Route::get('deliveries', [DeliveryController::class, 'index']);
        Route::get('deliveries/{order}', [DeliveryController::class, 'show']);
        Route::patch('deliveries/{order}/status', [DeliveryController::class, 'transition']);
        Route::post('deliveries/{order}/note', [DeliveryController::class, 'appendNote']);
    });
});
