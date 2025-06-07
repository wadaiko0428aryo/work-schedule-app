@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/approval.css') }}">
@endsection

@section('content')
申請修正承認画面（管理者）

<form method="POST" action="{{ route('admin.attendance.approve', $attendance->id) }}">
    @csrf
    <button type="submit">承認</button>
</form>

@endsection