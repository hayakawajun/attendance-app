<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目1：認証機能（一般ユーザー）

    // 名前が未入力の場合、バリデーションメッセージが表示される。

    public function test_register_name_validation()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $inputData = [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'dummypass',
            'password_confirmation' => 'dummypass'
        ];

        $response = $this->post('/register', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください'
        ]);
    }

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される。

    public function test_register_email_validation()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $inputData = [
            'name' => 'テストネーム',
            'email' => '',
            'password' => 'dummypass',
            'password_confirmation' => 'dummypass'
        ];

        $response = $this->post('/register', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    // パスワードが8文字未満の場合、バリデーションメッセージが表示される。

    public function test_register_password_character_count_validation()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $inputData = [
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567'
        ];

        $response = $this->post('/register', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください'
        ]);
    }

    // パスワードが一致しない場合、バリデーションメッセージが表示される。

    public function test_register_password_match_validation()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $inputData = [
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => 'dummypass',
            'password_confirmation' => 'fakepass'
        ];

        $response = $this->post('/register', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'password_confirmation' => 'パスワードと一致しません'
        ]);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される。

    public function test_register_password_validation()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $inputData = [
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => 'dummypass'
        ];

        $response = $this->post('/register', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

    // フォームに内容が入力されていた場合、データが正常に保存される。

    public function test_registration_completed()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $inputData = [
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => 'dummypass',
            'password_confirmation' => 'dummypass'
        ];

        $response = $this->post('/register', $inputData);

        $this->assertDatabaseHas('users',[
            'name' => 'テストネーム',
            'email' => 'test@example.com'
        ]);

        $user = User::where('email','test@example.com')->first();
        $this->assertTrue(Hash::check('dummypass', $user->password));
    }
}
