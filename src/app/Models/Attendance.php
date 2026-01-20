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
        'clock_out =>datetime'
    ];

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }
}
