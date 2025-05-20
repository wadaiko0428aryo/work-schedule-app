<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AdminController;



Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/attendance_list', [AdminController::class, 'attendance_list'])->name('admin.attendance_list');
});

Route::middleware('auth')->group(function()
{
	Route::get('/attendance', [AttendanceController::class, 'attendance'])->name('attendance');

    Route::get('/attendance/attendance_detail', [AttendanceController::class, 'attendance_detail'])->name('attendance_detail');

    Route::get('/attendance_list', [AttendanceController::class, 'attendance_list'])->name('attendance_list');

    Route::get('/request_list', [AttendanceController::class, 'request_list'])->name('request_list');


    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

});





