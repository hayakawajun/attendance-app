<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\Rest;

class GetDetailTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目10：勤怠詳細情報取得機能（一般ユーザー）

    // 勤怠詳細画面の「名前」がユーザーの氏名になっている。

    public function test_details_view_display_users_name()
    {
        $user = User::create([
            'name'     => 'テストネーム',
            'email'    => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $user->markEmailAsVerified();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2026-01-01',
            'clock_in'  => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '2026-01-01 12:00',
            'end_time'      => '2026-01-01 13:00'
        ]);

        $this->actingAs($user);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '勤怠詳細',
            '<td class="label">名前</td>','テストネーム'
        ], false);
    }

    // 勤怠詳細画面の「日付」が選択した日付になっている。

    public function test_details_view_display_target_date()
    {
        $user = User::create([
            'name'     => 'テストネーム',
            'email'    => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $user->markEmailAsVerified();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2026-01-01',
            'clock_in'  => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '2026-01-01 12:00',
            'end_time'      => '2026-01-01 13:00'
        ]);

        $this->actingAs($user);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '勤怠詳細',
            '<td class="label">日付</td>','2026年','1月1日'
        ], false);
    }

    // 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している。

    public function test_details_view_display_clock_in_and_out()
    {
        $user = User::create([
            'name'     => 'テストネーム',
            'email'    => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $user->markEmailAsVerified();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2026-01-01',
            'clock_in'  => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '2026-01-01 12:00',
            'end_time'      => '2026-01-01 13:00'
        ]);

        $this->actingAs($user);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '勤怠詳細',
            '<td class="label">出勤・退勤</td>','08:00','〜','17:00'
        ], false);
    }

    // 「休憩」にて記されている時間がログインユーザーの打刻と一致している。

    public function test_details_view_display_rest_time()
    {
        $user = User::create([
            'name'     => 'テストネーム',
            'email'    => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $user->markEmailAsVerified();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => '2026-01-01',
            'clock_in'  => '2026-01-01 08:00',
            'clock_out' => '2026-01-01 17:00'
        ]);

        $rest = Rest::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '2026-01-01 12:00',
            'end_time'      => '2026-01-01 13:00'
        ]);

        $this->actingAs($user);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '勤怠詳細',
            '<td class="label">休憩</td>','12:00','〜','13:00'
        ], false);
    }
}