@extends('layouts.app')

@section('content')

<history-component
    :customer="{{ json_encode($customer) }}"
    :initial_orders="{{ json_encode($orders) }}"
    :info="{{ json_encode(session('customer_info') ?? []) }}"
    :token="{{ json_encode(Utility::fetchApiToken()) }}"
></history-component>

@endsection
