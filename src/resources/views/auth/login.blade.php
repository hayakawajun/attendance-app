@extends('layouts.authentication')

@section('title','ログイン')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/authentication.css')}}">
@endsection

@section('content')
<form class="form__field" action="/login" method="post">
    @csrf
    <h1 class="form__heading">ログイン</h1>

    <div class="form__group">
        <label class="input-label" for="email">メールアドレス</label>
        @error('email')
            <p class="error-message">{{ $message }}</p>
        @enderror
        <input class="input-window" type="text" name="email" id="email" value="{{ old('email') }}">
    </div>

    <div class="form__group">
        <label class="input-label" for="password">パスワード</label>
        @error('password')
            <p class="error-message">{{ $message }}</p>
        @enderror
        <input class="input-window" type="password" name="password" id="password">
    </div>

    <button class="submit-button">ログインする</button>
    <a class="moving-page" href="/register">会員登録はこちら</a>
</form>
@endsection