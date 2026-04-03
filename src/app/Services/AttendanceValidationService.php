<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AttendanceValidationService
{
    public function Validate(array $input, int $staffId)
    {
        $workDate = $input['work_date'];
        $attendanceId = $input['attendance_id'] ?? 0;
        $isDeletion = ($input['request_type'] ?? '') === 'delete';

        $existingRequest = AttendanceRequest::where('user_id', $staffId)
            ->where('target_date', $workDate)
            ->where('status', AttendanceRequest::STATUS_PENDING)
            ->exists();

        if ($existingRequest) {
            $this->throwError('data_inconsistency','この日付の申請は承認依頼済み、または処理待ちです');
        }

        $currentWorkingDate = Attendance::getWorkingDate()->startOfDay();
        $inputDate = Carbon::parse($workDate)->startOfDay();

        if ($inputDate->greaterThanOrEqualTo($currentWorkingDate)) {
            $this->throwError('data_inconsistency','当日分および翌日以降の勤怠は操作できません');
        }

        if (!$isDeletion && $attendanceId != 0) {
            $attendance = Attendance::find($attendanceId);
            if ($attendance && !$attendance->clock_out && empty($input['attendance_end_time'])) {
                $this->throwError('attendance_end_time','退勤打刻が完了していない勤怠は修正できません');
            }
        }
    }

    private function throwError($key, $message)
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}