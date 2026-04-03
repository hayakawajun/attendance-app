<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\Admin;

class AdminsGetStaffListTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目14：ユーザー情報取得機能（管理者）

        /**
     * この項目のテスト手順に記載の「勤怠一覧ページ」とは
     * 「スタッフ別勤怠一覧ページ」の事であるという認識で
     * テストを作成しています。
     */

    // 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる。

    public function test_get_all_staff_data()
    {
        $firstStaff = User::create([
            'name'     => '労働者壱号',
            'email'    => 'first_worker@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $firstStaff->markEmailAsVerified();

        $secondStaff = User::create([
            'name'     => '労働者弐号',
            'email'    => 'second_worker@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $secondStaff->markEmailAsVerified();

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $response = $this->get('/admin/staff/list');
        $response->assertStatus(200);

        $response->assertViewHas('staffs', function($staffs){
            $staff = $staffs[0];
            return $staff->name  === '労働者壱号'
                && $staff->email === 'first_worker@example.com';
        });

        $response->assertViewHas('staffs', function($staffs){
            $staff = $staffs[1];
            return $staff->name  === '労働者弐号'
                && $staff->email === 'second_worker@example.com';
        });

        $response->assertSeeInOrder([
            'スタッフ一覧',
            '<td class="name">','労働者壱号',
            '<td class="email">','first_worker@example.com',
            '<td class="name">','労働者弐号',
            '<td class="email">','second_worker@example.com',
        ], false);
    }

    // ユーザーの勤怠情報が正しく表示される。

    public function test_attendance_of_staff_confirmable_in_the_list()
    {
        $knownDate = now()->parse('2026-01-01 20:00:00');
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

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $response = $this->get('/admin/staff/list');
        $response->assertStatus(200);

        $targetStaffUrl = route('admin.individual_index',[
            'id' => $staff->id
        ]);

        $response->assertSeeInOrder([
            '<a class="detail__link"', $targetStaffUrl,'詳細','</a>'
        ], false);

        $response = $this->get($targetStaffUrl);
        $response->assertStatus(200);

        $response->assertViewHas('calendar', function($calendar){
            $day = $calendar[0];
            return $day['date']->isoFormat('MM/DD(ddd)')        === '01/01(木)'
                && $day['attendance']->clock_in->format('H:i')  === '08:00'
                && $day['attendance']->clock_out->format('H:i') === '17:00'
                && $day['attendance']->total_rest_time          === '1:00'
                && $day['attendance']->total_working_time       === '8:00';
        });

        $response->assertSeeInOrder([
            'テストネームさんの勤怠',
            '<span class="target-date">','2026/01','</span>',
            '<td class="date">',
            '01/01(木)',
            '08:00','17:00','1:00','8:00'
        ], false);
    }

    // 「前月」を押下した時に表示月の前月の情報が表示される。

    public function test_display_previous_month()
    {
        $staff = User::create([
            'name'     => 'テストネーム',
            'email'    => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $staff->markEmailAsVerified();

        $pastAttendance = Attendance::create([
            'user_id'   => $staff->id,
            'work_date' => '2025-12-01',
            'clock_in'  => '2025-12-01 08:00',
            'clock_out' => '2025-12-01 17:00'
        ]);

        $pastRest = Rest::create([
            'attendance_id' => $pastAttendance->id,
            'start_time'    => '2025-12-01 12:00',
            'end_time'      => '2025-12-01 13:00'
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $currentDate = now()->parse('2026-01-01 10:00:00');

        $currentYear = $currentDate->year;
        $currentMonth = $currentDate->month;

        $response = $this->get("/admin/attendance/staff/{$staff->id}/{$currentYear}/{$currentMonth}");
        $response->assertStatus(200);

        $prevDate = $currentDate->copy()->subMonth();
        $prevUrl = route('admin.individual_index',[
            'id'    => $staff->id,
            'year'  => $prevDate->year,
            'month' => $prevDate->month
        ]);

        $response->assertSeeInOrder([
            '<a class="moving-date"', $prevUrl,'前月','</a>'
        ], false);

        $response = $this->get($prevUrl);
        $response->assertStatus(200);

        $response->assertViewHas('calendar', function($calendar){
            $day = $calendar[0];
            return $day['date']->isoFormat('MM/DD(ddd)')        === '12/01(月)'
                && $day['attendance']->clock_in->format('H:i')  === '08:00'
                && $day['attendance']->clock_out->format('H:i') === '17:00'
                && $day['attendance']->total_rest_time          === '1:00'
                && $day['attendance']->total_working_time       === '8:00';
        });

        $response->assertSeeInOrder([
            'テストネームさんの勤怠',
            '<span class="target-date">','2025/12','</span>',
            '<td class="date">',
            '12/01(月)',
            '08:00','17:00','1:00','8:00'
        ], false);
    }

    // 「翌月」を押下した時に表示月の翌月の情報が表示される。

    public function test_display_next_month()
    {
        $staff = User::create([
            'name'     => 'テストネーム',
            'email'    => 'test@example.com',
            'password' => Hash::make('dummypass')
        ]);
        $staff->markEmailAsVerified();

        $futureAttendance = Attendance::create([
            'user_id'   => $staff->id,
            'work_date' => '2026-02-01',
            'clock_in'  => '2026-02-01 08:00',
            'clock_out' => '2026-02-01 17:00'
        ]);

        $futureRest = Rest::create([
            'attendance_id' => $futureAttendance->id,
            'start_time'    => '2026-02-01 12:00',
            'end_time'      => '2026-02-01 13:00'
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $currentDate = now()->parse('2026-01-01 10:00:00');

        $currentYear = $currentDate->year;
        $currentMonth = $currentDate->month;

        $response = $this->get("/admin/attendance/staff/{$staff->id}/{$currentYear}/{$currentMonth}");
        $response->assertStatus(200);

        $nextDate = $currentDate->copy()->addMonth();
        $nextUrl = route('admin.individual_index',[
            'id'    => $staff->id,
            'year'  => $nextDate->year,
            'month' => $nextDate->month
        ]);

        $response->assertSeeInOrder([
            '<a class="moving-date"', $nextUrl,'翌月','</a>'
        ], false);

        $response = $this->get($nextUrl);
        $response->assertStatus(200);

        $response->assertViewHas('calendar', function($calendar){
            $day = $calendar[0];
            return $day['date']->isoFormat('MM/DD(ddd)')        === '02/01(日)'
                && $day['attendance']->clock_in->format('H:i')  === '08:00'
                && $day['attendance']->clock_out->format('H:i') === '17:00'
                && $day['attendance']->total_rest_time          === '1:00'
                && $day['attendance']->total_working_time       === '8:00';
        });

        $response->assertSeeInOrder([
            'テストネームさんの勤怠',
            '<span class="target-date">','2026/02','</span>',
            '<td class="date">',
            '02/01(日)',
            '08:00','17:00','1:00','8:00'
        ], false);
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移する。

    public function test_move_to_detail_page()
    {
        $knownDate = now()->parse('2026-01-01 20:00:00');
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
            'start_time' => '2026-01-01 12:00',
            'end_time'   => '2026-01-01 13:00'
        ]);

        $admin = Admin::factory()->create();
        $this->actingAs($admin,'admin');

        $response = $this->get('/admin/staff/list');
        $response->assertStatus(200);

        $response = $this->get("/admin/attendance/staff/{$staff->id}");
        $response->assertStatus(200);

        $targetUrl = route('admin.show_detail',[
            'id' => $attendance->id
        ]);

        $response->assertSeeInOrder([
            '<a class="detail__link"', $targetUrl,'詳細','</a>'
        ], false);

        $response = $this->get($targetUrl);
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            '勤怠詳細',
            '<td class="label">名前</td>','テストネーム',
            '<td class="label">日付</td>','2026年','1月1日',
            '<td class="label">出勤・退勤</td>','08:00','〜','17:00',
            '<td class="label">休憩</td>','12:00','〜','13:00'
        ], false);
    }
}