<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function attendance()
    {
        return view('attendance');
    }

    public function attendance_detail()
    {
        return view('attendance_detail');
    }

    public function attendance_list()
    {
        return view('attendance_list');
    }

    public function request_list()
    {
        return view('request_list');
    }


}
