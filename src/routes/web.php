<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;

//管理者用のログイン・ログアウトルーティング
Route::get('/admin/login', function(){
    return view('auth.admin_login');
})->name('admin.login');

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
});