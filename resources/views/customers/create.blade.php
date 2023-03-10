@extends('layouts.app')

@section('content')
<div class="col-12">
    <div class="col-10 mx-auto">
        <div class="card card-border col-12 py-2 mt-2 h4 text-center">
            会　員　情　報　入　力
        </div>
        <div class="card card-border p-5 ">
            <form method="POST" action="{{ route('customer.store') }}">
                {{ csrf_field() }}
                @php
                    $cutoff = range(0, 28);
                    $cutoff[99] = '末';
                    $cutoff[0] = '日付を選択してください';
                @endphp
                <customer-create-component
                    :cutoff="{{ json_encode($cutoff) }}"
                ></customer-create-component>
                @foreach ($errors->all() as $error)
                    <li>{{$error}}</li>
                @endforeach
                <div class="form-group mt-5 d-flex justify-content-between">
                    <a class="btn btn-secondary fs-20 px-5 py-3 card-border" href="{{ route('home') }}">ホームへ戻る</a>
                    <button class="btn btn-primary ml-5 fs-20 px-5 py-3 card-border" type="button" onclick="submit();">登録する</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
