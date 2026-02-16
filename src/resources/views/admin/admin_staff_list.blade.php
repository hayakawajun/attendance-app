@extends('layouts.app')

@section('title','スタッフ一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_staff_list.css')}}">
@endsection

@section('content')
<div class="wrapper">
    <div class="title">
        <div class="marker"></div>
        <h1 class="content-title">スタッフ一覧</h1>
    </div>

    <div class="index">

        <table class="staff-list__table">
            <colgroup>
                <col class="col__name">
                <col class="col__email">
                <col class="col__detail">
            </colgroup>
            <thead>
                <tr class="staff-list__table-header">
                    <th class="title__name">名前</th>
                    <th class="title__email">メールアドレス</th>
                    <th class="title__detail">月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staffs as $staff)
                    <tr class="staff-list__table-row">
                        <td class="name">{{ $staff->name }}</td>
                        <td class="email">{{ $staff->email }}</td>
                        <td class="detail">
                            <a class="detail__link" href="">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>

    </div>
</div>
@endsection