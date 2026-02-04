<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attendance;
use Carbon\Carbon;

class ApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $timeRegex = 'regex:/^\d{1,2}:[0-5][0-9]$/';

        return [
            'reason' => 'required| string| max:255',
            'attendance_start_time' => ['nullable',$timeRegex,'required_with:attendance_end_time'],
            'attendance_end_time' => ['nullable',$timeRegex,'required_with:attendance_start_time'],
            'rests.*.start_time' => ['nullable',$timeRegex,'required_with:rests.*.end_time'],
            'rests.*.end_time' => ['nullable',$timeRegex,'required_with:rests.*.start_time'],
            'new_rests.*.start_time' => ['nullable',$timeRegex,'required_with:new_rests.*.end_time'],
            'new_rests.*.end_time' => ['nullable',$timeRegex,'required_with:new_rests.*.start_time'],
        ];
    }

    public function messages()
    {
        return [
            'reason.required' => '備考を記入してください',
            'reason.string' => '備考は文字列で記入してください',
            'reason.max' => '備考は255文字以内で記入してください',
            '*.regex' => '時間は「00:00」の形式で入力してください',
            '*.required_with' => '開始時間と終了時間は両方入力してください'
        ];
    }

    public function withValidator($validator)
    {
        if($validator->errors()->count() > 0) return;

        $validator->after(function ($validator) {
            $attendanceId = $this->input('attendance_id');

            if(!$attendanceId) return;

            $baseDate = $this->input('work_date');
            $attendance = Attendance::with('rests')->find($attendanceId);

            if(!$attendance) return;

            $start = $this->parseTime($baseDate, $this->input('attendance_start_time'));
            $end = $this->parseTime($baseDate, $this->input('attendance_end_time'));

            if($start && $end && $start->greaterThanOrEqualTo($end)) {
                $validator->errors()->add('attendance_end_time','出勤時間もしくは退勤時間が不適切な値です');
            }

            $isChanged = false;

            if(!$attendance->clock_in->eq($start) || !$attendance->clock_out->eq($end)) {
                $isChanged = true;
            }

            $inputRests = $this->input('rests', []);

            if($attendance->rests->count() !== count($inputRests)) {
                $isChanged = true;
            } else {
                foreach($attendance->rests as $oldRest) {
                    $newTimes = $inputRests[$oldRest->id] ?? null;
                    if(!$newTimes) {
                        $isChanged = true;
                        break;
                    }
                    $newRestStart = $this->parseTime($baseDate, $newTimes['start_time']);
                    $newRestEnd = $this->parseTime($baseDate, $newTimes['end_time']);

                    if(!$oldRest->start_time->eq($newRestStart) || !$oldRest->end_time->eq($newRestEnd)) {
                        $isChanged = true;
                        break;
                    }
                }
            }

            foreach ($this->input('new_rests',[]) as $times) {
                if(!empty($times['start_time'])) {
                    $isChanged = true;
                    break;
                }
            }

            if(!$isChanged) {
                $validator->errors()->add('attendance_start_time','修正前と同一の内容のため申請できません');
            }

            foreach($this->input('rests',[]) as $id => $times) {
                $this->checkRestTimes($validator, $baseDate, $times,"rests.{$id}", $start, $end);
            }

            foreach($this->input('new_rests',[]) as $index => $times) {
                $this->checkRestTimes($validator, $baseDate, $times,"new_rests.{$index}", $start, $end);
            }
        });
    }

    private function checkRestTimes($validator, $baseDate, $times, $key, $workStart, $workEnd)
    {
        $restStart = $this->parseTime($baseDate, $times['start_time']);
        $restEnd = $this->parseTime($baseDate, $times['end_time']);

        if(!$restStart || !$restEnd) return;

        if($restStart->greaterThanOrEqualTo($restEnd)) {
            $validator->errors()->add("{$key}.end_time",'休憩時間が不適切な値です');
        }

        if($workStart && $restStart->lessThan($workStart)) {
            $validator->errors()->add("{$key}.start_time",'休憩時間が不適切な値です');
        }

        if($workEnd && $restEnd->greaterThan($workEnd)) {
            $validator->errors()->add("{$key}.end_time",'休憩時間もしくは退勤時間が不適切な値です');
        }
    }

    private function parseTime($baseDate, $inputTime)
    {
        if(empty($inputTime)) return null;
        $dt = Carbon::parse($baseDate);
        list($hour, $minute) = explode(':', $inputTime);
        return $dt->startOfDay()->addHours((int)$hour)->addMinutes((int)$minute);
    }
}
