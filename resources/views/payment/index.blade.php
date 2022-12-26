@extends('layouts.app')

@section('content')
    <payment-component
        :customer="{{ json_encode($customer) }}"
        :orders="{{ json_encode($orders) }}"
        :info="{{ json_encode( session('customer_info')) }}"
        :token="{{ json_encode(Utility::fetchApiToken()) }}"
    ></payment-component>
@endsection
