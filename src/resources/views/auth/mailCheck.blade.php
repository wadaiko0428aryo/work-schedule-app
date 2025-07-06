@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')

<div class="mail-messages">
    <p class="mail-message">登録していただいたメールアドレスに認証パスワードを送付しました。</p><p class="mail-message">メール認証を完了してください</p>
</div>

<a href="http://127.0.0.1:8025/" target="_blank" class="mail-check">認証メールを確認</a>

{{-- トークン入力フォーム --}}
<form method="POST" action="{{ route('auth') }}" >
    @csrf
    <div class="token-group">
        <input type="hidden" name="email" value="{{ session('email') }}">
        <label class="token-label">認証コード</label>
        <div class="token-child">
            <input type="text" name="onetime_token" class="token-input">
            <button type="submit" class="token-btn">送信</button>
        </div>
        <div class="error token-error">
            @error('onetime_token')
                {{ $message }}
            @enderror
        </div>
    </div>
</form>

<div class="mail-link_group">
    {{-- 認証コード再送信ボタン --}}
    <form method="POST" action="{{ route('resendToken') }}" style="margin-top: 20px;">
        @csrf
        <input type="hidden" name="email" value="{{ session('email') }}">
        <button type="submit" class="mail-link">認証コードを再送信</button>
    </form>



    @if (session('resent'))
        <p class="message">認証コードを再送信しました。</p>
    @endif
</div>

@endsection