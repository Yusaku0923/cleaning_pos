@extends('layouts.app')

@section('content')
    <payment-confimation-component
        :customer="{{ json_encode($customer) }}"
        :invoices="{{ json_encode($invoices) }}"
        :token="{{ json_encode(Utility::fetchApiToken()) }}"
    ></payment-confimation-component>
@endsection
