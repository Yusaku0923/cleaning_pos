@extends('layouts.app')

@section('content')

<order-component 
    :manager_id="{{ $manager_id }}"
    :customer_id="{{ $customer_id }}"
    :customer_name="{{ json_encode($customer_name) }}"
    :is_invoice="{{ json_encode((boolean)$is_invoice) }}"
    :categories='{{ json_encode($list) }}'
    :often_ordered='{{ json_encode($often_ordered) }}'
    :tax='{{ $tax }}'
    :token='{{ json_encode($auth_token) }}'
></order-component>
@endsection
