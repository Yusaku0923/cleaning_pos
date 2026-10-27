@extends('layouts.app')

@section('content')
    <payment-confirmation-component
        :customer="{{ json_encode($customer) }}"
        :invoices="{{ json_encode($invoices) }}"
        :info="{{ json_encode($customer_info) }}"
    ></payment-confirmation-component>
@endsection
