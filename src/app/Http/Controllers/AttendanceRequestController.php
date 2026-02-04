<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\Rest;
use App\Models\AttendanceRequest;

class AttendanceRequestController extends Controller
{
    public function show(Request $request,$id)
    {
        $user = Auth::user();

        if($id == 0){
            $targetDate = $request->query('date');
            $date = Carbon::parse($targetDate);

            $pendingRequest = AttendanceRequest::with('details')
                ->where('user_id',$user->id)
                ->whereNull('attendance_id')
                ->where('status',AttendanceRequest::STATUS_PENDING)
                ->latest()
                ->first();

            return view('attendance_detail',[
                'name' => $user->name,
                'attendance' => null,
                'date' => $date,
                'pendingRequest' => $pendingRequest
            ]);
        }

        $attendance = Attendance::with(['rests','attendanceRequests' => function($query){
            $query->where('status', AttendanceRequest::STATUS_PENDING)
                ->with('details')->latest();
            }])
            ->where('user_id',$user->id)
            ->findOrFail($id);

        $pendingRequest = $attendance->attendanceRequests->first();

        return view('attendance_detail',[
            'name' => $user->name,
            'attendance' => $attendance,
            'date' => $attendance->work_date,
            'pendingRequest' => $pendingRequest
        ]);
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $attendanceRequest = AttendanceRequest::create([
                'attendance_id' => $request->input('attendance_id') == 0 ? null : $request->input('attendance_id'),
                'user_id' => auth()->id(),
                'status' => AttendanceRequest::STATUS_PENDING,
                'reason' => $request->reason,
                'requested_at' => now()
            ]);

            $baseDate = $request->work_date;

            $attendanceRequest->details()->create([
                'type' => 'attendance',
                'original_id' => $request->input('attendance_id') == 0 ? null : $request->input('attendance_id'),
                'original_type' => Attendance::class,
                'start_time' => $this->parseTime($baseDate, $request->input('attendance_start_time')),
                'end_time' => $this->parseTime($baseDate, $request->input('attendance_end_time'))
            ]);

            foreach($request->input('rests',[]) as $restId => $times){
                $attendanceRequest->details()->create([
                    'type' => 'rest',
                    'original_id' => $restId,
                    'original_type' => Rest::class,
                    'start_time' => $this->parseTime($baseDate,$times['start_time']),
                    'end_time' => $this->parseTime($baseDate,$times['end_time'])
                ]);
            }

            if($request->filled('new_rests.start_time')){
                $attendanceRequest->details()->create([
                    'type' => 'rest',
                    'original_id' => null,
                    'original_type' => Rest::class,
                    'start_time' => $this->parseTime($baseDate,$request->input('new_rests.start_time')),
                    'end_time' => $this->parseTime($baseDate,$request->input('new_rests.end_time'))
                ]);
            }
        });

        return redirect()->route('detail.show',[
            'id' => $request->input('attendance_id',0),
            'date' => $request->input('work_date')
        ])->with('success','修正申請を送信しました');
    }

    // 入力フォームからの文字列をDB用の保存形式に変換するメソッド
    private function parseTime($baseDate, $inputTime)
    {
        if(empty($inputTime)){
            return null;
        }

        $dt = Carbon::parse($baseDate);

        list($hour, $minute) = explode(':',$inputTime);

        return $dt->startOfDay()->addHours((int)$hour)->addMinutes((int)$minute);
    }
}
