<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DetailService;
use App\Services\AttendanceValidationService;
use App\Services\AttendanceRequestService;
use App\Services\AttendanceFinalizeService;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceRequest;
use Carbon\Carbon;
use App\Http\Requests\ApplicationRequest;

class AdminApproveController extends Controller
{
    // 勤怠詳細の表示
    public function showDetail(Request $request, $id, DetailService $service)
    {
        $data = $service->getDetailData(
            (int)$id,
            $request->query('user_id'),
            $request->query('date')
        );

        return view('admin.admin_attendance_detail', $data);
    }

    // スタッフの勤怠を直接修正するアクション
    public function directUpdate(
        ApplicationRequest $request,
        AttendanceValidationService $validationService,
        AttendanceRequestService $requestService,
        AttendanceFinalizeService $finalizeService
    ){
        $validationService->validate($request->all(), (int)$request->staff_id);

        $attendanceRequest = $requestService->createRequest($request->all(), (int)$request->staff_id);

        if(is_null($attendanceRequest)) {
            $workDate = Carbon::parse($request->work_date);

            return redirect()->route('admin.day_index',[
                    'year'  => $workDate->year,
                    'month' => $workDate->month,
                    'day'   => $workDate->day
            ])->with('error','この勤怠データは既に削除されているか、存在しません');
        }

        $finalizeRequest = DB::transaction(function() use ($attendanceRequest, $finalizeService) {
            return $finalizeService->apply($attendanceRequest->id);
        });

        $targetDate = Carbon::parse($finalizeRequest->target_date);

        return redirect()->route('admin.day_index',[
            'year'  => $targetDate->year,
            'month' => $targetDate->month,
            'day'   => $targetDate->day
        ])->with('success','修正を反映しました');
    }

    // 修正申請の詳細を表示するアクション
    public function showRequest(int $attendanceCorrectRequestId)
    {
        $attendanceRequest = AttendanceRequest::with(['user','attendance.rests','details.original'])
            ->findOrFail($attendanceCorrectRequestId);

        $attendanceDetail = $attendanceRequest->details->first(function ($detail) {
            return $detail->original_type === Attendance::class;
        });

        $restDetails = $attendanceRequest->details->filter(function ($detail) {
            return $detail->original_type === Rest::class;
        });

        return view('admin.admin_attendance_approving',[
            'attendanceRequest' => $attendanceRequest,
            'attendanceDetail'  => $attendanceDetail,
            'restDetails'       => $restDetails
        ]);
    }

    // 修正申請の承認を行うアクション
    public function approve(int $attendanceCorrectRequestId, AttendanceFinalizeService $finalizeService)
    {
        $finalizeService->apply($attendanceCorrectRequestId);

        return redirect()->route('admin.show_request',[
            'attendance_correct_request_id' => $attendanceCorrectRequestId
            ])->with('success','修正申請を承認しました');
    }
}