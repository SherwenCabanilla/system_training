<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PortalNotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('appointments', AppointmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('medical-records', [MedicalRecordController::class, 'index'])->name('medical-records.index');
    Route::get('notifications', [PortalNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{portalNotification}/read', [PortalNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('staff', StaffDashboardController::class)->middleware(['auth', 'staff'])->name('staff.dashboard');

require __DIR__.'/auth.php';
