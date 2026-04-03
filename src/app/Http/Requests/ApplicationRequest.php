<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attendance;

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
                'reason' => ['required','string','max:255'],
            ];
        }

        $timeRegex = 'regex:/^\d{1,2}:[0-5][0-9]$/';

        return [
            'reason'                 => ['required','string','max:255'],
            'attendance_start_time'  => ['required', $timeRegex],
            'attendance_end_time'    => ['required', $timeRegex],
            'rests.*.start_time'     => ['nullable', $timeRegex,'required_with:rests.*.end_time'],
            'rests.*.end_time'       => ['nullable', $timeRegex,'required_with:rests.*.start_time'],
            'new_rests.*.start_time' => ['nullable', $timeRegex,'required_with:new_rests.*.end_time'],
            'new_rests.*.end_time'   => ['nullable', $timeRegex,'required_with:new_rests.*.start_time']
        ];
    }

    public function messages()
    {
        return [
            'reason.required'                      => '備考を記入してください',
            'reason.string'                        => '備考は文字列で記入してください',
            'reason.max'                           => '備考は255文字以内で記入してください',
            'attendance_start_time.required'       => '出勤時間と退勤時間は必ず入力してください',
            'attendance_end_time.required'         => '出勤時間と退勤時間は必ず入力してください',
            'attendance_start_time.regex'          => '時間は「00:00」の形式で入力してください',
            'attendance_end_time.regex'            => '時間は「00:00」の形式で入力してください',
            'rests.*.start_time.regex'             => '時間は「00:00」の形式で入力してください',
            'rests.*.end_time.regex'               => '時間は「00:00」の形式で入力してください',
            'new_rests.*.start_time.regex'         => '時間は「00:00」の形式で入力してください',
            'new_rests.*.end_time.regex'           => '時間は「00:00」の形式で入力してください',
            'rests.*.start_time.required_with'     => '開始時間と終了時間は両方入力してください',
            'rests.*.end_time.required_with'       => '開始時間と終了時間は両方入力してください',
            'new_rests.*.start_time.required_with' => '開始時間と終了時間は両方入力してください',
            'new_rests.*.end_time.required_with'   => '開始時間と終了時間は両方入力してください'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if($validator->errors()->any() || $this->input('request_type') === 'delete') return;

            $rawStart = $this->input('attendance_start_time');
            $rawEnd = $this->input('attendance_end_time');

            if(!$this->isNextDay($rawEnd) && $rawStart >= $rawEnd) {
                $validator->errors()->add('attendance_end_time','出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            $start = $this->toMinutes($rawStart);
            $end = $this->toMinutes($rawEnd);

            if($start >= $end) {
                $validator->errors()->add('attendance_end_time','出勤時間もしくは退勤時間が不適切な値です');
            }elseif($end >= 1740) {
                $validator->errors()->add('attendance_end_time','退勤時間は勤怠締め時刻「05:00」より前で入力してください');
            }

            $allRests = [];
            foreach(['rests','new_rests'] as $key){
                foreach($this->input($key,[]) as $index => $times) {
                    if(empty($times['start_time'])) continue;

                    $restStart = $this->toMinutes($times['start_time']);
                    $restEnd = $this->toMinutes($times['end_time']);

                    if($restStart <= $start || $restStart >= $end) {
                        $validator->errors()->add("{$key}.{$index}.start_time",'休憩時間が不適切な値です');
                    }elseif($restEnd <= $restStart || $restEnd <= $start || $restEnd >= $end) {
                        $validator->errors()->add("{$key}.{$index}.end_time",'休憩時間もしくは退勤時間が不適切な値です');
                    }elseif($restStart >= 1740) {
                        $validator->errors()->add("{$key}.{$index}.start_time",'休憩開始時間は勤怠締め時刻「05:00」より前で入力してください');
                    }elseif($restEnd >= 1740) {
                        $validator->errors()->add("{$key}.{$index}.end_time",'休憩終了時間は勤怠締め時刻「05:00」より前で入力してください');
                    }

                    $allRests[] = ['s' => $restStart, 'e' => $restEnd];
                }
            }

            $count = count($allRests);
            for($i = 0; $i < $count; $i++) {
                for($j = $i + 1; $j < $count; $j++) {
                    if ($allRests[$i]['s'] < $allRests[$j]['e'] && $allRests[$j]['s'] < $allRests[$i]['e']) {
                        $validator->errors()->add('rests','休憩時間が重なっています');
                        break 2;
                    }
                }
            }

            $attendanceId = $this->input('attendance_id');
            if($attendanceId) {
                $attendance = Attendance::with('rests')->find($attendanceId);
                if($attendance && !$this->checkIfChanged($attendance, $start, $end, $allRests)) {
                    $validator->errors()->add('data_inconsistency','修正前と同一の内容のため申請できません');
                }
            }
        });
    }

    // 勤怠締め時刻より前の「0:00〜4:59」の間かどうかを判定するメソッド
    private function isNextDay($timeStr)
    {
        if(!$timeStr) return false;
        list($hour) = explode(':', $timeStr);
        return (int)$hour <= 5;
    }

    // 入力した時刻を分換算するメソッド（5:00未満なら+1440分で翌日扱いに）
    private function toMinutes($timeStr)
    {
        if(empty($timeStr) || !str_contains($timeStr,':')) return null;

        list($hour, $minute) = explode(':', $timeStr);
        $minutes = (int)$hour * 60 + (int)$minute;

        if($minutes <= 300) {
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
            ? $attendance->clock_out->format('H:i') : null);

        if($start !== $dbStart || $end !== $dbEnd) return true;

        if($attendance->rests->count() !== count($allRests)) return true;

        $dbRests = $attendance->rests->map(fn($r) => [
            's' => $this->toMinutes($r->start_time->format('H:i')),
            'e' => $this->toMinutes($r->end_time->format('H:i'))
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