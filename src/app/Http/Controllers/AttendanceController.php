<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Rest;
use App\Http\Requests\AttendanceRequest as AttendanceRequestFrom;


class AttendanceController extends Controller
{
    // ①勤怠登録画面の表示
    public function attendance()
    {
        $user = Auth::user();

        // 今日の勤怠レコード取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        // 初期値
        $status = 'before_work';

        if ($attendance) {

            // 休憩状態をbreaksテーブルから取得
            $latest_break = $attendance->rests()->latest()->first();

            if ($latest_break && !$latest_break->break_end_time) {
                // 休憩中
                $status = 'on_break';
            }
            elseif ($attendance->start_time && !$attendance->end_time) {
                // 勤務中
                $status = 'working';
            }
            elseif ($attendance->end_time) {
                // 退勤済み
                $status = 'finished';
            }
        }

        return view('attendance', compact('attendance', 'status'));
    }

    // ②出勤処理
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

    // ③退勤処理
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

    // ④休憩開始処理
    public function break(Request $request)
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', now()->toDateString())->first();

        // すでに未終了の休憩がないことを確認
        if($attendance->rests()->whereNull('break_end_time')->exists())
        {
            return back()->with('error', '既に休憩中です');
        }

        // 新しい休憩レコードを作成
        $attendance->rests()->create([
            'break_start_time' => now(),
        ]);

