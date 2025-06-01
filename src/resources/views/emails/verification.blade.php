@component('mail::message')
# メールアドレス確認

{{ $user->name }}さん、こんにちは。

以下のリンクをクリックしてメールアドレスを確認してください。

@component('mail::button', ['url' => route('verify.token', ['token' => $user->verification_token])])
メールアドレス確認
@endcomponent

ありがとうございます。  
{{ config('app.name') }}
@endcomponent