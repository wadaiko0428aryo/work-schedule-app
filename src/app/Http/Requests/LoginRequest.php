<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ];
    }

    // passwordとemailが一致しない場合の処理
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $credentials = $this->only('email', 'password');

            // メールが存在するか確認
            $user = User::where('email', $credentials['email'])->first();

            if(!$user) {
                $validator->errors()->add('email', 'ログイン情報が登録されていません');
                return; //これ以上は確認しない
            }

            // メールが存在するのに認証失敗ならパスワード不一致
            if (!Auth::attempt($credentials)) {
                $validator->errors()->add('password', 'パスワードが一致しません');
            }
        });
    }

    public function messages()
    {
        return [
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => '有効なメールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは８文字以上で入力してください',
        ];
    }
}
