<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceRequest;
use App\Models\Admin;

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
        $user = User::factory()->create(['email_verified_at' => now()]);

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

    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される。

    public function test_integrity_of_rest_start_and_clock_out()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

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
            'attendance_start_time' => '08:00',
            'attendance_end_time' => '17:00',
            'rests' => [
                $rest->id => [
                    'start_time' => '18:00',
                    'end_time' => '13:00'
                ]
            ],
            'reason' => '打刻ミスの為'
        ];

        $response = $this->post('/attendance/request', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            "rests.{$rest->id}.start_time" => '休憩時間が不適切な値です'
        ]);
    }

    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される。

    public function test_integrity_of_rest_end_and_clock_out()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

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
            'attendance_start_time' => '08:00',
            'attendance_end_time' => '17:00',
            'rests' => [
                $rest->id => [
                    'start_time' => '12:00',
                    'end_time' => '18:00'
                ]
            ],
            'reason' => '打刻ミスの為'
        ];

        $response = $this->post('/attendance/request', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            "rests.{$rest->id}.end_time" => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    // 備考欄が未入力の場合エラーメッセージが表示される。

    public function test_reason_validation()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

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
            'attendance_start_time' => '10:00',
            'attendance_end_time' => '20:00',
            'rests' => [
                $rest->id => [
                    'start_time' => '12:00',
                    'end_time' => '13:00'
                ]
            ],
            'reason' => null
        ];

        $response = $this->post('/attendance/request', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            "reason" => '備考を記入してください'
        ]);
    }

    // 修正申請処理が実行される。

    public function test_execute_correction_request()
    {
        $knownDate = now()->parse('2026-02-22 10:00:00');
        $this->travelTo($knownDate);

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
            'attendance_start_time' => '08:08',
            'attendance_end_time' => '17:17',
            'rests' => [
                $rest->id => [
                    'start_time' => '12:12',
                    'end_time' => '13:13'
                ]
            ],
            'reason' => '申請テスト'
        ];

        $response = $this->post('/attendance/request', $inputData);
        $response->assertStatus(302);

        $attendanceRequest = AttendanceRequest::latest('id')->first();

        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin,'admin')
            ->get("/stamp_correction_request/approve/{$attendanceRequest->id}");
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            'テストネーム',
            '2026年','1月1日',
            '出勤・退勤','08:08','〜','17:17',
            '休憩','12:12','〜','13:13',
            '備考','申請テスト'
        ], false);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '承認','待ち','テストネーム','2026','01/01','申請テスト','2026','02/22','詳細'
        ], false);
    }

    // 「承認待ち」にログインユーザーが行った申請が全て表示されている。

    public function test_display_all_pending_requests()
    {
        $knownDate = now()->parse('2026-02-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::create([
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $user->markEmailAsVerified();

        $firstAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-01-01',
            'clock_in' => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);
        $secondAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-01',
            'clock_in' => '2026-02-01 08:00',
            'clock_out' => '2026-02-01 17:00'
        ]);

        $this->actingAs($user);

        $response = $this->get("/attendance/detail/$firstAttendance->id");
        $response->assertStatus(200);

        $firstAttendanceInputData = [
            'attendance_id' => $firstAttendance->id,
            'work_date' => $firstAttendance->work_date,
            'staff_id' => $user->id,
            'attendance_start_time' => '11:11',
            'attendance_end_time' => '22:22',
            'reason' => '申請その1'
        ];

        $response = $this->post('/attendance/request', $firstAttendanceInputData);
        $response->assertStatus(302);

        $this->travelTo($knownDate->addMonth(1));

        $response = $this->get("/attendance/detail/$secondAttendance->id");
        $response->assertStatus(200);

        $secondAttendanceInputData = [
            'attendance_id' => $secondAttendance->id,
            'work_date' => $secondAttendance->work_date,
            'staff_id' => $user->id,
            'attendance_start_time' => '11:11',
            'attendance_end_time' => '22:22',
            'reason' => '申請その2'
        ];

        $response = $this->post('/attendance/request', $secondAttendanceInputData);
        $response->assertStatus(302);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '<div class="pending__tab-content">',
            '承認','待ち','テストネーム','2026','02/01','申請その2','2026','03/01','詳細',
            '承認','待ち','テストネーム','2026','01/01','申請その1','2026','02/01','詳細'
        ], false);
    }

    // 「承認済み」に管理者が承認した修正申請が全て表示されている。

    public function test_display_all_approved_requests()
    {
        $knownDate = now()->parse('2026-02-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::create([
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $user->markEmailAsVerified();

        $firstAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-01-01',
            'clock_in' => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);
        $secondAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-01',
            'clock_in' => '2026-02-01 08:00',
            'clock_out' => '2026-02-01 17:00'
        ]);

        $this->actingAs($user);

        $response = $this->get("/attendance/detail/$firstAttendance->id");
        $response->assertStatus(200);

        $firstAttendanceInputData = [
            'attendance_id' => $firstAttendance->id,
            'work_date' => $firstAttendance->work_date,
            'staff_id' => $user->id,
            'attendance_start_time' => '11:11',
            'attendance_end_time' => '22:22',
            'reason' => '申請その1'
        ];

        $response = $this->post('/attendance/request', $firstAttendanceInputData);
        $response->assertStatus(302);

        $this->travelTo($knownDate->addMonth(1));

        $response = $this->get("/attendance/detail/$secondAttendance->id");
        $response->assertStatus(200);

        $secondAttendanceInputData = [
            'attendance_id' => $secondAttendance->id,
            'work_date' => $secondAttendance->work_date,
            'staff_id' => $user->id,
            'attendance_start_time' => '11:11',
            'attendance_end_time' => '22:22',
            'reason' => '申請その2'
        ];

        $response = $this->post('/attendance/request', $secondAttendanceInputData);
        $response->assertStatus(302);

        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin,'admin')
            ->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $firstAttendanceRequest = AttendanceRequest::where('attendance_id', $firstAttendance->id)
            ->first();
        $secondAttendanceRequest = AttendanceRequest::where('attendance_id', $secondAttendance->id)
            ->first();

        $response = $this->get("/stamp_correction_request/approve/{$firstAttendanceRequest->id}");
        $response->assertStatus(200);
        $response = $this->post("/stamp_correction_request/approve/{$firstAttendanceRequest->id}");
        $response->assertStatus(302);

        $response = $this->get("/stamp_correction_request/approve/{$secondAttendanceRequest->id}");
        $response->assertStatus(200);
        $response = $this->post("/stamp_correction_request/approve/{$secondAttendanceRequest->id}");
        $response->assertStatus(302);

        $response = $this->actingAs($user,'web')
            ->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '<div class="approved__tab-content">',
            '承認','済み','テストネーム','2026','02/01','申請その2','2026','03/01','詳細',
            '承認','済み','テストネーム','2026','01/01','申請その1','2026','02/01','詳細'
        ], false);
    }

    // 各申請の「詳細」を押下すると勤怠詳細画面に遷移する。

    public function test_move_to_detail_page()
    {
        $knownDate = now()->parse('2026-02-22 10:00:00');
        $this->travelTo($knownDate);

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
            'attendance_start_time' => '08:08',
            'attendance_end_time' => '17:17',
            'rests' => [
                $rest->id => [
                    'start_time' => '12:12',
                    'end_time' => '13:13'
                ]
            ],
            'reason' => '申請テスト'
        ];

        $response = $this->post('/attendance/request', $inputData);
        $response->assertStatus(302);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $detailUrl = route('detail.show',['id' => $attendance->id ]);

        $response->assertSeeInOrder([
            '承認','待ち','テストネーム','2026','01/01','申請テスト','2026','02/22',
            '<a class="detail__link"', $detailUrl,'詳細','</a>'
        ], false);

        $response = $this->get($detailUrl);

        $response->assertSeeInOrder([
            '勤怠詳細',
            'テストネーム',
            '2026年','1月1日',
            '出勤・退勤','08:08','〜','17:17',
            '休憩','12:12','〜','13:13',
            '*承認待ちのため修正はできません。'
        ], false);
    }
}
