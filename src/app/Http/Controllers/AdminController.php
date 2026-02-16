<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;

use Carbon\Carbon;

class AdminController extends Controller
{
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

    public function staffIndex()
    {
        $staffs = User::all();

        return view('admin.admin_staff_list',compact('staffs'));
    }
}
