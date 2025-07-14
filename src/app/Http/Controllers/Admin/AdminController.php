<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\BreakRequest;
use App\Models\AttendanceRequest;

class AdminController extends Controller
{

    // 勤怠一覧画面の表示
    public function attendance_list(Request $request)
    {
        // クエリパラメータ（URL）から日付を取得、指定がなければ今日の日付データを表示
        // $request->query('date') => URLの中にdate=⚪︎⚪︎があるか調べる
        // Carbon::parse => URLに⚪︎⚪︎があればその文字を(2025-07-10)のような「日付」としてつかえるように変換する
        // Carbon::today() => URLに日付がなかった場合「今日の日付」を表示
        // AA ？　　BB ：　　CC => Aの条件で、もしtrueなら　B　、falseなら　C //
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();

        // $date（当日）の一日前を（2025-07-09）のように文字列にして$previousDateに入れる
        $previousDate = $date->copy()->subDay()->toDateString();

        // $date（当日）の一日後を（2025-07-11）のように文字列にして$nextDateにいれる
        $nextDate = $date->copy()->addDay()->toDateString();

        // 今日の日付（$date）をそのまま$currentDateに代入
        $currentDate = $date;

        // Attendanceモデルから紐づくuser情報も一緒に取得し、start_timeの日付が$dateと一致するデータを絞り込みその日付のページに表示する
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


        // 月始・月末
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

        return view('admin.staff_attendance_list', compact(
            'user', 'attendances', 'year', 'month',
            'previousMonth', 'nextMonth', 'startOfMonth', 'endOfMonth'
        ));
    }

    // CSV機能
    public function exportStaffAttendanceCSV(Request $request, $user_id)
    {
        $user = User::findOrFail($user_id);

        $year = $request->query('year') ? (int)$request->query('year') : now()->year;
        $month = $request->query('month') ? (int)$request->query('month') : now()->month;

        $startOfMonth = Carbon::create($year, $month)->startOfMonth();
        $endOfMonth = Carbon::create($year, $month)->endOfMonth();

        $attendances = Attendance::with('rests')
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->where('user_id', $user->id)
            ->get();

        // CSV をストリームで生成
        $response = new StreamedResponse(function () use ($attendances, $user, $year, $month) {
            $handle = fopen('php://output', 'w');

            // ヘッダ
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩時間', '合計勤務時間']);

            foreach ($attendances as $attendance) {
                $date = Carbon::parse($attendance->start_time)->format('Y-m-d');
                $start = $attendance->start_time ? Carbon::parse($attendance->start_time)->format('H:i') : '';
                $end = $attendance->end_time ? Carbon::parse($attendance->end_time)->format('H:i') : '';

                // 休憩時間計算
                $breakMinutes = 0;
                foreach ($attendance->rests as $rest) {
                    if ($rest->break_start_time && $rest->break_end_time) {
                        $breakMinutes += Carbon::parse($rest->break_start_time)
                            ->diffInMinutes(Carbon::parse($rest->break_end_time));
                    }
                }

                $breakTime = $breakMinutes > 0 ? floor($breakMinutes / 60) . ':' . sprintf('%02d', $breakMinutes % 60) : '';

                // 勤務時間計算
                $totalMinutes = 0;
                if ($attendance->start_time && $attendance->end_time) {
                    $totalMinutes = Carbon::parse($attendance->start_time)
                        ->diffInMinutes(Carbon::parse($attendance->end_time)) - $breakMinutes;
                }
                $totalTime = $totalMinutes > 0 ? floor($totalMinutes / 60) . ':' . sprintf('%02d', $totalMinutes % 60) : '';

                fputcsv($handle, [$date, $start, $end, $breakTime, $totalTime]);
            }

            fclose($handle);
        });

        $fileName = "{$user->name}_{$year}_{$month}_attendances.csv";

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename={$fileName}");

        return $response;
    }


    // adminが勤怠データを新規作成する
    // public function attendance_create($user_id)
    // {
    //     $user = User::findOrFail($user_id);

    //     $rests = [
    //         ['break_start_time' => null, 'break_end_time' => null],
    //     ];

    //     $attendance = Attendance::where('user_id', $user_id)
    //                 ->latest('date')
    //                 ->first();

    //     return view('attendance_create', compact('user','rests', 'attendance'));
    // }

    // public function attendance_store(AttendanceRequest $request)
    // {

    // }


    public function approve(Request $request, $attendance_id)
    {
        // 勤怠データと関連申請を取得
        $attendance = Attendance::findOrFail($attendance_id);
        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance_id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$attendanceRequest) {
            return redirect()->back()->with('error', '申請データが見つかりませんでした。');
        }

        //  勤怠データを更新
        if ($attendanceRequest->edit_data) {
            $editData = json_decode($attendanceRequest->edit_data, true);
            foreach (['start_time', 'end_time', 'reason'] as $key) {
                if (isset($editData[$key])) {
                    $attendance->$key = $editData[$key];
                }
            }
        } else {
            // 個別カラムがある場合
            $attendance->start_time = $attendanceRequest->requested_start_time ?? $attendance->start_time;
            $attendance->end_time   = $attendanceRequest->requested_end_time ?? $attendance->end_time;
            $attendance->reason     = $attendanceRequest->requested_reason ?? $attendance->reason;
        }

        $attendance->is_approval = true;
        $attendance->status = 'approved';
        $attendance->save();

        //  既存の休憩を削除
        Rest::where('attendance_id', $attendance_id)->delete();

        //  break_requests を反映
        $breakRequests = BreakRequest::where('attendance_id', $attendance_id)
            ->where('status', 'pending')
            ->get();

        foreach ($breakRequests as $breakRequest) {
            Rest::create([
                'attendance_id'      => $attendance_id,
                'break_start_time'   => $breakRequest->requested_break_start_time,
                'break_end_time'     => $breakRequest->requested_break_end_time,
            ]);

            $breakRequest->update([
                'status'       => 'approved',
                'approved_by'  => Auth::id(),
                'approved_at'  => now(),
            ]);
        }

        //  attendance_request の status を更新
        $attendanceRequest->update([
            'status'       => 'approved',
            'approved_by'  => Auth::id(),
            'approved_at'  => now(),
        ]);

        //  完了リダイレクト
        return redirect()->route('requested_confirm', ['request_id' => $attendanceRequest->id])
            ->with('success', '申請を承認しました');
    }
}