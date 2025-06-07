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
        <a href="{{ route('request_list', ['status' => 'approved']) }}" class="request-link">承認済み</a>
    </span>
</div>

<div class="attendance-table">

<table border="1">
    <thead>
        <tr>
            <th>状態</th>
            <th>名前</th>
            <th>対象日時</th>
            <th>申請理由</th>
            <th>申請日時</th>
            <th>詳細</th>
        </tr>
    </thead>
    @if($requests->isEmpty())
        </table>
        <div class="request-message">
            {{ $status === 'pending' ? '申請中の勤怠情報はありません' : '承認済みの勤怠情報はありません' }}
        </div>
    @else
    <tbody>
        @foreach($requests as $request)
        @php
            $attendance = $request->attendance;

            $workStart = \Carbon\Carbon::parse($attendance->start_time);
            $workEnd = $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time) : null;
            $breakStart = $attendance->break_start_time ? \Carbon\Carbon::parse($attendance->break_start_time) : null;
            $breakEnd = $attendance->break_end_time ? \Carbon\Carbon::parse($attendance->break_end_time) : null;

            $breakDuration = ($breakStart && $breakEnd) ? $breakStart->diffInMinutes($breakEnd) : 0;
            $totalDuration = ($workStart && $workEnd) ? $workStart->diffInMinutes($workEnd) - $breakDuration : 0;
        @endphp
        <tr>
            <td>{{ $request->status === 'pending' ? '申請中' : '承認済み' }}</td>
            <td>{{ $request->user->name }}</td>
            <td>{{ $request->attendance->date }}</td>
            <td>{{ $request->requested_reason }}</td>
            <td>{{ $request->updated_at }}</td>
            <td>
                @if($user->is_admin)
                    <a href="{{ route('requested_confirm', ['request_id' => $request->id]) }}">確認</a>
                @else
                    <a href="{{ route('requested_confirm', ['request_id' => $request->id]) }}" class="attendance-link">詳細</a>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif
</div>
@endsection