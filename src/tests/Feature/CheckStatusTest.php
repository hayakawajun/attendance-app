<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;

class CheckStatusTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目5：ステータス確認機能

    // 勤務外の場合、勤怠ステータスが正しく表示される。

    public function test_status_off_duty()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('勤務外');
    }

    // 出勤中の場合、勤怠ステータスが正しく表示される。

    public function test_status_working()
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
    }

    // 休憩中の場合、勤怠ステータスが正しく表示される。

    public function test_status_breaking()
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

        $breaking = Rest::create([
            'attendance_id' => $working->id,
            'start_time' => $knownDate->copy()->addMinute(1),
            'end_time' => null,
            'created_at' => $knownDate->copy()->addMinute(1),
            'updated_at' => $knownDate->copy()->addMinute(1)
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee('休憩中');
    }

    // 退勤済みの場合、勤怠ステータスが正しく表示される。

    public function test_status_finished_work()
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
    }
}
