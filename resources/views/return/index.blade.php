@extends('layouts.app')

@section('content')
    <return-component
        :customer="{{ json_encode($customer) }}"
        :orders="{{ json_encode($orders) }}"
        :token="{{ json_encode($token) }}"
    ></return-component>
</div>

@endsection
