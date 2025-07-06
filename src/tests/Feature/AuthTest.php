<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\TokenEmail;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ーー会員登録テストーー

    //name未入力
    public function test_register_validation_error_when_name_is_empty()
    {
        // registerページを表示
        $this->get('/register');

        // 名前のみ未入力にし、postリクエストを送る
        $response = $this->post('/register',[
            'name'  => '',
            'email' => 'test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // nameのエラーメッセージが表示されているか
        $response->assertSessionHasErrors('name');

        // リダイレクト先は適切か
        $response->assertStatus(302);

        $response->assertRedirect('/register');

    }

    // emailが未入力
    public function test_register_validation_error_when_email_is_empty()
    {
        $this->get('/register');

        $response = $this->post('/register', [
            'name' => 'test',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertStatus(302);
        $response->assertRedirect('/register');
    }

    // passwordが未入力
    public function test_register_validation_error_when_password_is_empty()
    {
        $this->get('/register');
        $response = $this->post('/register', [
            'name' => 'test',
            'email' => 'test@gmail.com',
            'password' => '',
            'password_confirmation' => '',
        ]);
        $response->assertSessionHasErrors('password');
        $response->assertStatus(302);
        $response->assertRedirect('/register');
    }

    // passwordが８文字未満
    public function test_register_validation_error_when_password_is_8characters_or_less()
    {
        $this->get('/register');
        $response = $this->post('/register', [
            'name' => 'test',
            'email' => 'test@gmail.com',
            'name' => 'passwor',
            'name' => 'passwor',
        ]);

        $response->assertSessionHasErrors('password');
        $response->assertStatus(302);
        $response->assertRedirect('/register');
    }

    // passwordとpassword_confirmationが不一致
    public function test_register_validation_error_when_password_and_password_confirmation_mismatch()
    {
        $this->get('/register');
        $response = $this->post('/register', [
            'name' => 'test',
            'email' => 'test@gmail.com',
            'name' => 'password',
            'name' => 'testpass',
        ]);

        $response->assertSessionHasErrors('password');
        $response->assertStatus(302);
        $response->assertRedirect('/register');
    }

    // 会員登録成功
    public function test_register_validation_success()
    {
        $this->get('/register');
        $response = $this->post('/register', [
            'name' => 'test',
            'email' => 'test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // ユーザーがDBに存在しているか確認
        $this->assertDatabaseHas('users', [
            'email' => 'test@gmail.com',
        ]);
        $response->assertRedirect(route('mailCheck'));
    }


    // ーーログインテストーー

    public function test_login_validation_error_when_email_is_empty()
    {
        $this->get('/login');
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);
        $response->assertSessionHasErrors('email');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_login_validation_error_when_password_is_empty()
    {
        $this->get('/login');
        $response = $this->post('/login', [
            'email' => 'test@gmail.com',
            'password' => '',
        ]);
        // errorキーが存在するか確認
        $response->assertSessionHasErrors('password');
        // 実際のエラーメッセージを取り出して確認
        $errors = session('errors')->get('password');
        $this->assertContains('パスワードを入力してください', $errors);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_login_validation_error_when_email_and_password_mismatch()
    {
        // 登録済みのユーザー
        $user =  User::factory()->create([
            'email' => 'test@gmail.com',
            'password' => bcrypt('password'),
        ]);

        $this->get('/login');
        $response = $this->post('/login', [
            'email' => 'test@gmail.com',
            'password' => 'testpass',
        ]);
        $response->assertSessionHasErrors(['password']);

        $response->assertStatus(302);
        $response->assertRedirect();
    }

    //認証メールの送信
    public function test_sending_a_verification_email()
    {
        // メール送信をフェイク（実際には送らない）
        Mail::fake();

        // 登録データ
        $formData = [
            'name' => 'テスト',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        //登録リクエストを送信
        $response = $this->post(route('register'), $formData);

        // メールが送信されたか確認
        Mail::assertSent(TokenEmail::class, function ($email) use ($formData) {
            return $email->hasTo($formData['email']);
        });

        // redirect先の確認
        $response->assertRedirect(route('mailCheck'));

        // ユーザーがDBに存在するか確認
        $this->assertDatabaseHas('users', [
            'email' => $formData['email'],
        ]);

    }


}
