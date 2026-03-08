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

        $pendingRequests = AttendanceRequest::with('user')
            ->where('user_id',$user->id)
            ->where('status', AttendanceRequest::STATUS_PENDING)
            ->orderBy('requested_at','desc')
            ->get();

        $approvedRequests = AttendanceRequest::with('user')
            ->where('user_id',$user->id)
            ->where('status', AttendanceRequest::STATUS_APPROVED)
            ->orderBy('requested_at','desc')
            ->get();

        return view('request_list',compact('pendingRequests','approvedRequests'));
    }

    public function adminIndex()
    {
        $pendingRequests = AttendanceRequest::with('user')
            ->where('status', AttendanceRequest::STATUS_PENDING)
            ->orderBy('requested_at','desc')
            ->get();

        $approvedRequests = AttendanceRequest::with('user')
            ->where('status', AttendanceRequest::STATUS_APPROVED)
            ->orderBy('requested_at','desc')
            ->get();

        return view('request_list',compact('pendingRequests','approvedRequests'));
    }
}
