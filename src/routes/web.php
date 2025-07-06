<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuthController;


Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/mail-check', [AuthController::class, 'mailCheck'])->name('mailCheck');
Route::post('/send-token-email', [AuthController::class, 'sendTokenEmail'])->name('sendTokenEmail');
// 認証コード再送信用
Route::post('/resend-token', [AuthController::class, 'resendToken'])->name('resendToken');
Route::post('/auth', [AuthController::class, 'auth'])->name('auth');



Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/attendance_list', [AdminController::class, 'attendance_list'])->name('attendance_list');

    // Route::get('/attendance_list/attendance_detail/{attendance_id}', [AdminController::class, 'attendance_detail'])->name('attendance_detail');

    Route::get('/staff_list', [AdminController::class, 'staff_list'])->name('staff_list');

    Route::get('/staff_list/staff_attendance_list/{user_id}', [AdminController::class, 'staff_attendance_list'])->name('staff_attendance_list');

    Route::get('/staff_list/staff_attendance_list/{user_id}/export', [AdminController::class, 'exportStaffAttendanceCSV'])->name('staff_attendance_list.export');

    // Route::get('staff_list/staff_attendance_list/attendance_create/{user_id}', [AdminController::class, 'attendance_create'])->name('attendance_create');

    // Route::post('/staff_list/staff_attendance_list/attendance_store', [AdminController::class, 'attendanceStore'])
    //     ->name('attendance_store');


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

    Route::post('/attendance/request/{attendance_id}', [AttendanceController::class, 'request_edit'])->name('attendance.request_edit');


    Route::post('/attendance_list/attendance_detail/{attendance_id}', [AttendanceController::class, 'attendance_update'])->name('attendance_update');

    Route::get('/request_list', [AttendanceController::class, 'request_list'])->name('request_list');

    Route::post('/approve/{attendance_id}', [AdminController::class, 'approve'])->name('admin.attendance.approve');

    Route::get('/requested_confirm/{request_id}', [AttendanceController::class, 'requested_confirm'])->name('requested_confirm');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');

});





