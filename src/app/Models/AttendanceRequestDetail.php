<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequestDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_request_id',
        'original_id',
        'original_type',
        'start_time',
        'end_time'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];

    public function original()
    {
        return $this->morphTo();
    }

    public function attendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class);
    }
}
