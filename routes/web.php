<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\InstagramAccountController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// ── Auth ──────────────────────────────────────────────────────
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ── Meta Webhook (without CSRF & auth) ───────────────────────
Route::get('/webhook', [WebhookController::class, 'verify'])->name('webhook.verify');
Route::post('/webhook', [WebhookController::class, 'handle'])->name('webhook.handle');

// ── Protected Routes ──────────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    // Redirect root → dashboard
    Route::get('/', fn () => redirect()->route('dashboard'));

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('can:view_dashboard')
        ->name('dashboard');

    // ── Inbox ────────────────────────────────────────────────
    Route::middleware('can:view_inbox')->prefix('inbox')->name('inbox.')->group(function () {
        Route::get('/', [InboxController::class, 'index'])->name('index');
        Route::get('/{conversation}', [InboxController::class, 'show'])->name('show');
        Route::get('/{conversation}/poll', [InboxController::class, 'pollMessages'])->name('poll');
        Route::get('/poll/conversations', [InboxController::class, 'pollConversations'])->name('poll.conversations');
    });

    Route::post('/inbox/{conversation}/send', [InboxController::class, 'sendMessage'])
        ->middleware(['can:view_inbox', 'can:send_messages'])
        ->name('inbox.send');

    // ── Instagram Accounts ───────────────────────────────────
    Route::middleware('can:view_accounts')->prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [InstagramAccountController::class, 'index'])->name('index');

        Route::middleware('can:manage_accounts')->group(function () {
            Route::get('/connect', [InstagramAccountController::class, 'connect'])->name('connect');
            Route::get('/callback', [InstagramAccountController::class, 'callback'])->name('callback');
            Route::patch('/{account}/disconnect', [InstagramAccountController::class, 'disconnect'])->name('disconnect');
            Route::patch('/{account}/activate', [InstagramAccountController::class, 'activate'])->name('activate');
            Route::delete('/{account}', [InstagramAccountController::class, 'destroy'])->name('destroy');
        });
    });

    // ── Users ────────────────────────────────────────────────
    Route::middleware('can:view_users')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');

        Route::middleware('can:manage_users')->group(function () {
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        });
    });

    // ── Permissions ──────────────────────────────────────────
    Route::middleware('can:manage_permissions')->prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::put('/{user}', [PermissionController::class, 'update'])->name('update');
    });
});

