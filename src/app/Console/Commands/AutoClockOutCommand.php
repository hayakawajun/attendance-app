<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AutoClockOutCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:clock-out';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '締め時刻を過ぎた未退勤レコードおよび未終了休憩を自動補完します';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $currentWorkingDate = Attendance::getWorkingDate();

        $targetAttendances = Attendance::where('work_date', '<', $currentWorkingDate)
            ->whereNull('clock_out')
            ->get();

        if ($targetAttendances->isEmpty()) {
            $this->info('処理対象の未退勤レコードはありません。');
            return;
        }

        DB::transaction(function () use ($targetAttendances) {
            foreach ($targetAttendances as $attendance) {
                $limitTime = Carbon::parse($attendance->work_date)
                    ->addDay()
                    ->startOfDay()
                    ->addHours(4)
                    ->addMinutes(59);

                $attendance->rests()
                    ->whereNull('end_time')
                    ->update([
                        'end_time' => $limitTime->copy()->subMinute()
                    ]);

                $attendance->update([
                    'clock_out' => $limitTime
                ]);
            }
        });

        $this->info($targetAttendances->count().'件の勤怠データを自動補完しました。');
    }
}
