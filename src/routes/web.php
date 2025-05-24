<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AdminController;



Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/attendance_list', [AdminController::class, 'attendance_list'])->name('attendance_list');

    Route::get('/attendance_list/attendance_detail/{attendance_id}', [AdminController::class, 'attendance_detail'])->name('attendance_detail');

    Route::get('/staff_list', [AdminController::class, 'staff_list'])->name('staff_list');

    Route::get('/staff_list/staff_attendance_list/{user_id}', [AdminController::class, 'staff_attendance_list'])->name('staff_attendance_list');

    Route::get('/request_list', [AdminController::class, 'request_list'])->name('request_list');

    Route::get('/approval', [AdminController::class, 'approval'])->name('approval');

});

Route::middleware('auth')->group(function()
{
	Route::get('/attendance', [AttendanceController::class, 'attendance'])->name('attendance');

    // 勤怠ルート
    Route::post('/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
    Route::post('/attendance/break', [AttendanceController::class, 'break'])->name('attendance.break');
    Route::post('/attendance/resume', [AttendanceController::class, 'resume'])->name('attendance.resume');
    Route::post('/attendance/end', [AttendanceController::class, 'end'])->name('attendance.end');

    Route::get('/attendance_list', [AttendanceController::class, 'attendance_list'])->name('attendance_list');

    Route::get('/attendance_list/attendance_detail/{attendance_id}', [AttendanceController::class, 'attendance_detail'])->name('attendance_detail');


    Route::get('/request_list', [AttendanceController::class, 'request_list'])->name('request_list');


    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

});





