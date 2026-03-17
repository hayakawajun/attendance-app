<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class RestTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目7：休憩機能

    // 休憩ボタンが正しく機能する。

    public function test_rest_start_button_performance()
    {
        $knownDate = now()->parse('2026-01-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $working = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $knownDate->format('Y-m-d'),
            'clock_in' => $knownDate,
            'clock_out' => null,
            'created_at' => $knownDate,
            'updated_at' => $knownDate
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('出勤中');

        $response->assertSeeInOrder([
            '<button class="attendance__submit-btn rest">',
            '休憩入',
            '</button>'
        ], false);

        $this->travelTo($knownDate->addHour(2));

        $response = $this->post('/rest_start');
        $response->assertStatus(302);

        $this->followRedirects($response)->assertSee('休憩中');

        $this->assertDatabaseHas('rests',[
            'attendance_id' => $working->id,
            'start_time' => '2026-01-01 12:00:00'
        ]);
    }

    // 休憩は一日に何回でもできる。

    public function test_rest_start_repeatable()
    {
        $knownDate = now()->parse('2026-01-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $working = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $knownDate->format('Y-m-d'),
            'clock_in' => $knownDate,
            'clock_out' => null,
            'created_at' => $knownDate,
            'updated_at' => $knownDate
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('出勤中');

        $response->assertSeeInOrder([
            '<button class="attendance__submit-btn rest">',
            '休憩入',
            '</button>'
        ], false);

        $this->travelTo($knownDate->addHour(2));

        $response = $this->post('/rest_start');
        $response->assertStatus(302);

        $response = $this->followRedirects($response)->assertSee('休憩中');

        $response->assertSeeInOrder([
            '<button class="attendance__submit-btn rest">',
            '休憩戻',
            '</button>'
        ], false);

        $this->travelTo($knownDate->addHour(1));

        $response = $this->post('/rest_end');
        $response->assertStatus(302);

        $response = $this->followRedirects($response)->assertSee('出勤中');

        $response->assertSeeInOrder([
            '<button class="attendance__submit-btn rest">',
            '休憩入',
            '</button>'
        ], false);
    }

    // 休憩戻ボタンが正しく機能する。

    public function test_rest_end_button_performance()
    {
        $knownDate = now()->parse('2026-01-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $working = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $knownDate->format('Y-m-d'),
            'clock_in' => $knownDate,
            'clock_out' => null,
            'created_at' => $knownDate,
            'updated_at' => $knownDate
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $this->travelTo($knownDate->addHour(2));

        $response = $this->post('/rest_start');
        $response->assertStatus(302);

        $this->followRedirects($response)->assertSee('休憩中');

        $this->travelTo($knownDate->addHour(1));

        $response = $this->post('/rest_end');
        $response->assertStatus(302);

        $response = $this->followRedirects($response)->assertSee('出勤中');

        $this->assertDatabaseHas('rests',[
            'attendance_id' => $working->id,
            'start_time' => '2026-01-01 12:00:00',
            'end_time' => '2026-01-01 13:00:00'
        ]);
    }

    // 休憩戻は一日に何回でもできる。

    public function test_rest_end_repeatable()
    {
        $knownDate = now()->parse('2026-01-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $working = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $knownDate->format('Y-m-d'),
            'clock_in' => $knownDate,
            'clock_out' => null,
            'created_at' => $knownDate,
            'updated_at' => $knownDate
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $this->travelTo($knownDate->addHour(2));

        $response = $this->post('/rest_start');
        $response->assertStatus(302);

        $this->followRedirects($response)->assertSee('休憩中');

        $this->travelTo($knownDate->addHour(1));

        $response = $this->post('/rest_end');
        $response->assertStatus(302);

        $response = $this->followRedirects($response)->assertSee('出勤中');

        $this->travelTo($knownDate->addHour(2));

        $response = $this->post('/rest_start');
        $response->assertStatus(302);

        $response = $this->followRedirects($response)->assertSee('休憩中');

        $response->assertSeeInOrder([
            '<button class="attendance__submit-btn rest">',
            '休憩戻',
            '</button>'
        ], false);

        $this->assertDatabaseHas('rests',[
            'attendance_id' => $working->id,
            'start_time' => '2026-01-01 12:00:00',
            'end_time' => '2026-01-01 13:00:00'
        ]);
        $this->assertDatabaseHas('rests',[
            'attendance_id' => $working->id,
            'start_time' => '2026-01-01 15:00:00',
            'end_time' => null
        ]);
    }

    // 休憩時刻が勤怠一覧画面で確認できる。

    public function test_rest_confirmable_in_the_list()
    {
        $knownDate = now()->parse('2026-01-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $working = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $knownDate->format('Y-m-d'),
            'clock_in' => $knownDate,
            'clock_out' => null,
            'created_at' => $knownDate,
            'updated_at' => $knownDate
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $this->travelTo($knownDate->addHour(2));

        $response = $this->post('/rest_start');
        $response->assertStatus(302);

        $this->travelTo($knownDate->addHour(1));

        $response = $this->post('/rest_end');
        $response->assertStatus(302);

        $year = $knownDate->year;
        $month = $knownDate->format('m');

        $response = $this->actingAs($user)
            ->get("attendance/list/$year/$month");
        $response->assertStatus(200);

        $response->assertViewHas('calendar', function($calendar){
            $day = $calendar[0];
            return $day['date']->isoFormat('MM/DD(ddd)') === '01/01(木)'
                && $day['attendance']->total_rest_time === '1:00';
        });

        $response->assertSeeInOrder([
            '<td class="date">',
            '01/01(木)',
            '<td class="time">',
            '1:00'
        ], false);
    }
}
