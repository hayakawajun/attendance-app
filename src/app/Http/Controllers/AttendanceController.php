<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    // 現在の勤怠情報を取得するアクション
    public function show()
    {
        $today = Attendance::getWorkingDate();
        $attendance = Attendance::with('rests')
            ->where('user_id', auth()->id())
            ->whereDate('work_date', $today)
            ->firstOrNew();

        return view('attendance', compact('attendance'));
    }

    // 出勤時刻を登録するアクション
    public function clockIn()
    {
        $today = Attendance::getWorkingDate();
        $exists = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', $today)
            ->exists();

        if($exists){
            return redirect()->back()->with('error','すでに出勤済です');
        }

        $attendance = new Attendance();
        $attendance->user_id = auth()->id();
        $attendance->work_date = $today->format('Y-m-d');
        $attendance->clock_in = now();
        $attendance->save();

        return redirect()->route('attendance.show')->with('success','出勤しました');
    }

    // 休憩開始時刻を登録するアクション
    public function restStart()
    {
        $today = Attendance::getWorkingDate();
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', $today)
            ->first();

        if(!$attendance){
            return redirect()->back()->with('error','出勤データが見つかりません');
        }

        if(!$attendance->canStartRest($error)) {
            return redirect()->back()->with('error',$error);
        }

        $attendance->rests()->create(['start_time' => now()]);

        return redirect()->back()->with('success','休憩を開始しました');
    }

    // 休憩終了時刻を登録するアクション
    public function restEnd()
    {
        $today = Attendance::getWorkingDate();
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', $today)
            ->first();

        if(!$attendance){
            return redirect()->back()->with('error','出勤データが見つかりません');
        }

        if(!$attendance->canEndRest($error, $latestRest)) {
            return redirect()->back()->with('error',$error);
        }

        $latestRest->update(['end_time' => now()]);

        return redirect()->back()->with('success','休憩を終了しました');
    }

    // 退勤時刻を登録するアクション
    public function clockOut()
    {
        $today = Attendance::getWorkingDate();
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', $today)
            ->first();

        if(!$attendance){
            return redirect()->back()->with('error','出勤データが見つかりません');
        }

        if(!$attendance->canClockOut($error)) {
            return redirect()->back()->with('error',$error);
        }

        $attendance->update(['clock_out' => now()]);

        return redirect()->back()->with('success','退勤しました');
    }

    // 月次の勤怠一覧を取得するアクション
    public function index($year = null,$month = null)
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        $startOfMonth = Carbon::create($year,$month,1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $attendances = Attendance::where('user_id',Auth::id())
            ->whereYear('work_date',$year)
            ->whereMonth('work_date',$month)
            ->with('rests')
            ->get()
            ->keyBy(function($item){
                return Carbon::parse($item->work_date)->format('Y-m-d');
            });

        $period = CarbonPeriod::create($startOfMonth,$endOfMonth);

        $calendar = [];
        foreach($period as $date){
            $dateStr = $date->format('Y-m-d');
            $calendar[] = [
                'date' => $date,
                'attendance' => $attendances->get($dateStr)
            ];
        }

        $prevDate = $startOfMonth->copy()->subMonth();
        $nextDate = $startOfMonth->copy()->addMonth();

        return view('attendance_list',compact(
            'calendar',
            'year',
            'month',
            'prevDate',
            'nextDate'
        ));
    }
}
