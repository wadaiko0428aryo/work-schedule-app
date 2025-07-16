@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff_attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-title attendance-list_title">
    {{$user->name}}さんの勤怠
</div>
<div class="date-navigation">
    <a href="{{ route('admin.staff_attendance_list', ['user_id' => $user->id, 'year' => $previousMonth->year, 'month' => $previousMonth->month]) }}" class="date-link">←前月</a>
    <span class="date">{{ $year }}/{{ $month }}</span>
    <a href="{{ route('admin.staff_attendance_list', ['user_id' => $user->id, 'year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="date-link">翌月→</a>
</div>

<div class="attendance-table">
    <table>
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay())
            @php
                $attendance = $attendances->get($date->toDateString());
                $workStart = $attendance && $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time) : null;
                $workEnd = $attendance && $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time) : null;

                <!-- 複数の休憩時間を全て足して、userがその日に取得した休憩時間の総合計を$breakDurationに代入 -->
                $breakDuration = 0; <!-- $breakDurationの初期値を0にする（0からどんどん休憩時間を足すから！） -->
                if ($attendance) {
                    foreach ($attendance->rests as $rest) {
                        if ($rest->break_start_time && $rest->break_end_time) {
                            $breakDuration += \Carbon\Carbon::parse($rest->break_start_time)
                                ->diffInMinutes(\Carbon\Carbon::parse($rest->break_end_time));
                        }
                    }
                }
                <!-- 出勤時間と退勤時間が両方存在しているか => true->「$workStart->diffInMinutes($workEnd)」で出勤から退勤までの合計分数を求め、「- $breakDuration」で休憩の合計時間（分）を引く =>「よって業務時間が算出される」  false->null
                    ※ 「diffInMinutes」はlaravelの日時ライブラリのCarbonが提供する「二つの時刻の差を分単位で計算する関数」 -->
                $totalDuration = ($workStart && $workEnd) ? $workStart->diffInMinutes($workEnd) - $breakDuration : 0;
            @endphp

                <tr>

                <td>
                    <!-- $date->format('n/j') => n（何月、先頭に０なし）、j（何日、先頭に０なし）$date->dayOfWeek => 曜日を数字（０〜６）で取得 -> 数字に対応する日本語（曜日）を設定 -> bladeで「１」->「月」、「４」->「木」になる -->
                    {{ $date->format('n/j') }}（{{ ['日','月','火','水','木','金','土'][$date->dayOfWeek] }}）
                </td>
                    <td>{{ $workStart ? $workStart->format('H:i') : '' }}</td>
                    <td>{{ $workEnd ? $workEnd->format('H:i') : '' }}</td>
                    <td>
                        @if ($breakDuration > 0)    <!-- 休憩時間の合計が０より大きい場合以下を表示する -->
                            {{ floor($breakDuration / 60) }}:{{ sprintf('%02d', $breakDuration % 60) }}
                            <!-- $breakDurationを60で割ることで時間を求め、floor()で少数点以下を切り捨て->90分➗60＝1.5時間 でも少数点を切り捨てるから「１時間」
                                $breakDuration % 60　＝> ９０分➗６０分＝１余り0.5 => 「%」は余りのみを求めるから「３０分」と出る => sprintfは文字を整える関数。「%02d」　= 「d」->数字、「%02」->2桁で表示（前に0をつける） => 結果->休憩時間が90分の場合「１：３０」と表示される-->
                        @endif
                    </td>
                    <td>
                        @if ($totalDuration > 0)
                            {{ floor($totalDuration / 60) }}:{{ sprintf('%02d', $totalDuration % 60) }}
                        @endif
                    </td>
                    <td>
                        @if ($attendance)
                            @if($attendance->status === 'pending' && $attendance->latest_request)
                                <a href="{{ route('requested_confirm', ['request_id' => $attendance->latest_request->id]) }}" class="attendance-link">詳細</a>
                            @else
                                <a href="{{ route('attendance_detail', ['attendance_id' => $attendance->id]) }}" class="attendance-link">詳細</a>
                            @endif
                        @endif
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>
<div class="csv-export">
    <a href="{{ route('admin.staff_attendance_list.export', [
        'user_id' => $user->id,
        'year' => $year,
        'month' => $month
    ]) }}" class="csv-button">CSV出力</a>
</div>
@endsection