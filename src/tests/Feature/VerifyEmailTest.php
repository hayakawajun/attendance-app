<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    // テスト項目16：メール認証機能

    // 会員登録後、認証メールが送信される。

    public function test_verify_email_send()
    {
        Notification::fake();

        $response = $this->get('/register');
        $response->assertStatus(200);

        $inputData = [
            'name'                  => 'テストネーム',
            'email'                 => 'test@example.com',
            'password'              => 'dummypass',
            'password_confirmation' => 'dummypass'
        ];

        $response = $this->post('/register', $inputData);
        $response->assertStatus(302);
        $response->assertRedirect('http://localhost/email/verify');

        $user = User::where('email','test@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    // メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する。

    public function test_verify_email_url_check()
    {
        Notification::fake();

        $response = $this->get('/register');
        $response->assertStatus(200);

        $inputData = [
            'name'                  => 'テスト',
            'email'                 => 'test@example.com',
            'password'              => 'dummypass',
            'password_confirmation' => 'dummypass'
        ];

        $response = $this->post('/register', $inputData);
        $response->assertStatus(302);
        $response->assertRedirect('http://localhost/email/verify');

        $user = User::where('email','test@example.com')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, VerifyEmail::class);

        $response = $this->get('/email/verify');
        $response->assertStatus(200);

        $response->assertViewIs('auth.verify_email');

        $response->assertSee([
            '<a class="mail__verification" href="http://localhost:8025/">認証はこちらから</a>'
        ], false);

        /** mailhogへのアクセスはできないため、
         *  以下の方法で「メール認証サイトへの遷移〜認証完了」を擬似検証しています。
         *  - 実際に送信された通知から認証URLを取り出して、そのURLにアクセスして認証を完了。
         *  - 最後にusersテーブルのemail_verified_atがnullでなくなったことを確認。
         */

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            function ($notification, $channels) use ($user) {
                $mailData = $notification->toMail($user);
                $verificationUrl = $mailData->actionUrl;

                $response = $this->actingAs($user)->get($verificationUrl);
                $response->assertStatus(302);

                return true;
            }
        );

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    // メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する。

    public function test_verify_email_redirect_attendance_view()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);

        $inputData = [
            'name'                  => 'テスト',
            'email'                 => 'test@example.com',
            'password'              => 'dummypass',
            'password_confirmation' => 'dummypass'
        ];

        $response = $this->post('/register',$inputData);

        $response->assertRedirect('/email/verify');

        $user = User::where('email','test@example.com')->first();

        /** 以下でメール認証を擬似的に行っています。
         *  その後、認証済みであることを確認し、勤怠登録画面への遷移を検証しています。
         */

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->get($verificationUrl);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $response->assertRedirect('/attendance');

        $response = $this->get('/attendance');
        $response->assertStatus(200);

        $response->assertViewIs('attendance');

        $response->assertSeeInOrder([
            '勤務外',
            '<button class="attendance__submit-btn work">出勤</button>'
        ], false);
    }
}