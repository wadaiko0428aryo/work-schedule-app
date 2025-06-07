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
    $latestRequest = $attendance->requests()->latest()->first();
    $editData = $latestRequest ? json_decode($latestRequest->edit_data, true) : [];
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
                {{ isset($editData['start_time']) ? \Carbon\Carbon::parse($editData['start_time'])->format('H:i') : '--:--' }}
                <span>〜</span>
                {{ isset($editData['end_time']) ? \Carbon\Carbon::parse($editData['end_time'])->format('H:i') : '--:--' }}
            </div>
        </div>

        <div class="attendance-edit_input">
            <label class="attendance-label">休憩</label>
            <div class="attendance-input-wrapper attendance-input_read-only">
                {{ isset($editData['break_start_time']) ? \Carbon\Carbon::parse($editData['break_start_time'])->format('H:i') : '--:--' }}
                <span>〜</span>
                {{ isset($editData['break_end_time']) ? \Carbon\Carbon::parse($editData['break_end_time'])->format('H:i') : '--:--' }}
            </div>
        </div>

        <div class="attendance-edit_input">
            <label class="attendance-label">備考</label>
            <div class="attendance-input-wrapper attendance-input_read-only">
                {{ $latestRequest->requested_reason ?? '未入力' }}
            </div>
        </div>

    </div>

    @if($user->is_admin)
        <div class="edit-btn">
            @if($attendance->status === 'approved')
                <span class="edit-btn_submit approved">承認済み</span>
            @else
                <input type="submit" value="承認" class="edit-btn_submit">
            @endif
        </div>
    @else
        <div class="edit-btn">
            <p class="edit-btn_alert">
                ※ 承認待ちのため修正はできません。
            </p>
        </div>
    @endif

</form>

@endsection