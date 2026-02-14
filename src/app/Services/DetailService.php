<?php

namespace App\Services;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Carbon\Carbon;

class DetailService
{
    public function getDetailData(int $id, ?int $userId = null, ?string $targetDate = null): array
    {
        if ($id === 0) {
            $staff = User::findOrFail($userId);
            $date = Carbon::parse($targetDate);
            $attendance = null;
            $pendingRequest = AttendanceRequest::with('details')
                ->where('user_id', $staff->id)
                ->whereDate('target_date', $date->format('Y-m-d'))
                ->where('status', AttendanceRequest::STATUS_PENDING)
                ->latest()
                ->first();
        } else {
            $query = Attendance::with(['rests', 'attendanceRequests' => function ($query) {
                $query->where('status', AttendanceRequest::STATUS_PENDING)
                    ->with('details')->latest();
            }]);

            if ($userId) {
                $query->where('user_id', $userId);
            }

            $attendance = $query->findOrFail($id);
            $staff = $attendance->user;
            $date = $attendance->work_date;
            $pendingRequest = $attendance->attendanceRequests->first();
        }

        return [
            'name'           => $staff->name,
            'attendance'     => $attendance,
            'date'           => $date,
            'pendingRequest' => $pendingRequest,
            'requestDetails' => $pendingRequest ? $pendingRequest->details : null,
        ];
    }
}