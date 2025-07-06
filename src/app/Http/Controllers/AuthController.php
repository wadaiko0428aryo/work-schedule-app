<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\MailRequest;
use App\Models\User;
use App\Mail\TokenEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    // 登録処理
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $onetime_token = strval(rand(1000, 9999));
        $onetime_expiration = now()->addMinutes(10);

        $user->onetime_token = $onetime_token;
        $user->onetime_expiration = $onetime_expiration;
        $user->save();

        Mail::to($user->email)->send(new TokenEmail($user->email, $onetime_token));

        session([
            'email' => $user->email,
            'referer' => 'register',
        ]);

        return redirect()->route('mailCheck');
    }


    // メール送信確認ページ
    public function mailCheck()
    {
        return view('auth.mailCheck');
    }

    // ログイン処理（パスワード確認 → トークン送信）
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();

        if ($user && Hash::check($validated['password'], $user->password)) {

            // 管理者ならそのままログイン（トークン不要）
            if ($user->is_admin) {
                Auth::login($user);
                return redirect()->route('admin.attendance_list')->with('message', '管理者としてログインしました');
            }

            //  ここで「初回ログインかどうか」を判定
            if (!$user->has_logged_in) {
                $onetime_token = strval(rand(1000, 9999));
                $onetime_expiration = now()->addMinutes(10);

                $user->onetime_token = $onetime_token;
                $user->onetime_expiration = $onetime_expiration;
                $user->save();

                Mail::to($user->email)->send(new TokenEmail($user->email, $onetime_token));

                session([
                    'email' => $user->email,
                    'referer' => 'login',
                ]);

                return redirect()->route('mailCheck');
            }

            // 初回ログイン済みならそのままログイン
            Auth::login($user);
            return redirect()->route('attendance')->with('message', "{$user->name} さんとしてログインしました");
        }

        return back()->withErrors(['email' => 'ログイン情報が正しくありません'])->withInput();
    }

    // トークン認証処理
    public function auth(MailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user && $user->onetime_token == $request->onetime_token && now()->lessThanOrEqualTo($user->onetime_expiration)) {
            // ログイン成功
            Auth::login($user);

            // ★ ここで初回ログイン済みとして記録
            $user->has_logged_in = true;
            $user->onetime_token = null; // トークン破棄（任意）
            $user->onetime_expiration = null;
            $user->save();

            $referer = session('referer', 'login');

            if ($referer === 'register') {
                return redirect()->route('attendance');
            } else {
                return redirect()->route('attendance')->with('message', "{$user->name} さんとしてログインしました");
            }
        }

        return redirect()->route('mailCheck')->withErrors(['onetime_token' => '認証コードが正しくありません']);
    }

    // トークン付きメール送信（再送信・任意送信用）
    public function sendTokenEmail(Request $request)
    {
        $email = $request->email;
        $onetime_token = strval(rand(1000, 9999));
        $onetime_expiration = now()->addMinutes(3);

        $user = User::firstOrCreate(
            ['email' => $email],
            ['onetime_token' => $onetime_token, 'onetime_expiration' => $onetime_expiration]
        );

        // 既存ユーザーなら更新
        if (!$user->wasRecentlyCreated) {
            $user->update([
                'onetime_token' => $onetime_token,
                'onetime_expiration' => $onetime_expiration,
            ]);
        }

        Mail::to($email)->send(new TokenEmail($email, $onetime_token));

        session()->flash('email', $email);

        return view('auth.second-auth');
    }

    public function resendToken(Request $request)
    {
        $email = $request->email;
        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->back()->withErrors(['email' => 'ユーザーが見つかりません']);
        }

        $onetime_token = strval(rand(1000, 9999));
        $onetime_expiration = now()->addMinutes(10);

        $user->onetime_token = $onetime_token;
        $user->onetime_expiration = $onetime_expiration;
        $user->save();

        Mail::to($email)->send(new TokenEmail($email, $onetime_token));

        return back()->with('resent', true);
    }

}