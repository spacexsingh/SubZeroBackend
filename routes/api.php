<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConferenceController;
use App\Http\Controllers\Api\ConferenceStaffController;
use App\Http\Controllers\Api\SideEventController;
use App\Http\Controllers\Api\SideEventStaffController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public Conference Routes (anyone can view)
Route::get('/conferences', [ConferenceController::class, 'index']);
Route::get('/conferences/{conference}', [ConferenceController::class, 'show']);

// Protected Conference Routes (Administrator only)
Route::middleware(['auth:sanctum', 'check.user.type:administrator'])->group(function () {
    Route::post('/conferences', [ConferenceController::class, 'store']);
    Route::put('/conferences/{conference}', [ConferenceController::class, 'update']);
    Route::delete('/conferences/{conference}', [ConferenceController::class, 'destroy']);
});

// Side Events Routes
Route::get('/conferences/{conference}/side-events', [SideEventController::class, 'index']);
Route::get('/conferences/{conference}/side-events/{sideEvent}', [SideEventController::class, 'show']);

// Protected Side Event Routes (Administrator only)
Route::middleware(['auth:sanctum', 'check.user.type:administrator'])->group(function () {
    Route::post('/conferences/{conference}/side-events', [SideEventController::class, 'store']);
    Route::put('/conferences/{conference}/side-events/{sideEvent}', [SideEventController::class, 'update']);
    Route::delete('/conferences/{conference}/side-events/{sideEvent}', [SideEventController::class, 'destroy']);
});

// Conference Staff Management Routes
// Admin and Admin Assistant can manage site managers
Route::middleware(['auth:sanctum', 'check.user.type:administrator,administrator_assistant'])->group(function () {
    Route::post('/conferences/{conference}/site-managers', [ConferenceStaffController::class, 'attachSiteManager']);
    Route::delete('/conferences/{conference}/site-managers', [ConferenceStaffController::class, 'detachSiteManager']);
});

// Admin, Admin Assistant, and Site Manager can manage volunteers
Route::middleware(['auth:sanctum', 'check.user.type:administrator,administrator_assistant,site_manager'])->group(function () {
    Route::post('/conferences/{conference}/volunteers', [ConferenceStaffController::class, 'attachVolunteer']);
    Route::delete('/conferences/{conference}/volunteers', [ConferenceStaffController::class, 'detachVolunteer']);
});

// List staff (authenticated users)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/conferences/{conference}/site-managers', [ConferenceStaffController::class, 'listSiteManagers']);
    Route::get('/conferences/{conference}/volunteers', [ConferenceStaffController::class, 'listVolunteers']);
});

// Side Event Staff Management Routes
// Admin and Admin Assistant can manage site managers for side events
Route::middleware(['auth:sanctum', 'check.user.type:administrator,administrator_assistant'])->group(function () {
    Route::post('/conferences/{conference}/side-events/{sideEvent}/site-managers', [SideEventStaffController::class, 'attachSiteManager']);
    Route::delete('/conferences/{conference}/side-events/{sideEvent}/site-managers', [SideEventStaffController::class, 'detachSiteManager']);
});

// Admin, Admin Assistant, and Site Manager can manage volunteers for side events
Route::middleware(['auth:sanctum', 'check.user.type:administrator,administrator_assistant,site_manager'])->group(function () {
    Route::post('/conferences/{conference}/side-events/{sideEvent}/volunteers', [SideEventStaffController::class, 'attachVolunteer']);
    Route::delete('/conferences/{conference}/side-events/{sideEvent}/volunteers', [SideEventStaffController::class, 'detachVolunteer']);
});

// List side event staff and available staff (authenticated users)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/conferences/{conference}/side-events/{sideEvent}/site-managers', [SideEventStaffController::class, 'listSiteManagers']);
    Route::get('/conferences/{conference}/side-events/{sideEvent}/volunteers', [SideEventStaffController::class, 'listVolunteers']);
    Route::get('/conferences/{conference}/side-events/{sideEvent}/available-site-managers', [SideEventStaffController::class, 'listAvailableSiteManagers']);
    Route::get('/conferences/{conference}/side-events/{sideEvent}/available-volunteers', [SideEventStaffController::class, 'listAvailableVolunteers']);
});
