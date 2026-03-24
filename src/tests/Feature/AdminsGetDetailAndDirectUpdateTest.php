<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\Admin;

class AdminsGetDetailAndDirectUpdateTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目13：勤怠詳細情報取得・修正機能（管理者）

    // 勤怠詳細画面に表示されるデータが選択したものになっている。

    public function test_details_view_display_selected_attendance()
    {
        $staff = User::create([
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $staff->markEmailAsVerified();

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2026-01-01',
            'clock_in' => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2026-01-01 12:00',
            'end_time' => '2026-01-01 13:00'
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $year = 2026;
        $month = 1;

        $response = $this->get("/admin/attendance/staff/{$staff->id}/{$year}/{$month}");
        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'テストネームさんの勤怠',
            '<span class="target-date">','2026/01','</span>'
        ], false);

        $targetUrl = route('admin.show_detail',['id' => $attendance->id ]);

        $response->assertSeeInOrder([
            '<a class="detail__link"', $targetUrl,'詳細','</a>'
        ], false);

        $response = $this->get($targetUrl);
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '勤怠詳細',
            '<td class="label">名前</td>','テストネーム',
            '<td class="label">日付</td>','2026年','1月1日',
            '<td class="label">出勤・退勤</td>','08:00','〜','17:00',
            '<td class="label">休憩</td>','12:00','〜','13:00'
        ], false);
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される。

    public function test_integrity_of_clock_in_and_out()
    {
        $staff = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2026-01-01',
            'clock_in' => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $response = $this->get("/admin/attendance/{$staff->id}");
        $response->assertStatus(200);

        $inputData = [
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'staff_id' => $staff->id,
            'attendance_start_time' => '18:00',
            'attendance_end_time' => '17:00',
            'reason' => '打刻ミスの為'
        ];

        $response = $this->post('/admin/direct_update', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'attendance_end_time' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される。

    public function test_integrity_of_rest_start_and_clock_out()
    {
        $staff = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2026-01-01',
            'clock_in' => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2026-01-01 12:00',
            'end_time' => '2026-01-01 13:00'
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $response = $this->get("/admin/attendance/{$staff->id}");
        $response->assertStatus(200);

        $inputData = [
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'staff_id' => $staff->id,
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

        $response = $this->post('/admin/direct_update', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            "rests.{$rest->id}.start_time" => '休憩時間が不適切な値です'
        ]);
    }

    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される。

    public function test_integrity_of_rest_end_and_clock_out()
    {
        $staff = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2026-01-01',
            'clock_in' => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2026-01-01 12:00',
            'end_time' => '2026-01-01 13:00'
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $response = $this->get("/admin/attendance/{$staff->id}");
        $response->assertStatus(200);

        $inputData = [
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'staff_id' => $staff->id,
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

        $response = $this->post('/admin/direct_update', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            "rests.{$rest->id}.end_time" => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    // 備考欄が未入力の場合のエラーメッセージが表示される。

    public function test_reason_validation()
    {
        $staff = User::factory()->create(['email_verified_at' => now()]);

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2026-01-01',
            'clock_in' => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $response = $this->get("/admin/attendance/{$staff->id}");
        $response->assertStatus(200);

        $inputData = [
            'attendance_id' => $attendance->id,
            'work_date' => $attendance->work_date,
            'staff_id' => $staff->id,
            'attendance_start_time' => '10:00',
            'attendance_end_time' => '20:00',
            'reason' => null
        ];

        $response = $this->post('/admin/direct_update', $inputData);
        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            "reason" => '備考を記入してください'
        ]);
    }
}