        return redirect()->route('attendance');
    }

    // ⑤休憩終了処理
    public function resume(Request $request)
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)->whereDate('date', now()->toDateString())->first();

        // 未終了の休憩を取得
        $rest = $attendance->rests()->whereNull('break_end_time')->latest()->first();

        if(!$rest)
        {
            return back()->with('error', '休憩中ではありません');
        }

        $rest->update([
            'break_end_time' => now(),
        ]);

        return redirect()->route('attendance');
    }


    // ⑥勤怠一覧画面の表示
    public function attendance_list(Request $request)
    {
        $user = Auth::user();

        // クエリパラメータから年月を取得、なければ今日
        $year = $request->query('year') ? (int)$request->query('year') : now()->year;
        $month = $request->query('month') ? (int)$request->query('month') : now()->month;


        // 月初・月末
        $startOfMonth = Carbon::create($year, $month)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month)->endOfMonth();

        // 前月・翌月
        $previousMonth = $startOfMonth->copy()->subMonth();
        $nextMonth = $startOfMonth->copy()->addMonth();


         // 勤怠データ取得（start_time が該当月にあるもの）
        $attendances = Attendance::with('user', 'rests')
        ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
        ->where('user_id', $user->id)
        ->get();

        // 日付をキーにした連想配列にする
        $attendances = $attendances->keyBy(function ($attendance) {
            return Carbon::parse($attendance->start_time)->toDateString();
        });

        // 各勤怠に最新リクエストを付与
        foreach ($attendances as $attendance) {
            $attendance->latest_request = AttendanceRequest::where('attendance_id', $attendance->id)->latest()->first();
        }

        return view('attendance_list', compact(
            'user', 'attendances', 'year', 'month',
            'previousMonth', 'nextMonth', 'startOfMonth', 'endOfMonth'
        ));

    }



    // ⑦勤怠詳細画面の表示（一般、admin兼用）
    public function attendance_detail($attendance_id)
    {
        $user = Auth::user();

        $attendance = Attendance::with('user', 'rests')->find($attendance_id);
        $attendance->refresh(); // ←最新状態にする

        // アクセス制限：管理者以外は自分の勤怠のみ
        if (!$user->is_admin && $attendance->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $rests = $attendance->rests->toArray();
        $rests[] = ['break_start_time' => null, 'break_end_time' => null];

        return view('attendance_detail', compact('attendance', 'user', 'rests'));
    }




    // ⑧勤怠データ修正(admin)
    public function attendance_update(AttendanceRequestFrom $request, $attendance_id)
    {
        $attendance = Attendance::with('rests')->findOrFail($attendance_id);
        $user = Auth::user();

        // 管理者は誰の勤怠でも編集可能、staffは自分の勤怠のみ
        if (!$user->is_admin && $attendance->user_id !== $user->id) {
            abort(403, 'Unauthorized update.');
        }

        // 日付（既存の勤怠日付）
        $date = \Carbon\Carbon::parse($attendance->start_time)->toDateString();

        // 出勤・退勤更新
        if ($request->start_time) {
            $attendance->start_time = $date . ' ' . $request->start_time;
        }
        if ($request->end_time) {
            $attendance->end_time = $date . ' ' . $request->end_time;
        }

        $attendance->reason = $request->reason;

        // 一般ユーザーだけステータス変更
        if (!$user->is_admin) {
            $attendance->is_approval = false;
            $attendance->status = 'pending';
        }

        $attendance->save();

        // 既存休憩を一度削除して再登録
        $attendance->rests()->delete();

        $break_start_times = $request->break_start_time ?? [];
        $break_end_times = $request->break_end_time ?? [];

        foreach ($break_start_times as $index => $start_time) {
            $end_time = $break_end_times[$index] ?? null;

            // 両方空ならスキップ
            if (!$start_time && !$end_time) {
                continue;
            }

            $attendance->rests()->create([
                'break_start_time' => $start_time ? $date . ' ' . $start_time : null,
                'break_end_time'   => $end_time ? $date . ' ' . $end_time : null,
            ]);
        }

        return redirect()->route('attendance_detail', ['attendance_id' => $attendance->id])
            ->with('success', '勤怠を更新しました。');
    }





    // ⑨勤怠申請画面の表示、admin時の表示も記載
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


    // 🔟修正申請機能
    public function request_edit(AttendanceRequestFrom $request, $attendance_id)
    {
        $attendance = Attendance::findOrFail($attendance_id);
        $user = Auth::user();

        // 一般ユーザーのみ申請可能
        if ($user->is_admin || $attendance->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
        $baseDate = \Carbon\Carbon::parse($attendance->date)->toDateString();

        // 修正データのベース配列
        $editData = [
            'start_time' => $request->start_time ? $baseDate . ' ' . $request->start_time : null,
            'end_time' => $request->end_time ? $baseDate . ' ' . $request->end_time : null,
            'breaks' => [],  // 複数休憩用
        ];

        // 休憩を editData にも追加
        $breakStartTimes = $request->break_start_time ?? [];
        $breakEndTimes = $request->break_end_time ?? [];

        $attendanceRequest = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'date' => $attendance->date,
            'reason' => $request->reason,
            'status' => 'pending',
            'requested_start_time' => $editData['start_time'],
            'requested_end_time' => $editData['end_time'],
            'requested_reason' => $request->reason,
            'edit_data' => json_encode($editData),
        ]);

        // 複数休憩時間の配列は必ず同じ数で届く想定でループ処理
        $breakStartTimes = $request->break_start_time ?? [];
        $breakEndTimes = $request->break_end_time ?? [];

        foreach ($breakStartTimes as $index => $breakStart) {
            // 空の時間は無視（休憩が入ってない場合）
            if (!$breakStart) continue;

                $end_time = $breakEndTimes[$index] ?? null;

                $attendanceRequest->breakRequests()->create([
                    'attendance_id' => $attendance->id,
                    'requested_break_start_time' => $baseDate . ' ' . $breakStart,
                    'requested_break_end_time' => $end_time ? $baseDate . ' ' . $end_time : null,
                    'status' => 'pending',
                ]);
            }


        // 勤怠側のステータスを申請中に変更
        $attendance->is_approval = false;
        $attendance->status = 'pending';
        $attendance->save();

        return redirect()->route('requested_confirm', ['request_id' => $attendanceRequest->id])
            ->with('success', '修正申請を送信しました。');
    }

    // 11.修正内容確認画面
    public function requested_confirm($request_id)
    {
        $request = AttendanceRequest::with('attendance.user', 'breakRequests')->findOrFail($request_id);

        $attendance = $request->attendance;

        $user = Auth::user();

        return view('requested_confirm', compact('request', 'attendance', 'user'));
    }


}
