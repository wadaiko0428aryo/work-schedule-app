<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    public function attendance()
    {
        $user = Auth::user();

        // 今日の勤怠レコード取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        // 初期値を設定しておく
        $status = 'before_work';

        if ($attendance) {
            if ($attendance->start_time && !$attendance->end_time &&
                (!$attendance->break_start_time || ($attendance->break_start_time && $attendance->break_end_time))) {
                // 出勤していて、退勤しておらず、休憩中ではない
                $status = 'working';
            } elseif ($attendance->break_start_time && !$attendance->break_end_time) {
                // 休憩開始していて、まだ終わってない
                $status = 'on_break';
            } elseif ($attendance->end_time) {
                $status = 'finished';
            }
        }

        return view('attendance', compact('attendance', 'status'));
    }

    // 出勤
    public function start(Request $request)
    {
        $user = Auth::user();

        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
        ]);

        return redirect()->route('attendance');
    }

    // 退勤
    public function end(Request $request)
    {
        $user = Auth::user();

        // 今日の勤怠を取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        if ($attendance && !$attendance->end_time) {
            $attendance->end_time = now();
            $attendance->save();
        }
        return redirect()->route('attendance');
    }

    // 休憩開始
    public function break(Request $request)
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', now()->toDateString())->first();

        if($attendance && !$attendance->break_start_time)
        {
            $attendance->break_start_time = now();
            $attendance->save();
        }

        return redirect()->route('attendance');
    }

    // 休憩終了
    public function resume(Request $request)
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', now()->toDateString())->first();

        if($attendance && !$attendance->break_end_time)
        {
            $attendance->break_end_time = now();
            $attendance->save();
        }

        return redirect()->route('attendance');
    }



    public function attendance_list(Request $request)
    {
        $user = Auth::user();

        // クエリパラメータから日付を取得、なければ今日
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();

        $previousDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();
        $currentDate = $date;

        $attendances = Attendance::with('user')
        ->whereDate('start_time', $date)
        ->where('user_id', $user->id)
        ->get();

        return view('attendance_list', compact('attendances', 'date', 'previousDate', 'nextDate', 'currentDate'));
    }

    public function attendance_detail($attendance_id)
    {
        $user = Auth::user();
        $attendance = Attendance::find($attendance_id);
        return view('attendance_detail', compact('attendance', 'user'));
    }


    public function request_list(Request $request)
    {
        $user = Auth::user();
        $status = $request->query('status', '申請中');

        $attendances = Attendance::with('user')
            ->where('status', $status)
            ->where('user_id', $user->id) //login userのみにしぼる
            ->get();


        return view('request_list', compact('attendances', 'status'));
    }


}
