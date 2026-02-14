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

            // すでにエラーが発生している場合や、削除ボタンが押された場合
            if($validator->errors()->any() || $this->input('request_type') === 'delete') return;

            $start = $this->toMinutes($this->input('attendance_start_time'));
            $end = $this->toMinutes($this->input('attendance_end_time'), $start);

            // 勤怠締め時刻「翌AM5:00(前日の0:00から起算して計1740分)」を超える退勤時間の場合
            if($end > 1740) {
                $validator->errors()->add('attendance_end_time','退勤時間は勤怠締め時刻「05:00」以前を入力してください');

            // 出勤時間が退勤時間よりも後になっている場合
            }elseif($start >= $end) {
                $validator->errors()->add('attendance_end_time','出勤時間もしくは退勤時間が不適切な値です');
            }

            $allRests = [];
            foreach(['rests', 'new_rests'] as $key) {
                foreach($this->input($key, []) as $index => $times) {
                    if(empty($times['start_time'])) continue;

                    $restStart = $this->toMinutes($times['start_time'], $start);
                    $restEnd = $this->toMinutes($times['end_time'], $restStart);

                    // 勤怠締め時刻の翌AM5:00を超える休憩開始時間の場合
                    if($restStart > 1740) {
                        $validator->errors()->add("{$key}.{$index}.start_time",'休憩開始時間は勤怠締め時刻「05:00」以前を入力してください');

                    // 勤怠締め時刻の翌AM5:00を超える休憩終了時間の場合
                    }elseif($restEnd > 1740) {
                        $validator->errors()->add("{$key}.{$index}.end_time",'休憩終了時間は勤怠締め時刻「05:00」以前を入力してください');

                    // 休憩開始時間が出勤時間より前、および退勤時間より後の場合
                    }elseif($restStart <= $start || $restStart >= $end) {
                        $validator->errors()->add("{$key}.{$index}.start_time",'休憩時間が不適切な値です');

                    // 休憩終了時間が退勤時間より後、および出勤時間より前の場合
                    }elseif($restEnd >= $end || $restEnd <= $start) {
                        $validator->errors()->add("{$key}.{$index}.end_time",'休憩時間もしくは退勤時間が不適切な値です');
                    }

                    $allRests[] = ['s' => $restStart, 'e' => $restEnd];
                }
            }

            // 休憩Aと休憩Bの時間帯の一部が重なっている場合
            $count = count($allRests);
            for($i = 0; $i < $count; $i++) {
                for($j = $i + 1; $j < $count; $j++) {
                    if($allRests[$i]['s'] < $allRests[$j]['e'] && $allRests[$j]['s'] < $allRests[$i]['e']) {
                        $validator->errors()->add('rests', '休憩時間が重なっています');
                        break 2;
                    }
                }
            }

            // 入力値が既存値から変わっていなかった場合
            $attendanceId = $this->input('attendance_id');
            if($attendanceId) {
                $attendance = Attendance::with('rests')->find($attendanceId);
                if($attendance && !$this->checkIfChanged($attendance, $start, $end, $allRests)) {
                    $validator->errors()->add('data_inconsistency','修正前と同一の内容のため申請できません');
                }
            }
        });
    }

    // 入力時間を分に換算するメソッド
    private function toMinutes($timeStr, $baseMinutes = null)
    {
        if(empty($timeStr) || !str_contains($timeStr, ':')) return null;

        list($hour, $minute) = explode(':', $timeStr);
        $minutes = (int)$hour * 60 +(int)$minute;

        // 日またぎ勤務をした場合は、入力時間に1440分(24時間)を加える
        // 勤怠締め時刻のAM5:00を超えた入力の場合は入力ミスとして扱う
        if($baseMinutes !== null && $minutes < $baseMinutes) {
            $minutes += 1440;
        }
        return $minutes;
    }

    // 入力値をデータベースの既存値と比較するメソッド
    private function checkIfChanged($attendance, $start, $end, $allRests)
    {
        $dbStart = $this->toMinutes($attendance->clock_in
            ? $attendance->clock_in->format('H:i') : null);
        $dbEnd = $this->toMinutes($attendance->clock_out
            ? $attendance->clock_out->format('H:i') : null, $dbStart);

        if($start !== $dbStart || $end !== $dbEnd) return true;

        if($attendance->rests->count() !== count($allRests)) return true;
        $dbRests = $attendance->rests->map(fn($r) => [
            's' => $this->toMinutes($r->start_time->format('H:i'), $dbStart),
            'e' => $this->toMinutes($r->end_time->format('H:i'), $dbStart)
        ])->sortBy('s')->values()->toArray();

        usort($allRests, fn($a, $b) => $a['s'] <=> $b['s']);

        for($i = 0; $i < count($allRests); $i++) {
            if($allRests[$i]['s'] !== $dbRests[$i]['s'] || $allRests[$i]['e'] !== $dbRests[$i]['e']) {
                return true;
            }
        }

        return false;
    }
}



