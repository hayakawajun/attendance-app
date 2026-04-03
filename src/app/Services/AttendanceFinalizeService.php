<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceRequest;
use Illuminate\Support\Facades\DB;

class AttendanceFinalizeService
{
    public function apply(int $requestId)
    {
        return DB::transaction(function () use ($requestId) {
            $attendanceRequest = AttendanceRequest::with('details')->findOrFail($requestId);

            if($attendanceRequest->is_deletion) {
                if($attendanceRequest->attendance_id) {
                    Attendance::where('id', $attendanceRequest->attendance_id)->delete();
                }
                return $this->finalizeStatus($attendanceRequest);
            }

            $attendanceDetail = $attendanceRequest->details
                ->where('original_type','App\Models\Attendance')
                ->first();
            $targetAttendanceId = $attendanceRequest->attendance_id;

            if($attendanceDetail) {
                if($attendanceDetail->original_id) {
                    Attendance::where('id', $attendanceDetail->original_id)->update([
                        'clock_in'  => $attendanceDetail->start_time,
                        'clock_out' => $attendanceDetail->end_time
                    ]);
                    $targetAttendanceId = $attendanceDetail->original_id;
                }else{
                    $newAttendance = Attendance::create([
                        'user_id'   => $attendanceRequest->user_id,
                        'work_date' => $attendanceRequest->target_date,
                        'clock_in'  => $attendanceDetail->start_time,
                        'clock_out' => $attendanceDetail->end_time
                    ]);
                    $targetAttendanceId = $newAttendance->id;

                    $attendanceRequest->update(['attendance_id' => $targetAttendanceId]);
                }
            }

            $restDetails = $attendanceRequest->details->where('original_type','App\Models\Rest');
            foreach($restDetails as $detail) {
                if($detail->original_id) {
                    if(is_null($detail->start_time) && is_null($detail->end_time)) {
                        Rest::where('id', $detail->original_id)->delete();
                    }else{
                        Rest::where('id', $detail->original_id)->update([
                            'start_time' => $detail->start_time,
                            'end_time'   => $detail->end_time
                        ]);
                    }

                }else{
                    Rest::create([
                        'attendance_id' => $targetAttendanceId,
                        'start_time'    => $detail->start_time,
                        'end_time'      => $detail->end_time
                    ]);
                }
            }

            return $this->finalizeStatus($attendanceRequest);
        });
    }

    // 申請のステータスを「承認済み」にして管理者情報を登録するメソッド
    private function finalizeStatus($attendanceRequest)
    {
        $attendanceRequest->update([
            'status'           => AttendanceRequest::STATUS_APPROVED,
            'admin_id'         => auth()->user()->id,
            'approved_at'      => now(),
            'approved_by_name' => auth()->user()->name
        ]);

        return $attendanceRequest;
    }
}