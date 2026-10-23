@extends('layouts.app')

@section('content')
{{-- 旧と同じ。v2 は自店舗の private channel を購読するため店舗 ID を渡す (表示端末のログインから) --}}
<customer-display-component :store-id="{{ (int) auth('display')->id() }}"></customer-display-component>
@endsection
