@extends('layouts.app')

@section('title','勤怠登録')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('content')
<form action="/logout" method="post">
    @csrf
        <button type="submit">一般ユーザーログアウト</button>
    </form>
@endsection