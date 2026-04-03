@extends('layouts.app')

@section('title','勤怠登録')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('content')
    <div class="attendance__wrapper">

        <div class="attendance__group">
            <p class="attendance-status">{{ $attendance->status }}</p>
            <p class="today">{{ $attendance->today_display }}</p>
            <p class="clock" id="current-time">
                {{ now()->format('H') }}<span class="clock-separator">:</span>{{ now()->format('i') }}
            </p>
            <div class="attendance-register">

                @if($attendance->status === '勤務外')
                    <form class="attendance-register__form" action="{{ route('attendance.clock_in') }}" method="post">
                    @csrf
                        <button class="attendance__submit-btn work">出勤</button>
                    </form>

                @elseif($attendance->status === '出勤中')
                    <form class="attendance-register__form" action="{{ route('attendance.clock_out') }}" method="post">
                    @csrf
                        <button class="attendance__submit-btn work">退勤</button>
                    </form>

                    <form class="attendance-register__form" action="{{ route('attendance.rest_start') }}" method="post">
                    @csrf
                        <button class="attendance__submit-btn rest">休憩入</button>
                    </form>

                @elseif($attendance->status === '休憩中')
                    <form class="attendance-register__form" action="{{ route('attendance.rest_end') }}" method="post">
                    @csrf
                        <button class="attendance__submit-btn rest">休憩戻</button>
                    </form>

                @elseif($attendance->status === '退勤済')
                    <p class="clock-out__message">お疲れ様でした。</p>
                @endif

            </div>

            @if(session('success'))
                <div class="session">
                    <p class="session-message success">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="session">
                    <p class="session-message error">{{ session('error') }}</p>
                </div>
            @endif

        </div>

    </div>
@endsection

@section('script')
    <script src="{{ asset('js/current_time.js') }}"></script>
@endsection