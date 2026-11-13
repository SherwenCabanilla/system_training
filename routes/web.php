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
    Route::get('appointments/availability', [AppointmentController::class, 'availability'])->name('appointments.availability');
    Route::resource('appointments', AppointmentController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::get('medical-records', [MedicalRecordController::class, 'index'])->name('medical-records.index');
    Route::get('notifications', [PortalNotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/feed', [PortalNotificationController::class, 'feed'])->name('notifications.feed');
    Route::patch('notifications/read-all', [PortalNotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::patch('notifications/{portalNotification}/read', [PortalNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'staff'])->group(function () {
    Route::get('staff', StaffDashboardController::class)->name('staff.dashboard');
    Route::get('staff/requests', [StaffDashboardController::class, 'requests'])->name('staff.requests');
    Route::get('staff/upcoming', [StaffDashboardController::class, 'upcoming'])->name('staff.upcoming');
    Route::get('staff/history', [StaffDashboardController::class, 'history'])->name('staff.history');
    Route::patch('staff/appointments/{appointment}/status', [StaffDashboardController::class, 'updateAppointmentStatus'])->name('staff.appointments.status');
    Route::patch('staff/appointments/{appointment}/assignment', [StaffDashboardController::class, 'assignAppointment'])->name('staff.appointments.assignment');
    Route::put('staff/appointments/{appointment}/vital-reading', [StaffDashboardController::class, 'storeVitalReading'])->name('staff.appointments.vital-reading');
});

require __DIR__.'/auth.php';
