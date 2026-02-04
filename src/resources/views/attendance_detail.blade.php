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

    @if($pendingRequest)
        <p>申請中です。</p>
    @else

    <form class="application__form" action="{{ route('attendance.request') }}" method="post">
        @csrf

        @if($attendance)
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
        @endif
        <input type="hidden" name="work_date" value="{{ $date }}">

        <div class="content">
            <table class="detail__table">
                <colgroup>
                    <col class="col__left">
                    <col class="col__center" span="3">
                    <col class="col__right">
                </colgroup>
                <tr class="detail__table-row">
                    <td class="label">名前</td>
                    <td class="user-name" colspan="3">{{ $name }}</td>
                </tr>
                <tr class="detail__table-row">
                    <td class="label">日付</td>
                    <td class="parameter">{{ $date->year }}年</td>
                    <td></td>
                    <td class="parameter">{{ $date->month }}月{{ $date->day }}日</td>
                    <td></td>
                </tr>
                <tr class="detail__table-row">
                    <td class="label">出勤・退勤</td>
                    <td class="parameter">
                        <input class="time__input js__input-time--helper" type="text" maxlength="5" name="attendance_start_time" value="{{ $attendance ? $attendance->clock_in->format('H:i') : '' }}" placeholder="00:00">
                    </td>
                    <td class="parameter">〜</td>
                    <td class="parameter">
                        <input class="time__input js__input-time--helper" type="text" maxlength="5" name="attendance_end_time" value="{{ $attendance ? $attendance->clock_out->format('H:i') : '' }}" placeholder="00:00">
                    </td>
                    <td></td>
                </tr>
                @if($attendance)
                    @foreach($attendance->rests as $rest)
                        <tr class="detail__table-row">
                            <td class="label">{{ $loop->first ? '休憩' : '休憩'.$loop->iteration }}</td>
                            <td class="parameter">
                                <input class="time__input js__input-time--helper" type="text" maxlength="5" name="rests[{{ $rest->id }}][start_time]" value="{{ $rest->start_time->format('H:i') }}" placeholder="00:00">
                            </td>
                            <td class="parameter">〜</td>
                            <td class="parameter">
                                <input class="time__input js__input-time--helper" type="text" maxlength="5" name="rests[{{ $rest->id }}][end_time]" value="{{ $rest->end_time->format('H:i') }}" placeholder="00:00">
                            </td>
                            <td></td>
                        </tr>
                    @endforeach
                @endif
                @php
                    $restCount = $attendance ? $attendance->rests->count() : 0;
                    $nextNumber = $restCount + 1;
                @endphp
                <tr class="detail__table-row">
                    <td class="label">{{ $nextNumber === 1 ? '休憩' : '休憩'.$nextNumber }}</td>
                    <td class="parameter">
                        <input class="time__input js__input-time--helper" type="text" maxlength="5" name="new_rests[0][start_time]" value="" placeholder="00:00">
                    </td>
                    <td class="parameter">〜</td>
                    <td class="parameter">
                        <input class="time__input js__input-time--helper" type="text" maxlength="5" name="new_rests[0][end_time]" value="" placeholder="00:00">
                    </td>
                    <td></td>
                </tr>
                <tr class="detail__table-row">
                    <td class="label">備考</td>
                    <td colspan="3">
                        <textarea class="text" name="reason"></textarea>
                    </td>
                    <td></td>
                </tr>
            </table>
        </div>
        <button class="submit__button" type="submit">修正</button>
    </form>

    @endif


</div>
@endsection

@section('script')
<script src="{{ asset('js/input_time_helper.js') }}">></script>
@endsection