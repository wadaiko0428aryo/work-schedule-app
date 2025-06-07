<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;

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

        // 各勤怠に最新リクエストを付与
        foreach ($attendances as $attendance) {
            $attendance->latest_request = AttendanceRequest::where('attendance_id', $attendance->id)->latest()->first();
        }

        return view('attendance_list', compact(
            'user', 'attendances', 'year', 'month', 'previousMonth', 'nextMonth'
        ));

    }



    // 勤怠詳細画面の表示（一般、admin兼用）
    public function attendance_detail($attendance_id)
    {
        $user = Auth::user();

        $attendance = Attendance::with('user')->find($attendance_id);
        $attendance->refresh(); // ←最新状態にする

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

        // 管理者は誰の勤怠でも編集可能、staffは自分の勤怠のみ編集可能
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

        // is_adminがfalseの場合、ユーザーが申請すると自動的に「申請中」になる
        if (!$user->is_admin) {
            $attendance->is_approval = false;
            $attendance->status = 'pending';
        }

        $attendance->save(); //修正したデータを保存

        return redirect()->route('attendance_detail', ['attendance_id' => $attendance->id])->with('success', '勤怠を更新しました。');
    }





    // 勤怠申請画面の表示、admin時の表示も記載
    public function request_list(Request $request)
    {
        // ログインユーザー（staff）を取得
        $user = Auth::user();

        $status = $request->query('status', 'pending');

        // 管理者は全員の申請を取得、staffは自分の申請のみ取得
        $requests = AttendanceRequest::with(['attendance', 'user'])
            ->when(!$user->is_admin, function ($query) use ($user) {
                $query->where('user_id', $user->id); // staffなら自分だけ
            })
            ->where('status', $status)
            ->get();


            return view('request_list', compact('requests', 'status', 'user'));
    }

    public function request_edit(Request $request, $attendance_id)
    {
        $attendance = Attendance::findOrFail($attendance_id);
        $user = Auth::user();

        // 一般ユーザーのみ申請可能
        if ($user->is_admin || $attendance->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
        // 勤怠の基準日付を取得
        $baseDate = \Carbon\Carbon::parse($attendance->date)->toDateString();

        // 修正データをまとめて保存
        $editData = [];

        if ($request->start_time) $editData['start_time'] = $baseDate . ' ' . $request->start_time;
        if ($request->end_time) $editData['end_time'] = $baseDate . ' ' . $request->end_time;
        if ($request->break_start_time) $editData['break_start_time'] = $baseDate . ' ' . $request->break_start_time;
        if ($request->break_end_time) $editData['break_end_time'] = $baseDate . ' ' . $request->break_end_time;
        if ($request->break2_start_time) $editData['break2_start_time'] = $baseDate . ' ' . $request->break2_start_time;
        if ($request->break2_end_time) $editData['break2_end_time'] = $baseDate . ' ' . $request->break2_end_time;

        // 申請レコードを保存
        AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'date' => $attendance->date,
            'reason' => $request->reason,
            'status' => 'pending',
            'requested_start_time' => $request->start_time ? $baseDate . ' ' . $request->start_time : null,
            'requested_end_time' => $request->end_time ? $baseDate . ' ' . $request->end_time : null,
            'requested_break_start_time' => $request->break_start_time ? $baseDate . ' ' . $request->break_start_time : null,
            'requested_break_end_time' => $request->break_end_time ? $baseDate . ' ' . $request->break_end_time : null,
            'requested_reason' => $request->reason,
            'edit_data' => json_encode($editData),
        ]);

        // 勤怠側のステータスを「申請中」に変更
        $attendance->is_approval = false;
        $attendance->status = 'pending';
        $attendance->save();

        // 最新のリクエストを取得してリダイレクト
        $latestRequest = AttendanceRequest::where('attendance_id', $attendance->id)->latest()->first();


        return redirect()->route('requested_confirm', ['request_id' => $latestRequest->id])
        ->with('success', '修正申請を送信しました。');
    }

    public function requested_confirm($request_id)
    {
        $request = AttendanceRequest::with('attendance.user')->findOrFail($request_id);

        $attendance = $request->attendance;

        $user = Auth::user();

        return view('requested_confirm', compact('request', 'attendance', 'user'));
    }


}
