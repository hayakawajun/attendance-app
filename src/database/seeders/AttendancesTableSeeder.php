<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userId = 1;
        $rests = [];

        $startDate = Carbon::now()->subMonth()->startOfMonth();
        $endDate = Carbon::now()->subMonth()->endOfMonth();

        for($date = $startDate->copy(); $date <= $endDate; $date->addDay()){
            if($date->isWeekend()) continue;

            $weekNum = $date->weekOfMonth;

            if($weekNum % 2 !== 0){
                $clockIn = $date->copy()->setTime(8,0);
                $clockOut = $date->copy()->setTime(17,0);
            }else{
                $clockIn = $date->copy()->setTime(17,0);
                $clockOut = $date->copy()->addDay()->setTime(2,0);
            }

            $attendanceId = DB::table('attendances')->insertGetId([
                'user_id' => $userId,
                'work_date' => $date->toDateString(),
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            if($weekNum % 2 !== 0){
                $rests[] = [
                    'attendance_id' => $attendanceId,
                    'start_time' => $clockIn->copy()->addHours(4),
                    'end_time' => $clockIn->copy()->addHours(5),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }else{
                $rests[] = [
                    'attendance_id' => $attendanceId,
                    'start_time' => $clockIn->copy()->addHours(2),
                    'end_time' => $clockIn->copy()->addHours(2)->addMinute(30),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $rests[] = [
                    'attendance_id' => $attendanceId,
                    'start_time' => $clockIn->copy()->addHours(5),
                    'end_time' => $clockIn->copy()->addHours(5)->addMinute(30),
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        DB::table('rests')->insert($rests);
    }
}
