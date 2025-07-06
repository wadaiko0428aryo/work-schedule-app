<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class MailhogUiTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_see_mail_in_mailhog()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register')
                    ->type('name', 'テストユーザー')
                    ->type('email', 'test@example.com')
                    ->type('password', 'password')
                    ->type('password_confirmation', 'password')
                    ->press('登録')
                    ->assertPathIs('/mail-check');

            // MailHog UI にアクセス
            $browser->visit('http://127.0.0.1:8025')
                    ->assertSee('test@example.com'); // メールアドレスが一覧に表示されているか
        });
    }
}
