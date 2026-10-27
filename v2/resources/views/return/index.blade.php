@extends('layouts.app')

@section('content')
    <return-component
        :customer="{{ json_encode($customer) }}"
        :orders="{{ json_encode($orders) }}"
        :info="{{ json_encode($customer_info) }}"
    ></return-component>

@endsection
