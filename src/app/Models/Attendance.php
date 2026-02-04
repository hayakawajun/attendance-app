<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out'
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime'
    ];

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

    public function attendanceRequests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function attendanceRequestDetails()
    {
        return $this->morphMany(AttendanceRequestDetail::class,'original');
    }

    // 現在の勤怠ステータスを判定するアクセサ status
    public function getStatusAttribute()
    {
        if(!$this->exists || is_null($this->clock_in)){
            return '勤務外';
        }
        if($this->clock_out){
            return '退勤済';
        }
        $isResting = $this->rests->where('end_time', null)->isNotEmpty();
        if($isResting){
            return '休憩中';
        }
        return '出勤中';
    }

    // 現在日時を取得するアクセサ today_display
    public function getTodayDisplayAttribute()
    {
        return now()->isoFormat('YYYY年M月D日(ddd)');
    }

    // 毎朝 05:00 を1日の勤務区切りとして勤怠を締める、日付判定の基準となる静的メソッド
    public static function getWorkingDate()
    {
        $now = now();

        return $now->hour < 5 ? $now->subDay() : $now;
    }

    // 休憩時間の合計を計算するアクセサ rest_minutes
    public function getRestMinutesAttribute()
    {
        return $this->rests->sum(function($rest){
            if(!$rest->start_time || !$rest->end_time) return 0;
            $restStart = Carbon::parse($rest->start_time)->second(0);
            $restEnd = Carbon::parse($rest->end_time)->second(0);

            return $restStart->diffInMinutes($restEnd);
        });
    }

    // 休憩時間の合計をH:iの出力形式で返すアクセサ total_rest_time
    public function getTotalRestTimeAttribute()
    {
        $minutes = $this->rest_minutes;

        return sprintf('%d:%02d',floor($minutes / 60),$minutes % 60);
    }

    // 勤務時間から休憩時間の合計を引いた、実働時間を計算してH:iの出力形式で返すアクセサ total_working_time
    public function getTotalWorkingTimeAttribute()
    {
        if(!$this->clock_in || !$this->clock_out) return '';
        $workStart = Carbon::parse($this->clock_in)->second(0);
        $workEnd = Carbon::parse($this->clock_out)->second(0);
        $totalMInutes = $workStart->diffInMinutes($workEnd);

        $actualMinutes = $totalMInutes - $this->rest_minutes;
        $actualMinutes = max(0,$actualMinutes);

        return sprintf('%d:%02d',floor($actualMinutes / 60),$actualMinutes % 60);
    }
}
