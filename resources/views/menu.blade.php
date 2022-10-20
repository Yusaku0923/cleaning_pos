@extends('layouts.app')

@section('content')
<div class="col-12">
    <div class="col-12 d-flex justify-content-around mx-auto">
        <a class="card col-5 h3 p-4 cbtn cbtn-blue text-center">
            担当者マスタ
        </a>
        <a href="{{ route('clothes.create') }}" class="card col-5 h3 p-4 cbtn cbtn-blue text-center">
            商品マスタ
        </a>
    </div>
</div>
@endsection
