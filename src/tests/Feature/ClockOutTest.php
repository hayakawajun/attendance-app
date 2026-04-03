<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目8：退勤機能

    // 退勤ボタンが正しく機能する。

    public function test_clock_out_button_performance()
    {
        $knownDate = now()->parse('2026-01-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $working = Attendance::create([
            'user_id'    => $user->id,
            'work_date'  => $knownDate->format('Y-m-d'),
            'clock_in'   => $knownDate,
            'clock_out'  => null,
            'created_at' => $knownDate,
            'updated_at' => $knownDate
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('出勤中');

        $response->assertSeeInOrder([
            '<button class="attendance__submit-btn work">',
            '退勤',
            '</button>'
        ], false);

        $this->travelTo($knownDate->addHour(1));

        $response = $this->post('/clock_out');
        $response->assertStatus(302);

        $this->followRedirects($response)->assertSee('退勤済');

        $this->assertDatabaseHas('attendances',[
            'user_id'   => $user->id,
            'clock_in'  => '2026-01-01 10:00:00',
            'clock_out' => '2026-01-01 11:00:00'
        ]);
    }

    // 退勤時刻が勤怠一覧画面で確認できる。

    public function test_clock_out_confirmable_in_the_list()
    {
        $knownDate = now()->parse('2026-01-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('勤務外');

        $response = $this->post('/clock_in');
        $response->assertStatus(302);

        $this->travelTo($knownDate->addHour(1));

        $response = $this->post('/clock_out');
        $response->assertStatus(302);

        $this->followRedirects($response)->assertSee('退勤済');

        $year = $knownDate->year;
        $month = $knownDate->format('m');

        $response = $this->get("/attendance/list/$year/$month");
        $response->assertStatus(200);

        $response->assertViewHas('calendar', function($calendar){
            $day = $calendar[0];
            return $day['date']->isoFormat('MM/DD(ddd)')        === '01/01(木)'
                && $day['attendance']->clock_out->format('H:i') === '11:00';
        });

        $response->assertSeeInOrder([
            '<td class="date">',
            '01/01(木)',
            '<td class="time">',
            '11:00'
        ], false);
    }
}