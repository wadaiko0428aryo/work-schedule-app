@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')

<div class="auth-group">
    <div class="auth-title">
        会員登録
    </div>

    <form action="{{ route('register') }}" method="post">
        @csrf
        <div class="auth-input">
            <label for="name" class="auth-label">名前</label>
            <input type="name" id="name" name="name" value="{{ old('name') }}" class="auth-input-data">
            <div class="error">
                @error('name')
                    
                @enderror
            </div>
        </div>
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
            <label for="password_confirmation" class="auth-label">パスワード</label>
            <input type="password" id="password_confirmation" name="password_confirmation" value="{{ old('password_confirmation') }}" class="auth-input-data">
            <div class="error">
                @error('password_confirmation')
                    error
                @enderror
            </div>
        </div>
        <div class="auth-input">
            <input type="submit" value="登録する" class="auth-btn">
            <a href="{{ route('login') }}" class="auth-link">ログインはこちら</a>
        </div>
    </form>
</div>
@endsection