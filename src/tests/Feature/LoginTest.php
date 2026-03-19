<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目2：ログイン認証機能（一般ユーザー）

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される。

    public function test_login_email_validation()
    {
        $user = User::create([
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $user->markEmailAsVerified();

        $response = $this->get('/login');
        $response->assertStatus(200);

        $inputData = [
            'email' => '',
            'password' => 'dummypass'
        ];

        $response = $this->post('/login', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される。

    public function test_login_password_validation()
    {
        $user = User::create([
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $user->markEmailAsVerified();

        $response = $this->get('/login');
        $response->assertStatus(200);

        $inputData = [
            'email' => 'test@example.com',
            'password' => ''
        ];

        $response = $this->post('/login', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    // 登録内容と一致しない場合、バリデーションメッセージが表示される。

    public function test_login_match_validation()
    {
        $user = User::create([
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $user->markEmailAsVerified();

        $response = $this->get('/login');
        $response->assertStatus(200);

        $inputData = [
            'email' => 'wrong-address@example.com',
            'password' => 'dummypass'
        ];

        $response = $this->post('/login', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'password' => 'ログイン情報が登録されていません'
        ]);
    }
}
