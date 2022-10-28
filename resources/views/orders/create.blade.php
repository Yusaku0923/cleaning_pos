@extends('layouts.app')

@section('content')

<order-component 
    :manager_id="{{ $manager_id }}"
    :manager_id="{{ $manager_id }}"
    :customer_id="{{ $customer_id }}"
    :customer_name="{{ $customer_name }}"
    :categories='{{ $list_json }}'
    :tax='{{ $tax }}'
    :token='{{ $auth_token }}'
></order-component>
@endsection
