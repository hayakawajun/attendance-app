<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceRequest;
use App\Http\Requests\ApplicationRequest;

class AttendanceRequestController extends Controller
{
    public function show(Request $request,$id)
    {
        $user = Auth::user();

        if($id == 0){
            $targetDate = $request->query('date');
            $date = Carbon::parse($targetDate);
            $attendance = null;
            $pendingRequest = AttendanceRequest::with('details')
                ->where('user_id',$user->id)
                ->whereDate('target_date', $date->format('Y-m-d'))
                ->where('status',AttendanceRequest::STATUS_PENDING)
                ->latest()
                ->first();
        }else{
            $attendance = Attendance::with(['rests','attendanceRequests' => function($query){
                $query->where('status', AttendanceRequest::STATUS_PENDING)
                    ->with('details')->latest();
            }])
            ->where('user_id',$user->id)
            ->findOrFail($id);

            $date = $attendance->work_date;
            $pendingRequest = $attendance->attendanceRequests->first();
        }

        $requestDetails = null;
        if($pendingRequest) {
            $requestDetails = $pendingRequest->details->groupBy('type');
        }

        return view('attendance_detail',[
            'name' => $user->name,
            'attendance' => $attendance,
            'date' => $date,
            'pendingRequest' => $pendingRequest,
            'requestDetails' => $requestDetails
        ]);
    }

    public function store(ApplicationRequest $request)
    {
        $workDate = $request->input('work_date');

        $existingRequest = AttendanceRequest::where('user_id', auth()->id())
            ->where('target_date',$workDate)
            ->where('status', AttendanceRequest::STATUS_PENDING)
            ->exists();

        if($existingRequest) {
            return back()->withErrors(['data_inconsistency' => 'この日付の申請は承認依頼済みです']);
        }

        $currentWorkingDate = Attendance::getWorkingDate()->startOfDay();
        $inputDate = Carbon::parse($workDate)->startOfDay();

        if($inputDate->greaterThanOrEqualTo($currentWorkingDate)) {
            return back()->withErrors([
                'data_inconsistency' => '当日分の勤怠および翌日以降の勤怠は修正申請できません'
            ])->withInput();
        }

        $attendanceId = $request->input('attendance_id') == 0
            ? null
            : $request->input('attendance_id');

        if($request->input('request_type') === 'delete') {
            if(!$attendanceId) {
                return back()->withErrors(['data_inconsistency' => '登録されていない勤怠の削除申請はできません'])->withInput();
            }

            $attendance = Attendance::find($attendanceId);
            if(!$attendance) {
                return back()->withErrors(['data_inconsistency' => '指定された勤怠データが見つかりません'])->withInput();
            }

            AttendanceRequest::create([
                'attendance_id' => $attendanceId,
                'user_id' => auth()->id(),
                'target_date' => $workDate,
                'status' => AttendanceRequest::STATUS_PENDING,
                'is_deletion' => true,
                'reason' => $request->reason,
                'requested_at' => now()
            ]);

            return redirect()->route('detail.show',[
                'id' => $attendanceId,
                'date' => $attendance->work_date
            ])->with('success','削除申請を送信しました');
        }

        /* 毎日AM5:01に退勤・休憩終了の未打刻レコードを自動で埋めるよう設定していますが、
         * ローカルではその時刻にLaravelが起動しておらず、自動処理が行われない場合が考えられるので、
         * 下記のような退勤未打刻の勤怠に対して修正申請を受付けない記述を残しています。
        */
        if($request->filled('attendance_id')) {
            $attendance = Attendance::find($request->attendance_id);

            if(!$attendance->clock_out && !$request->filled('attendance_end_time')) {
                return back()->withErrors([
                    'attendance_end_time' => '退勤打刻が完了していない勤怠は修正できません'
                ])->withInput();
            }
        }

        DB::transaction(function () use ($request, $attendanceId, $workDate) {
            $attendanceRequest = AttendanceRequest::create([
                'attendance_id' => $attendanceId,
                'user_id' => auth()->id(),
                'target_date' => $workDate,
                'status' => AttendanceRequest::STATUS_PENDING,
                'is_deletion' => false,
                'reason' => $request->reason,
                'requested_at' => now()
            ]);

            $baseDate = $request->work_date;

            $attendanceRequest->details()->create([
                'type' => 'attendance',
                'original_id' => $attendanceId,
                'original_type' => Attendance::class,
                'start_time' => $this->parseTime($baseDate, $request->input('attendance_start_time')),
                'end_time' => $this->parseTime($baseDate, $request->input('attendance_end_time'))
            ]);

            foreach($request->input('rests',[]) as $restId => $times){
                if(!empty($times['start_time']) && !empty($times['end_time'])) {
                    $attendanceRequest->details()->create([
                        'type' => 'rest',
                        'original_id' => $restId,
                        'original_type' => Rest::class,
                        'start_time' => $this->parseTime($baseDate, $times['start_time']),
                        'end_time' => $this->parseTime($baseDate, $times['end_time'])
                    ]);
                }
            }

            if($request->filled('new_rests.0.start_time')) {
                $attendanceRequest->details()->create([
                    'type' => 'rest',
                    'original_id' => null,
                    'original_type' => Rest::class,
                    'start_time' => $this->parseTime($baseDate, $request->input('new_rests.0.start_time')),
                    'end_time' => $this->parseTime($baseDate, $request->input('new_rests.0.end_time'))
                ]);
            }
        });

        return redirect()->route('detail.show',[
            'id' => $attendanceId ?? 0,
            'date' => $request->input('work_date')
        ])->with('success','修正申請を送信しました');
    }

    // 入力フォームからの文字列をDB用の保存形式に変換するメソッド
    private function parseTime($baseDate, $inputTime)
    {
        if(empty($inputTime) || !str_contains($inputTime, ':')) {
            return null;
        }

        $dt = Carbon::parse($baseDate);

        $parts = explode(':', $inputTime);
        if(count($parts) < 2) {
            return null;
        }
        $hour = $parts[0];
        $minute = $parts[1];

        return $dt->startOfDay()->addHours((int)$hour)->addMinutes((int)$minute);
    }
}
