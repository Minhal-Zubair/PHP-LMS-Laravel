<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SettingsController;

// The main settings page
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

// The route to handle the form submission
Route::post('/settings/update', [SettingsController::class, 'update'])->name('settings.update');