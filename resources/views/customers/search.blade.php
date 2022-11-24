@extends('layouts.app')

@section('content')

<customer-search-component
    :token='{{ json_encode($auth_token) }}'
    :manager_id='{{ json_encode($manager_id) }}'
></customer-search-component>

@endsection