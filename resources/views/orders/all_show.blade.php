@extends('layouts.app')

@section('content')

<all-history-component
    :info="{{ json_encode(session('customer_info') ?? []) }}"
    :token="{{ json_encode(Utility::fetchApiToken()) }}"
></all-history-component>

@endsection
