@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-title attendance-list_title">
    勤怠一覧
</div>

<div class="date-navigation">
    <a href="{{ route('attendance_list', ['year' => $previousMonth->year, 'month' => $previousMonth->month]) }}" class="date-link">←前月</a>
    <span class="date">{{ $year }}/{{ $month }}</span>
    <a href="{{ route('attendance_list', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="date-link">翌月→</a>
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

                $breakDuration = 0;
                if ($attendance) {
                    foreach ($attendance->rests as $rest) {
                        if ($rest->break_start_time && $rest->break_end_time) {
                            $breakDuration += \Carbon\Carbon::parse($rest->break_start_time)
                                ->diffInMinutes(\Carbon\Carbon::parse($rest->break_end_time));
                        }
                    }
                }
                $totalDuration = ($workStart && $workEnd) ? $workStart->diffInMinutes($workEnd) - $breakDuration : 0;
            @endphp

                <tr>
                <td>
                    {{ $date->format('n/j') }}（{{ ['日','月','火','水','木','金','土'][$date->dayOfWeek] }}）
                </td>
                    <td>{{ $workStart ? $workStart->format('H:i') : '' }}</td>
                    <td>{{ $workEnd ? $workEnd->format('H:i') : '' }}</td>
                    <td>
                        @if ($breakDuration > 0)
                            {{ floor($breakDuration / 60) }}:{{ sprintf('%02d', $breakDuration % 60) }}
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
@endsection