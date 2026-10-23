@extends('layouts.app')

{{-- 仮のホーム。旧 home.blade.php の画面は後の Task で写す --}}
@section('content')
<div class="container">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-secondary">ログアウト</button>
            </form>
        </div>
    </div>
</div>
@endsection
