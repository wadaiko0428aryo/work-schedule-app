@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="attendance-title">
    勤怠詳細
</div>

<form action="" method="post">
    @csrf
    <div class="attendance-edit_group">
        <div class="attendance-edit_input">
            <label for="name" class="attendance-label">名前</label>
            <div class="attendance-input">{{ $user->name }}</div>
        </div>
        <div class="attendance-edit_input">
            <label for="data" class="attendance-label">日付</label>
            <div class="attendance-input">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年m月d日') }}</div>
        </div>
        <div class="attendance-edit_input">
            <label for="start_time-end_time" class="attendance-label">出勤・退勤</label>
            <input type="time" name="start_time-end_time" id="start_time-end_time" value="{{ $attendance->start_time }}" placeholder="{{ $attendance->start_time }}" class="attendance-input">
            <span>〜</span>
            <input type="time" name="start_time-end_time" id="start_time-end_time" value="{{ $attendance->end_time }}" placeholder="{{ $attendance->end_time }}" class="attendance-input">
        </div>
        <div class="attendance-edit_input">
            <label for="break_start_time-break_end_time" class="attendance-label">休憩</label>
            <input type="time" name="break_start_time-break_end_time" id="break_start_time-break_end_time" value="{{ $attendance->break_start_time }}" placeholder="{{ $attendance->break_start_time }}" class="attendance-input">
            <span>〜</span>
            <input type="time" name="break_start_time-break_end_time" id="break_start_time-break_end_time" value="{{ $attendance->break_end_time }}" placeholder="{{ $attendance->break_end_time }}" class="attendance-input">
        </div>
        <div class="attendance-edit_input">
            <label for="break_start_time-break_end_time2" class="attendance-label">休憩2</label>
            <input type="time" name="break_start_time-break_end_time2" id="break_start_time-break_end_time2" value="" placeholder="" class="attendance-input">
            <span>〜</span>
            <input type="time" name="break_start_time-break_end_time2" id="break_start_time-break_end_time2" value="" placeholder="" class="attendance-input">
        </div>
        <div class="attendance-edit_input">
            <label for="reason" class="attendance-label">備考</label>
            <input type="text" name="reason" id="reason" placeholder="{{ old('reason') }}" class="attendance-input">
        </div>
    </div>
    <div class="edit-btn">
        <input type="submit" value="修正" class="edit-btn_submit">
    </div>
</form>
@endsection