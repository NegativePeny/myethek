<?php

use App\Http\Controllers\AdminOverviewController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DriverPortalController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\MerchantDashboardController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\UserPortalController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

// Rute Autentikasi (Login, Register, Logout, & Quick Demo Switcher)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');
Route::post('/demo/switch-role', [AuthController::class, 'quickDemoSwitch'])->name('demo.switchRole');
Route::get('/demo/switch', [AuthController::class, 'quickDemoSwitch'])->name('demo.switch');

// Storefront Retail & B2B Routes (Tampilan Mobile PWA)
Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');
Route::post('/set-location', [StorefrontController::class, 'setLocation'])->name('storefront.setLocation');
Route::get('/product/{slug}', [StorefrontController::class, 'productDetail'])->name('storefront.product');

// Keranjang Belanja (Cart - Single Merchant Policy)
Route::get('/cart', [CartController::class, 'index'])->name('storefront.cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('storefront.cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('storefront.cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('storefront.cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('storefront.cart.clear');

// Webhook DOKU (Endpoint Backend Idempotent & Signature Verified - PAY-003, PAY-004)
Route::post('/payments/doku/webhook', [CheckoutController::class, 'dokuWebhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('payments.doku.webhook');

// Checkout & Transaksi Pengiriman (Wajib Login)
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('storefront.checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('storefront.checkout.store');
    Route::get('/order/{order_number}/success', [CheckoutController::class, 'orderSuccess'])->name('storefront.orderSuccess');
    Route::post('/order/{order_number}/simulate-doku', [CheckoutController::class, 'simulateDokuCallback'])->name('storefront.simulateDoku');
});

// Portal Pengguna / Customer B2C & B2B (Tampilan Mobile PWA)
Route::prefix('user')->name('user.')->middleware('auth')->group(function () {
    Route::get('/orders', [UserPortalController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{order_number}', [UserPortalController::class, 'orderDetail'])->name('orders.show');
    Route::post('/orders/{order_number}/cancel', [UserPortalController::class, 'cancelOrder'])->name('orders.cancel');
    Route::post('/orders/{order_number}/reorder', [UserPortalController::class, 'reorder'])->name('orders.reorder');
    Route::get('/profile', [UserPortalController::class, 'profile'])->name('profile');
    Route::post('/profile', [UserPortalController::class, 'updateProfile'])->name('profile.update');
    Route::post('/addresses', [UserPortalController::class, 'storeAddress'])->name('addresses.store');
    Route::delete('/addresses/{id}', [UserPortalController::class, 'deleteAddress'])->name('addresses.destroy');
});

// Portal Mitra Koperasi & Kulakan B2B (Merchant Dashboard)
Route::prefix('merchant')->name('merchant.')->middleware('role:merchant')->group(function () {
    Route::get('/', [MerchantDashboardController::class, 'index'])->name('index');
    Route::get('/orders', [MerchantDashboardController::class, 'orders'])->name('orders');
    Route::get('/finance', [MerchantDashboardController::class, 'finance'])->name('finance');
    Route::get('/profile', [MerchantDashboardController::class, 'profile'])->name('profile');
    Route::post('/profile', [MerchantDashboardController::class, 'updateProfile'])->name('profile.update');
    Route::post('/switch', [MerchantDashboardController::class, 'switchMerchant'])->name('switch');
    Route::post('/toggle-status', [MerchantDashboardController::class, 'toggleStatus'])->name('toggleStatus');
    Route::post('/order/{id}/status', [MerchantDashboardController::class, 'updateOrderStatus'])->name('updateOrderStatus');

    Route::get('/procurement', [MerchantDashboardController::class, 'procurement'])->name('procurement');
    Route::post('/procurement/order', [MerchantDashboardController::class, 'orderProcurement'])->name('orderProcurement');
    Route::post('/procurement/{id}/receive', [MerchantDashboardController::class, 'receiveProcurement'])->name('receiveProcurement');

    Route::get('/inventory', [MerchantDashboardController::class, 'inventory'])->name('inventory');
    Route::post('/inventory/{id}', [MerchantDashboardController::class, 'updateInventory'])->name('updateInventory');
});

// Portal Driver KurirQ Mak Ethek (Tampilan Mobile PWA - DRV-001..010)
Route::prefix('driver')->name('driver.')->middleware('role:driver')->group(function () {
    Route::get('/', [DriverPortalController::class, 'index'])->name('index');
    Route::post('/{delivery_id}/respond', [DriverPortalController::class, 'respondAssignment'])->name('respond');
    Route::post('/{delivery_id}/pickup', [DriverPortalController::class, 'takeJob'])->name('pickup');
    Route::post('/{delivery_id}/complete', [DriverPortalController::class, 'completeJob'])->name('complete');
});

// Portal Admin Gudang / WMS Koperasi Kota Blitar (Tampilan Web Desktop - WH-001..009)
Route::prefix('warehouse')->name('warehouse.')->middleware('role:warehouse,admin,superadmin')->group(function () {
    Route::get('/', [WarehouseController::class, 'index'])->name('index');
    Route::post('/items/{order_item_id}/pick', [WarehouseController::class, 'confirmPickingItem'])->name('pickItem');
    Route::post('/orders/{order_id}/pack', [WarehouseController::class, 'completePacking'])->name('packOrder');
    Route::post('/orders/{order_id}/handover', [WarehouseController::class, 'handoverToDriver'])->name('handover');
    Route::post('/inventory/{inventory_id}/adjust', [WarehouseController::class, 'adjustInventory'])->name('adjustInventory');
});

// Portal Admin Koperasi (Tampilan Web Desktop - ADM-001..008)
Route::prefix('admin')->name('admin.')->middleware('role:admin,superadmin')->group(function () {
    Route::get('/', [AdminOverviewController::class, 'index'])->name('index');
    Route::post('/orders/{order_id}/decision', [AdminOverviewController::class, 'updateOrderDecision'])->name('orders.decision');
    Route::post('/orders/{order_id}/assign-driver', [AdminOverviewController::class, 'assignDriver'])->name('orders.assignDriver');

    Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/products/{id}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{id}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [AdminProductController::class, 'destroy'])->name('products.destroy');
});

// Portal Super Admin & Finance (Tampilan Web Desktop - SA-001..007)
// Hanya boleh diakses oleh role finance dan superadmin (Petugas gudang & driver ditolak 403 - UAT #14)
Route::prefix('finance')->name('finance.')->middleware('role:finance,superadmin,admin')->group(function () {
    Route::get('/', [FinanceController::class, 'index'])->name('index');
    Route::post('/payments/{payment_id}/reconcile', [FinanceController::class, 'reconcileSettlement'])->name('reconcile');
    Route::post('/payments/{payment_id}/refund', [FinanceController::class, 'processRefund'])->name('refund');
    Route::post('/customers/{user_id}/toggle-b2b', [FinanceController::class, 'toggleB2BStatus'])->name('toggleB2B');
    Route::get('/export-csv', [FinanceController::class, 'exportCsv'])->name('exportCsv');
});
Route::get('/superadmin', [FinanceController::class, 'index'])->middleware('role:superadmin,finance,admin')->name('superadmin.index');
