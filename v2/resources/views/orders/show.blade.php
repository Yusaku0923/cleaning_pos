@extends('layouts.app')

@section('content')

<history-component
    :customer="{{ json_encode($customer) }}"
    :initial_orders="{{ json_encode($orders) }}"
    :info="{{ json_encode($customer_info) }}"
></history-component>

@endsection
