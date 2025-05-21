@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}">
@endsection

@section('content')
スタッフ一覧画面（管理者）

<a href="{{ route('admin.staff_attendance_list') }}">スタッフ別勤怠一覧</a>
@endsection