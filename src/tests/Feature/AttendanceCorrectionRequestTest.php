<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\Rest;

class AttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目11：勤怠詳細情報修正機能（一般ユーザー）

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される。

    public function test_integrity_of_clock_in_and_out()
    {
        $user = User::create([
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $user->markEmailAsVerified();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-01-01',
            'clock_in' => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2026-01-01 12:00',
            'end_time' => '2026-01-01 13:00'
        ]);

        $this->actingAs($user);

        $response = $this->get("/attendance/detail/$attendance->id");
        $response->assertStatus(200);

        $inputData = [
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'staff_id' => $user->id,
            'attendance_start_time' => '18:00',
            'attendance_end_time' => '17:00',
            'rests' => [
                $rest->id => [
                    'start_time' => '12:00',
                    'end_time' => '13:00'
                ]
            ],
            'reason' => '打刻ミスの為'
        ];

        $response = $this->post('/attendance/request', $inputData);
        $response->assertStatus(302);

        dd(session()->get('errors')->getBag('default')->getMessages());

        /**
         * テストケース一覧の期待挙動には
         *「出勤時間が不適切な値です」とありますが、
         * 機能要件一覧の機能詳細に従い
         *「出勤時間もしくは退勤時間が不適切な値です」の
         * バリデーションメッセージで検証しています。
         */
        $response->assertSessionHasErrors([
            'attendance_end_time' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }
}
