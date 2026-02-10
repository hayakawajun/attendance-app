@extends('layouts.app')

@section('title','勤怠一覧(管理者用)')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_list.css')}}">
    <link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css')}}">
@endsection

@section('content')
<div class="wrapper">
    <div class="title">
        <div class="marker"></div>
        <h1 class="content-title">{{ $date->format('Y年n月j日') }}の勤怠</h1>
    </div>

    <div class="date-display">
        <div class="preview">
            <a class="moving-date" href="{{ route('admin.day_index', [
                'year' => $date->copy()->subDay()->year,
                'month' => $date->copy()->subDay()->month,
                'day' => $date->copy()->subDay()->day
                ]) }}">
                <img src="{{ asset('image/left_arrow.png') }}" alt="<<">
                前日
            </a>
        </div>
        <div class="target__select" id="date__picker--trigger">
            <img src="{{ asset('image/calendar.png') }}" alt="calendar">
            <span class="target-date">{{ $date->format('Y/m/d') }}</span>
            <input class="target__select--input day" type="date" id="date__picker" value="{{ $date->format('Y-m-d') }}">
        </div>
        <div class="next">
            <a class="moving-date" href="{{ route('admin.day_index', [
                'year' => $date->copy()->addDay()->year,
                'month' => $date->copy()->addDay()->month,
                'day' => $date->copy()->addDay()->day
                ]) }}">
                翌日
                <img src="{{ asset('image/right_arrow.png') }}" alt=">>">
            </a>
        </div>
    </div>

</div>
@endsection

@section('script')
<script src="{{ asset('js/day_picker.js') }}">></script>
@endsection