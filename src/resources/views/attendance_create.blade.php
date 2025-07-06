<!-- @extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection


@section('session')
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
@endsection


@section('content')

<div class="attendance-title">
    勤怠詳細
</div>

    <form action="{{ route('admin.attendance_store') }}" method="post" class="form">
        @csrf

        <div class="attendance-edit_group">
            <div class="attendance-edit_input">
                <label for="name" class="attendance-label">名前</label>
                <div class="attendance-input_read-only">{{ $attendance->user->name }}</div>
            </div>
            <div class="attendance-edit_input">
                <label for="data" class="attendance-label">日付</label>
                <div class="attendance-input_read-only">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年m月d日') }}</div>
            </div>
            <div class="attendance-edit_input">
                <label for="start_time" class="attendance-label">出勤・退勤</label>
                <div class="attendance-input-wrapper">

                    <div class="column">
                        <input type="time" name="start_time" id="start_time" value="{{ \Carbon\Carbon::parse($attendance->start_time)->format('H:i') }}" class="attendance-input">
                        @error('start_time')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <span>〜</span>

                    <div class="column">
                        <input type="time" name="end_time" id="end_time" value="{{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}" class="attendance-input">
                        @error('end_time')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <div class="attendance-rests-wrapper">
                @foreach($rests as $index => $rest)
                    <div class="rest-block">
                        <div class="attendance-input-wrapper">
                            <label class="attendance-label">休憩{{ $index + 1 }}</label>

                            <div class="column">
                                <input type="time" name="break_start_time[]" value="{{ $rest['break_start_time'] ? \Carbon\Carbon::parse($rest['break_start_time'])->format('H:i') : '' }}" class="attendance-input">
                                @error('break_start_time' . $index)
                                    <div class="error">{{ $message }}</div>
                                @enderror
                            </div>

                            <span>〜</span>

                            <div class="column">
                                <input type="time" name="break_end_time[]" value="{{ $rest['break_end_time'] ? \Carbon\Carbon::parse($rest['break_end_time'])->format('H:i') : '' }}" class="attendance-input">
                                @error('break_end_time' . $index)
                                    <div class="error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>



            <div class="attendance-edit_input">
                <label for="reason" class="attendance-label">備考</label>
                <div class="attendance-input-wrapper">
                    <div class="column">
                        <input type="text" name="reason" id="reason" value="{{ old('reason', $attendance->reason) }}" class="attendance-input">
                        @error('reason')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="edit-btn">
            <input type="submit" value="作成" class="edit-btn_submit">
        </div>
    </form>




@endsection -->