<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AdminController extends Controller
{
    // 日ごとの勤怠一覧を取得するアクション
    public function dayIndex($year = null, $month = null, $day = null)
    {
        try {
            if($year && $month && $day) {
                $date = Carbon::createFromDate($year, $month, $day);
            } else {
                $date = Carbon::today();
            }

        } catch (\Exception $e) {
            $date = Carbon::today();
        }
        $attendances = Attendance::with('user','rests','attendanceRequests')
            ->whereDate('work_date',$date->format('Y-m-d'))
            ->get();

        $statusPending = AttendanceRequest::STATUS_PENDING;

        return view('admin.admin_attendance_list',compact('attendances','date','statusPending'));
    }


    // スタッフ一覧を取得するアクション
    public function staffIndex()
    {
        $staffs = User::all();

        return view('admin.admin_staff_list',compact('staffs'));
    }

    // スタッフ別の月次勤怠一覧を取得するアクション
    public function individualIndex(User $id, $year = null, $month = null)
    {
        $staff = $id;
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        $startOfMonth = Carbon::create($year,$month,1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $staff->id)
            ->whereYear('work_date',$year)
            ->whereMonth('work_date',$month)
            ->with('rests')
            ->get()
            ->keyBy(function($item){
                return Carbon::parse($item->work_date)->format('Y-m-d');
            });

        $requests = AttendanceRequest::where('user_id', $staff->id)
            ->whereBetween('target_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->groupBy(function($item){
                return Carbon::parse($item->target_date)->format('Y-m-d');
            });

        $period = CarbonPeriod::create($startOfMonth,$endOfMonth);

        $calendar = [];
        foreach($period as $date){
            $dateStr = $date->format('Y-m-d');
            $latestRequest = $requests->has($dateStr)
                ? $requests->get($dateStr)->sortByDesc('created_at')->first()
                : null;

            $calendar[] = [
                'date' => $date,
                'attendance' => $attendances->get($dateStr),
                'latestRequest' => $latestRequest
            ];
        }

        $prevDate = $startOfMonth->copy()->subMonth();
        $nextDate = $startOfMonth->copy()->addMonth();

        return view('admin.admin_individual_list',compact(
            'staff',
            'calendar',
            'year',
            'month',
            'prevDate',
            'nextDate'
        ))->with(['statusPending' => AttendanceRequest::STATUS_PENDING]);
    }

    // スタッフ別の月次勤怠一覧をCSV形式で出力するアクション
    public function exportCsv($id, $year, $month)
    {
        $staff = User::findOrFail($id);
        $attendances = Attendance::where('user_id', $id)
            ->whereYear('work_date', $year)
            ->whereMonth('work_date', $month)
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->work_date)->format('Y-m-d');
            });

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        return response()->streamDownload(function () use ($staff, $attendances, $period) {
            $file = fopen('php://output', 'w');
            stream_filter_append($file, 'convert.iconv.utf-8/cp932//TRANSLIT');
            fputcsv($file,['氏名','日付','出勤','退勤','休憩','合計']);

            $isFirst = true;

            foreach($period as $date) {
                $dateString = $date->format('Y-m-d');
                $date->locale('ja');

                $record = $attendances->get($dateString);

                fputcsv($file, [
                    $isFirst ? $staff->name : '',
                    $date->isoFormat('YYYY年M月D日(ddd)'),
                    $record ? ($record->clock_in ? Carbon::parse($record->clock_in)->format('H:i') : '') : '',
                    $record ? ($record->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '') : '',
                    $record ? $record->total_rest_time : '',
                    $record ? $record->total_working_time : ''
                ]);
                $isFirst = false;
            }
            fclose($file);
        }, "{$staff->name}さん_{$year}年{$month}月の勤怠一覧.csv");
    }
}
