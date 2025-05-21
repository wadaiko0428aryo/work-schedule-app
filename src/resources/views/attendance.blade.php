@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_register.css') }}">
@endsection

@section('content')

<div class="attendance-check">
    <div class="attendance-check_label">
        @if($status === 'before_work')
            勤務外
        @elseif($status === 'working')
            勤務中
        @elseif($status === 'on_break')
            休憩中
        @elseif($status === 'finished')
            退勤済
        @endif
    </div>
</div>

<div class="attendance-date">
    <div id="current-date" style="font-size: 1.2rem; font-weight: bold;"></div>
</div>

<div class="attendance-time">
    <div id="clock" style="font-size: 1.5rem; font-weight: bold;"></div>
</div>

<div class="attendance-buttons">
    @if($status === 'before_work')
        <form method="POST" action="{{ route('attendance.start') }}">
            @csrf
            <button type="submit">出勤</button>
        </form>
    @elseif($status === 'working')
        <form method="POST" action="{{ route('attendance.break') }}">
            @csrf
            <button type="submit">休憩</button>
        </form>
        <form method="POST" action="{{ route('attendance.end') }}">
            @csrf
            <button type="submit">退勤</button>
        </form>
    @elseif($status === 'on_break')
        <form method="POST" action="{{ route('attendance.resume') }}">
            @csrf
            <button type="submit">休憩戻</button>
        </form>
    @elseif($status === 'finished')
        <p>お疲れ様でした。</p>
    @endif

</div>



<!-- JavaScriptでリアルタイムに更新 -->
<script>
    function updateClock() {
        const now = new Date();

        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        const formattedTime = `${hours}:${minutes}`;

        document.getElementById('clock').textContent = formattedTime;
    }

    // 1分ごとに更新
    setInterval(updateClock, 60 * 1000);

    // 初回表示
    updateClock();
</script>

<!-- JavaScriptでリアルタイムの日付に更新 -->
<script>
    function updateDate() {
        const now = new Date();

        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0'); // 月は0スタート
        const day = String(now.getDate()).padStart(2, '0');
        const weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        const weekday = weekdays[now.getDay()];

        const formattedDate = `${year}年${month}月${day}日（${weekday}）`;

        document.getElementById('current-date').textContent = formattedDate;
    }

    updateDate();
</script>

@endsection

