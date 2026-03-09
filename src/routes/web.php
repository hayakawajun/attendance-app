<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminApproveController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRequestController;
use App\Http\Controllers\RequestListController;

// 管理者用のログイン・ログアウトルーティング
Route::get('/admin/login', function(){
    return view('auth.admin_login');})
    ->name('admin.login');
Route::post('/admin/login',
    [AuthenticatedSessionController::class,'store'])
    ->middleware(['guest:admin']);
Route::post('/admin/logout',
    [AuthenticatedSessionController::class,'destroy'])
    ->name('admin.logout');

// 管理者用ページを開く際のミドルウェアグループ
Route::middleware(['auth:admin'])->group(function()
{
    Route::get('/admin/attendance/list/{year?}/{month?}/{day?}',
        [AdminController::class,'dayIndex'])
        ->name('admin.day_index');
    Route::get('/admin/staff/list',
        [AdminController::class,'staffIndex'])
        ->name('admin.staff_index');
    Route::get('/admin/attendance/staff/{id}/{year?}/{month?}',
        [AdminController::class,'individualIndex'])
        ->name('admin.individual_index');
    Route::get('/admin/attendance/staff/export/{id}/{year}/{month}',
        [AdminController::class,'exportCsv'])
        ->name('admin.export_csv');

    Route::get('/admin/attendance/{id}',
        [AdminApproveController::class,'showDetail'])
        ->name('admin.show_detail');
    Route::post('/admin/direct_update',
        [AdminApproveController::class,'directUpdate'])
        ->name('admin.direct_update');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminApproveController::class,'showRequest'])
        ->name('admin.show_request');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}',
        [AdminApproveController::class,'approve'])
        ->name('admin.approve');
});

// 一般スタッフ用ページを開く際のミドルウェアグループ
Route::middleware(['auth','verified'])->group(function()
{
    Route::get('/attendance',
        [AttendanceController::class,'show'])
        ->name('attendance.show');
    Route::post('/clock_in',
        [AttendanceController::class,'clockIn'])
        ->name('attendance.clock_in');
    Route::post('/rest_start',
        [AttendanceController::class,'restStart'])
        ->name('attendance.rest_start');
    Route::post('/rest_end',
        [AttendanceController::class,'restEnd'])
        ->name('attendance.rest_end');
    Route::post('/clock_out',
        [AttendanceController::class,'clockOut'])
        ->name('attendance.clock_out');
    Route::get('attendance/list/{year?}/{month?}',
        [AttendanceController::class,'index'])
        ->name('attendance.index');

    Route::get('attendance/detail/{id}',
        [AttendanceRequestController::class,'showDetail'])
        ->name('detail.show');
    Route::post('attendance/request',
        [AttendanceRequestController::class,'apply'])
        ->name('attendance.request');
});

// 申請一覧画面のルーティング。通過している認証ミドルウェアによって呼び出すアクションを振り分け。
Route::get('/stamp_correction_request/list',
    [RequestListController::class,'index'])
    ->middleware(['auth:admin,web','verified','switch.controller'])
    ->name('request.list');