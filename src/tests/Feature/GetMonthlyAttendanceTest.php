<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;

class GetMonthlyAttendanceTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目9：勤怠一覧情報取得機能（一般ユーザー）

    // 自分が行った勤怠情報が全て表示されている。

    public function test_all_attendances_confirmable_in_the_list()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $attendanceOfFirstDay = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-01-01',
            'clock_in' => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);
        $attendanceOfSecondDay = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-01-02',
            'clock_in' => '2026-01-02 17:00',
            'clock_out' => '2026-01-03 02:00'
        ]);

        $restOfFirstDay = Rest::create([
            'attendance_id' => $attendanceOfFirstDay->id,
            'start_time' => '2026-01-01 12:00',
            'end_time' => '2026-01-01 13:00'
        ]);
        $restOfSecondDay = Rest::create([
            'attendance_id' => $attendanceOfSecondDay->id,
            'start_time' => '2026-01-02 21:00',
            'end_time' => '2026-01-02 22:00'
        ]);

        $this->actingAs($user);

        $year = 2026;
        $month = 1;

        $response = $this->get("/attendance/list/$year/$month");
        $response->assertStatus(200);

        $response->assertViewHas('calendar', function($calendar){
            $day = $calendar[0];
            return $day['date']->isoFormat('MM/DD(ddd)') === '01/01(木)'
                && $day['attendance']->clock_in->format('H:i') === '08:00'
                && $day['attendance']->clock_out->format('H:i') === '17:00'
                && $day['attendance']->total_rest_time === '1:00'
                && $day['attendance']->total_working_time === '8:00';
        });
        $response->assertViewHas('calendar', function($calendar){
            $day = $calendar[1];
            return $day['date']->isoFormat('MM/DD(ddd)') === '01/02(金)'
                && $day['attendance']->clock_in->format('H:i') === '17:00'
                && $day['attendance']->clock_out->format('H:i') === '02:00'
                && $day['attendance']->total_rest_time === '1:00'
                && $day['attendance']->total_working_time === '8:00';
        });

        $response->assertSeeInOrder([
            '<span class="target-date">',
            '2026/01',
            '</span>',
            '<td class="date">',
            '01/01(木)',
            '08:00',
            '17:00',
            '1:00',
            '8:00',
            '<td class="date">',
            '01/02(金)',
            '17:00',
            '02:00',
            '1:00',
            '8:00'
        ], false);
    }

    // 勤怠一覧画面に遷移した際に現在の月が表示される。

    public function test_display_current_month()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $currentDate = now()->parse('2026-01-01');

        $currentYear = $currentDate->year;
        $currentMonth = $currentDate->month;

        $response = $this->get("/attendance/list/$currentYear/$currentMonth");
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '<span class="target-date">',
            '2026/01',
            '</span>'
        ], false);
    }

    // 「前月」を押下した時に表示月の前月の情報が表示される。

    public function test_display_previous_month()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $pastAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-12-01',
            'clock_in' => '2025-12-01 08:00',
            'clock_out' => '2025-12-01 17:00'
        ]);

        $pastRest = Rest::create([
            'attendance_id' => $pastAttendance->id,
            'start_time' => '2025-12-01 12:00',
            'end_time' => '2025-12-01 13:00'
        ]);

        $this->actingAs($user);

        $currentDate = now()->parse('2026-01-01 10:00:00');

        $currentYear = $currentDate->year;
        $currentMonth = $currentDate->month;

        $response = $this->get("/attendance/list/$currentYear/$currentMonth");
        $response->assertStatus(200);

        $prevDate = $currentDate->copy()->subMonth();

        $prevUrl = route('attendance.index',[
            'year' => $prevDate->year,
            'month' => $prevDate->month
        ]);

        $response->assertSeeInOrder([
            '<a class="moving-date"',
            $prevUrl,
            '前月',
            '</a>'
        ], false);

        $response = $this->get($prevUrl);
        $response->assertStatus(200);

        $response->assertViewHas('calendar', function($calendar){
            $day = $calendar[0];
            return $day['date']->isoFormat('MM/DD(ddd)') === '12/01(月)'
                && $day['attendance']->clock_in->format('H:i') === '08:00'
                && $day['attendance']->clock_out->format('H:i') === '17:00'
                && $day['attendance']->total_rest_time === '1:00'
                && $day['attendance']->total_working_time === '8:00';
        });

        $response->assertSeeInOrder([
            '<span class="target-date">',
            '2025/12',
            '</span>',
            '<td class="date">',
            '12/01(月)',
            '08:00',
            '17:00',
            '1:00',
            '8:00'
        ], false);
    }

        // 「翌月」を押下した時に表示月の前月の情報が表示される。

    public function test_display_next_month()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $futureAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-01',
            'clock_in' => '2026-02-01 08:00',
            'clock_out' => '2026-02-01 17:00'
        ]);

        $futureRest = Rest::create([
            'attendance_id' => $futureAttendance->id,
            'start_time' => '2026-02-01 12:00',
            'end_time' => '2026-02-01 13:00'
        ]);

        $this->actingAs($user);

        $currentDate = now()->parse('2026-01-01 10:00:00');

        $currentYear = $currentDate->year;
        $currentMonth = $currentDate->month;

        $response = $this->get("/attendance/list/$currentYear/$currentMonth");
        $response->assertStatus(200);

        $nextDate = $currentDate->copy()->addMonth();

        $nextUrl = route('attendance.index',[
            'year' => $nextDate->year,
            'month' => $nextDate->month
        ]);

        $response->assertSeeInOrder([
            '<a class="moving-date"',
            $nextUrl,
            '翌月',
            '</a>'
        ], false);

        $response = $this->get($nextUrl);
        $response->assertStatus(200);

        $response->assertViewHas('calendar', function($calendar){
            $day = $calendar[0];
            return $day['date']->isoFormat('MM/DD(ddd)') === '02/01(日)'
                && $day['attendance']->clock_in->format('H:i') === '08:00'
                && $day['attendance']->clock_out->format('H:i') === '17:00'
                && $day['attendance']->total_rest_time === '1:00'
                && $day['attendance']->total_working_time === '8:00';
        });

        $response->assertSeeInOrder([
            '<span class="target-date">',
            '2026/02',
            '</span>',
            '<td class="date">',
            '02/01(日)',
            '08:00',
            '17:00',
            '1:00',
            '8:00'
        ], false);
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移する。

    public function test_display_attendance_detail()
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

        $year = 2026;
        $month = 1;

        $response = $this->get("/attendance/list/$year/$month");
        $response->assertStatus(200);

        $detailUrl = route('detail.show',['id' => $attendance->id ]);

        $response->assertSeeInOrder([
            '<span class="target-date">',
            '2026/01',
            '</span>',
            '<td class="date">',
            '01/01(木)',
            '08:00',
            '17:00',
            '1:00',
            '8:00',
            '<a class="detail__link"',
            $detailUrl,
            '詳細',
            '</a>'
        ], false);

        $response = $this->get($detailUrl);

        $response->assertSeeInOrder([
            '勤怠詳細',
            '2026年','1月1日',
            '出勤・退勤','08:00','〜','17:00',
            '休憩','12:00','〜','13:00'
        ]);
    }
}
