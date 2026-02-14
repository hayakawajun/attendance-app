<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DetailService;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Carbon\Carbon;
use App\Http\Requests\ApplicationRequest;

class AdminApproveController extends Controller
{
    public function showDetail(Request $request, $id, DetailService $service)
    {
        $data = $service->getDetailData(
            (int)$id,
            $id == 0 ? $request->query('user_id') : null,
            $request->query('date')
        );

        return view('admin.admin_attendance_detail', $data);

        /**if($id == 0){
            $userId = $request->query('user_id');
            $targetDate = $request->query('date');
            $staff = User::findOrFail($userId);
            $date = Carbon::parse($targetDate);
            $attendance = null;
            $pendingRequest = AttendanceRequest::with('details')
                ->where('user_id', $staff->id)
                ->whereDate('target_date', $date->format('Y-m-d'))
                ->where('status',AttendanceRequest::STATUS_PENDING)
                ->latest()
                ->first();
        }else{
            $attendance = Attendance::with(['rests','attendanceRequests' => function($query){
                $query->where('status', AttendanceRequest::STATUS_PENDING)
                    ->with('details')->latest();
            }])
            ->findOrFail($id);

            $staff = $attendance->user;
            $date = $attendance->work_date;
            $pendingRequest = $attendance->attendanceRequests->first();
        }

        $requestDetails = null;
        if($pendingRequest) {
            $requestDetails = $pendingRequest->details;
        }

        return view('admin.admin_attendance_detail',[
            'name' => $staff->name,
            'attendance' => $attendance,
            'date' => $date,
            'pendingRequest' => $pendingRequest,
            'requestDetails' => $requestDetails
        ]);**/
    }

    public function updateOrCreate(ApplicationRequest $request)
    {

    }
}
