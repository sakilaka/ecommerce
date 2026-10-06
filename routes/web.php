<?php

use App\Http\Controllers\Api\Customer\CustomerAddressController;
use App\Http\Controllers\Api\Customer\CustomerOrderController;
use App\Http\Controllers\Api\Customer\ProductReviewController as CustomerProductReviewController;
use App\Http\Controllers\Api\Customer\ReturnRequestController as CustomerReturnRequestController;
use App\Http\Controllers\Api\Customer\SupportTicketController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use App\Http\Controllers\Api\Storefront\CouponPreviewController;
use App\Http\Controllers\Auth\CustomerAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('products', 'welcome')->name('products.index');
Route::view('products/{slug}', 'welcome')->name('products.show');
Route::view('order-tracking', 'welcome')->name('orders.track');
Route::view('payment/result', 'welcome')->name('payments.result');
Route::view('login', 'welcome')->middleware('guest')->name('customer.login');
Route::view('register', 'welcome')->middleware('guest')->name('customer.register');
Route::view('customer/dashboard', 'welcome')->middleware('auth')->name('customer.dashboard');
Route::view('admin/login', 'welcome')->name('login');
Route::view('admin/dashboard', 'welcome')->middleware(['auth', 'admin'])->name('admin.dashboard');

Route::prefix('storefront')->name('storefront.')->group(function (): void {
    Route::post('coupon-preview', [CouponPreviewController::class, 'store'])->middleware('throttle:commerce-public-action')->name('coupon-preview.store');
    Route::post('checkout', [CheckoutController::class, 'store'])->middleware('throttle:commerce-checkout')->name('checkout.store');
});

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::get('user', [CustomerAuthController::class, 'user'])->name('user');
    Route::post('register', [CustomerAuthController::class, 'register'])->middleware('throttle:commerce-register')->name('register');
    Route::post('login', [CustomerAuthController::class, 'login'])->middleware('throttle:commerce-login')->name('login');
    Route::patch('profile', [CustomerAuthController::class, 'updateProfile'])->middleware('auth')->name('profile.update');
    Route::patch('password', [CustomerAuthController::class, 'updatePassword'])->middleware('auth')->name('password.update');
    Route::post('logout', [CustomerAuthController::class, 'logout'])->name('logout');
});

Route::middleware('auth')->prefix('customer')->name('customer.')->group(function (): void {
    Route::get('addresses', [CustomerAddressController::class, 'index'])->name('addresses.index');
    Route::post('addresses', [CustomerAddressController::class, 'store'])->name('addresses.store');
    Route::patch('addresses/{address}', [CustomerAddressController::class, 'update'])->name('addresses.update');
    Route::delete('addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('addresses.destroy');
    Route::get('orders', [CustomerOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
    Route::get('return-requests', [CustomerReturnRequestController::class, 'index'])->name('return-requests.index');
    Route::post('return-requests', [CustomerReturnRequestController::class, 'store'])->name('return-requests.store');
    Route::get('product-reviews', [CustomerProductReviewController::class, 'index'])->name('product-reviews.index');
    Route::post('product-reviews', [CustomerProductReviewController::class, 'store'])->name('product-reviews.store');
    Route::get('support-tickets', [SupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::post('support-tickets', [SupportTicketController::class, 'store'])->name('support-tickets.store');
});
