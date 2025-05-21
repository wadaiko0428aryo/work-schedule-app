@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/request_list.css') }}">
@endsection

@section('content')
申請一覧画面（管理者）

<a href="{{ route('admin.approval') }}">承認画面</a>
@endsection