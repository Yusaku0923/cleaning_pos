@extends('layouts.app')

@section('content')

<history-component
    :customer="{{ json_encode($customer) }}"
    :initial_orders="{{ json_encode($orders) }}"
    :token="{{ json_encode($token) }}"
></history-component>

@endsection
