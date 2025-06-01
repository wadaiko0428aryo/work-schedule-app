<form method="POST" action="{{ route('auth') }}">
    @csrf
    <input type="hidden" name="email" value="{{ session('email') }}">
    <label>認証コード：</label>
    <input type="text" name="onetime_token">
    <button type="submit">送信</button>
</form>