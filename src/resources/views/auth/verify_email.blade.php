@extends('layouts.authentication')

@section('title','メール認証')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/authentication.css')}}">
    <link rel="stylesheet" href="{{ asset('css/verify_email.css')}}">
@endsection

@section('content')
<div class="content">

    <p class="information">登録していただいたメールアドレスに<br class="line-break">認証メールを送付しました。<br>
    メール認証を完了してください。</p>

    <a class="mail__verification" href="http://localhost:8025/">認証はこちらから</a>

    <form action="{{ route('verification.send') }}" method="post">
    @csrf
        <button class="resending-email__button" type="submit">認証メールを再送する</button>
    </form>

</div>
@endsection