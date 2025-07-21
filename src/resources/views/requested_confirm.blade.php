@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')

@section('session')
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
@endsection


<div class="attendance-title">
    勤怠詳細
</div>

@php
    $latestRequest = $attendance->requests()
    ->latest()
    ->first();
    $editData = $request->edit_data ? json_decode($request->edit_data, true) : [];


    $startTime = !empty($editData['start_time'])
        ? \Carbon\Carbon::parse($editData['start_time'])
        : ($attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time) : null);

    $endTime = !empty($editData['end_time'])
        ? \Carbon\Carbon::parse($editData['end_time'])
        : ($attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time) : null);

        $breakRequests = $request->breakRequests ?? [];
@endphp

<form method="post" action="{{ route('admin.attendance.approve', ['attendance_id' => $attendance->id]) }}" class="form">
    @csrf

    <div class="attendance-edit_group">

        <div class="attendance-edit_input">
            <label class="attendance-label">名前</label>
            <div class="attendance-input_read-only">{{ $attendance->user->name }}</div>
        </div>

        <div class="attendance-edit_input">
            <label class="attendance-label">日付</label>
            <div class="attendance-input_read-only">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年m月d日') }}</div>
        </div>

        <div class="attendance-edit_input">
            <label class="attendance-label">出勤・退勤</label>
            <div class="attendance-input-wrapper attendance-input_read-only">
            {{ $startTime ? $startTime->format('H:i') : '--:--' }}
                <span>〜</span>
                {{ $endTime ? $endTime->format('H:i') : '--:--' }}
            </div>
        </div>

    @foreach($request->breakRequests as $index =>$break)
        <div class="attendance-edit_input">
            <label class="attendance-label">休憩{{ $index + 1 }}</label>
            <div class="attendance-input-wrapper attendance-input_read-only">
                {{ $break->requested_break_start_time ? \Carbon\Carbon::parse($break->requested_break_start_time)->format('H:i') : '--:--' }}
                <span>〜</span>
                {{ $break->requested_break_end_time ? \Carbon\Carbon::parse($break->requested_break_end_time)->format('H:i') : '--:--' }}
            </div>
        </div>
    @endforeach

        <div class="attendance-edit_input">
            <label class="attendance-label">備考</label>
            <div class="attendance-input-wrapper attendance-input_read-only">
                {{ $latestRequest->requested_reason ?? '未入力' }}
            </div>
        </div>

    </div>

    <div class="edit-btn">
        @if($user->is_admin)
            @if($attendance->status === 'approved')
                <span class="edit-btn_submit approved">承認済み</span>
            @else
                <input type="submit" value="承認" class="edit-btn_submit">
            @endif
        @else
            @if($attendance->status === 'approved')
                <p class="edit-btn_submit approved">
                    承認済み
                </p>
            @else
                <p class="edit-btn_alert">
                    ※ 承認待ちのため修正はできません。
                </p>
            @endif
        @endif
    </div>

</form>

@endsection