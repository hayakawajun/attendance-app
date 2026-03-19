@extends('layouts.app')

@section('title','修正申請詳細')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance_detail.css')}}">
@endsection

@section('content')
<div class="wrapper">
    <div class="title">
        <div class="marker"></div>
        <h1 class="content-title">勤怠詳細</h1>
    </div>

    @if($attendanceRequest->status === 'pending')
        <form class="application__form" action="{{ route('admin.approve',['attendance_correct_request_id' => $attendanceRequest->id ]) }}" method="post">
            @csrf
            <input type="hidden" name="attendance_id" value="{{ $attendanceRequest->id }}">
    @elseif($attendanceRequest->status === 'approved')
        <div class="application__form">
    @endif

        <div class="content">
            <table class="detail__table">

                <colgroup>
                    <col class="col__left">
                    <col class="col__center" span="3">
                    <col class="col__right">
                </colgroup>

                @if(session('success'))
                    <tr class="message__table-row success">
                        <td class="message" colspan="5">{{ session('success') }}</td>
                    </tr>
                @endif

                <tr class="detail__table-row">
                    <td class="label">名前</td>
                    <td class="user-name" colspan="3">{{ $attendanceRequest->user->name }}</td>
                    <td></td>
                </tr>

                <tr class="detail__table-row">
                    <td class="label">日付</td>
                    <td class="parameter">{{ $attendanceRequest->target_date->format('Y年') }}</td>
                    <td></td>
                    <td class="parameter">{{ $attendanceRequest->target_date->format('n月j日') }}</td>
                    <td></td>
                </tr>

                @if($attendanceRequest->is_deletion)
                    <tr class="detail__table-row">
                        <td class="label">勤怠</td>
                        <td class="reason-text" colspan="3"><span>勤怠情報の削除申請です</span></td>
                        <td></td>
                    </tr>
                @else
                    <tr class="detail__table-row">
                        <td class="label">出勤・退勤</td>
                        <td class="parameter">
                            {{ $attendanceDetail->start_time->format('H:i') }}
                        </td>
                        <td class="parameter">〜</td>
                        <td class="parameter">
                            {{ $attendanceDetail->end_time->format('H:i') }}
                        </td>
                        <td></td>
                    </tr>

                    @if($restDetails)
                        @foreach($restDetails as $restDetail)
                            <tr class="detail__table-row">
                                <td class="label">{{ $loop->first ? '休憩' : '休憩'.$loop->iteration }}</td>
                                <td class="parameter">
                                    {{ $restDetail->start_time ? $restDetail->start_time->format('H:i') : ''}}
                                </td>
                                <td class="parameter">〜</td>
                                <td class="parameter">
                                    {{ $restDetail->end_time ? $restDetail->end_time->format('H:i') : '' }}
                                </td>
                                <td></td>
                            </tr>
                        @endforeach
                    @else
                        <tr class="detail__table-row">
                            <td class="label">休憩</td>
                            <td class="parameter"></td>
                            <td class="parameter">〜</td>
                            <td class="parameter"></td>
                            <td></td>
                        </tr>
                    @endif

                @endif

                <tr class="detail__table-row">
                    <td class="label">備考</td>
                    <td class="reason-text" colspan="3">{{ $attendanceRequest->reason }}</td>
                    <td></td>
                </tr>

            </table>
        </div>

        <div class="form-buttons">
            @if($attendanceRequest->status === 'pending')
                <button class="submit__button update" type="submit">承認</button>
            @elseif($attendanceRequest->status === 'approved')
                <p class="already__approved">承認済み</p>
            @endif
        </div>

    @if($attendanceRequest->status === 'pending')
        </form>
    @elseif($attendanceRequest->status === 'approved')
        </div>
    @endif
</div>
@endsection