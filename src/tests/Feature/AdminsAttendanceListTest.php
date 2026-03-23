<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\Admin;

class AdminsAttendanceListTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目12：勤怠一覧情報取得機能（管理者）

    // その日になされた全ユーザーの勤怠情報が正確に確認できる。

    public function test_can_confirm_attendances_of_all_staffs()
    {
        $knownDate = now()->parse('2026-01-01 10:00:00');
        $this->travelTo($knownDate);

        $firstStaff = User::create([
            'name' => '労働者壱号',
            'email' => 'test1@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $firstStaff->markEmailAsVerified();

        $firstStaffAttendance = Attendance::create([
            'user_id' => $firstStaff->id,
            'work_date' => '2026-01-01',
            'clock_in' => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);
        $firstStaffRest = Rest::create([
            'attendance_id' => $firstStaff->id,
            'start_time' => '2026-01-01 12:00',
            'end_time' => '2026-01-01 13:00'
        ]);

        $secondStaff = User::create([
            'name' => '労働者弐号',
            'email' => 'test2@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $secondStaff->markEmailAsVerified();

        $secondStaffAttendance = Attendance::create([
            'user_id' => $secondStaff->id,
            'work_date' => '2026-01-01',
            'clock_in' => '2026-01-01 12:00',
            'clock_out' => '2026-01-01 21:00'
        ]);
        $secondStaffRest = Rest::create([
            'attendance_id' => $secondStaff->id,
            'start_time' => '2026-01-01 16:00',
            'end_time' => '2026-01-01 16:30'
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $year = $knownDate->year;
        $month = $knownDate->month;
        $day = $knownDate->day;

        $response = $this->get("/admin/attendance/list/{$year}/{$month}/{$day}");
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '2026年1月1日の勤怠',
            '労働者壱号','08:00','17:00','1:00','8:00',
            '労働者弐号','12:00','21:00','0:30','8:30'
        ], false);
    }
}
