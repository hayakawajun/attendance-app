<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

// 現在の勤怠ステータスを判定するメソッド
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

// 現在日時を取得するメソッド
    public function getTodayDisplayAttribute()
    {
        return now()->isoFormat('YYYY年M月D日(ddd)');
    }

// 毎朝 05:00 を1日の勤務区切りとして勤怠を締める、日付判定の基準となる静的メソッド
    public static function getWorkingDate()
    {
        $now = now();

        return $now->hour < 5 ? $now->subDay()->toDateString() : $now->toDateString();
    }
}
