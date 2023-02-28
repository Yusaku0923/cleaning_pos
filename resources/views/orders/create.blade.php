@extends('layouts.app')

@section('content')

<order-component 
    :manager_id="{{ $manager_id }}"
    :customer_id="{{ $customer_id }}"
    :customer_name="{{ json_encode($customer_name) }}"
    :is_invoice="{{ json_encode((boolean)$is_invoice) }}"
    :check_return="{{ json_encode((boolean)$check_return) }}"
    :categories='{{ json_encode($list) }}'
    :often_ordered='{{ json_encode($often_ordered) }}'
    :latest_tag='{{ $latest_tag }}'
    :tax='{{ $tax }}'
    :token="{{ json_encode(Utility::fetchApiToken()) }}"
></order-component>
@endsection
