@extends('layouts.app')

@section('title','勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_list.css')}}">
@endsection

@section('content')
<div class="wrapper">
    <div class="title">
        <div class="marker"></div>
        <h1 class="content-title">勤怠一覧</h1>
    </div>

    <div class="months">
        <div class="preview-month">
            <p>←前月</p>
        </div>
        <div class="target-month">📅2023/06</div>
        <div class="next-month">
            <p>翌月→</p>
        </div>
    </div>

    <div class="index">
        <table class="attendance__table">
            <colgroup>
                <col class="col__left">
                <col span="4">
                <col class="col__right">
            </colgroup>
            <thead>
                <tr class="attendance__table-header">
                    <th class="date">日付</th>
                    <th class="time">出勤</th>
                    <th class="time">退勤</th>
                    <th class="time">休憩</th>
                    <th class="time">勤務</th>
                    <th class="detail">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($calendar as $day)
                    <tr class="attendance__table-row">
                        <td class="date">{{ $day['date']->isoFormat('MM/DD(ddd)') }}</td>
                        @if($day['attendance'])
                            <td class="time">{{ $day['attendance']->clock_in?->format('H:i') }}</td>
                            <td class="time">{{ $day['attendance']->clock_out?->format('H:i') }}</td>
                            <td class="time">{{ $day['attendance']->total_rest_time }}</td>
                            <td class="time">{{ $day['attendance']->total_working_time }}</td>
                            <td class="detail">
                                <a class="detail__link" href="">詳細</a>
                            </td>
                        @else
                            <td class="time"></td>
                            <td class="time"></td>
                            <td class="time"></td>
                            <td class="time"></td>
                            <td class="detail">
                                <a class="detail__link" href="">詳細</a>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>
</div>
@endsection

@section('script')
@endsection