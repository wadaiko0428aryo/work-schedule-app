@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
勤怠一覧画面（一般）

<a href="{{ route('attendance_detail') }}">詳細</a>
@endsection