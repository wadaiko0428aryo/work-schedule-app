@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request_list.css') }}">
@endsection

@section('content')
<div class="attendance-title">
    申請一覧
</div>

<div class="request-link_group">
    <span class="request">
        <a href="{{ route('request_list', ['status' => 'pending']) }}" class="request-link">申請待ち</a>
    </span>
    <span class="request">
        <a href="{{ route('request_list', ['status' => 'approved']) }}" class="request-link">申請済み</a>
    </span>
</div>


<div class="attendance-table">
@if($attendances->isEmpty())
    <div class="request-message">
        {{ $status === '申請中' ? '申請中の勤怠情報はありません' : '承認済みの勤怠情報はありません' }}
    </div>
@else
<table border="1">
    <thead>
        <tr>
            <th>状態</th>
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
            $breakStart = $attendance->break_start_time ? \Carbon\Carbon::parse($attendance->break_start_time) : null;
            $breakEnd = $attendance->break_end_time ? \Carbon\Carbon::parse($attendance->break_end_time) : null;

            $breakDuration = ($breakStart && $breakEnd) ? $breakStart->diffInMinutes($breakEnd) : 0;
            $totalDuration = ($workStart && $workEnd) ? $workStart->diffInMinutes($workEnd) - $breakDuration : 0;

        @endphp

            <tr>
                <td></td>
                <td>{{ $attendance->user->name }} </td>
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
@endif
</div>


@endsection