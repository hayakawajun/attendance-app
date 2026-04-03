@extends('layouts.app')

@section('title','勤怠一覧(管理者用)')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_list.css')}}">
@endsection

@section('content')
    <div class="wrapper">

        <div class="title">
            <div class="marker"></div>
            <h1 class="content-title">{{ $date->format('Y年n月j日') }}の勤怠</h1>
        </div>

        <div class="date-display">
            <div class="previous">
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
                    'year'  => $date->copy()->addDay()->year,
                    'month' => $date->copy()->addDay()->month,
                    'day'   => $date->copy()->addDay()->day
                    ]) }}">
                    翌日
                    <img src="{{ asset('image/right_arrow.png') }}" alt=">>">
                </a>
            </div>
        </div>

        <div class="index">
            <table class="attendance__table">
                <colgroup>
                    <col class="col__left">
                    <col class="col__center" span="4">
                    <col class="col__right">
                </colgroup>
                <thead>

                    @if(session('success'))
                        <tr class="message__table-row success">
                            <td class="message" colspan="6">{{ session('success') }}</td>
                        </tr>
                    @elseif(session('error'))
                        <tr class="message__table-row error">
                            <td class="message" colspan="6">{{ session('error') }}</td>
                        </tr>
                    @endif

                    <tr class="attendance__table-header">
                        <th class="name">名前</th>
                        <th class="time">出勤</th>
                        <th class="time">退勤</th>
                        <th class="time">休憩</th>
                        <th class="time">合計</th>
                        <th class="detail">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $attendance)
                        <tr class="attendance__table-row">
                            <td class="name">{{ $attendance->user->name }}</td>
                            <td class="time">{{ $attendance->clock_in->format('H:i') }}</td>
                            <td class="time">{{ $attendance->clock_out?->format('H:i') }}</td>
                            <td class="time">{{ $attendance->total_rest_time }}</td>
                            <td class="time">{{ $attendance->total_working_time }}</td>
                            <td class="detail">
                                <a class="detail__link" href="{{ route('admin.show_detail',['id' => $attendance->id ]) }}">詳細</a>

                                @if($attendance->attendanceRequests->where('status', $statusPending)->isNotEmpty())
                                    <span class="pending">申請中</span>
                                @endif

                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
        </div>

    </div>
@endsection

@section('script')
    <script src="{{ asset('js/day_picker.js') }}"></script>
@endsection