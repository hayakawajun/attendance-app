<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Attendance;


class AttendanceController extends Controller
{
    public function show()
    {
        $attendance = Attendance::with('rests')
            ->where('user_id', auth()->id())
            ->whereDate('work_date', now())
            ->firstOrNew();

        return view('attendance', compact('attendance'));
    }
}
