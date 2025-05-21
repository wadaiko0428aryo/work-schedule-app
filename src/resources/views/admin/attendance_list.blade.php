@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}">
@endsection

@section('content')
勤怠一覧画面（管理者）
<a href="{{ route('admin.attendance_detail') }}">勤怠詳細画面</a>
@endsection