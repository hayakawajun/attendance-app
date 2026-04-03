<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DetailService;
use App\Services\AttendanceValidationService;
use App\Services\AttendanceRequestService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ApplicationRequest;

class AttendanceRequestController extends Controller
{
    // 勤怠詳細情報を取得するアクション
    public function showDetail(Request $request, $id, DetailService $service)
    {
        $data = $service->getDetailData((int)$id, auth()->id(), $request->query('date'));

        return view('attendance_detail', $data);
    }

    // 勤怠の修正申請を行うアクション
    public function apply(
        ApplicationRequest $request,
        AttendanceValidationService $validationService,
        AttendanceRequestService $requestService
    ){
        $data = DB::transaction(function() use ($request, $validationService, $requestService) {
            $validationService->validate($request->all(), (int)$request->staff_id, true);

            $attendanceRequest = $requestService->createRequest($request->all(), (int)$request->staff_id, false);

            return $attendanceRequest;
        });

        $targetDate = Carbon::parse($data->target_date);

        return redirect()->route('detail.show',[
            'id'   => $data->attendance_id ?? 0,
            'date' => $targetDate->format('Y-m-d')
        ])->with('success','修正申請を送信しました');
    }
}