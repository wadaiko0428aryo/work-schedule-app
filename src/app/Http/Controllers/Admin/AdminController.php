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

    // 勤怠一覧画面の表示
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


    // スタッフ一覧画面の表示
    public function staff_list()
    {
        $users = User::where('role', 'staff')->get(); //管理者以外を取得
        return view('admin.staff_list', compact('users'));
    }


    // スタッフ別勤怠一覧画面の表示
    public function staff_attendance_list(Request $request, $user_id)
    {
        $user = User::findOrFail($user_id);

        // クエリパラメータから年月を取得、なければ今日
        $year = $request->query('year') ? (int)$request->query('year') : now()->year;
        $month = $request->query('month') ? (int)$request->query('month') : now()->month;


        // 表示対象の月の開始日と終了日
        $startOfMonth = Carbon::create($year, $month)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month)->endOfMonth();

        // 前月・翌月
        $previousMonth = $startOfMonth->copy()->subMonth();
        $nextMonth = $startOfMonth->copy()->addMonth();

         // 勤怠データ取得（start_time が該当月にあるもの）
        $attendances = Attendance::with('user')
        ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
        ->where('user_id', $user->id)
        ->get();

        return view('admin.staff_attendance_list', compact(
            'user', 'attendances', 'year', 'month', 'previousMonth', 'nextMonth'
        ));
    }

    public function attendance_update(Request $request)
    {
        $attendances = Attendance::update([
            
        ]);
    }

    public function approval()
    {
        return view('admin.approval');
    }
}
