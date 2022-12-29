@extends('layouts.app')

@section('content')
    <return-component
        :customer="{{ json_encode($customer) }}"
        :orders="{{ json_encode($orders) }}"
        :token="{{ json_encode(Utility::fetchApiToken()) }}"
        :info="{{ json_encode(session('customer_info') ?? []) }}"
    ></return-component>
</div>

@endsection
