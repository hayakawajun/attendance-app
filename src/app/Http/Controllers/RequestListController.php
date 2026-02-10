<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceRequest;

class RequestListController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $name = $user->name;

        $pendingRequests = AttendanceRequest::where('user_id',$user->id)
            ->where('status', AttendanceRequest::STATUS_PENDING)
            ->orderBy('requested_at','desc')
            ->get();

        return view('request_list',compact('name','pendingRequests'));
    }
}
