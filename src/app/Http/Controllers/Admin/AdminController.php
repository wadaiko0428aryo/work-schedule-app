<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;

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





    public function approve($attendance_id)
    {
        $attendance = Attendance::findOrFail($attendance_id);

        // 最新の申請を取得
        $request = AttendanceRequest::where('attendance_id', $attendance_id)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();

        if (!$request) {
            return redirect()->back()->with('error', '申請データが見つかりませんでした。');
        }

        if ($request->edit_data) {
            $editData = json_decode($request->edit_data, true);

            foreach ($editData as $key => $value) {
                if (in_array($key, ['start_time', 'end_time', 'break_start_time', 'break_end_time', 'break2_start_time', 'break2_end_time'])) {
                    $attendance->$key = $value;
                }
            }

            // 備考（理由）の更新
            if (isset($editData['reason'])) {
                $attendance->reason = $editData['reason'];
            }

            // 勤怠データを承認済みに更新
            $attendance->is_approval = true;
            $attendance->status = 'approved';
            $attendance->save();

            // 申請レコードを更新
            $request->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);
        }

        return redirect()->route('requested_confirm', ['request_id' => $request->id])
                        ->with('success', '申請を承認しました');
    }
}
