<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    public function show()
    {
        $today = Attendance::getWorkingDate();
        $attendance = Attendance::with('rests')
            ->where('user_id', auth()->id())
            ->whereDate('work_date', $today)
            ->firstOrNew();

        return view('attendance', compact('attendance'));
    }

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

    public function restStart()
    {
        $today = Attendance::getWorkingDate();
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', $today)
            ->first();

        if(!$attendance){
            return redirect()->back()->with('error','出勤データが見つかりません');
        }
        if($attendance->rests()->whereNull('end_time')->exists()){
            return redirect()->back()->with('error','すでに休憩中です');
        }

        $count = $attendance->rests()->count();
        $attendance->rests()->create([
            'rest_number' => $count + 1,
            'start_time' => now()
        ]);

        return redirect()->back()->with('success','休憩を開始しました');
    }

    public function restEnd()
    {
        $today = Attendance::getWorkingDate();
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', $today)
            ->first();

        if(!$attendance){
            return redirect()->back()->with('error','出勤データが見つかりません');
        }

        $latestRest = $attendance->rests()->whereNull('end_time')->latest()->first();

        if(!$latestRest){
            return redirect()->back()->with('error','休憩中ではないか、またはすでに休憩を終了しています');
        }

        $latestRest->update([
            'end_time' => now()
        ]);

        return redirect()->back()->with('success','休憩を終了しました');
    }

    public function clockOut()
    {
        $today = Attendance::getWorkingDate();
        $attendance = Attendance::where('user_id', auth()->id())
            ->whereDate('work_date', $today)
            ->first();

        if(!$attendance){
            return redirect()->back()->with('error','出勤データが見つかりません');
        }
        if($attendance->clock_out){
            return redirect()->back()->with('error','すでに退勤済みです');
        }
        if($attendance->rests()->whereNull('end_time')->exists()){
            return redirect()->back()->with('error','休憩を終了させてから退勤してください');
        }

        $attendance->update([
            'clock_out' => now()
        ]);

        return redirect()->back()->with('success','退勤しました');
    }
}
