@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-title">
    {{ $currentDate->format('Y年n月j日') }}の勤怠
</div>


<div class="date-navigation">
    <a href="{{ route('admin.attendance_list', ['date' => $previousDate]) }}" class="date-link">←前日</a>
    <span class="date">{{ $date->format('Y/m/d') }}</span>
    <a href="{{ route('admin.attendance_list', ['date' => $nextDate]) }}" class="date-link">翌日→</a>
</div>

<div class="attendance-table">
<table border="1">
    <thead>
        <tr>
            <th>名前</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th>詳細</th>
        </tr>
    </thead>
    <tbody>
        @foreach($attendances as $attendance)
        @php
            $workStart = \Carbon\Carbon::parse($attendance->start_time);
            $workEnd = $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time) : null;

            $totalBreakMinutes = 0;
            foreach ($attendance->rests as $rest) {
                $totalBreakMinutes += \Carbon\Carbon::parse($rest->break_end_time)
                    ->diffInMinutes(\Carbon\Carbon::parse($rest->break_start_time));
            }

            $totalDuration = ($workStart && $workEnd) ? $workStart->diffInMinutes($workEnd) - $totalBreakMinutes : 0;

            $currentDate = \Carbon\Carbon::parse($date);
            $previousDate = $currentDate->copy()->subDay()->toDateString();
            $nextDate = $currentDate->copy()->addDay()->toDateString();
        @endphp

            <tr>
                <td>{{ $attendance->user->name }}</td>
                <td>{{ $workStart->format('H:i') }}</td>
                <td>{{ $workEnd ? $workEnd->format('H:i') : '' }}</td>
                <td>
                    @if ($totalBreakMinutes > 0)
                        {{ floor($totalBreakMinutes / 60) }}：{{ sprintf('%02d', $totalBreakMinutes % 60) }}
                    @endif
                </td>
                <td>
                    @if ($totalDuration > 0)
                        {{ floor($totalDuration / 60) }}：{{ sprintf('%02d', $totalDuration % 60) }}
                    @endif
                </td>
                <td>
                    <a href="{{ route('attendance_detail', ['attendance_id' => $attendance->id]) }}" class="attendance-link">詳細</a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>


@endsection