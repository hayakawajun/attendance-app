<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceRequestService
{
    public function createRequest(array $input, int $userId)
    {
        if(Auth::guard('admin')->check()) {
            $attendance = Attendance::find($input['attendance_id']);
            if(!$attendance && ($input['request_type'] === 'delete')) {
                return null;
            }
        }

        return DB::transaction(function () use ($input, $userId) {
            $attendanceId = ($input['attendance_id'] == 0) ? null : $input['attendance_id'];
            $isDeletion = ($input['request_type'] ?? '') === 'delete';
            $workDate = $input['work_date'];

            // 1. 申請親レコード（attendance_requests）の作成
            // 管理者の場合はステータスを最初から「承認済み」にし、承認者情報を記録する
            $attendanceRequest = AttendanceRequest::create([
                'attendance_id'    => $attendanceId,
                'user_id'          => $userId,
                'target_date'      => $workDate,
                'status'           => AttendanceRequest::STATUS_PENDING,
                'is_deletion'      => $isDeletion,
                'reason'           => $input['reason'],
                'requested_at'     => now(),
                'admin_id'         => null,
                'approved_at'      => null,
                'approved_by_name' => null
            ]);

            // 削除申請の場合は、書き換え内容（details）が存在しないためここで返却
            if ($isDeletion) {
                return $attendanceRequest;
            }

            // 2. 勤務時間の詳細レコード（attendance_request_details）作成
            $attendanceRequest->details()->create([
                'original_id'   => $attendanceId,
                'original_type' => Attendance::class,
                'start_time'    => $this->parseTimeWithNextDay($workDate, $input['attendance_start_time']),
                'end_time'      => $this->parseTimeWithNextDay($workDate, $input['attendance_end_time'])
            ]);

            // 3. 休憩時間の詳細レコード作成（既存分と新規追加分）
            $this->createRestDetails($attendanceRequest, $workDate, $input);

            return $attendanceRequest;
        });
    }

    // 休憩の詳細レコードを一括作成するメソッド
    private function createRestDetails($attendanceRequest, $workDate, $input)
    {
        // 既存休憩の修正（rests[id][start_time] 形式を想定）
        if (isset($input['rests']) && is_array($input['rests'])) {
            foreach ($input['rests'] as $restId => $times) {
                if (empty($times['start_time']) && empty($times['end_time'])) {
                    $attendanceRequest->details()->create([
                        'original_id'   => $restId,
                        'original_type' => Rest::class,
                        'start_time'    => null,
                        'end_time'      => null
                    ]);
                }else{
                    $attendanceRequest->details()->create([
                        'original_id'   => $restId,
                        'original_type' => Rest::class,
                        'start_time'    => $this->parseTimeWithNextDay($workDate, $times['start_time']),
                        'end_time'      => $this->parseTimeWithNextDay($workDate, $times['end_time'])
                    ]);
                }
            }
        }

        // 新規休憩の追加（new_rests[0][start_time] 形式を想定）
        if (isset($input['new_rests']) && is_array($input['new_rests'])) {
            foreach ($input['new_rests'] as $times) {
                if (!empty($times['start_time']) && !empty($times['end_time'])) {
                    $attendanceRequest->details()->create([
                        'original_id'   => null,
                        'original_type' => Rest::class,
                        'start_time'    => $this->parseTimeWithNextDay($workDate, $times['start_time']),
                        'end_time'      => $this->parseTimeWithNextDay($workDate, $times['end_time'])
                    ]);
                }
            }
        }
    }

    // 入力フォームからの時刻文字列をDB用の保存形式に変換するメソッド
    // 5:00未満なら翌日で処理
    private function parseTimeWithNextDay($workDate, $timeStr)
    {
        if(!$timeStr) return null;

        $dt = Carbon::parse($workDate);
        list($hour, $minute) = explode(':', $timeStr);

        $dt->hour((int)$hour)->minute((int)$minute)->second(0);

        if((int)$hour < 5) {
            $dt->addDay();
        }

        return $dt;
    }
}