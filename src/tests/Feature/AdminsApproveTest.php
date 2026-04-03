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

class AdminsApproveTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目15：勤怠情報修正機能（管理者）

    // 承認待ちの修正申請が全て表示されている。

    public function test_display_all_pending_requests()
    {
        $knownDate = now()->parse('2026-02-01 10:00:00');
        $this->travelTo($knownDate);

        $firstStaff = User::create([
            'name'     => '労働者壱号',
            'email'    => 'test1@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $firstStaff->markEmailAsVerified();

        $firstStaffAttendance = Attendance::create([
            'user_id'   => $firstStaff->id,
            'work_date' => '2026-01-01',
            'clock_in'  => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $response = $this->actingAs($firstStaff)
            ->get("/attendance/detail/{$firstStaffAttendance->id}");

        $inputData = [
            'attendance_id'         => $firstStaffAttendance->id,
            'work_date'             => $firstStaffAttendance->work_date,
            'staff_id'              => $firstStaff->id,
            'attendance_start_time' => '11:11',
            'attendance_end_time'   => '22:22',
            'reason'                => '申請その1'
        ];

        $response = $this->post('/attendance/request', $inputData);

        $this->travelTo($knownDate->addMonth(1));

        $secondStaff = User::create([
            'name'     => '労働者弐号',
            'email'    => 'test2@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $secondStaff->markEmailAsVerified();

        $secondStaffAttendance = Attendance::create([
            'user_id'   => $secondStaff->id,
            'work_date' => '2026-02-01',
            'clock_in'  => '2026-02-01 08:00',
            'clock_out' => '2026-02-01 17:00'
        ]);

        $response = $this->actingAs($secondStaff)
            ->get("/attendance/detail/{$secondStaffAttendance->id}");

        $inputData = [
            'attendance_id'         => $secondStaffAttendance->id,
            'work_date'             => $secondStaffAttendance->work_date,
            'staff_id'              => $secondStaff->id,
            'attendance_start_time' => '11:11',
            'attendance_end_time'   => '22:22',
            'reason'                => '申請その2'
        ];

        $response = $this->post('/attendance/request', $inputData);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '<div class="pending__tab-content">',
            '承認','待ち','労働者弐号','2026','02/01','申請その2','2026','03/01','詳細',
            '承認','待ち','労働者壱号','2026','01/01','申請その1','2026','02/01','詳細',
        ], false);
    }

    // 承認済みの修正申請が全て表示されている。

    public function test_display_all_approved_requests()
    {
        $knownDate = now()->parse('2026-02-01 10:00:00');
        $this->travelTo($knownDate);

        $firstStaff = User::create([
            'name'     => '労働者壱号',
            'email'    => 'test1@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $firstStaff->markEmailAsVerified();

        $firstStaffAttendance = Attendance::create([
            'user_id'   => $firstStaff->id,
            'work_date' => '2026-01-01',
            'clock_in'  => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $response = $this->actingAs($firstStaff)
            ->get("/attendance/detail/{$firstStaffAttendance->id}");

        $inputData = [
            'attendance_id'         => $firstStaffAttendance->id,
            'work_date'             => $firstStaffAttendance->work_date,
            'staff_id'              => $firstStaff->id,
            'attendance_start_time' => '11:11',
            'attendance_end_time'   => '22:22',
            'reason'                => '申請その1'
        ];

        $response = $this->post('/attendance/request', $inputData);

        $this->travelTo($knownDate->addMonth(1));

        $secondStaff = User::create([
            'name'     => '労働者弐号',
            'email'    => 'test2@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $secondStaff->markEmailAsVerified();

        $secondStaffAttendance = Attendance::create([
            'user_id'   => $secondStaff->id,
            'work_date' => '2026-02-01',
            'clock_in'  => '2026-02-01 08:00',
            'clock_out' => '2026-02-01 17:00'
        ]);

        $response = $this->actingAs($secondStaff)
            ->get("/attendance/detail/{$secondStaffAttendance->id}");

        $inputData = [
            'attendance_id'         => $secondStaffAttendance->id,
            'work_date'             => $secondStaffAttendance->work_date,
            'staff_id'              => $secondStaff->id,
            'attendance_start_time' => '11:11',
            'attendance_end_time'   => '22:22',
            'reason'                => '申請その2'
        ];

        $response = $this->post('/attendance/request', $inputData);

        $firstStaffRequest = AttendanceRequest::where('attendance_id', $firstStaffAttendance->id)
            ->first();
        $secondStaffRequest = AttendanceRequest::where('attendance_id', $secondStaffAttendance->id)
            ->first();

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response = $this->get("/stamp_correction_request/approve/{$firstStaffRequest->id}");
        $response->assertStatus(200);
        $response = $this->post("/stamp_correction_request/approve/{$firstStaffRequest->id}");
        $response->assertStatus(302);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response = $this->get("/stamp_correction_request/approve/{$secondStaffRequest->id}");
        $response->assertStatus(200);
        $response = $this->post("/stamp_correction_request/approve/{$secondStaffRequest->id}");
        $response->assertStatus(302);

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '<div class="approved__tab-content">',
            '承認','済み','労働者弐号','2026','02/01','申請その2','2026','03/01','詳細',
            '承認','済み','労働者壱号','2026','01/01','申請その1','2026','02/01','詳細',
        ], false);
    }

    // 修正申請の詳細内容が正しく表示されている。

    public function test_application_details_view_display_correctly()
    {
        $knownDate = now()->parse('2026-02-22 10:00:00');
        $this->travelTo($knownDate);

        $staff = User::create([
            'name'     => 'テストネーム',
            'email'    => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $staff->markEmailAsVerified();

        $attendance = Attendance::create([
            'user_id'   => $staff->id,
            'work_date' => '2026-01-01',
            'clock_in'  => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '2026-01-01 12:00',
            'end_time'      => '2026-01-01 13:00'
        ]);

        $response = $this->actingAs($staff)
            ->get("/attendance/detail/{$attendance->id}");

        $inputData = [
            'attendance_id'         => $attendance->id,
            'work_date'             => $attendance->work_date,
            'staff_id'              => $staff->id,
            'attendance_start_time' => '08:08',
            'attendance_end_time'   => '17:17',
            'rests' => [
                $rest->id => [
                    'start_time' => '12:12',
                    'end_time'   => '13:13'
                ]
            ],
            'reason' => '申請テスト'
        ];

        $response = $this->post('/attendance/request', $inputData);

        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)
            ->first();

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $detailUrl = route('admin.show_request',[
            'attendance_correct_request_id' => $attendanceRequest->id ]);

        $response->assertSeeInOrder([
            '承認','待ち','テストネーム','2026','01/01','申請テスト','2026','02/22',
            '<a class="detail__link"', $detailUrl,'詳細','</a>'
        ], false);

        $response = $this->get($detailUrl);
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '勤怠詳細',
            'テストネーム',
            '2026年','1月1日',
            '出勤・退勤','08:08','〜','17:17',
            '休憩','12:12','〜','13:13',
            '<button class="submit__button update"','承認','</button>'
        ], false);
    }

    // 修正申請の承認処理が正しく行われる。

    public function test_process_approvals_accurately()
    {
        $knownDate = now()->parse('2026-02-22 10:00:00');
        $this->travelTo($knownDate);

        $staff = User::create([
            'name'     => 'テストネーム',
            'email'    => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $staff->markEmailAsVerified();

        $attendance = Attendance::create([
            'user_id'   => $staff->id,
            'work_date' => '2026-01-01',
            'clock_in'  => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '2026-01-01 12:00',
            'end_time'      => '2026-01-01 13:00'
        ]);

        $response = $this->actingAs($staff)
            ->get("/attendance/detail/{$attendance->id}");

        $inputData = [
            'attendance_id'         => $attendance->id,
            'work_date'             => $attendance->work_date,
            'staff_id'              => $staff->id,
            'attendance_start_time' => '08:08',
            'attendance_end_time'   => '17:17',
            'rests' => [
                $rest->id => [
                    'start_time' => '12:12',
                    'end_time'   => '13:13'
                ]
            ],
            'reason' => '申請テスト'
        ];

        $response = $this->post('/attendance/request', $inputData);

        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)
            ->first();

        $this->assertDatabaseHas('attendance_requests',[
            'id'               => $attendanceRequest->id,
            'attendance_id'    => $attendance->id,
            'user_id'          => $staff->id,
            'target_date'      => '2026-01-01',
            'status'           => 'pending',
            'reason'           => '申請テスト',
            'requested_at'     => '2026-02-22 10:00:00',
            'admin_id'         => null,
            'approved_by_name' => null,
            'approved_at'      => null
        ]);

        $this->assertDatabaseHas('attendances',[
            'id'        => $attendance->id,
            'user_id'   => $staff->id,
            'work_date' => '2026-01-01',
            'clock_in'  => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $this->assertDatabaseHas('rests',[
            'id'            => $rest->id,
            'attendance_id' => $attendance->id,
            'start_time'    => '2026-01-01 12:00',
            'end_time'      => '2026-01-01 13:00'
        ]);

        $this->travelTo($knownDate->addMonth(1));

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200);

        $response = $this->get("/stamp_correction_request/approve/{$attendanceRequest->id}");
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '勤怠詳細',
            'テストネーム',
            '2026年','1月1日',
            '出勤・退勤','08:08','〜','17:17',
            '休憩','12:12','〜','13:13',
            '備考','申請テスト',
            '<button class="submit__button update"','承認','</button>'
        ], false);

        $response = $this->post("/stamp_correction_request/approve/{$attendanceRequest->id}");
        $response->assertStatus(302);
        $response = $this->followRedirects($response);

        $response->assertSeeInOrder([
            '勤怠詳細',
            'テストネーム',
            '2026年','1月1日',
            '出勤・退勤','08:08','〜','17:17',
            '休憩','12:12','〜','13:13',
            '備考','申請テスト',
            '<p class="already__approved">','承認済み','</p>'
        ], false);

        $this->assertDatabaseHas('attendance_requests',[
            'id'               => $attendanceRequest->id,
            'attendance_id'    => $attendance->id,
            'user_id'          => $staff->id,
            'target_date'      => '2026-01-01',
            'status'           => 'approved',
            'reason'           => '申請テスト',
            'requested_at'     => '2026-02-22 10:00:00',
            'admin_id'         => $admin->id,
            'approved_by_name' => $admin->name,
            'approved_at'      => '2026-03-22 10:00:00'
        ]);

        $this->assertDatabaseHas('attendances',[
            'id'        => $attendance->id,
            'user_id'   => $staff->id,
            'work_date' => '2026-01-01',
            'clock_in'  => '2026-01-01 08:08',
            'clock_out' => '2026-01-01 17:17'
        ]);

        $this->assertDatabaseHas('rests',[
            'id'            => $rest->id,
            'attendance_id' => $attendance->id,
            'start_time'    => '2026-01-01 12:12',
            'end_time'      => '2026-01-01 13:13'
        ]);
    }
}