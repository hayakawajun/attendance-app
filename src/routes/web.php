<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRequestController;
use App\Http\Controllers\RequestListController;

//管理者用のログイン・ログアウトルーティング
Route::get('/admin/login', function(){
    return view('auth.admin_login');})
    ->name('admin.login');
Route::post('/admin/login',[AuthenticatedSessionController::class,'store'])
    ->middleware(['guest:admin']);
Route::post('/admin/logout',[AuthenticatedSessionController::class,'destroy'])
    ->name('admin.logout');

//管理者用ページを開く際のミドルウェアグループ
Route::middleware(['auth:admin'])->group(function()
{
    Route::get('/admin/attendance/list',[AdminController::class,'show'])
        ->name('admin.show');
});

//一般スタッフ用ページを開く際のミドルウェアグループ
Route::middleware(['auth','verified'])->group(function()
{
    Route::get('/attendance',[AttendanceController::class,'show'])
        ->name('attendance.show');
    Route::post('/clock_in',[AttendanceController::class,'clockIn'])
        ->name('attendance.clock_in');
    Route::post('/rest_start',[AttendanceController::class,'restStart'])
        ->name('attendance.rest_start');
    Route::post('/rest_end',[AttendanceController::class,'restEnd'])
        ->name('attendance.rest_end');
    Route::post('/clock_out',[AttendanceController::class,'clockOut'])
        ->name('attendance.clock_out');
    Route::get('attendance/list/{year?}/{month?}',[AttendanceController::class,'index'])
        ->name('attendance.index');

    Route::get('attendance/detail/{id}',[AttendanceRequestController::class,'show'])
        ->name('detail.show');
    Route::post('attendance/request',[AttendanceRequestController::class,'store'])
        ->name('attendance.request');

    Route::get('/stamp_correction_request/list',[RequestListController::class,'index'])
        ->name('request.list');
});