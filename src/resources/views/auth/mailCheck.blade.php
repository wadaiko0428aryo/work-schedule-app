<p>メールをご確認ください。ワンタイム認証コードが送信されています。</p>

<a href="http://127.0.0.1:8025/">メール</a>

<form method="POST" action="{{ route('auth') }}">
    @csrf
    <input type="hidden" name="email" value="{{ session('email') }}">
    <label>認証コード：</label>
    <input type="text" name="onetime_token">
    <button type="submit">送信</button>
</form>