<?php

use App\Http\Controllers\AdminOverviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DriverPortalController;
use App\Http\Controllers\MerchantDashboardController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\UserPortalController;
use Illuminate\Support\Facades\Route;

// Rute Autentikasi (Login, Register, Logout)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Storefront Retail Routes (Mobile-first Groseri)
Route::get('/', [StorefrontController::class, 'index'])->name('storefront.index');
Route::post('/set-location', [StorefrontController::class, 'setLocation'])->name('storefront.setLocation');
Route::get('/product/{slug}', [StorefrontController::class, 'productDetail'])->name('storefront.product');

// Keranjang Belanja (Cart)
Route::get('/cart', [CartController::class, 'index'])->name('storefront.cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('storefront.cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('storefront.cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('storefront.cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('storefront.cart.clear');

// Checkout & Transaksi Pengiriman (Wajib Login)
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('storefront.checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('storefront.checkout.store');
    Route::get('/order/{order_number}/success', [CheckoutController::class, 'orderSuccess'])->name('storefront.orderSuccess');
});

// Portal Pengguna / Warga Belanja (Riwayat Pesanan, Detail, & Profil)
Route::prefix('user')->name('user.')->middleware('auth')->group(function () {
    Route::get('/orders', [UserPortalController::class, 'orders'])->name('orders.index');
    Route::get('/orders/{order_number}', [UserPortalController::class, 'orderDetail'])->name('orders.show');
    Route::post('/orders/{order_number}/cancel', [UserPortalController::class, 'cancelOrder'])->name('orders.cancel');
    Route::post('/orders/{order_number}/reorder', [UserPortalController::class, 'reorder'])->name('orders.reorder');
    Route::get('/profile', [UserPortalController::class, 'profile'])->name('profile');
    Route::post('/profile', [UserPortalController::class, 'updateProfile'])->name('profile.update');
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

    // Modul Kulakan B2B Berjenjang
    Route::get('/procurement', [MerchantDashboardController::class, 'procurement'])->name('procurement');
    Route::post('/procurement/order', [MerchantDashboardController::class, 'orderProcurement'])->name('orderProcurement');
    Route::post('/procurement/{id}/receive', [MerchantDashboardController::class, 'receiveProcurement'])->name('receiveProcurement');

    // Manajemen Inventori & Harga Eceran Toko
    Route::get('/inventory', [MerchantDashboardController::class, 'inventory'])->name('inventory');
    Route::post('/inventory/{id}', [MerchantDashboardController::class, 'updateInventory'])->name('updateInventory');
});

// Portal Kurir Ojol Lokal (Driver Logistics)
Route::prefix('driver')->name('driver.')->group(function () {
    Route::get('/', [DriverPortalController::class, 'index'])->name('index');
    Route::post('/{delivery_id}/pickup', [DriverPortalController::class, 'takeJob'])->name('pickup');
    Route::post('/{delivery_id}/complete', [DriverPortalController::class, 'completeJob'])->name('complete');
});

// Panel Audit & Rekapitulasi Holding Koperasi Jawa Timur
Route::get('/admin', [AdminOverviewController::class, 'index'])->name('admin.index');
