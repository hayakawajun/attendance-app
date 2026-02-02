@extends('layouts.app')

@section('title','勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css')}}">
@endsection

@section('content')
<form action="/admin/logout" method="post">
    @csrf
        <button type="submit">管理者ログアウト</button>
    </form>
@endsection