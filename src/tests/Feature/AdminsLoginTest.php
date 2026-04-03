<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminsLoginTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目3：ログイン認証機能（管理者）

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される。

    public function test_admins_login_email_validation()
    {
        Admin::create([
            'name'     => '管理者テストネーム',
            'email'    => 'admin-test@example.com',
            'password' => Hash::make('adminpass')
        ]);

        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        $inputData = [
            'email'    => '',
            'password' => 'adminpass'
        ];

        $response = $this->post('/admin/login', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される。

    public function test_admins_login_password_validation()
    {
        Admin::create([
            'name'     => '管理者テストネーム',
            'email'    => 'admin-test@example.com',
            'password' => Hash::make('adminpass')
        ]);

        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        $inputData = [
            'email'    => 'admin-test@example.com',
            'password' => ''
        ];

        $response = $this->post('/admin/login', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    // 登録内容と一致しない場合、バリデーションメッセージが表示される。

    public function test_admins_login_match_validation()
    {
        Admin::create([
            'name'     => '管理者テストネーム',
            'email'    => 'admin-test@example.com',
            'password' => Hash::make('adminpass')
        ]);

        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        $inputData = [
            'email'    => 'wrong-address@example.com',
            'password' => 'adminpass'
        ];

        $response = $this->post('/admin/login', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'password' => 'ログイン情報が登録されていません'
        ]);
    }
}