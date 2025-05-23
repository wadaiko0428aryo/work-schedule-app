<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;

class AdminController extends Controller
{
    public function attendance_list(Request $request)
    {
        // クエリパラメータから日付を取得、なければ今日
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();

        $previousDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();
        $currentDate = $date;

        $attendances = Attendance::with('user')
        ->whereDate('start_time', $date)->get();

        return view('admin.attendance_list', compact('attendances', 'date', 'previousDate', 'nextDate', 'currentDate'));
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
