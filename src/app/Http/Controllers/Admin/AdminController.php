<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;

class AdminController extends Controller
{
    public function attendance_list()
    {
        return view('admin.attendance_list');
    }
    public function attendance_detail()
    {
        return view('admin.attendance_detail');
    }
    public function staff_list()
    {
        return view('admin.staff_list');
    }
    public function staff_attendance_list()
    {
        return view('admin.staff_attendance_list');
    }
    public function request_list()
    {
        return view('admin.request_list');
    }

    public function approval()
    {
        return view('admin.approval');
    }
}
