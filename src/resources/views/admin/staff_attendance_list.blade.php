@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff_attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-title">
    {{$user->name}}さんの勤怠
</div>
<div class="date-navigation">
    <a href="{{ route('admin.staff_attendance_list', ['user_id' => $user->id, 'year' => $previousMonth->year, 'month' => $previousMonth->month]) }}">←前月</a>
    <span>{{ $year }}年{{ $month }}月</span>
    <a href="{{ route('admin.staff_attendance_list', ['user_id' => $user->id, 'year' => $nextMonth->year, 'month' => $nextMonth->month]) }}">翌月→</a>
</div>

<div class="attendance-table">
    <table border="1">
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
            @foreach($attendances as $attendance)
                @php
                    $workStart = \Carbon\Carbon::parse($attendance->start_time);
                    $workEnd = $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time) : null;
                    $breakStart = $attendance->break_start_time ? \Carbon\Carbon::parse($attendance->break_start_time) : null;
                    $breakEnd = $attendance->break_end_time ? \Carbon\Carbon::parse($attendance->break_end_time) : null;

                    $breakDuration = ($breakStart && $breakEnd) ? $breakStart->diffInMinutes($breakEnd) : 0;
                    $totalDuration = ($workStart && $workEnd) ? $workStart->diffInMinutes($workEnd) - $breakDuration : 0;
                @endphp

                <tr>
                    <td>{{ $attendance->date }}</td>
                    <td>{{ $workStart->format('H：i') }}</td>
                    <td>{{ $workEnd ? $workEnd->format('H：i') : '' }}</td>
                    <td>
                        @if ($breakStart && $breakEnd)
                            {{ floor($breakDuration / 60) }}:{{ $breakDuration % 60 }}
                        @endif
                    </td>
                    <td>
                        @if ($totalDuration > 0)
                            {{ floor($totalDuration / 60) }}:{{ $totalDuration % 60 }}
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