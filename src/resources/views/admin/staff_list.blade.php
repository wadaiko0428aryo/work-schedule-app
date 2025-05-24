@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}">
@endsection

@section('content')
<div class="attendance-title">
    スタッフ一覧
</div>

<div class="attendance-table">
    <table border="1">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <a href="{{ route('admin.staff_attendance_list', ['user_id' => $user->id]) }}" class="attendance-link">詳細</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@endsection