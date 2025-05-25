<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    // 勤怠登録画面の表示
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

    // 出勤処理
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

    // 退勤処理
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

    // 休憩開始処理
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

    // 休憩終了処理
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


    // 勤怠一覧画面の表示
    public function attendance_list(Request $request)
    {
        $user = Auth::user();

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

        return view('attendance_list', compact(
            'user', 'attendances', 'year', 'month', 'previousMonth', 'nextMonth'
        ));

    }

    // 勤怠詳細画面の表示（一般、admin兼用）
    public function attendance_detail($attendance_id)
    {
        $user = Auth::user();

        $attendance = Attendance::find($attendance_id);

        // アクセス制限：管理者以外は自分の勤怠のみ
        if (!$user->is_admin && $attendance->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        return view('attendance_detail', compact('attendance', 'user'));
    }


    // 勤怠データ修正（admin）と修正＆申請（一般）
    public function attendance_update(Request $request, $attendance_id)
    {
        $attendance = Attendance::findOrFail($attendance_id);
        $user = Auth::user();

        // 一般ユーザーは自分の勤怠のみ編集可能
        if (!$user->is_admin && $attendance->user_id !== $user->id) {
            abort(403, 'Unauthorized update.');
        }

        // 日付情報（元の勤怠日付）
        $date = \Carbon\Carbon::parse($attendance->start_time)->toDateString();

        // 時刻だけ更新用に結合
        if ($request->start_time) {
            $attendance->start_time = $date . ' ' . $request->start_time;
        }
        if ($request->end_time) {
            $attendance->end_time = $date . ' ' . $request->end_time;
        }
        if ($request->break_start_time) {
            $attendance->break_start_time = $date . ' ' . $request->break_start_time;
        }
        if ($request->break_end_time) {
            $attendance->break_end_time = $date . ' ' . $request->break_end_time;
        }

        $attendance->reason = $request->reason;

        // 一般ユーザーは「修正申請」にしてステータス変更
        if (!$user->is_admin) {
            $attendance->is_approval = false; // 承認待ち
        }

        $attendance->save();

        return redirect()->route('attendance_detail', ['attendance_id' => $attendance->id])->with('success', '勤怠を更新しました。');
    }



    // 勤怠申請画面の表示（一般、admin兼用）
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
