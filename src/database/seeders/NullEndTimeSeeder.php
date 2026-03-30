<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NullEndTimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $date = Carbon::now()->subMonthNoOverflow(2)->startOfMonth();

        $attendanceId = DB::table('attendances')->insertGetId([
            'user_id' => 1,
            'work_date' => $date->toDateString(),
            'clock_in' => $date->copy()->setTime(8,0),
            'clock_out' => NULL,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('rests')->insert([
            'attendance_id' => $attendanceId,
            'start_time' => $date->copy()->setTime(12,0),
            'end_time' => NULL,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
