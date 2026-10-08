@extends('layouts.app')

@section('content')
<div class="login-page">
    <div class="login-card card">
        <div class="card-body p-4">
            <h1 class="login-title">ログイン</h1>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input id="email" type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <span class="invalid-feedback" role="alert"><strong>メールアドレスまたはパスワードが正しくありません。</strong></span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">パスワード</label>
                    <input id="password" type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                    @error('password')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">ログイン</button>
            </form>
        </div>
    </div>
</div>
@endsection
