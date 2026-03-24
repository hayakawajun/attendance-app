@extends('layouts.app')

@section('title','スタッフ別勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_list.css')}}">
@endsection

@section('content')
<div class="wrapper">
    <div class="title">
        <div class="marker"></div>
        <h1 class="content-title">{{ $staff->name }}さんの勤怠</h1>
    </div>

    <div class="date-display">
        <div class="previous">
            <a class="moving-date" href="{{ route('admin.individual_index', ['id' => $staff->id, 'year' => $prevDate->year, 'month' => $prevDate->month]) }}">
                <img src="{{ asset('image/left_arrow.png') }}" alt="<<">
                前月
            </a>
        </div>
        <div class="target__select" id="month__picker--trigger">
            <img src="{{ asset('image/calendar.png') }}" alt="calendar">
            <span class="target-date">{{ $year }}/{{ sprintf('%02d',$month) }}</span>
            <input class="target__select--input month" type="month" id="month__picker" data-url="{{ isset($staff) ? route('admin.individual_index',['id' => $staff->id ]) : '/attendance/list' }}" value="{{ $year }}-{{ sprintf('%02d',$month) }}">
        </div>
        <div class="next">
            <a class="moving-date" href="{{ route('admin.individual_index', ['id' => $staff->id, 'year' => $nextDate->year, 'month' => $nextDate->month]) }}">
                翌月
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
                <tr class="attendance__table-header">
                    <th class="date">日付</th>
                    <th class="time">出勤</th>
                    <th class="time">退勤</th>
                    <th class="time">休憩</th>
                    <th class="time">合計</th>
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
                                <a class="detail__link" href="{{ route('admin.show_detail',['id' => $day['attendance']->id ]) }}">詳細</a>
                                @if($day['latestRequest']?->status === $statusPending)
                                    <span class="pending">申請中</span>
                                @endif
                            </td>
                        @else
                            <td class="time"></td>
                            <td class="time"></td>
                            <td class="time"></td>
                            <td class="time"></td>
                            <td class="detail">
                                <a class="detail__link" href="{{ route('admin.show_detail',['id' => 0,'user_id' => $staff->id, 'date' => $day['date']->format('Y-m-d')]) }}">詳細</a>
                                @if($day['latestRequest']?->status === $statusPending)
                                    <span class="pending">申請中</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>

    <div class="export__csv">
        <a class="export__button" href="{{ route('admin.export_csv',['id' => $staff->id,'year' => $year,'month' => $month]) }}">CSV出力</a>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('js/month_picker.js') }}"></script>
@endsection