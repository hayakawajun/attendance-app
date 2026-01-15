<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

Route::get('/admin/login', function(){
    return view('auth.admin_login');
})->name('admin.login');

Route::middleware(['auth','verified'])->group(function()
{
    Route::get('/attendance',
        [AttendanceController::class,'show'])->name('attendance.show');
});