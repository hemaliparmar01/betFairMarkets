<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\ContactMessages\ContactMessagesController;
use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Subscriptions\SubscriptionsController;
use App\Http\Controllers\Admin\Users\UsersController;
use App\Http\Middleware\Admin\AuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('login', [LoginController::class, 'loginView'])->name('logins.index');
    Route::post('login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login');

    Route::middleware(AuthMiddleware::class)->group(function (): void {
        Route::redirect('/', '/admin/dashboard');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('users', [UsersController::class, 'index'])->name('users');
        Route::get('users/create', [UsersController::class, 'create'])->name('users.create');
        Route::post('users', [UsersController::class, 'store'])->name('users.store');
        Route::get('users/{customer}/edit', [UsersController::class, 'edit'])->name('users.edit');
        Route::put('users/{customer}', [UsersController::class, 'update'])->name('users.update');
        Route::get('contact-messages', [ContactMessagesController::class, 'index'])->name('contact_messages.index');
        Route::resource('subscriptions', SubscriptionsController::class)->except('show');
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    });
});
