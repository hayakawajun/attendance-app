<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'attendance_id',
        'user_id',
        'admin_id',
        'target_date',
        'is_deletion',
        'status',
        'reason',
        'requested_at'
    ];

    protected $casts = [
        'target_date' => 'date',
        'requested_at' => 'datetime'
    ];

    public function details()
    {
        return $this->hasMany(AttendanceRequestDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
