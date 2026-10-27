@extends('layouts.app')

@section('content')
    <payment-component
        :customer="{{ json_encode($customer) }}"
        :orders="{{ json_encode($orders) }}"
        :info="{{ json_encode($customer_info) }}"
    ></payment-component>
@endsection
