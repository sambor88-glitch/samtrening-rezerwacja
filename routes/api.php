<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\AdminController;

// ─── Auth ────────────────────────────────────────────────────────────────────
Route::post('/auth/trainer/login', [AuthController::class, 'trainerLogin']);
Route::post('/trainer/login',      [AuthController::class, 'trainerLogin']); // backward compat
Route::post('/auth/client/login',  [AuthController::class, 'clientLogin']);
Route::post('/admin/login',        [AuthController::class, 'adminLogin']);
Route::post('/auth/logout',        [AuthController::class, 'logout']);
Route::get('/auth/me',             [AuthController::class, 'me']);

// ─── Public trainers list ─────────────────────────────────────────────────────
Route::get('/trainers', [TrainerController::class, 'index']);

// ─── Trainer routes (requires trainer session) ────────────────────────────────
Route::middleware('trainer.auth')->prefix('trainer')->group(function () {
    // Sync
    Route::get('/sync', [TrainerController::class, 'sync']);

    // Clients
    Route::get('/clients',             [TrainerController::class, 'clients']);
    Route::post('/clients',            [TrainerController::class, 'addClient']);
    Route::put('/clients/{id}',        [TrainerController::class, 'updateClient']);
    Route::delete('/clients/{id}',     [TrainerController::class, 'deleteClient']);
    Route::get('/clients/{id}/price',  [TrainerController::class, 'getClientPrice']);
    Route::post('/clients/{id}/price', [TrainerController::class, 'saveClientPrice']);
    Route::get('/clients/{id}/note',   [TrainerController::class, 'getClientNote']);
    Route::post('/clients/{id}/note',  [TrainerController::class, 'saveClientNote']);
    Route::get('/clients/{id}/measurements',  [TrainerController::class, 'getMeasurements']);
    Route::post('/clients/{id}/measurements', [TrainerController::class, 'addMeasurement']);

    // Bookings
    Route::get('/bookings',                [TrainerController::class, 'bookings']);
    Route::post('/bookings',               [TrainerController::class, 'addBooking']);
    Route::put('/bookings/{id}',           [TrainerController::class, 'updateBooking']);
    Route::delete('/bookings/{id}',        [TrainerController::class, 'deleteBooking']);
    Route::post('/bookings/{id}/complete', [TrainerController::class, 'completeBooking']);
    Route::post('/bookings/{id}/cancel',   [TrainerController::class, 'cancelBooking']);

    // Availability & blocked
    Route::get('/availability',      [TrainerController::class, 'availability']);
    Route::post('/availability',     [TrainerController::class, 'saveAvailability']);
    Route::get('/blocked',           [TrainerController::class, 'blocked']);
    Route::post('/blocked',          [TrainerController::class, 'addBlocked']);
    Route::delete('/blocked/{id}',   [TrainerController::class, 'deleteBlocked']);

    // Packages
    Route::get('/packages',        [TrainerController::class, 'packages']);
    Route::post('/packages',       [TrainerController::class, 'addPackage']);
    Route::put('/packages/{id}',   [TrainerController::class, 'updatePackage']);

    // Payments
    Route::get('/payments',                  [TrainerController::class, 'payments']);
    Route::post('/payments',                 [TrainerController::class, 'addPayment']);
    Route::put('/payments/{id}/confirm',     [TrainerController::class, 'confirmPayment']);

    // Messages
    Route::get('/messages/unread',           [TrainerController::class, 'unreadCounts']);
    Route::get('/messages/{clientId}',       [TrainerController::class, 'getMessages']);
    Route::post('/messages/{clientId}',      [TrainerController::class, 'sendMessage']);

    // Settings
    Route::get('/settings',   [TrainerController::class, 'getSettings']);
    Route::post('/settings',  [TrainerController::class, 'saveSettings']);

    // Training plans
    Route::get('/plans/{clientId}',   [TrainerController::class, 'getPlans']);
    Route::post('/plans/{clientId}',  [TrainerController::class, 'savePlan']);
});

// ─── Client routes (requires client session) ──────────────────────────────────
Route::middleware('client.auth')->prefix('client')->group(function () {
    Route::get('/sync',                   [ClientController::class, 'sync']);
    Route::post('/bookings',              [ClientController::class, 'addBooking']);
    Route::put('/bookings/{id}/cancel',   [ClientController::class, 'cancelBooking']);
    Route::get('/messages',               [ClientController::class, 'getMessages']);
    Route::post('/messages',              [ClientController::class, 'sendMessage']);
    Route::post('/packages/buy',          [ClientController::class, 'buyPackage']);
});

// ─── Admin routes (requires admin session) ────────────────────────────────────
Route::middleware('admin.auth')->prefix('admin')->group(function () {
    Route::get('/stats',              [AdminController::class, 'stats']);
    Route::get('/trainers',           [AdminController::class, 'trainers']);
    Route::post('/trainers',          [AdminController::class, 'addTrainer']);
    Route::put('/trainers/{id}',      [AdminController::class, 'updateTrainer']);
    Route::delete('/trainers/{id}',   [AdminController::class, 'deleteTrainer']);
    Route::get('/clients',            [AdminController::class, 'clients']);
    Route::get('/bookings',           [AdminController::class, 'bookings']);
    Route::get('/payments',           [AdminController::class, 'payments']);
});
