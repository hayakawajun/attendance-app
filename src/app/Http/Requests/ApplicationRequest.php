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
        if ($this->input('request_type') === 'delete') {
            return [
                'reason' => ['required', 'string', 'max:255'],
            ];
        }

        $timeRegex = 'regex:/^\d{1,2}:[0-5][0-9]$/';

        return [
            'reason' => ['required','string','max:255'],
            'attendance_start_time' => ['required',$timeRegex],
            'attendance_end_time' => ['required',$timeRegex],
            'rests.*.start_time' => ['nullable',$timeRegex,'required_with:rests.*.end_time'],
            'rests.*.end_time' => ['nullable',$timeRegex,'required_with:rests.*.start_time'],
            'new_rests.*.start_time' => ['nullable',$timeRegex,'required_with:new_rests.*.end_time'],
            'new_rests.*.end_time' => ['nullable',$timeRegex,'required_with:new_rests.*.start_time']
        ];
    }

    public function messages()
    {
        return [
            'reason.required' => '備考を記入してください',
            'reason.string' => '備考は文字列で記入してください',
            'reason.max' => '備考は255文字以内で記入してください',
            'attendance_start_time.required' => '出勤時間と退勤時間は必ず入力してください',
            'attendance_end_time.required' => '出勤時間と退勤時間は必ず入力してください',
            'attendance_start_time.regex' => '時間は「00:00」の形式で入力してください',
            'attendance_end_time.regex' => '時間は「00:00」の形式で入力してください',
            'rests.*.start_time.regex' => '時間は「00:00」の形式で入力してください',
            'rests.*.end_time.regex' => '時間は「00:00」の形式で入力してください',
            'new_rests.*.start_time.regex' => '時間は「00:00」の形式で入力してください',
            'new_rests.*.end_time.regex' => '時間は「00:00」の形式で入力してください',
            'rests.*.start_time.required_with' => '開始時間と終了時間は両方入力してください',
            'rests.*.end_time.required_with' => '開始時間と終了時間は両方入力してください',
            'new_rests.*.start_time.required_with' => '開始時間と終了時間は両方入力してください',
            'new_rests.*.end_time.required_with' => '開始時間と終了時間は両方入力してください'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('request_type') === 'delete') {
                return;
            }

            $attendanceId = $this->input('attendance_id');

            if(!$attendanceId) return;

            $baseDate = $this->input('work_date');
            $attendance = Attendance::with('rests')->find($attendanceId);

            if(!$attendance) return;

            $start = $this->parseTime($baseDate, $this->input('attendance_start_time'));
            $end = $this->parseTime($baseDate, $this->input('attendance_end_time'), $start);

            $tempEnd = ($end === false)
            ? $this->parseTime($baseDate, $this->input('attendance_end_time'))
            : $end;

            if($start && $tempEnd && $start->greaterThanOrEqualTo($tempEnd)) {
                $validator->errors()->add('attendance_end_time', '出勤時間もしくは退勤時間が不適切な値です');
            }

            if($end === false) {
                $validator->errors()->add('attendance_end_time','退勤時間は勤怠締め時刻「05:00」以前を入力してください');
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
                $validator->errors()->add('data_inconsistency','修正前と同一の内容のため申請できません');
            }

            foreach($this->input('rests',[]) as $id => $times) {
                $this->checkRestTimes($validator, $baseDate, $times,"rests.{$id}", $start, $tempEnd);
            }

            foreach($this->input('new_rests',[]) as $index => $times) {
                $this->checkRestTimes($validator, $baseDate, $times,"new_rests.{$index}", $start, $tempEnd);
            }
        });
    }

    private function checkRestTimes($validator, $baseDate, $times, $key, $workStart, $workEnd)
{
    $restStart = $this->parseTime($baseDate, $times['start_time'], $workStart);
    $restEnd = $this->parseTime($baseDate, $times['end_time'], $workStart);

    // --- 補正ロジックを修正 ---
    // false（5時超え）だった場合、単純に当日戻すのではなく、
    // 開始時間より数字が小さければ「翌日」として計算し直す
    $tempRestStart = $restStart;
    if ($restStart === false) {
        $tempRestStart = $this->parseTime($baseDate, $times['start_time']);
        if ($workStart && $tempRestStart->lessThan($workStart)) {
            $tempRestStart->addDay();
        }
    }

    $tempRestEnd = $restEnd;
    if ($restEnd === false) {
        $tempRestEnd = $this->parseTime($baseDate, $times['end_time']);
        if ($workStart && $tempRestEnd->lessThan($workStart)) {
            $tempRestEnd->addDay();
        }
    }
    // -------------------------

    if(!$tempRestStart || !$tempRestEnd) return;

    if($tempRestStart->greaterThanOrEqualTo($tempRestEnd)) {
        $validator->errors()->add("{$key}.end_time",'休憩時間が不適切な値です');
    }

    if(($workStart && $tempRestStart->lessThan($workStart)) || ($workEnd && $tempRestStart->greaterThan($workEnd))) {
        $validator->errors()->add("{$key}.start_time",'休憩時間が不適切な値です');
    }

    // これで 翌日06:00 > 翌日04:00 という比較になり、正しくエラーが出ます
    if($workEnd && $tempRestEnd->greaterThan($workEnd)) {
        $validator->errors()->add("{$key}.end_time",'休憩時間もしくは退勤時間が不適切な値です');
    }
}

    private function parseTime($baseDate, $inputTime, $startTime = null)
    {
        if(empty($inputTime) || !str_contains($inputTime, ':')) {
            return null;
        }

        $dt = Carbon::parse($baseDate)->startOfDay();
        list($hour, $minute) = explode(':', $inputTime);

        $time = $dt->copy()->addHours((int)$hour)->addMinutes((int)$minute);

        if($startTime) {
            if($time->lessThan($startTime)) {
                $time->addDay();
            }

            $limit = $dt->copy()->addDay()->addHours(5);

            if ($time->greaterThan($limit)) {
                return false;
            }
        }

        return $time;
    }
}
