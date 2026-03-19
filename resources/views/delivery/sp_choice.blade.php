@extends('layouts.app')
@section('content')
<div class="col-12 d-flex flex-column align-items-center justify-content-center" style="min-height: 70vh; gap: 32px;">
    <h4 class="mb-2">どちらを使いますか？</h4>
    <a href="{{ route('home') }}"
       class="btn btn-primary"
       style="width: 80%; max-width: 320px; padding: 30px; font-size: 22px; border-radius: 16px;">
        レ　ジ
    </a>
    <a href="{{ route('delivery.sp.entry') }}"
       class="btn btn-success"
       style="width: 80%; max-width: 320px; padding: 30px; font-size: 22px; border-radius: 16px;">
        納品書記帳
    </a>
</div>
@endsection
