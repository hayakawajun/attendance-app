<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;

class AdminController extends Controller
{
    public function show()
    {
        $admin = Auth::user();

        return view('admin.admin_attendance_list',compact('admin'));
    }
}
