<?php

use App\Http\Controllers\ResidentDashboardController;
use App\Http\Controllers\ResidentLoginController;
use App\Http\Controllers\ResidentAccountController;
use App\Http\Controllers\ResidentRequestController;
use App\Http\Controllers\ResidentSubmitController;
use App\Http\Controllers\AdminAuditController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminResidentController;
use App\Http\Controllers\AdminSummaryController;
use App\Http\Controllers\AdminWalkInController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

Route::get('/resident/login', [ResidentLoginController::class, 'create'])->name('resident.login');
Route::post('/resident/login', [ResidentLoginController::class, 'store'])->name('resident.login.store');
Route::get('/resident/dashboard', [ResidentDashboardController::class, 'index'])->name('resident.dashboard');
Route::get('/resident/dashboard/attachments/{filename}', [ResidentDashboardController::class, 'attachment'])
    ->name('resident.dashboard.attachment');
Route::get('/resident/logout', [ResidentDashboardController::class, 'logout'])->name('resident.logout');
Route::get('/resident/submit', [ResidentSubmitController::class, 'create'])->name('resident.submit');
Route::post('/resident/submit', [ResidentSubmitController::class, 'store'])->name('resident.submit.store');
Route::get('/resident/requests', [ResidentRequestController::class, 'index'])->name('resident.requests');
Route::post('/resident/requests/{requestId}/cancel', [ResidentRequestController::class, 'cancel'])
    ->whereNumber('requestId')->name('resident.requests.cancel');
Route::get('/resident/requests/{requestId}/certificate', [AdminSummaryController::class, 'residentCertificate'])
    ->whereNumber('requestId')->name('resident.requests.certificate');
Route::get('/resident/tracking', [ResidentRequestController::class, 'tracking'])->name('resident.tracking');
Route::post('/resident/tracking', [ResidentRequestController::class, 'tracking'])->name('resident.tracking.search');
Route::get('/resident/account', [ResidentAccountController::class, 'index'])->name('resident.account');
Route::post('/resident/account', [ResidentAccountController::class, 'update'])->name('resident.account.update');

Route::get('/admin/login', [AdminLoginController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'store'])->name('admin.login.store');
Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
Route::get('/admin/dashboard/attachments/{filename}', [AdminDashboardController::class, 'attachment'])
    ->name('admin.dashboard.attachment');
Route::post('/admin/dashboard/update', [AdminDashboardController::class, 'update'])->name('admin.dashboard.update');
Route::get('/admin/accounts', [AdminResidentController::class, 'createAccount'])->name('admin.accounts');
Route::post('/admin/accounts', [AdminResidentController::class, 'storeAccount'])->name('admin.accounts.store');
Route::get('/admin/residents', [AdminResidentController::class, 'index'])->name('admin.residents');
Route::post('/admin/residents/status', [AdminResidentController::class, 'toggleStatus'])->name('admin.residents.toggle');
Route::get('/admin/walkin', [AdminWalkInController::class, 'index'])->name('admin.walkin');
Route::post('/admin/walkin', [AdminWalkInController::class, 'store'])->name('admin.walkin.store');
Route::get('/admin/summary', [AdminSummaryController::class, 'index'])->name('admin.summary');
Route::post('/admin/summary/printed', [AdminSummaryController::class, 'printed'])->name('admin.summary.printed');
Route::get('/admin/audit', [AdminAuditController::class, 'index'])->name('admin.audit');
Route::get('/admin/logout', [AdminResidentController::class, 'logout'])->name('admin.logout');
Route::post('/admin/notifications/send', [AdminNotificationController::class, 'send'])->name('admin.notification.send');