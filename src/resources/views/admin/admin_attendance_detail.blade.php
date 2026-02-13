@extends('layouts.app')

@section('title','勤怠詳細')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_detail.css')}}">
@endsection

@section('content')
<div class="wrapper">
    <div class="title">
        <div class="marker"></div>
        <h1 class="content-title">勤怠詳細</h1>
    </div>



</div>
@endsection

@section('script')
<script src="{{ asset('js/input_time_helper.js') }}">></script>
@endsection