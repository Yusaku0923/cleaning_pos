@extends('layouts.app')

@section('content')
    <payment-component
        :customer="{{ json_encode($customer) }}"
        :orders="{{ json_encode($orders) }}"
        :token="{{ json_encode($token) }}"
    ></payment-component>
@endsection
