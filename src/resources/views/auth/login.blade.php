@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="auth-group">
    <div class="auth-title">
        ログイン
    </div>

    <form action="{{ route('login') }}" method="post">
        @csrf
        <div class="auth-input">
            <label for="email" class="auth-label">メールアドレス</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" class="auth-input-data">
            <div class="error">
                @error('email')
                    error
                @enderror
            </div>
        </div>
        <div class="auth-input">
            <label for="password" class="auth-label">パスワード</label>
            <input type="password" id="password" name="password" value="{{ old('password') }}" class="auth-input-data">
            <div class="error">
                @error('password')
                    error
                @enderror
            </div>
        </div>
        <div class="auth-input">
            <input type="submit" value="ログインする" class="auth-btn">
            <a href="{{ route('register') }}" class="auth-link">会員登録はこちら</a>
        </div>
    </form>
</div>
@endsection