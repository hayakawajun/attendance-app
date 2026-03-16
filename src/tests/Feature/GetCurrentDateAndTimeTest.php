<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class GetCurrentDateAndTimeTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     *
     * @return void
     */

    // テスト項目4：日時取得機能

    // 現在の日時情報がUIと同じ形式で出力されている。

    public function test_get_current_date_and_time()
    {
        $knownDate = now()->parse('2026-01-01 12:34:00');
        $this->travelTo($knownDate);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);

        $response->assertSee($knownDate->isoFormat('YYYY年M月D日(ddd)'));
        $response->assertSeeInOrder(['12',':','34']);
    }
}
