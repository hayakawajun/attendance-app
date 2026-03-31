<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceRequest;
use Illuminate\Support\Facades\DB;

class AttendanceFinalizeService
{
    /**
     * 申請内容を実際の勤怠・休憩データに反映する
     *
     * @param int $requestId 承認対象の attendance_requests.id
     */
    public function apply(int $requestId)
    {
        return DB::transaction(function () use ($requestId) {
            $attendanceRequest = AttendanceRequest::with('details')->findOrFail($requestId);

            // --- 1. 削除申請 (is_deletion = true) の場合 ---
            if($attendanceRequest->is_deletion) {
                if($attendanceRequest->attendance_id) {
                    // attendance_id に紐づく勤怠を削除（restsはcascadeで消える想定）
                    Attendance::where('id', $attendanceRequest->attendance_id)->delete();
                }
                return $this->finalizeStatus($attendanceRequest);
            }

            // --- 2. 修正・新規申請 (is_deletion = false) の場合 ---

            // a) まず Attendance（勤怠本体）を処理
            $attendanceDetail = $attendanceRequest->details->where('original_type', 'App\Models\Attendance')->first();
            $targetAttendanceId = $attendanceRequest->attendance_id;

            if($attendanceDetail) {
                if($attendanceDetail->original_id) {
                    // 既存データの更新
                    Attendance::where('id', $attendanceDetail->original_id)->update([
                        'clock_in'  => $attendanceDetail->start_time,
                        'clock_out' => $attendanceDetail->end_time
                    ]);
                    $targetAttendanceId = $attendanceDetail->original_id;
                }else{
                    // 全く新しい日の勤怠を作成
                    $newAttendance = Attendance::create([
                        'user_id'   => $attendanceRequest->user_id,
                        'work_date' => $attendanceRequest->target_date,
                        'clock_in'  => $attendanceDetail->start_time,
                        'clock_out' => $attendanceDetail->end_time,
                    ]);
                    // 新規作成された ID を保持し、後の休憩データの紐付けに使う
                    $targetAttendanceId = $newAttendance->id;

                    // 申請親レコードの attendance_id も更新しておく
                    $attendanceRequest->update(['attendance_id' => $targetAttendanceId]);
                }
            }

            // b) 次に Rest（休憩）を処理
            $restDetails = $attendanceRequest->details->where('original_type', 'App\Models\Rest');
            foreach($restDetails as $detail) {
                if($detail->original_id) {
                    if(is_null($detail->start_time) && is_null($detail->end_time)) {
                        Rest::where('id', $detail->original_id)->delete();
                    }else{
                        // 既存休憩の更新
                        Rest::where('id', $detail->original_id)->update([
                            'start_time' => $detail->start_time,
                            'end_time' => $detail->end_time,
                        ]);
                    }

                }else{
                    // 申請で新しく追加された休憩の作成
                    Rest::create([
                        'attendance_id' => $targetAttendanceId, // 採番されたIDを確実に紐付け
                        'start_time' => $detail->start_time,
                        'end_time' => $detail->end_time
                    ]);
                }
            }

            return $this->finalizeStatus($attendanceRequest);
        });
    }

    /**
     * 承認済みステータスの更新（共通）
     */
    private function finalizeStatus($attendanceRequest)
    {
        $attendanceRequest->update([
            'status' => AttendanceRequest::STATUS_APPROVED,
            'admin_id' => auth()->user()->id,
            'approved_at' => now(),
            'approved_by_name' => auth()->user()->name,
        ]);

        return $attendanceRequest;
    }
}