@extends('layouts.app')

@section('content')
{{-- 顧客表示の端末がログインしていない (またはログインが切れた)。お客様に POS のログイン画面を見せず、スタッフ向けに案内する。
     文言は CustomerDisplayComponent.vue の案内と同じ --}}
<div class="d-flex flex-column justify-content-center align-items-center text-center bg-white text-dark px-4" style="position: fixed; inset: 0; z-index: 2000;">
    <div class="fw-bold mb-4" style="font-size: 2.5rem;">顧客表示の再ログインが必要です（スタッフ向け）</div>
    <div style="font-size: 1.25rem;">管理者に顧客表示のログイン URL を発行してもらい（php artisan display:issue-token）、この端末で開いてください。</div>
</div>
@endsection
