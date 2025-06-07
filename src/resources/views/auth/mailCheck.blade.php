<p>メールをご確認ください。ワンタイム認証コードが送信されています。</p>

<a href="http://127.0.0.1:8025/" target="_blank">メール</a>

{{-- トークン入力フォーム --}}
<form method="POST" action="{{ route('auth') }}">
    @csrf
    <input type="hidden" name="email" value="{{ session('email') }}">
    <label>認証コード：</label>
    <input type="text" name="onetime_token">
    <button type="submit">送信</button>
</form>

{{-- 認証コード再送信ボタン --}}
<form method="POST" action="{{ route('resendToken') }}" style="margin-top: 20px;">
    @csrf
    <input type="hidden" name="email" value="{{ session('email') }}">
    <button type="submit">認証コードを再送信</button>
</form>

@if (session('resent'))
    <p style="color: green;">認証コードを再送信しました。</p>
@endif