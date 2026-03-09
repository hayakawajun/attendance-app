@extends('layouts.app')

@section('title','申請一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/request_list.css')}}">
@endsection

@section('content')
<div class="wrapper">
    <div class="title">
        <div class="marker"></div>
        <h1 class="content-title">申請一覧</h1>
    </div>

    <div class="tab-panel">
        <input class="pending__tab-switch" type="radio" name="tab__name" id="tab__pending" checked>
        <label class="tab-label pending" for="tab__pending">承認待ち</label>

        <input class="approved__tab-switch" type="radio" name="tab__name" id="tab__approved">
        <label class="tab-label approved" for="tab__approved">承認済み</label>

        <div class="border"></div>

        <div class="pending__tab-content">

            @if(!$pendingRequests)
                <p class="information">承認待ちの申請はありません。</p>
            @else
                <table class="request-list__table">
                    <colgroup>
                        <col class="col__status">
                        <col class="col__name">
                        <col class="col__date">
                        <col class="col__reason">
                        <col class="col__date">
                        <col class="col__detail">
                    </colgroup>
                    <thead>
                        <tr class="request-list__table-header">
                            <th class="title__status">状態</th>
                            <th class="title__name">名前</th>
                            <th class="title">対象日時</th>
                            <th class="title">申請理由</th>
                            <th class="title">申請日時</th>
                            <th class="title__detail">詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingRequests as $request)
                            <tr class="request-list__table-row">
                                <td class="status">承認<br class="smart-phone__br">待ち</td>
                                <td class="name">{{ $request->user->name }}</td>
                                <td class="date">{{ $request->target_date->format('Y') }}
                                    <span class="smart-phone__year--slash">/</span>
                                    <span class="smart-phone__year--chinese">年</span>
                                    <br class="smart-phone__br">{{ $request->target_date->format('m/d') }}</td>
                                <td class="reason">{{ Str::limit($request->reason,10,'...') }}</td>
                                <td class="date">{{ $request->requested_at->format('Y') }}
                                    <span class="smart-phone__year--slash">/</span>
                                    <span class="smart-phone__year--chinese">年</span>
                                    <br class="smart-phone__br">{{ $request->requested_at->format('m/d') }}</td>
                                <td class="detail">
                                    @auth('admin')
                                        <a class="detail__link" href="{{ route('admin.show_request',['attendance_correct_request_id' => $request->id ]) }}">詳細</a>
                                    @elseauth
                                        <a class="detail__link" href="{{ route('detail.show',['id' => $request->attendance_id ?? 0,'date' => $request->target_date->format('Y-m-d') ]) }}">詳細</a>
                                    @endauth
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            @endif
        </div>

        <div class="approved__tab-content">
            @if(!$approvedRequests)
                <p class="information">承認済みの申請はありません。</p>
            @else
                <table class="request-list__table">
                    <colgroup>
                        <col class="col__status">
                        <col class="col__name">
                        <col class="col__date">
                        <col class="col__reason">
                        <col class="col__date">
                        <col class="col__detail">
                    </colgroup>
                    <thead>
                        <tr class="request-list__table-header">
                            <th class="title__status">状態</th>
                            <th class="title__name">名前</th>
                            <th class="title">対象日時</th>
                            <th class="title">申請理由</th>
                            <th class="title">申請日時</th>
                            <th class="title__detail">詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($approvedRequests as $request)
                            <tr class="request-list__table-row">
                                <td class="status">承認<br class="smart-phone__br">済み</td>
                                <td class="name">{{ $request->user->name }}</td>
                                <td class="date">{{ $request->target_date->format('Y') }}
                                    <span class="smart-phone__year--slash">/</span>
                                    <span class="smart-phone__year--chinese">年</span>
                                    <br class="smart-phone__br">{{ $request->target_date->format('m/d') }}</td>
                                <td class="reason">{{ Str::limit($request->reason,10,'...') }}</td>
                                <td class="date">{{ $request->requested_at->format('Y') }}
                                    <span class="smart-phone__year--slash">/</span>
                                    <span class="smart-phone__year--chinese">年</span>
                                    <br class="smart-phone__br">{{ $request->requested_at->format('m/d') }}</td>
                                <td class="detail">
                                    @auth('admin')
                                        <a class="detail__link" href="{{ route('admin.show_request',['attendance_correct_request_id' => $request->id ]) }}">詳細</a>
                                    @elseauth
                                        <a class="detail__link" href="{{ route('detail.show',['id' => $request->attendance_id ?? 0,'date' => $request->target_date->format('Y-m-d') ]) }}">詳細</a>
                                    @endauth
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            @endif
        </div>

    </div>
</div>
@endsection