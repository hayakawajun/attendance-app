<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;

class ClockInTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目6：出勤機能

    // 出勤ボタンが正しく機能する。

    public function test_clock_in_button_performance()
    {
        $knownDate = now()->parse('2026-01-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('勤務外');

        $response->assertSeeInOrder([
            '<button class="attendance__submit-btn work">',
            '出勤',
            '</button>'
        ], false);

        $response = $this->post('/clock_in');
        $response->assertStatus(302);

        /**
         * テストケース一覧の期待挙動には「勤務中」とありますが、
         * 参考UIに従い「出勤中」で検証しています。
         */
        $this->followRedirects($response)->assertSee('出勤中');

        $this->assertDatabaseHas('attendances',[
            'user_id' => $user->id,
            'clock_in' => '2026-01-01 10:00:00'
        ]);
    }

    // 出勤は一日一回のみできる。

    public function test_clock_in_limit()
    {
        $knownDate = now()->parse('2026-01-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $finished = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $knownDate->format('Y-m-d'),
            'clock_in' => $knownDate,
            'clock_out' => $knownDate->copy()->addMinute(1),
            'created_at' => $knownDate,
            'updated_at' => $knownDate->copy()->addMinute(1)
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('退勤済');

        $response->assertDontSee('button class="attendance__submit-btn', false);
        $response->assertSee('お疲れ様でした。');
    }

    // 出勤時刻が勤怠一覧画面で確認できる。

    public function test_clock_in_confirmable_in_the_list()
    {
        $knownDate = now()->parse('2026-01-01 10:00:00');
        $this->travelTo($knownDate);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('勤務外');

        $response = $this->post('/clock_in');
        $response->assertStatus(302);

        $this->followRedirects($response)->assertSee('出勤中');

        $year = $knownDate->year;
        $month = $knownDate->format('m');

        $response = $this->get("/attendance/list/$year/$month");
        $response->assertStatus(200);

        $response->assertViewHas('calendar', function($calendar){
            $day = $calendar[0];
            return $day['date']->isoFormat('MM/DD(ddd)') === '01/01(木)'
                && $day['attendance']->clock_in->format('H:i') === '10:00';
        });

        $response->assertSeeInOrder([
            '<td class="date">',
            '01/01(木)',
            '<td class="time">',
            '10:00'
        ], false);
    }
}
