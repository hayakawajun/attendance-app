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

        $response = $this->get("attendance/list/$year/$month");
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
}
