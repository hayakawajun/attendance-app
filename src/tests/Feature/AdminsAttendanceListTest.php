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
            'attendance_id' => $firstStaffAttendance->id,
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
            'attendance_id' => $secondStaffAttendance->id,
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

    // 遷移した際に現在の日付が表示される。

    public function test_display_current_date()
    {
        $knownDate = now()->parse('2025-12-25 10:00:00');
        $this->travelTo($knownDate);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $year = $knownDate->year;
        $month = $knownDate->month;
        $day = $knownDate->day;

        $response = $this->get("/admin/attendance/list/{$year}/{$month}/{$day}");
        $response->assertStatus(200);

        $response->assertSee($knownDate->format('Y年n月j日'));
        $response->assertSee('2025年12月25日の勤怠');
    }

    // 「前日」を押下した時に前の日の勤怠情報が表示される。

    public function test_display_previous_day()
    {
        $knownDate = now()->parse('2025-12-25 10:00:00');
        $this->travelTo($knownDate);

        $staff = User::create([
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $staff->markEmailAsVerified();

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2025-12-24',
            'clock_in' => '2025-12-24 08:00',
            'clock_out' => '2025-12-24 17:00'
        ]);
        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2025-12-24 12:00',
            'end_time' => '2025-12-24 13:00'
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $year = $knownDate->year;
        $month = $knownDate->month;
        $day = $knownDate->day;

        $response = $this->get("/admin/attendance/list/{$year}/{$month}/{$day}");
        $response->assertStatus(200);

        $response->assertSee($knownDate->format('Y年n月j日'));
        $response->assertSee('2025年12月25日の勤怠');

        $previousUrl = route('admin.day_index',[
            'year' => $knownDate->copy()->subDay()->year,
            'month' => $knownDate->copy()->subDay()->month,
            'day' => $knownDate->copy()->subDay()->day
            ]);

        $response->assertSeeInOrder([
            '<a class="moving-date"', $previousUrl,'前日','</a>'
        ], false);

        $response = $this->get($previousUrl);
        $response->assertStatus(200);

        $response->assertSee($knownDate->subDay()->format('Y年n月j日'));
        $response->assertSeeInOrder([
            '2025年12月24日の勤怠',
            'テストネーム','08:00','17:00','1:00','8:00'
        ], false);;
    }

    // 「翌日」を押下した時に次の日の勤怠情報が表示される。

    public function test_display_next_day()
    {
        $knownDate = now()->parse('2025-12-25 10:00:00');
        $this->travelTo($knownDate);

        $staff = User::create([
            'name' => 'テストネーム',
            'email' => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $staff->markEmailAsVerified();

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2025-12-26',
            'clock_in' => '2025-12-26 08:00',
            'clock_out' => '2025-12-26 17:00'
        ]);
        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time' => '2025-12-26 12:00',
            'end_time' => '2025-12-26 13:00'
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $year = $knownDate->year;
        $month = $knownDate->month;
        $day = $knownDate->day;

        $response = $this->get("/admin/attendance/list/{$year}/{$month}/{$day}");
        $response->assertStatus(200);

        $response->assertSee($knownDate->format('Y年n月j日'));
        $response->assertSee('2025年12月25日の勤怠');

        $nextUrl = route('admin.day_index',[
            'year' => $knownDate->copy()->addDay()->year,
            'month' => $knownDate->copy()->addDay()->month,
            'day' => $knownDate->copy()->addDay()->day
            ]);

        $response->assertSeeInOrder([
            '<a class="moving-date"', $nextUrl,'翌日','</a>'
        ], false);

        $response = $this->get($nextUrl);
        $response->assertStatus(200);

        $response->assertSee($knownDate->addDay()->format('Y年n月j日'));
        $response->assertSeeInOrder([
            '2025年12月26日の勤怠',
            'テストネーム','08:00','17:00','1:00','8:00'
        ], false);;
    }
}
