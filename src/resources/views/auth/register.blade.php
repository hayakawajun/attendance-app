@extends('layouts.authentication')

@section('title','会員登録')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/authentication.css')}}">
@endsection

@section('content')
<form class="form__field" action="">
    @csrf
    <h1 class="form__heading">会員登録</h1>

    <div class="form__group">
        <label class="input-label" for="name">名前</label>
        <input class="input-window" type="text" name="name" id="name" value="{{ old('name') }}">
    </div>

    <div class="form__group">
        <label class="input-label" for="email">メールアドレス</label>
        <input class="input-window" type="text" name="email" id="email" value="{{ old('email') }}">
    </div>

    <div class="form__group">
        <label class="input-label" for="password">パスワード</label>
        <input class="input-window" type="password" name="password" id="password">
    </div>

    <div class="form__group">
        <label class="input-label" for="password-confirmation">パスワード確認</label>
        <input class="input-window" type="password" name="password-confirmation" id="password-confirmation" autocomplete="new-password">
    </div>

    <button class="submit-button">登録する</button>
    <a class="moving-page" href="/login">ログインはこちら</a>
</form>
@endsection